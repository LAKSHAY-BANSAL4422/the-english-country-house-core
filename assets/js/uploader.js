/**
 * ECH image / video uploader component (admin panel + live editor).
 *
 *   <div data-uploader data-name="images[]" data-multiple="1" data-max="24" data-subdir="rooms"
 *        data-value='["uploads/rooms/a.jpg"]' data-cover-name="cover_image" data-cover="uploads/rooms/a.jpg"></div>
 *   <div data-uploader data-name="banner_image" data-subdir="pages" data-value="uploads/pages/x.jpg"></div>
 *   <div data-uploader data-name="video_file" data-kind="video" data-subdir="video"></div>
 *
 * - Pictures are resized/compressed in the browser first (max side ADMIN.upMaxPx, JPEG quality ADMIN.upQuality),
 *   so a 6 MB phone photo becomes ~300 KB and never trips the hosting's upload limit.
 * - Files are sent one by one to admin/upload.php (AJAX) with progress; only the saved paths go into the form.
 * - Drag-and-drop to add, drag thumbnails to reorder, ★ = cover photo, ✕ = remove, 🖼 = pick from Gallery.
 */
(function () {
  'use strict';
  const A = () => window.ADMIN || { base: '', csrf: '' };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const urlOf = p => /^https?:\/\//.test(p) ? p : A().base + '/' + p;

  async function shrink(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return file;
    const maxPx = +(A().upMaxPx || 2000), q = (+(A().upQuality || 86)) / 100;
    try {
      const bmp = await createImageBitmap(file);
      let { width: w, height: h } = bmp;
      const scale = Math.min(1, maxPx / Math.max(w, h));
      if (scale === 1 && file.size < 900 * 1024) { bmp.close && bmp.close(); return file; }   // already small
      w = Math.round(w * scale); h = Math.round(h * scale);
      const c = document.createElement('canvas'); c.width = w; c.height = h;
      c.getContext('2d').drawImage(bmp, 0, 0, w, h); bmp.close && bmp.close();
      const keepPng = file.type === 'image/png' && file.size < 1.2 * 1024 * 1024;        // small PNGs (logos / transparency) stay PNG
      const type = keepPng ? 'image/png' : 'image/jpeg';
      const blob = await new Promise(r => c.toBlob(r, type, q));
      if (!blob || blob.size >= file.size) return file;
      return new File([blob], file.name.replace(/\.[^.]+$/, '') + (keepPng ? '.png' : '.jpg'), { type });
    } catch (e) { return file; }
  }

  /* ---- video: compress in the browser before upload (720p, ~2.4 Mbps) — no server tools needed ----
     Real-time re-encode through a canvas + MediaRecorder (WebM VP9/VP8 in Chrome/Firefox/Edge, MP4/H.264 in Safari).
     Audio is kept through Web Audio. Skipped when the file is already small or the browser cannot do it. */
  function videoCompressSupported() {
    return !!(window.MediaRecorder && document.createElement('canvas').captureStream && (MediaRecorder.isTypeSupported('video/webm;codecs=vp9') || MediaRecorder.isTypeSupported('video/webm;codecs=vp8') || MediaRecorder.isTypeSupported('video/webm') || MediaRecorder.isTypeSupported('video/mp4')));
  }
  function compressVideo(file, onProgress, cancelRef) {
    return new Promise((resolve) => {
      const minMB = +(A().videoCompressMB || 6);
      if (!/^video\//.test(file.type) || file.size < minMB * 1024 * 1024 || !videoCompressSupported()) return resolve(file);
      const url = URL.createObjectURL(file);
      const v = document.createElement('video'); v.src = url; v.muted = true; v.playsInline = true; v.preload = 'auto'; v.crossOrigin = 'anonymous';
      let done = false, rec = null, timer = null;
      const finish = out => { if (done) return; done = true; clearInterval(timer); try { v.pause(); } catch (e) {} URL.revokeObjectURL(url); resolve(out); };
      v.onerror = () => finish(file);
      v.onloadedmetadata = () => {
        const maxH = +(A().videoMaxH || 720), scale = Math.min(1, maxH / v.videoHeight, 1280 / v.videoWidth);
        const w = Math.round(v.videoWidth * scale / 2) * 2, h = Math.round(v.videoHeight * scale / 2) * 2;
        if (!w || !h || !isFinite(v.duration) || v.duration > 600) return finish(file);     // >10 min: leave it
        const c = document.createElement('canvas'); c.width = w; c.height = h; const ctx = c.getContext('2d');
        const stream = c.captureStream(30);
        // keep the audio without playing it out loud
        try {
          const AC = window.AudioContext || window.webkitAudioContext; const ac = new AC();
          const src = ac.createMediaElementSource(v); const dest = ac.createMediaStreamDestination(); src.connect(dest);
          dest.stream.getAudioTracks().forEach(t => stream.addTrack(t)); v.muted = false; v.volume = 1;
        } catch (e) {}
        const mime = ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp9', 'video/webm;codecs=vp8,opus', 'video/webm;codecs=vp8', 'video/webm', 'video/mp4'].find(m => MediaRecorder.isTypeSupported(m));
        const bps = Math.round(Math.min(2400000, Math.max(900000, w * h * 2.6)));
        try { rec = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: bps, audioBitsPerSecond: 96000 }); } catch (e) { return finish(file); }
        const chunks = [];
        rec.ondataavailable = e => { if (e.data && e.data.size) chunks.push(e.data); };
        rec.onstop = () => {
          const type = mime.split(';')[0], ext = type === 'video/mp4' ? 'mp4' : 'webm';
          const blob = new Blob(chunks, { type });
          if (!blob.size || blob.size > file.size * 0.85) return finish(file);   // not worth it → upload the original
          finish(new File([blob], file.name.replace(/\.[^.]+$/, '') + '-web.' + ext, { type }));
        };
        let raf = 0;
        const draw = () => { if (done || v.ended || v.paused && v.currentTime >= v.duration) return; ctx.drawImage(v, 0, 0, w, h); raf = v.requestVideoFrameCallback ? v.requestVideoFrameCallback(draw) : requestAnimationFrame(draw); };
        v.onended = () => { setTimeout(() => { try { rec.state !== 'inactive' && rec.stop(); } catch (e) { finish(file); } }, 150); };
        timer = setInterval(() => { if (cancelRef && cancelRef.cancelled) { try { rec.stop(); } catch (e) {} finish(file); return; } onProgress && onProgress(Math.min(99, Math.round(v.currentTime / v.duration * 100))); }, 250);
        v.play().then(() => { rec.start(1000); draw(); }).catch(() => finish(file));
      };
    });
  }

  function upload(file, subdir, kind, onProgress) {
    return new Promise((resolve, reject) => {
      const fd = new FormData();
      fd.append('file', file); fd.append('csrf', A().csrf); fd.append('subdir', subdir || 'editor'); fd.append('kind', kind || 'image');
      if (kind === 'video') { fd.append('poster', '1'); if (file.__preCompressed) fd.append('pre_compressed', '1'); }
      const xhr = new XMLHttpRequest();
      xhr.open('POST', A().base + '/admin/upload.php');
      xhr.upload.onprogress = e => { if (e.lengthComputable && onProgress) onProgress(Math.round(e.loaded / e.total * 100)); };
      xhr.onload = () => {
        let d = null; try { d = JSON.parse(xhr.responseText); } catch (e) {}
        if (d && d.ok) resolve(d); else reject(new Error((d && d.error) || ('Upload failed (HTTP ' + xhr.status + ')')));
      };
      xhr.onerror = () => reject(new Error('Network error during upload'));
      xhr.send(fd);
    });
  }

  function build(box) {
    if (box.dataset.upDone) return; box.dataset.upDone = '1';
    const multi = box.dataset.multiple === '1', kind = box.dataset.kind || 'image', subdir = box.dataset.subdir || 'editor';
    const max = +(box.dataset.max || (multi ? 30 : 1)), name = box.dataset.name || 'image';
    const coverName = box.dataset.coverName || '';
    let items = [];
    try { const v = box.dataset.value || ''; items = v.startsWith('[') ? JSON.parse(v) : (v ? [v] : []); } catch (e) { items = []; }
    items = items.filter(Boolean);
    let cover = box.dataset.cover || '';
    const accept = kind === 'video' ? 'video/mp4,video/webm,video/quicktime' : 'image/*';

    box.classList.add('up-box');
    box.innerHTML =
      '<div class="up-list"></div>' +
      '<div class="up-drop"><i class="fa-solid ' + (kind === 'video' ? 'fa-film' : 'fa-cloud-arrow-up') + '"></i>' +
      '<b>' + (kind === 'video' ? 'Upload video (MP4 / WebM)' : (multi ? 'Add photos' : 'Upload image')) + '</b>' +
      '<span>drag & drop here, or click to choose' + (multi ? ' — select many at once' : '') + '</span>' +
      (kind === 'image' ? '<button type="button" class="up-gal"><i class="fa-regular fa-images"></i> Choose from Gallery</button>' : '') +
      '<input type="file" accept="' + accept + '" ' + (multi ? 'multiple' : '') + ' hidden></div>' +
      '<div class="up-status"></div><div class="up-hidden"></div>';
    const list = box.querySelector('.up-list'), drop = box.querySelector('.up-drop'), input = box.querySelector('input[type=file]');
    const status = box.querySelector('.up-status'), hidden = box.querySelector('.up-hidden');
    box.__getItems = () => items.slice();
    box.__setItems = arr => { items = (arr || []).filter(Boolean).slice(0, max); if (coverName && !items.includes(cover)) cover = items[0] || ''; sync(); };

    function sync() {
      hidden.innerHTML = '';
      if (multi) {
        items.forEach(p => hidden.insertAdjacentHTML('beforeend', '<input type="hidden" name="' + esc(name) + '" value="' + esc(p) + '">'));
        if (!items.length) hidden.insertAdjacentHTML('beforeend', '<input type="hidden" name="' + esc(name.replace(/\[\]$/, '')) + '_empty" value="1">');
        if (coverName) { if (!items.includes(cover)) cover = items[0] || ''; hidden.insertAdjacentHTML('beforeend', '<input type="hidden" name="' + esc(coverName) + '" value="' + esc(cover) + '">'); }
      } else {
        hidden.insertAdjacentHTML('beforeend', '<input type="hidden" name="' + esc(name) + '" value="' + esc(items[0] || '') + '">');
      }
      list.innerHTML = items.map((p, i) =>
        '<div class="up-it' + (coverName && p === cover ? ' cover' : '') + '" draggable="' + (multi ? 'true' : 'false') + '" data-i="' + i + '">' +
        (kind === 'video' ? '<video src="' + esc(urlOf(p)) + '" muted preload="metadata"></video>' : '<img src="' + esc(urlOf(p)) + '" alt="">') +
        '<div class="up-tools">' +
        (coverName ? '<button type="button" class="up-cover" title="Use as cover / main photo">★</button>' : '') +
        '<button type="button" class="up-rm" title="Remove">✕</button></div>' +
        (coverName && p === cover ? '<span class="up-badge">Cover</span>' : '') +
        (multi ? '<span class="up-num">' + (i + 1) + '</span>' : '') + '</div>').join('');
      drop.style.display = (!multi && items.length) ? 'none' : '';
      if (multi) drop.querySelector('b').textContent = items.length ? 'Add more photos (' + items.length + '/' + max + ')' : 'Add photos';
      box.dispatchEvent(new CustomEvent('change', { bubbles: true }));
    }

    async function addFiles(files) {
      const arr = Array.from(files).slice(0, Math.max(0, multi ? max - items.length : 1));
      if (!arr.length) { status.innerHTML = '<span class="bad">Maximum ' + max + ' files.</span>'; return; }
      box.classList.add('busy');
      let n = 0, fails = [], lastSaved = '';
      for (const f of arr) {
        n++;
        status.innerHTML = '<span class="prog">' + esc(f.name) + ' — preparing…</span>';
        let file = f;
        if (kind === 'image') file = await shrink(f);
        else if (kind === 'video') {
          const cancelRef = { cancelled: false };
          status.innerHTML = '<span class="prog">🎬 Compressing ' + esc(f.name) + ' in your browser (' + Math.round(f.size / 1048576) + ' MB) — 0% <a href="#" class="up-skip">skip, upload original</a></span>';
          status.querySelector('.up-skip')?.addEventListener('click', e => { e.preventDefault(); cancelRef.cancelled = true; });
          file = await compressVideo(f, p => { const el = status.querySelector('.prog'); if (el) el.firstChild.textContent = '🎬 Compressing ' + f.name + ' in your browser (' + Math.round(f.size / 1048576) + ' MB) — ' + p + '% '; }, cancelRef);
          if (file !== f) file.__preCompressed = true;
        }
        try {
          const d = await upload(file, subdir, kind, p => status.innerHTML = '<span class="prog">' + n + '/' + arr.length + ' ' + esc(f.name) + ' — uploading ' + p + '%' + (p >= 100 ? ' · optimising on server…' : '') + '</span>');
          if (d.orig_size && d.size && d.orig_size > d.size * 1.15) lastSaved = ' (' + Math.round(d.orig_size / 1048576 * 10) / 10 + ' MB → ' + Math.round(d.size / 1048576 * 10) / 10 + ' MB)';
          else if (f.size > file.size * 1.15) lastSaved = ' (' + Math.round(f.size / 1048576 * 10) / 10 + ' MB → ' + Math.round(file.size / 1048576 * 10) / 10 + ' MB)';
          else lastSaved = '';
          if (multi) items.push(d.path); else items = [d.path];
          if (coverName && !cover) cover = d.path;
          sync();
          // video → auto-fill the empty poster / background image field next to it (server made a frame with ffmpeg)
          if (d.poster && box.dataset.posterTarget) {
            const tgt = (box.closest('form') || document).querySelector('[data-uploader][data-name="' + box.dataset.posterTarget + '"]');
            if (tgt && tgt.__setItems && !tgt.__getItems().length) tgt.__setItems([d.poster]);
          }
        } catch (e) { fails.push(f.name + ': ' + e.message); }
      }
      box.classList.remove('busy');
      status.innerHTML = fails.length ? '<span class="bad">⚠ ' + esc(fails.join(' · ')) + '</span>' : '<span class="ok">✓ ' + arr.length + ' file(s) uploaded &amp; optimised' + esc(lastSaved) + '</span>';
      setTimeout(() => { if (!fails.length) status.innerHTML = ''; }, 3500);
    }

    drop.addEventListener('click', e => { if (!e.target.closest('.up-gal')) input.click(); });
    input.addEventListener('change', () => { addFiles(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(ev => box.addEventListener(ev, e => { if (e.dataTransfer && e.dataTransfer.types.includes('Files')) { e.preventDefault(); box.classList.add('over'); } }));
    ['dragleave', 'drop'].forEach(ev => box.addEventListener(ev, e => { if (ev === 'drop' && e.dataTransfer && e.dataTransfer.files.length) { e.preventDefault(); addFiles(e.dataTransfer.files); } box.classList.remove('over'); }));
    const gal = box.querySelector('.up-gal');
    if (gal) gal.addEventListener('click', e => { e.stopPropagation(); pickFromGallery(multi ? max - items.length : 1).then(paths => { if (!paths.length) return; if (multi) items = items.concat(paths).slice(0, max); else items = [paths[0]]; if (coverName && !cover) cover = items[0]; sync(); }); });
    list.addEventListener('click', e => {
      const it = e.target.closest('.up-it'); if (!it) return; const i = +it.dataset.i;
      if (e.target.closest('.up-rm')) { items.splice(i, 1); sync(); }
      else if (e.target.closest('.up-cover')) { cover = items[i]; sync(); }
      else if (kind === 'image') { const w = window.open(urlOf(items[i]), '_blank'); if (w) w.opener = null; }
    });
    // reorder by drag
    let drag = null;
    list.addEventListener('dragstart', e => { drag = e.target.closest('.up-it'); if (drag) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', drag.dataset.i); } catch (x) {} drag.classList.add('dragging'); } });
    list.addEventListener('dragover', e => { if (!drag) return; e.preventDefault(); const over = e.target.closest('.up-it'); if (over && over !== drag) { const r = over.getBoundingClientRect(); list.insertBefore(drag, (e.clientX - r.left) > r.width / 2 ? over.nextSibling : over); } });
    list.addEventListener('dragend', () => { if (!drag) return; drag.classList.remove('dragging'); drag = null; items = [...list.querySelectorAll('.up-it')].map(el => items[+el.dataset.i]); sync(); });
    sync();
    box.getValue = () => multi ? items.slice() : (items[0] || '');
    box.setValue = v => { items = Array.isArray(v) ? v.slice() : (v ? [v] : []); sync(); };
  }

  /* ---- gallery picker modal ---- */
  let galCache = null;
  function pickFromGallery(maxPick) {
    return new Promise(resolve => {
      const m = document.createElement('div'); m.className = 'up-modal';
      m.innerHTML = '<div class="up-mbox"><div class="up-mhead"><b><i class="fa-regular fa-images"></i> Choose from Gallery</b><input class="up-search" placeholder="Search caption / category…"><button type="button" class="up-mclose">&times;</button></div><div class="up-mgrid"><p style="padding:20px;color:#888">Loading…</p></div><div class="up-mfoot"><span class="up-mcount"></span><button type="button" class="up-muse">Use selected</button></div></div>';
      document.body.appendChild(m);
      const grid = m.querySelector('.up-mgrid'), count = m.querySelector('.up-mcount'); const picked = [];
      const close = v => { m.remove(); resolve(v); };
      m.querySelector('.up-mclose').onclick = () => close([]);
      m.addEventListener('click', e => { if (e.target === m) close([]); });
      m.querySelector('.up-muse').onclick = () => close(picked.slice());
      const render = q => {
        const rows = (galCache || []).filter(g => !q || (g.title + ' ' + g.category).toLowerCase().includes(q));
        grid.innerHTML = rows.length ? rows.map(g => '<div class="up-mit' + (picked.includes(g.image) ? ' on' : '') + '" data-p="' + esc(g.image) + '"><img src="' + esc(urlOf(g.image)) + '" loading="lazy"><span>' + esc(g.title || g.category) + '</span></div>').join('') : '<p style="padding:20px;color:#888">No images in the Gallery yet — upload some under Admin → Gallery.</p>';
      };
      m.querySelector('.up-search').addEventListener('input', e => render(e.target.value.toLowerCase()));
      grid.addEventListener('click', e => {
        const it = e.target.closest('.up-mit'); if (!it) return;
        const p = it.dataset.p, i = picked.indexOf(p);
        if (i >= 0) picked.splice(i, 1); else { if (picked.length >= maxPick) { if (maxPick === 1) picked.length = 0; else return; } picked.push(p); }
        it.classList.toggle('on', picked.includes(p)); count.textContent = picked.length ? picked.length + ' selected' : '';
        if (maxPick === 1 && picked.length) close(picked.slice());
      });
      const fd = new FormData(); fd.append('csrf', A().csrf); fd.append('action', 'gallery_list');
      (galCache ? Promise.resolve({ ok: true, images: galCache }) : fetch(A().base + '/admin/ajax.php', { method: 'POST', body: fd }).then(r => r.json()))
        .then(d => { galCache = d.images || []; render(''); });
    });
  }

  function init(root) { (root || document).querySelectorAll('[data-uploader]').forEach(build); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init()); else init();
  window.ECH_UPLOADER_INIT = init;
  window.ECH_UPLOAD = { upload, shrink, pickFromGallery };
})();
