<?php

/**
 * Customizations to the i693 form at:
 * https://greatcitymedical.com/i693form/
 *
 * With today's date:
 * https://greatcitymedical.com/i693form/?date1=Today&time=11-42-AM
 *
 * With specific date:
 * https://greatcitymedical.com/i693form/?date1=2024-01-03&time=12%3A20%20PM&location=68E
 *
 * With custom expiration date and time:
 * https://greatcitymedical.com/i693form/?date1=Today&time=11-42-AM&expd=2024-01-03&expt=12%3A20%20PM
 */

/**
 * Check if the current URL includes an i693 form link that has expired.
 *
 * @param string &$debug_message  Optional and by reference. Debugging details will be stored here.
 *
 * @return bool
 */
function gcm_is_i693_link_expired( &$debug_message = '' ) {
	// Do not apply if the form is currently being submitted
	if ( isset($_POST['_wpcf7']) ) return false;
	
	// Check if the 'exp' query parameter is set
	$expiration_date = isset($_GET['exp']) ? stripslashes($_GET['exp']) : false;
	if ( ! $expiration_date ) return false;
	
	// How long after the expiration date should the form be available?
	$grace_period = 30 * MINUTE_IN_SECONDS;
	
	// Convert the expiration date to a timestamp
	$exp_ts = strtotime($expiration_date);
	
	// Check that the expiration date is valid (2024 or later)
	if ( $exp_ts < strtotime('2024-01-01') ) {
		$debug_message = '<h4><strong>[Debug] Error: Invalid expiration date: "' . esc_html($expiration_date) . '"</strong></h4>';
		return false;
	}
	
	// Current timestamp
	// $current_ts = current_time('timestamp'); // server time
	$current_ts = time(); // utc
	
	// Get number of seconds until the link expires
	$diff = $current_ts - ($exp_ts + $grace_period);
	
	// Check if expired
	$is_expired = $diff > 0;
	
	// Calculate how long ago the link expired, or how long until it expires
	$remaining = gcm_get_time_remaining( $diff );
	
	// Create a debug message that includes details used in the calculation
	$debug_message = '<pre>';
	$debug_message .= '';
	
	if ( $is_expired ) {
		$debug_message .= '<strong>[Debug] This link expired ' . $remaining . ' ago.</strong>' . "\n";
	} else {
		$debug_message .= '<strong>[Debug] This link will expire in ' . $remaining . '.</strong>' . "\n";
	}
	
	$debug_message .= '        Time Now (UTC): ' . date('m/d/Y h:i:s a', $current_ts) . "\n";
	$debug_message .= '        Expiration:     ' . date('m/d/Y h:i:s a', $exp_ts) . "\n";
	$debug_message .= '        Grace Period:   ' . gcm_get_time_remaining($grace_period) . "\n";
	$debug_message .= '</pre>';
	
	return $is_expired;
}

// Hook into 'wpcf7_form_elements' to filter the form output
function gcm_replace_i693_form_if_expired( $form ) {
	// Whether to show a debug messages, if &debug is set on the URL
	$show_debug_message = isset($_GET['debug']);
	
	// Store details about the calculation here
	$debug_message = false;
	
	// Check if the link is expired, and store the debug message separately if so.
	$is_expired = gcm_is_i693_link_expired( $debug_message );
	
	// If the link expired, show a message instead of the form
	if ( $is_expired ) {
		$form = get_i693_expired_message();
	}
	
	// If debug is enabled, show the debug message above the form
	if ( $show_debug_message && $debug_message ) {
		$form = $debug_message . "\n\n" . $form;
	}
	
	// If not expired, return the original form
	return $form;
}
add_filter( 'wpcf7_form_elements', 'gcm_replace_i693_form_if_expired' );


/**
 * Convert number of seconds to human-readable string such as: "1 hour, 13 minutes, and 51 seconds"
 *
 * Includes days, hours, minutes, and seconds.
 *
 * @param int $diff
 *
 * @return string
 */
