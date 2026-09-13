# Vanderbilt Symptom Subset — Contact Form 7

This WordPress form contains 18 symptom items only. It is not a complete
Vanderbilt assessment and does not establish or exclude a diagnosis.

## Setup and runtime contract

Use the native form created by `gcm_install_evaluation_forms()` and its current
`includes/form-installer.php` template. Identity and respondent fields are required;
the symptom-section example below is not a complete standalone form. Do not edit
PHP to insert a form ID. The installer saves the native ID in WordPress options.

Configure primary clinical recipients/sender in the native Mail tab. The shared
transport sends one generic-subject email with in-memory XML and TXT attachments.
No patient data appears in the subject, and primary failure reports failure.
There is no separate diagnostic email or respondent acknowledgement for this form.

Ratings 0–3 retain their numerical meaning. Counts of items rated 2 or 3 and
thresholds of six items per nine-item domain are symptom summaries only. Missing
or malformed ratings fail instead of becoming zero. See the plugin readme for
field bounds, native transport tests and complete-versus-partial instrument limits.

## Symptom Section Example

```html
<div class="field-group-heading">
	<h3 class="title">NICHQ Vanderbilt Assessment Scale</h3>
	<p>Please rate each behavior based on the child's behavior over the past 6 months.</p>
	<p><strong>Rating:</strong> 0 = Never | 1 = Occasionally | 2 = Often | 3 = Very Often</p>
</div>

<div class="field-group field-list">
	<div class="group-label">Student Information</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="student-first-name">Student's First Name</label>
			</div>
			<div class="field-content">
				[text* student_first_name id:student-first-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="student-last-name">Student's Last Name</label>
			</div>
			<div class="field-content">
				[text* student_last_name id:student-last-name class:letters_space]
			</div>
		</div>

		<div class="field type-date is-required">
			<div class="field-label">
				<label for="student-dob">Student's Date of Birth</label>
			</div>
			<div class="field-content">
				[date* student_dob id:student-dob]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="respondent-name">Your Name (Person Completing Form)</label>
			</div>
			<div class="field-content">
				[text* respondent_name id:respondent-name class:letters_space]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="respondent-relationship">Your Relationship to Student</label>
			</div>
			<div class="field-content">
				[select* respondent_relationship id:respondent-relationship "Parent" "Teacher" "Guardian" "School Counselor" "Other"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Part 1: Inattention Symptoms (Questions 1-9)</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Inattention Items</div>
	<div class="group-fields group-columns-1">
		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>1. Fails to give attention to details or makes careless mistakes</label>
			</div>
			<div class="field-content">
				[radio q1_fails_attention use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>2. Has difficulty sustaining attention to tasks or activities</label>
			</div>
			<div class="field-content">
				[radio q2_difficulty_sustaining use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>3. Does not seem to listen when spoken to directly</label>
			</div>
			<div class="field-content">
				[radio q3_not_listening use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>4. Does not follow through on instructions and fails to finish work</label>
			</div>
			<div class="field-content">
				[radio q4_not_follow_through use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>5. Has difficulty organizing tasks and activities</label>
			</div>
			<div class="field-content">
				[radio q5_difficulty_organizing use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>6. Avoids tasks that require sustained mental effort</label>
			</div>
			<div class="field-content">
				[radio q6_avoids_tasks use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>7. Loses things necessary for tasks or activities</label>
			</div>
			<div class="field-content">
				[radio q7_loses_things use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>8. Is easily distracted by extraneous stimuli</label>
			</div>
			<div class="field-content">
				[radio q8_easily_distracted use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>9. Is forgetful in daily activities</label>
			</div>
			<div class="field-content">
				[radio q9_forgetful use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Part 2: Hyperactivity/Impulsivity Symptoms (Questions 10-18)</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Hyperactivity/Impulsivity Items</div>
	<div class="group-fields group-columns-1">
		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>10. Fidgets with hands or feet or squirms in seat</label>
			</div>
			<div class="field-content">
				[radio q10_fidgets use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>11. Leaves seat in situations when remaining seated is expected</label>
			</div>
			<div class="field-content">
				[radio q11_leaves_seat use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>12. Runs about or climbs excessively in inappropriate situations</label>
			</div>
			<div class="field-content">
				[radio q12_runs_climbs use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>13. Has difficulty playing or engaging in leisure activities quietly</label>
			</div>
			<div class="field-content">
				[radio q13_difficulty_quiet use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>14. Is "on the go" or acts as if "driven by a motor"</label>
			</div>
			<div class="field-content">
				[radio q14_on_the_go use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>15. Talks excessively</label>
			</div>
			<div class="field-content">
				[radio q15_talks_excessively use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>16. Blurts out answers before questions have been completed</label>
			</div>
			<div class="field-content">
				[radio q16_blurts_answers use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>17. Has difficulty waiting his or her turn</label>
			</div>
			<div class="field-content">
				[radio q17_difficulty_waiting use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label>18. Interrupts or intrudes on others</label>
			</div>
			<div class="field-content">
				[radio q18_interrupts use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
			</div>
		</div>
	</div>
</div>

<div class="field-group group-submit">
	<div class="group-fields group-columns-3">
		<div class="field type-submit">
			[submit "Submit Assessment" class:button]
		</div>
	</div>
</div>
```

## Export version 2

XML reports `assessment_complete=false`, `diagnosis_determined=false` and
`clinical_review_required=true`. Domain counts use `symptom_count_threshold_met`,
not `clinically_significant`; `symptom_summary` replaces `clinical_interpretation`.
No output assigns an ADHD subtype or says that a low count excludes a diagnosis.

A native rendering filter adds the subset notice to previously installed forms
without overwriting operator-edited content. Numeric calculations are unchanged.

The [publisher scoring instructions](https://nichq.org/wp-content/uploads/2024/09/07Scoring-Instructions.pdf)
include performance impairment and warn against using these scales alone for
diagnosis. Adding arbitrary fields is not complete instrument validation; use a
reviewed full-instrument workflow for that purpose. The EHR questionnaire system
is separate from this WordPress intake/export.
