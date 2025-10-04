<?php
/**
 * Teacher Report Form Processing
 *
 * Allows teachers to submit behavioral and academic observations for students
 * undergoing developmental evaluations. Teacher does not need EHR access.
 *
 * Data is collected separately and can be matched to patient records later
 * using student name + DOB as identifiers.
 */

add_action( 'wpcf7_before_send_mail', 'gcm_process_teacher_report_form' );

/**
 * Process teacher report form submissions
 *
 * @param WPCF7_ContactForm $cf7
 * @return void
 */
function gcm_process_teacher_report_form( $cf7 ) {
	$form_id = $cf7->id;

	// Get form ID from options (set by form installer)
	$target_form_id = get_option( 'gcm_teacher_report_form_id' );
	if ( ! $target_form_id ) {
		return; // Form not installed yet
	}

	if ( $form_id == $target_form_id ) {

		// Get admin email from ACF settings
		$admin_email = get_field('primary_email', 'option') ?: 'admin@developmentalondemand.com';
		$to = $admin_email;
		$headers = array( "From: " . $cf7->mail['sender'] );

		// Student identifiers
		$student_first = sanitize_text_field( $_POST['student_first_name'] ?? '' );
		$student_last = sanitize_text_field( $_POST['student_last_name'] ?? '' );
		$student_dob = $_POST['student_dob'] ?? '';
		$formatted_dob = $student_dob ? date("m/d/Y", strtotime($student_dob)) : '';
		$filename_date = $student_dob ? date("mdY", strtotime($student_dob)) : date("mdY");
		$today = date("m/d/Y");

		// Teacher info
		$teacher_name = sanitize_text_field( $_POST['teacher_name'] ?? '' );
		$teacher_email = sanitize_email( $_POST['teacher_email'] ?? '' );

		// Generate XML data
		$xml_output = generate_teacher_report_xml( $_POST, $formatted_dob, $today );

		// Generate readable summary
		$summary_output = generate_teacher_report_summary( $_POST, $today );

		// Create filenames with student identifier
		$base_filename = "{$student_first}_{$student_last}_{$filename_date}_teacher";

		// Write files temporarily
		file_put_contents( "teacher_report.xml", $xml_output );
		file_put_contents( "teacher_report_summary.txt", $summary_output );

		// Email subject
		$subject = "Teacher Report - {$student_first} {$student_last} - {$teacher_name}";

		// Email body
		$body = "Teacher Report Form Submission\n\n";
		$body .= "Student: {$student_first} {$student_last}\n";
		$body .= "DOB: {$formatted_dob}\n";
		$body .= "Teacher: {$teacher_name}\n";
		$body .= "Teacher Email: {$teacher_email}\n";
		$body .= "Submission Date: {$today}\n\n";
		$body .= "Please find attached teacher report data.\n";
		$body .= "Match this report to patient record using student name and DOB.\n";

		// Attachments
		$attachments = array( "teacher_report.xml", "teacher_report_summary.txt" );

		// Send email
		wp_mail( $to, $subject, $body, $headers, $attachments );

		// Send confirmation to teacher
		$teacher_confirmation_subject = "Thank you for your report - {$student_first} {$student_last}";
		$teacher_confirmation_body = "Dear {$teacher_name},\n\n";
		$teacher_confirmation_body .= "Thank you for submitting your teacher report for {$student_first} {$student_last}.\n\n";
		$teacher_confirmation_body .= "Your observations are an important part of our evaluation process. ";
		$teacher_confirmation_body .= "We will review your report and may contact you if we need any clarification.\n\n";
		$teacher_confirmation_body .= "Best regards,\n";
		$teacher_confirmation_body .= "Developmental On Demand\n";

		wp_mail( $teacher_email, $teacher_confirmation_subject, $teacher_confirmation_body );

		// Clean up
		@unlink( "teacher_report.xml" );
		@unlink( "teacher_report_summary.txt" );
	}
}

/**
 * Generate XML for teacher report
 *
 * @param array $data Posted form data
 * @param string $formatted_dob Formatted DOB
 * @param string $today Today's date
 * @return string XML content
 */
