<?php
/**
 * Restaurant Menu & Food Orders
 * tabs: orders (live, with sound alerts) · items · sections · QR codes · page & settings
 */
require_once ROOT_PATH.'/lib/food.php';
require_once ROOT_PATH.'/admin/menu_orders.php';

$tab = get('tab', 'orders');
$TABS = ['orders'=>'🛎 Live Orders','items'=>'🍛 Menu Items','sections'=>'📚 Sections','qr'=>'🔳 QR Codes','settings'=>'⚙ Page & Settings'];
if (!isset($TABS[$tab])) $tab = 'orders';
$flash = ''; $err = ''; $waTest = null;
$ICONS = ['fa-utensils','fa-mug-saucer','fa-mug-hot','fa-bowl-food','fa-bowl-rice','fa-pepper-hot','fa-leaf','fa-seedling','fa-drumstick-bite','fa-fish','fa-egg','fa-bread-slice','fa-pizza-slice','fa-burger','fa-cheese','fa-ice-cream','fa-cake-candles','fa-cookie','fa-martini-glass-citrus','fa-wine-glass','fa-glass-water','fa-champagne-glasses','fa-mountain-sun','fa-fire-burner','fa-star','fa-heart','fa-child-reaching','fa-moon','fa-sun','fa-carrot','fa-lemon','fa-apple-whole'];
$DIETS = FOOD_DIETS;

$cleanTime = fn($t) => preg_match('/^\d{1,2}:\d{2}$/', (string)$t) ? $t.':00' : null;
$catSlug = function (string $name, int $id = 0): string {
    $base = slugify($name) ?: 'section'; $s = $base; $i = 2;
    while (q_val("SELECT id FROM menu_categories WHERE slug=? AND id<>?", [$s, $id])) $s = $base.'-'.$i++;
    return $s;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
    /* ---------- items ---------- */
    case 'save_item':
        $id = (int)post('id');
        $data = [
            'category_id'=>(int)post('category_id'), 'name'=>mb_substr(post('name'),0,160), 'description'=>mb_substr(post('description'),0,600),
            'price'=>max(0, (float)post('price')), 'diet'=>array_key_exists(post('diet'), $DIETS) ? post('diet') : 'veg',
            'variants'=>implode(', ', array_filter(array_map('trim', explode(',', post('variants'))))),
            'tags'=>implode(', ', array_filter(array_map('trim', explode(',', post('tags'))))),
            'sort'=>(int)post('sort'), 'active'=>(int)(bool)post('active'), 'available'=>(int)(bool)post('available'),
        ];
        if ($data['name'] === '' || !$data['category_id']) { $err = 'Name and section are required.'; break; }
        $img = handle_upload('image', 'menu', ['jpg','jpeg','png','webp']);
        if ($img) $data['image'] = $img; elseif (post('remove_image')) $data['image'] = '';
        if ($id) { db_update('menu_items', $data, 'id=?', [$id]); $flash = '“'.$data['name'].'” updated.'; }
        else {
            if (!$data['sort']) $data['sort'] = (int)q_val("SELECT COALESCE(MAX(sort),0)+10 FROM menu_items WHERE category_id=?", [$data['category_id']], 10);
            $id = db_insert('menu_items', $data); $flash = '“'.$data['name'].'” added to the menu.';
        }
        $_GET['cat'] = (string)$data['category_id']; unset($_GET['edit'], $_GET['add']);
        break;
    case 'delete_item':
        $n = q_val("SELECT name FROM menu_items WHERE id=?", [(int)post('id')]);
        db_delete('menu_items', 'id=?', [(int)post('id')]); $flash = '“'.$n.'” deleted.';
        break;
    case 'bulk_price':
        $pct = (float)post('percent'); $cid = (int)post('category_id');
        if ($pct != 0 && abs($pct) <= 100) {
            $round = max(1, (int)post('round', 5));
            q("UPDATE menu_items SET price=GREATEST(0, ROUND(price*(1+?/100)/?)*?)".($cid ? " WHERE category_id=?" : ""), $cid ? [$pct, $round, $round, $cid] : [$pct, $round, $round]);
            $flash = 'Prices '.($pct > 0 ? 'increased' : 'reduced').' by '.abs($pct).'% '.($cid ? 'in “'.q_val("SELECT name FROM menu_categories WHERE id=?", [$cid]).'”' : 'across the whole menu').' (rounded to ₹'.$round.').';
        } else $err = 'Enter a percentage between -100 and 100.';
        break;

    /* ---------- sections ---------- */
    case 'save_cat':
        $id = (int)post('id');
        $data = ['name'=>mb_substr(post('name'),0,140), 'subtitle'=>mb_substr(post('subtitle'),0,255),
                 'icon'=>in_array(post('icon'), $ICONS, true) ? post('icon') : 'fa-utensils',
                 'time_from'=>$cleanTime(post('time_from')), 'time_to'=>$cleanTime(post('time_to')),
                 'advance_order'=>(int)(bool)post('advance_order'), 'active'=>(int)(bool)post('active'), 'sort'=>(int)post('sort')];
        if ($data['name'] === '') { $err = 'Section name is required.'; break; }
        $data['slug'] = $catSlug($data['name'], $id);
        $img = handle_upload('image', 'menu', ['jpg','jpeg','png','webp']);
        if ($img) $data['image'] = $img; elseif (post('remove_image')) $data['image'] = '';
        if ($id) { db_update('menu_categories', $data, 'id=?', [$id]); $flash = 'Section “'.$data['name'].'” updated.'; }
        else {
            if (!$data['sort']) $data['sort'] = (int)q_val("SELECT COALESCE(MAX(sort),0)+10 FROM menu_categories", [], 10);
            db_insert('menu_categories', $data); $flash = 'Section “'.$data['name'].'” created — now add dishes to it.';
        }
        unset($_GET['edit']);
        break;
    case 'delete_cat':
        $cid = (int)post('id'); $c = q_row("SELECT * FROM menu_categories WHERE id=?", [$cid]);
        if (!$c) break;
        $moveTo = (int)post('move_to');
        if ($moveTo && $moveTo !== $cid) { q("UPDATE menu_items SET category_id=? WHERE category_id=?", [$moveTo, $cid]); $extra = ' Its dishes were moved.'; }
        else { $nI = (int)q_val("SELECT COUNT(*) FROM menu_items WHERE category_id=?", [$cid], 0); db_delete('menu_items', 'category_id=?', [$cid]); $extra = " $nI dish(es) deleted with it."; }
        db_delete('menu_categories', 'id=?', [$cid]);
        $flash = 'Section “'.$c['name'].'” deleted.'.$extra;
        break;

    /* ---------- orders quick controls ---------- */
    case 'quick_wait':
        setting_save('menu_wait_minutes', (string)max(5, min(240, (int)post('minutes'))));
        $flash = 'Waiting time shown to guests is now '.menu_wait_minutes().' minutes.';
        break;
    case 'toggle_ordering':
        setting_save('menu_ordering_enabled', setting('menu_ordering_enabled') === '1' ? '0' : '1');
        $flash = setting('menu_ordering_enabled') === '1' ? 'Online ordering is ON.' : 'Online ordering PAUSED — guests see the menu with a "call to order" button.';
        break;

    /* ---------- settings ---------- */
    case 'save_settings':
        $keys = ['menu_title','menu_restaurant_name','menu_tagline','menu_intro','menu_price_note','menu_tax_label','menu_wait_note',
                 'menu_order_phone','menu_kitchen_from','menu_kitchen_to','menu_wa_mode','menu_wa_cc','menu_wa_cloud_token','menu_wa_cloud_phone_id',
                 'menu_wa_cloud_template','menu_wa_cloud_lang','menu_wa_gateway_url','menu_wa_gateway_method','menu_wa_gateway_body'];
        foreach ($keys as $k) if (isset($_POST[$k])) setting_save($k, post($k));
        foreach (['menu_enabled','menu_ordering_enabled','menu_enforce_timings','menu_room_required','menu_wa_guest_open','menu_alert_email','menu_alert_repeat','menu_show_footer_qr'] as $k)
            setting_save($k, post($k) ? '1' : '0');
        setting_save('menu_wait_minutes', (string)max(5, min(240, (int)post('menu_wait_minutes'))));
        setting_save('menu_tax_percent', (string)max(0, min(40, (float)post('menu_tax_percent'))));
        setting_save('menu_min_order', (string)max(0, (float)post('menu_min_order')));
        if (preg_match('/^#[0-9a-f]{6}$/i', post('menu_theme_color'))) setting_save('menu_theme_color', post('menu_theme_color'));
        $hero = handle_upload('menu_hero', 'menu', ['jpg','jpeg','png','webp']); if ($hero) setting_save('menu_hero_image', $hero);
        // staff WhatsApp numbers (unlimited)
        $nums = [];
        foreach ((array)($_POST['wa_number'] ?? []) as $i => $num) {
            $num = preg_replace('/[^0-9+]/', '', (string)$num);
            if (strlen(preg_replace('/\D/', '', $num)) < 10) continue;
            $nums[] = ['name'=>mb_substr(trim((string)($_POST['wa_name'][$i] ?? '')), 0, 60), 'number'=>$num, 'active'=>!empty($_POST['wa_active'][$i]) ? 1 : 0];
        }
        setting_save('menu_wa_numbers', json_encode($nums, JSON_UNESCAPED_UNICODE));
        // header navigation link
        $nav = json_decode(setting('menu_items','[]'), true) ?: [];
        $found = false;
        foreach ($nav as &$n) if (($n['url'] ?? '') === 'menu.php') { $n['visible'] = post('show_in_nav') ? 1 : 0; $n['label'] = post('menu_title') ?: 'Our Menu'; $found = true; }
        unset($n);
        if (!$found && post('show_in_nav')) $nav[] = ['label'=>post('menu_title') ?: 'Our Menu','url'=>'menu.php','visible'=>1];
        setting_save('menu_items', json_encode($nav, JSON_UNESCAPED_UNICODE));
        $flash = 'Menu settings saved. '.count($nums).' WhatsApp number(s) will receive orders.';
        if (post('wa_test_after')) $waTest = wa_send_all("✅ Test from ".setting('site_name')." — food orders will arrive on this WhatsApp number.\n🕒 ".date('d M Y, h:i A'));
        break;
    case 'wa_test':
        $waTest = wa_send_all("✅ Test from ".setting('site_name')." — food orders will arrive on this WhatsApp number.\n🕒 ".date('d M Y, h:i A'));
        break;

    /* ---------- QR ---------- */
    case 'save_qr':
        if (preg_match('/^#[0-9a-f]{6}$/i', post('menu_qr_color'))) setting_save('menu_qr_color', post('menu_qr_color'));
        setting_save('menu_qr_rooms', mb_substr(post('menu_qr_rooms'), 0, 3000));
        $lg = handle_upload('menu_qr_logo', 'logo', ['png','jpg','jpeg','webp']); if ($lg) setting_save('menu_qr_logo', $lg);
        if (post('reset_logo')) setting_save('menu_qr_logo', 'assets/img/brand/logo.png');
        $flash = 'QR settings saved.';
        break;
    }
}

