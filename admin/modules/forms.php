<?php
/** Form builder + submissions viewer */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $fields = [];
        $labels = (array)($_POST['f_label'] ?? []);
        foreach ($labels as $i => $lbl) {
            if (trim($lbl) === '') continue;
            $fields[] = ['type'=>$_POST['f_type'][$i] ?? 'text','label'=>trim($lbl),
                'name'=>slugify($_POST['f_name'][$i] ?: $lbl) ?: 'field'.$i,
                'required'=>!empty($_POST['f_req'][$i]),'half'=>!empty($_POST['f_half'][$i]),
                'options'=>$_POST['f_opts'][$i] ?? ''];
        }
        $data = ['name'=>post('name'),'fields'=>json_encode($fields, JSON_UNESCAPED_UNICODE),'submit_label'=>post('submit_label') ?: 'Submit',
                 'success_msg'=>post('success_msg'),'email_to'=>post('email_to'),'create_lead'=>(int)(bool)post('create_lead'),'active'=>(int)(bool)post('active')];
        if ((int)post('id')) { db_update('forms', $data, 'id=?', [(int)post('id')]); $flash='Form saved.'; }
        else { db_insert('forms', $data); $flash='Form created.'; }
    }
    if (post('do') === 'delete') { db_delete('forms','id=?',[(int)post('id')]); $flash='Form deleted.'; }
}
if (get('export') === 'csv' && get('form')) {
    $subs = q_all("SELECT * FROM form_submissions WHERE form_id=? ORDER BY id DESC", [(int)get('form')]);
    raw_output(); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=submissions-'.date('Ymd').'.csv');
    $out = fopen('php://output','w'); $keys = [];
    foreach ($subs as $s) $keys = array_unique(array_merge($keys, array_keys(json_decode($s['data'],true) ?: [])));
    fputcsv($out, array_merge(['ID','Date','Page','IP'], $keys), ",", "\"", "\\");
    foreach ($subs as $s) { $d = json_decode($s['data'],true) ?: [];
        fputcsv($out, array_merge([$s['id'],$s['created_at'],$s['page'],$s['ip']], array_map(fn($k)=>$d[$k] ?? '', $keys)), ",", "\"", "\\"); }
    exit;
}
$forms = q_all("SELECT f.*,(SELECT COUNT(*) FROM form_submissions s WHERE s.form_id=f.id) subs FROM forms f ORDER BY f.id");
$edit = get('edit') !== '' && get('edit') !== null ? q_row("SELECT * FROM forms WHERE id=?", [(int)get('edit')]) : null;
$editFields = $edit ? (json_decode($edit['fields'], true) ?: []) : [['type'=>'text','label'=>'Name','name'=>'name','required'=>true,'half'=>true],['type'=>'tel','label'=>'Phone','name'=>'phone','required'=>true,'half'=>true]];
$viewSubs = get('subs') ? q_all("SELECT * FROM form_submissions WHERE form_id=? ORDER BY id DESC LIMIT 200", [(int)get('subs')]) : null;
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>

<?php if ($viewSubs !== null): ?>
<p><a href="?m=forms">← Forms</a> · <a class="btn sm ghost" href="?m=forms&export=csv&form=<?= (int)get('subs') ?>">Export CSV</a></p>
<div class="card"><h3>Submissions</h3>
  <?php foreach ($viewSubs as $s): ?>
    <div style="border:1px solid var(--bd);border-radius:8px;padding:12px;margin-bottom:10px">
      <div class="hint"><?= $s['created_at'] ?> · <?= e($s['page']) ?> · <?= e($s['ip']) ?><?= $s['lead_id'] ? ' · <a href="?m=leads&view='.$s['lead_id'].'">Lead #'.$s['lead_id'].'</a>' : '' ?></div>
      <table class="tbl" style="margin-top:6px"><?php foreach ((json_decode($s['data'],true) ?: []) as $k=>$v): ?>
        <tr><th style="width:180px"><?= e($k) ?></th><td><?= nl2br(e($v)) ?></td></tr><?php endforeach; ?></table>
    </div>
  <?php endforeach; if(!$viewSubs): ?><p class="hint">No submissions yet.</p><?php endif; ?>
</div>
<?php return; endif; ?>