function generate_teacher_report_xml( $data, $formatted_dob, $today ) {
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<teacher_report>' . "\n";
	$xml .= '  <submission_date>' . esc_xml( $today ) . '</submission_date>' . "\n";
	$xml .= '  <report_type>teacher_behavioral_academic</report_type>' . "\n";
	$xml .= "\n";

	// Student Identifiers (for matching to patient record)
	$xml .= '  <student_identifiers>' . "\n";
	$xml .= '    <first_name>' . esc_xml( $data['student_first_name'] ?? '' ) . '</first_name>' . "\n";
	$xml .= '    <last_name>' . esc_xml( $data['student_last_name'] ?? '' ) . '</last_name>' . "\n";
	$xml .= '    <date_of_birth>' . esc_xml( $formatted_dob ) . '</date_of_birth>' . "\n";
	$xml .= '  </student_identifiers>' . "\n";
	$xml .= "\n";

	// Teacher Information
	$xml .= '  <teacher_information>' . "\n";
	$xml .= '    <name>' . esc_xml( $data['teacher_name'] ?? '' ) . '</name>' . "\n";
	$xml .= '    <email>' . esc_xml( $data['teacher_email'] ?? '' ) . '</email>' . "\n";
	$xml .= '    <school_name>' . esc_xml( $data['school_name'] ?? '' ) . '</school_name>' . "\n";
	$xml .= '    <grade_taught>' . esc_xml( $data['grade_taught'] ?? '' ) . '</grade_taught>' . "\n";
	$xml .= '    <subject_taught>' . esc_xml( $data['subject_taught'] ?? '' ) . '</subject_taught>' . "\n";
	$xml .= '    <time_known_student>' . esc_xml( $data['time_known_student'] ?? '' ) . '</time_known_student>' . "\n";
	$xml .= '  </teacher_information>' . "\n";
	$xml .= "\n";

	// Academic Performance
	$xml .= '  <academic_performance>' . "\n";
	$xml .= '    <reading_level>' . esc_xml( $data['reading_level'] ?? '' ) . '</reading_level>' . "\n";
	$xml .= '    <math_level>' . esc_xml( $data['math_level'] ?? '' ) . '</math_level>' . "\n";
	$xml .= '    <writing_level>' . esc_xml( $data['writing_level'] ?? '' ) . '</writing_level>' . "\n";
	$xml .= '    <overall_academic_performance>' . esc_xml( $data['academic_performance'] ?? '' ) . '</overall_academic_performance>' . "\n";
	$xml .= '    <academic_concerns>' . esc_xml( $data['academic_concerns'] ?? '' ) . '</academic_concerns>' . "\n";
	$xml .= '  </academic_performance>' . "\n";
	$xml .= "\n";

	// Behavioral Observations
	$xml .= '  <behavioral_observations>' . "\n";
	$xml .= '    <attention_focus>' . esc_xml( $data['attention_focus'] ?? '' ) . '</attention_focus>' . "\n";
	$xml .= '    <follows_directions>' . esc_xml( $data['follows_directions'] ?? '' ) . '</follows_directions>' . "\n";
	$xml .= '    <completes_tasks>' . esc_xml( $data['completes_tasks'] ?? '' ) . '</completes_tasks>' . "\n";
	$xml .= '    <impulsivity_level>' . esc_xml( $data['impulsivity'] ?? '' ) . '</impulsivity_level>' . "\n";
	$xml .= '    <hyperactivity_level>' . esc_xml( $data['hyperactivity'] ?? '' ) . '</hyperactivity_level>' . "\n";
	$xml .= '    <behavioral_concerns>' . esc_xml( $data['behavioral_concerns'] ?? '' ) . '</behavioral_concerns>' . "\n";
	$xml .= '  </behavioral_observations>' . "\n";
	$xml .= "\n";

	// Social/Emotional
	$xml .= '  <social_emotional>' . "\n";
	$xml .= '    <peer_relationships>' . esc_xml( $data['peer_relationships'] ?? '' ) . '</peer_relationships>' . "\n";
	$xml .= '    <adult_relationships>' . esc_xml( $data['adult_relationships'] ?? '' ) . '</adult_relationships>' . "\n";
	$xml .= '    <emotional_regulation>' . esc_xml( $data['emotional_regulation'] ?? '' ) . '</emotional_regulation>' . "\n";
	$xml .= '    <social_concerns>' . esc_xml( $data['social_concerns'] ?? '' ) . '</social_concerns>' . "\n";
	$xml .= '  </social_emotional>' . "\n";
	$xml .= "\n";

	// Additional Information
	$xml .= '  <additional_information>' . "\n";
	$xml .= '    <accommodations_used>' . esc_xml( $data['accommodations'] ?? '' ) . '</accommodations_used>' . "\n";
	$xml .= '    <iep_504_status>' . esc_xml( $data['iep_status'] ?? '' ) . '</iep_504_status>' . "\n";
	$xml .= '    <strengths>' . esc_xml( $data['student_strengths'] ?? '' ) . '</strengths>' . "\n";
	$xml .= '    <recommendations>' . esc_xml( $data['recommendations'] ?? '' ) . '</recommendations>' . "\n";
	$xml .= '    <additional_comments>' . esc_xml( $data['additional_comments'] ?? '' ) . '</additional_comments>' . "\n";
	$xml .= '  </additional_information>' . "\n";
	$xml .= "\n";

	$xml .= '</teacher_report>';

	return $xml;
}

