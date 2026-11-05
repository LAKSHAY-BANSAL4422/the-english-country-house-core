<?php
/** Chat history & oversight: transcripts, agent performance, CSV export */
if (get('export') === 'csv') {
    raw_output(); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=chats-'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Visitor','Phone','Email','Agent','Status','Mode','Rating','FirstResponse(s)','Started','Closed','Transcript'], ",", "\"", "\\");
    foreach (q_all("SELECT vc.*, a.name agent_name FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id ORDER BY vc.id DESC") as $c) {
        $tr = implode(" | ", array_map(fn($m)=>strtoupper($m['sender']).': '.$m['message'],
            q_all("SELECT sender,message FROM visitor_chat_messages WHERE chat_id=? ORDER BY id", [(int)$c['id']])));
        fputcsv($out, [$c['id'],$c['name'],$c['phone'],$c['email'],$c['agent_name'],$c['status'],$c['mode'],$c['rating'],$c['first_response_seconds'],$c['created_at'],$c['closed_at'],$tr], ",", "\"", "\\");
    }
    exit;
}

if (get('view')) {
    $c = q_row("SELECT vc.*, a.name agent_name FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id WHERE vc.id=?", [(int)get('view')]);
    if (!$c) { echo '<div class="alert err">Chat not found</div>'; return; }
    $ms = q_all("SELECT m.*, a.name an FROM visitor_chat_messages m LEFT JOIN admins a ON a.id=m.sender_id WHERE m.chat_id=? ORDER BY m.id", [(int)$c['id']]);
    ?>
    <p><a href="?m=chat_history">← All chats</a>
      <?php if (in_array($c['status'],['open','active'])): ?> · <a class="btn sm" href="?m=chat">Open in live console</a><?php endif; ?></p>
    <div class="grid2" style="grid-template-columns:1.5fr 1fr">
      <div class="card"><h3>Transcript #<?= $c['id'] ?></h3>
        <div style="display:flex;flex-direction:column;gap:8px;background:#f4f6f9;border-radius:10px;padding:16px">
          <?php foreach ($ms as $mm): ?>
            <div class="am <?= $mm['sender'] ?>" style="align-self:<?= $mm['sender']==='agent'?'flex-end':($mm['sender']==='system'?'center':'flex-start') ?>">
              <b style="font-size:10.5px;opacity:.7"><?= $mm['sender']==='agent' ? e($mm['an']) : ucfirst($mm['sender']) ?></b><br><?= nl2br(e($mm['message'])) ?>
              <span class="t"><?= date('d M h:i A', strtotime($mm['created_at'])) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card"><h3>Details</h3>
        <table class="tbl">
          <tr><th>Visitor</th><td><?= e($c['name'] ?: '—') ?> · <?= e($c['phone'] ?: '—') ?><br><?= e($c['email']) ?></td></tr>
          <tr><th>Agent</th><td><?= e($c['agent_name'] ?: '—') ?></td></tr>
          <tr><th>Status</th><td><span class="badge <?= $c['status']==='closed'?'green':($c['status']==='missed'?'red':'blue') ?>"><?= e($c['status']) ?></span> (<?= e($c['mode']) ?>)</td></tr>
          <tr><th>First response</th><td><?= $c['first_response_seconds'] !== null ? $c['first_response_seconds'].'s' : '—' ?></td></tr>
          <tr><th>Rating</th><td><?= $c['rating'] ? str_repeat('⭐', (int)$c['rating']) : '—' ?></td></tr>
          <tr><th>Page / IP</th><td><?= e($c['page']) ?> · <?= e($c['ip']) ?></td></tr>
          <tr><th>Started / Closed</th><td><?= $c['created_at'] ?><br><?= $c['closed_at'] ?: '—' ?></td></tr>
          <tr><th>Lead</th><td><?= $c['lead_id'] ? '<a href="?m=leads&view='.$c['lead_id'].'">Lead #'.$c['lead_id'].' →</a>' : '—' ?></td></tr>
        </table>
      </div>
    </div>
    <?php return;
}

