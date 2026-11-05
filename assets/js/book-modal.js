/**
 * "Book Your Stay" modal — visual 2-month calendar with range selection, live sold-out nights & rates per room.
 * Opens from any [data-book-open] (header button, floating pill, hero CTA, bottom nav, any link to /booking without dates).
 */
(function () {
  'use strict';
  const modal = document.getElementById('bookModal');
  if (!modal) return;
  const cal = document.getElementById('bkCal'), inEl = document.getElementById('bkIn'), outEl = document.getElementById('bkOut');
  const inLbl = document.getElementById('bkInLbl'), outLbl = document.getElementById('bkOutLbl'), sum = document.getElementById('bkSum'), go = document.getElementById('bkGo');
  const roomSel = document.getElementById('bkRoom'), sym = cal.dataset.sym || '₹';
  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const DAYS = ['Mo','Tu','We','Th','Fr','Sa','Su'];
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const fmt = s => { if (!s) return ''; const d = new Date(s + 'T00:00:00'); return d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }); };
  let view = new Date(today.getFullYear(), today.getMonth(), 1);
  let start = '', end = '', hover = '', sold = new Set(), rates = {}, which = 'checkin';
  const months = () => window.innerWidth < 640 ? 1 : 2;

  function render() {
    let html = '';
    for (let m = 0; m < months(); m++) {
      const d = new Date(view.getFullYear(), view.getMonth() + m, 1);
      html += '<div class="bk-month"><div class="bk-mh">' + (m === 0 ? '<button type="button" class="bk-nav" data-nav="-1" aria-label="Previous month">&lsaquo;</button>' : '<span></span>') +
        '<b>' + MONTHS[d.getMonth()] + ' ' + d.getFullYear() + '</b>' + (m === months() - 1 ? '<button type="button" class="bk-nav" data-nav="1" aria-label="Next month">&rsaquo;</button>' : '<span></span>') + '</div>';
      html += '<div class="bk-grid">' + DAYS.map(x => '<i>' + x + '</i>').join('');
      const first = (d.getDay() + 6) % 7;                       // Monday first
      for (let i = 0; i < first; i++) html += '<s></s>';
      const n = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
      for (let day = 1; day <= n; day++) {
        const dt = new Date(d.getFullYear(), d.getMonth(), day), k = iso(dt);
        const past = dt < today, isSold = sold.has(k);
        const rangeEnd = end || (start && hover > start ? hover : '');
        const inRange = start && rangeEnd && k > start && k < rangeEnd;
        const cls = ['bk-d', past ? 'past' : '', isSold ? 'sold' : '', k === start ? 'sel st' : '', k === end ? 'sel en' : '', inRange ? 'in' : '', k === iso(today) ? 'today' : ''].filter(Boolean).join(' ');
        const rate = rates[k] ? '<em>' + sym + (rates[k] >= 1000 ? (rates[k] % 1000 === 0 ? rates[k] / 1000 : (rates[k] / 1000).toFixed(1)) + 'k' : rates[k]) + '</em>' : '';
        html += '<button type="button" class="' + cls + '" data-d="' + k + '" ' + (past ? 'disabled' : '') + '><span>' + day + '</span>' + rate + '</button>';
      }
      html += '</div></div>';
    }
    cal.innerHTML = html;
    document.querySelectorAll('.bk-date').forEach(b => b.classList.toggle('on', b.dataset.which === which));
    inLbl.textContent = start ? fmt(start) : 'Select a date'; outLbl.textContent = end ? fmt(end) : 'Select a date';
    inEl.value = start; outEl.value = end;
    const ok = start && end;
    go.disabled = !ok;
    if (ok) {
      const nights = Math.round((new Date(end) - new Date(start)) / 86400000);
      let total = 0, known = true; for (let t = new Date(start + 'T00:00:00'); iso(t) < end; t.setDate(t.getDate() + 1)) { if (rates[iso(t)]) total += rates[iso(t)]; else known = false; }
      sum.innerHTML = '<b>' + nights + ' night' + (nights > 1 ? 's' : '') + '</b> · ' + fmt(start) + ' → ' + fmt(end) + (known && total && roomSel.value ? ' · from <b>' + sym + total.toLocaleString('en-IN') + '</b> + taxes' : '');
    } else sum.textContent = start ? 'Now pick your check-out date' : 'Pick your check-in and check-out dates';
  }

  // range between start and candidate end must not cross a sold-out night
  function crossesSold(a, b) { for (let t = new Date(a + 'T00:00:00'); iso(t) < b; t.setDate(t.getDate() + 1)) if (sold.has(iso(t))) return true; return false; }

  cal.addEventListener('click', e => {
    const nav = e.target.closest('.bk-nav');
    if (nav) { view = new Date(view.getFullYear(), view.getMonth() + (+nav.dataset.nav), 1); if (view < new Date(today.getFullYear(), today.getMonth(), 1)) view = new Date(today.getFullYear(), today.getMonth(), 1); render(); return; }
    const b = e.target.closest('.bk-d'); if (!b || b.disabled) return;
    const k = b.dataset.d;
    if (which === 'checkin' || !start || k <= start) {
      if (sold.has(k)) { sum.innerHTML = '<span style="color:#c0392b">That night is sold out — choose another check-in date.</span>'; return; }
      start = k; end = ''; which = 'checkout';
    } else {
      if (crossesSold(start, k)) {   // the stay would cross a sold-out night → start a fresh stay from the clicked day instead
        if (sold.has(k)) { sum.innerHTML = '<span style="color:#c0392b">Those dates include a sold-out night — try a shorter stay or another room.</span>'; return; }
        start = k; end = ''; which = 'checkout'; render();
        sum.innerHTML = '<span style="color:#b9600d">That stay would cross a sold-out night — starting a new stay from ' + fmt(k) + '. Now pick the check-out.</span>'; return;
      }
      end = k; which = 'checkin';
    }
    render();
  });
  // hover preview of the range: repaint classes only (no re-render → no flicker)
  function paint() {
    const rangeEnd = end || (start && hover > start ? hover : '');
    cal.querySelectorAll('.bk-d').forEach(b => { const k = b.dataset.d; b.classList.toggle('in', !!(start && rangeEnd && k > start && k < rangeEnd)); b.classList.toggle('en', k === end); });
  }
  cal.addEventListener('mouseover', e => { const b = e.target.closest('.bk-d'); if (b && start && !end && b.dataset.d !== hover) { hover = b.dataset.d; paint(); } });
  cal.addEventListener('mouseleave', () => { if (hover) { hover = ''; paint(); } });
  document.querySelectorAll('.bk-date').forEach(b => b.addEventListener('click', () => { which = b.dataset.which; if (which === 'checkout' && !start) which = 'checkin'; render(); }));

  function loadAvailability() {
    sold = new Set(); rates = {};
    fetch((window.ECH ? ECH.base : '') + '/api/availability.php?room_id=' + (roomSel.value || 0)).then(r => r.json()).then(d => {
      if (!d.ok) return; sold = new Set(d.sold || []); rates = d.rates || {};
      if (start && end && crossesSold(start, end)) { start = ''; end = ''; which = 'checkin'; }
      render();
    }).catch(() => {});
  }
  roomSel.addEventListener('change', () => {
    const o = roomSel.selectedOptions[0];
    if (o && o.dataset.maxAdults) { const a = document.getElementById('bkAdults'); if (+a.value > +o.dataset.maxAdults) a.value = o.dataset.maxAdults; }
    loadAvailability();
  });

  let lastFocus = null;
  function open(roomId) {
    if (roomId) roomSel.value = String(roomId);
    lastFocus = document.activeElement;
    modal.classList.add('show'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('bk-lock');
    render(); loadAvailability();
    setTimeout(() => { const f = cal.querySelector('.bk-d:not([disabled])'); f && f.focus({ preventScroll: true }); }, 50);
  }
  function close() { modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('bk-lock'); lastFocus && lastFocus.focus && lastFocus.focus(); }
  window.openBookModal = open;

  document.addEventListener('click', e => {
    const t = e.target.closest('[data-book-open], a[href]');
    if (!t) return;
    let hit = t.hasAttribute('data-book-open');
    if (!hit && t.tagName === 'A') {
      // plain "book" links (no room / dates in the URL) also open the calendar first — room cards keep their direct link
      try { const u = new URL(t.href, location.href); hit = u.origin === location.origin && /\/booking(\.php)?\/?$/.test(u.pathname) && !u.search && !t.closest('.bk-modal') && !t.closest('.le-tools'); } catch (err) {}
    }
    if (!hit) return;
    e.preventDefault();
    // on a room page the floating pill / header button jump to that room's own booking form instead
    const own = document.querySelector('[data-room-book]');
    if (own && !t.dataset.room) { own.closest('.card').scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(() => { const i = own.querySelector('[name=checkin]'); i && i.focus({ preventScroll: true }); }, 450); return; }
    open(t.dataset.room || '');
  });
  modal.addEventListener('click', e => { if (e.target === modal || e.target.closest('[data-book-close]')) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('show')) close(); });
  window.addEventListener('resize', () => { if (modal.classList.contains('show')) render(); });

  // floating pill: hide while the page's own hero CTA is on screen, show after scrolling a little
  const pill = document.querySelector('.book-float');
  if (pill) {
    const upd = () => pill.classList.toggle('show', window.scrollY > 260 || !document.querySelector('.hero'));
    window.addEventListener('scroll', upd, { passive: true }); upd();
  }
})();
