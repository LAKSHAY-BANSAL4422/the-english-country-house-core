/* Branded QR codes with the hotel logo in the centre (uses vendor/qrcode.js, error-correction H). */
(function () {
  const logoCache = {};
  function loadImg(src) {
    if (!src) return Promise.resolve(null);
    if (!logoCache[src]) logoCache[src] = new Promise(res => {
      const im = new Image();
      im.onload = () => res(im); im.onerror = () => res(null); im.src = src;
    });
    return logoCache[src];
  }
  function rr(ctx, x, y, w, h, r) {
    r = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
  }
  function isFinder(r, c, n) {
    return (r < 7 && c < 7) || (r < 7 && c >= n - 7) || (r >= n - 7 && c < 7);
  }
  /**
   * draw(canvas, text, {size, color, bg, logo, label}) → Promise<canvas>
   */
  async function draw(canvas, text, o) {
    const given = {};
    Object.entries(o || {}).forEach(([k, v]) => { if (v !== undefined && v !== null) given[k] = v; });   // ignore unset options
    o = Object.assign({ size: 260, color: (window.ECH_QR && ECH_QR.color) || '#163a2c', bg: '#ffffff',
                        logo: (window.ECH_QR && ECH_QR.logo) || '', label: '', hiDpi: true, round: 0.3, eye: 0.4 }, given);   // eye 0.4 keeps finder patterns readable by every scanner
    const qr = qrcode(0, 'H');
    qr.addData(text, 'Byte'); qr.make();
    const n = qr.getModuleCount();
    const dpr = o.hiDpi ? Math.max(1, Math.min(3, window.devicePixelRatio || 1)) : 1;
    const S = o.size, labelH = o.label ? Math.round(S * 0.12) : 0;
    canvas.width = Math.round(S * dpr); canvas.height = Math.round((S + labelH) * dpr);
    canvas.style.width = S + 'px'; canvas.style.height = (S + labelH) + 'px';
    const ctx = canvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    // card
    ctx.fillStyle = o.bg; rr(ctx, 0, 0, S, S + labelH, S * 0.06); ctx.fill();
    const quiet = 4, cell = S / (n + quiet * 2), off = quiet * cell;
    // logo window (in modules) — keep ~22% of width clear
    const lw = Math.round(n * 0.24) | 1, l0 = Math.floor((n - lw) / 2), l1 = l0 + lw;
    ctx.fillStyle = o.color;
    for (let r = 0; r < n; r++) for (let c = 0; c < n; c++) {
      if (!qr.isDark(r, c) || isFinder(r, c, n)) continue;
      if (o.logo && r >= l0 && r < l1 && c >= l0 && c < l1) continue;
      rr(ctx, off + c * cell - 0.25, off + r * cell - 0.25, cell + 0.5, cell + 0.5, cell * o.round); ctx.fill();
    }
    // finder eyes
    [[0, 0], [0, n - 7], [n - 7, 0]].forEach(([r, c]) => {
      const x = off + c * cell, y = off + r * cell;
      ctx.fillStyle = o.color; rr(ctx, x, y, 7 * cell, 7 * cell, cell * o.eye * 1.6); ctx.fill();
      ctx.fillStyle = o.bg; rr(ctx, x + cell, y + cell, 5 * cell, 5 * cell, cell * o.eye); ctx.fill();
      ctx.fillStyle = o.color; rr(ctx, x + 2 * cell, y + 2 * cell, 3 * cell, 3 * cell, cell * o.eye * 0.6); ctx.fill();
    });
    // logo
    const img = await loadImg(o.logo);
    if (img) {
      const box = lw * cell, bx = off + l0 * cell, by = off + l0 * cell;
      ctx.fillStyle = o.bg; rr(ctx, bx - cell * 0.3, by - cell * 0.3, box + cell * 0.6, box + cell * 0.6, cell * 1.2); ctx.fill();
      const pad = box * 0.08, maxW = box - pad * 2, maxH = box - pad * 2;
      const k = Math.min(maxW / img.width, maxH / img.height);
      const w = img.width * k, h = img.height * k;
      ctx.drawImage(img, bx + (box - w) / 2, by + (box - h) / 2, w, h);
    }
    if (o.label) {
      ctx.fillStyle = o.color;
      ctx.font = '600 ' + Math.round(S * 0.05) + 'px Outfit, Arial, sans-serif';
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      if ('letterSpacing' in ctx) ctx.letterSpacing = Math.round(S * 0.008) + 'px';
      ctx.fillText(o.label, S / 2, S + labelH * 0.35);
    }
    return canvas;
  }
  async function toPng(text, opts) {
    const c = document.createElement('canvas');
    await draw(c, text, Object.assign({ size: 1024, hiDpi: false }, opts || {}));
    return c.toDataURL('image/png');
  }
  function renderAll(root) {
    (root || document).querySelectorAll('canvas[data-qr]').forEach(cv => {
      draw(cv, cv.dataset.qr, { size: +cv.dataset.qrSize || 220, label: cv.dataset.qrLabel || '',
        color: cv.dataset.qrColor || undefined, logo: cv.dataset.qrLogo || undefined });
    });
  }
  window.ECHQR = { draw, toPng, renderAll };
  document.addEventListener('DOMContentLoaded', () => renderAll());
})();
