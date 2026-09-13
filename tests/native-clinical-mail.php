<?php
declare(strict_types=1);
// Run one case per PHP process: CF7 deliberately has one submission singleton per request.
if (PHP_SAPI !== 'cli' || getenv('PRACTICE_FRESH_INSTALL_TEST') !== 'true') {
    throw new RuntimeException('Disposable CLI fixture required');
}
// Exercise the managed services too; no real account or network transport.
foreach (array('SMTP_MANAGED'=>'1', 'SMTP_HOST'=>'smtp.example.invalid', 'SMTP_PORT'=>'587',
    'SMTP_SECURITY'=>'starttls', 'SMTP_USERNAME'=>'fixture', 'SMTP_PASSWORD'=>'synthetic-password',
    'SMTP_FROM_EMAIL'=>'website@example.invalid', 'TURNSTILE_MANAGED'=>'1',
    'TURNSTILE_SITE_KEY'=>'1x00000000000000000000AA',
    'TURNSTILE_SECRET_KEY'=>'1x0000000000000000000000000000000AA') as $key => $value) {
    putenv('GCM_' . $key . '=' . $value);
}
putenv('WP_ENVIRONMENT_TYPE=local');
require '/var/www/html/wp-load.php';
if (!preg_match('~^https://www\.practice-fresh-check-[a-f0-9]{12}\.localhost$~D', get_option('home'))) {
    throw new RuntimeException('Expected retained isolated website');
}
function check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if ($url !== 'https://challenges.cloudflare.com/turnstile/v0/siteverify') {
        return new WP_Error('fixture_network_blocked');
    }
    check(array_keys($args['body']) === array('secret', 'response'), 'Clinical data sent to spam provider');
    return array('headers'=>array(), 'body'=>'{"success":true}', 'response'=>array('code'=>200, 'message'=>'OK'), 'cookies'=>array());
}, 10, 3);
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
if (str_starts_with($case, 'i693')) { $kind = 'i693'; }
if ($case === 'invalid_rating' || $case === 'missing_rating' || $case === 'vanderbilt_low') { $kind = 'vanderbilt'; }
$form = WPCF7_ContactForm::get_instance((int) get_option('gcm_' . $kind . '_form_id'));
if ($kind === 'i693') {
    $form = WPCF7_ContactForm::get_template();
    $form->set_properties(array('additional_settings' => "gcm_export: i693\ngcm_i693_pdf: Intake.pdf\ngcm_i693_location: TEST|Synthetic clinic",
        'form' => '[text* FirstName][text* LastName][text MiddleName][date* dob][text Street]'
            . '[radio ApartmentType "APT" "STE" "FLR"][text Apartment][text CityTown][select State "NY" "NC"]'
            . '[text ZipCode][radio Gender "M" "F"][text CityBirth][text CountryBirth][text ANumber][text USCIS]'
            . '[tel DaytimeTelephone][tel MobileTelephone][email Emailaddres][text find][text examination]'
            . '[text lawyer][text lawyer-name][text lawyer-company][text lawyer-phone][hidden appointmentfield][hidden location][submit "Send"]'));
}
check($form !== null, 'Missing native form');
$saved = $form->get_properties();
$mail = $form->prop('mail');
$mail['recipient'] = 'clinical@example.invalid';
$mail['sender'] = 'Website <website@example.invalid>';
$mail['additional_headers'] = '';
$form->set_properties(array('mail' => $mail));
$_POST = array('_wpcf7' => (string) $form->id(), '_wpcf7_locale' => 'en_US',
    '_wpcf7_turnstile_response' => 'XXXX.DUMMY.TOKEN.XXXX',
    '_wpcf7_unit_tag' => 'wpcf7-f' . $form->id() . '-o1');