$cats = q_all("SELECT c.*, (SELECT COUNT(*) FROM menu_items i WHERE i.category_id=c.id) cnt, (SELECT COUNT(*) FROM menu_items i WHERE i.category_id=c.id AND i.active=1) live FROM menu_categories c ORDER BY c.sort, c.id");
$today = date('Y-m-d');
$kp = q_row("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN status<>'cancelled' THEN total END),0) rev,
             SUM(status IN ('new','accepted','preparing','ready')) open, SUM(status='new') fresh FROM food_orders WHERE DATE(created_at)=?", [$today]);
$pendingAll = (int)q_val("SELECT COUNT(*) FROM food_orders WHERE status IN ('new','accepted','preparing','ready')", [], 0);
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>

<div class="tabs">
  <?php foreach ($TABS as $k=>$v): ?><a href="?m=menu&tab=<?= $k ?>" class="<?= $tab===$k?'active':'' ?>"><?= $v ?><?= $k==='orders' && $pendingAll ? ' <span class="badge orange">'.$pendingAll.'</span>' : '' ?></a><?php endforeach; ?>
  <a href="<?= u('menu.php') ?>" target="_blank" style="margin-left:auto"><i class="fa-solid fa-arrow-up-right-from-square"></i> View public menu</a>
</div>

<?php /* =============================== ORDERS =============================== */ if ($tab === 'orders'):
  $view = get('view', 'active'); $date = get('date', $view === 'active' ? '' : $today); ?>
<div class="kpis" style="margin-bottom:14px">
  <div class="kpi"><i class="fa-solid fa-receipt"></i><div class="k-num"><?= (int)$kp['n'] ?></div><div class="k-lbl">Food orders today</div></div>
  <div class="kpi"><i class="fa-solid fa-fire-burner"></i><div class="k-num" id="foOpenCount"><?= (int)$kp['open'] ?></div><div class="k-lbl">Open / in kitchen</div></div>
  <div class="kpi"><i class="fa-solid fa-indian-rupee-sign"></i><div class="k-num"><?= money($kp['rev']) ?></div><div class="k-lbl">Food revenue today</div></div>
  <div class="kpi"><i class="fa-regular fa-clock"></i><div class="k-num"><?= menu_wait_minutes() ?> min</div><div class="k-lbl">Waiting time shown</div></div>
</div>
<div class="card" style="padding:12px 16px;margin-bottom:14px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <form method="post" style="display:flex;gap:6px;align-items:center;margin:0"><?= csrf_field() ?><input type="hidden" name="do" value="quick_wait">
      <b style="font-size:13px">⏱ Waiting time</b><input class="input" type="number" name="minutes" min="5" max="240" value="<?= menu_wait_minutes() ?>" style="width:80px;padding:6px 8px"> min <button class="btn sm">Update</button></form>
    <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_ordering">
      <button class="btn sm" style="background:<?= setting('menu_ordering_enabled')==='1' ? '#27ae60' : '#95a5a6' ?>;color:#fff">🛎 Online ordering — <?= setting('menu_ordering_enabled')==='1' ? 'ON' : 'PAUSED' ?></button></form>
    <button class="btn sm ghost" type="button" id="foMute">🔔 Sound on</button>
    <span class="hint" id="foLive" style="margin-left:auto"><i class="fa-solid fa-circle" style="color:#27ae60;font-size:9px"></i> Live — refreshes every 10 s</span>
  </div>
  <div class="filters" style="margin:12px 0 0;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
    <?php foreach (['active'=>'Open orders','delivered'=>'Delivered','cancelled'=>'Cancelled','all'=>'All'] as $k=>$v): ?>
      <a class="btn sm <?= $view===$k?'sec':'ghost' ?>" href="?m=menu&tab=orders&view=<?= $k ?>"><?= $v ?></a>
    <?php endforeach; ?>
    <?php if ($view !== 'active'): ?>
    <form style="display:flex;gap:6px;margin:0"><input type="hidden" name="m" value="menu"><input type="hidden" name="tab" value="orders"><input type="hidden" name="view" value="<?= e($view) ?>">
      <input class="input" type="date" name="date" value="<?= e($date) ?>" style="padding:5px 8px"><button class="btn sm ghost">Go</button>
      <a class="btn sm ghost" href="?m=menu&tab=orders&view=<?= e($view) ?>&date=">Any date</a></form>
    <?php endif; ?>
  </div>
</div>
<div class="fo-grid" id="foGrid" data-view="<?= e($view) ?>" data-date="<?= e($date) ?>"><?php render_food_orders($view, $date); ?></div>

<script>
(function(){
  const grid = document.getElementById('foGrid');
  const muteBtn = document.getElementById('foMute');
  const muted = () => { try { return localStorage.getItem('fo_mute') === '1'; } catch(e) { return false; } };
  const paint = () => muteBtn.textContent = muted() ? '🔕 Sound off' : '🔔 Sound on';
  muteBtn.onclick = () => { try { localStorage.setItem('fo_mute', muted() ? '0' : '1'); } catch(e) {} paint(); };
  paint();
  function post(data){
    const fd = new FormData(); fd.append('csrf', ADMIN.csrf);
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));
    return fetch(ADMIN.base + '/admin/ajax.php', {method:'POST', body:fd}).then(r => r.json());
  }
  function refresh(){
    post({action:'food_list', view:grid.dataset.view, date:grid.dataset.date}).then(d => {
      if (!d.ok) return;
      if (grid.dataset.sig !== d.sig) { grid.innerHTML = d.html; grid.dataset.sig = d.sig; }
      const oc = document.getElementById('foOpenCount'); if (oc) oc.textContent = d.open;
    }).catch(()=>{});
  }
  grid.addEventListener('click', e => {
    const b = e.target.closest('[data-fo-status]'); if (!b) return;
    if (b.dataset.confirm && !confirm(b.dataset.confirm)) return;
    const card = b.closest('.fo-card'); const eta = card.querySelector('.fo-eta');
    b.disabled = true;
    post({action:'food_status', id:card.dataset.id, status:b.dataset.foStatus, eta: eta ? eta.value : ''}).then(d => {
      if (d.ok) { toast('Order ' + d.code + ' → <b>' + d.label + '</b>', 'fa-bell-concierge'); grid.dataset.sig = ''; refresh(); }
      else { alert(d.error || 'Could not update'); b.disabled = false; }
    });
  });
  document.addEventListener('click', e => { if (!e.target.closest('.fo-fwd')) document.querySelectorAll('.fo-fwd-menu.show').forEach(m => m.classList.remove('show')); });
  setInterval(refresh, 10000);
})();
</script>

