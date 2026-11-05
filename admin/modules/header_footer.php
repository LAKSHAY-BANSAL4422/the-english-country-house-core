<?php
/** Header & Footer manager: drag-order menus, show/hide, custom links, footer text */
$flash = '';
function hf_read_items(string $prefix): array {
    $items = [];
    foreach ((array)($_POST[$prefix.'_label'] ?? []) as $k=>$lbl) {
        if (trim($lbl) === '') continue;
        $items[] = ['label'=>trim($lbl),'url'=>trim($_POST[$prefix.'_url'][$k] ?? '#'),'visible'=>!empty($_POST[$prefix.'_visible'][$k]) ? 1 : 0];
    }
    return $items;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save_menu') {
        setting_save('menu_items', json_encode(hf_read_items('mi'), JSON_UNESCAPED_UNICODE));
        $flash = 'Header menu saved.';
    }
    if (post('do') === 'save_footer') {
        setting_save('footer_about', post('footer_about'));
        setting_save('footer_links', json_encode(hf_read_items('fl'), JSON_UNESCAPED_UNICODE));
        $flash = 'Footer saved.';
    }
}
$menu = json_decode(setting('menu_items','[]'), true) ?: [];
$flinks = json_decode(setting('footer_links','[]'), true) ?: [];
$rowTpl = function($prefix, $i, $it) {
    return '<div class="sb-item open" draggable="true" style="padding:0">
      <div class="sb-head" style="padding:8px 12px"><i class="fa-solid fa-grip-vertical drag-handle"></i>
        <input class="input" style="max-width:180px" name="'.$prefix.'_label['.$i.']" value="'.e($it['label'] ?? '').'" placeholder="Label">
        <input class="input" style="flex:1" name="'.$prefix.'_url['.$i.']" value="'.e($it['url'] ?? '').'" placeholder="page.php?slug=... or https://…">
        <label class="inline" style="margin:0"><input type="checkbox" name="'.$prefix.'_visible['.$i.']" value="1" '.(!isset($it['visible'])||$it['visible']?'checked':'').'> 👁</label>
        <button type="button" class="btn sm danger" onclick="this.closest(\'.sb-item\').remove()">✕</button>
      </div></div>';
};
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tip">💡 Drag rows to reorder · 👁 hides a link without deleting it. Logos, sizes &amp; favicon are under <a href="?m=appearance"><b>Appearance</b></a>. Pages ticked "Show in menu" (CMS) appear after these items automatically.</div>
<div class="grid2">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save_menu">
    <h3>🧭 Header Menu</h3>
    <div class="sb-list" id="menuList"><?php foreach ($menu as $i=>$it) echo $rowTpl('mi', $i, $it); ?></div>
    <button type="button" class="btn ghost sm" style="margin-top:10px" onclick="addRow('menuList','mi')">+ Add menu item</button>
    <button class="btn" style="margin-top:10px;float:right">Save Header Menu</button>
  </form>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save_footer">
    <h3>🦶 Footer</h3>
    <label>About text (first footer column)</label>
    <textarea class="input" name="footer_about"><?= e(setting('footer_about')) ?></textarea>
    <label style="margin-top:12px">Quick Links</label>
    <div class="sb-list" id="footList"><?php foreach ($flinks as $i=>$it) echo $rowTpl('fl', $i, $it); ?></div>
    <button type="button" class="btn ghost sm" style="margin-top:10px" onclick="addRow('footList','fl')">+ Add footer link</button>
    <p class="hint" style="margin-top:8px">Policy pages &amp; location pages are appended automatically. Contact info comes from Site Settings → General.</p>
    <button class="btn" style="margin-top:10px;float:right">Save Footer</button>
  </form>
</div>
<script>
function wireDrag(listId){
  const list = document.getElementById(listId); let dragEl = null;
  list.addEventListener('dragstart', e=>{ const it = e.target.closest('.sb-item'); if (it){ dragEl = it; it.classList.add('dragging'); } });
  list.addEventListener('dragend', e=>{ if (dragEl) dragEl.classList.remove('dragging'); dragEl = null; });
  list.addEventListener('dragover', e=>{
    e.preventDefault();
    const it = e.target.closest('.sb-item');
    if (dragEl && it && it !== dragEl) {
      const after = (e.clientY - it.getBoundingClientRect().top) > it.offsetHeight/2;
      list.insertBefore(dragEl, after ? it.nextSibling : it);
    }
  });
}
wireDrag('menuList'); wireDrag('footList');
let rowN = 1000;
function addRow(listId, prefix){
  const i = rowN++;
  const d = document.createElement('div');
  d.className = 'sb-item open'; d.draggable = true; d.style.padding = '0';
  d.innerHTML = `<div class="sb-head" style="padding:8px 12px"><i class="fa-solid fa-grip-vertical drag-handle"></i>
    <input class="input" style="max-width:180px" name="${prefix}_label[${i}]" placeholder="Label">
    <input class="input" style="flex:1" name="${prefix}_url[${i}]" placeholder="page.php?slug=... or https://…">
    <label class="inline" style="margin:0"><input type="checkbox" name="${prefix}_visible[${i}]" value="1" checked> 👁</label>
    <button type="button" class="btn sm danger" onclick="this.closest('.sb-item').remove()">✕</button></div>`;
  document.getElementById(listId).appendChild(d);
}
</script>
