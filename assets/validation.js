(() => {
  'use strict';
  // DOM enhancement only. CF7 retains its API, required/type rules and server validation.
  const installed = Symbol.for('development-medical.form-validation');
  if (window[installed]) return;
  window[installed] = true;

  const settings = window.gcm_validation_settings || {};
  const formSelector = 'form.wpcf7-form, form[data-gcm-validation]';
  const supported = new Set(['letters', 'numbers', 'spaces']);
  const methods = new Map();
  if (Array.isArray(settings.methods)) {
    for (const entry of settings.methods) {
      if (!entry || typeof entry.class !== 'string' || !entry.class || !Array.isArray(entry.methods)) continue;
      const selected = entry.methods.filter(method => supported.has(method));
      if (selected.length) methods.set(entry.class, selected);
    }
  }
  const prefixes = {
    min: typeof settings.min_prefix === 'string' ? settings.min_prefix : '',
    max: typeof settings.max_prefix === 'string' ? settings.max_prefix : '',
  };
  const messages = new WeakMap();
  let sequence = 0;

  function eligible(field) {
    return field instanceof HTMLElement && field.matches('input, textarea') &&
      !field.matches('[type=hidden], [type=checkbox], [type=radio], [type=file], [type=button], [type=submit], [type=reset], [type=image], :disabled') &&
      !field.readOnly && !field.closest('[hidden], [inert]') && field.getClientRects().length > 0 &&
      field.form?.matches(formSelector);
  }

  function errorFor(field) {
    if (!eligible(field) || field.value === '') return '';
    const patterns = new Set();
    for (const name of field.classList) {
      for (const kind of ['min', 'max']) {
        const prefix = prefixes[kind];
        if (!prefix || !name.startsWith(prefix)) continue;
        const suffix = name.slice(prefix.length);
        if (!/^(0|[1-9][0-9]*)$/.test(suffix)) continue;
        const limit = Number(suffix);
        if (!Number.isSafeInteger(limit)) continue;
        if ((kind === 'min' && field.value.length < limit) || (kind === 'max' && field.value.length > limit)) {
          return (kind === 'min' ? 'Minimum' : 'Maximum') + ' length: ' + limit + ' characters';
        }
      }
      for (const pattern of methods.get(name) || []) patterns.add(pattern);
    }
    if (!patterns.size) return '';
    const classes = { letters: 'a-zA-Z', numbers: '0-9', spaces: ' ' };
    const regex = new RegExp('^[' + [...patterns].map(pattern => classes[pattern]).join('') + ']+$');
    return regex.test(field.value) ? '' : 'Must contain only ' + [...patterns].join(', ') + '.';
  }

  function clearMessage(field) {
    const prior = messages.get(field);
    if (!prior) return;
    prior.node.remove();
    const descriptions = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== prior.id);
    if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
    else field.removeAttribute('aria-describedby');
    messages.delete(field);
  }

  function showMessage(field, message) {
    const prior = messages.get(field);
    if (prior?.node.isConnected && prior.node.textContent === message) return;
    clearMessage(field);
    if (!message) return;
    const node = document.createElement('span');
    do { node.id = 'gcm-validation-' + ++sequence; } while (document.getElementById(node.id));
    node.className = 'gcm-validation-tip wpcf7-not-valid-tip';
    node.setAttribute('role', 'alert');
    node.textContent = message;
    const wrapper = field.closest('.wpcf7-form-control-wrap');
    if (wrapper) wrapper.append(node);
    else field.insertAdjacentElement('afterend', node);
    const descriptions = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
    field.setAttribute('aria-describedby', [...new Set([...descriptions, node.id])].join(' '));
    messages.set(field, { node, id: node.id });
  }

  for (const event of ['input', 'change', 'focusout']) {
    document.addEventListener(event, e => {
      if (!(e.target instanceof HTMLElement) || !e.target.form?.matches(formSelector)) return;
      showMessage(e.target, errorFor(e.target));
    });
  }

  // Capture precedes CF7's native form submit listener; no replacement wpcf7 API.
  document.addEventListener('submit', e => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.matches(formSelector)) return;
    let firstError;
    for (const field of form.elements) {
      const message = errorFor(field);
      showMessage(field, message);
      if (message && !firstError) firstError = field;
    }
    if (firstError) {
      e.preventDefault();
      e.stopImmediatePropagation();
      firstError.focus({ preventScroll: true });
      firstError.scrollIntoView?.({ block: 'center', behavior: 'auto' });
    }
  }, true);

  document.addEventListener('reset', e => {
    if (e.target instanceof HTMLFormElement && e.target.matches(formSelector)) {
      for (const field of e.target.elements) clearMessage(field);
    }
  });
})();
