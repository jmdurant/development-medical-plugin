<?php

function gcm_enqueue_validation_js() {
	$methods = get_field( 'validation_methods', 'options' ); // array( string $class, string[] $methods )
	$limit_prefixes = get_field( 'character_limit_class_prefix', 'options' );
	
	$settings = array(
		'methods' => $methods,
		'min_prefix' => $limit_prefixes['min_length'] ?? '',
		'max_prefix' => $limit_prefixes['max_length'] ?? '',
		'debug_mode' => current_user_can('administrator') ? 1 : 0,
	);
	
	// Enqueue validation.js
	wp_enqueue_script( 'gcm-validation', GCM_URL . '/assets/validation.js', array( 'jquery' ), GCM_VERSION );
	
	// Include data for validation.js
	wp_localize_script( 'gcm-validation', 'gcm_validation_settings', $settings );
}
add_action( 'wp_enqueue_scripts', 'gcm_enqueue_validation_js' );