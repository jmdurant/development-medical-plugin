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
website spam/rate controls, EHR matching and the separate older i693 workflow
require additional acceptance. This is not a HIPAA-compliance assertion, and the
existing Vanderbilt interpretation wording/clinical validity is not established
by the transport tests.
