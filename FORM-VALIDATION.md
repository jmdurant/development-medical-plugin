# Form validation ownership

The retained intake, teacher, Vanderbilt, referral and optional i693 workflows
continue to use Contact Form 7. CF7 owns submission, required/type rules, spam
checks, server validation and delivery status. There is no fake `window.wpcf7`,
replacement `wpcf7.submit`, extra AJAX transport or dependency on jQuery.

`assets/validation.js` supplies optional, browser-only character/length feedback:

- It applies to CF7 forms (`form.wpcf7-form`) and explicitly opted-in custom
  forms (`form[data-gcm-validation]`), not login/search/unrelated forms.
- Existing optional SCF `validation_methods` and `character_limit_class_prefix`
  values still come from their `options` storage. No storage migration or new
  settings GUI is implied. Missing/malformed settings are tolerated.
- Configured character sets are `letters` (ASCII), `numbers`, and `spaces`.
  Arbitrary `RegEx-*` labels were never implemented and are not executed. No
  character restriction is enabled merely because a generated field carries
  `class:letters_space`; it needs an actual configured method. Name fields are
  not newly restricted by this change.
- Length suffixes must be non-negative safe integers. Empty optional values,
  disabled/hidden/readonly fields, radios, checkboxes and files remain outside
  these extra hints. Native/vendor validation owns their required/type semantics.
- Capture-phase submission checking precedes CF7's form listener without
  replacing its API. A valid form reaches CF7 once, with its original submitter.
  Direct programmatic CF7/API calls bypass these UX hints; **server validation
  must enforce every required rule**. This is not authorization or a server-side
  validation guarantee.
- Feedback uses text nodes, unique IDs, `role=alert` and `aria-describedby`.
  Only owned messages/references are cleared; vendor errors/ARIA state are not
  deleted. Missing wrappers, newly inserted forms and repeated initialization
  are supported. No form values or DOM nodes are logged to the console.

The theme no longer dequeues CF7/validation/core block styles by guessing from
`post_content`, or rewrites their stylesheet tags into inline-event loaders.
Native CF7 asset configuration remains authoritative. Do not disable CF7 assets
globally without handling all actual templates/blocks/widgets that render forms.

## Replay

```sh
npm ci --ignore-scripts
npm test
php tests/validation-without-fields.php
php tests/validation-settings.php
CF7_ASSET_DIR=/path/to/extracted/contact-form-7 npm run test:native
```

The last command requires an extracted **official plugin distribution**, not a
running WordPress directory. It executes the actual bundled CF7 JavaScript in
isolated jsdom pages. Every HTTP response is supplied by an explicit fixture;
there are no external requests, mail, secrets, database or browser sessions.
It tests our script loading before and after native initialization, stopping an
invalid optional hint before POST, one native POST with the submitter, and display
of a synthetic server rejection. It does not test live server validation/delivery.

Twelve component cases pass; ten fail against the former script when supplied
with actual WordPress jQuery. The two actual-CF7 loading-order cases pass against
the official 6.1.7 distribution reviewed September 13, 2026. This is recorded
test provenance, not a deployment pin: Practice Stack selects latest stable CF7.
The theme's `tests/native-script-loading.php` covers actual WordPress enqueue and
style ownership. Browser layout/editor/accessibility and live submission acceptance
remain separate from these source checks.

Upstream references: [asset loading](https://contactform7.com/loading-javascript-and-stylesheet-only-when-it-is-necessary/),
[DOM events](https://contactform7.com/dom-events/),
[native submit listener](https://github.com/rocklobster-in/contact-form-7/blob/master/includes/js/src/init.js).
