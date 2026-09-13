# Developmental Intake — Contact Form 7

## Deployment and current contract

Normal Practice Stack setup invokes `gcm_install_evaluation_forms()` and creates
the native form/page binding in `gcm_developmental_eval_form_id` and its `_page` option.
Use the current form in `includes/form-installer.php`; no PHP form-ID edits or
extra includes are required. Existing editor content is retained on install retry.

Configure fixed clinical recipients/sender in CF7’s Mail tab. Public contact
settings do not select clinical recipients. The shared transport owns generic
subjects/bodies and in-memory XML/TXT attachments; do not put patient information
in email headers or send a second clinical copy to an unverified respondent.
This form sends one primary clinical mail, without a respondent acknowledgement.

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
	<div class="step">Step 1/3</div>
	<h3 class="title">Child Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Child's Full Name</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="child-first-name">First Name</label>
			</div>
			<div class="field-content">
				[text* child_first_name id:child-first-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="child-last-name">Last Name</label>
			</div>
			<div class="field-content">
				[text* child_last_name id:child-last-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="child-middle-name">Middle Name (Optional)</label>
			</div>
			<div class="field-content">
				[text child_middle_name id:child-middle-name class:letters_space]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Child's Details</div>
	<div class="group-fields group-columns-3">
		<div class="field type-date is-required">
			<div class="field-label">
				<label for="child-dob">Date of Birth</label>
			</div>
			<div class="field-content">
				[date* child_dob id:child-dob]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label for="child-gender">Gender</label>
			</div>
			<div class="field-content">
				[radio child_gender id:child-gender use_label_element default:1 "Male" "Female" "Non-binary" "Prefer not to say"]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="grade-level">Current Grade Level (Optional)</label>
			</div>
			<div class="field-content">
				[text grade_level id:grade-level]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="school-name">School Name (Optional)</label>
			</div>
			<div class="field-content">
				[text school_name id:school-name class:letters_space]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<div class="step">Step 2/3</div>
	<h3 class="title">Parent/Guardian Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Parent/Guardian Name</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="parent-first-name">First Name</label>
			</div>
			<div class="field-content">
				[text* parent_first_name id:parent-first-name class:letters_space]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="parent-last-name">Last Name</label>
			</div>
			<div class="field-content">
				[text* parent_last_name id:parent-last-name class:letters_space]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="relationship">Relationship to Child</label>
			</div>
			<div class="field-content">
				[select* relationship id:relationship "Mother" "Father" "Legal Guardian" "Grandparent" "Other"]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Contact Information</div>
	<div class="group-fields group-columns-3">
		<div class="field type-email is-required">
			<div class="field-label">
				<label for="parent-email">Email Address</label>
			</div>
			<div class="field-content">
				[email* parent_email id:parent-email]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="phone-primary">Primary Phone</label>
			</div>
			<div class="field-content">
				[tel* phone_primary id:phone-primary class:digits class:JVmaxlength-10 class:JVminlength-10]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="phone-secondary">Secondary Phone (Optional)</label>
			</div>
			<div class="field-content">
				[tel phone_secondary id:phone-secondary class:digits class:JVmaxlength-10 class:JVminlength-10]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Home Address</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-required">
			<div class="field-label">
				<label for="street">Street Address</label>
			</div>
			<div class="field-content">
				[text* street id:street]
			</div>
		</div>

		<div class="field type-radio-button-row is-optional">
			<div class="field-label">
				<label for="apartment-type">Apartment Type (Optional)</label>
			</div>
			<div class="field-content">
				[radio apartment_type id:apartment-type use_label_element "APT" "STE" "FLR"]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="apartment-number">Apartment Number (Optional)</label>
			</div>
			<div class="field-content">
				[text apartment_number id:apartment-number]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="city">City</label>
			</div>
			<div class="field-content">
				[text* city id:city class:letters_space]
			</div>
		</div>

		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="state">State</label>
			</div>
			<div class="field-content">
				[select* state id:state "AL" "AK" "AZ" "AR" "CA" "CO" "CT" "DE" "FL" "GA" "HI" "ID" "IL" "IN" "IA" "KS" "KY" "LA" "ME" "MD" "MA" "MI" "MN" "MS" "MO" "MT" "NE" "NV" "NH" "NJ" "NM" "NY" "NC" "ND" "OH" "OK" "OR" "PA" "RI" "SC" "SD" "TN" "TX" "UT" "VT" "VA" "WA" "WV" "WI" "WY"]
			</div>
		</div>

		<div class="field type-text is-required">
			<div class="field-label">
				<label for="zip-code">Zip Code</label>
			</div>
			<div class="field-content">
				[text* zip_code id:zip-code class:digits class:JVmaxlength-5 class:JVminlength-5]
			</div>
		</div>
	</div>
</div>

<div class="field-group-heading">
	<div class="step">Step 3/3</div>
	<h3 class="title">Evaluation Information</h3>
</div>

<div class="field-group field-list">
	<div class="group-label">Evaluation Details</div>
	<div class="group-fields group-columns-3">
		<div class="field type-dropdown is-required">
			<div class="field-label">
				<label for="evaluation-type">Type of Evaluation Requested</label>
			</div>
			<div class="field-content">
				[select* evaluation_type id:evaluation-type "ADHD Evaluation" "Autism Spectrum Evaluation" "Learning Disability Assessment" "Developmental Delay Evaluation" "General Developmental Evaluation" "Other"]
			</div>
		</div>

		<div class="field type-textarea is-required">
			<div class="field-label">
				<label for="primary-concerns">Primary Concerns (What brings you here today?)</label>
			</div>
			<div class="field-content">
				[textarea* primary_concerns id:primary-concerns]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label for="previous-evaluations">Has your child had previous evaluations?</label>
			</div>
			<div class="field-content">
				[radio previous_evaluations id:previous-evaluations use_label_element "Yes" "No"]
			</div>
		</div>

		<div class="field type-textarea is-optional">
			<div class="field-label">
				<label for="current-services">Current Services/Therapies (Optional)</label>
			</div>
			<div class="field-content">
				[textarea current_services id:current-services placeholder:"e.g., Speech therapy, OT, etc."]
			</div>
		</div>

		<div class="field type-radio-button-row is-required">
			<div class="field-label">
				<label for="referral-source">How did you hear about us?</label>
			</div>
			<div class="field-content">
				[radio referral_source id:referral-source use_label_element "Pediatrician" "School" "Friend/Family" "Insurance Provider" "Online Search" "Social Media" "Other"]
			</div>
		</div>
	</div>
</div>

<div class="field-group field-list">
	<div class="group-label">Insurance & Scheduling</div>
	<div class="group-fields group-columns-3">
		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="insurance-name">Insurance Provider (Optional)</label>
			</div>
			<div class="field-content">
				[text insurance_name id:insurance-name]
			</div>
		</div>

		<div class="field type-text is-optional">
			<div class="field-label">
				<label for="insurance-id">Insurance ID (Optional)</label>
			</div>
			<div class="field-content">
				[text insurance_id id:insurance-id]
			</div>
		</div>

		<div class="field type-dropdown is-optional">
			<div class="field-label">
				<label for="preferred-time">Preferred Appointment Time (Optional)</label>
			</div>
			<div class="field-content">
				[select preferred_time id:preferred-time "Morning (8am-12pm)" "Afternoon (12pm-4pm)" "Evening (4pm-7pm)" "Weekends" "Flexible"]
			</div>
		</div>
	</div>
</div>

<div class="field-group group-submit">
	<div class="group-fields group-columns-3">
		<div class="field type-submit">
			[submit "Submit Intake Form" class:button]
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
