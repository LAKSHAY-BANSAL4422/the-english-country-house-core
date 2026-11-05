<?php
/** Lead Management: pipeline, filters, detail w/ remarks & follow-ups, mail/WhatsApp, import/export CSV */
$STAGES = ['new'=>'New','contacted'=>'Contacted','follow_up'=>'Follow Up','quoted'=>'Quoted','converted'=>'Converted','lost'=>'Lost'];
$team = q_all("SELECT id,name FROM admins WHERE active=1 ORDER BY name");
$flash = ''; $flashOk = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'add':
            $id = create_lead(['name'=>post('name'),'email'=>post('email'),'phone'=>post('phone'),'source'=>'manual','message'=>post('message')]);
            $flash = 'Lead #'.$id.' added.'; break;
        case 'stage':
            $l = q_row("SELECT * FROM leads WHERE id=?", [(int)post('lead_id')]);
            db_update('leads', ['stage'=>post('stage')], 'id=?', [(int)post('lead_id')]);
            db_insert('lead_activities', ['lead_id'=>(int)post('lead_id'),'admin_id'=>(int)$ADMIN['id'],'type'=>'stage','content'=>'Stage: '.$l['stage'].' → '.post('stage')]);
            $flash = 'Stage updated.'; break;
        case 'assign':
            db_update('leads', ['assigned_to'=>(int)post('assigned_to') ?: null], 'id=?', [(int)post('lead_id')]);
            db_insert('lead_activities', ['lead_id'=>(int)post('lead_id'),'admin_id'=>(int)$ADMIN['id'],'type'=>'action','content'=>'Assigned to team member #'.post('assigned_to')]);
            $flash = 'Assigned.'; break;
        case 'remark':
            db_insert('lead_activities', ['lead_id'=>(int)post('lead_id'),'admin_id'=>(int)$ADMIN['id'],'type'=>post('type','remark'),'content'=>post('content')]);
            $flash = ucfirst(post('type','remark')).' saved.'; break;
        case 'followup':
            db_insert('lead_activities', ['lead_id'=>(int)post('lead_id'),'admin_id'=>(int)$ADMIN['id'],'type'=>'followup','content'=>post('content'),'due_at'=>post('due_at')]);
            $flash = 'Follow-up scheduled.'; break;
        case 'fu_done':
            db_update('lead_activities', ['done'=>1], 'id=?', [(int)post('activity_id')]);
            $flash = 'Follow-up completed.'; break;
        case 'mail':
            $l = q_row("SELECT * FROM leads WHERE id=?", [(int)post('lead_id')]);
            if ($l && $l['email']) {
                $ok = send_email($l['email'], post('subject'), nl2br(e(post('body'))), 'lead');
                db_insert('lead_activities', ['lead_id'=>(int)$l['id'],'admin_id'=>(int)$ADMIN['id'],'type'=>'mail','content'=>'Email: '.post('subject')."\n".post('body')]);
                $flash = $ok ? 'Email sent to '.$l['email'] : 'Email failed — check SMTP settings.'; $flashOk = $ok;
            } else { $flash = 'Lead has no email.'; $flashOk = false; }
            break;
        case 'update':
            db_update('leads', ['name'=>post('name'),'email'=>post('email'),'phone'=>preg_replace('/[^0-9+]/','',post('phone')),'tags'=>post('tags'),'score'=>(int)post('score')], 'id=?', [(int)post('lead_id')]);
            $flash = 'Lead updated.'; break;
        case 'delete':
            db_delete('leads', 'id=?', [(int)post('lead_id')]);
            db_delete('lead_activities', 'lead_id=?', [(int)post('lead_id')]);
            $flash = 'Lead deleted.'; break;
        case 'import':
            if (!empty($_FILES['csv']['tmp_name'])) {
                $fh = fopen($_FILES['csv']['tmp_name'], 'r'); $head = fgetcsv($fh); $n=0;
                $idx = array_flip(array_map(fn($h)=>strtolower(trim($h)), $head ?: []));
                while (($row = fgetcsv($fh)) !== false) {
                    $g = fn($k)=>isset($idx[$k], $row[$idx[$k]]) ? trim($row[$idx[$k]]) : '';
                    if ($g('name')==='' && $g('phone')==='' && $g('email')==='') continue;
                    create_lead(['name'=>$g('name'),'email'=>$g('email'),'phone'=>$g('phone'),'source'=>'import','message'=>$g('message') ?: $g('notes')]);
                    $n++;
                }
                fclose($fh); $flash = "$n leads imported.";
            } else { $flash = 'Choose a CSV file.'; $flashOk = false; }
            break;
    }
}

