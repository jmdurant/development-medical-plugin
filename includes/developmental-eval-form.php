<?php
/**
 * Developmental Evaluation Form Processing
 *
 * Generates XML attachments for developmental evaluation intake forms.
 * This is a template that can be adapted for different evaluation types:
 * - ADHD Evaluations
 * - Autism Spectrum Evaluations
 * - Learning Disability Assessments
 * - Developmental Delay Evaluations
 */

add_action( 'wpcf7_before_send_mail', 'gcm_process_developmental_eval_form' );

/**
 * Process developmental evaluation intake form submissions
 *
 * @param WPCF7_ContactForm $cf7
 * @return void
 */
function gcm_process_developmental_eval_form( $cf7 ) {
	$form_id = $cf7->id;

	// TODO: Replace XXXX with your actual Contact Form 7 form ID
	// Find this in WordPress Admin → Contact → Contact Forms
	if ( $form_id === XXXX ) {

		// Get admin email from ACF settings
		$admin_email = get_field('primary_email', 'option') ?: 'admin@developmentalondemand.com';
		$to = $admin_email;
		$headers = array( "From: " . $cf7->mail['sender'] );

		// Format dates
		$child_dob = $_POST['child_dob'] ?? '';
		$formatted_child_dob = $child_dob ? date("m/d/Y", strtotime($child_dob)) : '';
		$filename_date = $child_dob ? date("mdY", strtotime($child_dob)) : date("mdY");
		$today = date("m/d/Y");

		// Generate XML data structure for intake form
		$xml_output = generate_developmental_eval_xml( $_POST, $formatted_child_dob, $today );

		// Generate CSV/TXT summary for quick reference
		$summary_output = generate_eval_summary( $_POST, $today );

		// Create filenames
		$child_first = sanitize_file_name( $_POST['child_first_name'] ?? 'Child' );
		$child_last = sanitize_file_name( $_POST['child_last_name'] ?? 'Unknown' );
		$base_filename = "{$child_first}_{$child_last}_{$filename_date}";

		// Write files temporarily
		file_put_contents( "dev_eval_data.xml", $xml_output );
		file_put_contents( "dev_eval_summary.txt", $summary_output );

		// Email subject
		$eval_type = $_POST['evaluation_type'] ?? 'Developmental Evaluation';
		$subject = "{$eval_type} Intake - {$child_first} {$child_last} - {$filename_date}";

		// Email body
		$body = "Developmental Evaluation Intake Form Submission\n\n";
		$body .= "Child: {$child_first} {$child_last}\n";
		$body .= "DOB: {$formatted_child_dob}\n";
		$body .= "Evaluation Type: {$eval_type}\n";
		$body .= "Submission Date: {$today}\n\n";
		$body .= "Please find attached XML data file and summary.\n";

		// Attachments
		$attachments = array( "dev_eval_data.xml", "dev_eval_summary.txt" );

		// Send email with attachments
		wp_mail( $to, $subject, $body, $headers, $attachments );

		// Clean up temporary files
		@unlink( "dev_eval_data.xml" );
		@unlink( "dev_eval_summary.txt" );
	}
}

/**
 * Generate XML data structure for developmental evaluation
 *
 * @param array $data Posted form data
 * @param string $formatted_dob Formatted date of birth
 * @param string $today Today's date
 * @return string XML content
 */
