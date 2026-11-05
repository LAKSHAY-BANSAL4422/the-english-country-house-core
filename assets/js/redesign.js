/* ==========================================================================
   REDESIGN 2026 — front-end behaviour (vanilla JS, no dependencies)
   Loaded after main.js. Only decorative/UI behaviour lives here; booking,
   forms, OTP, chat and the booking modal stay in their original scripts.

   1. Helpers            5. Room card hover slideshow
   2. Floating navbar    6. About / Sustainability / FAQ tabs + accordion
   3. Desktop dropdowns  7. Testimonials guest book
   4. Mobile drawer      8. Hero video, newsletter placeholder
                         9. Gallery page: filters, load more, lightbox
                         10. Home Welcome: reveal, parallax, counters
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- 1. Helpers ---------- */
  const $ = (s, el) => (el || document).querySelector(s);
  const $$ = (s, el) => Array.from((el || document).querySelectorAll(s));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');

  /* ---------- 2. Floating navbar: full-width at the top, compact floating bar after ~80px ---------- */
  (function () {
    const wrap = $('#rdHeader');
    if (!wrap) return;
    const THRESHOLD = 80;   // px scrolled before the bar floats
    let ticking = false, floating = null;
    const update = () => {
      ticking = false;
      const on = window.scrollY > THRESHOLD;
      if (on !== floating) { floating = on; wrap.classList.toggle('is-floating', on); }
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
  })();

  /* ---------- 3. Desktop dropdowns: hover with close delay, click/keyboard toggle, Esc, nested flyouts ---------- */
  (function () {
    const menu = $('.rd-menu');
    if (!menu) return;
    const items = $$('.rd-menu__item--has-sub', menu);
    const CLOSE_DELAY = 260;   // ms — lets the pointer travel diagonally to the panel ("safe triangle")

    const setOpen = (li, on) => {
      li.classList.toggle('is-open', on);
      const btn = li.querySelector(':scope > .rd-menu__toggle');
      if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (!on) $$('.rd-menu__item.is-open', li).forEach(c => setOpen(c, false));
    };
    const closeSiblings = li => {
      const parent = li.parentElement;
      $$(':scope > .rd-menu__item.is-open', parent).forEach(s => { if (s !== li) setOpen(s, false); });
    };
    const closeAll = () => $$('.rd-menu__item.is-open', menu).forEach(li => setOpen(li, false));

    items.forEach(li => {
      let timer = null;
      li.addEventListener('mouseenter', () => {
        if (!canHover.matches) return;
        clearTimeout(timer); closeSiblings(li); setOpen(li, true);
      });
      li.addEventListener('mouseleave', () => {
        if (!canHover.matches) return;
        clearTimeout(timer); timer = setTimeout(() => setOpen(li, false), CLOSE_DELAY);
      });
      const btn = li.querySelector(':scope > .rd-menu__toggle');
      btn.addEventListener('click', e => {
        e.preventDefault();
        const on = !li.classList.contains('is-open');
        closeSiblings(li); setOpen(li, on);
        if (on) { const first = li.querySelector(':scope > .rd-dd > li > a'); if (first && e.detail === 0) first.focus(); }   // keyboard (Enter/Space) → move into the panel
      });
    });

    // keyboard: Esc closes the current panel and returns focus to its toggle; arrows move within a panel
    menu.addEventListener('keydown', e => {
      const li = e.target.closest('.rd-menu__item');
      if (!li) return;
      if (e.key === 'Escape') {
        const owner = e.target.closest('.rd-dd') ? e.target.closest('.rd-dd').parentElement : (li.classList.contains('is-open') ? li : null);
        if (owner) { setOpen(owner, false); const b = owner.querySelector(':scope > .rd-menu__toggle'); if (b) b.focus(); e.preventDefault(); }
      }
      if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && e.target.closest('.rd-dd')) {
        const links = $$(':scope > li > a', e.target.closest('.rd-dd'));
        const i = links.indexOf(e.target);
        if (i > -1) { e.preventDefault(); links[(i + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length].focus(); }
      }
    });
    // focus leaving the menu or a click elsewhere closes everything
    menu.addEventListener('focusout', e => { if (!menu.contains(e.relatedTarget)) closeAll(); });
    document.addEventListener('click', e => { if (!menu.contains(e.target)) closeAll(); });
  })();

  /* ---------- 4. Mobile drawer with accordion sub-menus ---------- */
  (function () {
    const drawer = $('#rdDrawer'), burger = $('#rdBurger');
    if (!drawer || !burger) return;
    let lastFocus = null;
    const panel = $('.rd-drawer__panel', drawer);
    const open = () => {
      lastFocus = document.activeElement;
      drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false');
      burger.setAttribute('aria-expanded', 'true'); document.body.classList.add('rd-lock');
      setTimeout(() => { const f = $('[data-rd-close].rd-iconbtn', drawer); if (f) f.focus(); }, 60);
    };
    const close = () => {
      drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true');
      burger.setAttribute('aria-expanded', 'false'); document.body.classList.remove('rd-lock');
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    };
    burger.addEventListener('click', open);
    drawer.addEventListener('click', e => {
      if (e.target.closest('[data-rd-close]')) { close(); return; }
      const t = e.target.closest('.rd-acc__toggle');
      if (t) {
        const p = document.getElementById(t.getAttribute('aria-controls'));
        const on = t.getAttribute('aria-expanded') !== 'true';
        t.setAttribute('aria-expanded', on ? 'true' : 'false');
        if (p) p.classList.toggle('is-open', on);
      }
      if (e.target.closest('[data-enquire], [data-book-open]')) close();   // the enquiry form opens on top
    });
    document.addEventListener('keydown', e => {
      if (!drawer.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'Tab') {   // keep focus inside the drawer
        const f = $$('a[href], button:not([disabled])', panel).filter(el => el.offsetParent !== null);
        if (!f.length) return;
        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
      }
    });
    window.matchMedia('(min-width: 1101px)').addEventListener('change', m => { if (m.matches && drawer.classList.contains('is-open')) close(); });
  })();

  /* ---------- 5. Room card hover slideshow ----------
     hover → slide to the next photo (600ms), pause 3.5s, next… loops; leave → glide back to photo 1.
     Touch / no-hover devices: auto-play while the card is in the viewport. Reduced motion: no auto-play. */
  (function () {
    const PAUSE = 3500;
    $$('[data-rd-slides]').forEach(media => {
      const track = $('.rd-room__track', media), imgs = $$('img', track), dots = $$('.rd-room__dots i', media);
      const n = imgs.length;
      if (n < 2) return;
      let i = 0, timer = null, active = false;
      const go = k => {
        i = (k + n) % n;
        track.style.transform = 'translateX(' + (-100 * i) + '%)';
        dots.forEach((d, j) => d.classList.toggle('is-on', j === i));
        const next = imgs[(i + 1) % n]; if (next.loading === 'lazy') next.loading = 'eager';   // warm up the next photo
      };
      const tick = () => { timer = setTimeout(() => { go(i + 1); tick(); }, PAUSE); };
      const start = firstStepNow => {
        if (active || reduceMotion.matches) return;
        active = true; imgs.forEach(im => { if (im.loading === 'lazy') im.loading = 'eager'; });
        if (firstStepNow) go(i + 1);
        tick();
      };
      const stop = reset => { active = false; clearTimeout(timer); timer = null; if (reset) go(0); };

      media.addEventListener('mouseenter', () => { if (canHover.matches) start(true); });
      media.addEventListener('mouseleave', () => { if (canHover.matches) stop(true); });
      media.addEventListener('focusin', () => start(true));
      media.addEventListener('focusout', e => { if (!media.contains(e.relatedTarget)) stop(true); });

      // swipe on touch screens
      let x0 = null;
      media.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
      media.addEventListener('touchend', e => {
        if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; x0 = null;
        if (Math.abs(dx) > 40) { clearTimeout(timer); go(dx < 0 ? i + 1 : i - 1); if (active) tick(); }
      });

      // touch devices: play while visible
      if (!canHover.matches && 'IntersectionObserver' in window) {
        new IntersectionObserver(en => { en[0].isIntersecting ? start(false) : stop(false); }, { threshold: 0.5 }).observe(media);
      }
      document.addEventListener('visibilitychange', () => { if (document.hidden && active) { clearTimeout(timer); } else if (active) { clearTimeout(timer); tick(); } });
    });
  })();

  /* ---------- 6. Tabs (About / Sustainability / FAQ) + accordion ---------- */
  (function () {
    $$('[data-rd-tabs]').forEach(root => {
      const tabs = $$('[role=tab]', root);
      const select = (tab, focus) => {
        tabs.forEach(t => {
          const on = t === tab, p = document.getElementById(t.getAttribute('aria-controls'));
          t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1;
          if (!p) return;
          if (on) {
            if (!p.hidden) return;
            p.hidden = false; p.classList.add('is-entering', 'is-active');
            requestAnimationFrame(() => requestAnimationFrame(() => p.classList.remove('is-entering')));   // fade + slide in
          } else { p.hidden = true; p.classList.remove('is-active'); }
        });
        if (focus) tab.focus();
      };
      tabs.forEach((t, i) => {
        t.addEventListener('click', () => select(t));
        t.addEventListener('keydown', e => {
          let k = null;
          if (e.key === 'ArrowRight') k = (i + 1) % tabs.length;
          if (e.key === 'ArrowLeft') k = (i - 1 + tabs.length) % tabs.length;
          if (e.key === 'Home') k = 0;
          if (e.key === 'End') k = tabs.length - 1;
          if (k !== null) { e.preventDefault(); select(tabs[k], true); }
        });
      });
    });
    // accordion: smooth height via grid-template-rows (0fr → 1fr)
    document.addEventListener('click', e => {
      const q = e.target.closest('.rd-faq__q');
      if (!q) return;
      const a = document.getElementById(q.getAttribute('aria-controls'));
      const on = q.getAttribute('aria-expanded') !== 'true';
      q.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (a) a.classList.toggle('is-open', on);
    });
  })();

  /* ---------- 7. Testimonials "Guest book" ----------
     One review at a time. The active guest's progress line (CSS animation, 7s) is the timer: when it ends the next
     review shows. Pausing = pausing that animation, so the timer resumes exactly where it stopped. Pauses while a mouse
     is over the section, while keyboard focus is inside, while the tab is hidden or the section is off-screen.
     Tabs and arrows jump to a review and restart the line. Works for any number of reviews. */
  (function () {
    const DURATION = 7000;
    $$('[data-rd-guestbook]').forEach(root => {
      const slides = $$('.rd-gb__slide', root), tabs = $$('.rd-gb__tab', root), now = $('[data-rd-gb-now]', root);
      const n = slides.length;
      /* Read more / Read less (round 4): CSS clamps the quote once .has-more is on; a review counts as truncated when
         its text is taller than the clamped box. Re-measured when a review shows, on resize and after fonts load. */
      let open = false;
      const measure = sl => {
        if (!sl || sl.hidden || sl.classList.contains('is-open')) return;
        const p = $('.rd-gb__quote p', sl);
        sl.classList.toggle('is-trunc', p.scrollHeight > p.clientHeight + 2);
      };
      const collapse = sl => {
        if (!sl.classList.contains('is-open')) return;
        sl.classList.remove('is-open');
        const b = $('.rd-gb__more', sl); b.textContent = 'Read more'; b.setAttribute('aria-expanded', 'false');
      };
      root.classList.add('has-more');
      slides.forEach(sl => {
        $('.rd-gb__more', sl)?.addEventListener('click', e => {
          const b = e.currentTarget, opening = !sl.classList.contains('is-open');
          sl.classList.toggle('is-open', opening);
          b.textContent = opening ? 'Read less' : 'Read more';
          b.setAttribute('aria-expanded', String(opening));
          open = opening; sync();
          if (!opening) measure(sl);
        });
      });
      const remeasure = () => slides.forEach(measure);
      remeasure();
      window.addEventListener('resize', remeasure, { passive: true });
      document.fonts?.ready.then(remeasure);
      let sync = () => {};
      if (n < 2) return;
      root.style.setProperty('--gb-dur', DURATION + 'ms');
      let idx = 0, hover = false, focus = false, inView = false;
      sync = () => root.classList.toggle('is-paused', open || hover || focus || !inView || document.hidden);   // an expanded review stays put
      const show = i => {
        idx = (i + n) % n;
        slides.forEach(collapse); open = false;
        slides.forEach((sl, k) => { sl.hidden = k !== idx; sl.classList.toggle('is-active', k === idx); });
        measure(slides[idx]); sync();
        tabs.forEach((t, k) => { t.classList.remove('is-active'); t.setAttribute('aria-current', k === idx ? 'true' : 'false'); });
        void root.offsetWidth;                 // restart the progress animation
        tabs[idx].classList.add('is-active');
        if (now) now.textContent = String(idx + 1).padStart(2, '0');
      };
      tabs.forEach((t, k) => {
        t.addEventListener('click', () => show(k));
        $('.rd-gb__bar i', t).addEventListener('animationend', () => { if (t.classList.contains('is-active')) show(idx + 1); });
      });
      $('[data-rd-next]', root)?.addEventListener('click', () => show(idx + 1));
      $('[data-rd-prev]', root)?.addEventListener('click', () => show(idx - 1));
      root.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse') { hover = true; sync(); } });
      root.addEventListener('pointerleave', e => { if (e.pointerType === 'mouse') { hover = false; sync(); } });
      root.addEventListener('focusin', e => { if (e.target.matches(':focus-visible')) { focus = true; sync(); } });
      root.addEventListener('focusout', e => { if (!root.contains(e.relatedTarget)) { focus = false; sync(); } });
      root.addEventListener('pointerdown', () => { if (focus) { focus = false; sync(); } });
      document.addEventListener('visibilitychange', sync);
      if ('IntersectionObserver' in window) new IntersectionObserver(en => { inView = en[0].isIntersecting; sync(); }, { threshold: 0.3 }).observe(root);
      else inView = true;
      sync();
    });
  })();

  /* ---------- 8. Hero video (reduced motion → stay on the poster) + newsletter placeholder ---------- */
  (function () {
    // Plays /assets/video/hero.mp4 (or the CMS video): muted inline autoplay, play() retried once data is in, any
    // rejection (autoplay blocked, decode error, missing file) leaves the poster photo showing. Pauses while the tab is
    // hidden or the hero is off-screen and resumes after. Reduced motion → starts paused on the poster; the
    // pause/play button (WCAG 2.2.2) lets anyone start or stop it.
    $$('.rd-hero__video').forEach(v => {
      const hero = v.closest('.rd-hero'), btn = hero && hero.querySelector('.rd-hero__vbtn');
      const srcs = v.querySelectorAll('source'), src = srcs[srcs.length - 1];   // round 17: the last source erroring = none could play
      if (!hero || !src) { if (btn) btn.remove(); return; }
      let userPaused = reduceMotion.matches, inView = true;
      const sync = () => {
        if (!btn) return;
        btn.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
        btn.setAttribute('aria-label', userPaused ? 'Play background video' : 'Pause background video');
        hero.classList.toggle('is-video-paused', userPaused);
      };
      const fail = () => { hero.classList.remove('is-playing'); hero.classList.add('is-video-failed'); v.pause(); };
      const tryPlay = () => {
        if (userPaused || document.hidden || !inView) return;
        v.muted = true;
        const p = v.play();
        if (p && p.then) p.then(() => hero.classList.add('is-playing')).catch(() => { hero.classList.remove('is-playing'); });
        else hero.classList.add('is-playing');
      };
      const halt = () => { if (!v.paused) v.pause(); };
      src.addEventListener('error', fail);
      v.addEventListener('error', fail);
      v.addEventListener('playing', () => hero.classList.add('is-playing'));
      if (v.readyState >= 2) tryPlay(); else { v.addEventListener('loadeddata', tryPlay, { once: true }); v.addEventListener('canplay', tryPlay, { once: true }); }
      tryPlay();
      document.addEventListener('visibilitychange', () => (document.hidden ? halt() : tryPlay()));
      if ('IntersectionObserver' in window) new IntersectionObserver(en => { inView = en[0].isIntersecting; inView ? tryPlay() : halt(); }, { threshold: 0.1 }).observe(hero);
      if (btn) btn.addEventListener('click', () => { userPaused = !userPaused; sync(); userPaused ? halt() : tryPlay(); });
      reduceMotion.addEventListener?.('change', () => { userPaused = reduceMotion.matches; sync(); userPaused ? halt() : tryPlay(); });
      if (userPaused) halt();
      sync();
    });

    // PLACEHOLDER — the site has no public newsletter endpoint yet; nothing is sent anywhere.
    $$('[data-rd-newsletter]').forEach(f => f.addEventListener('submit', e => {
      e.preventDefault();
      const msg = f.parentElement.querySelector('.rd-news__msg');
      if (msg) msg.textContent = 'Newsletter sign-up is coming soon. Guests can opt in from their account page.';
    }));
  })();
  /* ---------- 9. Gallery page: category filter (no reload), "Load more", full-screen lightbox ---------- */
  $$('[data-rd-gallery]').forEach(root => {
    const grid = $('[data-rd-gal-grid]', root);
    if (!grid) return;
    const tiles = $$('.rd-gal__tile', grid), pills = $$('.rd-gal__pill', root);
    const moreWrap = $('[data-rd-gal-morewrap]', root), moreBtn = $('[data-rd-gal-more]', root), empty = $('[data-rd-gal-empty]', root);
    const PAGE = +root.dataset.page || 0;
    const OUT = 200, IN = 350;   // fade + scale out, then in (≈350ms total feel)
    let filter = (pills.find(p => p.classList.contains('is-active')) || {}).dataset?.filter || 'all';
    let limit = PAGE || Infinity, shown = [];
    const matches = t => filter === 'all' || t.dataset.cat === filter;
    const render = animate => {
      const list = tiles.filter(matches), next = list.slice(0, limit);
      const leaving = shown.filter(t => !next.includes(t)), entering = next.filter(t => !shown.includes(t));
      const swap = () => {
        leaving.forEach(t => { t.classList.add('is-gone'); t.classList.remove('is-out'); });
        entering.forEach(t => { t.classList.remove('is-gone'); if (animate) t.classList.add('is-out'); });
        if (animate) { void grid.offsetWidth; requestAnimationFrame(() => entering.forEach(t => t.classList.remove('is-out'))); }
      };
      if (animate && leaving.length && !reduceMotion.matches) { leaving.forEach(t => t.classList.add('is-out')); setTimeout(swap, OUT); }
      else swap();
      shown = next;
      if (moreWrap) moreWrap.hidden = list.length <= next.length;
      if (empty) empty.hidden = list.length > 0;
    };
    tiles.forEach(t => t.classList.add('is-gone'));
    render(false);
    pills.forEach(p => p.addEventListener('click', () => {
      if (p.dataset.filter === filter) return;
      filter = p.dataset.filter; limit = PAGE || Infinity;
      pills.forEach(x => { const on = x === p; x.classList.toggle('is-active', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
      render(true);
      try { const u = new URL(location.href); filter === 'all' ? u.searchParams.delete('cat') : u.searchParams.set('cat', filter); history.replaceState(null, '', u); } catch (e) {}
    }));
    if (moreBtn) moreBtn.addEventListener('click', () => {
      const first = tiles.filter(matches)[limit];
      limit += PAGE; render(true);
      if (first) setTimeout(() => first.focus({ preventScroll: true }), IN);
    });

    /* lightbox: counter, prev/next, arrows/Esc, swipe, click outside, focus trap, neighbour preloading */
    const lb = $('[data-rd-lb]', root);
    if (!lb) return;
    document.body.appendChild(lb);   // out of any transformed/overflow parent so position:fixed covers the viewport
    const img = $('[data-rd-lb-img]', lb), cap = $('[data-rd-lb-cap]', lb), count = $('[data-rd-lb-count]', lb), fig = $('.rd-lb__fig', lb);
    const stage = $('[data-rd-lb-stage]', lb), btnClose = $('[data-rd-lb-close]', lb);
    let idx = 0, lastFocus = null, closeTimer = null;
    const preload = i => { const t = shown[(i + shown.length) % shown.length]; if (t) { const im = new Image(); im.src = t.href; } };
    const show = i => {
      idx = (i + shown.length) % shown.length;
      const t = shown[idx], ti = $('img', t);
      fig.classList.remove('is-ready');
      img.onload = () => fig.classList.add('is-ready');
      img.onerror = () => fig.classList.add('is-ready');
      img.src = t.href; img.alt = ti ? ti.alt : '';
      cap.textContent = t.dataset.caption || '';
      count.textContent = (idx + 1) + ' / ' + shown.length;
      if (img.complete && img.naturalWidth) fig.classList.add('is-ready');
      preload(idx + 1); preload(idx - 1);
    };
    const open = i => {
      clearTimeout(closeTimer);
      lastFocus = document.activeElement;
      lb.hidden = false; document.body.classList.add('rd-lb-open');
      show(i);
      requestAnimationFrame(() => requestAnimationFrame(() => lb.classList.add('is-open')));
      btnClose.focus();
    };
    const close = () => {
      lb.classList.remove('is-open'); document.body.classList.remove('rd-lb-open');
      closeTimer = setTimeout(() => { lb.hidden = true; }, reduceMotion.matches ? 0 : 300);
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    };
    grid.addEventListener('click', e => {
      const t = e.target.closest('.rd-gal__tile');
      if (!t || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault(); open(shown.indexOf(t));
    });
    btnClose.addEventListener('click', close);
    $('[data-rd-lb-prev]', lb).addEventListener('click', () => show(idx - 1));
    $('[data-rd-lb-next]', lb).addEventListener('click', () => show(idx + 1));
    stage.addEventListener('click', e => { if (!e.target.closest('.rd-lb__img, .rd-lb__cap')) close(); });
    lb.addEventListener('keydown', e => {
      if (e.key === 'Escape') { e.preventDefault(); close(); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); show(idx + 1); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); show(idx - 1); }
      else if (e.key === 'Tab') {   // focus trap
        const f = $$('button', lb), a = f[0], z = f[f.length - 1];
        if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
        else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
        else if (!lb.contains(document.activeElement)) { e.preventDefault(); a.focus(); }
      }
    });
    let x0 = null, y0 = 0;
    stage.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
    stage.addEventListener('touchend', e => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0; x0 = null;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(idx + (dx < 0 ? 1 : -1));
    });
  });
  /* ---------- 10. Home Welcome: reveal on entry, subtle parallax, count-up stats (once) ---------- */
  $$('[data-rd-welcome]').forEach(root => {
    const counters = $$('[data-rd-count]', root), layers = $$('[data-rd-parallax]', root);
    const fmt = (v, d) => v.toLocaleString('en-IN', { minimumFractionDigits: d, maximumFractionDigits: d });
    const countUp = el => {
      const end = parseFloat(el.dataset.rdCount), d = +el.dataset.decimals || 0;
      if (reduceMotion.matches || !isFinite(end)) { el.textContent = fmt(end, d); return; }
      const t0 = performance.now(), DUR = 1600;
      const step = t => { const k = Math.min(1, (t - t0) / DUR), e = 1 - Math.pow(1 - k, 3); el.textContent = fmt(end * e, d); if (k < 1) requestAnimationFrame(step); };
      el.textContent = fmt(0, d); requestAnimationFrame(step);
    };
    if (!('IntersectionObserver' in window)) { root.classList.add('is-in'); return; }
    counters.forEach(el => { el.textContent = fmt(0, +el.dataset.decimals || 0); });
    new IntersectionObserver((en, io) => { if (en[0].isIntersecting) { root.classList.add('is-in'); io.disconnect(); } }, { threshold: 0.2 }).observe(root);
    const strip = $('.rd-welcome__stats', root);
    if (strip) new IntersectionObserver((en, io) => { if (en[0].isIntersecting) { counters.forEach(countUp); io.disconnect(); } }, { threshold: 0.5 }).observe(strip);
    // parallax: each layer drifts by (its factor × distance of the section centre from the viewport centre)
    let ticking = false, visible = false;
    const move = () => {
      ticking = false;
      if (reduceMotion.matches || window.innerWidth < 600) { layers.forEach(l => { l.style.transform = ''; }); return; }
      const r = root.getBoundingClientRect(), off = (r.top + r.height / 2) - window.innerHeight / 2;
      layers.forEach(l => { l.style.transform = 'translate3d(0,' + (off * parseFloat(l.dataset.rdParallax)).toFixed(1) + 'px,0)'; });
    };
    new IntersectionObserver(en => { visible = en[0].isIntersecting; if (visible) move(); }).observe(root);
    window.addEventListener('scroll', () => { if (visible && !ticking) { ticking = true; requestAnimationFrame(move); } }, { passive: true });
    window.addEventListener('resize', move);
  });

  /* ---------- 11. WhatsApp enquiry form (round 4) ----------
     Replaces online booking. Opened by [data-enquire] (data-room preselects a room), by any link that still points at
     booking.php (CMS buttons, popups — room_id is carried over) and by ?enquire=1 in the URL. Validates in the browser,
     builds the WhatsApp message from the filled-in fields only, shows "You'll be redirected…" and then opens
     https://wa.me/<number>?text=<encoded>.
     Round 18: a copy of the enquiry is saved first (api/form_submit.php, form "wa_enquiry" → Admin → Leads and an email to
     the hotel), GTM gets one form_submit_success (form_name stay_enquiry), and the redirect waits for the copy to be
     saved (at most SAVE_WAIT_MS) and at least REDIRECT_MS. */
  (function () {
    const box = $('#rdEnquiry');
    if (!box) return;
    const form = $('#rdEnqForm', box), panel = $('.rd-enq__panel', box), submit = $('button[type=submit]', form);
    const steps = { form: $('[data-enq-step="form"]', box), go: $('[data-enq-step="go"]', box) };
    const link = $('[data-enq-link]', box), number = (box.dataset.wa || '').replace(/\D/g, '');
    const f = id => $('#rdEnq' + id, box);
    const REDIRECT_MS = 1600, SAVE_WAIT_MS = 4000;
    let lastFocus = null, busy = false, timer = 0, trackNow = null, seq = 0;

    const pad = n => String(n).padStart(2, '0');
    const today = () => { const d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const isDate = v => /^\d{4}-\d{2}-\d{2}$/.test(v) && !isNaN(new Date(v + 'T00:00:00'));
    const niceDate = v => { const [y, m, d] = v.split('-').map(Number); return d + ' ' + MONTHS[m - 1] + ' ' + y; };   // 25 Oct 2026
    const clean = v => String(v || '').replace(/\r\n?/g, '\n').replace(/[\u0000-\u0009\u000B-\u001F\u007F]/g, ' ').trim();   // keep line breaks only
    const oneLine = v => clean(v).replace(/\s+/g, ' ');

    const setStep = name => {
      Object.entries(steps).forEach(([k, el]) => { el.hidden = k !== name; });
    };
    const reset = () => {
      clearTimeout(timer); busy = false; seq++;
      submit.disabled = !number; submit.removeAttribute('aria-busy');
      setStep('form');
    };
    const open = roomId => {
      reset();
      const t = today(); f('In').min = t; f('Out').min = f('In').value || t;
      if (roomId && $('option[value="' + CSS.escape(String(roomId)) + '"]', f('Room'))) f('Room').value = String(roomId);
      lastFocus = document.activeElement;
      box.hidden = false; void box.offsetWidth; box.classList.add('is-open');
      document.body.classList.add('rd-lock');
      setTimeout(() => f('Name').focus(), 60);
    };
    const close = () => {
      if (box.hidden) return;
      reset();
      box.classList.remove('is-open'); document.body.classList.remove('rd-lock');
      setTimeout(() => { if (!box.classList.contains('is-open')) box.hidden = true; }, reduceMotion.matches ? 0 : 300);
      if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus();
    };
    window.rdOpenEnquiry = open;

    /* triggers: [data-enquire] and any leftover link to the old booking page */
    const bookingRoom = a => {
      try {
        const u = new URL(a.getAttribute('href'), location.href);
        if (u.origin !== location.origin || !/\/booking(\.php)?$/i.test(u.pathname)) return null;
        return u.searchParams.get('room_id') || '';
      } catch (err) { return null; }
    };
    document.addEventListener('click', e => {
      const t = e.target.closest('[data-enquire], a[href]');
      if (!t || box.contains(t)) return;
      let room = null;
      if (t.hasAttribute('data-enquire')) room = t.dataset.room || '';
      else room = bookingRoom(t);
      if (room === null) return;
      e.preventDefault();
      open(room);
    });

    /* field errors */
    const err = (input, msg) => {
      const p = $('#' + input.id + '-err', box);
      input.setAttribute('aria-invalid', msg ? 'true' : 'false');
      input.closest('.rd-enq__field').classList.toggle('is-bad', !!msg);
      if (p) { p.textContent = msg || ''; p.hidden = !msg; }
      return !msg;
    };
    const rules = {
      Name: v => { v = oneLine(v); return !v ? 'Please enter your full name.' : v.length < 2 ? 'Please enter your full name.' : ''; },
      Phone: v => {   // round 5: the site-wide phone rule (assets/js/phone.js)
        v = oneLine(v);
        if (!v) return 'Please enter your WhatsApp or mobile number.';
        return window.ECHPhone ? ECHPhone.check(v, true).error : '';
      },
      Email: v => { v = oneLine(v); return v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? 'Please enter a valid email address, or leave it empty.' : ''; },
      In: v => !v ? '' : !isDate(v) ? 'Please choose a valid date.' : v < today() ? 'Check-in can’t be in the past.' : '',
      Out: v => {
        if (!v) return '';
        if (!isDate(v)) return 'Please choose a valid date.';
        const vin = f('In').value;
        if (!vin) return 'Please add your check-in date too.';
        return isDate(vin) && v <= vin ? 'Check-out must be after check-in.' : '';
      },
    };
    const check = key => err(f(key), rules[key](f(key).value));
    Object.keys(rules).forEach(key => {
      f(key).addEventListener('blur', () => { if (f(key).value || f(key).getAttribute('aria-invalid') === 'true') check(key); });
      f(key).addEventListener('input', () => { if (f(key).getAttribute('aria-invalid') === 'true') check(key); });
    });
    f('In').addEventListener('change', () => {
      f('Out').min = f('In').value || today();
      if (f('Out').value) check('Out');
    });

    /* the message: only filled-in fields, in the agreed order */
    const message = () => {
      const room = f('Room').value ? f('Room').selectedOptions[0].textContent : '';
      const special = clean(f('Msg').value).replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n');
      const rows = [
        ['Name', oneLine(f('Name').value)],
        ['Phone', oneLine(f('Phone').value)],
        ['Email', oneLine(f('Email').value)],
        ['Check-in', f('In').value ? niceDate(f('In').value) : ''],
        ['Check-out', f('Out').value ? niceDate(f('Out').value) : ''],
        ['Guests', f('Guests').value],
        ['Room Preference', oneLine(room)],
        ['Special Requirements', special],
      ].filter(r => r[1]).map(r => r[0] + ': ' + r[1]);
      return 'Hello English Country House,\n\nI would like to enquire about a stay at your property.\n\n' + rows.join('\n') +
        '\n\nPlease share the availability, details, and applicable pricing.\n\nThank you.';
    };
    const waUrl = () => 'https://wa.me/' + number + '?text=' + encodeURIComponent(message());
    box.rdEnquiryUrl = waUrl;   // used by the test scripts

    form.addEventListener('submit', e => {
      e.preventDefault();
      if (busy || !number) return;   // one redirect per submission
      const ok = Object.keys(rules).map(check);
      if (ok.includes(false)) { const bad = $('[aria-invalid="true"]', form); if (bad) bad.focus(); return; }
      busy = true; submit.disabled = true; submit.setAttribute('aria-busy', 'true');
      const url = waUrl();
      link.href = url;
      setStep('go');
      $('[data-enq-gotitle]', box).focus();
      // the backup copy (the guest still goes to WhatsApp if it cannot be saved)
      let saved = false, waited = false, gone = false, pushed = 0; const sub = ++seq;   // a later submission (after "Back") cancels this one
      const go = () => {
        if (gone || !busy || sub !== seq || !(waited && saved)) return;
        const wait = pushed + 300 - Date.now();   // GTM gets at least 300 ms to send the event before the page changes
        if (wait > 0) { timer = setTimeout(go, wait); return; }
        gone = true; timer = 0; window.location.href = url;
      };
      const track = trackNow = () => { if (pushed || sub !== seq) return; pushed = Date.now(); if (window.echFormSuccess) window.echFormSuccess('stay_enquiry'); else { window.dataLayer = window.dataLayer || []; dataLayer.push({ event: 'form_submit_success', form_name: 'stay_enquiry' }); } };
      const fd = new FormData();
      [['name', oneLine(f('Name').value)], ['phone', oneLine(f('Phone').value)], ['email', oneLine(f('Email').value)],
       ['checkin', f('In').value], ['checkout', f('Out').value], ['guests', f('Guests').value], ['room_id', f('Room').value],
       ['message', clean(f('Msg').value)], ['form_id', 'wa_enquiry'], ['csrf', (window.ECH && ECH.csrf) || ''], ['page', location.pathname]]
        .forEach(([k, v]) => fd.append(k, v));
      fetch(((window.ECH && ECH.base) || '') + '/api/form_submit.php', { method: 'POST', body: fd, keepalive: true })
        .then(r => r.json()).catch(() => ({}))
        .then(() => { track(); saved = true; go(); });
      setTimeout(() => { track(); saved = true; go(); }, SAVE_WAIT_MS);   // slow connection: don't keep the guest waiting
      timer = setTimeout(() => { waited = true; go(); }, REDIRECT_MS);
    });
    link.addEventListener('click', () => { clearTimeout(timer); if (trackNow) trackNow(); });   // manual open: don't also redirect this tab; count it once
    $('[data-enq-back]', box).addEventListener('click', () => { reset(); f('Name').focus(); });
    box.addEventListener('click', e => { if (e.target.closest('[data-enq-close]')) close(); });
    document.addEventListener('keydown', e => {
      if (box.hidden) return;
      if (e.key === 'Escape') close();
      if (e.key === 'Tab') {   // keep focus inside the dialog
        const els = $$('a[href], button:not([disabled]), input, select, textarea', panel).filter(el => el.offsetParent !== null);
        if (!els.length) return;
        if (e.shiftKey && document.activeElement === els[0]) { e.preventDefault(); els[els.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === els[els.length - 1]) { e.preventDefault(); els[0].focus(); }
      }
    });
    window.addEventListener('pageshow', e => { if (e.persisted) reset(); });   // back from WhatsApp: the form works again

    const qs = new URLSearchParams(location.search);
    if (qs.has('enquire') || location.hash === '#enquire') open(qs.get('room_id') || '');
  })();
})();
