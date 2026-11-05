/* ECH live editor — edit any page right where you see it (admins only) */
(function () {
  'use strict';
  const L = window.ECH_LIVE, A = window.ADMIN;
  if (!L || !A) return;
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const $ = (s, el) => (el || document).querySelector(s);

  /* ---------- floating bar ---------- */
  const bar = document.createElement('div');
  bar.className = 'le-bar' + (L.editMode ? ' on' : '');
  bar.innerHTML = L.editMode
    ? '<span class="le-bar-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Editing: <b>' + esc(L.pageTitle) + '</b></span>' +
      '<button type="button" class="le-b" data-le="banner"><i class="fa-solid fa-image"></i> Banner</button>' +
      '<button type="button" class="le-b" data-le="intro"><i class="fa-solid fa-align-left"></i> Intro</button>' +
      '<button type="button" class="le-b" data-le="add"><i class="fa-solid fa-plus"></i> Add section</button>' +
      '<a class="le-b" href="' + esc(L.adminUrl) + '" target="_blank"><i class="fa-solid fa-sliders"></i> Builder</a>' +
      '<a class="le-b le-exit" href="' + esc(L.plainUrl) + '"><i class="fa-solid fa-xmark"></i> Done</a>'
    : '<a class="le-b le-start" href="' + esc(L.editUrl) + '"><i class="fa-solid fa-pen"></i> Edit this page</a>';
  document.body.appendChild(bar);
  if (!L.editMode) return;
  document.body.classList.add('le-on');

  /* ---------- toolbars on every section / banner / intro ---------- */
  const secs = [...document.querySelectorAll('.page-section[data-sec]')];
  secs.forEach((el, i) => {
    const idx = +el.dataset.sec, hidden = el.dataset.hidden === '1';
    const type = (L.types.find(t => t.type === el.dataset.type) || {}).label || el.dataset.type;
    el.classList.add('le-sec'); if (hidden) el.classList.add('le-hidden');
    el.insertAdjacentHTML('afterbegin',
      '<div class="le-tools" data-idx="' + idx + '">' +
      '<span class="le-lbl"><i class="fa-solid fa-cube"></i> ' + (idx + 1) + '. ' + esc(type) + (hidden ? ' <em>hidden</em>' : '') + '</span>' +
      '<button type="button" data-op="edit" title="Edit this section"><i class="fa-solid fa-pen"></i> Edit</button>' +
      '<button type="button" data-op="toggle" title="' + (hidden ? 'Show on website' : 'Hide from website') + '">' + (hidden ? '<i class="fa-solid fa-eye"></i> Show' : '<i class="fa-solid fa-eye-slash"></i> Hide') + '</button>' +
      '<button type="button" data-op="up" title="Move up" ' + (i === 0 ? 'disabled' : '') + '><i class="fa-solid fa-arrow-up"></i></button>' +
      '<button type="button" data-op="down" title="Move down" ' + (i === secs.length - 1 ? 'disabled' : '') + '><i class="fa-solid fa-arrow-down"></i></button>' +
      '<button type="button" data-op="dup" title="Duplicate"><i class="fa-regular fa-clone"></i></button>' +
      '<button type="button" data-op="addafter" title="Add a section below"><i class="fa-solid fa-plus"></i></button>' +
      '<button type="button" data-op="del" class="danger" title="Delete section"><i class="fa-solid fa-trash"></i></button>' +
      '</div>');
  });
  const banner = $('.page-banner[data-banner]');
  if (banner) { banner.classList.add('le-sec'); if (banner.dataset.hidden === '1') banner.classList.add('le-hidden'); banner.insertAdjacentHTML('afterbegin', '<div class="le-tools le-tools-banner"><span class="le-lbl"><i class="fa-solid fa-image"></i> Page banner' + (banner.dataset.hidden === '1' ? ' <em>hidden</em>' : '') + '</span><button type="button" data-le="banner"><i class="fa-solid fa-pen"></i> Edit banner</button></div>'); }
  const hero = $('.page-section[data-type="hero"]');
  const intro = $('.page-intro[data-intro]');
  if (intro) { intro.classList.add('le-sec'); intro.insertAdjacentHTML('afterbegin', '<div class="le-tools"><span class="le-lbl"><i class="fa-solid fa-align-left"></i> Intro content</span><button type="button" data-le="intro"><i class="fa-solid fa-pen"></i> Edit intro</button></div>'); }
  if (!secs.length) document.body.insertAdjacentHTML('beforeend', '<div class="le-empty"><b>This page has no sections yet.</b><button type="button" class="le-b" data-le="add"><i class="fa-solid fa-plus"></i> Add your first section</button></div>');

  /* ---------- helpers ---------- */
  function post(data) {
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    if (!fd.has('csrf')) fd.append('csrf', A.csrf);
    if (!fd.has('page')) fd.append('page', L.pageId);
    return fetch(A.base + '/admin/ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json());
  }
  function toast(msg, bad) { const t = document.createElement('div'); t.className = 'le-toast' + (bad ? ' bad' : ''); t.innerHTML = msg; document.body.appendChild(t); setTimeout(() => t.remove(), bad ? 6000 : 2500); }
  function reload(idx) { const u = new URL(location.href); u.searchParams.set('edit', '1'); u.hash = idx != null ? 'sec-' + idx : ''; location.href = u.toString(); location.reload(); }

  /* ---------- drawer ---------- */
  const drawer = document.createElement('div');
  drawer.className = 'le-drawer';
  drawer.innerHTML = '<div class="le-drawer-box"><div class="le-drawer-head"><b id="leDrawerTitle">Edit</b><button type="button" class="le-x" data-close>&times;</button></div>' +
    '<form id="leForm" class="le-drawer-body"></form>' +
    '<div class="le-drawer-foot"><button type="button" class="le-b le-save" id="leSave"><i class="fa-solid fa-floppy-disk"></i> Save &amp; refresh</button><button type="button" class="le-b" data-close>Cancel</button><span class="le-msg" id="leMsg"></span></div></div>';
  document.body.appendChild(drawer);
  let ctx = null;
  function openDrawer(kind, idx, extra) {
    ctx = Object.assign({ kind, idx }, extra || {});
    $('#leDrawerTitle').textContent = 'Loading…'; $('#leForm').innerHTML = '<p class="le-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</p>'; $('#leMsg').textContent = '';
    drawer.classList.add('show'); document.body.classList.add('le-lock');
    post(Object.assign({ action: 'le_form', kind, idx: idx == null ? 0 : idx }, extra || {})).then(d => {
      if (!d.ok) { $('#leForm').innerHTML = '<p class="le-err">' + esc(d.error || 'Could not load') + '</p>'; return; }
      $('#leDrawerTitle').textContent = d.title; $('#leForm').innerHTML = d.html;
      if (window.ECH_EDITOR_INIT) ECH_EDITOR_INIT();
      if (window.ECH_UPLOADER_INIT) ECH_UPLOADER_INIT($('#leForm'));
      if (window.ECH_ITEMS_INIT) ECH_ITEMS_INIT($('#leForm'));
    }).catch(() => { $('#leForm').innerHTML = '<p class="le-err">Network error</p>'; });
  }
  function closeDrawer() { drawer.classList.remove('show'); document.body.classList.remove('le-lock'); ctx = null; }
  drawer.addEventListener('click', e => { if (e.target.closest('[data-close]') || e.target === drawer) closeDrawer(); });
  $('#leSave').addEventListener('click', () => {
    if (!ctx) return;
    const form = $('#leForm');
    form.querySelectorAll('.we-area').forEach(a => a.dispatchEvent(new Event('blur')));   // sync visual editors → textareas
    form.dispatchEvent(new Event('submit'));                                             // sync items editors
    const fd = new FormData(form);
    fd.append('action', 'le_save'); fd.append('kind', ctx.kind); fd.append('idx', ctx.idx == null ? 0 : ctx.idx);
    if (ctx.type) fd.append('type', ctx.type); if (ctx.at != null) fd.append('at', ctx.at);
    const b = $('#leSave'); b.disabled = true; $('#leMsg').textContent = 'Saving…';
    post(fd).then(d => {
      if (!d.ok) { b.disabled = false; $('#leMsg').textContent = '⚠ ' + (d.error || 'Save failed'); return; }
      $('#leMsg').textContent = '✓ Saved — refreshing…';
      reload(d.idx != null ? d.idx : ctx.idx);
    }).catch(() => { b.disabled = false; $('#leMsg').textContent = '⚠ Network error'; });
  });

  /* ---------- type picker ---------- */
  function pickType(at) {
    const m = document.createElement('div'); m.className = 'le-picker';
    m.innerHTML = '<div class="le-picker-box"><div class="le-drawer-head"><b>Add a section</b><button type="button" class="le-x" data-close>&times;</button></div><div class="le-picker-grid">' +
      L.types.map(t => '<button type="button" data-type="' + esc(t.type) + '"><i class="fa-solid ' + esc(t.icon) + '"></i><b>' + esc(t.label) + '</b><small>' + esc(t.desc) + '</small></button>').join('') + '</div></div>';
    document.body.appendChild(m);
    m.addEventListener('click', e => {
      if (e.target.closest('[data-close]') || e.target === m) { m.remove(); return; }
      const b = e.target.closest('[data-type]'); if (!b) return;
      m.remove(); openDrawer('new', 0, { type: b.dataset.type, at });
    });
  }

  /* ---------- events ---------- */
  document.addEventListener('click', e => {
    const le = e.target.closest('[data-le]');
    if (le) { e.preventDefault(); const k = le.dataset.le; if (k === 'add') pickType(secs.length); else openDrawer(k, 0); return; }
    const b = e.target.closest('.le-tools [data-op]'); if (!b) return;
    e.preventDefault();
    const idx = +b.closest('.le-tools').dataset.idx, op = b.dataset.op;
    if (op === 'edit') return openDrawer('section', idx);
    if (op === 'addafter') return pickType(idx + 1);
    if (op === 'del' && !confirm('Delete this section from the page?')) return;
    b.disabled = true;
    post({ action: 'le_op', op, idx }).then(d => { if (d.ok) reload(d.idx); else { b.disabled = false; toast('⚠ ' + (d.error || 'Failed'), true); } });
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
  // jump to the section we just edited
  if (location.hash && /^#sec-\d+$/.test(location.hash)) { const el = $(location.hash); if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }), 300); }
})();
