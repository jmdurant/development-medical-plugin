# NICHQ Vanderbilt Assessment Scale - Contact Form 7 Template

Public domain ADHD screening tool with automatic scoring and clinical interpretation.

## Scoring Criteria

**Rating Scale:**
- 0 = Never
- 1 = Occasionally
- 2 = Often
- 3 = Very Often

**Clinical Significance:**
- **Inattention**: 6 or more items (out of 9) rated 2 or 3
- **Hyperactivity/Impulsivity**: 6 or more items (out of 9) rated 2 or 3

**Interpretations:**
- Inattention ONLY positive → **ADHD, Predominantly Inattentive Type**
- Hyperactivity/Impulsivity ONLY positive → **ADHD, Predominantly Hyperactive-Impulsive Type**
- BOTH positive → **ADHD, Combined Type**
- Neither positive → Does not meet ADHD criteria

## Setup Instructions

1. Create new Contact Form 7 form
2. Copy HTML template below
3. Note Form ID
4. Edit `vanderbilt-scoring.php` line 22, replace `XXXX` with form ID
5. Add to `developmentalondemand.php`:
   ```php
   include( __DIR__ . '/includes/vanderbilt-scoring.php' );
   ```

## Features

- **Automatic Scoring** - Calculates domain scores instantly
- **Clinical Interpretation** - Provides ADHD type indication
- **Detailed Email** - Results in subject line for quick triage
- **XML Export** - Structured data with scores and interpretation
- **Summary Report** - Human-readable TXT file

## Contact Form 7 HTML Template

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

## What Happens After Submission

1. **Automatic Scoring**:
   - Counts items rated 2-3 in each domain
   - Determines if ≥6 items in each domain
   - Calculates raw scores

2. **Email Sent with Results**:
   - Subject: "Vanderbilt Assessment - [Name] - [Interpretation]"
   - Body includes domain results and interpretation
   - XML attachment with full data
   - TXT summary report

3. **Clinical Interpretation Provided**:
   - ADHD, Combined Type (both positive)
   - ADHD, Predominantly Inattentive Type (inattention only)
   - ADHD, Predominantly Hyperactive-Impulsive Type (hyperactivity only)
   - Does not meet ADHD criteria (neither positive)

## Example Email Output

```
Subject: Vanderbilt Assessment - John Doe - Indicative of ADHD, Combined Type

Vanderbilt Assessment Results

Student: John Doe
DOB: 05/15/2015
Completed by: Mrs. Smith (Teacher)
Date: 10/03/2025

RESULTS:
--------
Inattention: 7/9 items rated 2-3 (POSITIVE)
Hyperactivity/Impulsivity: 8/9 items rated 2-3 (POSITIVE)

Clinical Interpretation: Indicative of ADHD, Combined Type

See attached files for complete results.
```

## Important Clinical Notes

- This is a **screening tool**, not a diagnostic instrument
- Positive results indicate need for comprehensive evaluation
- Diagnosis requires:
  - Clinical interview
  - Multiple informants (parent + teacher reports)
  - Assessment of functional impairment
  - Rule out other conditions

## Adding Performance & Behavior Sections

The full Vanderbilt also includes:
- Academic performance ratings
- Classroom behavioral performance
- Additional questions about comorbid conditions

These can be added as optional fields without affecting the core ADHD scoring.

## Customization

To add parent version:
1. Copy this template
2. Change wording ("your child" instead of "the student")
3. Use different form ID
4. Adapt for home settings vs. classroom

To add follow-up versions:
- Track scores over time
- Compare pre/post intervention
- Monitor medication effects