if (get('export') === 'csv') {
    raw_output(); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=leads-'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Name','Email','Phone','Source','Stage','Assigned','Tags','Score','Message','Created','LastActivity'], ",", "\"", "\\");
    foreach (q_all("SELECT l.*, a.name assigned_name,(SELECT MAX(created_at) FROM lead_activities WHERE lead_id=l.id) last_act FROM leads l LEFT JOIN admins a ON a.id=l.assigned_to ORDER BY l.id DESC") as $r)
        fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['phone'],$r['source'],$r['stage'],$r['assigned_name'],$r['tags'],$r['score'],$r['message'],$r['created_at'],$r['last_act']], ",", "\"", "\\");
    exit;
}

/* ---------- detail view ---------- */
if (get('view')) {
    $l = q_row("SELECT l.*, r.name room_name, a.name assigned_name FROM leads l LEFT JOIN rooms r ON r.id=l.room_id LEFT JOIN admins a ON a.id=l.assigned_to WHERE l.id=?", [(int)get('view')]);
    if (!$l) { echo '<div class="alert err">Lead not found</div>'; return; }
    $acts = q_all("SELECT la.*, a.name admin_name FROM lead_activities la LEFT JOIN admins a ON a.id=la.admin_id WHERE la.lead_id=? ORDER BY la.id DESC", [(int)$l['id']]);
    $booking = $l['booking_id'] ? q_row("SELECT * FROM bookings WHERE id=?", [(int)$l['booking_id']]) : null;
    $icon = ['remark'=>'fa-comment','call'=>'fa-phone','mail'=>'fa-envelope','whatsapp'=>'fa-brands fa-whatsapp','stage'=>'fa-arrows-turn-right','followup'=>'fa-bell','action'=>'fa-bolt','system'=>'fa-robot'];
    ?>
    <?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
    <p><a href="?m=leads">← All leads</a></p>
    <div class="grid2" style="grid-template-columns:1.3fr 1fr">
      <div>
        <div class="card">
          <h3>#<?= $l['id'] ?> — <?= e($l['name'] ?: 'Unknown') ?>
            <span class="badge <?= $l['stage']==='converted'?'green':($l['stage']==='lost'?'red':($l['stage']==='new'?'blue':'orange')) ?>"><?= $STAGES[$l['stage']] ?></span>
            <span class="badge gray"><?= e($l['source']) ?></span></h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="update"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <div class="frow">
              <div><label>Name</label><input class="input" name="name" value="<?= e($l['name']) ?>"></div>
              <div><label>Phone</label><input class="input" name="phone" value="<?= e($l['phone']) ?>"></div>
              <div><label>Email</label><input class="input" name="email" value="<?= e($l['email']) ?>"></div>
              <div><label>Tags (comma separated)</label><input class="input" name="tags" value="<?= e($l['tags']) ?>"></div>
              <div><label>Score (0–100)</label><input class="input" type="number" name="score" value="<?= $l['score'] ?>"></div>
            </div>
            <button class="btn sm" style="margin-top:10px">Save</button>
          </form>
          <p class="hint" style="margin-top:10px">Interest: <?= e($l['room_name'] ?: '—') ?> · Created <?= $l['created_at'] ?><?= $booking ? ' · Booking <a href="?m=bookings&view='.$booking['id'].'"><b>'.$booking['booking_code'].'</b></a>' : '' ?></p>
          <?php if ($l['message']): ?><div class="tip" style="margin-top:8px"><?= nl2br(e($l['message'])) ?></div><?php endif; ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
            <a class="btn green sm" target="_blank" href="<?= e(wa_link($l['phone'], 'Hello '.($l['name'] ?: '').'! Greetings from '.setting('site_name').'. ')) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            <a class="btn ghost sm" href="tel:<?= e($l['phone']) ?>"><i class="fa-solid fa-phone"></i> Call</a>
            <?php if ($booking): ?><a class="btn ghost sm" target="_blank" href="<?= base_url('invoice.php?code='.$booking['booking_code']) ?>"><i class="fa-regular fa-file-lines"></i> Invoice</a><?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this lead permanently?')" style="margin-left:auto"><?= csrf_field() ?>
              <input type="hidden" name="do" value="delete"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
              <button class="btn danger sm">Delete</button></form>
          </div>
        </div>

        <div class="card"><h3>📧 Send Email</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="mail"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <input class="input" name="subject" placeholder="Subject" value="Your enquiry at <?= e(setting('site_name')) ?>" required>
            <textarea class="input" name="body" style="margin-top:8px" required placeholder="Message…">Dear <?= e($l['name']) ?>,

Thank you for your interest in <?= setting('site_name') ?>. </textarea>
            <button class="btn" style="margin-top:8px" <?= $l['email']?'':'disabled title="No email on lead"' ?>>Send Email</button>
          </form>
        </div>

        <div class="card"><h3>🕐 Activity Timeline</h3>
          <?php foreach ($acts as $a): ?>
          <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f0f2f6">
            <i class="fa-solid <?= $icon[$a['type']] ?? 'fa-circle' ?>" style="color:var(--p);margin-top:3px;width:18px"></i>
            <div style="flex:1">
              <div style="font-size:13.5px"><?= nl2br(e($a['content'])) ?></div>
              <div class="hint"><?= e($a['admin_name'] ?: 'System') ?> · <?= date('d M Y h:i A', strtotime($a['created_at'])) ?>
                <?php if ($a['type']==='followup'): ?> · due <?= date('d M h:i A', strtotime($a['due_at'])) ?>
                  <?php if (!$a['done']): ?>
                    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="fu_done"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>"><input type="hidden" name="activity_id" value="<?= $a['id'] ?>">
                    <button class="btn sm green" style="padding:2px 8px">✔ Done</button></form>
                  <?php else: ?><span class="badge green">done</span><?php endif; ?>
                <?php endif; ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <div class="card"><h3>Pipeline Stage</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="stage"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <div style="display:flex;gap:8px"><select class="input" name="stage">
              <?php foreach ($STAGES as $k=>$v): ?><option value="<?= $k ?>" <?= $l['stage']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
            </select><button class="btn">Move</button></div>
          </form>
          <form method="post" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="do" value="assign"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <label>Assign to</label>
            <div style="display:flex;gap:8px"><select class="input" name="assigned_to"><option value="0">— Unassigned —</option>
              <?php foreach ($team as $t): ?><option value="<?= $t['id'] ?>" <?= $l['assigned_to']==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
            </select><button class="btn sec">Assign</button></div>
          </form>
        </div>
        <div class="card"><h3>✍ Add Remark / Log</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="remark"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <select class="input" name="type"><option value="remark">Remark</option><option value="call">Call log</option><option value="whatsapp">WhatsApp log</option><option value="action">Action</option></select>
            <textarea class="input" name="content" style="margin-top:8px" required placeholder="What happened…"></textarea>
            <button class="btn" style="margin-top:8px">Save</button>
          </form>
        </div>
        <div class="card"><h3>⏰ Schedule Follow-up</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="followup"><input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
            <input class="input" type="datetime-local" name="due_at" required>
            <input class="input" name="content" style="margin-top:8px" required placeholder="e.g. Call to confirm dates">
            <button class="btn" style="margin-top:8px">Schedule</button>
          </form>
          <p class="hint">You'll get an alert in the panel when due.</p>
        </div>
      </div>
    </div>
    <?php return;
}

