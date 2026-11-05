<?php
/** Admin activity / audit log — who changed what, when (pricing, pages, settings, everything) */
$admins = q_all("SELECT id,name FROM admins ORDER BY name");
$w = ["1=1"]; $p = [];
if (get('admin')) { $w[]="a.admin_id=?"; $p[]=(int)get('admin'); }
if (get('module')) { $w[]="a.module=?"; $p[]=get('module'); }
if (get('q')) { $w[]="(a.detail LIKE ? OR a.action LIKE ?)"; $qq='%'.get('q').'%'; array_push($p,$qq,$qq); }
$where = implode(' AND ', $w);
$pageNo = max(1,(int)get('p',1)); $per=50; $off=($pageNo-1)*$per;
$total = (int)q_val("SELECT COUNT(*) FROM admin_activity a WHERE $where", $p);
$rows = q_all("SELECT a.*, ad.name admin_name FROM admin_activity a LEFT JOIN admins ad ON ad.id=a.admin_id WHERE $where ORDER BY a.id DESC LIMIT $per OFFSET $off", $p);
$modules = q_all("SELECT DISTINCT module FROM admin_activity ORDER BY module");
if (get('export') === 'csv') {
    raw_output(); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=activity-'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Admin','Module','Action','Detail','IP','When'], ",", "\"", "\\");
    foreach (q_all("SELECT a.*, ad.name admin_name FROM admin_activity a LEFT JOIN admins ad ON ad.id=a.admin_id ORDER BY a.id DESC") as $r)
        fputcsv($out, [$r['id'],$r['admin_name'],$r['module'],$r['action'],$r['detail'],$r['ip'],$r['created_at']], ",", "\"", "\\");
    exit;
}
?>
<div class="card">
  <form class="filters" method="get"><input type="hidden" name="m" value="activity">
    <div><label>Team member</label><select class="input" name="admin"><option value="">All</option>
      <?php foreach ($admins as $a): ?><option value="<?= $a['id'] ?>" <?= get('admin')==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
    <div><label>Module</label><select class="input" name="module"><option value="">All</option>
      <?php foreach ($modules as $mo): ?><option <?= get('module')===$mo['module']?'selected':'' ?>><?= e($mo['module']) ?></option><?php endforeach; ?></select></div>
    <div><label>Search</label><input class="input" name="q" value="<?= e(get('q')) ?>"></div>
    <button class="btn">Filter</button>
    <a class="btn ghost" href="?m=activity&export=csv"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
  </form>
  <p class="hint">Every change made in the panel — pricing calendar updates, page edits, settings, bookings, team changes, logins — is recorded here automatically with who did it.</p>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>When</th><th>Team member</th><th>Module</th><th>Action</th><th>Detail</th><th>IP</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td style="white-space:nowrap"><?= date('d M Y h:i A', strtotime($r['created_at'])) ?></td>
      <td><b><?= e($r['admin_name'] ?: '—') ?></b></td>
      <td><span class="badge blue"><?= e($r['module']) ?></span></td>
      <td><span class="badge gray"><?= e($r['action']) ?></span></td>
      <td style="font-size:12.5px;max-width:340px"><?= e($r['detail']) ?></td>
      <td style="font-size:12px"><?= e($r['ip']) ?></td></tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="6" class="hint">No activity yet.</td></tr><?php endif; ?>
  </table></div>
  <?php $pages=(int)ceil($total/$per); if ($pages>1): ?><div class="pagination">
  <?php for($i=1;$i<=min($pages,30);$i++): $qs=$_GET; $qs['p']=$i; ?><a class="<?= $i===$pageNo?'cur':'' ?>" href="?<?= http_build_query($qs) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
</div>
