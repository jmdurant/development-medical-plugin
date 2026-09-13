<?php
declare(strict_types=1);
// Run one case per PHP process: CF7 deliberately has one submission singleton per request.
if (PHP_SAPI !== 'cli' || getenv('PRACTICE_FRESH_INSTALL_TEST') !== 'true') {
    throw new RuntimeException('Disposable CLI fixture required');
}
require '/var/www/html/wp-load.php';
if (!preg_match('~^https://www\.practice-fresh-check-[a-f0-9]{12}\.localhost$~D', get_option('home'))) {
    throw new RuntimeException('Expected retained isolated website');
}
function check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
// Real MIME construction and wp_mail result hooks; transport is intercepted before
// any network/socket/mail()/SMTP call. Never install this class on a live site.
final class GCM_Capture_Mailer extends \PHPMailer\PHPMailer\PHPMailer {
    public array $messages = [];
    public int $failAt = 0;
    public bool $failAttachment = false;
    public function addStringAttachment($string, $filename, $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment') {
        if ($this->failAttachment) { return false; }
        return parent::addStringAttachment($string, $filename, $encoding, $type, $disposition);
    }
    public function send() {
        check($this->preSend(), 'Native MIME construction failed');
        $this->messages[] = array('to' => $this->getToAddresses(), 'subject' => $this->Subject,
            'body' => $this->Body, 'attachments' => $this->getAttachments(),
            'headers' => $this->getCustomHeaders(), 'mime' => $this->getSentMIMEMessage());
        if (count($this->messages) === $this->failAt) {
            throw new \PHPMailer\PHPMailer\Exception('Synthetic transport failure');
        }
        return true;
    }
}
$phpmailer = new GCM_Capture_Mailer(true);
$case = getenv('GCM_FORM_PROBE_CASE') ?: 'teacher_report';
$kinds = array_keys(GCM_Clinical_Form_Mail::kinds());
$kind = in_array($case, $kinds, true) ? $case : 'teacher_report';
if ($case === 'invalid_rating' || $case === 'missing_rating') { $kind = 'vanderbilt'; }
$form = WPCF7_ContactForm::get_instance((int) get_option('gcm_' . $kind . '_form_id'));
check($form !== null, 'Missing native form');
$saved = $form->get_properties();
$mail = $form->prop('mail');
$mail['recipient'] = 'clinical@example.invalid';
$mail['sender'] = 'Website <website@example.invalid>';
$mail['additional_headers'] = '';
$form->set_properties(array('mail' => $mail));
$_POST = array('_wpcf7' => (string) $form->id(), '_wpcf7_locale' => 'en_US',
    '_wpcf7_unit_tag' => 'wpcf7-f' . $form->id() . '-o1');
foreach ($form->scan_form_tags() as $tag) {
    if (!$tag->name) { continue; }
    $value = 'Synthetic ' . $tag->name;
    if ($tag->basetype === 'date') { $value = '2016-04-05'; }
    elseif ($tag->basetype === 'email') { $value = 'external@example.invalid'; }
    elseif ($tag->basetype === 'tel') { $value = '+12025550142'; }
    elseif (wpcf7_form_tag_supports($tag->type, 'selectable-values')) {
        $value = $tag->basetype === 'radio' ? '2 - Often' : $tag->values[0];
    }
    $_POST[$tag->name] = $value;
}
$marker = 'Synthetic & ' . bin2hex(random_bytes(8));
foreach (array('student_first_name', 'child_first_name', 'patient_first_name') as $field) {
    if (isset($_POST[$field])) { $_POST[$field] = $marker; }
}
$_POST['unrecognized_field'] = 'MUST_NOT_LEAK';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Isolated native form acceptance';
$_SERVER['SERVER_NAME'] = parse_url(get_option('home'), PHP_URL_HOST);
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['REQUEST_METHOD'] = 'POST';
if ($case === 'fail_primary') { $phpmailer->failAt = 1; }
if ($case === 'fail_ack') { $phpmailer->failAt = 2; }
if ($case === 'fail_attachment') { $phpmailer->failAttachment = true; }
if ($case === 'array_name') { $_POST['student_first_name'] = array('Synthetic', 'Injected'); }
if ($case === 'array_choice') { $_POST['reading_level'] = array('Below Grade Level', 'At Grade Level'); }
if ($case === 'bad_choice') { $_POST['reading_level'] = 'Injected invalid option'; }
if ($case === 'bad_date') { $_POST['student_dob'] = '2016-02-31'; }
if ($case === 'large_field') { $_POST['additional_comments'] = str_repeat('x', 20001); }
if ($case === 'invalid_rating') { $_POST['q1_fails_attention'] = '3junk'; }
if ($case === 'missing_rating') { unset($_POST['q1_fails_attention']); }
if ($case === 'recipient_tag') {
    $mail['recipient'] = '[teacher_email]';
    $form->set_properties(array('mail' => $mail));
}
if ($case === 'bcc_tag') {
    $mail['additional_headers'] = 'Bcc: [teacher_email]';
    $form->set_properties(array('mail' => $mail));
}
if ($case === 'missing_recipient') {
    $mail['recipient'] = '';
    $form->set_properties(array('mail' => $mail));
}
if ($case === 'site_admin_recipient') {
    $mail['recipient'] = '[_site_admin_email]';
    $form->set_properties(array('mail' => $mail));
}
if ($case === 'unknown_form') {
    // Native unsaved contact form: no option mapping, so our handler must leave it alone.
    $form = WPCF7_ContactForm::get_template();
    $mail['body'] = 'Unrelated form';
    $form->set_properties(array('form' => '[text* name]', 'mail' => $mail, 'mail_2' => array('active' => false)));
    $_POST['name'] = 'Synthetic';
}
$result = $form->submit(array('skip_mail' => false));
$messages = $phpmailer->messages;
$negative = in_array($case, array('array_name', 'array_choice', 'bad_choice', 'bad_date', 'large_field',
    'invalid_rating', 'missing_rating', 'recipient_tag', 'bcc_tag', 'missing_recipient'), true);