/* ---------- board / list ---------- */
$w = ["1=1"]; $p = [];
if (get('q')) { $w[]="(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)"; $qq='%'.get('q').'%'; array_push($p,$qq,$qq,$qq); }
if (get('stage')) { $w[]="l.stage=?"; $p[]=get('stage'); }
if (get('source')) { $w[]="l.source=?"; $p[]=get('source'); }
if (get('assigned')) { $w[]="l.assigned_to=?"; $p[]=(int)get('assigned'); }
if (get('f')==='due') { $w[]="l.id IN (SELECT lead_id FROM lead_activities WHERE type='followup' AND done=0 AND due_at <= DATE_ADD(NOW(), INTERVAL 1 DAY))"; }
$where = implode(' AND ', $w);
$leads = q_all("SELECT l.*, a.name assigned_name FROM leads l LEFT JOIN admins a ON a.id=l.assigned_to WHERE $where ORDER BY l.updated_at DESC LIMIT 400", $p);
$byStage = []; foreach ($STAGES as $sk=>$sv) $byStage[$sk] = [];
foreach ($leads as $l) $byStage[$l['stage']][] = $l;
$viewMode = get('vw', 'board');
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="card">
  <form class="filters" method="get"><input type="hidden" name="m" value="leads"><input type="hidden" name="vw" value="<?= e($viewMode) ?>">
    <div><label>Search</label><input class="input" name="q" value="<?= e(get('q')) ?>" placeholder="name / phone / email"></div>
    <div><label>Stage</label><select class="input" name="stage"><option value="">All</option><?php foreach ($STAGES as $k=>$v): ?><option value="<?= $k ?>" <?= get('stage')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div><label>Source</label><select class="input" name="source"><option value="">All</option><?php foreach (['contact','inquiry','chat','popup','booking','manual','import'] as $s): ?><option <?= get('source')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div><label>Assigned</label><select class="input" name="assigned"><option value="">All</option><?php foreach ($team as $t): ?><option value="<?= $t['id'] ?>" <?= get('assigned')==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
    <button class="btn">Filter</button>
    <a class="btn ghost" href="?m=leads&vw=<?= $viewMode==='board'?'list':'board' ?>"><?= $viewMode==='board' ? '☰ List view' : '▦ Board view' ?></a>
    <a class="btn ghost" href="?m=leads&export=csv"><i class="fa-solid fa-file-export"></i> Export</a>
    <a class="btn ghost" href="#" onclick="document.getElementById('impModal').classList.add('show');return false"><i class="fa-solid fa-file-import"></i> Import</a>
    <a class="btn sec" href="#" onclick="document.getElementById('addModal').classList.add('show');return false">+ Add Lead</a>
  </form>

  <?php if ($viewMode === 'board'): ?>
  <p class="hint" style="margin:4px 0 10px"><i class="fa-solid fa-hand"></i> Drag any card into another column to move the lead through your pipeline — it saves instantly. Click a card to open the lead.</p>
  <div class="pipeline" id="pipeBoard">
    <?php foreach ($STAGES as $sk=>$sv): ?>
    <div class="pipe-col" data-stage="<?= $sk ?>"><h4><?= $sv ?> <span class="pc-count"><?= count($byStage[$sk]) ?></span></h4>
      <div class="pipe-drop" data-stage="<?= $sk ?>">
      <?php foreach ($byStage[$sk] as $l): ?>
      <div class="pipe-card" draggable="true" data-id="<?= $l['id'] ?>" data-href="?m=leads&view=<?= $l['id'] ?>">
        <b><?= e($l['name'] ?: 'Unknown') ?></b>
        <span class="hint"><?= e($l['phone']) ?></span><br>
        <span class="badge gray" style="font-size:10px"><?= e($l['source']) ?></span>
        <?php if ($l['assigned_name']): ?><span class="badge purple" style="font-size:10px"><?= e(explode(' ',$l['assigned_name'])[0]) ?></span><?php endif; ?>
        <div class="hint" style="margin-top:4px"><?= date('d M', strtotime($l['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <script>
  (function () {
    const board = document.getElementById('pipeBoard');
    if (!board) return;
    let dragCard = null, wasDragged = false;

    board.addEventListener('dragstart', e => {
      const card = e.target.closest('.pipe-card');
      if (!card) return;
      dragCard = card; wasDragged = true;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', card.dataset.id); } catch (err) {}
    });
    board.addEventListener('dragend', () => {
      if (dragCard) dragCard.classList.remove('dragging');
      board.querySelectorAll('.pipe-drop.over').forEach(z => z.classList.remove('over'));
      dragCard = null;
      setTimeout(() => { wasDragged = false; }, 150);
    });
    board.querySelectorAll('.pipe-drop').forEach(zone => {
      zone.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; zone.classList.add('over'); });
      zone.addEventListener('dragleave', e => { if (!zone.contains(e.relatedTarget)) zone.classList.remove('over'); });
      zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('over');
        if (!dragCard) return;
        const card = dragCard, from = card.closest('.pipe-drop'), to = zone;
        if (from === to) return;
        to.prepend(card);
        card.classList.add('dropped');
        setTimeout(() => card.classList.remove('dropped'), 600);
        updateCounts();
        const fd = new FormData();
        fd.append('csrf', ADMIN.csrf);
        fd.append('action', 'lead_stage');
        fd.append('lead_id', card.dataset.id);
        fd.append('stage', to.dataset.stage);
        fetch(ADMIN.base + '/admin/ajax.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(d => {
            if (d.ok) { toast('✅ Lead #' + card.dataset.id + ' moved to <b>' + (d.stage_label || to.dataset.stage) + '</b>', 'fa-arrows-turn-right'); }
            else { toast('⚠ ' + (d.error || 'Could not save — refresh and retry'), 'fa-triangle-exclamation'); from.prepend(card); updateCounts(); }
          })
          .catch(() => { toast('⚠ Network error — move not saved', 'fa-triangle-exclamation'); from.prepend(card); updateCounts(); });
      });
    });
    board.addEventListener('click', e => {
      const card = e.target.closest('.pipe-card');
      if (card && !wasDragged) location = card.dataset.href;
    });
    function updateCounts() {
      board.querySelectorAll('.pipe-col').forEach(col => {
        const n = col.querySelectorAll('.pipe-card').length;
        const c = col.querySelector('.pc-count');
        if (c) c.textContent = n;
      });
    }
  })();
  </script>
  <?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>#</th><th>Name</th><th>Contact</th><th>Source</th><th>Stage</th><th>Assigned</th><th>Created</th><th></th></tr>
    <?php foreach ($leads as $l): ?>
    <tr><td><?= $l['id'] ?></td><td><b><?= e($l['name'] ?: '—') ?></b></td>
      <td><?= e($l['phone']) ?><br><small class="hint"><?= e($l['email']) ?></small></td>
      <td><span class="badge gray"><?= e($l['source']) ?></span></td>
      <td><span class="badge <?= $l['stage']==='converted'?'green':($l['stage']==='lost'?'red':($l['stage']==='new'?'blue':'orange')) ?>"><?= $STAGES[$l['stage']] ?></span></td>
      <td><?= e($l['assigned_name'] ?: '—') ?></td><td><?= date('d M Y', strtotime($l['created_at'])) ?></td>
      <td><a class="btn sm ghost" href="?m=leads&view=<?= $l['id'] ?>">Open</a>
          <a class="btn sm green" target="_blank" href="<?= e(wa_link($l['phone'])) ?>"><i class="fa-brands fa-whatsapp"></i></a></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>

<div class="modalx" id="addModal"><div class="mbox"><button class="mclose" onclick="this.closest('.modalx').classList.remove('show')">&times;</button>
  <h3>Add Lead</h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="add">
    <div class="frow"><div><label>Name</label><input class="input" name="name" required></div>
    <div><label>Phone</label><input class="input" name="phone"></div>
    <div><label>Email</label><input class="input" name="email"></div></div>
    <label>Message / Notes</label><textarea class="input" name="message"></textarea>
    <button class="btn" style="margin-top:12px">Add Lead</button></form>
</div></div>
<div class="modalx" id="impModal"><div class="mbox"><button class="mclose" onclick="this.closest('.modalx').classList.remove('show')">&times;</button>
  <h3>Import Leads (CSV)</h3>
  <p class="hint">Header row required. Recognised columns: <b>name, phone, email, message</b>. Duplicate phones/emails (open leads within 30 days) get merged automatically.</p>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="do" value="import">
    <input class="input" type="file" name="csv" accept=".csv" required>
    <button class="btn" style="margin-top:12px">Import</button></form>
</div></div>
