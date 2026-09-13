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

Practice Stack installs pinned Secure Custom Fields and Contact Form 7 before
running the native custom-plugin form installer. `gcm_install_evaluation_forms()`
creates the four evaluation forms/pages and retains existing page content on
retry; reinstalling forms is not an editor-content reset. Actual form submission,
delivery/storage and clinical integration require their own acceptance.

Run `php tests/validation-without-fields.php` for the plugin-independent frontend
enqueue check. `tests/native-form-install.php` exercises actual CF7 creation,
page/form IDs and retry with an operator-edited page on the isolated Practice
Stack WordPress fixture only (`PRACTICE_FRESH_INSTALL_TEST=true`). It performs no
mail delivery and must not run against a production site.
