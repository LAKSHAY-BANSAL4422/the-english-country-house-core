<?php
/** Rooms CRUD with images, amenities, SEO + iCal export URL for OTA sync */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $id = (int)post('id');
        // photos come from the uploader component (already saved by admin/upload.php) — unlimited, ordered, with a cover
        $clean = fn($p) => preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$p);
        $images = array_values(array_filter(array_map($clean, (array)($_POST['images'] ?? []))));
        if (!$images && !isset($_POST['images_empty']) && $id) $images = json_decode(q_val("SELECT images FROM rooms WHERE id=?", [$id], '[]'), true) ?: [];
        $cover = $clean(post('cover_image')); if ($cover !== '' && !in_array($cover, $images, true)) $cover = '';
        if ($cover === '' && $images) $cover = $images[0];
        $data = [
          'name'=>post('name'),'slug'=>slugify(post('slug') ?: post('name')),
          'short_desc'=>post('short_desc'),'description'=>post('description'),
          'base_price'=>(float)post('base_price'),'weekend_price'=>(float)post('weekend_price'),
          'extra_adult_price'=>(float)post('extra_adult_price'),'child_price'=>(float)post('child_price'),
          'base_occupancy'=>(int)post('base_occupancy'),'max_adults'=>(int)post('max_adults'),'max_children'=>(int)post('max_children'),
          'total_units'=>max(1,(int)post('total_units')),'pets_allowed'=>(int)(bool)post('pets_allowed'),
          'room_size'=>post('room_size'),'bed_type'=>post('bed_type'),'view_type'=>post('view_type'),
          'images'=>json_encode($images),'cover_image'=>$cover,'amenity_ids'=>json_encode(array_map('intval',(array)($_POST['amenity_ids'] ?? []))),
          'video_url'=>mb_substr(trim(post('video_url')),0,500),'video_file'=>$clean(post('video_file')),'highlights'=>mb_substr(trim(post('highlights')),0,2000),
          'featured'=>(int)(bool)post('featured'),'active'=>(int)(bool)post('active'),'sort'=>(int)post('sort'),
          'seo_title'=>post('seo_title'),'seo_desc'=>post('seo_desc'),'seo_keywords'=>post('seo_keywords'),
        ];
        if ($id) { db_update('rooms', $data, 'id=?', [$id]); $flash='Room updated.'; }
        else { $data['ical_token'] = bin2hex(random_bytes(16)); db_insert('rooms', $data); $flash='Room added.'; }
    }
    if (post('do') === 'delete') { db_delete('rooms','id=?',[(int)post('id')]); $flash='Room deleted.'; }
}
$rooms = q_all("SELECT * FROM rooms ORDER BY sort,id");
$amens = q_all("SELECT * FROM amenities WHERE active=1 ORDER BY sort");
$edit = get('edit') ? q_row("SELECT * FROM rooms WHERE id=?", [(int)get('edit')]) : null;
$editAmens = $edit ? (json_decode($edit['amenity_ids'] ?: '[]', true) ?: []) : [];
$editImgs = $edit ? (json_decode($edit['images'] ?: '[]', true) ?: []) : [];
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<?php if ($edit || get('new')): ?>
<p><a href="?m=rooms">← All rooms</a></p>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
  <input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <h3><?= $edit ? 'Edit — '.e($edit['name']) : 'Add Room' ?></h3>
  <div class="frow">
    <div><label>Room Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
    <div><label>Slug (URL)</label><input class="input" name="slug" value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto"></div>
  </div>
  <label>Short Description</label><textarea class="input" name="short_desc" style="min-height:60px"><?= e($edit['short_desc'] ?? '') ?></textarea>
  <label>Full Description (visual editor)</label><textarea class="input wysiwyg" name="description" data-minh="160px"><?= e($edit['description'] ?? '') ?></textarea>
  <div class="frow4">
    <div><label>Base Price / night (₹)</label><input class="input" type="number" step="0.01" name="base_price" value="<?= $edit['base_price'] ?? '' ?>" required></div>
    <div><label>Weekend Price (0 = same)</label><input class="input" type="number" step="0.01" name="weekend_price" value="<?= $edit['weekend_price'] ?? 0 ?>"></div>
    <div><label>Extra Adult / night</label><input class="input" type="number" step="0.01" name="extra_adult_price" value="<?= $edit['extra_adult_price'] ?? 0 ?>"></div>
    <div><label>Child / night</label><input class="input" type="number" step="0.01" name="child_price" value="<?= $edit['child_price'] ?? 0 ?>"></div>
    <div><label>Base Occupancy</label><input class="input" type="number" name="base_occupancy" value="<?= $edit['base_occupancy'] ?? 2 ?>"></div>
    <div><label>Max Adults</label><input class="input" type="number" name="max_adults" value="<?= $edit['max_adults'] ?? 3 ?>"></div>
    <div><label>Max Children</label><input class="input" type="number" name="max_children" value="<?= $edit['max_children'] ?? 2 ?>"></div>
    <div><label>Units (rooms of this type)</label><input class="input" type="number" name="total_units" value="<?= $edit['total_units'] ?? 1 ?>"></div>
    <div><label>Room Size</label><input class="input" name="room_size" value="<?= e($edit['room_size'] ?? '') ?>" placeholder="320 sq.ft"></div>
    <div><label>Bed Type</label><input class="input" name="bed_type" value="<?= e($edit['bed_type'] ?? '') ?>"></div>
    <div><label>View</label><input class="input" name="view_type" value="<?= e($edit['view_type'] ?? '') ?>"></div>
    <div><label>Sort Order</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div>
  </div>
  <div style="margin-top:10px">
    <label class="inline"><input type="checkbox" name="pets_allowed" value="1" <?= !empty($edit['pets_allowed'])?'checked':'' ?>> 🐾 Pet friendly</label>
    <label class="inline"><input type="checkbox" name="featured" value="1" <?= !empty($edit['featured'])?'checked':'' ?>> Featured on home</label>
    <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label>
  </div>
  <label>Amenities</label>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
    <?php foreach ($amens as $a): ?>
      <label class="inline"><input type="checkbox" name="amenity_ids[]" value="<?= $a['id'] ?>" <?= in_array($a['id'],$editAmens)?'checked':'' ?>> <?= e($a['name']) ?></label>
    <?php endforeach; ?>
  </div>
  <h3 style="margin-top:18px">📷 Photos <small class="hint">— add up to 30, drag to reorder, ★ marks the cover / feature image (shown first in room cards &amp; as the page banner)</small></h3>
  <p class="hint" style="margin:-4px 0 10px">🎞 The room cards on the home page and the Rooms page <b>slide through all of these photos automatically</b> (cover first, then the rest in this order, looping back to the first) and the room page shows them in a big slider. Add at least 3–5 photos per room for the slide to show — a room with one photo stays still.</p>
  <div data-uploader data-name="images[]" data-multiple="1" data-max="30" data-subdir="rooms" data-cover-name="cover_image"
       data-value="<?= e(json_encode($editImgs)) ?>" data-cover="<?= e($edit['cover_image'] ?? ($editImgs[0] ?? '')) ?>"></div>
  <p class="hint">Photos are resized to <?= (int)setting('upload_max_px','2000') ?> px and compressed in your browser before upload, so even large phone photos upload quickly.</p>
  <h3 style="margin-top:18px">🎬 Room video <small class="hint">(optional — shown on the room page as “Room Tour”)</small></h3>
  <div class="frow">
    <div><label>YouTube / Vimeo link</label><input class="input" name="video_url" value="<?= e($edit['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=…"></div>
    <div><label>…or upload a video file <small class="hint">(MP4 / WebM, under 40 MB — a YouTube link is lighter &amp; faster)</small></label>
      <div data-uploader data-name="video_file" data-kind="video" data-subdir="video" data-value="<?= e($edit['video_file'] ?? '') ?>"></div></div>
  </div>
  <label>Room highlights <small class="hint">(one per line — shown as a tick list, e.g. “Private balcony with valley view”)</small></label>
  <textarea class="input" name="highlights" style="min-height:90px"><?= e($edit['highlights'] ?? '') ?></textarea>
  <?php if ($edit): ?>
  <div class="tip" style="margin-top:14px"><b>📅 OTA iCal export URL (availability feed for Airbnb / channel calendars):</b><br>
    <code style="font-size:12px;word-break:break-all"><?= base_url('ical.php?room='.$edit['slug'].'&token='.$edit['ical_token']) ?></code><br>
    <span class="hint">Paste this in each OTA's "Import calendar" — and add their export URL under OTA Channels → Room Mapping so their bookings block your dates automatically.</span></div>
  <?php endif; ?>
  <h3 style="margin-top:18px">SEO</h3>
  <div class="frow">
    <div><label>SEO Title</label><input class="input" name="seo_title" value="<?= e($edit['seo_title'] ?? '') ?>"></div>
    <div><label>SEO Keywords</label><input class="input" name="seo_keywords" value="<?= e($edit['seo_keywords'] ?? '') ?>"></div>
  </div>
  <label>SEO Description</label><textarea class="input" name="seo_desc" style="min-height:60px"><?= e($edit['seo_desc'] ?? '') ?></textarea>
  <button class="btn" style="margin-top:14px">Save Room</button>
