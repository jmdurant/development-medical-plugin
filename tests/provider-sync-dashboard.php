<?php
/** Offline rendering/dispatch fixture, not native WordPress acceptance. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$admin=false; $nonce=true; $reads=0; $syncs=0; $checks=0; $data=[];
function add_action(...$args) {}
function current_user_can($cap) { return $GLOBALS['admin'] && $cap==='manage_options'; }
function get_field($key,$post) {
    if ($post!=='gcm_settings') { throw new RuntimeException('Wrong settings namespace'); }
    $GLOBALS['reads']++; return $GLOBALS['data'][$key] ?? false;
}
function check_admin_referer(...$args) {
    if (!$GLOBALS['nonce']) { throw new RuntimeException('nonce rejected'); }
    return true;
}
function gcm_sync_providers_from_google_sheets() {
    $GLOBALS['syncs']++; return ['success'=>true,'message'=>'Fixture sync complete'];
}
function esc_html($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function get_admin_page_title() { return 'Fixture'; }
function admin_url($path) { return '/wp-admin/'.$path; }
function wp_next_scheduled($hook) { return false; }
function wp_nonce_field(...$args) {}
function check($condition,$label) { $GLOBALS['checks']++; if (!$condition) { throw new RuntimeException($label); } }
function render() { ob_start(); try { gcm_display_provider_sync_page(); return ob_get_contents(); } finally { ob_end_clean(); } }
require dirname(__DIR__).'/includes/dashboard.php';
$_POST=['gcm_trigger_sync'=>'1'];
check(render()==='' && $reads===0 && $syncs===0,'Non-administrator cannot read settings or dispatch a sync');
$admin=true; $_POST=[];
$html=render();
check(str_contains($html,'WP_SHEETS_SERVICE_ACCOUNT_B64') && !str_contains($html,'JSON path'),'Dashboard explains protected credentials');
check(str_contains($html,'First Name, Last Name, Status'),'Dashboard uses the current exporter schema');
$data=['google_sheets_enabled'=>1,'staff_list'=>[['name'=>'Synthetic']]];
check(str_contains(render(),'<strong>1</strong> providers'),'Dashboard reads the same roster as the website');
$_POST=['gcm_trigger_sync'=>'1'];
check(str_contains(render(),'Fixture sync complete') && $syncs===1,'Administrator with nonce dispatches one sync');
$nonce=false;
try { render(); } catch (RuntimeException $error) { check($error->getMessage()==='nonce rejected','Native nonce failure stops rendering/dispatch'); }
check($syncs===1,'Rejected nonce never calls sync');
echo "PASS $checks dashboard assertions\n";
