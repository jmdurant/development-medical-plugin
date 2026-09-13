<?php
declare(strict_types=1);
define('GCM_URL', 'https://example.invalid/plugin');
define('GCM_VERSION', 'test');
function add_action(...$args) {}
function current_user_can(...$args) { return false; }
function wp_enqueue_script(...$args) {}
function wp_localize_script($handle, $object, $settings) { $GLOBALS['settings'] = $settings; }
require dirname(__DIR__).'/includes/validation.php';
gcm_enqueue_validation_js();
if ($GLOBALS['settings']['min_prefix'] !== '' || $GLOBALS['settings']['max_prefix'] !== '') {
    throw new RuntimeException('Expected empty optional settings without ACF/SCF');
}
echo "PASS frontend validation loads without a custom-fields provider\n";
