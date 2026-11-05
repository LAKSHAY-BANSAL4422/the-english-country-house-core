/* Site-wide phone number rule (round 5) — the browser half of phone_check() in includes/ech_contact.php; keep in step.
   Applies to every phone input on the public site: input[type=tel] and inputs named phone / telephone / mobile,
   including forms added later (chat). The value the guest typed is never rewritten; ECHPhone.check() returns the
   normalised +<country><number> form for scripts that need it.
   - Required field left empty, letters, a misplaced "+", too short/long or an obviously fake number → message under
     the field, the form does not submit. Optional fields are only checked when something is typed.
   - India: 10 digits starting 6–9, with or without +91 / 91 / 0; landlines as 0 + STD code.
   - International: + and country code, 8–15 digits (or 11–15 digits without the +). */
(function () {
  'use strict';
  const ok = e164 => ({ ok: true, e164: e164, error: '' });
  const no = error => ({ ok: false, e164: '', error: error });
  const MSG = {
    empty: 'Please enter your mobile number.',
    chars: 'Please use digits only (spaces, dashes and a leading + are fine).',
    india: 'Please enter a valid 10-digit Indian mobile number (starting with 6, 7, 8 or 9).',
    short: 'That number looks too short. Indian mobiles have 10 digits; for other countries add the country code with +.',
    bad: 'Please enter a valid mobile number, e.g. 98765 43210 or +44 7911 123456.'
  };
  const repeated = d => /^(\d)\1+$/.test(d);
  const india = ten => /^[6-9]\d{9}$/.test(ten) && !repeated(ten) ? ok('+91' + ten) : no(MSG.india);

  function check(raw, required) {
    const v = String(raw == null ? '' : raw).trim();
    if (!v) return required === false ? ok('') : no(MSG.empty);
    if (/[^\d\s\-().+]/.test(v) || (v.match(/\+/g) || []).length > 1 || (v.includes('+') && v[0] !== '+')) return no(MSG.chars);
    const d = v.replace(/\D/g, '');
    if (v[0] === '+') {
      if (d.startsWith('91')) return d.length === 12 ? india(d.slice(2)) : no(MSG.india);
      if (!d || d[0] === '0' || d.length < 8 || d.length > 15 || repeated(d)) return no(MSG.bad);
      return ok('+' + d);
    }
    if (d.length === 10) return india(d);
    if (d.length === 12 && d.startsWith('91')) return india(d.slice(2));
    if (d.length === 11 && d[0] === '0') return /^0[1-5]\d{9}$/.test(d) ? ok('+91' + d.slice(1)) : india(d.slice(1));
    if (d.length >= 11 && d.length <= 15 && d[0] !== '0' && !repeated(d)) return ok('+' + d);
    return no(d.length < 10 ? MSG.short : MSG.bad);
  }
  window.ECHPhone = { check: check };

  const SEL = 'input[type=tel], input[name=phone], input[name=telephone], input[name=mobile]';
  const managed = el => el.matches(SEL) && !el.closest('[data-phone-own]') && !el.disabled && el.type !== 'hidden';
  let uid = 0;
  function show(el, msg) {
    let p = el.id && document.getElementById(el.id + '-pherr');
    if (!p && el.dataset.pherr) p = document.getElementById(el.dataset.pherr);
    if (!p) {
      if (!msg) return;
      p = document.createElement('span');
      p.className = 'ph-err'; p.setAttribute('role', 'alert');
      p.id = (el.id || 'ph' + (++uid)) + '-pherr';
      el.dataset.pherr = p.id;
      el.insertAdjacentElement('afterend', p);
      el.setAttribute('aria-describedby', ((el.getAttribute('aria-describedby') || '') + ' ' + p.id).trim());
    }
    p.textContent = msg || '';
    p.hidden = !msg;
    el.classList.toggle('ph-bad', !!msg);
    el.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }
  function validate(el) {
    const r = check(el.value, el.required);
    el.setCustomValidity(r.error);
    show(el, r.error);
    return r.ok;
  }
  document.addEventListener('focusout', e => { const el = e.target; if (el.matches && managed(el) && (el.value || el.getAttribute('aria-invalid') === 'true')) validate(el); }, true);
  document.addEventListener('input', e => { const el = e.target; if (el.matches && managed(el)) { if (el.getAttribute('aria-invalid') === 'true') validate(el); else el.setCustomValidity(check(el.value, el.required).error); } }, true);
  document.addEventListener('invalid', e => { const el = e.target; if (el.matches && managed(el)) validate(el); }, true);
  /* capture phase: runs before each form's own submit handler, so an invalid number never reaches it */
  document.addEventListener('submit', e => {
    const bad = Array.from(e.target.querySelectorAll(SEL)).filter(managed).filter(el => !validate(el));
    if (!bad.length) return;
    e.preventDefault(); e.stopImmediatePropagation();
    bad[0].focus();
  }, true);
})();