<?php /* =============================== ITEMS =============================== */ elseif ($tab === 'items'):
  $editId = (int)get('edit'); $addCat = (int)get('add');
  $edit = $editId ? q_row("SELECT * FROM menu_items WHERE id=?", [$editId]) : null;
  $showForm = $edit || $addCat || get('new');
  $filterCat = (int)get('cat');
  $items = q_all("SELECT * FROM menu_items ".($filterCat ? "WHERE category_id=? " : "")."ORDER BY sort, id", $filterCat ? [$filterCat] : []);
  $byCat = []; foreach ($items as $it) $byCat[(int)$it['category_id']][] = $it; ?>

<?php if ($showForm): $f = $edit ?: ['id'=>0,'category_id'=>$addCat ?: ($cats[0]['id'] ?? 0),'name'=>'','description'=>'','price'=>'','diet'=>'veg','variants'=>'','tags'=>'','image'=>'','sort'=>0,'active'=>1,'available'=>1]; ?>
<form method="post" enctype="multipart/form-data" class="card" style="margin-bottom:16px;border:2px solid #c9974b33">
  <?= csrf_field() ?><input type="hidden" name="do" value="save_item"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
  <h3><?= $edit ? '✏ Edit dish' : '➕ Add a dish' ?></h3>
  <div class="frow3">
    <div><label>Section</label><select class="input" name="category_id" required><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$f['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><label>Dish name</label><input class="input" name="name" value="<?= e($f['name']) ?>" required maxlength="160"></div>
    <div><label>Price (₹)</label><input class="input" type="number" step="0.01" min="0" name="price" value="<?= e((string)$f['price']) ?>" required></div>
  </div>
  <label>Description</label><textarea class="input" name="description" rows="2" maxlength="600"><?= e($f['description']) ?></textarea>
  <div class="frow3">
    <div><label>Food type</label><select class="input" name="diet"><?php foreach ($DIETS as $k=>$v): ?><option value="<?= $k ?>" <?= $f['diet']===$k?'selected':'' ?>><?= food_diet_dot($k) ?> <?= $v ?></option><?php endforeach; ?></select></div>
    <div><label>Options guest chooses <small class="hint">(comma separated)</small></label><input class="input" name="variants" value="<?= e($f['variants']) ?>" placeholder="e.g. Plain, Malai, Achari"></div>
    <div><label>Tags <small class="hint">(comma separated)</small></label><input class="input" name="tags" value="<?= e($f['tags']) ?>" list="tagList" placeholder="Bestseller, Chef’s Special, New">
      <datalist id="tagList"><option value="Bestseller"><option value="Chef’s Special"><option value="New"><option value="Spicy"><option value="Jain available"></datalist></div>
  </div>
  <div class="frow3">
    <div><label>Photo <small class="hint">(optional, square looks best)</small></label><input class="input" type="file" name="image" accept="image/*">
      <?php if ($f['image']): ?><div style="display:flex;gap:10px;align-items:center;margin-top:6px"><img class="imgthumb" src="<?= base_url($f['image']) ?>"><label class="inline"><input type="checkbox" name="remove_image" value="1"> remove</label></div><?php endif; ?></div>
    <div><label>Sort order</label><input class="input" type="number" name="sort" value="<?= (int)$f['sort'] ?>"><small class="hint">or drag rows in the list</small></div>
    <div style="padding-top:22px">
      <label class="inline"><input type="checkbox" name="active" value="1" <?= $f['active']?'checked':'' ?>> 👁 Visible on menu</label><br>
      <label class="inline"><input type="checkbox" name="available" value="1" <?= $f['available']?'checked':'' ?>> ✅ In stock (uncheck = “Sold out”)</label>
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:14px"><button class="btn"><?= $edit ? 'Update dish' : 'Add dish' ?></button><a class="btn ghost" href="?m=menu&tab=items<?= $filterCat ? '&cat='.$filterCat : '' ?>">Cancel</a></div>
</form>
<?php endif; ?>

<div class="card" style="padding:12px 16px;margin-bottom:14px">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a class="btn" href="?m=menu&tab=items&new=1<?= $filterCat ? '&add='.$filterCat : '' ?>">➕ Add dish</a>
    <form style="display:flex;gap:6px;margin:0"><input type="hidden" name="m" value="menu"><input type="hidden" name="tab" value="items">
      <select class="input" name="cat" onchange="this.form.submit()" style="padding:7px 10px"><option value="0">All sections</option>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $filterCat===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?> (<?= $c['cnt'] ?>)</option><?php endforeach; ?></select></form>
    <input class="input" id="miSearch" placeholder="🔍 Find a dish…" style="max-width:220px;padding:7px 10px">
    <form method="post" style="display:flex;gap:6px;align-items:center;margin:0 0 0 auto" onsubmit="return confirm('Change prices now?')"><?= csrf_field() ?><input type="hidden" name="do" value="bulk_price">
      <span class="hint">Bulk price</span>
      <select class="input" name="category_id" style="padding:6px 8px"><option value="0">Whole menu</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $filterCat===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <input class="input" type="number" step="0.5" name="percent" placeholder="+10 / -5" style="width:84px;padding:6px 8px" required>%
      <select class="input" name="round" style="padding:6px 8px" title="Round to"><option value="1">₹1</option><option value="5" selected>₹5</option><option value="10">₹10</option></select>
      <button class="btn sm ghost">Apply</button></form>
  </div>
</div>

<?php foreach ($cats as $c): if ($filterCat && $filterCat !== (int)$c['id']) continue; $list = $byCat[(int)$c['id']] ?? []; ?>
<div class="card mi-cat <?= $c['active'] ? '' : 'off' ?>" style="margin-bottom:14px">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
    <h3 style="margin:0"><i class="fa-solid <?= e($c['icon']) ?>" style="color:#c9974b"></i> <?= e($c['name']) ?>
      <small class="hint"><?= count($list) ?> dishes<?= $c['active'] ? '' : ' · <b style="color:#c0392b">section hidden</b>' ?></small></h3>
    <div style="display:flex;gap:6px"><a class="btn sm ghost" href="?m=menu&tab=sections&edit=<?= $c['id'] ?>">Edit section</a><a class="btn sm" href="?m=menu&tab=items&add=<?= $c['id'] ?>&cat=<?= $filterCat ?: '' ?>">➕ Dish</a></div>
  </div>
  <?php if (!$list): ?><p class="hint">No dishes yet.</p><?php else: ?>
  <table class="tbl mi-tbl"><thead><tr><th style="width:28px"></th><th>Dish</th><th style="width:120px">Price ₹</th><th style="width:150px">Visibility</th><th style="width:130px"></th></tr></thead>
  <tbody data-sort="item">
  <?php foreach ($list as $it): ?>
    <tr draggable="true" data-id="<?= $it['id'] ?>" class="<?= $it['active'] ? '' : 'mi-off' ?>" data-search="<?= e(mb_strtolower($it['name'].' '.$it['variants'])) ?>">
      <td class="drag" title="Drag to reorder">⠿</td>
      <td><i class="diet diet-<?= e($it['diet']) ?>"></i><b><?= e($it['name']) ?></b>
        <?php if ($it['image']): ?> <i class="fa-regular fa-image hint" title="has photo"></i><?php endif; ?>
        <?php if ($it['tags']): ?> <span class="badge orange" style="font-size:10px"><?= e($it['tags']) ?></span><?php endif; ?>
        <br><small class="hint"><?= e(mb_strimwidth($it['description'], 0, 90, '…')) ?><?= $it['variants'] ? ' · <b>Options:</b> '.e($it['variants']) : '' ?></small></td>
      <td><input class="input mi-price" type="number" step="0.01" min="0" value="<?= (float)$it['price'] ?>" data-id="<?= $it['id'] ?>" style="padding:5px 8px;width:100px"></td>
      <td>
        <button type="button" class="btn sm <?= $it['active']?'green':'ghost' ?>" data-mtoggle="item" data-field="active" data-id="<?= $it['id'] ?>" title="Show / hide on menu"><?= $it['active']?'👁 Shown':'🚫 Hidden' ?></button>
        <button type="button" class="btn sm <?= $it['available']?'green':'danger' ?>" data-mtoggle="item" data-field="available" data-id="<?= $it['id'] ?>" title="In stock / sold out"><?= $it['available']?'In stock':'Sold out' ?></button>
      </td>
      <td style="white-space:nowrap"><a class="btn sm ghost" href="?m=menu&tab=items&edit=<?= $it['id'] ?><?= $filterCat ? '&cat='.$filterCat : '' ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete “<?= e(addslashes($it['name'])) ?>”?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete_item"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="btn sm danger">🗑</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php /* =============================== SECTIONS =============================== */ elseif ($tab === 'sections'):
  $edit = get('edit') ? q_row("SELECT * FROM menu_categories WHERE id=?", [(int)get('edit')]) : null;
  $f = $edit ?: ['id'=>0,'name'=>'','subtitle'=>'','icon'=>'fa-utensils','image'=>'','time_from'=>null,'time_to'=>null,'advance_order'=>0,'active'=>1,'sort'=>0]; ?>
<div class="grid2" style="grid-template-columns:1.4fr 1fr;align-items:start">
  <div class="card">
    <h3>📚 Menu sections <small class="hint">drag ⠿ to reorder — order is saved instantly</small></h3>
    <table class="tbl mi-tbl"><thead><tr><th style="width:28px"></th><th>Section</th><th>Dishes</th><th>Visibility</th><th></th></tr></thead>
    <tbody data-sort="cat">
    <?php foreach ($cats as $c): ?>
      <tr draggable="true" data-id="<?= $c['id'] ?>" class="<?= $c['active'] ? '' : 'mi-off' ?>">
        <td class="drag">⠿</td>
        <td><i class="fa-solid <?= e($c['icon']) ?>" style="color:#c9974b;width:18px"></i> <b><?= e($c['name']) ?></b>
          <br><small class="hint"><?= e($c['subtitle']) ?><?= $c['time_from'] ? ' · 🕘 '.e(menu_time_label($c['time_from'], $c['time_to'])) : '' ?><?= $c['advance_order'] ? ' · ⏳ advance order' : '' ?></small></td>
        <td><a href="?m=menu&tab=items&cat=<?= $c['id'] ?>"><?= (int)$c['live'] ?>/<?= (int)$c['cnt'] ?></a></td>
        <td><button type="button" class="btn sm <?= $c['active']?'green':'ghost' ?>" data-mtoggle="cat" data-field="active" data-id="<?= $c['id'] ?>"><?= $c['active']?'👁 Shown':'🚫 Hidden' ?></button></td>
        <td style="white-space:nowrap"><a class="btn sm ghost" href="?m=menu&tab=sections&edit=<?= $c['id'] ?>">Edit</a>
          <button type="button" class="btn sm danger" onclick="document.getElementById('del<?= $c['id'] ?>').classList.toggle('show')">🗑</button></td>
      </tr>
      <tr class="mi-del" id="del<?= $c['id'] ?>"><td colspan="5">
        <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0" onsubmit="return confirm('Delete section “<?= e(addslashes($c['name'])) ?>”?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete_cat"><input type="hidden" name="id" value="<?= $c['id'] ?>">
          <span>Its <?= (int)$c['cnt'] ?> dish(es):</span>
          <select class="input" name="move_to" style="padding:6px 8px;max-width:260px"><option value="0">delete them too</option>
            <?php foreach ($cats as $o): if ($o['id'] == $c['id']) continue; ?><option value="<?= $o['id'] ?>">move to “<?= e($o['name']) ?>”</option><?php endforeach; ?></select>
          <button class="btn sm danger">Delete section</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save_cat"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
    <h3><?= $edit ? '✏ Edit section' : '➕ New section' ?></h3>
    <label>Section name</label><input class="input" name="name" value="<?= e($f['name']) ?>" required placeholder="e.g. Chef’s Specials">
    <label>Subtitle / note</label><input class="input" name="subtitle" value="<?= e($f['subtitle']) ?>" placeholder="e.g. Available from 9:00 am to 11:00 am">
    <label>Icon</label>
    <div class="mi-icons">
      <?php foreach ($ICONS as $ic): ?><label title="<?= $ic ?>"><input type="radio" name="icon" value="<?= $ic ?>" <?= $f['icon']===$ic?'checked':'' ?>><i class="fa-solid <?= $ic ?>"></i></label><?php endforeach; ?>
    </div>
    <div class="frow">
      <div><label>Served from <small class="hint">(optional)</small></label><input class="input" type="time" name="time_from" value="<?= e(substr((string)$f['time_from'],0,5)) ?>"></div>
      <div><label>Served until</label><input class="input" type="time" name="time_to" value="<?= e(substr((string)$f['time_to'],0,5)) ?>"></div>
    </div>
    <label>Banner image <small class="hint">(optional — shows behind the section title)</small></label><input class="input" type="file" name="image" accept="image/*">
    <?php if ($f['image']): ?><div style="display:flex;gap:10px;align-items:center;margin-top:6px"><img class="imgthumb" src="<?= base_url($f['image']) ?>"><label class="inline"><input type="checkbox" name="remove_image" value="1"> remove</label></div><?php endif; ?>
    <div class="frow" style="margin-top:8px">
      <div><label>Sort order</label><input class="input" type="number" name="sort" value="<?= (int)$f['sort'] ?>"></div>
      <div style="padding-top:24px"><label class="inline"><input type="checkbox" name="advance_order" value="1" <?= $f['advance_order']?'checked':'' ?>> ⏳ Advance order</label><br>
        <label class="inline"><input type="checkbox" name="active" value="1" <?= $f['active']?'checked':'' ?>> 👁 Visible</label></div>
    </div>
    <div style="display:flex;gap:8px;margin-top:14px"><button class="btn"><?= $edit ? 'Update section' : 'Create section' ?></button><?php if ($edit): ?><a class="btn ghost" href="?m=menu&tab=sections">+ New instead</a><?php endif; ?></div>
  </form>
</div>

<?php /* =============================== QR =============================== */ elseif ($tab === 'qr'):
  $rooms = q_all("SELECT name FROM rooms WHERE active=1 ORDER BY sort, id");
  $extra = array_filter(array_map('trim', preg_split('/\r?\n/', setting('menu_qr_rooms'))));
  $labels = array_merge(array_column($rooms, 'name'), $extra);
  $base = u('menu.php'); ?>
<div class="grid2" style="grid-template-columns:1fr 1.3fr;align-items:start">
  <div class="card" style="text-align:center">
    <h3>🔳 Main menu QR (with your logo)</h3>
    <p class="hint">Opens <code><?= e($base) ?></code> — put it on tables, reception, brochures or social media.</p>
    <canvas id="qrMain" data-qr="<?= e($base.'?src=qr') ?>" data-qr-size="280" data-qr-label="SCAN FOR MENU" style="margin:10px auto;display:block;box-shadow:0 10px 30px rgba(0,0,0,.12);border-radius:14px"></canvas>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
      <button class="btn" type="button" data-qr-dl="qrMain" data-name="menu-qr.png"><i class="fa-solid fa-download"></i> Download PNG (1024 px)</button>
      <a class="btn ghost" target="_blank" href="<?= base_url('admin/menu_qr_print.php') ?>"><i class="fa-solid fa-print"></i> Print table cards</a>
    </div>
    <form method="post" enctype="multipart/form-data" style="text-align:left;margin-top:18px;border-top:1px solid var(--bd);padding-top:14px"><?= csrf_field() ?><input type="hidden" name="do" value="save_qr">
      <div class="frow">
        <div><label>QR colour</label><input type="color" name="menu_qr_color" value="<?= e(setting('menu_qr_color','#163a2c')) ?>" style="width:60px;height:38px;border:1px solid var(--bd);border-radius:6px"></div>
        <div><label>Centre logo</label><input class="input" type="file" name="menu_qr_logo" accept="image/*">
          <div style="display:flex;gap:8px;align-items:center;margin-top:6px"><img src="<?= base_url(setting('menu_qr_logo') ?: 'assets/img/brand/logo.png') ?>" style="height:46px;background:#fff;border:1px solid var(--bd);border-radius:6px;padding:3px"><label class="inline"><input type="checkbox" name="reset_logo" value="1"> use default logo</label></div></div>
      </div>
      <label>Extra QR labels <small class="hint">(one per line — tables, deck, pool… rooms are added automatically)</small></label>
      <textarea class="input" name="menu_qr_rooms" rows="3" placeholder="Restaurant Table 1&#10;Sunset Deck&#10;Pool side"><?= e(setting('menu_qr_rooms')) ?></textarea>
      <button class="btn sm" style="margin-top:8px">Save QR settings</button>
    </form>
  </div>
  <div class="card">
    <h3>🏠 Room-wise QR codes <small class="hint">— scanning pre-fills the room number on the order</small></h3>
    <div class="qr-grid">
      <?php foreach ($labels as $i => $lb): $url = $base.'?room='.rawurlencode($lb).'&src=qr'; ?>
      <div class="qr-cell">
        <canvas id="qrR<?= $i ?>" data-qr="<?= e($url) ?>" data-qr-size="150"></canvas>
        <b><?= e($lb) ?></b>
        <div style="display:flex;gap:4px;justify-content:center">
          <button class="btn sm ghost" type="button" data-qr-dl="qrR<?= $i ?>" data-label="<?= e($lb) ?>" data-name="menu-qr-<?= e(slugify($lb)) ?>.png" title="Download"><i class="fa-solid fa-download"></i> PNG</button>
          <a class="btn sm ghost" target="_blank" href="<?= e($url) ?>" title="Open"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>window.ECH_QR = {logo: '<?= e(base_url(setting('menu_qr_logo') ?: 'assets/img/brand/logo.png')) ?>', color: '<?= e(setting('menu_qr_color','#163a2c')) ?>'};</script>
<script src="<?= base_url('assets/js/vendor/qrcode.js') ?>"></script>
<script src="<?= base_url('assets/js/menu-qr.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/menu-qr.js') ?>"></script>
<script>
document.addEventListener('click', e => {
  const b = e.target.closest('[data-qr-dl]'); if (!b) return;
  const cv = document.getElementById(b.dataset.qrDl);
  ECHQR.toPng(cv.dataset.qr, {label: b.dataset.label ? b.dataset.label.toUpperCase() : (cv.dataset.qrLabel || '')}).then(url => {
    const a = document.createElement('a'); a.href = url; a.download = b.dataset.name || 'qr.png'; document.body.appendChild(a); a.click(); a.remove();
  });
});
</script>

<?php /* =============================== SETTINGS =============================== */ else:
  $s = fn($k, $d = '') => e(setting($k, $d));
  $chk = fn($k) => setting($k) === '1' ? 'checked' : '';
  $nums = menu_wa_numbers(false);
  if (!$nums) $nums = [['name'=>'Reception','number'=>setting('contact_phone'),'active'=>1]];
  $navOn = false; foreach (json_decode(setting('menu_items','[]'), true) ?: [] as $n) if (($n['url'] ?? '') === 'menu.php' && !empty($n['visible'])) $navOn = true;
  $mode = setting('menu_wa_mode','link'); ?>
<?php if ($waTest !== null): ?>
<div class="card" style="margin-bottom:14px;border-left:4px solid #25d366">
  <h3>WhatsApp test result</h3>
  <?php if (!$waTest): ?><p>No active numbers.</p><?php endif; ?>
  <?php foreach ($waTest as $r): ?>
    <p><?= $r['ok'] ? '✅' : ($mode === 'link' ? '🔗' : '❌') ?> <b><?= e($r['name'] ?: 'Staff') ?></b> <?= e($r['number']) ?> — <?= e($r['info']) ?>
      <?php if ($mode === 'link'): ?> · <a target="_blank" href="<?= e(wa_link($r['number'], 'Test from '.setting('site_name'))) ?>">open WhatsApp chat</a><?php endif; ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="do" value="save_settings">
<div class="grid2" style="align-items:start">
  <div class="card">
    <h3>🌐 Public “Our Menu” page</h3>
    <label class="inline"><input type="checkbox" name="menu_enabled" value="1" <?= $chk('menu_enabled') ?>> Menu page enabled</label> &nbsp;
    <label class="inline"><input type="checkbox" name="show_in_nav" value="1" <?= $navOn ? 'checked' : '' ?>> Show in header menu</label> &nbsp;
    <label class="inline"><input type="checkbox" name="menu_show_footer_qr" value="1" <?= $chk('menu_show_footer_qr') ?>> QR card in footer</label>
    <div class="frow" style="margin-top:8px">
      <div><label>Page title (menu tab name)</label><input class="input" name="menu_title" value="<?= $s('menu_title','Our Menu') ?>"></div>
      <div><label>Restaurant name</label><input class="input" name="menu_restaurant_name" value="<?= $s('menu_restaurant_name') ?>"></div>
    </div>
    <label>Tagline <small class="hint">(script font)</small></label><input class="input" name="menu_tagline" value="<?= $s('menu_tagline') ?>">
    <label>Intro text</label><textarea class="input" name="menu_intro" rows="2"><?= $s('menu_intro') ?></textarea>
    <label>Price note <small class="hint">(shown under the menu)</small></label><input class="input" name="menu_price_note" value="<?= $s('menu_price_note') ?>">
    <div class="frow">
      <div><label>Hero photo</label><input class="input" type="file" name="menu_hero" accept="image/*"><img class="imgthumb" style="margin-top:6px" src="<?= base_url(setting('menu_hero_image') ?: 'assets/img/restaurant.jpg') ?>"></div>
      <div><label>Menu theme colour</label><input type="color" name="menu_theme_color" value="<?= $s('menu_theme_color','#163a2c') ?>" style="width:60px;height:38px;border:1px solid var(--bd);border-radius:6px"></div>
    </div>
  </div>
  <div class="card">
    <h3>🛎 Ordering &amp; waiting time</h3>
    <label class="inline"><input type="checkbox" name="menu_ordering_enabled" value="1" <?= $chk('menu_ordering_enabled') ?>> Online ordering ON</label> &nbsp;
    <label class="inline"><input type="checkbox" name="menu_room_required" value="1" <?= $chk('menu_room_required') ?>> Room number required</label>
    <div class="frow3" style="margin-top:8px">
      <div><label>⏱ Waiting time (min)</label><input class="input" type="number" min="5" max="240" name="menu_wait_minutes" value="<?= menu_wait_minutes() ?>"></div>
      <div><label>Call-to-order number</label><input class="input" name="menu_order_phone" value="<?= $s('menu_order_phone') ?>"></div>
      <div><label>Minimum order ₹</label><input class="input" type="number" min="0" name="menu_min_order" value="<?= $s('menu_min_order','0') ?>"></div>
    </div>
    <label>Waiting-time message</label><input class="input" name="menu_wait_note" value="<?= $s('menu_wait_note') ?>">
    <div class="frow3">
      <div><label>Kitchen takes orders from</label><input class="input" type="time" name="menu_kitchen_from" value="<?= $s('menu_kitchen_from') ?>"></div>
      <div><label>until</label><input class="input" type="time" name="menu_kitchen_to" value="<?= $s('menu_kitchen_to') ?>"></div>
      <div style="padding-top:22px"><small class="hint">leave empty = 24 h</small></div>
    </div>
    <label class="inline"><input type="checkbox" name="menu_enforce_timings" value="1" <?= $chk('menu_enforce_timings') ?>> Only allow ordering a section during its served hours (e.g. Breakfast 9–11 am)</label>
    <div class="frow">
      <div><label>Tax % on food <small class="hint">(0 = prices inclusive)</small></label><input class="input" type="number" step="0.5" min="0" max="40" name="menu_tax_percent" value="<?= $s('menu_tax_percent','0') ?>"></div>
      <div><label>Tax label</label><input class="input" name="menu_tax_label" value="<?= $s('menu_tax_label','GST') ?>"></div>
    </div>
    <h3 style="margin-top:14px">🔔 Alerts</h3>
    <label class="inline"><input type="checkbox" name="menu_alert_repeat" value="1" <?= $chk('menu_alert_repeat') ?>> Keep ringing in the admin panel until someone accepts the order</label><br>
    <label class="inline"><input type="checkbox" name="menu_alert_email" value="1" <?= $chk('menu_alert_email') ?>> E-mail every order to <?= e(setting('contact_email')) ?></label>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <h3><i class="fa-brands fa-whatsapp" style="color:#25d366"></i> WhatsApp — who receives food orders</h3>
  <p class="hint">Add as many numbers as you like (kitchen, reception, manager, room service…). Each order is sent with room no., guest name, mobile and the full item list.</p>
  <div id="waRows">
    <?php foreach ($nums as $i => $n): ?>
    <div class="wa-row">
      <input class="input" name="wa_name[<?= $i ?>]" value="<?= e($n['name'] ?? '') ?>" placeholder="Label e.g. Kitchen">
      <input class="input" name="wa_number[<?= $i ?>]" value="<?= e($n['number']) ?>" placeholder="WhatsApp number" inputmode="tel">
      <label class="inline"><input type="checkbox" name="wa_active[<?= $i ?>]" value="1" <?= !empty($n['active']) ? 'checked' : '' ?>> active</label>
      <button type="button" class="btn sm danger" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn sm ghost" id="waAdd">➕ Add another number</button>

  <h3 style="margin-top:18px">How orders reach WhatsApp</h3>
  <div class="wa-modes">
    <label class="<?= $mode==='link'?'on':'' ?>"><input type="radio" name="menu_wa_mode" value="link" <?= $mode==='link'?'checked':'' ?>>
      <b>📲 Click-to-chat (free, no setup)</b><small>The order appears instantly on your dashboard with a sound alert, and the guest's phone opens WhatsApp with the full order ready to send to your <u>first</u> number. Staff can forward to the others in one tap.</small></label>
    <label class="<?= $mode==='cloud'?'on':'' ?>"><input type="radio" name="menu_wa_mode" value="cloud" <?= $mode==='cloud'?'checked':'' ?>>
      <b>⚡ Automatic — WhatsApp Cloud API (Meta)</b><small>Every order is sent automatically to <u>all</u> numbers above from your WhatsApp Business number.</small></label>
    <label class="<?= $mode==='gateway'?'on':'' ?>"><input type="radio" name="menu_wa_mode" value="gateway" <?= $mode==='gateway'?'checked':'' ?>>
      <b>🔌 Automatic — any WhatsApp API provider</b><small>UltraMsg, Interakt, WATI, Gupshup, AiSensy, Twilio… paste their send-message URL.</small></label>
  </div>
  <div class="frow3" style="margin-top:10px">
    <div><label>Default country code</label><input class="input" name="menu_wa_cc" value="<?= $s('menu_wa_cc','91') ?>"></div>
    <div style="grid-column:span 2;padding-top:22px"><label class="inline"><input type="checkbox" name="menu_wa_guest_open" value="1" <?= $chk('menu_wa_guest_open') ?>> In click-to-chat mode, open WhatsApp on the guest's phone automatically after ordering</label></div>
  </div>
  <div class="wa-cfg" data-for="cloud">
    <div class="frow">
      <div><label>Permanent access token</label><input class="input" name="menu_wa_cloud_token" value="<?= $s('menu_wa_cloud_token') ?>" autocomplete="off"></div>
      <div><label>Phone-number ID</label><input class="input" name="menu_wa_cloud_phone_id" value="<?= $s('menu_wa_cloud_phone_id') ?>"></div>
    </div>
    <div class="frow">
      <div><label>Approved template name <small class="hint">(needed to message staff who haven't written in 24 h — one body variable {{1}})</small></label><input class="input" name="menu_wa_cloud_template" value="<?= $s('menu_wa_cloud_template') ?>" placeholder="food_order_alert"></div>
      <div><label>Template language</label><input class="input" name="menu_wa_cloud_lang" value="<?= $s('menu_wa_cloud_lang','en') ?>"></div>
    </div>
  </div>
  <div class="wa-cfg" data-for="gateway">
    <label>Send URL — placeholders <code>{phone}</code> (with country code), <code>{phone10}</code>, <code>{message}</code></label>
    <input class="input" name="menu_wa_gateway_url" value="<?= $s('menu_wa_gateway_url') ?>" placeholder="https://api.ultramsg.com/instanceXXXX/messages/chat?token=XXXX&to={phone}&body={message}">
    <div class="frow">
      <div><label>Method</label><select class="input" name="menu_wa_gateway_method"><option <?= setting('menu_wa_gateway_method')==='GET'?'selected':'' ?>>GET</option><option <?= setting('menu_wa_gateway_method')==='POST'?'selected':'' ?>>POST</option></select></div>
      <div><label>POST body <small class="hint">(optional — form or JSON with the same placeholders)</small></label><input class="input" name="menu_wa_gateway_body" value="<?= $s('menu_wa_gateway_body') ?>" placeholder='{"to":"{phone}","text":"{message}"}'></div>
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:16px">
    <button class="btn">💾 Save menu settings</button>
    <button class="btn ghost" name="wa_test_after" value="1">📤 Save &amp; send test to all numbers</button>
  </div>
</div>
</form>
<template id="waTpl"><div class="wa-row">
  <input class="input" name="wa_name[__i]" placeholder="Label e.g. Kitchen"><input class="input" name="wa_number[__i]" placeholder="WhatsApp number" inputmode="tel">
  <label class="inline"><input type="checkbox" name="wa_active[__i]" value="1" checked> active</label><button type="button" class="btn sm danger" onclick="this.parentElement.remove()">✕</button></div></template>
<script>
(function(){
  let i = 1000;
  document.getElementById('waAdd').onclick = () => {
    const html = document.getElementById('waTpl').innerHTML.replace(/__i/g, i++);
    document.getElementById('waRows').insertAdjacentHTML('beforeend', html);
    document.querySelector('#waRows .wa-row:last-child input[name^="wa_number"]').focus();
  };
  const modes = document.querySelectorAll('input[name=menu_wa_mode]');
  const sync = () => {
    const v = document.querySelector('input[name=menu_wa_mode]:checked').value;
    document.querySelectorAll('.wa-modes label').forEach(l => l.classList.toggle('on', l.querySelector('input').checked));
    document.querySelectorAll('.wa-cfg').forEach(c => c.style.display = c.dataset.for === v ? '' : 'none');
  };
  modes.forEach(m => m.addEventListener('change', sync)); sync();
})();
</script>
<?php endif; ?>

<?php if (in_array($tab, ['items','sections'])): ?>
<script>
(function(){
  function post(data){
    const fd = new FormData(); fd.append('csrf', ADMIN.csrf);
    Object.entries(data).forEach(([k,v]) => Array.isArray(v) ? v.forEach(x => fd.append(k+'[]', x)) : fd.append(k, v));
    return fetch(ADMIN.base + '/admin/ajax.php', {method:'POST', body:fd}).then(r => r.json());
  }
  // drag & drop ordering (dishes within a section, or sections)
  document.querySelectorAll('tbody[data-sort]').forEach(tb => {
    let drag = null;
    tb.addEventListener('dragstart', e => { drag = e.target.closest('tr[draggable]'); if (drag) { drag.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', drag.dataset.id); } catch(x){} } });
    tb.addEventListener('dragover', e => {
      e.preventDefault(); const over = e.target.closest('tr[draggable]');
      if (!drag || !over || over === drag) return;
      const r = over.getBoundingClientRect(), after = (e.clientY - r.top) > r.height / 2;
      const ref = after ? over.nextElementSibling : over;
      tb.insertBefore(drag, ref && ref.classList.contains('mi-del') && after ? ref.nextElementSibling : ref);
    });
    tb.addEventListener('dragend', () => {
      if (!drag) return; drag.classList.remove('dragging'); drag = null;
      // keep each section's delete row right below its row
      tb.querySelectorAll('tr[draggable]').forEach(tr => { const d = document.getElementById('del' + tr.dataset.id); if (d && tb.dataset.sort === 'cat') tr.after(d); });
      const ids = [...tb.querySelectorAll('tr[draggable]')].map(tr => tr.dataset.id);
      post({action:'menu_sort', type: tb.dataset.sort, ids}).then(d => d.ok ? toast('↕ New order saved', 'fa-arrows-up-down') : alert(d.error || 'Could not save order'));
    });
  });
  // quick show/hide & sold-out toggles
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-mtoggle]'); if (!b) return;
    b.disabled = true;
    post({action:'menu_toggle', type:b.dataset.mtoggle, field:b.dataset.field, id:b.dataset.id}).then(d => {
      b.disabled = false; if (!d.ok) return alert(d.error || 'Failed');
      const on = d.value === 1;
      if (b.dataset.field === 'active') { b.textContent = on ? '👁 Shown' : '🚫 Hidden'; b.className = 'btn sm ' + (on ? 'green' : 'ghost'); b.closest('tr').classList.toggle('mi-off', !on); }
      else { b.textContent = on ? 'In stock' : 'Sold out'; b.className = 'btn sm ' + (on ? 'green' : 'danger'); }
    });
  });
  // inline price edit
  document.querySelectorAll('.mi-price').forEach(inp => inp.addEventListener('change', () => {
    post({action:'menu_price', id: inp.dataset.id, price: inp.value}).then(d => {
      if (d.ok) { inp.style.background = '#e6f7ee'; setTimeout(() => inp.style.background = '', 900); toast('💰 Price updated to ' + d.price, 'fa-tag'); }
      else alert(d.error || 'Failed');
    });
  }));
  const ms = document.getElementById('miSearch');
  ms && ms.addEventListener('input', () => {
    const q = ms.value.toLowerCase();
    document.querySelectorAll('.mi-tbl tr[data-search]').forEach(tr => tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none');
  });
})();
</script>
<?php endif; ?>
