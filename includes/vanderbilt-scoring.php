<?php
/**
 * NICHQ Vanderbilt Assessment Scale Scoring
 *
 * Numeric summaries for the installed 18-item Vanderbilt symptom subset.
 *
 * Symptom-count thresholds:
 * - Inattention: 6 or more items rated 2 or 3 (out of 9 questions)
 * - Hyperactivity/Impulsivity: 6 or more items rated 2 or 3 (out of 9 questions)
 *
 * These counts do not include performance impairment or establish/exclude a diagnosis.
 * The complete instrument and clinical evaluation remain distinct from this subset.
 */

// Submission transport is shared in clinical-form-mail.php.

function gcm_vanderbilt_subset_notice($html) {
    if (GCM_Clinical_Form_Mail::kind(wpcf7_get_current_contact_form()) !== 'vanderbilt') { return $html; }
    return '<p class="gcm-assessment-notice">This form contains 18 symptom items only. It is not a complete Vanderbilt assessment and cannot establish or exclude a diagnosis. A clinician must review the results.</p>' . $html;
}
add_filter('wpcf7_form_elements', 'gcm_vanderbilt_subset_notice', 20);

/**
 * Calculate symptom counts without making a diagnosis
 *
 * @param array $data Form submission data
 * @return array Scoring results
 */
function calculate_vanderbilt_scores( $data ) {
	// Inattention items (questions 1-9)
	$inattention_items = array(
		'q1_fails_attention',
		'q2_difficulty_sustaining',
		'q3_not_listening',
		'q4_not_follow_through',
		'q5_difficulty_organizing',
		'q6_avoids_tasks',
		'q7_loses_things',
		'q8_easily_distracted',
		'q9_forgetful'
	);

	// Hyperactivity/Impulsivity items (questions 10-18)
	$hyperactivity_items = array(
		'q10_fidgets',
		'q11_leaves_seat',
		'q12_runs_climbs',
		'q13_difficulty_quiet',
		'q14_on_the_go',
		'q15_talks_excessively',
		'q16_blurts_answers',
		'q17_difficulty_waiting',
		'q18_interrupts'
	);

	// Missing or malformed answers are not a zero score. Accept the native form's
	// labels or their explicit numeric pipe values, never arrays or numeric prefixes.
	$ratings = array('0' => 0, '0 - Never' => 0, '1' => 1, '1 - Occasionally' => 1,
		'2' => 2, '2 - Often' => 2, '3' => 3, '3 - Very Often' => 3);
	foreach (array_merge($inattention_items, $hyperactivity_items) as $item) {
		$value = $data[$item] ?? null;
		if (!is_string($value) || !array_key_exists($value, $ratings)) {
			throw new UnexpectedValueException('Every Vanderbilt item requires an explicit valid rating');
		}
		$data[$item] = $ratings[$value];
	}

	// Count items rated 2 or 3 for inattention
	$inattention_count = 0;
	foreach ( $inattention_items as $item ) {
		$value = (int) ( $data[$item] ?? 0 );
		if ( $value >= 2 ) {
			$inattention_count++;
		}
	}

	// Count items rated 2 or 3 for hyperactivity/impulsivity
	$hyperactivity_count = 0;
	foreach ( $hyperactivity_items as $item ) {
		$value = (int) ( $data[$item] ?? 0 );
		if ( $value >= 2 ) {
			$hyperactivity_count++;
		}
	}

	// Determine clinical significance (6 or more = positive)
	$inattention_positive = ( $inattention_count >= 6 );
	$hyperactivity_positive = ( $hyperactivity_count >= 6 );

	// Symptom counts only: this partial form does not assess impairment or diagnose.
	$interpretation = '';
	if ( $inattention_positive && $hyperactivity_positive ) {
		$interpretation = 'Both symptom-count thresholds reached; clinician review required.';
	} elseif ( $inattention_positive ) {
		$interpretation = 'Inattention symptom-count threshold reached; clinician review required.';
	} elseif ( $hyperactivity_positive ) {
		$interpretation = 'Hyperactivity/impulsivity symptom-count threshold reached; clinician review required.';
	} else {
		$interpretation = 'Neither symptom-count threshold reached. This partial form does not exclude a diagnosis.';
	}

	// Calculate raw scores (sum of all ratings)
	$inattention_raw = 0;
	foreach ( $inattention_items as $item ) {
		$inattention_raw += (int) ( $data[$item] ?? 0 );
	}

	$hyperactivity_raw = 0;
	foreach ( $hyperactivity_items as $item ) {
		$hyperactivity_raw += (int) ( $data[$item] ?? 0 );
	}

	return array(
		'assessment_complete' => false,
		'diagnosis_determined' => false,
		'clinical_review_required' => true,
		'inattention_count' => $inattention_count,
		'inattention_positive' => $inattention_positive,
		'inattention_raw_score' => $inattention_raw,
		'hyperactivity_count' => $hyperactivity_count,
		'hyperactivity_positive' => $hyperactivity_positive,
		'hyperactivity_raw_score' => $hyperactivity_raw,
		'interpretation' => $interpretation
	);
}

