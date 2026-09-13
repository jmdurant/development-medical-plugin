# PCP Referral — Contact Form 7

## Deployment and current contract

Normal Practice Stack setup invokes `gcm_install_evaluation_forms()` and creates
the native form/page binding in `gcm_pcp_referral_form_id` and its `_page` option.
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
	<h3 class="title">Primary Care Physician Referral Form</h3>
	<p>Please complete this form to refer a patient for developmental evaluation. Medical records should be sent separately via secure fax.</p>
</div>

<div class="field-group field-list">
	<div class="group-label">Patient Information</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="patient-first-name">Patient's First Name</label>
			</div>
			<div class="field-content">
				[text* patient_first_name id:patient-first-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="patient-last-name">Patient's Last Name</label>
			</div>
			<div class="field-content">
				[text* patient_last_name id:patient-last-name class:letters_space]
			</div>
		</div>

		<div class="field type-date is-required">
			<div class="field-label">
				<label for="patient-dob">Patient's Date of Birth</label>
			</div>
			<div class="field-content">
				[date* patient_dob id:patient-dob]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Referring Physician Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Your Practice Details</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="physician-name">Your Name</label>
			</div>
			<div class="field-content">
				[text* physician_name id:physician-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="practice-name">Practice Name</label>
			</div>
			<div class="field-content">
				[text* practice_name id:practice-name]
			</div>
		</div>

		<div class="field type-tel is-required">
			<div class="field-label">
				<label for="physician-phone">Phone Number</label>
			</div>
			<div class="field-content">
				[tel* physician_phone id:physician-phone class:digits]
			</div>
		</div>

		<div class="field type-tel is-required">
			<div class="field-label">
				<label for="physician-fax">Fax Number</label>
			</div>
			<div class="field-content">
				[tel* physician_fax id:physician-fax class:digits]
			</div>
		</div>

		<div class="field type-email is-required">
			<div class="field-label">
				<label for="physician-email">Email Address</label>
			</div>
			<div class="field-content">
				[email* physician_email id:physician-email]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="practice-address">Practice Address (Optional)</label>
			</div>
			<div class="field-content">
				[text practice_address id:practice-address]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Referral Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Evaluation Request</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="referral-reason">Reason for Referral</label>
			</div>
			<div class="field-content">
				[select* referral_reason id:referral-reason "ADHD Evaluation" "Autism Spectrum Evaluation" "Learning Disability Assessment" "Developmental Delay Evaluation" "Behavioral Concerns" "Academic Difficulties" "Other"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="urgency-level">Urgency Level</label>
			</div>
			<div class="field-content">
				[select* urgency_level id:urgency-level "Routine" "Moderate - Schedule within 4-6 weeks" "Urgent - Schedule within 2 weeks"]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="preferred-timeframe">Preferred Appointment Timeframe</label>
			</div>
			<div class="field-content">
				[select* preferred_timeframe id:preferred-timeframe "Morning (8am-12pm)" "Afternoon (12pm-4pm)" "After School (4pm-6pm)" "Flexible"]
			</div>
		</div>

		<div class="field type-textarea is-required">
			<div class="field-label">
				<label for="chief-complaint">Chief Complaint / Clinical Concerns</label>
			</div>
			<div class="field-content">
				[textarea* chief_complaint id:chief-complaint placeholder:"Brief description of primary concerns, symptoms, or behaviors (1-3 sentences)"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Parent/Guardian Contact Information</h3>
	<p>We will contact the parent/guardian directly to schedule the evaluation.</p>
</div>

<div class="field-group field-list">
	<div class="group-label">Parent Contact for Scheduling</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="parent-name">Parent/Guardian Name</label>
			</div>
			<div class="field-content">
				[text* parent_name id:parent-name class:letters_space]
			</div>
		</div>

		<div class="field type-tel is-required">
			<div class="field-label">
				<label for="parent-phone">Parent Phone Number</label>
			</div>
			<div class="field-content">
				[tel* parent_phone id:parent-phone class:digits]
			</div>
		</div>

		<div class="field type-email is-optional">
			<div class="field-label">
				<label for="parent-email">Parent Email (Optional)</label>
			</div>
			<div class="field-content">
				[email parent_email id:parent-email]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Insurance Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Insurance (Plan Name Only)</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="insurance-name">Insurance Plan Name</label>
			</div>
			<div class="field-content">
				[text insurance_name id:insurance-name placeholder:"e.g., Blue Cross Blue Shield, Aetna, Medicaid"]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<h3 class="title">Medical Records</h3>
	<p><strong>Important:</strong> Contact the clinic for instructions on sending medical records separately. Do not attach records to this referral form.</p>
</div>

<div class="field-group group-submit">
	<div class="group-fields group-columns-3">
		<div class="field type-submit">
			[submit "Submit Referral" class:button]
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
