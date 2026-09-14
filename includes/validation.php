<?php

function gcm_enqueue_validation_js() {
	$methods = function_exists('get_field') ? get_field( 'validation_methods', 'options' ) : array();
	$limit_prefixes = function_exists('get_field') ? get_field( 'character_limit_class_prefix', 'options' ) : array();
	
	$settings = array(
		'methods' => is_array($methods) ? array_values(array_filter($methods, 'is_array')) : array(),
		'min_prefix' => is_array($limit_prefixes) && is_string($limit_prefixes['min_length'] ?? null) ? $limit_prefixes['min_length'] : '',
		'max_prefix' => is_array($limit_prefixes) && is_string($limit_prefixes['max_length'] ?? null) ? $limit_prefixes['max_length'] : '',
	);
	
	// Optional DOM hints: no jQuery/CF7 API dependency or PHI console logging.
	$version = GCM_VERSION . '.' . substr(hash_file('sha256', dirname(__DIR__) . '/assets/validation.js'), 0, 12);
	wp_enqueue_script( 'gcm-validation', GCM_URL . '/assets/validation.js', array(), $version, array('in_footer' => true, 'strategy' => 'defer') );
	
	// Include data for validation.js
	wp_localize_script( 'gcm-validation', 'gcm_validation_settings', $settings );
}
add_action( 'wp_enqueue_scripts', 'gcm_enqueue_validation_js' );