$w = ["1=1"]; $p = [];
if (get('status')) { $w[]="vc.status=?"; $p[]=get('status'); }
if (get('agent')) { $w[]="vc.agent_id=?"; $p[]=(int)get('agent'); }
if (get('q')) { $w[]="(vc.name LIKE ? OR vc.phone LIKE ?)"; $qq='%'.get('q').'%'; array_push($p,$qq,$qq); }
$where = implode(' AND ',$w);
$chats = q_all("SELECT vc.*, a.name agent_name,(SELECT COUNT(*) FROM visitor_chat_messages m WHERE m.chat_id=vc.id) mc FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id WHERE $where ORDER BY vc.id DESC LIMIT 200", $p);
$perf = q_all("SELECT a.name, COUNT(vc.id) chats, AVG(vc.rating) avg_rating, AVG(vc.first_response_seconds) avg_frt
    FROM admins a JOIN visitor_chats vc ON vc.agent_id=a.id GROUP BY a.id ORDER BY chats DESC");
$agents = q_all("SELECT id,name FROM admins ORDER BY name");
?>
<div class="card"><h3>🏆 Agent Performance</h3>
  <table class="tbl"><tr><th>Agent</th><th>Chats handled</th><th>Avg rating</th><th>Avg first response</th></tr>
  <?php foreach ($perf as $pf): ?>
    <tr><td><b><?= e($pf['name']) ?></b></td><td><?= $pf['chats'] ?></td>
    <td><?= $pf['avg_rating'] ? round($pf['avg_rating'],1).' ⭐' : '—' ?></td>
    <td><?= $pf['avg_frt'] !== null ? round($pf['avg_frt']).'s' : '—' ?></td></tr>
  <?php endforeach; if(!$perf): ?><tr><td colspan="4" class="hint">No agent chats yet.</td></tr><?php endif; ?></table>
</div>
<div class="card">
  <form class="filters" method="get"><input type="hidden" name="m" value="chat_history">
    <div><label>Search</label><input class="input" name="q" value="<?= e(get('q')) ?>" placeholder="name / phone"></div>
    <div><label>Status</label><select class="input" name="status"><option value="">All</option>
      <?php foreach (['open','active','closed','missed','whatsapp'] as $s): ?><option <?= get('status')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div><label>Agent</label><select class="input" name="agent"><option value="">All</option>
      <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>" <?= get('agent')==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
    <button class="btn">Filter</button>
    <a class="btn ghost" href="?m=chat_history&export=csv"><i class="fa-solid fa-file-csv"></i> Export (with transcripts)</a>
  </form>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>#</th><th>Visitor</th><th>Agent</th><th>Msgs</th><th>Status</th><th>Rating</th><th>1st resp</th><th>Started</th><th></th></tr>
    <?php foreach ($chats as $c): ?>
    <tr><td><?= $c['id'] ?></td>
      <td><b><?= e($c['name'] ?: 'Visitor') ?></b><br><small class="hint"><?= e($c['phone']) ?></small></td>
      <td><?= e($c['agent_name'] ?: '—') ?></td><td><?= $c['mc'] ?></td>
      <td><span class="badge <?= $c['status']==='closed'?'green':($c['status']==='missed'?'red':($c['status']==='whatsapp'?'purple':'blue')) ?>"><?= e($c['status']) ?></span></td>
      <td><?= $c['rating'] ? $c['rating'].'⭐' : '—' ?></td>
      <td><?= $c['first_response_seconds'] !== null ? $c['first_response_seconds'].'s' : '—' ?></td>
      <td><?= date('d M h:i A', strtotime($c['created_at'])) ?></td>
      <td><a class="btn sm ghost" href="?m=chat_history&view=<?= $c['id'] ?>">Transcript</a></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
