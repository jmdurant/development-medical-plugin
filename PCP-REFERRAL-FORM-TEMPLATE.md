# PCP Referral Form Template

This form allows primary care physicians to submit referral information for patients needing developmental evaluations **without transmitting detailed medical records via unsecured email**.

## Key Features

- **Minimal PHI** - Only patient name + DOB for matching
- **Secure Records Workflow** - Medical records sent separately via secure fax
- **Automatic Confirmation** - Physician receives confirmation with fax instructions
- **Structured Data** - Generates XML for easy processing
- **Parent Contact Info** - Enables direct scheduling contact

## Setup Instructions

1. **Create Contact Form 7 form**: WordPress Admin → Contact → Contact Forms → Add New
2. **Copy the HTML template** below into the form editor
3. **Note the Form ID** (shown in URL or form list)
4. **Update PHP file**: Edit `includes/pcp-referral-form.php` line 22, replace `XXXX` with form ID
5. **Update fax number**: Edit line 86 in PHP file with your practice fax number
6. **Include the file**: Add to `developmentalondemand.php`:
   ```php
   include( __DIR__ . '/includes/pcp-referral-form.php' );
   ```
7. **Share link with PCPs**: Provide referral form URL

## Workflow

1. **PCP completes online referral** - Provides patient identifiers and referral reason
2. **System processes** - Generates XML attachment and sends to admin email
3. **PCP gets confirmation** - Email includes fax number for medical records
4. **PCP faxes records** - Sends actual medical documentation via secure fax
5. **Staff matches referral** - Uses patient name + DOB to link to patient record
6. **Staff contacts parent** - Schedules evaluation using contact info from referral

## Contact Form 7 HTML Template

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
	<p><strong>Important:</strong> Please fax medical records, growth charts, developmental history, and any previous evaluation reports separately to our secure fax line. You will receive our fax number in the confirmation email.</p>
</div>

<div class="field-group group-submit">
	<div class="group-fields group-columns-3">
		<div class="field type-submit">
			[submit "Submit Referral" class:button]
		</div>
	</div>
</div>
```

## What Happens After Submission

1. **Staff receives email** with:
   - Subject: "PCP Referral - [Patient Name] - [Referral Reason]"
   - Body with referral summary
   - XML attachment with structured data
   - TXT summary for quick review

2. **Physician receives confirmation** with:
   - Thank you message
   - Practice fax number for medical records
   - Assurance that family will be contacted

3. **Staff workflow**:
   - Match referral to patient record using name + DOB
   - Create/update patient file
   - Contact parent to schedule evaluation
   - Request medical records via fax if not received
   - Send evaluation report to referring physician when complete

## Example Email Output

**To Staff:**
```
Subject: PCP Referral - John Doe - ADHD Evaluation

PCP Referral Form Submission

Patient: John Doe
DOB: 05/15/2015
Referring Physician: Dr. Sarah Johnson
Practice: Pediatric Associates
Referral Reason: ADHD Evaluation
Submission Date: 10/03/2025

Please find attached referral data.
Match this referral to patient record using patient name and DOB.
Request medical records via secure fax if not already received.
```

**To Referring Physician:**
```
Subject: Referral Received - John Doe

Dear Dr. Sarah Johnson,

Thank you for referring John Doe to Developmental On Demand for evaluation.

We have received your referral and will contact the family to schedule an appointment.

If you have not already done so, please fax medical records and relevant documentation to:
Fax: (XXX) XXX-XXXX

We will send you a copy of our evaluation report once completed.

Best regards,
Developmental On Demand
```

## Privacy & Security

✅ **HIPAA-Compliant Approach:**
- Only minimal identifiers collected (name + DOB)
- No detailed medical history via unsecured email
- Clinical records transmitted via secure fax
- Physician confirmation email contains no patient PHI

❌ **What NOT to collect via this form:**
- Detailed medical history
- Current medications/diagnoses
- Previous evaluation results
- Social security numbers or insurance policy numbers

## Customization

**Add/Remove Fields:**
- Follow the same field-group HTML structure
- Update XML generation in `generate_pcp_referral_xml()`
- Adjust summary format in `generate_pcp_referral_summary()`

**Change Referral Reasons:**
- Modify dropdown options to match your service offerings
- Add specialty-specific referral types

**Additional Referral Sources:**
- Copy this template for Specialist Referrals, School Referrals, etc.
- Change form ID and processing function name
- Adapt fields to referral source

## Testing

1. Create test referral with patient "Test Patient" DOB 01/01/2010
2. Fill as referring physician
3. Verify XML attachment received by staff
4. Check physician received confirmation with fax number
5. Verify you can match to patient using name + DOB
6. Test fax workflow for medical records
