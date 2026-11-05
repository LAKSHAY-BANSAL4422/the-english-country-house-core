/* ECH front-end behaviour */
(function () {
  // preloader — round 17 (speed): fades out as soon as the page is built, not after every photo and the video have
  // downloaded (on a slow phone that held a white screen for up to 2.5 s and delayed the first view)
  const hidePreload = () => { const p = document.getElementById('preload'); if (p) setTimeout(() => p.classList.add('done'), 250); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', hidePreload); else hidePreload();
  setTimeout(() => { const p = document.getElementById('preload'); if (p) p.classList.add('done'); }, 2500);

  // sticky header
  const header = document.getElementById('siteHeader');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 60);
  window.addEventListener('scroll', onScroll); onScroll();

  // mobile nav
  const ham = document.getElementById('hamburger'), nav = document.getElementById('mainNav');
  if (ham) ham.addEventListener('click', () => nav.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (nav && nav.classList.contains('open') && !nav.contains(e.target) && e.target !== ham && !ham.contains(e.target)) nav.classList.remove('open');
  });

  // hero ▼ arrow → scroll to whatever comes after the hero
  document.querySelectorAll('.hero-scroll').forEach(a => a.addEventListener('click', e => { const h = a.closest('.hero'), nx = h && (h.closest('.page-section') || h).nextElementSibling; if (nx) { e.preventDefault(); nx.scrollIntoView({ behavior: 'smooth' }); } }));

  // hero video: skip it only on data-saver / 2G connections (the poster photo shows instead)
  let heroVideoOff = false;
  try { const conn = navigator.connection || {}; if (conn.saveData || /\b(slow-2g|2g)\b/.test(conn.effectiveType || '')) { heroVideoOff = true; document.querySelectorAll('.hero-video').forEach(v => v.remove()); } } catch (e) {}

  /* ---------- hero: YouTube background through the IFrame Player API — muted autoplay, infinite loop, restarts if the browser pauses it ---------- */
  (function () {
    const boxes = document.querySelectorAll('.hero-yt[data-yt]');
    if (!boxes.length || heroVideoOff) return;
    const plainEmbed = box => {   // fallback when the API cannot load (blocked network): classic autoplay/loop embed
      const id = box.dataset.yt;
      box.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&mute=1&loop=1&playlist=' + id + '&controls=0&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&disablekb=1" title="Background video" allow="autoplay; encrypted-media" tabindex="-1"></iframe>';
      box.querySelector('iframe').addEventListener('load', () => setTimeout(() => box.classList.add('playing'), 1200));
    };
    let apiLoaded = false;
    const build = () => {
      apiLoaded = true;
      boxes.forEach(box => {
        const id = box.dataset.yt, holder = box.querySelector('.hero-yt-player'); if (!holder) return;
        let player, kicks = 0;
        const play = () => { try { player.mute(); player.playVideo(); } catch (e) {} };
        player = new YT.Player(holder, {
          host: 'https://www.youtube-nocookie.com', videoId: id, width: '100%', height: '100%',
          playerVars: { autoplay: 1, mute: 1, controls: 0, loop: 1, playlist: id, rel: 0, modestbranding: 1, playsinline: 1, iv_load_policy: 3, disablekb: 1, fs: 0, origin: location.origin },
          events: {
            onReady: () => { play(); box.classList.add('ready'); },
            onStateChange: e => {
              if (e.data === YT.PlayerState.ENDED) { try { player.seekTo(0, true); } catch (x) {} play(); }   // belt-and-braces infinite loop
              if (e.data === YT.PlayerState.PLAYING) box.classList.add('playing');
              if ((e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.CUED) && kicks < 20) { kicks++; setTimeout(play, 800); }   // autoplay held back → nudge it
            },
            onError: () => { box.classList.add('failed'); box.remove(); }   // private / embedding disabled → the poster photo stays
          }
        });
        // some browsers only allow playback after the first interaction — retry then
        ['pointerdown', 'touchstart', 'keydown', 'scroll'].forEach(ev => window.addEventListener(ev, () => { if (!box.classList.contains('playing')) play(); }, { passive: true, once: true }));
      });
    };
    if (window.YT && YT.Player) build();
    else {
      const prev = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = () => { prev && prev(); build(); };
      const sc = document.createElement('script'); sc.src = 'https://www.youtube.com/iframe_api'; sc.async = true;
      sc.onerror = () => boxes.forEach(b => b.remove());   // YouTube unreachable (blocked network) → keep the poster photo, never a broken frame
      document.head.appendChild(sc);
      setTimeout(() => { if (!apiLoaded) boxes.forEach(b => { if (!b.querySelector('iframe')) plainEmbed(b); }); }, 6000);
    }
  })();

  // reveal on scroll
  const revealEls = document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.stagger');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
    revealEls.forEach(el => io.observe(el));
  } else revealEls.forEach(el => el.classList.add('visible'));
  // safety net: never leave content invisible (e.g. observer blocked) — reveal whatever is on screen after a moment, and on every scroll
  const revealOnScreen = () => revealEls.forEach(el => { if (!el.classList.contains('visible')) { const r = el.getBoundingClientRect(); if (r.top < innerHeight + 80 && r.bottom > -80) el.classList.add('visible'); } });
  setTimeout(revealOnScreen, 1200);
  window.addEventListener('scroll', revealOnScreen, { passive: true });
  window.addEventListener('beforeprint', () => revealEls.forEach(el => el.classList.add('visible')));

  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(q => q.addEventListener('click', () => q.parentElement.classList.toggle('open')));

  // date inputs
  const today = new Date().toISOString().slice(0, 10);
  document.querySelectorAll('input[type=date][data-min-today]').forEach(i => i.min = today);
  document.querySelectorAll('form[data-datepair]').forEach(f => {
    const ci = f.querySelector('[name=checkin]'), co = f.querySelector('[name=checkout]');
    if (ci && co) ci.addEventListener('change', () => {
      const d = new Date(ci.value); d.setDate(d.getDate() + 1);
      co.min = d.toISOString().slice(0, 10);
      if (co.value && co.value <= ci.value) co.value = co.min;
    });
  });

  // promotional popups
  document.querySelectorAll('.promo-popup').forEach(pp => {
    const id = pp.id, onceH = parseInt(pp.dataset.once || '24', 10);
    try {
      const seen = localStorage.getItem('pp_' + id);
      if (seen && onceH > 0 && Date.now() - parseInt(seen, 10) < onceH * 3600e3) return;
    } catch (e) {}
    setTimeout(() => {
      pp.classList.add('show');
      try { localStorage.setItem('pp_' + id, String(Date.now())); } catch (e) {}
      const dur = parseInt(pp.dataset.duration || '0', 10);
      if (dur > 0) setTimeout(() => pp.classList.remove('show'), dur * 1000);
    }, (parseInt(pp.dataset.delay || '5', 10)) * 1000);
    pp.addEventListener('click', e => { if (e.target === pp) pp.classList.remove('show'); });
  });
  window.closePopup = id => { const el = document.getElementById('popup' + id); if (el) el.classList.remove('show'); };

  // generic modal
  window.openModal = id => document.getElementById(id)?.classList.add('show');
  window.closeModal = id => document.getElementById(id)?.classList.remove('show');
  document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); }));

  // built form submit
  /* Round 18: one GTM event per successful submission. dataLayer only (GTM-NPSXP9J7 is in every page's <head>);
     the site sends nothing to GA4 / Ads / Meta itself. No personal details go into dataLayer. */
  window.echFormSuccess = function (formName) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: 'form_submit_success', form_name: formName });
  };

  /* Form-builder forms (Contact page …). The entry is saved first (Admin → Leads + Form submissions, and an email to the
     form's address); only when the server confirms it does GTM get form_submit_success. A form with data-wa (the
     Contact form, round 18) then sends the guest to WhatsApp with the message filled in, 300 ms later.
     form.dataset.busy stops double clicks / double Enter from sending or counting twice. */
  window.submitBuiltForm = function (form) {
    if (form.dataset.busy === '1') return false;
    form.dataset.busy = '1';
    const msg = form.querySelector('.form-msg');
    const btn = form.querySelector('button[type=submit]');
    const wa = (form.dataset.wa || '').replace(/\D/g, '');
    const fd = new FormData(form);
    fd.append('form_id', form.dataset.form);
    fd.append('csrf', window.ECH.csrf);
    fd.append('page', location.pathname);   // round 18: no query string (it can carry campaign codes, never personal details)
    if (btn) { btn.disabled = true; btn.dataset.t = btn.textContent; btn.textContent = 'Sending…'; }
    let leaving = false;
    fetch(window.ECH.base + '/api/form_submit.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (msg) { msg.textContent = d.message || (d.ok ? 'Sent!' : 'Failed'); msg.className = 'form-msg ' + (d.ok ? 'ok' : 'err'); }
        if (!d.ok) return;
        window.echFormSuccess(form.dataset.gaName || ('form_' + form.dataset.form));
        if (wa) {
          const url = 'https://wa.me/' + wa + '?text=' + encodeURIComponent(builtFormWhatsApp(form));
          leaving = true;
          if (msg) {
            msg.textContent = 'Thank you! Opening WhatsApp so you can send us your message… ';
            const a = document.createElement('a'); a.href = url; a.rel = 'noopener'; a.textContent = 'Open WhatsApp';
            msg.appendChild(a);
          }
          setTimeout(() => { window.location.href = url; }, 300);
        }
        form.reset();
      })
      .catch(() => { if (msg) { msg.textContent = 'Network error — please retry.'; msg.className = 'form-msg err'; } })
      .finally(() => {
        if (leaving) return;   // stays locked until the page changes; pageshow below unlocks it on "Back"
        form.dataset.busy = '';
        if (btn) { btn.disabled = false; btn.textContent = btn.dataset.t; }
      });
    return false;
  };
  /* the WhatsApp message for a form-builder form: its filled-in fields, by label, in form order */
  function builtFormWhatsApp(form) {
    const rows = [];
    form.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
      if (el.type === 'hidden' || el.name === 'website_hp' || el.name === 'g-recaptcha-response') return;
      const v = String(el.value || '').replace(/\r\n?/g, '\n').trim();
      if (!v) return;
      const box = el.closest('div'), lab = box && box.querySelector('label');
      rows.push((lab ? lab.textContent.trim() : (el.placeholder || el.name)) + ': ' + v);
    });
    return 'Hello English Country House,\n\nI am enquiring from your website.\n\n' + rows.join('\n') + '\n\nThank you.';
  }
  window.addEventListener('pageshow', e => {
    if (!e.persisted) return;
    document.querySelectorAll('form[data-busy="1"]').forEach(f => {
      f.dataset.busy = '';
      const b = f.querySelector('button[type=submit]'); if (b) { b.disabled = false; if (b.dataset.t) b.textContent = b.dataset.t; }
    });
  });

  // room inquiry modal helper
  window.openInquiry = function (roomId, roomName) {
    const m = document.getElementById('inquiryModal');
    if (!m) return;
    const sel = m.querySelector('[name=room_id]');
    if (sel && roomId) sel.value = roomId;
    const ttl = m.querySelector('.inq-room');
    if (ttl) ttl.textContent = roomName ? 'Enquiry — ' + roomName : 'Booking Enquiry';
    m.classList.add('show');
  };

  // live price quote on booking page
  window.fetchQuote = function () {
    const f = document.getElementById('quoteForm');
    if (!f) return;
    const box = document.getElementById('quoteBox');
    const fd = new FormData(f);
    if (!fd.get('room_id') || !fd.get('checkin') || !fd.get('checkout')) return;
    box.innerHTML = '<p style="color:#8a93a0">Calculating best price…</p>';
    fetch(window.ECH.base + '/api/quote.php?' + new URLSearchParams(fd).toString())
      .then(r => r.json())
      .then(d => {
        if (!d.ok) { box.innerHTML = '<div class="alert err">' + d.error + '</div>'; return; }
        let rows = d.lines.map(l =>
          `<tr><td>${l.date}${l.rule ? ' <small style="color:var(--c-primary)">(' + l.rule + ')</small>' : ''}</td><td style="text-align:right">${l.total_f}</td></tr>`).join('');
        box.innerHTML = `<table class="tbl"><tr><th>Night</th><th style="text-align:right">Price</th></tr>${rows}
          <tr><td><b>Subtotal</b></td><td style="text-align:right">${d.subtotal_f}</td></tr>
          <tr><td>${d.tax_label} (${d.tax_percent}%)</td><td style="text-align:right">${d.tax_f}</td></tr>
          <tr><td style="font-size:16px"><b>Total</b></td><td style="text-align:right;font-size:16px"><b>${d.total_f}</b></td></tr></table>
          <p class="price-note">${d.available ? '✅ Available for your dates.' : '❌ Sorry, this room is sold out for the selected dates. Try different dates or send an enquiry.'}</p>`;
        setProceed(d.available ? 'ok' : 'soldout');
      })
      .catch(() => { box.innerHTML = '<div class="alert err">Could not fetch the live price — you can still continue, the final price is confirmed on the next step.</div>'; setProceed('ok'); });
  };
  function payLabel(btn) {
    const m = (document.querySelector('input[name=payment_method]:checked') || {}).value;
    return m === 'cod' ? btn.dataset.cod : btn.dataset.pay;
  }
  function setProceed(state) {
    const btn = document.getElementById('proceedBtn'); if (!btn) return;
    const span = btn.querySelector('span') || btn;
    if (state === 'ok') { btn.disabled = false; span.innerHTML = payLabel(btn); btn.classList.remove('btn-disabled'); }
    else if (state === 'soldout') { btn.disabled = true; span.textContent = 'Sold out for these dates'; }
    else { btn.disabled = true; span.textContent = 'Select room & dates'; }
  }
  document.addEventListener('change', e => { if (e.target.name === 'payment_method') { const b = document.getElementById('proceedBtn'); if (b && !b.disabled) (b.querySelector('span') || b).innerHTML = payLabel(b); } });

  // one click only: show progress + block double submits (runs after the OTP guard, which may cancel the submit)
  document.addEventListener('submit', e => {
    if (e.target.id !== 'quoteForm' || e.defaultPrevented) return;
    const b = document.getElementById('proceedBtn'); if (!b) return;
    setTimeout(() => { b.disabled = true; (b.querySelector('span') || b).textContent = 'Processing…'; }, 0);
  });
  // dates / room arriving from the quick-booking bar or a room page → price immediately (no need to re-pick dates)
  document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('quoteForm')) fetchQuote(); });

  /* ---------- email OTP verification on booking page ---------- */
  const vb = document.getElementById('verifyBox');
  if (vb) {
    const emailInput = document.querySelector('#quoteForm [name=email]');
    const state = document.getElementById('verifyState');
    const codeRow = document.getElementById('verifyCodeRow');
    const post = (action, data) => {
      const fd = new FormData();
      fd.append('action', action); fd.append('csrf', window.ECH.csrf);
      Object.keys(data || {}).forEach(k => fd.append(k, data[k]));
      return fetch(window.ECH.base + '/api/otp.php', { method: 'POST', body: fd }).then(r => r.json());
    };
    window.sendBookingOtp = function () {
      const email = emailInput.value.trim();
      const name = document.querySelector('#quoteForm [name=guest_name]')?.value || '';
      if (!email) { state.textContent = 'Enter your email first.'; return; }
      state.textContent = 'Sending code…';
      post('send', { email, name }).then(d => {
        state.textContent = d.message || '';
        if (d.ok) { codeRow.style.display = 'flex'; setTimeout(() => document.getElementById('otpCode').focus(), 50); }
        else if (/still confirm/i.test(d.message || '')) { vb.dataset.skip = '1'; state.innerHTML = '<b style="color:#b9600d">' + d.message + '</b>'; }
      }).catch(() => state.textContent = 'Network error — please try again.');
    };
    // guard the Confirm click: not verified yet → send the code now, open the code box and explain (no page reload, nothing typed is lost)
    const qf = document.getElementById('quoteForm');
    if (qf) qf.addEventListener('submit', e => {
      if (vb.classList.contains('ok') || vb.dataset.skip === '1') return;
      e.preventDefault();
      const email = emailInput.value.trim();
      if (!email) { state.textContent = 'Enter your email first.'; emailInput.focus(); return; }
      if (codeRow.style.display === 'flex' && document.getElementById('otpCode').value.trim().length === 6) { window.verifyBookingOtp(true); return; }
      if (codeRow.style.display !== 'flex') window.sendBookingOtp();
      else state.textContent = 'Enter the 6-digit code we emailed you, then press Verify.';
      vb.scrollIntoView({ behavior: 'smooth', block: 'center' });
      vb.classList.add('pulse'); setTimeout(() => vb.classList.remove('pulse'), 1600);
    });
    window.verifyBookingOtp = function (thenSubmit) {
      const email = emailInput.value.trim();
      const code = document.getElementById('otpCode').value.trim();
      post('verify', { email, code }).then(d => {
        if (d.ok) {
          vb.classList.add('ok'); vb.dataset.email = email.toLowerCase();
          vb.innerHTML = '<b style="color:#1d7d4d"><i class="fa-solid fa-circle-check"></i> Email verified — you are now logged in.</b>';
          document.getElementById('emailVerifiedFlag').value = '1';
          if (thenSubmit === true) document.getElementById('proceedBtn').click();
        } else state.textContent = d.message || 'Verification failed';
      });
    };
    if (emailInput) emailInput.addEventListener('input', () => {
      if (vb.classList.contains('ok') && emailInput.value.trim().toLowerCase() !== vb.dataset.email) {
        // email changed after verification — require again (server enforces too)
      }
    });
  }

  /* ---------- room card photo slider: auto-plays through every photo and loops back to the first (arrows, dots, swipe) ---------- */
  document.querySelectorAll('.rc-slider').forEach((box, idx) => {
    const imgs = box.querySelectorAll('.rc-slides img'), dots = box.querySelectorAll('.rc-dots i');
    const every = Math.max(0, +(box.dataset.auto || 0)) * 1000;          // 0 = only slides while hovered
    let i = 0, timer = null, x0 = null, onScreen = true, hover = false;
    const show = k => { i = (k + imgs.length) % imgs.length; imgs.forEach((im, n) => im.classList.toggle('on', n === i)); dots.forEach((d, n) => d.classList.toggle('on', n === i)); const nx = imgs[(i + 1) % imgs.length]; if (nx.loading === 'lazy') nx.loading = 'eager'; };
    const stop = () => { clearInterval(timer); timer = null; };
    const play = (ms) => { stop(); if (!ms || document.hidden || !onScreen) return; timer = setInterval(() => show(i + 1), ms); };
    const resume = () => play(hover ? 2200 : every);
    const manual = k => { show(k); stop(); setTimeout(resume, 5000); };   // a click pauses the auto-slide for 5 s
    box.querySelector('.rc-prev').addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); manual(i - 1); });
    box.querySelector('.rc-next').addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); manual(i + 1); });
    box.addEventListener('mouseenter', () => { hover = true; resume(); });
    box.addEventListener('mouseleave', () => { hover = false; resume(); });
    box.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 40) { e.preventDefault(); manual(dx < 0 ? i + 1 : i - 1); } });
    // only animate cards that are on screen / in a visible tab (saves battery), staggered so the row doesn't flip in lockstep
    if ('IntersectionObserver' in window) new IntersectionObserver(en => { onScreen = en[0].isIntersecting; resume(); }, { threshold: 0.1 }).observe(box);
    document.addEventListener('visibilitychange', resume);
    setTimeout(resume, 900 + idx * 650);
  });

  /* ---------- room page booking form: dates + guests → live price & availability → Book Now (booking page, pre-filled) ---------- */
  document.querySelectorAll('[data-room-book]').forEach(f => {
    const inp = f.querySelector('[name=checkin]'), out = f.querySelector('[name=checkout]'), box = f.querySelector('.rb-quote'), btn = f.querySelector('button[type=submit]'), lbl = btn.querySelector('span');
    const iso = d => d.toISOString().slice(0, 10);
    const set = (on, text) => { btn.disabled = !on; lbl.textContent = text; };
    let seq = 0;
    const quote = () => {
      if (inp.value) { const m = new Date(inp.value); m.setDate(m.getDate() + 1); out.min = iso(m); if (!out.value || out.value <= inp.value) out.value = iso(m); }
      if (!inp.value || !out.value) { set(false, 'Select dates'); return; }
      const my = ++seq; set(false, 'Checking…'); box.innerHTML = 'Checking availability &amp; price…';
      fetch(window.ECH.base + '/api/quote.php?' + new URLSearchParams(new FormData(f)).toString()).then(r => r.json()).then(d => {
        if (my !== seq) return;
        if (!d.ok) { box.innerHTML = '<span class="bad">' + d.error + '</span>'; set(false, 'Change dates'); return; }
        box.innerHTML = '<table><tr><td>' + d.nights + ' night' + (d.nights > 1 ? 's' : '') + '</td><td>' + d.subtotal_f + '</td></tr><tr><td>' + d.tax_label + ' (' + d.tax_percent + '%)</td><td>' + d.tax_f + '</td></tr><tr class="rb-total"><td>Total</td><td>' + d.total_f + '</td></tr></table>' +
          (d.available ? '<div class="ok" style="margin-top:6px">✓ Available for your dates</div>' : '<div class="bad" style="margin-top:6px">✕ Sold out for these dates — try other dates</div>');
        d.available ? set(true, 'Book Now') : set(false, 'Sold out');
      }).catch(() => { box.innerHTML = 'Could not fetch the price — you can still continue.'; set(true, 'Continue to booking'); });
    };
    [inp, out, f.querySelector('[name=adults]'), f.querySelector('[name=children]')].forEach(el => el && el.addEventListener('change', quote));
    // dates carried over from a link (?checkin=…&checkout=…) are pre-filled and priced straight away
    const q = new URLSearchParams(location.search);
    if (q.get('checkin')) { inp.value = q.get('checkin'); out.value = q.get('checkout') || ''; quote(); }
    f.addEventListener('submit', () => set(false, 'Opening booking…'));
  });

  /* ---------- room page photo slider ---------- */
  document.querySelectorAll('[data-rs]').forEach(rs => {
    const imgs = rs.querySelectorAll('.rs-stage img'), th = rs.querySelectorAll('.rs-thumbs img'), cnt = rs.querySelector('.rs-count b');
    let i = 0, timer = null, x0 = null;
    // round 15: centre the active thumbnail by scrolling the thumbnail strip sideways only. scrollIntoView() also scrolled
    // the page itself, so every 5-second slide pulled a reader who had scrolled down back up to the slider.
    const centerThumb = t => { const box = t.parentElement; if (!box) return; const bl = box.getBoundingClientRect().left, tl = t.getBoundingClientRect().left;
      box.scrollTo({ left: box.scrollLeft + (tl - bl) - (box.clientWidth - t.offsetWidth) / 2, behavior: 'smooth' }); };
    const show = k => { i = (k + imgs.length) % imgs.length; imgs.forEach((im, n) => { im.classList.toggle('on', n === i); if (Math.abs(n - i) <= 1 && im.loading === 'lazy') im.loading = 'eager'; }); th.forEach((t, n) => t.classList.toggle('on', n === i)); if (th[i]) centerThumb(th[i]); if (cnt) cnt.textContent = i + 1; };
    const play = () => { clearInterval(timer); if (imgs.length > 1) timer = setInterval(() => show(i + 1), 5000); };
    rs.querySelector('.rs-prev')?.addEventListener('click', () => { show(i - 1); play(); });
    rs.querySelector('.rs-next')?.addEventListener('click', () => { show(i + 1); play(); });
    th.forEach(t => t.addEventListener('click', () => { show(+t.dataset.k); play(); }));
    const full = () => { if (window.openLightbox) openLightbox(imgs[i].dataset.full || imgs[i].src, imgs[i].alt); };
    rs.querySelector('.rs-full')?.addEventListener('click', full);
    imgs.forEach(im => im.addEventListener('click', full));
    rs.addEventListener('mouseenter', () => clearInterval(timer)); rs.addEventListener('mouseleave', play);
    rs.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; clearInterval(timer); }, { passive: true });
    rs.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 40) show(dx < 0 ? i + 1 : i - 1); play(); });
    document.addEventListener('keydown', e => { if (document.getElementById('lightbox')?.classList.contains('show')) return; if (e.key === 'ArrowRight') show(i + 1); if (e.key === 'ArrowLeft') show(i - 1); });
    play();
  });

  /* ---------- Explore Mussoorie: attraction detail popup + interactive map ---------- */
  (function () {
    const modal = document.getElementById('atModal');
    const D = () => window.ECH_ATTR || {};
    let cur = null, lastFocus = null;
    function openAt(id) {
      const a = D()[id]; if (!a || !modal) return;
      cur = a; lastFocus = document.activeElement;
      const img = document.getElementById('atImg'), th = document.getElementById('atThumbs'), media = modal.querySelector('.at-media');
      media.classList.toggle('no-img', !a.imgs.length);
      img.src = a.imgs[0] || ''; img.alt = a.name;
      th.innerHTML = a.imgs.length > 1 ? a.imgs.map((u, i) => '<img src="' + u + '" alt="" class="' + (i ? '' : 'on') + '" data-i="' + i + '" loading="lazy">').join('') : '';
      document.getElementById('atCat').innerHTML = '<i class="fa-solid ' + a.icon + '"></i> ' + a.label;
      document.getElementById('atName').textContent = a.name;
      const dist = document.getElementById('atDist'), time = document.getElementById('atTime');
      dist.style.display = a.dist ? '' : 'none'; dist.querySelector('b').textContent = a.dist;
      time.style.display = a.time ? '' : 'none'; time.querySelector('b').textContent = a.time;
      document.getElementById('atText').innerHTML = (a.long && a.long.trim()) ? a.long : '<p>' + a.desc + '</p>';
      document.getElementById('atDir').href = a.maps;
      const wa = document.getElementById('atWa'); if (wa) { try { const u = new URL(wa.href); u.searchParams.set('text', 'Hi! Can you help us plan a visit to ' + a.name + '?'); wa.href = u.toString(); } catch (e) {} }
      modal.classList.add('show'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('bk-lock');
      setTimeout(() => modal.querySelector('.at-x').focus({ preventScroll: true }), 30);
    }
    function closeAt() { if (!modal) return; modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('bk-lock'); lastFocus && lastFocus.focus && lastFocus.focus(); }
    window.openAttraction = openAt;
    document.addEventListener('click', e => {
      const t = e.target.closest('[data-at-open]');
      if (t) { e.preventDefault(); openAt(+t.dataset.atOpen); return; }
      if (modal && (e.target === modal || e.target.closest('[data-at-close]'))) closeAt();
      const th = e.target.closest('#atThumbs img'); if (th) { document.getElementById('atImg').src = th.src; document.querySelectorAll('#atThumbs img').forEach(i => i.classList.toggle('on', i === th)); }
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeAt();
      const t = e.target.closest && e.target.closest('[data-at-open]'); if (t && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openAt(+t.dataset.atOpen); }
    });

    // map (Leaflet + OpenStreetMap, loaded only on pages that have a map section)
    const maps = document.querySelectorAll('[data-at-map]');
    if (!maps.length) return;
    let tries = 0;
    const init = () => {
      if (!window.L) { if (++tries < 40) return setTimeout(init, 250); maps.forEach(m => { const f = m.querySelector('.at-map-fallback p'); if (f) f.textContent = 'Map could not load — open it in Google Maps instead.'; }); return; }
      maps.forEach(el => {
        const home = JSON.parse(el.dataset.home || '{}'), pins = JSON.parse(el.dataset.pins || '[]');
        el.innerHTML = '';
        const map = L.map(el, { scrollWheelZoom: false, zoomControl: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 17, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(map);
        const icon = (cls, html) => L.divIcon({ className: 'at-pin ' + cls, html, iconSize: [34, 42], iconAnchor: [17, 42], popupAnchor: [0, -40] });
        const bounds = [];
        if (home.lat) { L.marker([home.lat, home.lng], { icon: icon('home', '<i class="fa-solid fa-house-chimney"></i>'), zIndexOffset: 1000 }).addTo(map).bindPopup('<b>' + home.name + '</b><br><small>You are here</small>'); bounds.push([home.lat, home.lng]); }
        const markers = {};
        pins.forEach(p => {
          const m = L.marker([p.lat, p.lng], { icon: icon(p.cat, '<i class="fa-solid fa-location-dot"></i>') }).addTo(map)
            .bindPopup('<b>' + p.name + '</b>' + (p.dist ? '<br><small>' + p.dist + ' from the house</small>' : '') + '<br><a href="#" class="at-pop-link" data-at-open="' + p.id + '">View details →</a>');
          markers[p.id] = m; bounds.push([p.lat, p.lng]);
        });
        if (bounds.length) map.fitBounds(bounds, { paddingTopLeft: [30, 52], paddingBottomRight: [30, 20] });   // extra room at the top: pins are 42px tall above their point
        const side = el.closest('.at-map-wrap');
        side && side.querySelectorAll('[data-at-pin]').forEach(li => {
          li.addEventListener('mouseenter', () => { const m = markers[li.dataset.atPin]; if (m) { m.openPopup(); } });
        });
        setTimeout(() => map.invalidateSize(), 400);
      });
    };
    if (document.readyState === 'complete') init(); else window.addEventListener('load', init);
  })();

  /* ---------- amenities sliding popup (left / right, swipe, keyboard, thumbnails) ---------- */
  (function () {
    const box = document.getElementById('amSlider');
    if (!box || !window.ECH_AMENITIES || !ECH_AMENITIES.length) return;
    const S = ECH_AMENITIES, img = document.getElementById('amImg'), title = document.getElementById('amTitle'), text = document.getElementById('amText'), count = document.getElementById('amCount'), thumbs = document.getElementById('amThumbs');
    let i = 0;
    const esc = v => String(v || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    thumbs.innerHTML = S.map((s, k) => '<img src="' + esc(s.src) + '" alt="' + esc(s.alt || s.title) + '" data-k="' + k + '" loading="lazy">').join('');   // v21: real alt text
    function show(k) {
      i = (k + S.length) % S.length;
      const s = S[i];
      img.style.animation = 'none'; void img.offsetWidth; img.style.animation = '';
      img.src = s.src; img.alt = s.alt || s.title; title.textContent = s.title; text.textContent = s.text || '';
      count.textContent = (i + 1) + ' / ' + S.length + ' · Amenities';
      thumbs.querySelectorAll('img').forEach(t => t.classList.toggle('on', +t.dataset.k === i));
      const on = thumbs.querySelector('img.on'); if (on && on.scrollIntoView) on.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
      // preload neighbours
      [i + 1, i - 1].forEach(n => { const p = new Image(); p.src = S[(n + S.length) % S.length].src; });
    }
    function open(k) { show(k); box.classList.add('show'); box.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; }
    function close() { box.classList.remove('show'); box.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
    document.addEventListener('click', e => {
      const t = e.target.closest('[data-am-open]');
      if (t) { e.preventDefault(); const id = +t.dataset.amOpen; const k = S.findIndex(s => s.id === id); open(k < 0 ? 0 : k); return; }
      if (e.target.closest('[data-am-prev]')) show(i - 1);
      else if (e.target.closest('[data-am-next]')) show(i + 1);
      else if (e.target.closest('[data-am-close]') || (e.target === box)) close();
      else if (e.target.closest('#amThumbs img')) show(+e.target.dataset.k);
    });
    document.addEventListener('keydown', e => { if (!box.classList.contains('show')) return; if (e.key === 'ArrowRight') show(i + 1); if (e.key === 'ArrowLeft') show(i - 1); if (e.key === 'Escape') close(); });
    let sx = 0;
    box.addEventListener('touchstart', e => { sx = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', e => { const dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) show(dx < 0 ? i + 1 : i - 1); }, { passive: true });
    // auto-advance slowly while open
    setInterval(() => { if (box.classList.contains('show') && box.dataset.hold !== '1') show(i + 1); }, 6000);
    box.addEventListener('mouseenter', () => box.dataset.hold = '1'); box.addEventListener('mouseleave', () => box.dataset.hold = '0');
  })();

  /* ---------- abandoned-checkout lead capture ----------
     As soon as a visitor types a usable phone/email on the booking form (or any
     form marked data-capture) their details are quietly saved as a lead, so the
     team can follow up even if they never press Submit. Harmless duplicates are
     merged server-side, and completing the booking merges into the same lead. */
  (function () {
    const forms = [];
    const qf = document.getElementById('quoteForm');
    if (qf) forms.push({ form: qf, kind: 'booking' });
    document.querySelectorAll('form[data-capture]').forEach(f => forms.push({ form: f, kind: 'form' }));
    if (!forms.length || !window.ECH) return;

    let timer = null, lastSent = '', submitted = false;

    function fieldVal(form, names) {
      for (const n of names) { const el = form.querySelector('[name=' + n + ']'); if (el && el.value) return el.value; }
      return '';
    }
    function payload(entry) {
      const f = entry.form;
      const data = new FormData();
      data.append('csrf', ECH.csrf);
      data.append('kind', entry.kind);
      data.append('name',  fieldVal(f, ['guest_name', 'name', 'fullname']));
      data.append('phone', fieldVal(f, ['phone', 'mobile', 'telephone']));
      data.append('email', fieldVal(f, ['email']));
      data.append('page', location.pathname + location.search);
      const room = f.querySelector('[name=room_id]');
      if (room) {
        data.append('room_id', room.value || '');
        const opt = room.options && room.selectedIndex >= 0 ? room.options[room.selectedIndex] : null;
        if (opt && room.value) data.append('room', opt.text);
      }
      data.append('checkin',  fieldVal(f, ['checkin']));
      data.append('checkout', fieldVal(f, ['checkout']));
      const ad = fieldVal(f, ['adults']), ch = fieldVal(f, ['children']);
      if (ad) data.append('guests', ad + ' adult(s)' + (ch && ch !== '0' ? ', ' + ch + ' child(ren)' : ''));
      return data;
    }
    function worthSending(entry) {
      const f = entry.form;
      const phone = fieldVal(f, ['phone', 'mobile', 'telephone']).replace(/[^0-9+]/g, '');
      const email = fieldVal(f, ['email']);
      return phone.length >= 7 || /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
    }
    function fingerprint(entry) {
      const f = entry.form;
      return [fieldVal(f, ['guest_name', 'name', 'fullname']), fieldVal(f, ['phone', 'mobile', 'telephone']),
              fieldVal(f, ['email']), fieldVal(f, ['room_id']), fieldVal(f, ['checkin']), fieldVal(f, ['checkout'])].join('|');
    }
    function send(entry, useBeacon) {
      if (submitted || !worthSending(entry)) return;
      const fp = fingerprint(entry);
      if (fp === lastSent) return;
      lastSent = fp;
      const url = ECH.base + '/api/capture.php';
      const data = payload(entry);
      if (useBeacon && navigator.sendBeacon) navigator.sendBeacon(url, data);
      else fetch(url, { method: 'POST', body: data, keepalive: true }).catch(() => {});
    }
    forms.forEach(entry => {
      entry.form.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => send(entry, false), 2500);
      });
      entry.form.addEventListener('submit', () => { submitted = true; clearTimeout(timer); });
    });
    window.addEventListener('pagehide', () => forms.forEach(entry => send(entry, true)));
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') forms.forEach(entry => send(entry, true));
    });
  })();
})();