if ($case === 'fail_attachment') {
    check(count($messages) === 0 && $result['status'] === 'mail_failed', 'Attachment failure sent mail or reported success');
} elseif ($negative) {
    check(count($messages) === 0 && $result['status'] !== 'mail_sent', 'Invalid form was accepted or sent');
} elseif ($case === 'unknown_form') {
    check($result['status'] === 'mail_sent' && count($messages) === 1 && $messages[0]['attachments'] === array(), 'Unrelated form changed');
} else {
    $external = in_array($kind, array('teacher_report', 'pcp_referral'), true);
    $count = $external && $case !== 'fail_primary' ? 2 : 1;
    check(count($messages) === $count, 'Duplicate or missing primary/acknowledgement');
    check($result['status'] === ($case === 'fail_primary' ? 'mail_failed' : 'mail_sent'), 'Wrong native delivery status');
    $main = $messages[0];
    check($main['to'][0][0] === ($case === 'site_admin_recipient' ? get_option('admin_email') : 'clinical@example.invalid'), 'Wrong clinical recipient');
    check(count($main['attachments']) === 2, 'Missing clinical attachments');
    check(!str_contains($main['subject'] . $main['body'], 'Synthetic'), 'Patient data in subject/body');
    check(!str_contains($main['mime'], 'X-GCM-Attachments'), 'Internal attachment marker leaked');
    foreach ($main['attachments'] as $attachment) {
        check($attachment[5] === true, 'Attachment was written to disk rather than held in memory');
        check(!str_contains($attachment[0], 'MUST_NOT_LEAK'), 'Unrecognized POST field leaked');
    }
    check(simplexml_load_string($main['attachments'][0][0]) !== false, 'Invalid XML attachment');
    check(str_contains($main['attachments'][0][0], esc_xml($marker)), 'Concurrent request content mixed or missing');
    if ($kind === 'vanderbilt') {
        $scores = calculate_vanderbilt_scores(GCM_Clinical_Form_Mail::data($form, WPCF7_Submission::get_instance()->get_posted_data()));
        check($scores['inattention_raw_score'] === 18 && $scores['hyperactivity_raw_score'] === 18, 'Radio arrays were mis-scored');
    }
    if ($kind === 'developmental_eval' || $kind === 'pcp_referral') {
        check(!str_contains($main['attachments'][0][0], 'k^'), 'Unrelated i693 insurance transform ran');
    }
    if (count($messages) === 2) {
        check($messages[1]['to'][0][0] === 'external@example.invalid' && $messages[1]['attachments'] === array(), 'Acknowledgement recipient/attachments incorrect');
        check(!str_contains($messages[1]['body'] . $messages[1]['subject'], 'Synthetic'), 'Clinical data in external acknowledgement');
    }
}
check($phpmailer->getAttachments() === array(), 'Clinical bytes retained in global mailer after submission');
$reloaded = WPCF7_ContactForm::get_instance((int) get_option('gcm_' . $kind . '_form_id'));
check($reloaded->get_properties() === $saved, 'Submission overwrote persisted operator configuration');
echo json_encode(array('passed' => true, 'case' => $case, 'status' => $result['status'],
    'intercepted_mail_count' => count($messages), 'external_delivery' => false,
    'native_mime_and_result_hooks' => true), JSON_THROW_ON_ERROR) . "\n";
