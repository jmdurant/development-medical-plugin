<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('PRACTICE_FRESH_INSTALL_TEST') !== 'true') {
    throw new RuntimeException('Disposable CLI fixture required');
}
$case = getenv('GCM_SERVICE_PROBE_CASE') ?: 'valid';
foreach (array('SMTP_MANAGED'=>'1', 'SMTP_HOST'=>'smtp.example.invalid', 'SMTP_PORT'=>'587',
    'SMTP_SECURITY'=>'starttls', 'SMTP_USERNAME'=>'fixture', 'SMTP_PASSWORD'=>'synthetic-password',
    'SMTP_FROM_EMAIL'=>'website@example.invalid', 'TURNSTILE_MANAGED'=>'1',
    'TURNSTILE_SITE_KEY'=>'1x00000000000000000000AA',
    'TURNSTILE_SECRET_KEY'=>'1x0000000000000000000000000000000AA') as $key => $value) {
    putenv('GCM_' . $key . '=' . $value);
}
putenv('WP_ENVIRONMENT_TYPE=' . ($case === 'production_test_keys' ? 'production' : 'local'));
if ($case === 'missing_secret') { putenv('GCM_TURNSTILE_SECRET_KEY='); }
require '/var/www/html/wp-load.php';
if (!preg_match('~^https://www\.practice-fresh-check-[a-f0-9]{12}\.localhost$~D', get_option('home'))) {
    throw new RuntimeException('Expected retained isolated website');
}
// Optional candidate file before publication; the recorded final run omits this.
if ($candidate = getenv('GCM_SERVICE_CANDIDATE')) {
    require $candidate;
    wpcf7_remove_form_tag('turnstile');
    wpcf7_add_form_tag_turnstile();
}
function check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
$calls = 0;
$messages = 0;
$saved = WPCF7::get_option('turnstile');
add_filter('pre_http_request', static function ($pre, $args, $url) use (&$calls, $case) {
    check($url === 'https://challenges.cloudflare.com/turnstile/v0/siteverify', 'Unexpected network request');
    check(array_keys($args['body']) === array('secret', 'response'), 'Clinical data sent to spam provider');
    check($args['body']['response'] === 'XXXX.DUMMY.TOKEN.XXXX', 'Wrong verification token');
    $calls++;
    if ($case === 'network_error') { return new WP_Error('synthetic_network_error'); }
    $body = in_array($case, array('rejected', 'expired'), true)
        ? array('success'=>false, 'error-codes'=>array($case === 'expired' ? 'timeout-or-duplicate' : 'invalid-input-response'))
        : array('success'=>true);
    return array('headers'=>array(), 'body'=>wp_json_encode($body),
        'response'=>array('code'=>200, 'message'=>'OK'), 'cookies'=>array());
}, 10, 3);
add_filter('pre_wp_mail', static function ($result) use (&$messages) {
    if ($result === false) { return false; }
    $messages++;
    return true; // No mail delivery: native CF7 spam/submit/result path is real.
}, 200);

if ($case === 'smtp_config') {
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    GCM_Site_Services::mailer($mailer);
    check($mailer->Mailer === 'smtp' && $mailer->SMTPSecure === 'tls' && $mailer->SMTPAuth, 'Missing authenticated required TLS');
    check(!$mailer->SMTPAutoTLS && !$mailer->SMTPKeepAlive && $mailer->SMTPDebug === 0, 'Unsafe SMTP fallback/debug/reuse');
    check($mailer->SMTPOptions['ssl']['verify_peer'] && $mailer->SMTPOptions['ssl']['verify_peer_name'] &&
        !$mailer->SMTPOptions['ssl']['allow_self_signed'], 'Certificate verification disabled');
    check($mailer->From === 'website@example.invalid' && $mailer->Sender === $mailer->From, 'Relay sender mismatch');
    foreach (array('SMTP_SECURITY'=>'none', 'SMTP_HOST'=>'smtp://bad;second', 'SMTP_PORT'=>'65536',
        'SMTP_USERNAME'=>"bad\nuser", 'SMTP_PASSWORD'=>'', 'SMTP_FROM_EMAIL'=>'not-an-email') as $key=>$value) {
        $old = getenv('GCM_' . $key);
        putenv('GCM_' . $key . '=' . $value);
        check(GCM_Site_Services::smtp() === null, 'Invalid SMTP config accepted: ' . $key);
        check(!wp_mail('nobody@example.invalid', 'Synthetic', 'Synthetic'), 'Invalid config fell back to mail transport');
        putenv('GCM_' . $key . '=' . $old);
    }
    putenv('GCM_SMTP_SECURITY=smtps');
    GCM_Site_Services::mailer($mailer);
    check($mailer->SMTPSecure === 'ssl', 'Implicit TLS not applied');
    check($calls === 0 && $messages === 0, 'Config checks attempted delivery');
} else {
    $form = WPCF7_ContactForm::get_template();
    $mail = $form->prop('mail');
    $mail['recipient'] = 'clinical@example.invalid';
    $mail['sender'] = 'website@example.invalid';
    $form->set_properties(array('form'=>'[text* name][submit "Send"]', 'mail'=>$mail, 'mail_2'=>array('active'=>false)));
    $_POST = array('_wpcf7'=>(string) $form->id(), '_wpcf7_unit_tag'=>'wpcf7-fixture-o1',
        '_wpcf7_locale'=>'en_US', 'name'=>'Synthetic name', '_wpcf7_turnstile_response'=>'XXXX.DUMMY.TOKEN.XXXX');
    if ($case === 'missing_token') { unset($_POST['_wpcf7_turnstile_response']); }
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Isolated native service acceptance';
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = parse_url(get_option('home'), PHP_URL_HOST);
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $result = $form->submit(array('skip_mail'=>false));
    $valid = $case === 'valid';
    check($result['status'] === ($valid ? 'mail_sent' : 'spam'), 'Incorrect native submission status');
    check($messages === ($valid ? 1 : 0), 'Rejected submission reached mail or valid mail missing');
    $offline = in_array($case, array('missing_token', 'missing_secret', 'production_test_keys'), true);
    check($calls === ($offline ? 0 : 1), 'Wrong native verification count');
    if ($valid) {
        check(str_contains($form->form_html(), 'data-sitekey="1x00000000000000000000AA"'), 'Native widget missing');
    }
}
check(WPCF7::get_option('turnstile') === $saved, 'Managed keys written into native DB options');
echo wp_json_encode(array('passed'=>true, 'case'=>$case, 'verification_calls'=>$calls,
    'intercepted_mail_count'=>$messages, 'external_delivery'=>false, 'site_services'=>GCM_Site_Services::readiness())) . "\n";
