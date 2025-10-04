<?php
/**
 * PCP Referral Form Processing
 *
 * Allows referring physicians to submit referral information for patients
 * needing developmental evaluations. Form collects minimal identifiers only.
 *
 * Medical records should be sent separately via secure fax.
 */

add_action( 'wpcf7_before_send_mail', 'gcm_process_pcp_referral_form' );

/**
 * Process PCP referral form submissions
 *
 * @param WPCF7_ContactForm $cf7
 * @return void
 */
function gcm_process_pcp_referral_form( $cf7 ) {
	$form_id = $cf7->id;

	// Get form ID from options (set by form installer)
	$target_form_id = get_option( 'gcm_pcp_referral_form_id' );
	if ( ! $target_form_id ) {
		return; // Form not installed yet
	}

	if ( $form_id == $target_form_id ) {

		// Get admin email from ACF settings
		$admin_email = get_field('primary_email', 'option') ?: 'admin@developmentalondemand.com';
		$to = $admin_email;
		$headers = array( "From: " . $cf7->mail['sender'] );

		// Patient identifiers
		$patient_first = sanitize_text_field( $_POST['patient_first_name'] ?? '' );
		$patient_last = sanitize_text_field( $_POST['patient_last_name'] ?? '' );
		$patient_dob = $_POST['patient_dob'] ?? '';
		$formatted_dob = $patient_dob ? date("m/d/Y", strtotime($patient_dob)) : '';
		$filename_date = $patient_dob ? date("mdY", strtotime($patient_dob)) : date("mdY");
		$today = date("m/d/Y");

		// Physician info
		$physician_name = sanitize_text_field( $_POST['physician_name'] ?? '' );
		$physician_email = sanitize_email( $_POST['physician_email'] ?? '' );
		$practice_name = sanitize_text_field( $_POST['practice_name'] ?? '' );

		// Generate XML data
		$xml_output = generate_pcp_referral_xml( $_POST, $formatted_dob, $today );

		// Generate readable summary
		$summary_output = generate_pcp_referral_summary( $_POST, $today );

		// Create filenames with patient identifier
		$base_filename = "{$patient_first}_{$patient_last}_{$filename_date}_referral";

		// Write files temporarily
		file_put_contents( "pcp_referral.xml", $xml_output );
		file_put_contents( "pcp_referral_summary.txt", $summary_output );

		// Email subject
		$referral_type = $_POST['referral_reason'] ?? 'Developmental Evaluation';
		$subject = "PCP Referral - {$patient_first} {$patient_last} - {$referral_type}";

		// Email body
		$body = "PCP Referral Form Submission\n\n";
		$body .= "Patient: {$patient_first} {$patient_last}\n";
		$body .= "DOB: {$formatted_dob}\n";
		$body .= "Referring Physician: {$physician_name}\n";
		$body .= "Practice: {$practice_name}\n";
		$body .= "Referral Reason: {$referral_type}\n";
		$body .= "Submission Date: {$today}\n\n";
		$body .= "Please find attached referral data.\n";
		$body .= "Match this referral to patient record using patient name and DOB.\n";
		$body .= "Request medical records via secure fax if not already received.\n";

		// Attachments
		$attachments = array( "pcp_referral.xml", "pcp_referral_summary.txt" );

		// Send email
		wp_mail( $to, $subject, $body, $headers, $attachments );

		// Send confirmation to referring physician
		$practice_fax = get_field('primary_fax', 'option') ?: '(XXX) XXX-XXXX';
		$physician_confirmation_subject = "Referral Received - {$patient_first} {$patient_last}";
		$physician_confirmation_body = "Dear Dr. {$physician_name},\n\n";
		$physician_confirmation_body .= "Thank you for referring {$patient_first} {$patient_last} to Developmental On Demand for evaluation.\n\n";
		$physician_confirmation_body .= "We have received your referral and will contact the family to schedule an appointment.\n\n";
		$physician_confirmation_body .= "If you have not already done so, please fax medical records and relevant documentation to:\n";
		$physician_confirmation_body .= "Fax: {$practice_fax}\n\n";
		$physician_confirmation_body .= "We will send you a copy of our evaluation report once completed.\n\n";
		$physician_confirmation_body .= "Best regards,\n";
		$physician_confirmation_body .= "Developmental On Demand\n";

		wp_mail( $physician_email, $physician_confirmation_subject, $physician_confirmation_body );

		// Clean up
		@unlink( "pcp_referral.xml" );
		@unlink( "pcp_referral_summary.txt" );
	}
}

/**
 * Generate XML for PCP referral
 *
 * @param array $data Posted form data
 * @param string $formatted_dob Formatted DOB
 * @param string $today Today's date
 * @return string XML content
 */
