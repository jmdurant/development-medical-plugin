Developmental On Demand Plugin
-

A custom WordPress plugin for Developmental On Demand.

Includes custom features for Developmental On Demand, which adds custom attachments sent with Contact Form 7, custom shortcodes, and form validation methods.

Built by James DuRant, using PHPStorm.

## Quick Links

- Developmental On Demand - Theme:

   https://github.com/jmdurant/development-medical-theme

- Developmental On Demand - Plugin:

   https://github.com/jmdurant/development-medical-plugin

## Build process

Not needed. The plugin does not contain any compiled scripts.

## Website installation acceptance

Practice Stack resolves the latest stable Secure Custom Fields and Contact Form 7 before
running the native custom-plugin form installer. `gcm_install_evaluation_forms()`
creates the four evaluation forms/pages and retains existing page content on
retry; reinstalling forms is not an editor-content reset. Actual form submission,
delivery/storage and clinical integration require their own acceptance.

Run `php tests/validation-without-fields.php` for the plugin-independent frontend
enqueue check. `tests/native-form-install.php` exercises actual CF7 creation,
page/form IDs and retry with an operator-edited page on the isolated Practice
Stack WordPress fixture only (`PRACTICE_FRESH_INSTALL_TEST=true`). It performs no
mail delivery and must not run against a production site.

## Retained external clinical forms

Teacher reports and referring-provider submissions do not require EHR accounts.
The four installed forms use the shared `includes/clinical-form-mail.php` path:

- CF7 validates the submission and performs its ordinary spam/acceptance checks.
  Only declared fields are exported. Choices must match the current form schema;
  scalar fields are bounded to 1,024 bytes, textareas to 20,000 bytes and the
  complete export to 128 KiB. Dates and all 18 Vanderbilt ratings must be valid;
  missing/malformed ratings are not silently scored as zero.
- Configure the clinical destination and sender in each form's native **Mail**
  tab. Fixed addresses and `[_site_admin_email]` are accepted. Submitted mail tags
  in recipient/sender/additional headers are rejected. The public website contact
  email and a hardcoded practice address are not fallback clinical destinations.
- XML and text attachments are generated **in memory**, using WordPress's native
  PHPMailer string-attachment API. No clinical attachment files are created in
  the web root, uploads or a shared temporary directory. Existing XML/summary
  generators remain the export format; this does not create/import an EHR record.
- CF7 sends one primary email. Primary failure produces its normal `mail_failed`
  status, without sending an acknowledgement. Teacher/referral acknowledgements
  remain, but contain no names, DOB, answers or attachments. CF7's optional
  acknowledgement result does not change primary success; neither means inbox
  delivery, clinical review or appointment confirmation.
- Runtime subject/body/attachments and Mail (2) are controlled by this transport:
  the four forms do not send a customized second clinical copy. Saved operator
  form/mail settings are not overwritten. Attachments go only to the primary
  configured clinical recipients; the external email address is unverified.

`tests/native-clinical-mail.php` uses actual installed WordPress/CF7 validation,
PHPMailer MIME creation and send-result hooks, but intercepts transport before
network delivery. Practice Stack's `native-submissions` acceptance action runs
the cases in separate PHP processes, including concurrent requests. It must only
run on the guarded synthetic fixture. Real delivery/SMTP privacy controls,
website spam/rate controls and EHR matching require additional acceptance. This
is not a HIPAA-compliance assertion or complete clinical validation.

## Managed mail and spam protection

Practice Stack enables `GCM_SMTP_MANAGED=1` and `GCM_TURNSTILE_MANAGED=1`.
Missing/invalid managed configuration blocks mail/submission instead of falling
back to PHP mail or inactive bot protection. Administrator notices and the native
deployment `site_services` report expose readiness without exposing credentials.
Outside managed deployment, native administrator integrations remain unchanged.

SMTP uses WordPress's PHPMailer hook, authenticated `starttls` or `smtps`, required
TLS 1.2/1.3 and OS-trusted peer/hostname verification. No plaintext fallback,
self-signed bypass, SMTP wire debugging or persistent SMTP connection. Configure
`GCM_SMTP_HOST`, `PORT`, `SECURITY`, `USERNAME`, `PASSWORD`, `FROM_EMAIL` (each with
the `GCM_SMTP_` prefix). The authorized relay sender replaces the From/envelope;
native CF7 clinical destinations and Reply-To are not redirected.

