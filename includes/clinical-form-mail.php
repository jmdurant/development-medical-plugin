<?php
/**
 * Mail transport for installed clinical forms and the optional i693 adapter. External respondents do
 * not need EHR accounts. CF7 owns validation, spam checks and delivery status.
 * Generated attachments stay in memory: never in the public uploads/web root.
 */
final class GCM_Clinical_Form_Mail {
    private static $pending = array();
    private static $owned_mail = false;

    public static function kinds() {
        return array(
            'developmental_eval' => array('Developmental intake', 'child_dob', null),
            'vanderbilt' => array('Vanderbilt report', 'student_dob', null),
            'teacher_report' => array('Teacher report', 'student_dob', 'teacher_email'),
            'pcp_referral' => array('Referral', 'patient_dob', 'physician_email'),
            'i693' => array('i693 applicant intake', 'dob', null),
        );
    }

    public static function kind($form) {
        if (!$form) {
            return null;
        }
        foreach (self::kinds() as $kind => $spec) {
            if ($kind === 'i693') { continue; }
            $id = (int) get_option('gcm_' . $kind . '_form_id');
            if ($id > 0 && $id === (int) $form->id()) {
                return $kind;
            }
        }
        if ($form->additional_setting('gcm_export', 0)) {
            return 'i693'; // Validation rejects unknown/duplicate adapter settings, not a silent fallback.
        }
        return null;
    }

    /** Normalize only declared fields; CF7 represents select/radio values as arrays. */
    public static function data($form, $posted) {
        $data = array();
        $bytes = 0;
        foreach ($form->scan_form_tags() as $tag) {
            if (!$tag->name) {
                continue;
            }
            $value = $posted[$tag->name] ?? '';
            if (wpcf7_form_tag_supports($tag->type, 'selectable-values')) {
                $values = $value === '' ? array() : (array) $value;
                $multiple = $tag->basetype === 'checkbox' || $tag->has_option('multiple');
                if (!$multiple && count($values) > 1) {
                    throw new UnexpectedValueException('Invalid choice cardinality');
                }
                $allowed = $tag->values;
                if (WPCF7_USE_PIPE) {
                    $allowed = $form->get_pipes($tag->name)->collect_afters();
                }
                foreach ($values as $choice) {
                    if (!is_string($choice) || !in_array($choice, $allowed, true)) {
                        throw new UnexpectedValueException('Invalid choice');
                    }
                }
                $value = implode(', ', $values);
            }
            $limit = $tag->basetype === 'textarea' ? 20000 : 1024;
            if (!is_string($value) || strlen($value) > $limit ||
                preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) {
                throw new UnexpectedValueException('Invalid field shape or length');
            }
            if (($tag->is_required() || $tag->basetype === 'radio') && trim($value) === '') {
                throw new UnexpectedValueException('Missing required field');
            }
            $bytes += strlen($value);
            if ($bytes > 131072) {
                throw new UnexpectedValueException('Submission too large');
            }
            $data[$tag->name] = $value;
        }
        return $data;
    }

    /** Clinical destinations are operator-configured Mail settings, never form answers. */
    private static function fixed_mail_setting($value) {
        $value = str_replace('[_site_admin_email]', (string) get_option('admin_email'), (string) $value);
        if (str_contains($value, '[') || str_contains($value, ']') || str_contains($value, "\0")) {
            throw new UnexpectedValueException('Clinical mail settings must not use submitted mail tags');
        }
        return $value;
    }

