<?php
/** Social Media Scheduler: accounts + scheduled posts (cron publishes) */
require_once ROOT_PATH.'/lib/social.php';
$flash = ''; $flashOk = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'save_acc':
            $data = ['platform'=>post('platform'),'label'=>post('label'),'access_token'=>post('access_token'),
                     'account_ref'=>post('account_ref'),'extra'=>post('extra') ?: '{}','active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('social_accounts', $data, 'id=?', [(int)post('id')]); $flash='Account saved.'; }
            else { db_insert('social_accounts', $data); $flash='Account connected.'; }
            break;
        case 'del_acc': db_delete('social_accounts','id=?',[(int)post('id')]); $flash='Account removed.'; break;
        case 'save_post':
            $platforms = implode(',', (array)($_POST['platforms'] ?? []));
            if (!$platforms) { $flash='Choose at least one platform.'; $flashOk=false; break; }
            $data = ['platforms'=>$platforms,'content'=>post('content'),'link'=>post('link'),
                     'scheduled_at'=>post('scheduled_at') ?: date('Y-m-d H:i:s'),
                     'status'=>post('status')==='draft'?'draft':'scheduled','created_by'=>(int)$ADMIN['id']];
            $mv = handle_upload('media','social');
            if ($mv) { $data['media'] = $mv; $data['media_type'] = preg_match('/\.(mp4|webm)$/i',$mv) ? 'video' : 'image'; }
            if ((int)post('id')) { db_update('social_posts', $data, 'id=?', [(int)post('id')]); $flash='Post saved.'; }
            else { db_insert('social_posts', $data); $flash='Post scheduled — the cron job will publish it automatically at the set time.'; }
            break;
        case 'del_post': db_delete('social_posts','id=?',[(int)post('id')]); $flash='Post deleted.'; break;
        case 'publish_now':
            $sp = q_row("SELECT * FROM social_posts WHERE id=?", [(int)post('id')]);
            if ($sp) {
                $res = social_publish($sp);
                $okAll = !in_array(false, array_map(fn($r)=>(bool)($r['ok'] ?? false), $res));
                $anyOk = in_array(true, array_map(fn($r)=>(bool)($r['ok'] ?? false), $res));
                db_update('social_posts', ['results'=>json_encode($res),'status'=>$okAll?'published':($anyOk?'partial':'failed'),'published_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$sp['id']]);
                $flash = 'Publish attempted: '.implode(' · ', array_map(fn($k,$r)=>$k.' '.(!empty($r['ok'])?'✅':'❌ '.($r['error'] ?? '')), array_keys($res), $res));
                $flashOk = $okAll;
            }
            break;
    }
}
$tab = get('tab','posts');
$accounts = q_all("SELECT * FROM social_accounts ORDER BY platform");
$icons = ['facebook'=>'fa-facebook-f','instagram'=>'fa-instagram','linkedin'=>'fa-linkedin-in','x'=>'fa-x-twitter'];
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs">
  <a href="?m=social&tab=posts" class="<?= $tab==='posts'?'active':'' ?>">📅 Scheduled Posts</a>
  <a href="?m=social&tab=accounts" class="<?= $tab==='accounts'?'active':'' ?>">🔗 Connected Accounts</a>
</div>

<?php if ($tab === 'accounts'):
$edit = get('edit') ? q_row("SELECT * FROM social_accounts WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2">
  <div class="card"><h3>Connected Accounts</h3>
    <table class="tbl"><tr><th>Platform</th><th>Label</th><th>Ref</th><th></th></tr>
    <?php foreach ($accounts as $a): ?>
    <tr><td><i class="fa-brands <?= $icons[$a['platform']] ?>"></i> <?= e($a['platform']) ?><?= $a['active']?'':' <span class="badge red">off</span>' ?></td>
      <td><?= e($a['label']) ?></td><td style="font-size:12px"><?= e(mb_substr($a['account_ref'],0,26)) ?></td>
      <td><a class="btn sm ghost" href="?m=social&tab=accounts&edit=<?= $a['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove account?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_acc"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
    <div class="tip" style="margin-top:12px"><b>How to connect:</b><br>
      <b>Instagram</b> — Ref = IG Business user ID, Token = token with instagram_content_publish.<br>
      <b>LinkedIn</b> — Ref = urn:li:organization:ID, Token = OAuth token with w_member_social / w_organization_social.<br>
      <b>X</b> — Token = Access Token; Extra JSON = {"consumer_key":"...","consumer_secret":"...","token_secret":"..."}
    </div>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Account' : 'Connect Account' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_acc"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Platform</label><select class="input" name="platform">
        <?php foreach (['instagram','linkedin','x'] as $p): ?><option <?= ($edit['platform'] ?? '')===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select>
      <label>Label</label><input class="input" name="label" value="<?= e($edit['label'] ?? '') ?>" placeholder="Main page">
      <label>Account Ref (IG user ID / URN)</label><input class="input" name="account_ref" value="<?= e($edit['account_ref'] ?? '') ?>">
      <label>Access Token</label><textarea class="input" name="access_token" style="min-height:70px"><?= e($edit['access_token'] ?? '') ?></textarea>
      <label>Extra (JSON, for X keys)</label><textarea class="input" name="extra" style="min-height:56px;font-family:monospace"><?= e($edit['extra'] ?? '') ?></textarea>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save</button></form>
  </div>
</div>

<?php else:
$posts = q_all("SELECT * FROM social_posts ORDER BY scheduled_at DESC LIMIT 100");
$edit = get('edit') ? q_row("SELECT * FROM social_posts WHERE id=?", [(int)get('edit')]) : null;
$editPf = $edit ? explode(',', $edit['platforms']) : []; ?>
<div class="grid2" style="grid-template-columns:1.4fr 1fr">
  <div class="card"><h3>Post Queue</h3>
    <p class="hint">Cron job <code>cron/run.php</code> publishes due posts automatically every few minutes.</p>
    <table class="tbl"><tr><th>Content</th><th>Platforms</th><th>Scheduled</th><th>Status</th><th></th></tr>
    <?php foreach ($posts as $p): $res = json_decode($p['results'] ?: '{}', true) ?: []; ?>
    <tr><td style="max-width:260px"><?= e(mb_substr($p['content'],0,80)) ?>…<?= $p['media'] ? ' 📎' : '' ?></td>
      <td><?php foreach (explode(',',$p['platforms']) as $pf): ?><i class="fa-brands <?= $icons[$pf] ?? '' ?>" title="<?= $pf.(isset($res[$pf])?(!empty($res[$pf]['ok'])?' ✅':' ❌ '.($res[$pf]['error'] ?? '')):'') ?>" style="margin-right:6px;color:<?= isset($res[$pf]) ? (!empty($res[$pf]['ok'])?'#1d7d4d':'#c0392b') : 'inherit' ?>"></i><?php endforeach; ?></td>
      <td><?= date('d M h:i A', strtotime($p['scheduled_at'])) ?></td>
      <td><span class="badge <?= ['published'=>'green','failed'=>'red','partial'=>'orange','scheduled'=>'blue','draft'=>'gray','publishing'=>'orange'][$p['status']] ?>"><?= e($p['status']) ?></span></td>
      <td><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="publish_now"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm sec" title="Publish now">▶</button></form>
        <a class="btn sm ghost" href="?m=social&edit=<?= $p['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete post?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_post"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Post' : 'Schedule a Post' ?></h3>
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="do" value="save_post"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Platforms</label>
      <div><?php foreach (['instagram','linkedin','x'] as $p): ?>
        <label class="inline"><input type="checkbox" name="platforms[]" value="<?= $p ?>" <?= in_array($p,$editPf)?'checked':'' ?>> <i class="fa-brands <?= $icons[$p] ?>"></i> <?= $p ?></label><?php endforeach; ?></div>
      <label>Content</label><textarea class="input" name="content" style="min-height:120px" required><?= e($edit['content'] ?? '') ?></textarea>
      <label>Link (optional)</label><input class="input" name="link" value="<?= e($edit['link'] ?? '') ?>">
      <label>Image / Video</label><input class="input" type="file" name="media" accept="image/*,video/mp4,video/webm">
      <?php if (!empty($edit['media'])): ?><p class="hint">Current: <?= e($edit['media']) ?></p><?php endif; ?>
      <div class="frow"><div><label>Publish at</label><input class="input" type="datetime-local" name="scheduled_at" value="<?= $edit ? date('Y-m-d\TH:i', strtotime($edit['scheduled_at'])) : '' ?>"></div>
      <div><label>Status</label><select class="input" name="status"><option value="scheduled" <?= ($edit['status'] ?? '')==='scheduled'?'selected':'' ?>>Scheduled</option><option value="draft" <?= ($edit['status'] ?? '')==='draft'?'selected':'' ?>>Draft</option></select></div></div>
      <button class="btn" style="margin-top:12px">Save Post</button></form>
  </div>
</div>
<?php endif; ?>