function generate_pcp_referral_xml( $data, $formatted_dob, $today ) {
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<pcp_referral>' . "\n";
	$xml .= '  <submission_date>' . esc_xml( $today ) . '</submission_date>' . "\n";
	$xml .= '  <referral_type>primary_care_physician</referral_type>' . "\n";
	$xml .= "\n";

	// Patient Identifiers (for matching to patient record)
	$xml .= '  <patient_identifiers>' . "\n";
	$xml .= '    <first_name>' . esc_xml( $data['patient_first_name'] ?? '' ) . '</first_name>' . "\n";
	$xml .= '    <last_name>' . esc_xml( $data['patient_last_name'] ?? '' ) . '</last_name>' . "\n";
	$xml .= '    <date_of_birth>' . esc_xml( $formatted_dob ) . '</date_of_birth>' . "\n";
	$xml .= '  </patient_identifiers>' . "\n";
	$xml .= "\n";

	// Referring Physician Information
	$xml .= '  <referring_physician>' . "\n";
	$xml .= '    <physician_name>' . esc_xml( $data['physician_name'] ?? '' ) . '</physician_name>' . "\n";
	$xml .= '    <practice_name>' . esc_xml( $data['practice_name'] ?? '' ) . '</practice_name>' . "\n";
	$xml .= '    <phone>' . esc_xml( $data['physician_phone'] ?? '' ) . '</phone>' . "\n";
	$xml .= '    <fax>' . esc_xml( $data['physician_fax'] ?? '' ) . '</fax>' . "\n";
	$xml .= '    <email>' . esc_xml( $data['physician_email'] ?? '' ) . '</email>' . "\n";
	$xml .= '    <practice_address>' . esc_xml( $data['practice_address'] ?? '' ) . '</practice_address>' . "\n";
	$xml .= '  </referring_physician>' . "\n";
	$xml .= "\n";

	// Referral Information
	$xml .= '  <referral_information>' . "\n";
	$xml .= '    <referral_reason>' . esc_xml( $data['referral_reason'] ?? '' ) . '</referral_reason>' . "\n";
	$xml .= '    <chief_complaint>' . esc_xml( $data['chief_complaint'] ?? '' ) . '</chief_complaint>' . "\n";
	$xml .= '    <urgency_level>' . esc_xml( $data['urgency_level'] ?? '' ) . '</urgency_level>' . "\n";
	$xml .= '    <preferred_timeframe>' . esc_xml( $data['preferred_timeframe'] ?? '' ) . '</preferred_timeframe>' . "\n";
	$xml .= '  </referral_information>' . "\n";
	$xml .= "\n";

	// Parent/Guardian Contact (for scheduling)
	$xml .= '  <parent_contact>' . "\n";
	$xml .= '    <parent_name>' . esc_xml( $data['parent_name'] ?? '' ) . '</parent_name>' . "\n";
	$xml .= '    <parent_phone>' . esc_xml( $data['parent_phone'] ?? '' ) . '</parent_phone>' . "\n";
	$xml .= '    <parent_email>' . esc_xml( $data['parent_email'] ?? '' ) . '</parent_email>' . "\n";
	$xml .= '  </parent_contact>' . "\n";
	$xml .= "\n";

	// Insurance Information (name only, no policy numbers)
	$xml .= '  <insurance>' . "\n";
	$xml .= '    <insurance_name>' . esc_xml( $data['insurance_name'] ?? '' ) . '</insurance_name>' . "\n";
	$xml .= '  </insurance>' . "\n";
	$xml .= "\n";

	$xml .= '</pcp_referral>';

	return $xml;
}

/**
 * Generate readable summary
 *
 * @param array $data Posted form data
 * @param string $today Today's date
 * @return string Summary content
 */
function generate_pcp_referral_summary( $data, $today ) {
	$summary = "PCP REFERRAL SUMMARY\n";
	$summary .= "====================\n\n";
	$summary .= "Submission Date: {$today}\n\n";

	$summary .= "PATIENT:\n";
	$summary .= "--------\n";
	$summary .= "Name: " . ( $data['patient_first_name'] ?? '' ) . " " . ( $data['patient_last_name'] ?? '' ) . "\n";
	$summary .= "DOB: " . ( $data['patient_dob'] ?? 'N/A' ) . "\n\n";

	$summary .= "REFERRING PHYSICIAN:\n";
	$summary .= "-------------------\n";
	$summary .= "Name: " . ( $data['physician_name'] ?? 'N/A' ) . "\n";
	$summary .= "Practice: " . ( $data['practice_name'] ?? 'N/A' ) . "\n";
	$summary .= "Phone: " . ( $data['physician_phone'] ?? 'N/A' ) . "\n";
	$summary .= "Fax: " . ( $data['physician_fax'] ?? 'N/A' ) . "\n";
	$summary .= "Email: " . ( $data['physician_email'] ?? 'N/A' ) . "\n";
	$summary .= "Address: " . ( $data['practice_address'] ?? 'N/A' ) . "\n\n";

	$summary .= "REFERRAL INFORMATION:\n";
	$summary .= "--------------------\n";
	$summary .= "Reason: " . ( $data['referral_reason'] ?? 'N/A' ) . "\n";
	$summary .= "Chief Complaint: " . ( $data['chief_complaint'] ?? 'N/A' ) . "\n";
	$summary .= "Urgency: " . ( $data['urgency_level'] ?? 'N/A' ) . "\n";
	$summary .= "Preferred Timeframe: " . ( $data['preferred_timeframe'] ?? 'N/A' ) . "\n\n";

	$summary .= "PARENT/GUARDIAN CONTACT:\n";
	$summary .= "-----------------------\n";
	$summary .= "Name: " . ( $data['parent_name'] ?? 'N/A' ) . "\n";
	$summary .= "Phone: " . ( $data['parent_phone'] ?? 'N/A' ) . "\n";
	$summary .= "Email: " . ( $data['parent_email'] ?? 'N/A' ) . "\n\n";

	$summary .= "INSURANCE:\n";
	$summary .= "---------\n";
	$summary .= "Plan: " . ( $data['insurance_name'] ?? 'N/A' ) . "\n\n";

	$summary .= "NEXT STEPS:\n";
	$summary .= "----------\n";
	$summary .= "1. Match referral to patient record using name + DOB\n";
	$summary .= "2. Contact parent to schedule evaluation\n";
	$summary .= "3. Request medical records via fax if not received\n";
	$summary .= "4. Send evaluation report to referring physician when complete\n";

	return $summary;
}
