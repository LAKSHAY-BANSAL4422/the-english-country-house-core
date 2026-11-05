<?php
/** OTA Channel Manager: credentials, room mapping, rate-plan mapping, iCal auto-sync */
require_once ROOT_PATH.'/lib/ical.php';
$flash = ''; $flashOk = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'save_ch':
            $data = ['name'=>post('name'),'hotel_id'=>post('hotel_id'),'username'=>post('username'),
                     'link'=>post('link'),'notes'=>post('notes'),'sync_mode'=>post('sync_mode')==='manual'?'manual':'ical',
                     'active'=>(int)(bool)post('active'),'sort'=>(int)post('sort')];
            if (post('password') !== '') $data['password'] = post('password');
            if ((int)post('id')) { db_update('ota_channels', $data, 'id=?', [(int)post('id')]); $flash='Channel updated.'; }
            else { db_insert('ota_channels', $data); $flash='Channel added.'; }
            break;
        case 'del_ch':
            db_delete('ota_channels','id=?',[(int)post('id')]);
            db_delete('ota_room_map','channel_id=?',[(int)post('id')]);
            db_delete('ota_rate_map','channel_id=?',[(int)post('id')]);
            $flash='Channel removed.'; break;
        case 'save_map':
            $data = ['channel_id'=>(int)post('channel_id'),'room_id'=>(int)post('room_id'),
                     'ota_room_id'=>post('ota_room_id'),'ota_room_name'=>post('ota_room_name'),
                     'ical_import_url'=>post('ical_import_url'),'active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('ota_room_map', $data, 'id=?', [(int)post('id')]); $flash='Mapping updated.'; }
            else { db_insert('ota_room_map', $data); $flash='Room mapped.'; }
            break;
        case 'del_map': db_delete('ota_room_map','id=?',[(int)post('id')]); $flash='Mapping removed.'; break;
        case 'save_rate':
            $data = ['channel_id'=>(int)post('channel_id'),'room_id'=>(int)post('room_id'),
                     'ota_rate_plan_id'=>post('ota_rate_plan_id'),'rate_plan_name'=>post('rate_plan_name'),
                     'price_mode'=>post('price_mode'),'value'=>(float)post('value'),'active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('ota_rate_map', $data, 'id=?', [(int)post('id')]); $flash='Rate plan updated.'; }
            else { db_insert('ota_rate_map', $data); $flash='Rate plan mapped.'; }
            break;
        case 'del_rate': db_delete('ota_rate_map','id=?',[(int)post('id')]); $flash='Rate plan removed.'; break;
        case 'sync_now':
            $log = ical_sync_all();
            $flash = $log ? '🔄 Sync complete: '.implode(' · ', $log) : 'No iCal import URLs configured yet — add them under Room Mapping.';
            break;
    }
}
$tab = get('tab','credentials');
$channels = q_all("SELECT c.*,(SELECT COUNT(*) FROM ota_room_map m WHERE m.channel_id=c.id) maps FROM ota_channels c ORDER BY c.sort, c.id");
$rooms = q_all("SELECT id,name,slug,ical_token,base_price FROM rooms ORDER BY sort");
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs">
  <a href="?m=ota&tab=credentials" class="<?= $tab==='credentials'?'active':'' ?>">🔑 Credentials</a>
  <a href="?m=ota&tab=rooms" class="<?= $tab==='rooms'?'active':'' ?>">🛏 Room Mapping</a>
  <a href="?m=ota&tab=rates" class="<?= $tab==='rates'?'active':'' ?>">💰 Rate Plan Mapping</a>
  <a href="?m=ota&tab=sync" class="<?= $tab==='sync'?'active':'' ?>">🔄 Calendar Sync</a>
</div>