function gcm_get_time_remaining( $diff ) {
	$r = abs($diff);
	$days = floor($r / DAY_IN_SECONDS);
	$r = $r % DAY_IN_SECONDS;
	$hours = floor($r / HOUR_IN_SECONDS);
	$r = $r % HOUR_IN_SECONDS;
	$minutes = floor($r / MINUTE_IN_SECONDS);
	$r = $r % MINUTE_IN_SECONDS;
	$seconds = $r;
	
	$remaining = array();
	if ( $days > 0 ) $remaining[] = sprintf(_n('%d day', '%d days', $days), $days);
	if ( $hours > 0 ) $remaining[] = sprintf(_n('%d hour', '%d hours', $hours), $hours);
	if ( $minutes > 0 ) $remaining[] = sprintf(_n('%d minute', '%d minutes', $minutes), $minutes);
	if ( $seconds > 0 ) $remaining[] = sprintf(_n('%d second', '%d seconds', $seconds), $seconds);
	$remaining = implode(', ', $remaining);
	
	// Replace the last comma with ', and'
	$remaining = preg_replace('/,([^,]*)$/', ', and$1', $remaining);
	
	return $remaining;
}

/**
 * Get the message to show when an appointment link has expired.
 *
 * @return string
 */
function get_i693_expired_message() {
	$message = get_field( 'i639_expired_message', 'gcm_settings' );
	if ( ! $message ) $message = "We're sorry, the link you followed has expired.";
	
	return wpautop( $message );
}

/**
 * Get the appointment message for the i693 form page, or the email that is sent to the admins.
 *
 * @param bool $use_form_value  Whether the message should be formatted for display (default, true) or as a form value that is sent in the email (if set to true)
 *
 * @return string
 */
function get_i693_appointment_message( $use_form_value = false ) {
	// Get the date, time, and location.
	$date = isset($_GET['date1']) ? stripslashes($_GET['date1']) : false; // "2024-01-03"
	$time = isset($_GET['time']) ? stripslashes($_GET['time']) : false; // "12:20 PM"
	$location = isset($_GET['location']) ? stripslashes($_GET['location']) : false; // "1513V" or "68E"
	if ( !$date && !$time && !$location ) return false;
	
	// Format the date as m/d/Y
	$date = $date ? date( 'm/d/Y', strtotime($date) ) : false;
	
	// Get the message template from the settings page
	if ( $use_form_value ) {
		// Use the message template formatted for use in the form. This message is sent to the admins.
		$template = get_field( 'i639_appointment_form_value', 'gcm_settings' );
		if ( ! $template ) {
			$template = 'Your appointment is on [date] at [time]';
		}
	}else{
		// Use the message template formatted for the visitor to see. This is shown on the front-end.
		$template = get_field( 'i639_appointment_message', 'gcm_settings' );
		if ( ! $template ) {
			$template = 'Your appointment is on <strong>[date]</strong> at [time], <br>';
			$template.= '<strong>Location:</strong> [location]';
		}
	}
	
	$address = '';
	if ( $location == '1513V' ) $address = '1513 Voorhies Ave 3rd Floor, Brooklyn, NY 11235';
	if ( $location == '68E' ) $address = '68e 131st Street Suite 100, New York, NY 10037';
	
	$tags = array(
		'[date1]' => $date,
		'[date]' => $date, // (alias)
		
		'[time]' => $time,
		
		'[location]' => $address,
		'[address]' => $address, // (alias)
	);
	
	$message = str_replace( array_keys($tags), array_values($tags), $template );
	
	return $message;
}

/**
 * Shortcode to display the appointment message on a page.
 *
 *  Appointment link example:
 *  (live)    https://greatcitymedical.com/i693form/?date1=2024-01-03&time=12%3A20%20PM&location=68E&exp=2024-01-06-14:00:00&debug
 *  (staging) https://greatcitymedical.radgh.com/i693form/?date1=2024-01-03&time=12%3A20%20PM&location=68E&exp=2024-01-06-14:00:00&debug
 *
 * @param $atts
 * @param $content
 * @param $shortcode_name
 *
 * @return string
 */
function shortcode_i693_appointment( $atts, $content = '', $shortcode_name = 'i693_appointment' ) {
	$message = get_i693_appointment_message();
	if ( ! $message ) return '';
	
	$is_expired = gcm_is_i693_link_expired();
	if ( $is_expired ) return '';
	
	ob_start();
	?>
	
	<div class="i693-appointment-card">
		
		<div class="wp-block-group container-style-card-x-small has-lightest-blue-background-color has-background is-layout-constrained">
			<div class="wp-block-group gap-16 is-nowrap is-layout-flex wp-container-3">
				
				<?php
				echo gcm_get_icon_html( 'patient', 'blue', 'circle', 'medium' );
				?>
				
				<div class="i693-content has-blue-color has-text-color">
					<?php echo $message; ?>
				</div>
			
			</div>
		</div>
	
	</div>
	
	<?php
	return ob_get_clean();
}
add_shortcode( 'i693_appointment', 'shortcode_i693_appointment' );