<div class="grid2" style="grid-template-columns:1fr 1.4fr">
  <div class="card"><h3>Forms</h3>
    <table class="tbl"><tr><th>Name</th><th>Fields</th><th>Submissions</th><th></th></tr>
    <?php foreach ($forms as $f): ?>
    <tr><td><b><?= e($f['name']) ?></b><?= $f['active']?'':' <span class="badge red">off</span>' ?></td>
      <td><?= count(json_decode($f['fields'],true) ?: []) ?></td>
      <td><a href="?m=forms&subs=<?= $f['id'] ?>"><?= $f['subs'] ?> →</a></td>
      <td><a class="btn sm ghost" href="?m=forms&edit=<?= $f['id'] ?>">Edit</a>
      <form method="post" style="display:inline" onsubmit="return confirm('Delete form?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $f['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
    <a class="btn" style="margin-top:12px" href="?m=forms&edit=0">+ New Form</a>
    <p class="hint" style="margin-top:10px">Forms can be attached to <b>popups</b>, and the first active form powers the Contact page.</p>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit — '.e($edit['name']) : 'Form Builder' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="frow"><div><label>Form Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div><label>Submit Button Label</label><input class="input" name="submit_label" value="<?= e($edit['submit_label'] ?? 'Submit') ?>"></div>
      <div><label>Notify Email</label><input class="input" name="email_to" value="<?= e($edit['email_to'] ?? '') ?>"></div>
      <div><label>Success Message</label><input class="input" name="success_msg" value="<?= e($edit['success_msg'] ?? 'Thank you!') ?>"></div></div>
      <label class="inline"><input type="checkbox" name="create_lead" value="1" <?= !$edit || $edit['create_lead']?'checked':'' ?>> Create lead from submission</label>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label>
      <h4 style="margin-top:14px">Fields <button type="button" class="btn sm ghost" onclick="addField()">+ Add field</button></h4>
      <div id="fieldRows">
        <?php foreach ($editFields as $i=>$f): ?>
        <div class="frow4" style="border:1px dashed var(--bd);border-radius:8px;padding:8px;margin-top:8px;position:relative">
          <div><label>Label</label><input class="input" name="f_label[<?= $i ?>]" value="<?= e($f['label']) ?>"></div>
          <div><label>Type</label><select class="input" name="f_type[<?= $i ?>]">
            <?php foreach (['text','email','tel','number','date','textarea','select'] as $t): ?><option <?= $f['type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
          <div><label>Field key</label><input class="input" name="f_name[<?= $i ?>]" value="<?= e($f['name']) ?>"></div>
          <div><label>Options (for select)</label><input class="input" name="f_opts[<?= $i ?>]" value="<?= e($f['options'] ?? '') ?>" placeholder="A,B,C"></div>
          <div style="grid-column:1/-1">
            <label class="inline"><input type="checkbox" name="f_req[<?= $i ?>]" value="1" <?= !empty($f['required'])?'checked':'' ?>> Required</label>
            <label class="inline"><input type="checkbox" name="f_half[<?= $i ?>]" value="1" <?= !empty($f['half'])?'checked':'' ?>> Half width</label>
            <button type="button" class="btn sm danger" style="float:right" onclick="this.closest('.frow4').remove()">Remove</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="btn" style="margin-top:14px">Save Form</button>
    </form>
  </div>
</div>
<script>
let fi = <?= count($editFields) ?>;
function addField(){
  const d = document.createElement('div');
  d.className = 'frow4'; d.style.cssText = 'border:1px dashed var(--bd);border-radius:8px;padding:8px;margin-top:8px';
  d.innerHTML = `<div><label>Label</label><input class="input" name="f_label[${fi}]"></div>
    <div><label>Type</label><select class="input" name="f_type[${fi}]"><option>text</option><option>email</option><option>tel</option><option>number</option><option>date</option><option>textarea</option><option>select</option></select></div>
    <div><label>Field key</label><input class="input" name="f_name[${fi}]"></div>
    <div><label>Options</label><input class="input" name="f_opts[${fi}]" placeholder="A,B,C"></div>
    <div style="grid-column:1/-1"><label class="inline"><input type="checkbox" name="f_req[${fi}]" value="1"> Required</label>
    <label class="inline"><input type="checkbox" name="f_half[${fi}]" value="1"> Half width</label>
    <button type="button" class="btn sm danger" style="float:right" onclick="this.closest('.frow4').remove()">Remove</button></div>`;
  document.getElementById('fieldRows').appendChild(d); fi++;
}
</script>
