<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('PRACTICE_FRESH_INSTALL_TEST') !== 'true') {
    throw new RuntimeException('Disposable CLI fixture required');
}
require '/var/www/html/wp-load.php';
if (!preg_match('~^https://www\.practice-fresh-check-[a-f0-9]{12}\.localhost$~D', get_option('home'))) {
    throw new RuntimeException('Expected retained isolated website');
}
add_filter('pre_wp_mail', static fn() => false);
function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$first = gcm_install_evaluation_forms();
check($first['success'], 'Native form install failed');
$keys = ['developmental_eval', 'vanderbilt', 'teacher_report', 'pcp_referral'];
$before = [];
foreach ($keys as $key) {
    $option = 'gcm_'.$key.'_form_id';
    $form = WPCF7_ContactForm::get_instance(get_option($option));
    $page = get_post(get_option($option.'_page'));
    check($form !== null && $page && $page->post_type === 'page', 'Missing native form/page');
    check(str_contains($page->post_content, 'id="'.$form->id().'"'), 'Page/form binding mismatch');
    $before[$key] = ['form'=>$form->id(), 'page'=>(int)$page->ID, 'slug'=>$page->post_name];
}
$target = $before['teacher_report']['page'];
$original = get_post($target)->post_content;
$custom = $original."\n<p>Retained operator customization fixture</p>";
wp_update_post(['ID'=>$target, 'post_content'=>$custom]);
try {
    $retry = gcm_install_evaluation_forms();
    check($retry['success'], 'Native retry failed');
    foreach ($keys as $key) {
        $option = 'gcm_'.$key.'_form_id';
        check((int)get_option($option) === $before[$key]['form'] &&
              (int)get_option($option.'_page') === $before[$key]['page'], 'Retry changed IDs');
    }
    check(get_post($target)->post_content === $custom, 'Retry overwrote customized form page');
    echo json_encode(['passed'=>true, 'native_forms'=>$before, 'retry_preserves_ids_and_page_content'=>true], JSON_THROW_ON_ERROR)."\n";
} finally {
    wp_update_post(['ID'=>$target, 'post_content'=>$original]);
}
