/**
 * ECH Visual Editor — dependency-free WYSIWYG for admin panel.
 * Upgrades every <textarea class="wysiwyg"> into a rich editor with toolbar,
 * inline image upload, colors, lists, links and an HTML source toggle.
 * The original textarea stays in the form and is kept in sync automatically.
 */
(function () {
  'use strict';

  const UPLOAD_URL = (window.ADMIN ? ADMIN.base : '') + '/admin/upload.php';

  const BTNS = [
    { cmd: 'undo', icon: 'fa-rotate-left', tip: 'Undo' },
    { cmd: 'redo', icon: 'fa-rotate-right', tip: 'Redo' },
    { sep: 1 },
    { block: 'H2', label: 'H2', tip: 'Heading' },
    { block: 'H3', label: 'H3', tip: 'Sub-heading' },
    { block: 'P', label: '¶', tip: 'Paragraph' },
    { sep: 1 },
    { cmd: 'bold', icon: 'fa-bold', tip: 'Bold' },
    { cmd: 'italic', icon: 'fa-italic', tip: 'Italic' },
    { cmd: 'underline', icon: 'fa-underline', tip: 'Underline' },
    { cmd: 'strikeThrough', icon: 'fa-strikethrough', tip: 'Strike' },
    { sep: 1 },
    { color: 'foreColor', icon: 'fa-font', tip: 'Text color' },
    { color: 'hiliteColor', icon: 'fa-highlighter', tip: 'Highlight' },
    { sep: 1 },
    { cmd: 'insertUnorderedList', icon: 'fa-list-ul', tip: 'Bullet list' },
    { cmd: 'insertOrderedList', icon: 'fa-list-ol', tip: 'Numbered list' },
    { block: 'BLOCKQUOTE', icon: 'fa-quote-left', tip: 'Quote' },
    { cmd: 'insertHorizontalRule', icon: 'fa-minus', tip: 'Divider line' },
    { sep: 1 },
    { cmd: 'justifyLeft', icon: 'fa-align-left', tip: 'Align left' },
    { cmd: 'justifyCenter', icon: 'fa-align-center', tip: 'Center' },
    { cmd: 'justifyRight', icon: 'fa-align-right', tip: 'Align right' },
    { sep: 1 },
    { act: 'link', icon: 'fa-link', tip: 'Insert link' },
    { cmd: 'unlink', icon: 'fa-link-slash', tip: 'Remove link' },
    { act: 'image', icon: 'fa-image', tip: 'Upload / insert image' },
    { act: 'button', icon: 'fa-square-plus', tip: 'Insert button' },
    { sep: 1 },
    { cmd: 'removeFormat', icon: 'fa-eraser', tip: 'Clear formatting' },
    { act: 'source', icon: 'fa-code', tip: 'HTML source view' },
    { act: 'expand', icon: 'fa-up-right-and-down-left-from-center', tip: 'Expand' },
  ];

  function build(ta) {
    if (ta.dataset.wysiwygDone) return;
    ta.dataset.wysiwygDone = '1';

    const wrap = document.createElement('div');
    wrap.className = 'we-wrap';
    const bar = document.createElement('div');
    bar.className = 'we-bar';
    const area = document.createElement('div');
    area.className = 'we-area';
    area.contentEditable = 'true';
    area.innerHTML = ta.value || '<p><br></p>';
    if (ta.dataset.minh) area.style.minHeight = ta.dataset.minh;

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar);
    wrap.appendChild(area);
    wrap.appendChild(ta);
    ta.classList.add('we-src');
    ta.style.display = 'none';

    const isBlank = h => !/<(img|iframe|video|table|ul|ol|h[1-6]|a\s)/i.test(h) && h.replace(/<br\s*\/?>|<\/?p[^>]*>|<\/?div[^>]*>|<\/?span[^>]*>|&nbsp;|\u00a0/g, '').replace(/<[^>]+>/g, '').trim() === '';
    const sync = () => { if (!wrap.classList.contains('we-source-mode')) ta.value = isBlank(area.innerHTML) ? '' : area.innerHTML; };
    area.addEventListener('input', sync);
    area.addEventListener('blur', sync);
    const form = ta.closest('form');
    if (form) form.addEventListener('submit', sync);

    const exec = (cmd, val) => { area.focus(); document.execCommand(cmd, false, val || null); sync(); };

    BTNS.forEach(b => {
      if (b.sep) { const s = document.createElement('span'); s.className = 'we-sep'; bar.appendChild(s); return; }
      if (b.color) {
        const lab = document.createElement('label');
        lab.className = 'we-btn'; lab.title = b.tip;
        lab.innerHTML = '<i class="fa-solid ' + b.icon + '"></i>';
        const inp = document.createElement('input');
        inp.type = 'color'; inp.className = 'we-color';
        inp.addEventListener('input', () => exec(b.color, inp.value));
        lab.appendChild(inp);
        bar.appendChild(lab);
        return;
      }
      const btn = document.createElement('button');
      btn.type = 'button'; btn.className = 'we-btn'; btn.title = b.tip;
      btn.innerHTML = b.icon ? '<i class="fa-solid ' + b.icon + '"></i>' : '<b>' + b.label + '</b>';
      btn.addEventListener('click', () => {
        if (b.cmd) return exec(b.cmd);
        if (b.block) return exec('formatBlock', '<' + b.block + '>');
        if (b.act === 'link') {
          const url = prompt('Link URL (https://… or booking.php):');
          if (url) exec('createLink', url);
        }
        if (b.act === 'button') {
          const label = prompt('Button text:', 'Book Your Stay');
          if (!label) return;
          const url = prompt('Button link:', 'booking.php') || '#';
          area.focus();
          document.execCommand('insertHTML', false,
            '<a href="' + url + '" class="btn btn-primary" style="display:inline-block;background:#c9974b;color:#fff;padding:10px 24px;border-radius:5px;text-decoration:none">' + label + '</a>&nbsp;');
          sync();
        }
        if (b.act === 'image') pickImage(area, sync);
        if (b.act === 'source') toggleSource(wrap, area, ta, btn);
        if (b.act === 'expand') wrap.classList.toggle('we-full');
      });
      bar.appendChild(btn);
    });

    // drag-drop & paste image upload straight into the editor
    area.addEventListener('drop', e => {
      const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f && f.type.startsWith('image/')) { e.preventDefault(); uploadImage(f, area, sync); }
    });
    area.addEventListener('paste', e => {
      const items = (e.clipboardData || {}).items || [];
      for (const it of items) {
        if (it.type && it.type.startsWith('image/')) { e.preventDefault(); uploadImage(it.getAsFile(), area, sync); return; }
      }
    });
    // click an image inside the editor to resize/align/remove
    area.addEventListener('click', e => {
      if (e.target.tagName === 'IMG') imageTools(e.target, sync);
    });
  }

  function toggleSource(wrap, area, ta, btn) {
    const on = wrap.classList.toggle('we-source-mode');
    if (on) { ta.value = area.innerHTML; ta.style.display = 'block'; area.style.display = 'none'; btn.classList.add('on'); }
    else { area.innerHTML = ta.value; ta.style.display = 'none'; area.style.display = 'block'; btn.classList.remove('on'); }
  }

  function pickImage(area, sync) {
    const choice = prompt('Paste an image URL, or leave EMPTY to upload a file:');
    if (choice === null) return;
    if (choice.trim() !== '') {
      area.focus();
      document.execCommand('insertHTML', false, '<img src="' + choice.trim() + '" style="max-width:100%">');
      sync(); return;
    }
    const fi = document.createElement('input');
    fi.type = 'file'; fi.accept = 'image/*';
    fi.onchange = () => { if (fi.files[0]) uploadImage(fi.files[0], area, sync); };
    fi.click();
  }

  function uploadImage(file, area, sync) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('csrf', window.ADMIN ? ADMIN.csrf : '');
    const ph = '⏳ uploading…';
    area.focus();
    document.execCommand('insertHTML', false, '<span class="we-uploading">' + ph + '</span>');
    fetch(UPLOAD_URL, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        area.querySelectorAll('.we-uploading').forEach(x => x.remove());
        if (d.ok) {
          document.execCommand('insertHTML', false, '<img src="' + d.url + '" style="max-width:100%;border-radius:8px">');
        } else alert(d.error || 'Upload failed');
        sync();
      })
      .catch(() => { area.querySelectorAll('.we-uploading').forEach(x => x.remove()); alert('Upload failed'); });
  }

  function imageTools(img, sync) {
    const act = prompt('Image: type width in % (e.g. 50), or "left"/"center"/"right" to align, or "remove":', '100');
    if (act === null) return;
    const a = act.trim().toLowerCase();
    if (a === 'remove') { img.remove(); }
    else if (a === 'left') { img.style.float = 'left'; img.style.margin = '0 14px 10px 0'; }
    else if (a === 'right') { img.style.float = 'right'; img.style.margin = '0 0 10px 14px'; }
    else if (a === 'center') { img.style.float = 'none'; img.style.display = 'block'; img.style.margin = '10px auto'; }
    else if (/^\d+$/.test(a)) { img.style.width = a + '%'; img.style.maxWidth = '100%'; }
    sync();
  }

  function init() { document.querySelectorAll('textarea.wysiwyg').forEach(build); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
  window.ECH_EDITOR_INIT = init; // for dynamically added textareas
})();
