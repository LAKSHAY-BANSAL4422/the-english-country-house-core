<?php
/** Explore Mussoorie — nearby attractions (name, type, distance, travel time, photos, map pin) shown on the Explore page, Contact page strip and the map */
require_admin('cms');
require_once ROOT_PATH.'/install/explore_data.php';
require_once ROOT_PATH.'/includes/sections.php';
$flash = '';
$CATS = attraction_categories();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $clean = fn($x) => preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$x);
        $imgs = array_values(array_filter(array_map($clean, (array)($_POST['images'] ?? []))));
        $name = mb_substr(trim(post('name')), 0, 190);
        $data = ['name'=>$name, 'category'=>isset($CATS[post('category')]) ? post('category') : 'other', 'label'=>mb_substr(trim(post('label')), 0, 80),
                 'description'=>trim(post('description')), 'long_description'=>$_POST['long_description'] ?? '',
                 'distance_km'=>mb_substr(trim(str_ireplace('km', '', post('distance_km'))), 0, 30), 'travel_time'=>mb_substr(trim(post('travel_time')), 0, 60),
                 'lat'=>post('lat') !== '' ? (float)post('lat') : null, 'lng'=>post('lng') !== '' ? (float)post('lng') : null,
                 'maps_url'=>mb_substr(trim(post('maps_url')), 0, 500), 'images'=>json_encode($imgs), 'image'=>$imgs[0] ?? '',
                 'featured'=>(int)(bool)post('featured'), 'active'=>(int)(bool)post('active'), 'sort'=>(int)post('sort')];
        if ($name === '') $flash = 'Name is required.';
        else {
            $data['slug'] = slugify($name) ?: 'place-'.time();
            if ((int)post('id')) { db_update('attractions', $data, 'id=?', [(int)post('id')]); $flash = 'Attraction updated.'; }
            else { db_insert('attractions', $data); $flash = 'Attraction added.'; }
        }
    }
    if (post('do') === 'delete') { db_delete('attractions', 'id=?', [(int)post('id')]); $flash = 'Deleted.'; }
    if (post('do') === 'toggle') { q("UPDATE attractions SET active=1-active WHERE id=?", [(int)post('id')]); $flash = 'Visibility changed.'; }
    if (post('do') === 'restore_defaults') { $n = explore_seed_attractions(db()); $flash = $n ? "$n default attraction(s) restored." : 'All default attractions already exist.'; }
}
$rows = q_all("SELECT * FROM attractions ORDER BY sort, id");
$edit = get('edit') ? q_row("SELECT * FROM attractions WHERE id=?", [(int)get('edit')]) : (get('new') ? ['id'=>0] : null);
$explorePage = q_row("SELECT id FROM pages WHERE slug='explore'");
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="alert" style="margin-bottom:14px">🏔 <b>Explore Mussoorie</b> — these places power the <a href="<?= u('explore.php') ?>" target="_blank">Explore Mussoorie page</a> (map + numbered cards + waterfalls), the <i>More to Explore</i> strip on the Contact page, and any <i>Attractions</i> section you add to other pages in the builder.
  Edit the page layout itself in <a href="?m=cms&builder=<?= (int)($explorePage["id"] ?? 0) ?>">Pages / Builder → Explore Mussoorie</a>. Distances are from the house on Dhanaulti Road; map pins are approximate — adjust the latitude / longitude if a pin sits in the wrong place.</div>