<?php if ($tab === 'credentials'):
$edit = get('edit') ? q_row("SELECT * FROM ota_channels WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card"><h3>Online Travel Agent Details of <?= e(strtoupper(setting('site_name'))) ?></h3>
    <div class="tbl-wrap"><table class="tbl">
    <tr><th>#</th><th>Channel Name</th><th>Hotel ID</th><th>UserName</th><th>Password</th><th>Link</th><th></th></tr>
    <?php foreach ($channels as $i=>$c): ?>
    <tr><td><?= $i+1 ?></td>
      <td><b><?= e($c['name']) ?></b><?= $c['active']?'':' <span class="badge red">off</span>' ?><br><small class="hint"><?= $c['maps'] ?> room(s) mapped<?= $c['last_sync'] ? ' · synced '.date('d M h:i A', strtotime($c['last_sync'])) : '' ?></small></td>
      <td style="font-size:12.5px"><?= e($c['hotel_id'] ?: '—') ?></td>
      <td style="font-size:12.5px"><?= e($c['username'] ?: '—') ?></td>
      <td style="font-size:12.5px"><span class="pw-mask" data-pw="<?= e($c['password']) ?>">••••••</span>
        <?php if ($c['password']): ?><a href="#" onclick="const s=this.previousElementSibling; s.textContent = s.textContent==='••••••' ? s.dataset.pw : '••••••'; return false" title="show/hide">👁</a><?php endif; ?></td>
      <td><?= $c['link'] ? '<a href="'.e($c['link']).'" target="_blank">'.e(parse_url($c['link'], PHP_URL_HOST) ?: 'Open').'</a>' : '—' ?></td>
      <td><a class="btn sm ghost" href="?m=ota&tab=credentials&edit=<?= $c['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove this channel and its mappings?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_ch"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table></div>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit — '.e($edit['name']) : 'Add Platform' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_ch"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Channel Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="e.g. Booking.com" list="otaNames">
      <datalist id="otaNames"><option>Expedia</option><option>Airbnb</option><option>Goibibo / MakeMyTrip</option><option>Booking.com</option><option>Agoda</option><option>Cleartrip</option><option>EaseMyTrip</option><option>Desiya</option><option>Yatra</option><option>Trivago</option><option>Hostelworld</option><option>TripAdvisor</option></datalist>
      <div class="frow">
        <div><label>Hotel ID</label><input class="input" name="hotel_id" value="<?= e($edit['hotel_id'] ?? '') ?>"></div>
        <div><label>UserName</label><input class="input" name="username" value="<?= e($edit['username'] ?? '') ?>"></div>
        <div><label>Password <?= $edit && $edit['password'] ? '(blank = keep)' : '' ?></label><input class="input" name="password" autocomplete="new-password"></div>
        <div><label>Extranet / Login Link</label><input class="input" name="link" value="<?= e($edit['link'] ?? '') ?>"></div>
        <div><label>Availability sync</label><select class="input" name="sync_mode">
          <option value="ical" <?= ($edit['sync_mode'] ?? 'ical')==='ical'?'selected':'' ?>>iCal auto-sync (recommended)</option>
          <option value="manual" <?= ($edit['sync_mode'] ?? '')==='manual'?'selected':'' ?>>Manual</option></select></div>
        <div><label>Sort</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div>
      </div>
      <label>Notes</label><textarea class="input" name="notes" style="min-height:56px"><?= e($edit['notes'] ?? '') ?></textarea>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save Channel</button>
      <?php if ($edit): ?><a class="btn ghost" href="?m=ota&tab=credentials">Cancel</a><?php endif; ?>
    </form>
    <p class="hint" style="margin-top:10px">🔐 Credentials are stored in your own database and visible only to panel users with OTA permission.</p>
  </div>
</div>

<?php elseif ($tab === 'rooms'):
$maps = q_all("SELECT m.*, c.name ch_name, r.name room_name FROM ota_room_map m JOIN ota_channels c ON c.id=m.channel_id JOIN rooms r ON r.id=m.room_id ORDER BY c.sort, r.sort");
$edit = get('edit') ? q_row("SELECT * FROM ota_room_map WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card"><h3>Room Mapping (your room ↔ OTA room)</h3>
    <div class="tbl-wrap"><table class="tbl">
    <tr><th>Channel</th><th>Your Room</th><th>OTA Room ID / Name</th><th>iCal import</th><th></th></tr>
    <?php foreach ($maps as $mp): ?>
    <tr><td><b><?= e($mp['ch_name']) ?></b></td><td><?= e($mp['room_name']) ?></td>
      <td style="font-size:12.5px"><?= e($mp['ota_room_id']) ?><br><small class="hint"><?= e($mp['ota_room_name']) ?></small></td>
      <td><?= $mp['ical_import_url'] ? '<span class="badge green">auto-sync</span>' : '<span class="badge gray">—</span>' ?></td>
      <td><a class="btn sm ghost" href="?m=ota&tab=rooms&edit=<?= $mp['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove mapping?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_map"><input type="hidden" name="id" value="<?= $mp['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; if(!$maps): ?><tr><td colspan="5" class="hint">No rooms mapped yet.</td></tr><?php endif; ?></table></div>
    <div class="tip" style="margin-top:12px"><b>📤 Give these export links to each OTA</b> ("import calendar") so your direct bookings block their calendar:<br>
      <?php foreach ($rooms as $r): ?>
      <div style="font-size:12px;margin-top:4px"><b><?= e($r['name']) ?>:</b> <code style="word-break:break-all"><?= base_url('ical.php?room='.$r['slug'].'&token='.$r['ical_token']) ?></code></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Mapping' : 'Map a Room' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_map"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Channel</label><select class="input" name="channel_id" required>
        <?php foreach ($channels as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['channel_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <label>Your Room</label><select class="input" name="room_id" required>
        <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>" <?= ($edit['room_id'] ?? '')==$r['id']?'selected':'' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select>
      <div class="frow">
        <div><label>OTA Room ID</label><input class="input" name="ota_room_id" value="<?= e($edit['ota_room_id'] ?? '') ?>"></div>
        <div><label>OTA Room Name</label><input class="input" name="ota_room_name" value="<?= e($edit['ota_room_name'] ?? '') ?>"></div>
      </div>
      <label>OTA iCal export URL (paste from the OTA — their bookings will auto-block your dates)</label>
      <input class="input" name="ical_import_url" value="<?= e($edit['ical_import_url'] ?? '') ?>" placeholder="https://www.airbnb.com/calendar/ical/…ics">
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save Mapping</button></form>
  </div>
</div>

<?php elseif ($tab === 'rates'):
$ratemaps = q_all("SELECT rm.*, c.name ch_name, r.name room_name, r.base_price FROM ota_rate_map rm JOIN ota_channels c ON c.id=rm.channel_id JOIN rooms r ON r.id=rm.room_id ORDER BY c.sort, r.sort");
$edit = get('edit') ? q_row("SELECT * FROM ota_rate_map WHERE id=?", [(int)get('edit')]) : null;
$rateModes = ['same'=>'Same as website price','markup_percent'=>'Website price + % markup','markup_amount'=>'Website price + ₹ amount','discount_percent'=>'Website price − % discount']; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card"><h3>Rate Plan Mapping</h3>
    <div class="tbl-wrap"><table class="tbl">
    <tr><th>Channel</th><th>Room</th><th>Rate Plan</th><th>Pricing</th><th>Today's OTA rate</th><th></th></tr>
    <?php foreach ($ratemaps as $rm):
      $base = (float)$rm['base_price'];
      $ota = match($rm['price_mode']){ 'markup_percent'=>$base*(1+(float)$rm['value']/100), 'markup_amount'=>$base+(float)$rm['value'], 'discount_percent'=>$base*(1-(float)$rm['value']/100), default=>$base }; ?>
    <tr><td><b><?= e($rm['ch_name']) ?></b></td><td><?= e($rm['room_name']) ?></td>
      <td style="font-size:12.5px"><?= e($rm['rate_plan_name'] ?: '—') ?><br><small class="hint"><?= e($rm['ota_rate_plan_id']) ?></small></td>
      <td><?= $rateModes[$rm['price_mode']] ?><?= $rm['price_mode']!=='same' ? ' ('.rtrim(rtrim(number_format($rm['value'],2),'0'),'.') .(str_contains($rm['price_mode'],'percent')?'%':'₹').')' : '' ?></td>
      <td><b><?= money($ota) ?></b></td>
      <td><a class="btn sm ghost" href="?m=ota&tab=rates&edit=<?= $rm['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_rate"><input type="hidden" name="id" value="<?= $rm['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; if(!$ratemaps): ?><tr><td colspan="6" class="hint">No rate plans mapped yet.</td></tr><?php endif; ?></table></div>
    <p class="hint" style="margin-top:10px">💡 Use markups to cover OTA commissions (e.g. +18% on Goibibo) while keeping your website the cheapest — the "Book Direct &amp; Save" pitch. Update prices once here; the calculated OTA rate shows what to enter in each extranet.</p>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Rate Plan' : 'Map a Rate Plan' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_rate"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Channel</label><select class="input" name="channel_id" required>
        <?php foreach ($channels as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['channel_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <label>Room</label><select class="input" name="room_id" required>
        <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>" <?= ($edit['room_id'] ?? '')==$r['id']?'selected':'' ?>><?= e($r['name']) ?> (<?= money($r['base_price']) ?>)</option><?php endforeach; ?></select>
      <div class="frow">
        <div><label>OTA Rate Plan ID</label><input class="input" name="ota_rate_plan_id" value="<?= e($edit['ota_rate_plan_id'] ?? '') ?>"></div>
        <div><label>Rate Plan Name</label><input class="input" name="rate_plan_name" value="<?= e($edit['rate_plan_name'] ?? '') ?>" placeholder="EP / CP / Standard"></div>
        <div><label>Pricing rule</label><select class="input" name="price_mode">
          <?php foreach ($rateModes as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit['price_mode'] ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div><label>Value</label><input class="input" type="number" step="0.01" name="value" value="<?= $edit['value'] ?? 0 ?>"></div>
      </div>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save Rate Plan</button></form>
  </div>
</div>

<?php else: /* sync tab */ ?>
<div class="grid2">
  <div class="card"><h3>🔄 Two-way Calendar Sync (works with every OTA that supports iCal)</h3>
    <ol style="padding-left:20px;font-size:13.5px;line-height:2">
      <li><b>Export (you → OTA):</b> copy each room's iCal export URL (Room Mapping tab) and paste it into the OTA's <i>Import calendar</i> — your direct bookings + blocks instantly appear there.</li>
      <li><b>Import (OTA → you):</b> copy the OTA's iCal export link into the mapping's <i>iCal import URL</i> — the cron pulls it automatically every run and their bookings block your dates here.</li>
      <li><b>Verify:</b> blocked dates from OTAs appear in Pricing Calendar → Blocked Dates with a purple OTA badge, and those dates instantly become unbookable on your website.</li>
    </ol>
    <form method="post" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="do" value="sync_now">
      <button class="btn"><i class="fa-solid fa-rotate"></i> Sync All Channels Now</button>
      <span class="hint"> — also runs automatically with the cron job every few minutes.</span></form>
  </div>
  <div class="card"><h3>Channel status</h3>
    <table class="tbl"><tr><th>Channel</th><th>Mode</th><th>Mapped rooms</th><th>Last sync</th></tr>
    <?php foreach ($channels as $c): ?>
    <tr><td><b><?= e($c['name']) ?></b></td>
      <td><span class="badge <?= $c['sync_mode']==='ical'?'green':'gray' ?>"><?= e($c['sync_mode']) ?></span></td>
      <td><?= $c['maps'] ?></td>
      <td><?= $c['last_sync'] ? date('d M Y h:i A', strtotime($c['last_sync'])) : '—' ?></td></tr>
    <?php endforeach; ?></table>
    <p class="hint" style="margin-top:10px">Direct 2-way API connections (rates+inventory push) require each OTA's partner/channel-manager API agreement. This panel gives you universal iCal availability sync plus a single source of truth for credentials, mappings and rate math for every platform.</p>
  </div>
</div>
<?php endif; ?>
