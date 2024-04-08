<?php

/**
 * Register the theme's top-level options page
 */
function gcm_register_options_pages() {
	// Great City Medical Main page
	add_menu_page(
		null,
		__( 'Great City Medical', 'gcm' ),
		'manage_options',
		'acf-gcm-settings',
		'gcm_display_general_settings_page',
		'dashicons-gcm-icon',
		80
	);
	
	// Submenu page just to change the first link in the submenu to say General Settings
	add_submenu_page(
		'acf-gcm-settings',
		null,
		__( 'General Settings', 'gcm' ),
		'manage_options',
		'acf-gcm-settings',
		'gcm_display_general_settings_page'
	);
	
	/*
	// Add other sub options pages
	// Great City Medical -> Icons
	add_submenu_page(
		'acf-gcm-settings', // $parent, // 'acf-gcm-root',
		'Icons',
		'Icons',
		'manage_options',
		'gcm-icons-settings',
		'gcm_display_icons_settings_page'
	);
	*/
	
	// Register the ACF options page at the same time despite also being hooked in acf/init
	gcm_register_acf_options_pages();
}
add_action( 'admin_menu', 'gcm_register_options_pages' );

/**
 * Register the theme options page using ACF
 * @return void
 */
function gcm_register_acf_options_pages() {
	if ( ! function_exists('acf_add_options_sub_page') ) return;
	
	// ACF options page to let us add acf fields to that page
	// Great City Medical -> General Settings
	// get_field( 'xxx', 'gcm_settings' );
	acf_add_options_sub_page(array(
		'parent_slug' => 'acf-gcm-root', // $parent, // 'acf-gcm-root',
		'menu_slug'   => 'acf-gcm-settings',
		'page_title'  => __( 'General Settings', 'gcm' ) . ' (gcm_settings)',
		'menu_title'  => null,
		'capability'  => 'manage_options',
		'post_id'     => 'gcm_settings',
		'autoload'      => false,
		'redirect' 		=> true,
	));
}
add_action( 'acf/init', 'gcm_register_acf_options_pages' );


/**
 * Displays the general settings page. This should be replaced with an ACF page, unless ACF is disabled
 *
 * @return void
 */
function gcm_display_general_settings_page() {
	// If ACF is running, do not show the default options page.
	if ( function_exists('acf_add_options_page') ) {
		return;
	}
	
	// ACF is NOT active at this point - display a message to explain.
	?>
	<div class="wrap">
		<h1>General Settings</h1>
		<p>Activate the plugin "Advanced Custom Field Pro" to enable theme settings.</p>
	</div>
	<?php
}