</form>
<?php else: ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;margin-bottom:12px"><h3>All Rooms</h3><a class="btn" href="?m=rooms&new=1">+ Add Room</a></div>
  <div class="tbl-wrap"><table class="tbl">
  <tr><th>Image</th><th>Name</th><th>Base ₹</th><th>Occupancy</th><th>Units</th><th>Pets</th><th>Status</th><th></th></tr>
  <?php foreach ($rooms as $r): $im = room_images($r); $cv = room_cover($r); ?>
  <tr><td><?= $cv ? '<img class="imgthumb" src="'.base_url($cv).'" title="cover">' : '<span class="hint">no photo</span>' ?><br><small class="hint"><?= count($im) ?> photo(s)<?= ($r['video_url'] ?? '') !== '' || ($r['video_file'] ?? '') !== '' ? ' · 🎬 video' : '' ?></small></td>
    <td><b><?= e($r['name']) ?></b><br><small class="hint">/<?= e($r['slug']) ?></small></td>
    <td><?= money($r['base_price']) ?></td><td><?= $r['base_occupancy'] ?> (max <?= $r['max_adults'] ?>+<?= $r['max_children'] ?>c)</td>
    <td><?= $r['total_units'] ?></td><td><?= $r['pets_allowed'] ? '🐾' : '—' ?></td>
    <td><span class="badge <?= $r['active']?'green':'red' ?>"><?= $r['active']?'active':'hidden' ?></span></td>
    <td><a class="btn sm ghost" href="?m=rooms&edit=<?= $r['id'] ?>">Edit</a>
      <form method="post" style="display:inline" onsubmit="return confirm('Delete room?')"><?= csrf_field() ?>
      <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
  <?php endforeach; ?></table></div>
</div>
<?php endif; ?>
