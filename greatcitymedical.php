<?php
/*
Plugin Name: * Great City Medical
Description: Custom features for Great City Medical, which adds custom attachments sent with Contact Form 7, custom shortcodes, and form validation methods.
Version: 1.2.0
Author: Radley Sustaire
Author URI: https://radleysustaire.com/
*/

define( 'GCM_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'GCM_PATH', dirname(__FILE__) );
define( 'GCM_VERSION', '1.2.0' );

include( __DIR__ . '/includes/shortcodes.php' );
include( __DIR__ . '/includes/acf.php' );
include( __DIR__ . '/includes/contact-form-7.php' );
include( __DIR__ . '/includes/redirects.php' );
include( __DIR__ . '/includes/validation.php' );
include( __DIR__ . '/includes/dashboard.php' );
include( __DIR__ . '/includes/i693_form.php' );
