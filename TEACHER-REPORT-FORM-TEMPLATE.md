# Teacher Report — Contact Form 7

## Deployment and current contract

Normal Practice Stack setup invokes `gcm_install_evaluation_forms()` and creates
the native form/page binding in `gcm_teacher_report_form_id` and its `_page` option.
Use the current form in `includes/form-installer.php`; no PHP form-ID edits or
extra includes are required. Existing editor content is retained on install retry.

Configure fixed clinical recipients/sender in CF7’s Mail tab. Public contact
settings do not select clinical recipients. The shared transport owns generic
subjects/bodies and in-memory XML/TXT attachments; do not put patient information
in email headers or send a second clinical copy to an unverified respondent.
The external respondent receives only a generic acknowledgement after primary
mail succeeds, with no patient identifiers, answers or attachments.

Primary mail/attachment failure reports failure. Mail acceptance does not prove
inbox delivery, EHR import, clinical review or appointment confirmation. Record
matching and record-submission instructions remain clinic workflows.

Only current declared form fields are exported. CF7 owns native validation and
spam/acceptance checks, with additional bounded-shape/choice checks in the shared
transport. Frontend CSS/JavaScript validation classes are not an access control.
See `readme.md` for exact bounds, recipient rules and the guarded native tests.

## HTML reference

This reference does not replace the installed form schema or deployment setup.

