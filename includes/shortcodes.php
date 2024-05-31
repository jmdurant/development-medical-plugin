<?php

// 2023/09/13: Swapped first and last name fields on all 3 output files, and the filename itself.

add_shortcode('expirydate', 'gcm_shortcode_displaydate');
add_shortcode('expirydate7', 'gcm_shortcode_displaydate7');
add_shortcode('getaptdateval', 'gcm_shortcode_getaptdate');
add_shortcode('appointmentfieldval', 'gcm_shortcode_appointmentfield');


// used on the coupon form
// https://greatcitymedical.com/wp-admin/admin.php?page=wpcf7&post=3365&action=edit
// https://greatcitymedical.com/nyc-immigration-services/
function gcm_shortcode_displaydate() {
	return date('m-d-Y', strtotime("+20 days"));
}

// used on the form called "Mr" and the medical request form page
// https://greatcitymedical.com/wp-admin/admin.php?page=wpcf7&post=3441&action=edit
// https://greatcitymedical.com/mr/
function gcm_shortcode_displaydate7() {
	return date('F d, Y', strtotime("+7 days"));
}

// used on i639 form
// https://greatcitymedical.com/i693form/?date1=2023-07-19&time=12%3A50%20PM&location=68E (old address)
// https://greatcitymedical.com/i693form/?date1=2023-07-19&time=12%3A50%20PM&location=51SN
function gcm_shortcode_getaptdate() {
	$date1 = $_GET['date1'];
	$aptdate = date("m/d/Y", strtotime($date1));
	return $aptdate;
}

// used on i639 form, this value is stored in a field that is then submitted in the contact form
// https://greatcitymedical.com/i693form/?date1=2023-07-19&time=12%3A50%20PM&location=68E  (old address)
// https://greatcitymedical.com/i693form/?date1=2023-07-19&time=12%3A50%20PM&location=51SN
function gcm_shortcode_appointmentfield() {
	// Get the appointment message, without location
	// Old: 'Your appointment is on ' . esc_html($date) . ' at ' . esc_html($time);
	$message = get_i693_appointment_message( true );
	
	if ( $message ) {
		// Remove HTML formatting from valid message
		$message = strip_tags($message);
	}else{
		// Fallback if no date was selected
		$message = 'Appointment was not selected';
	}
	
	return $message;
}