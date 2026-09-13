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

// Submission transport is shared in clinical-form-mail.php.

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
