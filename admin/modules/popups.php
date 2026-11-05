<?php
/** Promotional popups */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $data = ['title'=>post('title'),'content'=>$_POST['content'] ?? '','form_id'=>(int)post('form_id') ?: null,
          'delay_seconds'=>(int)post('delay_seconds'),'pageview_count'=>max(1,(int)post('pageview_count')),
          'duration_seconds'=>(int)post('duration_seconds'),'date_from'=>post('date_from') ?: null,'date_to'=>post('date_to') ?: null,
          'pages'=>post('pages'),'show_once_hours'=>(int)post('show_once_hours'),'active'=>(int)(bool)post('active')];
        $data['image'] = preg_replace('#[^A-Za-z0-9_./-]#', '', post('image'));
        if ((int)post('id')) { db_update('popups', $data, 'id=?', [(int)post('id')]); $flash='Popup saved.'; }
        else { db_insert('popups', $data); $flash='Popup created.'; }
    }
    if (post('do') === 'delete') { db_delete('popups','id=?',[(int)post('id')]); $flash='Deleted.'; }
}
$rows = q_all("SELECT p.*, f.name form_name FROM popups p LEFT JOIN forms f ON f.id=p.form_id ORDER BY p.id DESC");
$forms = q_all("SELECT id,name FROM forms WHERE active=1");
$edit = get('edit') ? q_row("SELECT * FROM popups WHERE id=?", [(int)get('edit')]) : null;
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2">
  <div class="card"><h3>Popups</h3>
    <table class="tbl"><tr><th>Title</th><th>Trigger</th><th>Dates</th><th>Form</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><b><?= e($r['title']) ?></b></td>
      <td><?= $r['delay_seconds'] ?>s delay · <?= $r['pageview_count'] ?> pv<?= $r['duration_seconds'] ? ' · closes '.$r['duration_seconds'].'s' : '' ?></td>
      <td><?= $r['date_from'] ? date('d M',strtotime($r['date_from'])).'–'.date('d M',strtotime($r['date_to'])) : 'Always' ?></td>
      <td><?= e($r['form_name'] ?: '—') ?></td>
      <td><span class="badge <?= $r['active']?'green':'red' ?>"><?= $r['active']?'live':'off' ?></span></td>
      <td><a class="btn sm ghost" href="?m=popups&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete popup?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Popup' : 'New Popup' ?></h3>
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Title</label><input class="input" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
      <label>Content (visual editor)</label><textarea class="input wysiwyg" name="content" data-minh="110px"><?= e($edit['content'] ?? '') ?></textarea>
      <label>Image</label><div data-uploader data-name="image" data-subdir="popups" data-value="<?= e($edit['image'] ?? '') ?>"></div>
      <div class="frow">
        <div><label>Attach Form</label><select class="input" name="form_id"><option value="0">None</option>
          <?php foreach ($forms as $f): ?><option value="<?= $f['id'] ?>" <?= ($edit['form_id'] ?? '')==$f['id']?'selected':'' ?>><?= e($f['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Delay (seconds)</label><input class="input" type="number" name="delay_seconds" value="<?= $edit['delay_seconds'] ?? 5 ?>"></div>
        <div><label>Show after N page views</label><input class="input" type="number" name="pageview_count" value="<?= $edit['pageview_count'] ?? 1 ?>"></div>
        <div><label>Auto-close after (s, 0=never)</label><input class="input" type="number" name="duration_seconds" value="<?= $edit['duration_seconds'] ?? 0 ?>"></div>
        <div><label>Start date</label><input class="input" type="date" name="date_from" value="<?= $edit['date_from'] ?? '' ?>"></div>
        <div><label>End date</label><input class="input" type="date" name="date_to" value="<?= $edit['date_to'] ?? '' ?>"></div>
        <div><label>Only on pages (contains, comma sep; blank = all)</label><input class="input" name="pages" value="<?= e($edit['pages'] ?? '') ?>" placeholder="rooms, blog"></div>
        <div><label>Re-show after (hours)</label><input class="input" type="number" name="show_once_hours" value="<?= $edit['show_once_hours'] ?? 24 ?>"></div>
      </div>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save Popup</button></form>
  </div>
</div>
