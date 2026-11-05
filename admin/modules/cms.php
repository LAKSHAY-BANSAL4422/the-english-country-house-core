<?php
/**
 * Pages & Section Builder — every public page (Home, About, Rooms, Amenities, Contact, FAQ, Blog, Gallery, Booking,
 * policy / location / custom pages) has: banner (image, heading, tagline), intro content, SEO and drag-and-drop
 * sections that can be added, edited, hidden, duplicated, re-ordered and deleted. Also opens the live editor.
 */
require_once ROOT_PATH.'/admin/section_form.php';
require_once ROOT_PATH.'/includes/page_render.php';
$TYPES = section_types();
$flash = ''; $err = '';
$pageUrl = function (array $p): string {
    $map = ['home'=>'', 'about'=>'about.php','rooms'=>'rooms.php','amenities'=>'amenities.php','contact'=>'contact.php','faqs'=>'faqs.php','blog'=>'blog.php','gallery'=>'gallery.php','booking'=>'booking.php','menu'=>'menu.php'];
    return $p['page_type'] === 'system' && isset($map[$p['slug']]) ? ($map[$p['slug']] === '' ? base_url('') : u($map[$p['slug']])) : u('page.php?slug='.$p['slug']);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
    case 'save':
        $id = (int)post('id');
        $data = ['title'=>mb_substr(trim(post('title')),0,190), 'content'=>html_is_empty($_POST['content'] ?? '') ? '' : $_POST['content'],
          'show_in_menu'=>(int)(bool)post('show_in_menu'),'menu_order'=>(int)post('menu_order'),'active'=>(int)(bool)post('active'),
          'seo_title'=>post('seo_title'),'seo_desc'=>post('seo_desc'),'seo_keywords'=>post('seo_keywords'),'schema_json'=>post('schema_json')] + banner_from_post();
        if ($data['title'] === '') { $err = 'Title is required.'; break; }
        $sys = $id ? q_val("SELECT page_type FROM pages WHERE id=?", [$id]) : '';
        if ($sys !== 'system') {
            $data['slug'] = slugify(post('slug') ?: post('title')) ?: 'page-'.time();
            $data['page_type'] = in_array(post('page_type'), ['custom','policy','location']) ? post('page_type') : 'custom';
            if (q_val("SELECT id FROM pages WHERE slug=? AND id<>?", [$data['slug'], $id])) { $err = 'That slug is already used by another page.'; break; }
        }
        if ($id) { db_update('pages', $data, 'id=?', [$id]); $flash = 'Page saved.'; }
        else { $data['sections'] = '[]'; $id = db_insert('pages', $data); $flash = 'Page created — now add sections.'; }
        $_GET['edit'] = (string)$id;
        break;

    case 'save_sections':
        $id = (int)post('id');
        $existing = json_decode(q_val("SELECT sections FROM pages WHERE id=?", [$id], '[]'), true) ?: [];
        $order = json_decode(post('order', '[]'), true);
        if (!is_array($order) || !$order) $order = array_keys($existing);
        $out = [];
        foreach ($order as $origIdx) {
            $origIdx = (int)$origIdx;
            if (!isset($existing[$origIdx])) continue;
            $out[] = section_from_post($existing[$origIdx], $origIdx);
        }
        // instant tools (applied after the field values are saved, so nothing typed is lost)
        $op = post('op');
        if ($op !== '') {
            [$what, $pos] = array_pad(explode(':', $op, 2), 2, '');
            $pos = array_search((int)$pos, array_map('intval', $order), true);      // position in the new order
            if ($pos !== false && isset($out[$pos])) {
                if ($what === 'up' && $pos > 0) { [$out[$pos-1], $out[$pos]] = [$out[$pos], $out[$pos-1]]; }
                if ($what === 'down' && $pos < count($out)-1) { [$out[$pos+1], $out[$pos]] = [$out[$pos], $out[$pos+1]]; }
                if ($what === 'dup') { $copy = $out[$pos]; array_splice($out, $pos+1, 0, [$copy]); }
                if ($what === 'del') { array_splice($out, $pos, 1); }
                if ($what === 'toggle') { $out[$pos]['visible'] = empty($out[$pos]['visible']) ? 1 : 0; }
            }
            $labels = ['up'=>'Section moved up.','down'=>'Section moved down.','dup'=>'Section duplicated.','del'=>'Section deleted.','toggle'=>'Visibility changed.'];
            $flash = ($labels[$what] ?? 'Done.').' All sections saved.';
        } else $flash = '✅ All sections saved — the page is updated.';
        if (post('add_type') !== '' && isset($TYPES[post('add_type')])) {
            $new = section_blank(post('add_type'));
            $at = post('add_at') === 'top' ? 0 : count($out);
            array_splice($out, $at, 0, [$new]);
            $flash = '“'.$TYPES[post('add_type')]['label'].'” section added'.(post('add_at') === 'top' ? ' at the top' : '').' — fill it in and press Save.';
            $_GET['open'] = (string)$at;
        }
        db_update('pages', ['sections'=>json_encode(array_values($out), JSON_UNESCAPED_UNICODE)], 'id=?', [$id]);
        $_GET['builder'] = (string)$id;
        break;

    case 'delete':
        $t = q_val("SELECT page_type FROM pages WHERE id=?", [(int)post('id')]);
        if ($t !== 'system') { db_delete('pages','id=?',[(int)post('id')]); $flash = 'Page deleted.'; } else $err = 'System pages cannot be deleted — hide them instead.';
        break;
    case 'toggle_page':
        q("UPDATE pages SET active=1-active WHERE id=? AND slug<>'home'", [(int)post('id')]); $flash = 'Page visibility changed.';
        break;
    case 'reset_sections':
        require_once ROOT_PATH.'/install/page_defaults.php';
        $p = q_row("SELECT * FROM pages WHERE id=?", [(int)post('id')]);
        $defs = page_default_definitions();
        if ($p && isset($defs[$p['slug']])) { db_update('pages', ['sections'=>json_encode($defs[$p['slug']]['sections'], JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$p['id']]); $flash = 'Default layout restored.'; }
        elseif ($p && $p['page_type'] === 'location') { db_update('pages', ['sections'=>json_encode(location_default_sections($p['title']), JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$p['id']]); $flash = 'Default layout restored.'; }
        $_GET['builder'] = (string)post('id');
        break;
    }
}
$pages = q_all("SELECT * FROM pages ORDER BY FIELD(page_type,'system','custom','location','policy'), menu_order, id");
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>

<?php /* ============================== SECTION BUILDER ============================== */
if (get('builder')):
    $pg = q_row("SELECT * FROM pages WHERE id=?", [(int)get('builder')]);
    if (!$pg) { echo '<div class="alert err">Page not found</div>'; return; }
    $sections = json_decode($pg['sections'] ?: '[]', true) ?: [];
    $openIdx = get('open') !== '' ? (int)get('open') : -1;
    $live = $pageUrl($pg).(str_contains($pageUrl($pg), '?') ? '&' : '?').'edit=1';
    section_datalists(); ?>
<div class="sb-top">
  <div><a href="?m=cms">← All pages</a> &nbsp;·&nbsp; <b style="font-size:16px"><?= e($pg['title']) ?></b> <span class="badge <?= $pg['page_type']==='system'?'blue':'gray' ?>"><?= e($pg['page_type']) ?></span></div>
  <div class="sb-top-btns">
    <a class="btn sm ghost" href="?m=cms&edit=<?= $pg['id'] ?>"><i class="fa-solid fa-image"></i> Banner, intro &amp; SEO</a>
    <a class="btn sm ghost" target="_blank" href="<?= e($pageUrl($pg)) ?>"><i class="fa-solid fa-eye"></i> Preview</a>
    <a class="btn sm sec" target="_blank" href="<?= e($live) ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> Live editor (edit on the page)</a>
  </div>
</div>
<div class="tip">Each block below is one section of the page. <b>Type directly into the fields</b>, upload images, pick colours — then press <b>Save All Sections</b> (top or bottom). The 👁 ▲ ▼ ⧉ 🗑 buttons act instantly and also save everything you typed. Drag the <i class="fa-solid fa-grip-vertical"></i> handle to reorder.</div>

<form method="post" id="builderForm" enctype="multipart/form-data"><?= csrf_field() ?>
  <input type="hidden" name="do" value="save_sections"><input type="hidden" name="id" value="<?= $pg['id'] ?>">
  <input type="hidden" name="order" id="orderField" value="">
  <input type="hidden" name="add_type" id="addTypeField" value=""><input type="hidden" name="add_at" id="addAtField" value="">

  <div class="sb-bar">
    <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save All Sections</button>
    <button class="btn ghost" type="button" data-add-open="top"><i class="fa-solid fa-plus"></i> Add section at top</button>
    <span class="hint" style="margin-left:auto"><?= count($sections) ?> section(s)</span>
    <button class="btn sm ghost" type="button" id="sbExpand">Expand all</button>
    <button class="btn sm ghost" type="button" id="sbCollapse">Collapse all</button>
  </div>

  <div class="sb-list" id="sbList">
    <?php if (!$sections): ?><div class="sb-empty">This page has no sections yet — add your first one below.</div><?php endif; ?>
    <?php foreach ($sections as $i => $s): $t = $TYPES[$s['type']] ?? ['label'=>$s['type'],'icon'=>'fa-cube','desc'=>'']; $n = fn($f) => "s_{$f}[{$i}]"; ?>
    <div class="sb-item <?= empty($s['visible']) ? 'hidden-sec' : '' ?> <?= $openIdx === $i || $openIdx === -1 ? 'open' : '' ?>" data-idx="<?= $i ?>" id="sbi<?= $i ?>">
      <div class="sb-head">
        <span class="drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
        <span class="sb-num"><?= $i+1 ?></span>
        <?php if (!empty($s['image'])): ?><img class="sb-thumb" src="<?= base_url($s['image']) ?>" alt=""><?php else: ?><span class="sb-ico"><i class="fa-solid <?= e($t['icon']) ?>"></i></span><?php endif; ?>
        <div class="sb-title" onclick="this.closest('.sb-item').classList.toggle('open')">
          <span class="sb-type"><?= e($t['label']) ?></span>
          <b><?= e(mb_strimwidth($s['title'] ?: ($s['subtitle'] ?? ''), 0, 60, '…')) ?: '<span class="hint">(no heading)</span>' ?></b>
          <?php if (empty($s['visible'])): ?><span class="badge red">hidden</span><?php endif; ?>
        </div>
        <div class="sb-tools">
          <input type="hidden" name="<?= e($n('visible')) ?>" value="<?= empty($s['visible']) ? 0 : 1 ?>">
          <button type="submit" class="tb" name="op" value="toggle:<?= $i ?>" title="<?= empty($s['visible']) ? 'Show on website' : 'Hide from website (keeps content)' ?>"><?= empty($s['visible']) ? '🚫' : '👁' ?></button>
          <button type="submit" class="tb" name="op" value="up:<?= $i ?>" title="Move up">▲</button>
          <button type="submit" class="tb" name="op" value="down:<?= $i ?>" title="Move down">▼</button>
          <button type="submit" class="tb" name="op" value="dup:<?= $i ?>" title="Duplicate">⧉</button>
          <button type="submit" class="tb danger" name="op" value="del:<?= $i ?>" title="Delete section" onclick="return confirm('Delete this section?')">🗑</button>
          <button type="button" class="tb edit" onclick="this.closest('.sb-item').classList.toggle('open')" title="Edit">✎</button>
        </div>
      </div>
      <div class="sb-body">
        <?php section_fields_html($s, $n, 'b'.$i); ?>
        <div class="sb-body-foot"><button class="btn sm" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button> <span class="hint">saves every section on this page</span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="sb-bar" style="margin-top:14px">
    <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save All Sections</button>
    <button class="btn sec" type="button" data-add-open="bottom"><i class="fa-solid fa-plus"></i> Add section</button>
    <?php if ($pg['page_type'] === 'system' || $pg['page_type'] === 'location'): ?>
    <button class="btn sm ghost" type="submit" name="do" value="reset_sections" style="margin-left:auto" onclick="return confirm('Replace all sections of this page with the default layout?')">↺ Restore default layout</button>
    <?php endif; ?>
  </div>
</form>

<div class="sb-picker" id="sbPicker" hidden>
  <div class="sb-picker-box">
    <div class="sb-picker-head"><b>Add a section</b><span class="hint">Pick a block type — you can edit everything afterwards</span><button type="button" class="mclose" data-add-close>&times;</button></div>
    <div class="sb-picker-grid">
      <?php foreach ($TYPES as $k => $t): ?>
      <button type="button" class="sb-pick" data-type="<?= e($k) ?>"><i class="fa-solid <?= e($t['icon']) ?>"></i><b><?= e($t['label']) ?></b><small><?= e($t['desc']) ?></small></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('builderForm'), list = document.getElementById('sbList');
  const order = () => document.getElementById('orderField').value = JSON.stringify([...list.querySelectorAll('.sb-item')].map(el => +el.dataset.idx));
  order(); form.addEventListener('submit', order);
  // drag by the handle only (so typing / selecting text inside the fields keeps working)
  let drag = null;
  list.querySelectorAll('.sb-item').forEach(item => {
    const h = item.querySelector('.drag-handle');
    h.addEventListener('mousedown', () => item.setAttribute('draggable', 'true'));
    h.addEventListener('mouseup', () => item.removeAttribute('draggable'));
    item.addEventListener('dragstart', e => { drag = item; item.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', item.dataset.idx); } catch (x) {} });
    item.addEventListener('dragend', () => { item.classList.remove('dragging'); item.removeAttribute('draggable'); drag = null; order(); });
    item.addEventListener('dragover', e => { if (!drag || drag === item) return; e.preventDefault(); const r = item.getBoundingClientRect(); list.insertBefore(drag, (e.clientY - r.top) > r.height / 2 ? item.nextSibling : item); });
  });
  document.getElementById('sbExpand').onclick = () => list.querySelectorAll('.sb-item').forEach(i => i.classList.add('open'));
  document.getElementById('sbCollapse').onclick = () => list.querySelectorAll('.sb-item').forEach(i => i.classList.remove('open'));
  // add-section picker
  const picker = document.getElementById('sbPicker'); let addAt = 'bottom';
  document.querySelectorAll('[data-add-open]').forEach(b => b.onclick = () => { addAt = b.dataset.addOpen; picker.hidden = false; });
  picker.querySelector('[data-add-close]').onclick = () => picker.hidden = true;
  picker.addEventListener('click', e => { if (e.target === picker) picker.hidden = true; });
  picker.querySelectorAll('.sb-pick').forEach(b => b.onclick = () => {
    document.getElementById('addTypeField').value = b.dataset.type; document.getElementById('addAtField').value = addAt; form.requestSubmit();
  });
  <?php if ($openIdx >= 0): ?>setTimeout(() => { const el = document.getElementById('sbi<?= $openIdx ?>'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150);<?php endif; ?>
})();
</script>
<?php return; endif; ?>

<?php /* ============================== PAGE EDIT (banner, intro, SEO) ============================== */
$edit = get('edit') ? q_row("SELECT * FROM pages WHERE id=?", [(int)get('edit')]) : null;
if ($edit || get('new')): ?>
<p><a href="?m=cms">← All pages</a><?= $edit ? ' · <a class="btn sm sec" href="?m=cms&builder='.$edit['id'].'">🧩 Open Section Builder</a> <a class="btn sm ghost" target="_blank" href="'.e($pageUrl($edit)).'">👁 View page</a>' : '' ?></p>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
  <input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <h3><?= $edit ? 'Edit — '.e($edit['title']) : 'New Page' ?> <?= $edit && $edit['page_type']==='system' ? '<span class="badge blue">system page</span>' : '' ?></h3>
  <div class="frow3">
    <div><label>Page title</label><input class="input" name="title" value="<?= e($edit['title'] ?? '') ?>" required></div>
    <div><label>Slug (URL)</label><input class="input" name="slug" value="<?= e($edit['slug'] ?? '') ?>" <?= $edit && $edit['page_type']==='system'?'disabled':'' ?> placeholder="auto from title"></div>
    <div><label>Type</label><select class="input" name="page_type" <?= $edit && $edit['page_type']==='system'?'disabled':'' ?>>
      <option value="custom" <?= ($edit['page_type'] ?? '')==='custom'?'selected':'' ?>>Custom page</option>
      <option value="policy" <?= ($edit['page_type'] ?? '')==='policy'?'selected':'' ?>>Policy (footer link)</option>
      <option value="location" <?= ($edit['page_type'] ?? '')==='location'?'selected':'' ?>>Location page (local SEO)</option>
      <?php if (($edit['page_type'] ?? '') === 'system'): ?><option selected>System page</option><?php endif; ?>
    </select></div>
  </div>
  <h3 style="margin-top:14px">🖼 Page header / banner</h3>
  <?php banner_fields_html($edit ?: ['title'=>'', 'banner_title'=>'', 'banner_subtitle'=>'', 'banner_image'=>'', 'show_banner'=>1]); ?>
  <h3 style="margin-top:18px">✍ Intro content <small class="hint">(shown under the banner, above the sections — optional)</small></h3>
  <textarea class="input wysiwyg" name="content" data-minh="200px"><?= e($edit['content'] ?? '') ?></textarea>
  <p class="hint">Placeholders you can type: {REFUND_DAYS}, {REFUND_PERCENT}, {SITE_NAME}, {PHONE}, {EMAIL}</p>
  <div class="frow3" style="margin-top:8px">
    <div><label class="inline"><input type="checkbox" name="show_in_menu" value="1" <?= !empty($edit['show_in_menu'])?'checked':'' ?>> Show in header menu</label></div>
    <div><label>Menu order</label><input class="input" type="number" name="menu_order" value="<?= $edit['menu_order'] ?? 50 ?>"></div>
    <div><label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?> <?= ($edit['slug'] ?? '') === 'home' ? 'disabled' : '' ?>> Published</label><?= ($edit['slug'] ?? '') === 'home' ? '<input type="hidden" name="active" value="1">' : '' ?></div>
  </div>
  <h3 style="margin-top:16px">SEO</h3>
  <div class="frow">
    <div><label>SEO Title</label><input class="input" name="seo_title" value="<?= e($edit['seo_title'] ?? '') ?>"></div>
    <div><label>SEO Keywords</label><input class="input" name="seo_keywords" value="<?= e($edit['seo_keywords'] ?? '') ?>"></div>
  </div>
  <label>SEO Description</label><textarea class="input" name="seo_desc" style="min-height:56px"><?= e($edit['seo_desc'] ?? '') ?></textarea>
  <label>Custom Schema.org JSON-LD (optional)</label><textarea class="input" name="schema_json" style="min-height:70px;font-family:monospace"><?= e($edit['schema_json'] ?? '') ?></textarea>
  <div style="display:flex;gap:8px;margin-top:14px"><button class="btn">💾 Save Page</button><?php if ($edit): ?><a class="btn sec" href="?m=cms&builder=<?= $edit['id'] ?>">🧩 Edit sections →</a><?php endif; ?></div>
</form>

<?php /* ============================== PAGE LIST ============================== */ else: ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
    <h3>All Pages <small class="hint">— every page is editable: banner image, heading, intro, SEO and sections</small></h3>
    <a class="btn" href="?m=cms&new=1">+ New Page</a>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Banner</th><th>Page</th><th>Type</th><th>Sections</th><th>Menu</th><th>Status</th><th></th></tr>
    <?php foreach ($pages as $p): $secs = json_decode($p['sections'] ?: '[]', true) ?: []; $vis = count(array_filter($secs, fn($s) => !empty($s['visible']))); ?>
    <tr>
      <td><?php if ($p['banner_image']): ?><img class="imgthumb" src="<?= base_url($p['banner_image']) ?>" style="width:70px;height:44px;object-fit:cover"><?php else: ?><span class="hint">default</span><?php endif; ?></td>
      <td><b><?= e($p['title']) ?></b><br><small class="hint"><?= e($pageUrl($p)) ?></small></td>
      <td><span class="badge <?= $p['page_type']==='system'?'blue':($p['page_type']==='policy'?'purple':($p['page_type']==='location'?'orange':'gray')) ?>"><?= e($p['page_type']) ?></span></td>
      <td><?= count($secs) ?> <?= $vis < count($secs) ? '<small class="hint">('.$vis.' visible)</small>' : '' ?></td>
      <td><?= $p['show_in_menu'] ? '✔ ('.$p['menu_order'].')' : '—' ?></td>
      <td><span class="badge <?= $p['active']?'green':'red' ?>"><?= $p['active']?'live':'hidden' ?></span></td>
      <td style="white-space:nowrap">
        <a class="btn sm sec" href="?m=cms&builder=<?= $p['id'] ?>">🧩 Sections</a>
        <a class="btn sm ghost" href="?m=cms&edit=<?= $p['id'] ?>">🖼 Banner &amp; SEO</a>
        <a class="btn sm ghost" target="_blank" href="<?= e($pageUrl($p)) ?>?edit=1" title="Edit directly on the page">✨ Live</a>
        <?php if ($p['slug'] !== 'home'): ?>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_page"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm ghost" title="Show / hide page"><?= $p['active'] ? '👁' : '🚫' ?></button></form>
        <?php endif; ?>
        <?php if ($p['page_type'] !== 'system'): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete page?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm danger">✕</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <div class="tip" style="margin-top:12px">💡 <b>Two ways to edit:</b> the <b>Section Builder</b> here in the admin, or the <b>✨ Live editor</b> — open any page while logged in, press “Edit this page”, and change text, images and sections right where you see them.</div>
</div>
<?php endif; ?>
