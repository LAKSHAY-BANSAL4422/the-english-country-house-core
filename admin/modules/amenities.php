<?php
/** Amenities CRUD (admin + team with permission) */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $data = ['name'=>post('name'),'icon'=>post('icon') ?: 'fa-star','description'=>post('description'),
                 'show_home'=>(int)(bool)post('show_home'),'sort'=>(int)post('sort'),'active'=>(int)(bool)post('active')];
        $clean = fn($x) => preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$x);
        $imgs = array_values(array_filter(array_map($clean, (array)($_POST['images'] ?? []))));
        $data['images'] = json_encode($imgs);
        $data['image'] = $imgs[0] ?? '';          // first photo = main / thumbnail
        if ((int)post('id')) { db_update('amenities', $data, 'id=?', [(int)post('id')]); $flash='Amenity updated.'; }
        else { db_insert('amenities', $data); $flash='Amenity added.'; }
    }
    if (post('do') === 'delete') { db_delete('amenities','id=?',[(int)post('id')]); $flash='Deleted.'; }
}
$rows = q_all("SELECT * FROM amenities ORDER BY sort,id");
$edit = get('edit') ? q_row("SELECT * FROM amenities WHERE id=?", [(int)get('edit')]) : null;
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card"><h3>Amenities</h3>
    <?php $withPhotos = count(array_filter($rows, fn($r) => $r['active'] && $r['image'] !== '')); ?>
    <p class="hint" style="margin:-4px 0 12px">🌿 <b>Home-page showcase:</b> the <b>latest 6 amenities that have photos</b> appear on the home page (3 per row × 2 rows); visitors click a tile to open the full-screen slider with every photo + a <i>Book Your Stay</i> button.
      <?= $withPhotos ? '<span style="color:#1d7d4d">'.$withPhotos.' amenit'.($withPhotos===1?'y has':'ies have').' photos.</span>' : '<span style="color:#b9600d">No amenity has a photo yet — upload photos below and the showcase appears automatically.</span>' ?></p>
    <table class="tbl"><tr><th>Icon</th><th>Name</th><th>Photos</th><th>Home</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><i class="fa-solid <?= e($r['icon']) ?>" style="color:var(--p);font-size:18px"></i></td>
      <td><b><?= e($r['name']) ?></b></td>
      <td><?php $n = count(array_filter(json_decode($r['images'] ?? '[]', true) ?: [])); echo $r['image'] ? '<img class="imgthumb" src="'.base_url($r['image']).'">'.($n > 1 ? ' <small class="hint">+'.($n-1).'</small>' : '') : '<span style="color:#b9600d">no photo</span>'; ?></td>
      <td><?= $r['show_home'] ? '✔' : '—' ?></td>
      <td><span class="badge <?= $r['active']?'green':'red' ?>"><?= $r['active']?'active':'hidden' ?></span></td>
      <td><a class="btn sm ghost" href="?m=amenities&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Amenity' : 'Add Amenity' ?></h3>
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required>
      <label>Font Awesome icon class</label><input class="input" name="icon" value="<?= e($edit['icon'] ?? 'fa-star') ?>" placeholder="fa-wifi">
      <p class="hint">Find icons at fontawesome.com/icons (e.g. fa-wifi, fa-person-swimming, fa-dumbbell)</p>
      <label>Description</label><textarea class="input" name="description"><?= e($edit['description'] ?? '') ?></textarea>
      <?php $eImgs = json_decode($edit['images'] ?? '[]', true) ?: []; if ($edit && $edit['image'] !== '' && !in_array($edit['image'], $eImgs, true)) array_unshift($eImgs, $edit['image']); ?>
      <label>Photos <small class="hint">(first = main photo on the home grid; all photos appear in the sliding popup)</small></label>
      <div data-uploader data-name="images[]" data-multiple="1" data-max="12" data-subdir="amenities" data-value="<?= e(json_encode($eImgs)) ?>"></div>
      <div class="frow"><div><label>Sort</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div></div>
      <label class="inline"><input type="checkbox" name="show_home" value="1" <?= !$edit || $edit['show_home']?'checked':'' ?>> Show on home facilities</label>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save</button>
      <?php if ($edit): ?><a class="btn ghost" href="?m=amenities">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