```html
<div class="field-group-heading">
	<div class="step">Step 1/4</div>
	<h3 class="title">Student Information</h3>
	<p>Please provide the student's information to help us match this report to their records.</p>
</div>

<div class="field-group field-list">
	<div class="group-label">Student Identifiers</div>
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
	</div>
</div>

<div class="field-group-heading">
	<div class="step">Step 2/4</div>
	<h3 class="title">Teacher Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">About You</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="teacher-name">Your Full Name</label>
			</div>
			<div class="field-content">
				[text* teacher_name id:teacher-name class:letters_space]
			</div>
		</div>

		<div class="field type-email is-required">
			<div class="field-label">
				<label for="teacher-email">Your Email Address</label>
			</div>
			<div class="field-content">
				[email* teacher_email id:teacher-email]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="school-name">School Name</label>
			</div>
			<div class="field-content">
				[text* school_name id:school-name]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="grade-taught">Grade Level You Teach</label>
			</div>
			<div class="field-content">
				[text* grade_taught id:grade-taught]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="subject-taught">Subject(s) You Teach (Optional)</label>
			</div>
			<div class="field-content">
				[text subject_taught id:subject-taught]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="time-known">How long have you known this student?</label>
			</div>
			<div class="field-content">
				[select* time_known_student id:time-known "Less than 1 month" "1-3 months" "4-6 months" "7-12 months" "1-2 years" "More than 2 years"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<div class="step">Step 3/4</div>
	<h3 class="title">Academic & Behavioral Observations</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Academic Performance</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="reading-level">Reading Level Compared to Grade</label>
			</div>
			<div class="field-content">
				[select* reading_level id:reading-level "Well Above Grade Level" "Above Grade Level" "At Grade Level" "Below Grade Level" "Well Below Grade Level" "Unable to Assess"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="math-level">Math Level Compared to Grade</label>
			</div>
			<div class="field-content">
				[select* math_level id:math-level "Well Above Grade Level" "Above Grade Level" "At Grade Level" "Below Grade Level" "Well Below Grade Level" "Unable to Assess"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="writing-level">Writing Level Compared to Grade</label>
			</div>
			<div class="field-content">
				[select* writing_level id:writing-level "Well Above Grade Level" "Above Grade Level" "At Grade Level" "Below Grade Level" "Well Below Grade Level" "Unable to Assess"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="academic-performance">Overall Academic Performance</label>
			</div>
			<div class="field-content">
				[select* academic_performance id:academic-performance "Excellent" "Above Average" "Average" "Below Average" "Struggling Significantly"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="academic-concerns">Academic Concerns (Optional)</label>
			</div>
			<div class="field-content">
				[textarea academic_concerns id:academic-concerns placeholder:"Describe any specific academic challenges or patterns you've observed"]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Behavioral Observations</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="attention-focus">Attention & Focus</label>
			</div>
			<div class="field-content">
				[select* attention_focus id:attention-focus "Excellent - Sustained focus" "Good - Usually focused" "Fair - Frequently distracted" "Poor - Rarely focused" "Very Poor - Cannot maintain focus"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="follows-directions">Follows Directions</label>
			</div>
			<div class="field-content">
				[select* follows_directions id:follows-directions "Always" "Usually" "Sometimes" "Rarely" "Never"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="completes-tasks">Completes Assignments</label>
			</div>
			<div class="field-content">
				[select* completes_tasks id:completes-tasks "Always on time" "Usually completes" "Sometimes completes" "Rarely completes" "Never completes"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="impulsivity">Impulsivity Level</label>
			</div>
			<div class="field-content">
				[select* impulsivity id:impulsivity "Not impulsive" "Slightly impulsive" "Moderately impulsive" "Very impulsive" "Extremely impulsive"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="hyperactivity">Hyperactivity/Restlessness</label>
			</div>
			<div class="field-content">
				[select* hyperactivity id:hyperactivity "Not hyperactive" "Slightly restless" "Moderately active" "Very hyperactive" "Extremely hyperactive"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="behavioral-concerns">Behavioral Concerns (Optional)</label>
			</div>
			<div class="field-content">
				[textarea behavioral_concerns id:behavioral-concerns placeholder:"Describe any behavioral challenges or patterns"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<div class="step">Step 4/4</div>
	<h3 class="title">Social, Emotional & Additional Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Social & Emotional Functioning</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="peer-relationships">Peer Relationships</label>
			</div>
			<div class="field-content">
				[select* peer_relationships id:peer-relationships "Excellent social skills" "Good with peers" "Some difficulties" "Significant difficulties" "Very isolated/withdrawn"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="adult-relationships">Relationships with Adults</label>
			</div>
			<div class="field-content">
				[select* adult_relationships id:adult-relationships "Excellent rapport" "Good interactions" "Some difficulties" "Significant difficulties" "Very oppositional/avoidant"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="emotional-regulation">Emotional Regulation</label>
			</div>
			<div class="field-content">
				[select* emotional_regulation id:emotional-regulation "Excellent self-control" "Good regulation" "Some difficulties" "Frequent outbursts" "Severe dysregulation"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="social-concerns">Social/Emotional Concerns (Optional)</label>
			</div>
			<div class="field-content">
				[textarea social_concerns id:social-concerns placeholder:"Describe any social or emotional challenges"]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Support & Services</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="iep-status">IEP or 504 Plan Status</label>
			</div>
			<div class="field-content">
				[select* iep_status id:iep-status "Has IEP" "Has 504 Plan" "In evaluation process" "No services" "Unknown"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="accommodations">Current Accommodations (Optional)</label>
			</div>
			<div class="field-content">
				[textarea accommodations id:accommodations placeholder:"List any accommodations or modifications currently in place"]
			</div>
		</div>

		<div class="field type-textarea is-required">
			<div class="field-label">
				<label for="student-strengths">Student's Strengths</label>
			</div>
			<div class="field-content">
				[textarea* student_strengths id:student-strengths placeholder:"What does this student do well? What are their interests or talents?"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="recommendations">Your Recommendations (Optional)</label>
			</div>
			<div class="field-content">
				[textarea recommendations id:recommendations placeholder:"What strategies or supports have been helpful? What would you recommend?"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="additional-comments">Additional Comments (Optional)</label>
			</div>
			<div class="field-content">
				[textarea additional_comments id:additional-comments placeholder:"Any other information that would be helpful for the evaluation"]
			</div>
		</div>
	</div>
</div>

<div class="field-group group-submit">
	<div class="group-fields group-columns-3">
		<div class="field type-submit">
			[submit "Submit Teacher Report" class:button]
		</div>
	</div>
</div>
```

## Testing and clinical/privacy boundaries

Run Practice Stack’s `native-submissions` acceptance action on its isolated
synthetic fixture. The owning `tests/native-clinical-mail.php` probe uses actual
CF7 and PHPMailer MIME/result hooks, with transport intercepted before delivery.
Do not run that fixture or transmit sample clinical submissions to real recipients.

The web form remains available for its intended intake/external role; it does not
require an EHR account or grant access to an existing patient record. Any downstream
matching/import must be separately authenticated and verified. This template is
not a certification of HIPAA compliance or of a deployed mail provider’s controls.
