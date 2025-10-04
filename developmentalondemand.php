<?php
/*
Plugin Name: * Developmental On Demand
Description: Custom features for Developmental On Demand, which adds custom attachments sent with Contact Form 7, custom shortcodes, and form validation methods.
Version: 1.2.1
Author: James DuRant
Author URI: https://developmentalondemand.com/
*/

define( 'GCM_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'GCM_PATH', dirname(__FILE__) );
define( 'GCM_VERSION', '1.2.1' );

include( __DIR__ . '/includes/shortcodes.php' );
include( __DIR__ . '/includes/acf.php' );
include( __DIR__ . '/includes/contact-form-7.php' );
include( __DIR__ . '/includes/redirects.php' );
include( __DIR__ . '/includes/validation.php' );
include( __DIR__ . '/includes/dashboard.php' );
include( __DIR__ . '/includes/i693_form.php' );
include( __DIR__ . '/includes/form-installer.php' );
include( __DIR__ . '/includes/developmental-eval-form.php' );
include( __DIR__ . '/includes/vanderbilt-scoring.php' );
include( __DIR__ . '/includes/teacher-report-form.php' );
include( __DIR__ . '/includes/pcp-referral-form.php' );