/**
 * Generate XML with Vanderbilt scores
 *
 * @param array $data Form data
 * @param array $scores Calculated scores
 * @param string $formatted_dob Formatted DOB
 * @param string $today Today's date
 * @return string XML content
 */
function generate_vanderbilt_xml( $data, $scores, $formatted_dob, $today ) {
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<vanderbilt_assessment>' . "\n";
	$xml .= '  <assessment_info>' . "\n";
	$xml .= '    <submission_date>' . esc_xml( $today ) . '</submission_date>' . "\n";
	$xml .= '    <form_type>NICHQ Vanderbilt Assessment Scale</form_type>' . "\n";
	$xml .= '  </assessment_info>' . "\n\n";

	// Student info
	$xml .= '  <student>' . "\n";
	$xml .= '    <first_name>' . esc_xml( $data['student_first_name'] ?? '' ) . '</first_name>' . "\n";
	$xml .= '    <last_name>' . esc_xml( $data['student_last_name'] ?? '' ) . '</last_name>' . "\n";
	$xml .= '    <date_of_birth>' . esc_xml( $formatted_dob ) . '</date_of_birth>' . "\n";
	$xml .= '  </student>' . "\n\n";

	// Respondent info
	$xml .= '  <respondent>' . "\n";
	$xml .= '    <name>' . esc_xml( $data['respondent_name'] ?? '' ) . '</name>' . "\n";
	$xml .= '    <relationship>' . esc_xml( $data['respondent_relationship'] ?? '' ) . '</relationship>' . "\n";
	$xml .= '  </respondent>' . "\n\n";

	// Scores and interpretation
	$xml .= '  <scoring_results export_version="2">' . "\n";
	$xml .= '    <assessment_complete>false</assessment_complete>' . "\n";
	$xml .= '    <diagnosis_determined>false</diagnosis_determined>' . "\n";
	$xml .= '    <clinical_review_required>true</clinical_review_required>' . "\n";
	$xml .= '    <inattention>' . "\n";
	$xml .= '      <items_rated_2_or_3>' . $scores['inattention_count'] . '</items_rated_2_or_3>' . "\n";
	$xml .= '      <raw_score>' . $scores['inattention_raw_score'] . '</raw_score>' . "\n";
	$xml .= '      <symptom_count_threshold_met>' . ( $scores['inattention_positive'] ? 'Yes' : 'No' ) . '</symptom_count_threshold_met>' . "\n";
	$xml .= '    </inattention>' . "\n";
	$xml .= '    <hyperactivity_impulsivity>' . "\n";
	$xml .= '      <items_rated_2_or_3>' . $scores['hyperactivity_count'] . '</items_rated_2_or_3>' . "\n";
	$xml .= '      <raw_score>' . $scores['hyperactivity_raw_score'] . '</raw_score>' . "\n";
	$xml .= '      <symptom_count_threshold_met>' . ( $scores['hyperactivity_positive'] ? 'Yes' : 'No' ) . '</symptom_count_threshold_met>' . "\n";
	$xml .= '    </hyperactivity_impulsivity>' . "\n";
	$xml .= '    <symptom_summary>' . esc_xml( $scores['interpretation'] ) . '</symptom_summary>' . "\n";
	$xml .= '  </scoring_results>' . "\n\n";

	// Individual responses (all 18 symptom items)
	$xml .= '  <symptom_responses>' . "\n";

	// Questions 1-9 (Inattention)
	for ( $i = 1; $i <= 9; $i++ ) {
		$field_map = array(
			1 => 'q1_fails_attention',
			2 => 'q2_difficulty_sustaining',
			3 => 'q3_not_listening',
			4 => 'q4_not_follow_through',
			5 => 'q5_difficulty_organizing',
			6 => 'q6_avoids_tasks',
			7 => 'q7_loses_things',
			8 => 'q8_easily_distracted',
			9 => 'q9_forgetful'
		);
		$field = $field_map[$i];
		$value = $data[$field] ?? '0';
		$xml .= "    <question_{$i}_inattention>{$value}</question_{$i}_inattention>" . "\n";
	}

	// Questions 10-18 (Hyperactivity/Impulsivity)
	for ( $i = 10; $i <= 18; $i++ ) {
		$field_map = array(
			10 => 'q10_fidgets',
			11 => 'q11_leaves_seat',
			12 => 'q12_runs_climbs',
			13 => 'q13_difficulty_quiet',
			14 => 'q14_on_the_go',
			15 => 'q15_talks_excessively',
			16 => 'q16_blurts_answers',
			17 => 'q17_difficulty_waiting',
			18 => 'q18_interrupts'
		);
		$field = $field_map[$i];
		$value = $data[$field] ?? '0';
		$xml .= "    <question_{$i}_hyperactivity>{$value}</question_{$i}_hyperactivity>" . "\n";
	}

	$xml .= '  </symptom_responses>' . "\n";
	$xml .= '</vanderbilt_assessment>';

	return $xml;
}