<div class="grid2" style="grid-template-columns:1.9fr 1fr">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap"><h3>Attractions (<?= count($rows) ?>)</h3>
      <div><a class="btn sm" href="?m=explore&new=1">+ Add place</a>
      <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="restore_defaults"><button class="btn sm ghost" type="submit" title="Re-adds any of the 10 default places that were deleted">↺ Restore defaults</button></form></div></div>
    <table class="tbl"><tr><th>#</th><th>Photo</th><th>Place</th><th>Type</th><th>Distance</th><th>Pin</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $i => $r): $n = count(array_filter(json_decode($r['images'] ?? '[]', true) ?: [])); ?>
    <tr><td><?= $i + 1 ?></td>
      <td><?= $r['image'] ? '<img class="imgthumb" src="'.base_url($r['image']).'">'.($n > 1 ? ' <small class="hint">+'.($n-1).'</small>' : '') : '<span style="color:#b9600d">no photo</span>' ?></td>
      <td><b><?= e($r['name']) ?></b><?= $r['featured'] ? ' <span class="badge" style="background:#f0c987;color:#5a3d0b">featured</span>' : '' ?><br><small class="hint"><?= e(mb_strimwidth($r["description"], 0, 48, "…")) ?></small></td>
      <td><?= e($r['label'] ?: ($CATS[$r['category']][0] ?? '')) ?></td>
      <td><?= e($r['distance_km']) ?> km<br><small class="hint"><?= e($r['travel_time']) ?></small></td>
      <td><?= $r['lat'] && $r['lng'] ? '<a href="https://www.google.com/maps/search/?api=1&query='.$r['lat'].','.$r['lng'].'" target="_blank" title="Check pin on Google Maps">📍</a>' : '<span class="hint">—</span>' ?></td>
      <td><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="badge <?= $r['active']?'green':'red' ?>" style="border:0;cursor:pointer" title="Click to show / hide"><?= $r['active']?'visible':'hidden' ?></button></form></td>
      <td style="white-space:nowrap"><a class="btn sm ghost" href="?m=explore&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this place?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>

  <div class="card">
    <?php if ($edit): ?>
    <h3><?= !empty($edit['id']) ? 'Edit place' : 'Add a place' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label>Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="e.g. Kempty Falls">
      <div class="frow">
        <div><label>Type</label><select class="input" name="category"><?php foreach ($CATS as $k => $c): ?><option value="<?= $k ?>" <?= ($edit['category'] ?? '') === $k ? 'selected' : '' ?>><?= e($c[0]) ?></option><?php endforeach; ?></select></div>
        <div><label>Label on the card <small class="hint">(optional, e.g. "Scenic Spot")</small></label><input class="input" name="label" value="<?= e($edit['label'] ?? '') ?>"></div>
      </div>
      <label>Short description <small class="hint">(one or two lines — shown on the cards)</small></label><textarea class="input" name="description" rows="3" required><?= e($edit['description'] ?? '') ?></textarea>
      <label>Full description <small class="hint">(shown in the popup when a visitor clicks the card)</small></label>
      <textarea class="wysiwyg" name="long_description" rows="6"><?= e($edit['long_description'] ?? '') ?></textarea>
      <div class="frow">
        <div><label>Distance from the house (km)</label><input class="input" name="distance_km" value="<?= e($edit['distance_km'] ?? '') ?>" placeholder="8.3 or 6 to 7"></div>
        <div><label>Travel time</label><input class="input" name="travel_time" value="<?= e($edit['travel_time'] ?? '') ?>" placeholder="~25 mins"></div>
      </div>
      <div class="frow">
        <div><label>Latitude</label><input class="input" name="lat" value="<?= e((string)($edit['lat'] ?? '')) ?>" placeholder="30.4636"></div>
        <div><label>Longitude</label><input class="input" name="lng" value="<?= e((string)($edit['lng'] ?? '')) ?>" placeholder="78.1000"></div>
      </div>
      <p class="hint">Find the pin: open <a href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode(($edit['name'] ?? '').' Mussoorie') ?>" target="_blank">Google Maps</a>, right-click the spot → the first line is "latitude, longitude" — copy them here.</p>
      <label>Directions link <small class="hint">(optional — overrides the automatic Google Maps directions)</small></label><input class="input" name="maps_url" value="<?= e($edit['maps_url'] ?? '') ?>" placeholder="https://maps.app.goo.gl/…">
      <?php $eImgs = json_decode($edit['images'] ?? '[]', true) ?: []; if (!empty($edit['image']) && !in_array($edit['image'], $eImgs, true)) array_unshift($eImgs, $edit['image']); ?>
      <label>Photos <small class="hint">(first = card photo; all appear in the popup)</small></label>
      <div data-uploader data-name="images[]" data-multiple="1" data-max="8" data-subdir="pages" data-value="<?= e(json_encode($eImgs)) ?>"></div>
      <div class="frow"><div><label>Sort</label><input class="input" type="number" name="sort" value="<?= (int)($edit['sort'] ?? (count($rows) + 1)) ?>"></div></div>
      <label class="inline"><input type="checkbox" name="featured" value="1" <?= !empty($edit['featured']) ? 'checked' : '' ?>> Featured (shown first / in "Featured only" sections)</label>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= empty($edit['id']) || !empty($edit['active']) ? 'checked' : '' ?>> Visible on the website</label><br>
      <button class="btn" style="margin-top:12px">Save</button> <a class="btn ghost" href="?m=explore">Cancel</a>
    </form>
    <?php else: ?>
    <h3>Tips</h3>
    <p class="hint">• Click <b>Edit</b> on a place to add photos, fix the distance or move its map pin.<br>• <b>Featured</b> places appear first in the numbered cards.<br>• Photos are optimised automatically on upload (and can be imported from a shared Google Drive folder under Gallery → Import).<br>• The <a href="<?= u('explore.php') ?>" target="_blank">Explore Mussoorie</a> page and the Contact page strip update instantly.</p>
    <a class="btn" href="?m=explore&new=1">+ Add a place</a>
    <?php endif; ?>
  </div>
</div>