CF7's built-in Turnstile integration owns widget rendering, token verification
and spam result handling. Deployment supplies `GCM_TURNSTILE_SITE_KEY` and
`GCM_TURNSTILE_SECRET_KEY` through vendor filters; neither is persisted to CF7's
database settings. Configure a Managed widget restricted to the actual website
hostnames. Missing/failed/expired tokens cannot reach mail. Keep `WP_DEBUG=false`:
CF7's own failed-request debug logger can include the verification secret.
Public Cloudflare dummy keys are accepted only with `WP_ENVIRONMENT_TYPE=local`
and a `.localhost` site. They provide no production protection.

Use 1Password `WP_SMTP_PASSWORD` (or explicitly select the existing
`MAIL_SMTP_PASSWORD`) and `WP_TURNSTILE_SECRET_KEY` through Practice Stack's
protected secret bank. Non-secret host/username/sender/site key live in deployment
configuration, not this source. `tests/native-site-services.php` exercises native
CF7 decisions with intercepted verification/mail. `tests/native-smtp-transport.py`
tests real TLS/AUTH/native mail results using only an internal disposable receiver
and process-local trust. No real inbox or global trust-store change.

Configured is not delivery-verified. Before clinical use, approve the actual
relay/recipient handling and verify delivery, public challenge/browser behavior,
and operational rate limits. SMTP TLS covers the relay hop, not end-to-end inbox
encryption or retention. These tests are not a compliance certification.

## Optional i693 intake export

In the intended native CF7 form's **Additional Settings** tab, add:

```text
gcm_export: i693
gcm_i693_pdf: YourReviewedTemplate.pdf
gcm_i693_location: MAIN|Your actual public clinic address
```

Only `gcm_export` is required. The PDF setting is an optional local **basename**,
not a URL/drive path; without it the XDP contains data without an automatic PDF
reference. Locations are optional `code|label` lines and are display hints only.
There is no database-ID constant or guessed form migration. The old ID 3170 is
not an activation mechanism. Select the intended form explicitly before using
the export; no i693 form is automatically installed by the four-form installer.

Field names map to the existing demographic XML nodes. `FirstName`, `LastName`
and `dob` are required and must have the correct visible labels. An old developer
example reversed first/last labels: do not import that example as a working form.
Optional fields are `MiddleName`, `Street`, `ApartmentType`, `Apartment`, `CityTown`,
`State`, `ZipCode`, `Gender`, `CityBirth`, `CountryBirth`, `ANumber`, `USCIS`,
`DaytimeTelephone`, `MobileTelephone`, `Emailaddres`, `find`, `examination`, `lawyer`,
`lawyer-name`, `lawyer-company`, `lawyer-phone`, `appointmentfield`, and `location`.
CF7 owns the current form schema and normal field validation. Neither misspelled
`Appartment` fields nor an unrelated insurance-code transformation are used.

The shared native mail path sends `i693FormData.xml`, `i693FormData.xdp` and the
13-column CSV `i693Survey.txt` entirely in memory. CSV values are properly quoted
and formula-like values are prefixed with an apostrophe for spreadsheet safety.
An additional `i693Appointment.txt` is attached when appointment/location fields
are present, explicitly labelled applicant-supplied and unverified. No external
acknowledgement or second clinical mail is generated.

Hardcoded practice addresses, lab/vaccination/exam findings and the old Windows
PDF path have been removed. These are demographic intake packets, **not completed
or certified medical forms**. A clinician must supply verified clinical findings
in the appropriate workflow. Compatibility with a particular current official
PDF edition or an external import tool is not established by XML/MIME tests.

`[i693_appointment form_id="<native-form-id>"]` can render the configured public
location outside the CF7 block. `date1`, `time`, `location` and `exp` query values
are bounded/validated and escaped. The 30-minute expiry grace is only a display
feature of an unsigned URL: it is **not** authorization, a verified booking or a
server-enforced invitation expiry. Anonymous visitors cannot enable debug output,
and these display filters do not hide unrelated forms. Do not use these links to
grant access to an existing chart; that requires the EHR's authenticated workflow.

## Vanderbilt subset, export version 2

The installed WordPress form contains only 18 symptom items, not the complete
Vanderbilt assessment. Numerical counts/thresholds are retained, but diagnostic
labels have been removed. Native form rendering warns about the subset, including
forms created before this update. XML uses `symptom_count_threshold_met` and
`symptom_summary` rather than `clinically_significant`/`clinical_interpretation`;
machine-readable flags explicitly report `assessment_complete=false`,
`diagnosis_determined=false`, `clinical_review_required=true`.

The publisher's [scoring instructions](https://nichq.org/wp-content/uploads/2024/09/07Scoring-Instructions.pdf)
include performance impairment and caution against using the scales alone for
diagnosis. Do not infer diagnostic criteria from this subset or mark it as a
complete assessment in a downstream importer. The broader EHR questionnaire
workflows are separate; this change does not replace their instruments/scoring.
