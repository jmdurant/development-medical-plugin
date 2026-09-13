<?php
/** Deployment-managed transport and CF7 spam integration; no vendor patches. */
final class GCM_Site_Services {
    public static function managed($service) {
        return getenv('GCM_' . $service . '_MANAGED') === '1';
    }

    public static function smtp() {
        $host = getenv('GCM_SMTP_HOST') ?: '';
        $port = getenv('GCM_SMTP_PORT') ?: '';
        $security = getenv('GCM_SMTP_SECURITY') ?: '';
        $username = getenv('GCM_SMTP_USERNAME') ?: '';
        $password = getenv('GCM_SMTP_PASSWORD') ?: '';
        $from = getenv('GCM_SMTP_FROM_EMAIL') ?: '';
        // A single operator-selected host, not PHPMailer's host-list/URL syntax.
        if (strlen($host) > 253 || !preg_match('/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/iD', $host) ||
            !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535 ||
            !in_array($security, array('starttls', 'smtps'), true) ||
            $username === '' || strlen($username) > 320 || preg_match('/[\x00-\x1f\x7f]/', $username) ||
            $password === '' || strlen($password) > 4096 || preg_match('/[\x00\r\n]/', $password) ||
            !is_email($from) || preg_match('/[\r\n]/', $from)) {
            return null;
        }
        return compact('host', 'port', 'security', 'username', 'password', 'from');
    }

    public static function mail_gate($result) {
        return self::managed('SMTP') && !self::smtp() ? false : $result;
    }

    public static function mailer($mailer) {
        if (!self::managed('SMTP')) { return; }
        $config = self::smtp();
        // WordPress invokes this hook outside its send try/catch. Do not throw.
        if (!$config) {
            $mailer->clearAllRecipients();
            $mailer->clearAttachments();
            return;
        }
        $mailer->isSMTP();
        $mailer->Host = $config['host'];
        $mailer->Port = (int) $config['port'];
        $mailer->SMTPSecure = $config['security'] === 'smtps' ? 'ssl' : 'tls';
        $mailer->SMTPAutoTLS = false; // Explicit required TLS; never opportunistic downgrade.
        $mailer->SMTPAuth = true;
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
        $mailer->SMTPDebug = 0;
        $mailer->Timeout = 15;
        $mailer->Timelimit = 30;
        $mailer->SMTPKeepAlive = false;
        $mailer->SMTPOptions = array('ssl' => array(
            'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
        ));
        // Use the authorized relay sender for the envelope and header. Reply-To
        // and clinical recipients remain controlled by the native CF7 Mail tab.
        try {
            $mailer->setFrom($config['from'], '', false);
            $mailer->Sender = $config['from'];
        } catch (\PHPMailer\PHPMailer\Exception $error) {
            $mailer->clearAllRecipients();
            $mailer->clearAttachments();
        }
    }

    public static function turnstile() {
        $site = getenv('GCM_TURNSTILE_SITE_KEY') ?: '';
        $secret = getenv('GCM_TURNSTILE_SECRET_KEY') ?: '';
        if (!preg_match('/^[a-zA-Z0-9_-]{20,128}$/D', $site) ||
            !preg_match('/^[a-zA-Z0-9_-]{20,128}$/D', $secret) || (defined('WP_DEBUG') && WP_DEBUG)) {
            return null; // CF7 debug error logging can include its verification secret.
        }
        $test = preg_match('/^[123]x0+/', $site) || preg_match('/^[123]x0+/', $secret);
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($test && (wp_get_environment_type() !== 'local' ||
            !is_string($host) || !preg_match('/(?:^|\.)localhost$/D', $host))) {
            return null; // Public dummy keys must never protect a production form.
        }
        return compact('site', 'secret', 'test');
    }

    public static function sitekey($native) {
        return self::managed('TURNSTILE') ? (self::turnstile()['site'] ?? '') : $native;
    }

    public static function secret($native) {
        return self::managed('TURNSTILE') ? (self::turnstile()['secret'] ?? '') : $native;
    }

    public static function spam_gate($spam, $submission) {
        if (self::managed('TURNSTILE') && (!self::turnstile() || !class_exists('WPCF7_Turnstile'))) {
            $submission->add_spam_log(array('agent' => 'site-configuration', 'reason' => 'Spam protection unavailable.'));
            return true;
        }
        return $spam; // CF7 priority 9 performs the actual server-side verification.
    }

    /** Configuration only: never represents delivery, TLS-handshake or bot-test proof. */
    public static function readiness() {
        $turnstile = self::turnstile();
        return array(
            'smtp_managed' => self::managed('SMTP'),
            'smtp_configured' => self::managed('SMTP') && self::smtp() !== null,
            'turnstile_managed' => self::managed('TURNSTILE'),
            'turnstile_configured' => self::managed('TURNSTILE') && $turnstile !== null && class_exists('WPCF7_Turnstile'),
            'turnstile_test_keys' => (bool) ($turnstile['test'] ?? false),
            'delivery_verified' => false,
        );
    }

    public static function notice() {
        if (!current_user_can('manage_options')) { return; }
        $state = self::readiness();
        if (($state['smtp_managed'] && !$state['smtp_configured']) ||
            ($state['turnstile_managed'] && !$state['turnstile_configured'])) {
            echo '<div class="notice notice-error"><p>Website mail or spam protection is not configured. Managed submissions are blocked. Check deployment SMTP and Turnstile settings; do not use clinical forms until delivery and protection are verified.</p></div>';
        }
    }
}

add_filter('pre_wp_mail', array('GCM_Site_Services', 'mail_gate'), 100);
add_action('phpmailer_init', array('GCM_Site_Services', 'mailer'), 100);
add_filter('wpcf7_turnstile_sitekey', array('GCM_Site_Services', 'sitekey'));
add_filter('wpcf7_turnstile_secret', array('GCM_Site_Services', 'secret'));
add_filter('wpcf7_spam', array('GCM_Site_Services', 'spam_gate'), 8, 2);
add_action('admin_notices', array('GCM_Site_Services', 'notice'));
