/**
 * Repeatable "cards" editor used by the Feature Cards section (admin builder + live editor).
 *   <div data-items-editor data-name="s_items[2]" data-value='[{"icon":"fa-solid fa-star","title":"…","text":"…"}]'></div>
 * Keeps a hidden input (JSON) in sync. Rows can be added, removed, re-ordered; each row may have an icon,
 * a photo (uploader), a title, text and an optional link.
 */
(function () {
  'use strict';
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const ICONS = ['fa-solid fa-star','fa-solid fa-heart','fa-solid fa-leaf','fa-solid fa-mug-hot','fa-solid fa-bed','fa-solid fa-water-ladder','fa-solid fa-spa','fa-solid fa-mountain-sun','fa-solid fa-utensils','fa-solid fa-wifi','fa-solid fa-car','fa-solid fa-square-parking','fa-solid fa-paw','fa-solid fa-fire','fa-solid fa-tree','fa-solid fa-sun','fa-regular fa-moon','fa-solid fa-bullseye','fa-regular fa-circle-dot','fa-regular fa-building','fa-solid fa-shield-halved','fa-solid fa-phone','fa-solid fa-location-dot','fa-solid fa-camera','fa-solid fa-dumbbell','fa-solid fa-person-swimming','fa-solid fa-bicycle','fa-solid fa-book','fa-solid fa-charging-station','fa-solid fa-champagne-glasses'];

  function build(box) {
    if (box.dataset.itemsDone) return; box.dataset.itemsDone = '1';
    let items = [];
    try { items = JSON.parse(box.dataset.value || '[]') || []; } catch (e) { items = []; }
    const name = box.dataset.name || 'items';
    box.classList.add('ie-box');
    box.innerHTML = '<div class="ie-list"></div><button type="button" class="btn sm ghost ie-add"><i class="fa-solid fa-plus"></i> Add card</button><input type="hidden" name="' + esc(name) + '" value="">';
    const list = box.querySelector('.ie-list'), hidden = box.querySelector('input[type=hidden]');
    const dl = 'ie-icons-' + Math.random().toString(36).slice(2, 7);
    box.insertAdjacentHTML('beforeend', '<datalist id="' + dl + '">' + ICONS.map(i => '<option value="' + i + '">').join('') + '</datalist>');

    function read() {
      items = [...list.querySelectorAll('.ie-row')].map(r => ({
        icon: r.querySelector('[data-f=icon]').value.trim(), title: r.querySelector('[data-f=title]').value.trim(),
        text: r.querySelector('[data-f=text]').value.trim(), url: r.querySelector('[data-f=url]').value.trim(),
        link_label: r.querySelector('[data-f=link_label]').value.trim(), image: (r.querySelector('[data-uploader]') && r.querySelector('[data-uploader]').getValue) ? r.querySelector('[data-uploader]').getValue() : (r.dataset.image || '')
      }));
      hidden.value = JSON.stringify(items);
    }
    function row(it, i) {
      return '<div class="ie-row" draggable="false" data-image="' + esc(it.image) + '">' +
        '<div class="ie-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></div>' +
        '<div class="ie-main">' +
          '<div class="frow3">' +
            '<div><label>Icon <small class="hint">(Font Awesome class)</small></label><div class="ie-icon"><i class="' + esc(it.icon || 'fa-solid fa-star') + '"></i><input class="input" data-f="icon" list="' + dl + '" value="' + esc(it.icon) + '" placeholder="fa-solid fa-star"></div></div>' +
            '<div style="grid-column:span 2"><label>Title</label><input class="input" data-f="title" value="' + esc(it.title) + '"></div>' +
          '</div>' +
          '<label>Text</label><textarea class="input" data-f="text" rows="2">' + esc(it.text) + '</textarea>' +
          '<div class="frow3">' +
            '<div><label>Link (optional)</label><input class="input" data-f="url" value="' + esc(it.url) + '" placeholder="rooms.php" list="linklist"></div>' +
            '<div><label>Link text</label><input class="input" data-f="link_label" value="' + esc(it.link_label) + '" placeholder="Learn more"></div>' +
            '<div><label>Photo (optional — replaces icon)</label><div data-uploader data-name="ie_img_' + i + '" data-subdir="sections" data-value="' + esc(it.image) + '"></div></div>' +
          '</div>' +
        '</div>' +
        '<button type="button" class="ie-rm" title="Remove card">&times;</button></div>';
    }
    function render() {
      list.innerHTML = items.map(row).join('');
      if (window.ECH_UPLOADER_INIT) ECH_UPLOADER_INIT(list);
      read();
    }
    box.querySelector('.ie-add').onclick = () => { read(); items.push({ icon: 'fa-solid fa-star', title: '', text: '', url: '', link_label: '', image: '' }); render(); };
    list.addEventListener('click', e => { const rm = e.target.closest('.ie-rm'); if (rm) { read(); const r = rm.closest('.ie-row'); items.splice([...list.children].indexOf(r), 1); render(); } });
    list.addEventListener('input', e => { read(); const ic = e.target.closest('.ie-icon'); if (ic && e.target.dataset.f === 'icon') ic.querySelector('i').className = e.target.value || 'fa-solid fa-star'; });
    list.addEventListener('change', read);
    // reorder with handle
    let drag = null;
    list.addEventListener('mousedown', e => { const h = e.target.closest('.ie-handle'); if (h) h.closest('.ie-row').setAttribute('draggable', 'true'); });
    list.addEventListener('dragstart', e => { drag = e.target.closest('.ie-row'); if (drag) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'x'); } catch (x) {} drag.classList.add('dragging'); } });
    list.addEventListener('dragover', e => { if (!drag) return; e.preventDefault(); const over = e.target.closest('.ie-row'); if (over && over !== drag) { const r = over.getBoundingClientRect(); list.insertBefore(drag, (e.clientY - r.top) > r.height / 2 ? over.nextSibling : over); } });
    list.addEventListener('dragend', () => { if (drag) { drag.classList.remove('dragging'); drag.setAttribute('draggable', 'false'); drag = null; read(); } });
    render();
    const form = box.closest('form'); if (form) form.addEventListener('submit', read);
  }
  function init(root) { (root || document).querySelectorAll('[data-items-editor]').forEach(build); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init()); else init();
  window.ECH_ITEMS_INIT = init;
})();