/**
 * Generate readable summary
 *
 * @param array $data Posted form data
 * @param string $today Today's date
 * @return string Summary content
 */
function generate_teacher_report_summary( $data, $today ) {
	$summary = "TEACHER REPORT SUMMARY\n";
	$summary .= "======================\n\n";
	$summary .= "Submission Date: {$today}\n\n";

	$summary .= "STUDENT:\n";
	$summary .= "--------\n";
	$summary .= "Name: " . ( $data['student_first_name'] ?? '' ) . " " . ( $data['student_last_name'] ?? '' ) . "\n";
	$summary .= "DOB: " . ( $data['student_dob'] ?? 'N/A' ) . "\n\n";

	$summary .= "TEACHER:\n";
	$summary .= "--------\n";
	$summary .= "Name: " . ( $data['teacher_name'] ?? 'N/A' ) . "\n";
	$summary .= "Email: " . ( $data['teacher_email'] ?? 'N/A' ) . "\n";
	$summary .= "School: " . ( $data['school_name'] ?? 'N/A' ) . "\n";
	$summary .= "Grade/Subject: " . ( $data['grade_taught'] ?? 'N/A' ) . " / " . ( $data['subject_taught'] ?? 'N/A' ) . "\n";
	$summary .= "Time Known: " . ( $data['time_known_student'] ?? 'N/A' ) . "\n\n";

	$summary .= "ACADEMIC PERFORMANCE:\n";
	$summary .= "--------------------\n";
	$summary .= "Reading: " . ( $data['reading_level'] ?? 'N/A' ) . "\n";
	$summary .= "Math: " . ( $data['math_level'] ?? 'N/A' ) . "\n";
	$summary .= "Writing: " . ( $data['writing_level'] ?? 'N/A' ) . "\n";
	$summary .= "Overall: " . ( $data['academic_performance'] ?? 'N/A' ) . "\n";
	$summary .= "Concerns: " . ( $data['academic_concerns'] ?? 'None noted' ) . "\n\n";

	$summary .= "BEHAVIORAL OBSERVATIONS:\n";
	$summary .= "-----------------------\n";
	$summary .= "Attention/Focus: " . ( $data['attention_focus'] ?? 'N/A' ) . "\n";
	$summary .= "Follows Directions: " . ( $data['follows_directions'] ?? 'N/A' ) . "\n";
	$summary .= "Task Completion: " . ( $data['completes_tasks'] ?? 'N/A' ) . "\n";
	$summary .= "Impulsivity: " . ( $data['impulsivity'] ?? 'N/A' ) . "\n";
	$summary .= "Hyperactivity: " . ( $data['hyperactivity'] ?? 'N/A' ) . "\n";
	$summary .= "Concerns: " . ( $data['behavioral_concerns'] ?? 'None noted' ) . "\n\n";

	$summary .= "SOCIAL/EMOTIONAL:\n";
	$summary .= "----------------\n";
	$summary .= "Peer Relationships: " . ( $data['peer_relationships'] ?? 'N/A' ) . "\n";
	$summary .= "Adult Relationships: " . ( $data['adult_relationships'] ?? 'N/A' ) . "\n";
	$summary .= "Emotional Regulation: " . ( $data['emotional_regulation'] ?? 'N/A' ) . "\n";
	$summary .= "Concerns: " . ( $data['social_concerns'] ?? 'None noted' ) . "\n\n";

	$summary .= "ADDITIONAL INFO:\n";
	$summary .= "---------------\n";
	$summary .= "IEP/504: " . ( $data['iep_status'] ?? 'N/A' ) . "\n";
	$summary .= "Accommodations: " . ( $data['accommodations'] ?? 'None' ) . "\n";
	$summary .= "Strengths: " . ( $data['student_strengths'] ?? 'N/A' ) . "\n";
	$summary .= "Recommendations: " . ( $data['recommendations'] ?? 'None' ) . "\n\n";

	if ( !empty( $data['additional_comments'] ) ) {
		$summary .= "ADDITIONAL COMMENTS:\n";
		$summary .= "-------------------\n";
		$summary .= $data['additional_comments'] . "\n";
	}

	return $summary;
}