    public static function prepare($form, &$abort, $submission) {
        $kind = self::kind($form);
        if (!$kind || $abort) {
            return;
        }
        try {
            $spec = self::kinds()[$kind];
            $data = self::data($form, $submission->get_posted_data());
            $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $data[$spec[1]] ?? '');
            if (!$dob || $dob->format('Y-m-d') !== ($data[$spec[1]] ?? '')) {
                throw new UnexpectedValueException('Invalid date of birth');
            }
            $mail = $form->prop('mail');
            foreach (array('recipient', 'sender', 'additional_headers') as $field) {
                $mail[$field] = self::fixed_mail_setting($mail[$field] ?? '');
            }
            if (!wpcf7_is_mailbox_list($mail['recipient']) || !wpcf7_is_mailbox_list($mail['sender'])) {
                throw new UnexpectedValueException('Missing valid clinical mail destination or sender');
            }
            $today = wp_date('m/d/Y');
            $date = $dob->format('m/d/Y');
            switch ($kind) {
                case 'developmental_eval':
                    $xml = generate_developmental_eval_xml($data, $date, $today);
                    $summary = generate_eval_summary($data, $today);
                    break;
                case 'vanderbilt':
                    $scores = calculate_vanderbilt_scores($data);
                    $xml = generate_vanderbilt_xml($data, $scores, $date, $today);
                    $summary = generate_vanderbilt_summary($data, $scores, $today);
                    break;
                case 'teacher_report':
                    $xml = generate_teacher_report_xml($data, $date, $today);
                    $summary = generate_teacher_report_summary($data, $today);
                    break;
                case 'pcp_referral':
                    $xml = generate_pcp_referral_xml($data, $date, $today);
                    $summary = generate_pcp_referral_summary($data, $today);
                    break;
                case 'i693':
                    $attachments = gcm_i693_attachments($form, $data, $date, $today);
                    break;
            }
            if ($kind !== 'i693') {
                $attachments = array($kind . '.xml' => $xml, $kind . '_summary.txt' => $summary);
            }
            // No patient identifiers in envelope/subject; clinical content is attached.
            $mail['subject'] = $spec[0] . ' submission';
            $mail['body'] = 'A form was submitted. Review the attached XML and summary before matching to an EHR record.';
            $mail['attachments'] = '';
            $mail['use_html'] = false;
            // These two existing external workflows retain a generic acknowledgement.
            // Never echo patient information to an unverified submitted email address.
            $ack = array('active' => false);
            if ($spec[2]) {
                $email = $data[$spec[2]] ?? '';
                if (!is_email($email) || preg_match('/[\r\n]/', $email)) {
                    throw new UnexpectedValueException('Invalid acknowledgement email');
                }
                $ack = array_merge($mail, array(
                    'active' => true,
                    'recipient' => $email,
                    'additional_headers' => '',
                    'subject' => 'Form submission acknowledgement',
                    'body' => 'Thank you for submitting a form. The clinic will review it. This automated acknowledgement does not confirm clinical review or an appointment.',
                ));
            }
            $token = bin2hex(random_bytes(16));
            self::$pending[spl_object_id($form)] = array(
                'token' => $token,
                'attachments' => $attachments,
            );
            // In-memory properties only: do not overwrite the operator's saved CF7 configuration.
            $form->set_properties(array('mail' => $mail, 'mail_2' => $ack));
        } catch (Throwable $error) {
            unset(self::$pending[spl_object_id($form)]);
            $abort = true;
            // Do not return/log submitted data, recipient addresses or exception payloads.
            $submission->set_response('The form could not be sent. Please check your entries or contact the clinic.');
        }
    }

    public static function components($components, $form, $mail) {
        $pending = $form ? (self::$pending[spl_object_id($form)] ?? null) : null;
        if ($pending && $mail->name() === 'mail') {
            $components['additional_headers'] .= "\nX-GCM-Attachments: " . $pending['token'];
        }
        return $components;
    }

    /** Use PHPMailer's native string attachment API; CF7 rejects outside-content paths. */
    public static function attach($mailer) {
        foreach ($mailer->getCustomHeaders() as $header) {
            if (strtolower($header[0]) !== 'x-gcm-attachments') {
                continue;
            }
            $mailer->clearCustomHeader('X-GCM-Attachments');
            foreach (self::$pending as $pending) {
                if (!hash_equals($pending['token'], $header[1])) {
                    continue;
                }
                self::$owned_mail = true;
                try {
                    foreach ($pending['attachments'] as $name => $bytes) {
                        $type = str_ends_with($name, '.xml') ? 'application/xml' : (str_ends_with($name, '.xdp') ? 'application/vnd.adobe.xdp+xml' : 'text/plain');
                        if (!$mailer->addStringAttachment($bytes, $name, 'base64', $type)) {
                            throw new \PHPMailer\PHPMailer\Exception('Could not attach clinical form');
                        }
                    }
                } catch (\PHPMailer\PHPMailer\Exception $error) {
                    // phpmailer_init runs before wp_mail's send try/catch. Make
                    // native send fail safely rather than throwing out of that hook.
                    $mailer->clearAttachments();
                    $mailer->clearAllRecipients();
                }
                return;
            }
            $mailer->clearAttachments();
            $mailer->clearAllRecipients();
            return;
        }
    }

    public static function release_mail() {
        global $phpmailer;
        if (self::$owned_mail && $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer) {
            $phpmailer->clearAttachments();
        }
        self::$owned_mail = false;
    }

    public static function finish($form) {
        unset(self::$pending[spl_object_id($form)]);
        self::release_mail();
    }
}

add_action('wpcf7_before_send_mail', array('GCM_Clinical_Form_Mail', 'prepare'), 20, 3);
add_filter('wpcf7_mail_components', array('GCM_Clinical_Form_Mail', 'components'), 20, 3);
add_action('phpmailer_init', array('GCM_Clinical_Form_Mail', 'attach'), 20);
add_action('wp_mail_succeeded', array('GCM_Clinical_Form_Mail', 'release_mail'));
add_action('wp_mail_failed', array('GCM_Clinical_Form_Mail', 'release_mail'));
add_action('wpcf7_submit', array('GCM_Clinical_Form_Mail', 'finish'));