/**
 * Generate summary report
 *
 * @param array $data Form data
 * @param array $scores Calculated scores
 * @param string $today Today's date
 * @return string Summary text
 */
function generate_vanderbilt_summary( $data, $scores, $today ) {
	$summary = "NICHQ VANDERBILT ASSESSMENT SCALE - RESULTS\n";
	$summary .= "============================================\n\n";
	$summary .= "Date: {$today}\n";
	$summary .= "Student: " . ( $data['student_first_name'] ?? '' ) . " " . ( $data['student_last_name'] ?? '' ) . "\n";
	$summary .= "DOB: " . ( $data['student_dob'] ?? 'N/A' ) . "\n";
	$summary .= "Completed by: " . ( $data['respondent_name'] ?? 'Unknown' ) . " (" . ( $data['respondent_relationship'] ?? 'N/A' ) . ")\n\n";

	$summary .= "SCORING CRITERIA:\n";
	$summary .= "-----------------\n";
	$summary .= "Each item rated on 0-3 scale:\n";
	$summary .= "  0 = Never    1 = Occasionally    2 = Often    3 = Very Often\n\n";
	$summary .= "Clinical significance: 6 or more items rated 2-3 in a domain\n\n";

	$summary .= "RESULTS:\n";
	$summary .= "--------\n\n";

	$summary .= "INATTENTION DOMAIN (Questions 1-9):\n";
	$summary .= "  Items rated 2 or 3: {$scores['inattention_count']} out of 9\n";
	$summary .= "  Raw score: {$scores['inattention_raw_score']}\n";
	$summary .= "  Symptom-count threshold reached: " . ( $scores['inattention_positive'] ? "YES (≥6 items)" : "No (<6 items)" ) . "\n\n";

	$summary .= "HYPERACTIVITY/IMPULSIVITY DOMAIN (Questions 10-18):\n";
	$summary .= "  Items rated 2 or 3: {$scores['hyperactivity_count']} out of 9\n";
	$summary .= "  Raw score: {$scores['hyperactivity_raw_score']}\n";
	$summary .= "  Symptom-count threshold reached: " . ( $scores['hyperactivity_positive'] ? "YES (≥6 items)" : "No (<6 items)" ) . "\n\n";

	$summary .= "SYMPTOM-COUNT SUMMARY:\n";
	$summary .= "------------------------\n";
	$summary .= $scores['interpretation'] . "\n\n";

	$summary .= "IMPORTANT NOTE:\n";
	$summary .= "--------------\n";
	$summary .= "This is an 18-item symptom subset, not a complete Vanderbilt assessment.\n";
	$summary .= "These counts alone cannot establish or exclude a diagnosis.\n";
	$summary .= "Diagnosis requires clinical interview, multiple informants, and\n";
	$summary .= "assessment of functional impairment across multiple settings.\n";

	return $summary;
}
