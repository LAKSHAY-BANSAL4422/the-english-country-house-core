/* "Our Menu" — cart, filters, checkout → kitchen (dashboard + WhatsApp), live order tracking */
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('foodMenu');
  if (!root) return;
  const $ = (s, el) => (el || root).querySelector(s);
  const $$ = (s, el) => Array.from((el || root).querySelectorAll(s));
  const CUR = root.dataset.currency || '₹';
  const TAX = parseFloat(root.dataset.tax || '0');
  const MIN = parseFloat(root.dataset.min || '0');
  const KEY = 'ech_food_cart_v1';
  const fmt = n => CUR + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* ---------- state ---------- */
  let cart = [];
  try { cart = JSON.parse(localStorage.getItem(KEY) || '[]') || []; } catch (e) { cart = []; }
  const itemEl = id => $('.fm-item[data-id="' + id + '"]');
  cart = cart.filter(l => itemEl(l.id) && !itemEl(l.id).classList.contains('soldout'));   // drop removed dishes
  const save = () => { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} };
  const keyOf = (id, v) => id + '|' + (v || '');

  function variantOf(el) { const b = el.querySelector('.fm-var.on'); return b ? b.dataset.v : ''; }
  function add(el) {
    const id = +el.dataset.id, v = variantOf(el);
    const line = cart.find(l => keyOf(l.id, l.variant) === keyOf(id, v));
    if (line) line.qty = Math.min(30, line.qty + 1);
    else cart.push({ id, variant: v, qty: 1, name: el.dataset.name, price: +el.dataset.price, diet: el.dataset.diet });
    save(); render(); bump();
  }
  function setQty(k, d) {
    const line = cart.find(l => keyOf(l.id, l.variant) === k);
    if (!line) return;
    line.qty += d;
    if (line.qty <= 0) cart = cart.filter(l => l !== line);
    save(); render();
  }
  const totals = () => {
    const sub = cart.reduce((s, l) => s + l.price * l.qty, 0);
    const tax = Math.round(sub * TAX) / 100;
    return { sub, tax, total: sub + tax, count: cart.reduce((s, l) => s + l.qty, 0) };
  };

  /* ---------- rendering ---------- */
  function lineHtml(l) {
    const k = keyOf(l.id, l.variant);
    return '<div class="fm-line"><i class="diet diet-' + esc(l.diet) + '"></i><div class="fm-line-name">' + esc(l.name) +
      (l.variant ? '<small>' + esc(l.variant) + '</small>' : '') + '</div>' +
      '<div class="fm-step"><button type="button" data-q="' + esc(k) + '" data-d="-1" aria-label="less">−</button><b>' + l.qty +
      '</b><button type="button" data-q="' + esc(k) + '" data-d="1" aria-label="more">+</button></div>' +
      '<div class="fm-line-amt">' + fmt(l.price * l.qty) + '</div></div>';
  }
  function render() {
    const t = totals();
    $$('[data-cart-list]').forEach(box => {
      box.innerHTML = cart.length ? cart.map(lineHtml).join('') : '<p class="fm-muted">Tap <b>ADD</b> on any dish to start your order.</p>';
    });
    $$('[data-cart-total]').forEach(x => x.textContent = fmt(t.total));
    $$('[data-cart-count]').forEach(x => x.textContent = t.count + (t.count === 1 ? ' item' : ' items'));
    $$('[data-cart-foot]').forEach(x => x.hidden = !cart.length);
    const tb = $('[data-cart-totals]');
    if (tb) tb.innerHTML = cart.length ?
      (TAX > 0 ? '<div><span>Subtotal</span><span>' + fmt(t.sub) + '</span></div><div><span>' + esc(root.dataset.taxLabel) + ' ' + TAX + '%</span><span>' + fmt(t.tax) + '</span></div>' : '') +
      '<div class="grand"><span>Total</span><span>' + fmt(t.total) + '</span></div>' : '';
    const bar = document.getElementById('fmCartBar');
    if (bar) bar.hidden = !cart.length;
    document.body.classList.toggle('fm-has-cart', cart.length > 0);
    // reflect quantities on the menu cards
    $$('.fm-item').forEach(el => {
      const q = cart.filter(l => l.id === +el.dataset.id).reduce((s, l) => s + l.qty, 0);
      const btn = el.querySelector('[data-add]');
      if (btn) btn.innerHTML = q ? 'ADDED · ' + q + ' <i class="fa-solid fa-plus"></i>' : 'ADD <i class="fa-solid fa-plus"></i>';
      el.classList.toggle('in-cart', q > 0);
    });
  }
  function bump() {
    const bar = document.getElementById('fmCartBar');
    if (bar) { bar.classList.remove('bump'); void bar.offsetWidth; bar.classList.add('bump'); }
  }

  /* ---------- events ---------- */
  root.addEventListener('click', e => {
    const v = e.target.closest('.fm-var');
    if (v) { $$('.fm-var', v.parentElement).forEach(b => b.classList.toggle('on', b === v)); return; }
    const a = e.target.closest('[data-add]');
    if (a) { add(a.closest('.fm-item')); return; }
    const q = e.target.closest('[data-q]');
    if (q) { setQty(q.dataset.q, +q.dataset.d); return; }
    if (e.target.closest('[data-checkout]')) { openSheet('cart'); return; }
    if (e.target.closest('[data-qr-open]')) { open(document.getElementById('fmQrModal')); return; }
    if (e.target.closest('[data-close]') || e.target.classList.contains('fm-sheet')) { closeAll(); return; }
    if (e.target.closest('[data-more]')) { closeAll(); return; }
    const dl = e.target.closest('[data-qr-download]');
    if (dl) {
      e.preventDefault();
      const cv = $('#fmQrModal canvas');
      ECHQR.toPng(cv.dataset.qr, { label: cv.dataset.qrLabel || '' }).then(url => {
        const a2 = document.createElement('a'); a2.href = url; a2.download = 'menu-qr.png'; document.body.appendChild(a2); a2.click(); a2.remove();
      });
    }
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });

  function open(sheet) { sheet.classList.add('show'); sheet.setAttribute('aria-hidden', 'false'); document.body.classList.add('fm-lock'); }
  function closeAll() { $$('.fm-sheet.show').forEach(s => { s.classList.remove('show'); s.setAttribute('aria-hidden', 'true'); }); document.body.classList.remove('fm-lock'); }
  function openSheet(step) {
    const sh = document.getElementById('fmSheet');
    $$('[data-step]', sh).forEach(s => s.hidden = s.dataset.step !== step);
    open(sh);
  }

  /* search + veg filter */
  const search = document.getElementById('fmSearch'), vegOnly = document.getElementById('fmVegOnly');
  function filter() {
    const q = (search.value || '').trim().toLowerCase(), veg = vegOnly.checked;
    let shown = 0;
    $$('.fm-sec').forEach(sec => {
      let n = 0;
      $$('.fm-item', sec).forEach(el => {
        const ok = (!q || el.dataset.search.includes(q)) && (!veg || el.dataset.diet === 'veg' || el.dataset.diet === 'both');
        el.hidden = !ok; if (ok) n++;
      });
      sec.hidden = n === 0; shown += n;
    });
    document.getElementById('fmEmpty').hidden = shown > 0;
  }
  search && search.addEventListener('input', filter);
  vegOnly && vegOnly.addEventListener('change', filter);

  /* category chips: scroll-spy */
  const chips = $$('#fmCats a');
  const spy = new IntersectionObserver(ents => {
    ents.forEach(en => {
      if (!en.isIntersecting) return;
      chips.forEach(c => {
        const on = c.dataset.cat === en.target.dataset.cat;
        c.classList.toggle('on', on);
        if (on && c.parentElement.scrollTo) c.parentElement.scrollTo({ left: c.offsetLeft - 20, behavior: 'smooth' });
      });
    });
  }, { rootMargin: '-35% 0px -60% 0px' });
  $$('.fm-sec').forEach(s => spy.observe(s));

  /* ---------- place order ---------- */
  const form = document.getElementById('fmForm');
  const errBox = $('[data-err]');
  form.addEventListener('submit', async e => {
    e.preventDefault();
    errBox.hidden = true;
    const t = totals();
    const fd = new FormData(form);
    const fail = m => { errBox.textContent = m; errBox.hidden = false; };
    if (!cart.length) return fail('Your order is empty — add a dish first.');
    if (MIN > 0 && t.sub < MIN) return fail('Minimum order value is ' + fmt(MIN) + '.');
    if (root.dataset.roomRequired === '1' && !String(fd.get('room') || '').trim()) return fail('Please tell us your room number or table.');
    if (!String(fd.get('name') || '').trim()) return fail('Please enter your name.');
    const pc = window.ECHPhone ? ECHPhone.check(fd.get('phone'), true) : { ok: String(fd.get('phone') || '').replace(/\D/g, '').length >= 10, error: 'Please enter a valid mobile number.' };   // round 5: site-wide rule (phone.js)
    if (!pc.ok) return fail(pc.error);

    const btn = $('[data-place]');
    btn.disabled = true; const label = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending to kitchen…';
    // open a tab inside the click so WhatsApp hand-off is not blocked as a pop-up
    let waWin = null;
    fd.append('action', 'place');
    fd.append('csrf', (window.ECH && ECH.csrf) || '');
    fd.append('source', root.dataset.source || 'website');
    fd.append('items', JSON.stringify(cart.map(l => ({ id: l.id, qty: l.qty, variant: l.variant }))));
    try {
      const r = await fetch(root.dataset.api, { method: 'POST', body: fd, credentials: 'same-origin' });
      const d = await r.json();
      if (!d.ok) { fail(d.error || 'Could not place the order. Please call us.'); return; }
      cart = []; save(); render();
      showDone(d);
      if (d.wa_open && d.wa_link) {
        // try to hand the order to WhatsApp straight away; if the browser blocks it, the big pulsing button stays
        try { waWin = window.open(d.wa_link, '_blank'); } catch (x) { waWin = null; }
        if (waWin) { try { waWin.opener = null; } catch (x) {} }
        const wa = $('[data-o-wa]', document.getElementById('fmSheet'));
        if (!waWin && wa) { wa.classList.add('pulse'); wa.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Tap to send your order on WhatsApp'; }
      }
    } catch (err) {
      fail('Network problem — please try again or call us.');
    } finally {
      btn.disabled = false; btn.innerHTML = label;
    }
  });

  /* ---------- done + live tracking ---------- */
  let pollT = null, cdT = null;
  function showDone(d) {
    openSheet('done');
    const sh = document.getElementById('fmSheet');
    $('[data-o-code]', sh).textContent = d.code;
    $('[data-o-total]', sh).textContent = d.total || '';
    $('[data-o-ready]', sh).textContent = d.ready_by || '';
    const wa = $('[data-o-wa]', sh);
    if (d.wa_link) { wa.href = d.wa_link; wa.hidden = false; wa.innerHTML = '<i class="fa-brands fa-whatsapp"></i> ' + (d.wa_auto ? 'Also message us on WhatsApp' : 'Send order on WhatsApp'); }
    try { localStorage.setItem('ech_food_last', JSON.stringify({ code: d.code, token: d.token, at: Date.now() })); } catch (e) {}
    track(d.code, d.token);
  }
  function track(code, token) {
    clearInterval(pollT); clearInterval(cdT);
    const sh = document.getElementById('fmSheet');
    const order = ['new', 'accepted', 'preparing', 'ready', 'delivered'];
    let secs = null;
    const tick = () => {
      if (secs === null) return;
      secs = Math.max(0, secs - 1);
      $('[data-o-countdown]', sh).textContent = secs > 0 ? '≈ ' + Math.ceil(secs / 60) + ' min to go' : 'Arriving any moment';
    };
    const poll = () => fetch(root.dataset.api + '?action=status&code=' + encodeURIComponent(code) + '&t=' + encodeURIComponent(token))
      .then(r => r.json()).then(s => {
        if (!s.ok) return;
        $('[data-o-code]', sh).textContent = s.code;
        $('[data-o-total]', sh).textContent = s.total;
        $('[data-o-ready]', sh).textContent = s.ready_by;
        secs = s.seconds_left; tick();
        const idx = order.indexOf(s.status);
        $$('[data-track] li', sh).forEach((li, i) => { li.classList.toggle('done', idx >= 0 && i <= idx); li.classList.toggle('now', i === idx); });
        $('[data-o-cancel]', sh).hidden = s.status !== 'cancelled';
        if (s.status === 'delivered' || s.status === 'cancelled') { clearInterval(pollT); $('[data-o-countdown]', sh).textContent = s.status === 'delivered' ? 'Enjoy your meal!' : ''; }
      }).catch(() => {});
    poll(); pollT = setInterval(poll, 12000); cdT = setInterval(tick, 1000);
  }
  // deep link: ?track=CODE&t=TOKEN (from WhatsApp / account)
  const P = new URLSearchParams(location.search);
  if (P.get('track') && P.get('t')) { openSheet('done'); track(P.get('track'), P.get('t')); }

  render();
});
