<?php
/** Pricing rules: seasons, occasions, specials, weekend — calendar-based dynamic pricing (all changes audit-logged) */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $data = ['room_id'=>(int)post('room_id'),'name'=>post('name'),'rule_type'=>post('rule_type'),
          'date_from'=>post('date_from') ?: null,'date_to'=>post('date_to') ?: null,
          'days_of_week'=>implode(',', (array)($_POST['dow'] ?? [])),
          'price_mode'=>post('price_mode'),'value'=>(float)post('value'),
          'extra_adult_price'=>post('extra_adult_price') !== '' ? (float)post('extra_adult_price') : null,
          'child_price'=>post('child_price') !== '' ? (float)post('child_price') : null,
          'min_nights'=>max(1,(int)post('min_nights')),'priority'=>(int)post('priority'),'active'=>(int)(bool)post('active')];
        if ((int)post('id')) { db_update('pricing_rules', $data, 'id=?', [(int)post('id')]); $flash='Rule updated.'; }
        else { db_insert('pricing_rules', $data); $flash='Rule added.'; }
    }
    if (post('do') === 'delete') { db_delete('pricing_rules','id=?',[(int)post('id')]); $flash='Rule deleted.'; }
    if (post('do') === 'block') {
        db_insert('room_blocks', ['room_id'=>(int)post('room_id'),'date_from'=>post('date_from'),'date_to'=>post('date_to'),'source'=>'manual','note'=>post('note')]);
        $flash='Dates blocked.';
    }
    if (post('do') === 'unblock') { db_delete('room_blocks','id=?',[(int)post('id')]); $flash='Block removed.'; }
}
$rules = q_all("SELECT pr.*, r.name room_name FROM pricing_rules pr LEFT JOIN rooms r ON r.id=pr.room_id ORDER BY pr.priority DESC, pr.id DESC");
$rooms = q_all("SELECT id,name FROM rooms ORDER BY sort");
$blocks = q_all("SELECT rb.*, r.name room_name FROM room_blocks rb JOIN rooms r ON r.id=rb.room_id WHERE rb.date_to >= CURDATE() ORDER BY rb.date_from LIMIT 60");
$edit = get('edit') ? q_row("SELECT * FROM pricing_rules WHERE id=?", [(int)get('edit')]) : null;
$editDow = $edit ? explode(',', $edit['days_of_week']) : [];
$modes = ['set'=>'Set fixed price (₹)','percent_inc'=>'Increase by %','percent_dec'=>'Decrease by % (offer)','amount_inc'=>'Increase by ₹','amount_dec'=>'Decrease by ₹'];
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div>
  <div class="card"><h3>Pricing Rules (highest priority wins per night)</h3>
    <div class="tbl-wrap"><table class="tbl">
    <tr><th>Rule</th><th>Type</th><th>Applies</th><th>Dates / Days</th><th>Adjustment</th><th>Prio</th><th></th></tr>
    <?php foreach ($rules as $r): ?>
    <tr><td><b><?= e($r['name']) ?></b><?= $r['min_nights']>1 ? '<br><small class="hint">min '.$r['min_nights'].' nights</small>':'' ?></td>
      <td><span class="badge <?= ['season'=>'blue','occasion'=>'purple','special'=>'green','weekend'=>'orange'][$r['rule_type']] ?>"><?= e($r['rule_type']) ?></span></td>
      <td><?= $r['room_id'] ? e($r['room_name']) : 'All rooms' ?></td>
      <td><?= $r['date_from'] ? date('d M', strtotime($r['date_from'])).' – '.date('d M Y', strtotime($r['date_to'])) : '' ?>
          <?= $r['days_of_week'] !== '' ? '<br><small class="hint">'.implode(', ', array_map(fn($d)=>['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][(int)$d], explode(',',$r['days_of_week']))).'</small>' : '' ?></td>
      <td><?= ['set'=>'= ₹','percent_inc'=>'+','percent_dec'=>'−','amount_inc'=>'+₹','amount_dec'=>'−₹'][$r['price_mode']] ?><?= rtrim(rtrim(number_format($r['value'],2),'0'),'.') ?><?= str_contains($r['price_mode'],'percent')?'%':'' ?></td>
      <td><?= $r['priority'] ?><?= $r['active'] ? '' : ' <span class="badge red">off</span>' ?></td>
      <td><a class="btn sm ghost" href="?m=pricing&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete rule?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table></div>
    <p class="hint" style="margin-top:10px">💡 <b>Season</b> — summer +25% · <b>Occasion</b> — Diwali/New Year set ₹15,000 & min 2 nights · <b>Special</b> — monsoon −15% offer · <b>Weekend</b> — Fri/Sat +20%. Every change is recorded in the Activity Log with your name.</p>
  </div>
  <div class="card"><h3>🚫 Blocked Dates (manual + OTA sync)</h3>
    <table class="tbl"><tr><th>Room</th><th>Dates</th><th>Source</th><th></th></tr>
    <?php foreach ($blocks as $b): ?>
    <tr><td><?= e($b['room_name']) ?></td><td><?= date('d M', strtotime($b['date_from'])) ?> → <?= date('d M Y', strtotime($b['date_to'])) ?></td>
      <td><span class="badge <?= str_starts_with($b['source'],'ota')?'purple':'gray' ?>"><?= e(str_starts_with($b['source'],'ota') ? 'OTA' : $b['source']) ?></span> <small class="hint"><?= e($b['note']) ?></small></td>
      <td><form method="post" style="display:inline" onsubmit="return confirm('Unblock these dates?')"><?= csrf_field() ?><input type="hidden" name="do" value="unblock"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; if(!$blocks): ?><tr><td colspan="4" class="hint">No upcoming blocks.</td></tr><?php endif; ?></table>
    <form method="post" class="filters" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="do" value="block">
      <div><label>Room</label><select class="input" name="room_id"><?php foreach ($rooms as $rm): ?><option value="<?= $rm['id'] ?>"><?= e($rm['name']) ?></option><?php endforeach; ?></select></div>
      <div><label>From</label><input class="input" type="date" name="date_from" required></div>
      <div><label>To</label><input class="input" type="date" name="date_to" required></div>
      <div><label>Note</label><input class="input" name="note" placeholder="maintenance"></div>
      <button class="btn">Block Dates</button>
    </form>
  </div>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Rule' : 'Add Rule' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Rule Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="e.g. Diwali Special">
      <div class="frow">
        <div><label>Type</label><select class="input" name="rule_type">
          <?php foreach (['season','occasion','special','weekend'] as $t): ?><option <?= ($edit['rule_type'] ?? '')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
        <div><label>Room</label><select class="input" name="room_id"><option value="0">All rooms</option>
          <?php foreach ($rooms as $rm): ?><option value="<?= $rm['id'] ?>" <?= ($edit['room_id'] ?? 0)==$rm['id']?'selected':'' ?>><?= e($rm['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>From date</label><input class="input" type="date" name="date_from" value="<?= $edit['date_from'] ?? '' ?>"></div>
        <div><label>To date</label><input class="input" type="date" name="date_to" value="<?= $edit['date_to'] ?? '' ?>"></div>
        <div><label>Adjustment</label><select class="input" name="price_mode">
          <?php foreach ($modes as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit['price_mode'] ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div><label>Value</label><input class="input" type="number" step="0.01" name="value" value="<?= $edit['value'] ?? '' ?>" required></div>
        <div><label>Extra adult ₹ (optional)</label><input class="input" type="number" step="0.01" name="extra_adult_price" value="<?= $edit['extra_adult_price'] ?? '' ?>"></div>
        <div><label>Child ₹ (optional)</label><input class="input" type="number" step="0.01" name="child_price" value="<?= $edit['child_price'] ?? '' ?>"></div>
        <div><label>Min nights</label><input class="input" type="number" name="min_nights" value="<?= $edit['min_nights'] ?? 1 ?>"></div>
        <div><label>Priority (higher wins)</label><input class="input" type="number" name="priority" value="<?= $edit['priority'] ?? 10 ?>"></div>
      </div>
      <label>Days of week (blank = all days in range)</label>
      <div><?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i=>$d): ?>
        <label class="inline"><input type="checkbox" name="dow[]" value="<?= $i ?>" <?= in_array((string)$i,$editDow,true)?'checked':'' ?>> <?= $d ?></label><?php endforeach; ?></div>
      <label class="inline" style="margin-top:8px"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px"><?= $edit ? 'Save' : 'Add Rule' ?></button>
      <?php if ($edit): ?><a class="btn ghost" href="?m=pricing">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
