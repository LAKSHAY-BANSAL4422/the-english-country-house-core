<?php
/** Own profile: name, phone, password, chat settings */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = ['name'=>post('name'),'phone'=>post('phone'),'chat_max'=>max(1,(int)post('chat_max'))];
    if (post('password') !== '') {
        if (strlen(post('password')) < 6) $flash = 'Password must be at least 6 characters.';
        else $data['password'] = password_hash(post('password'), PASSWORD_DEFAULT);
    }
    if (!$flash) { db_update('admins', $data, 'id=?', [(int)$ADMIN['id']]); $flash='Profile saved.'; $ADMIN = q_row("SELECT * FROM admins WHERE id=?", [(int)$ADMIN['id']]); }
}
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="card" style="max-width:520px">
  <h3>My Profile</h3>
  <form method="post"><?= csrf_field() ?>
    <label>Name</label><input class="input" name="name" value="<?= e($ADMIN['name']) ?>" required>
    <label>Email (login — password or email OTP)</label><input class="input" value="<?= e($ADMIN['email']) ?>" disabled>
    <label>Phone</label><input class="input" name="phone" value="<?= e($ADMIN['phone']) ?>">
    <label>New password (blank = keep current)</label><input class="input" type="password" name="password" autocomplete="new-password">
    <label>Max concurrent chats</label><input class="input" type="number" name="chat_max" value="<?= (int)$ADMIN['chat_max'] ?>" min="1" max="10">
    <button class="btn" style="margin-top:14px">Save</button>
  </form>
</div>
