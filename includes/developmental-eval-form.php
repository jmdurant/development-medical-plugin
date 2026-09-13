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

// Submission transport is shared in clinical-form-mail.php.

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
