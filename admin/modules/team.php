<?php
/** Team member management (admin grants access & permissions; team log in via password OR email OTP) */
if ($ADMIN['role'] === 'team') { echo '<div class="alert err">Only admins can manage team members.</div>'; return; }
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $perms = json_encode(array_values(array_intersect(array_keys(perm_list()), (array)($_POST['perms'] ?? []))));
        $data = ['name'=>post('name'),'email'=>strtolower(post('email')),'phone'=>post('phone'),
                 'role'=>in_array(post('role'),['admin','team']) ? post('role') : 'team',
                 'permissions'=>$perms,'active'=>(int)(bool)post('active'),
                 'chat_max'=>max(1,(int)post('chat_max',3))];
        if (post('password') !== '') $data['password'] = password_hash(post('password'), PASSWORD_DEFAULT);
        $id = (int)post('id');
        if ($id) {
            $target = q_row("SELECT role FROM admins WHERE id=?", [$id]);
            if ($target && $target['role'] === 'superadmin') { unset($data['role']); unset($data['active']); }
            db_update('admins', $data, 'id=?', [$id]); $flash = 'Member updated.';
        } else {
            if (post('password') === '') $flash = 'Password required for new member (they can also log in with email OTP).';
            elseif (q_row("SELECT id FROM admins WHERE email=?", [$data['email']])) $flash = 'Email already exists.';
            else { db_insert('admins', $data); $flash = 'Member added — share their login: '.base_url('admin/login.php').' (password or Email-OTP tab)'; }
        }
    }
    if (post('do') === 'delete') {
        $t = q_row("SELECT role FROM admins WHERE id=?", [(int)post('id')]);
        if ($t && $t['role'] !== 'superadmin' && (int)post('id') !== (int)$ADMIN['id']) { db_delete('admins','id=?',[(int)post('id')]); $flash='Member removed.'; }
        else $flash = 'Cannot delete this account.';
    }
}
$members = q_all("SELECT *,(SELECT COUNT(*) FROM visitor_chats vc WHERE vc.agent_id=admins.id AND vc.status='active') live FROM admins ORDER BY role='superadmin' DESC, name");
$edit = get('edit') ? q_row("SELECT * FROM admins WHERE id=?", [(int)get('edit')]) : null;
$editPerms = $edit ? (json_decode($edit['permissions'] ?: '[]', true) ?: []) : array_keys(perm_list());
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.4fr 1fr">
  <div class="card"><h3>Team Members <span class="hint">(all can log in with password or email OTP)</span></h3>
    <table class="tbl"><tr><th>Name</th><th>Role</th><th>Chat</th><th>Status</th><th></th></tr>
    <?php foreach ($members as $mm): $online = $mm['chat_available'] && $mm['last_seen'] && strtotime($mm['last_seen']) > time()-(int)setting('chat_idle_seconds',90); ?>
    <tr><td><b><?= e($mm['name']) ?></b><br><small class="hint"><?= e($mm['email']) ?></small></td>
      <td><span class="badge <?= $mm['role']==='superadmin'?'purple':($mm['role']==='admin'?'blue':'gray') ?>"><?= e($mm['role']) ?></span></td>
      <td><?= $online ? '<span class="badge green">● online ('.$mm['live'].' chats)</span>' : '<span class="badge gray">away</span>' ?></td>
      <td><?= $mm['active'] ? '<span class="badge green">active</span>' : '<span class="badge red">disabled</span>' ?></td>
      <td><a class="btn sm ghost" href="?m=team&edit=<?= $mm['id'] ?>">Edit</a>
        <?php if ($mm['role'] !== 'superadmin' && $mm['id'] != $ADMIN['id']): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove this member?')"><?= csrf_field() ?>
          <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $mm['id'] ?>">
          <button class="btn sm danger">✕</button></form>
        <?php endif; ?></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card">
    <h3><?= $edit ? 'Edit — '.e($edit['name']) : 'Add Team Member' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="frow">
        <div><label>Name</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
        <div><label>Email (login + OTP)</label><input class="input" type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required></div>
        <div><label>Phone</label><input class="input" name="phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
        <div><label>Password <?= $edit?'(leave blank to keep)':'' ?></label><input class="input" type="password" name="password" autocomplete="new-password"></div>
        <div><label>Role</label><select class="input" name="role" <?= ($edit['role'] ?? '')==='superadmin'?'disabled':'' ?>>
          <option value="team" <?= ($edit['role'] ?? 'team')==='team'?'selected':'' ?>>Team (limited access)</option>
          <option value="admin" <?= ($edit['role'] ?? '')==='admin'?'selected':'' ?>>Admin (full access)</option>
        </select></div>
        <div><label>Max concurrent chats</label><input class="input" type="number" name="chat_max" value="<?= (int)($edit['chat_max'] ?? 3) ?>" min="1" max="10"></div>
      </div>
      <label class="inline" style="margin-top:12px"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active'] ? 'checked':'' ?>> Account active</label>
      <label style="margin-top:10px">Module Permissions (team role)</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 12px">
        <?php foreach (perm_list() as $pk=>$pv): ?>
          <label class="inline"><input type="checkbox" name="perms[]" value="<?= $pk ?>" <?= in_array($pk,$editPerms)?'checked':'' ?>> <?= e($pv) ?></label>
        <?php endforeach; ?>
      </div>
      <button class="btn" style="margin-top:14px"><?= $edit ? 'Save Changes' : 'Add Member' ?></button>
      <?php if ($edit): ?><a class="btn ghost" href="?m=team">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
