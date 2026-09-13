"""Real wp_mail/PHPMailer TLS + AUTH against an in-memory, internal-only receiver.

Only the named retained isolated WordPress fixture is accepted. No external relay,
account, inbox, clinical content, persistent listener or global trust-store change.
"""
import argparse
import base64
import ipaddress
import json
from pathlib import Path
import re
import socket
import ssl
import subprocess
import tempfile
import threading


def run(args, **kwargs):
    return subprocess.run(args, text=True, capture_output=True, check=True, timeout=40, **kwargs).stdout.strip()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--container', required=True)
    args = parser.parse_args()
    if not re.fullmatch(r'practice-fresh-check-[a-f0-9]{12}-wordpress-1', args.container):
        raise ValueError('Only the isolated fresh website is allowed')
    target = json.loads(run(['docker', 'inspect', args.container]))[0]
    if target['Config']['Labels'].get('practice.fresh-install-test') != 'true' or target['HostConfig']['PortBindings']:
        raise ValueError('Fixture label/no published ports required')
    network_name = args.container.removesuffix('-wordpress-1') + '_wordpress-backend'
    network = json.loads(run(['docker', 'network', 'inspect', network_name]))[0]
    if not network['Internal'] or network_name not in target['NetworkSettings']['Networks']:
        raise ValueError('Internal fixture backend required')
    address = network['IPAM']['Config'][0]['Gateway']
    if not ipaddress.ip_address(address).is_private:
        raise ValueError('Receiver must bind a private fixture gateway')
    remote = run(['docker', 'exec', args.container, 'mktemp', '-d', '/tmp/gcm-smtp-XXXXXX'])
    if not re.fullmatch(r'/tmp/gcm-smtp-[A-Za-z0-9]+', remote):
        raise ValueError('Invalid private scratch path')
    results = []
    try:
        with tempfile.TemporaryDirectory(prefix='gcm-smtp-') as scratch:
            folder = Path(scratch)
            for name, san in [('trusted', 'IP:'+address), ('wrong-name', 'DNS:wrong.example.invalid'), ('untrusted', 'IP:'+address)]:
                # Each disposable self-signed CA is trusted only by the test PHP
                # process. Normal WordPress still uses its OS root certificates.
                run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1',
                     '-subj', '/CN=Disposable SMTP fixture', '-addext', 'subjectAltName='+san,
                     '-keyout', str(folder/(name+'.key')), '-out', str(folder/(name+'.pem'))])
                run(['docker', 'cp', str(folder/(name+'.pem')), args.container+':'+remote+'/'+name+'.pem'])
            run(['docker', 'exec', args.container, 'chmod', '755', remote])
            for case in ('starttls', 'smtps', 'untrusted_ca', 'wrong_hostname', 'no_starttls', 'auth_rejected'):
                observations = {'tls': False, 'auth': False, 'data': False}
                context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
                context.minimum_version = ssl.TLSVersion.TLSv1_2
                cert = 'wrong-name' if case == 'wrong_hostname' else 'trusted'
                context.load_cert_chain(folder/(cert+'.pem'), folder/(cert+'.key'))
                with socket.socket() as listener:
                    listener.bind((address, 0))
                    listener.listen(1)
                    listener.settimeout(20)
                    port = listener.getsockname()[1]

                    def receive():
                        connection = None
                        try:
                            connection, _ = listener.accept()
                            connection.settimeout(20)
                            if case == 'smtps':
                                connection = context.wrap_socket(connection, server_side=True)
                                observations['tls'] = True
                            def send(line):
                                connection.sendall(line.encode('ascii')+b'\r\n')
                            def line():
                                value = b''
                                while not value.endswith(b'\r\n'):
                                    part = connection.recv(1)
                                    if not part:
                                        raise EOFError()
                                    value += part
                                    if len(value) > 16384:
                                        raise ValueError('Unexpected oversized fixture line')
                                return value[:-2]
                            send('220 fixture ESMTP')
                            while True:
                                command = line()
                                verb = command.split(b' ', 1)[0].upper()
                                if verb in (b'EHLO', b'HELO'):
                                    send('250-fixture')
                                    if not observations['tls'] and case != 'no_starttls':
                                        send('250-STARTTLS')
                                    send('250 AUTH LOGIN')
                                elif verb == b'STARTTLS':
                                    if case == 'no_starttls':
                                        send('454 TLS unavailable')
                                        continue
                                    send('220 Ready for TLS')
                                    connection = context.wrap_socket(connection, server_side=True)
                                    observations['tls'] = True
                                elif verb == b'AUTH':
                                    if not observations['tls']:
                                        raise ValueError('Credentials attempted without TLS')
                                    send('334 VXNlcm5hbWU6')
                                    username = base64.b64decode(line())
                                    send('334 UGFzc3dvcmQ6')
                                    password = base64.b64decode(line())
                                    if (username, password) != (b'fixture', b'synthetic-password'):
                                        raise ValueError('Unexpected fixture credentials')
                                    observations['auth'] = True
                                    send('535 Authentication rejected' if case == 'auth_rejected' else '235 Authenticated')
                                elif verb in (b'MAIL', b'RCPT'):
                                    send('250 OK')
                                elif verb == b'DATA':
                                    if not observations['tls'] or not observations['auth'] or case == 'auth_rejected':
                                        raise ValueError('Message before authenticated TLS')
                                    send('354 Send data')
                                    body = []
                                    while (value := line()) != b'.':
                                        body.append(value)
                                    if b'Synthetic transport probe' not in b'\n'.join(body):
                                        raise ValueError('Unexpected message content')
                                    observations['data'] = True
                                    send('250 Accepted by local fixture only')
                                elif verb == b'QUIT':
                                    send('221 Bye')
                                    break
                                elif verb == b'RSET':
                                    send('250 OK')
                                else:
                                    raise ValueError('Unexpected SMTP command')
                        except (ssl.SSLError, EOFError, ConnectionError):
                            # Expected when the client's peer/name checks reject TLS.
                            pass
                        except Exception as error:
                            observations['error'] = type(error).__name__
                        finally:
                            if connection:
                                connection.close()

                    worker = threading.Thread(target=receive, daemon=True)
                    worker.start()
                    env = {'GCM_SMTP_MANAGED':'1', 'GCM_SMTP_HOST':address, 'GCM_SMTP_PORT':str(port),
                           'GCM_SMTP_SECURITY':'smtps' if case == 'smtps' else 'starttls',
                           'GCM_SMTP_USERNAME':'fixture', 'GCM_SMTP_PASSWORD':'synthetic-password',
                           'GCM_SMTP_FROM_EMAIL':'website@example.invalid'}
                    ca = 'untrusted' if case == 'untrusted_ca' else cert
                    php = '''<?php
                    require '/var/www/html/wp-load.php';
                    if (getenv('PRACTICE_FRESH_INSTALL_TEST') !== 'true' ||
                        !preg_match('~^https://www\\.practice-fresh-check-[a-f0-9]{12}\\.localhost$~D', get_option('home'))) {
                        throw new RuntimeException('Isolated site required');
                    }
                    $ok = wp_mail('receiver@example.invalid', 'Synthetic transport probe', 'Synthetic transport probe');
                    echo json_encode(array('accepted'=>$ok));
                    '''
                    command = ['docker', 'exec', '-i', '--user', 'www-data']
                    for key, value in env.items():
                        command += ['-e', key+'='+value]
                    command += [args.container, 'php', '-d', 'openssl.cafile='+remote+'/'+ca+'.pem']
                    response = json.loads(run(command, input=php))
                    worker.join(timeout=22)
                    if worker.is_alive() or 'error' in observations:
                        raise RuntimeError('Local SMTP fixture failed: '+case)
                    success = case in ('starttls', 'smtps')
                    if response['accepted'] != success or observations['data'] != success:
                        raise RuntimeError('Incorrect native mail result: '+case)
                    if case in ('untrusted_ca', 'wrong_hostname', 'no_starttls') and observations['auth']:
                        raise RuntimeError('Credentials released without verified TLS: '+case)
                    results.append({'case':case, 'passed':True, **observations, **response})
    finally:
        # Only the exact scratch created and validated above; no user data.
        run(['docker', 'exec', args.container, 'rm', '-f', '--', *[remote+'/'+name+'.pem' for name in ('trusted', 'wrong-name', 'untrusted')]])
        run(['docker', 'exec', args.container, 'rmdir', '--', remote])
    print(json.dumps({'passed':True, 'cases':results, 'external_delivery':False, 'persistent_trust_changes':False}))


if __name__ == '__main__':
    main()