function generate_developmental_eval_xml( $data, $formatted_dob, $today ) {
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<developmental_evaluation>' . "\n";
	$xml .= '  <submission_date>' . esc_xml( $today ) . '</submission_date>' . "\n";
	$xml .= '  <evaluation_type>' . esc_xml( $data['evaluation_type'] ?? '' ) . '</evaluation_type>' . "\n";
	$xml .= "\n";

	// Child Information
	$xml .= '  <child_information>' . "\n";
	$xml .= '    <first_name>' . esc_xml( $data['child_first_name'] ?? '' ) . '</first_name>' . "\n";
	$xml .= '    <last_name>' . esc_xml( $data['child_last_name'] ?? '' ) . '</last_name>' . "\n";
	$xml .= '    <middle_name>' . esc_xml( $data['child_middle_name'] ?? '' ) . '</middle_name>' . "\n";
	$xml .= '    <date_of_birth>' . esc_xml( $formatted_dob ) . '</date_of_birth>' . "\n";
	$xml .= '    <gender>' . esc_xml( $data['child_gender'] ?? '' ) . '</gender>' . "\n";
	$xml .= '    <grade_level>' . esc_xml( $data['grade_level'] ?? '' ) . '</grade_level>' . "\n";
	$xml .= '    <school_name>' . esc_xml( $data['school_name'] ?? '' ) . '</school_name>' . "\n";
	$xml .= '  </child_information>' . "\n";
	$xml .= "\n";

	// Parent/Guardian Information
	$xml .= '  <parent_guardian_information>' . "\n";
	$xml .= '    <first_name>' . esc_xml( $data['parent_first_name'] ?? '' ) . '</first_name>' . "\n";
	$xml .= '    <last_name>' . esc_xml( $data['parent_last_name'] ?? '' ) . '</last_name>' . "\n";
	$xml .= '    <relationship>' . esc_xml( $data['relationship'] ?? '' ) . '</relationship>' . "\n";
	$xml .= '    <email>' . esc_xml( $data['parent_email'] ?? '' ) . '</email>' . "\n";
	$xml .= '    <phone_primary>' . esc_xml( $data['phone_primary'] ?? '' ) . '</phone_primary>' . "\n";
	$xml .= '    <phone_secondary>' . esc_xml( $data['phone_secondary'] ?? '' ) . '</phone_secondary>' . "\n";
	$xml .= '  </parent_guardian_information>' . "\n";
	$xml .= "\n";

	// Address Information
	$xml .= '  <address>' . "\n";
	$xml .= '    <street>' . esc_xml( $data['street'] ?? '' ) . '</street>' . "\n";
	$xml .= '    <apartment_type>' . esc_xml( $data['apartment_type'] ?? '' ) . '</apartment_type>' . "\n";
	$xml .= '    <apartment_number>' . esc_xml( $data['apartment_number'] ?? '' ) . '</apartment_number>' . "\n";
	$xml .= '    <city>' . esc_xml( $data['city'] ?? '' ) . '</city>' . "\n";
	$xml .= '    <state>' . esc_xml( $data['state'] ?? '' ) . '</state>' . "\n";
	$xml .= '    <zip_code>' . esc_xml( $data['zip_code'] ?? '' ) . '</zip_code>' . "\n";
	$xml .= '  </address>' . "\n";
	$xml .= "\n";

	// Evaluation Details
	$xml .= '  <evaluation_details>' . "\n";
	$xml .= '    <primary_concerns>' . esc_xml( $data['primary_concerns'] ?? '' ) . '</primary_concerns>' . "\n";
	$xml .= '    <previous_evaluations>' . esc_xml( $data['previous_evaluations'] ?? '' ) . '</previous_evaluations>' . "\n";
	$xml .= '    <current_services>' . esc_xml( $data['current_services'] ?? '' ) . '</current_services>' . "\n";
	$xml .= '    <referral_source>' . esc_xml( $data['referral_source'] ?? '' ) . '</referral_source>' . "\n";
	$xml .= '    <insurance_name>' . esc_xml( $data['insurance_name'] ?? '' ) . '</insurance_name>' . "\n";
	$xml .= '    <insurance_id>' . esc_xml( $data['insurance_id'] ?? '' ) . '</insurance_id>' . "\n";
	$xml .= '    <preferred_appointment_time>' . esc_xml( $data['preferred_time'] ?? '' ) . '</preferred_appointment_time>' . "\n";
	$xml .= '  </evaluation_details>' . "\n";
	$xml .= "\n";

	$xml .= '</developmental_evaluation>';

	return $xml;
}

/**
 * Generate summary text file
 *
 * @param array $data Posted form data
 * @param string $today Today's date
 * @return string Summary content
 */
function generate_eval_summary( $data, $today ) {
	$summary = "DEVELOPMENTAL EVALUATION INTAKE SUMMARY\n";
	$summary .= "========================================\n\n";
	$summary .= "Submission Date: {$today}\n";
	$summary .= "Evaluation Type: " . ( $data['evaluation_type'] ?? 'N/A' ) . "\n\n";

	$summary .= "CHILD INFORMATION:\n";
	$summary .= "------------------\n";
	$summary .= "Name: " . ( $data['child_first_name'] ?? '' ) . " " . ( $data['child_last_name'] ?? '' ) . "\n";
	$summary .= "DOB: " . ( $data['child_dob'] ?? 'N/A' ) . "\n";
	$summary .= "Gender: " . ( $data['child_gender'] ?? 'N/A' ) . "\n";
	$summary .= "Grade: " . ( $data['grade_level'] ?? 'N/A' ) . "\n";
	$summary .= "School: " . ( $data['school_name'] ?? 'N/A' ) . "\n\n";

	$summary .= "PARENT/GUARDIAN:\n";
	$summary .= "----------------\n";
	$summary .= "Name: " . ( $data['parent_first_name'] ?? '' ) . " " . ( $data['parent_last_name'] ?? '' ) . "\n";
	$summary .= "Relationship: " . ( $data['relationship'] ?? 'N/A' ) . "\n";
	$summary .= "Email: " . ( $data['parent_email'] ?? 'N/A' ) . "\n";
	$summary .= "Phone: " . ( $data['phone_primary'] ?? 'N/A' ) . "\n\n";

	$summary .= "EVALUATION DETAILS:\n";
	$summary .= "-------------------\n";
	$summary .= "Primary Concerns: " . ( $data['primary_concerns'] ?? 'N/A' ) . "\n";
	$summary .= "Referral Source: " . ( $data['referral_source'] ?? 'N/A' ) . "\n";
	$summary .= "Insurance: " . ( $data['insurance_name'] ?? 'N/A' ) . "\n";
	$summary .= "Preferred Time: " . ( $data['preferred_time'] ?? 'N/A' ) . "\n";

	return $summary;
}