foreach ($form->scan_form_tags() as $tag) {
    if (!$tag->name) { continue; }
    $value = 'Synthetic ' . $tag->name;
    if ($tag->basetype === 'date') { $value = '2016-04-05'; }
    elseif ($tag->basetype === 'email') { $value = 'external@example.invalid'; }
    elseif ($tag->basetype === 'tel') { $value = '+12025550142'; }
    elseif (wpcf7_form_tag_supports($tag->type, 'selectable-values')) {
        $value = in_array('2 - Often', $tag->values, true) ? '2 - Often' : $tag->values[0];
    }
    $_POST[$tag->name] = $value;
}
$marker = 'Synthetic & ' . bin2hex(random_bytes(8));
foreach (array('student_first_name', 'child_first_name', 'patient_first_name', 'FirstName') as $field) {
    if (isset($_POST[$field])) { $_POST[$field] = $marker; }
}
$_POST['unrecognized_field'] = 'MUST_NOT_LEAK';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Isolated native form acceptance';
$_SERVER['SERVER_NAME'] = parse_url(get_option('home'), PHP_URL_HOST);
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['REQUEST_METHOD'] = 'POST';
if (in_array($case, array('fail_primary', 'i693_fail_primary'), true)) { $phpmailer->failAt = 1; }
if ($case === 'fail_ack') { $phpmailer->failAt = 2; }
if ($case === 'fail_attachment') { $phpmailer->failAttachment = true; }
if ($case === 'array_name') { $_POST['student_first_name'] = array('Synthetic', 'Injected'); }
if ($case === 'array_choice') { $_POST['reading_level'] = array('Below Grade Level', 'At Grade Level'); }
if ($case === 'bad_choice') { $_POST['reading_level'] = 'Injected invalid option'; }
if ($case === 'bad_date') { $_POST['student_dob'] = '2016-02-31'; }
if ($case === 'large_field') { $_POST['additional_comments'] = str_repeat('x', 20001); }
if ($case === 'invalid_rating') { $_POST['q1_fails_attention'] = '3junk'; }
if ($case === 'missing_rating') { unset($_POST['q1_fails_attention']); }
if ($case === 'vanderbilt_low') {
    foreach (array_keys($_POST) as $key) { if (preg_match('/^q\d+_/', $key)) { $_POST[$key] = '0 - Never'; } }
}
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
if ($case === 'i693_bad_pdf') { $form->set_properties(array('additional_settings' => "gcm_export: i693\ngcm_i693_pdf: https://example.invalid/file.pdf")); }
if ($case === 'i693_unknown_export') { $form->set_properties(array('additional_settings' => 'gcm_export: unknown')); }
if ($case === 'i693_duplicate_export') { $form->set_properties(array('additional_settings' => "gcm_export: i693\ngcm_export: i693")); }
if ($case === 'i693_no_pdf') { $form->set_properties(array('additional_settings' => 'gcm_export: i693')); }
if ($case === 'i693_no_appointment') { unset($_POST['appointmentfield'], $_POST['location']); }
if ($case === 'i693_missing_name') { unset($_POST['FirstName']); }
if ($case === 'i693_bad_dob') { $_POST['dob'] = '2016-02-31'; }
if ($case === 'i693_array_name') { $_POST['FirstName'] = array('One', 'Two'); }
if ($case === 'i693_csv') {
    $marker = $_POST['FirstName'] = '=SUM(1,2)';
    $_POST['LastName'] = 'Quoted, "Name"';
    $_POST['MiddleName'] = '<unexpected> & "value"';
}
if ($case === 'i693_display') {
    wp_set_current_user(0);
    $_GET = array('date1'=>'2026-09-20', 'time'=>'11-42-AM', 'location'=>'TEST');
    check(str_contains(get_i693_appointment_message(false, $form), '09/20/2026 at 11:42 AM. Synthetic clinic'), 'Native link display failed');
    check(str_contains(do_shortcode('[i693_appointment]'), 'do not confirm a booking'), 'Shortcode omitted advisory boundary');
    $_GET['time'] = '<img src=x onerror=alert(1)>';
    check(get_i693_appointment_message(false, $form) === false && do_shortcode('[i693_appointment]') === '', 'Untrusted time rendered');
    $_GET['time'] = array('11:42 AM');
    check(get_i693_appointment_message(false, $form) === false, 'Array time not rejected');
    $_GET['date1'] = array('2026-09-20');
    check(gcm_shortcode_getaptdate() === '', 'Array date caused invalid display');
    $_GET = array('exp'=>'2000-01-01-00:00:00', 'debug'=>'1');
    check(gcm_is_i693_link_expired(), 'Submission marker bypassed display expiration');
    check(!str_contains(gcm_replace_i693_form_if_expired('<form>Original</form>'), 'Link display window'), 'Anonymous debug display');
    $_GET['exp'] = array('invalid');
    check(gcm_is_i693_link_expired(), 'Malformed expiry accepted');
    $other = WPCF7_ContactForm::get_instance((int) get_option('gcm_teacher_report_form_id'));
    check(gcm_replace_i693_form_if_expired('<form>Unrelated</form>') === '<form>Unrelated</form>', 'Expiry changed unrelated CF7 form');
    check(count($phpmailer->messages) === 0, 'Display checks sent mail');
    echo json_encode(array('passed'=>true, 'case'=>$case, 'external_delivery'=>false, 'native_shortcode_and_form_filter'=>true)) . "\n";
    exit;
}
$result = $form->submit(array('skip_mail' => false));
$messages = $phpmailer->messages;
$negative = in_array($case, array('array_name', 'array_choice', 'bad_choice', 'bad_date', 'large_field',
    'invalid_rating', 'missing_rating', 'recipient_tag', 'bcc_tag', 'missing_recipient',
    'i693_bad_pdf', 'i693_unknown_export', 'i693_duplicate_export', 'i693_missing_name', 'i693_bad_dob', 'i693_array_name'), true);
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
    check($result['status'] === (in_array($case, array('fail_primary', 'i693_fail_primary'), true) ? 'mail_failed' : 'mail_sent'), 'Wrong native delivery status');
    $main = $messages[0];
    check($main['to'][0][0] === ($case === 'site_admin_recipient' ? get_option('admin_email') : 'clinical@example.invalid'), 'Wrong clinical recipient');
    $expected_attachments = $kind === 'i693' ? ($case === 'i693_no_appointment' ? 3 : 4) : 2;
    check(count($main['attachments']) === $expected_attachments, 'Missing clinical attachments');
    check(!str_contains($main['subject'] . $main['body'], 'Synthetic'), 'Patient data in subject/body');
    check(!str_contains($main['mime'], 'X-GCM-Attachments'), 'Internal attachment marker leaked');
    foreach ($main['attachments'] as $attachment) {
        check($attachment[5] === true, 'Attachment was written to disk rather than held in memory');
        check(!str_contains($attachment[0], 'MUST_NOT_LEAK'), 'Unrecognized POST field leaked');
    }
    check(simplexml_load_string($main['attachments'][0][0]) !== false, 'Invalid XML attachment');
    check(str_contains($main['attachments'][0][0], esc_xml($marker)), 'Concurrent request content mixed or missing');
    if ($kind === 'i693') {
        $xml = simplexml_load_string($main['attachments'][0][0]);
        check((string) $xml->Pt1Line1b_GivenName === $marker && (string) $xml->Pt1Line1a_FamilyName === $_POST['LastName'], 'Given/family names reversed');
        check((string) $xml->Pt1Line2_Unit === 'APT', 'Apartment field mismatch');
        $xdp = $main['attachments'][1][0];
        check(simplexml_load_string($xdp) !== false && !preg_match('/<(Pt7|Pt8|Pt10|P10_)/', $xdp), 'Clinical/practice defaults in intake export');
        check(!str_contains($xdp, 'Z:/') && str_contains($xdp, '<pdf ') === ($case !== 'i693_no_pdf'), 'Wrong PDF reference');
        $csv = str_getcsv($main['attachments'][2][0], ',', '"', '');
        check(count($csv) === 13 && $csv[1] === $_POST['LastName'], 'CSV field escaping/order changed');
        check($csv[3] === "'" . $_POST['DaytimeTelephone'], 'Spreadsheet formula prefix unprotected');
        if ($case === 'i693_csv') { check($csv[0] === "'" . $marker, 'CSV formula payload unprotected'); }
    }
    if ($kind === 'vanderbilt') {
        $scores = calculate_vanderbilt_scores(GCM_Clinical_Form_Mail::data($form, WPCF7_Submission::get_instance()->get_posted_data()));
        $expected_score = $case === 'vanderbilt_low' ? 0 : 18;
        check($scores['inattention_raw_score'] === $expected_score && $scores['hyperactivity_raw_score'] === $expected_score, 'Radio arrays were mis-scored');
        check(!$scores['assessment_complete'] && !$scores['diagnosis_determined'] && $scores['clinical_review_required'], 'Partial instrument claimed a diagnosis/completeness');
        check(!str_contains($main['attachments'][0][0], '<clinically_significant>') && str_contains($main['attachments'][0][0], '<diagnosis_determined>false</diagnosis_determined>'), 'Wrong clinical export schema');
        check(str_contains(gcm_vanderbilt_subset_notice('<form>Fixture</form>'), 'not a complete Vanderbilt assessment'), 'Existing form omitted subset notice');
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
if ($kind !== 'i693') {
    $reloaded = WPCF7_ContactForm::get_instance((int) get_option('gcm_' . $kind . '_form_id'));
    check($reloaded->get_properties() === $saved, 'Submission overwrote persisted operator configuration');
}
echo json_encode(array('passed' => true, 'case' => $case, 'status' => $result['status'],
    'intercepted_mail_count' => count($messages), 'external_delivery' => false,
    'native_mime_and_result_hooks' => true), JSON_THROW_ON_ERROR) . "\n";
