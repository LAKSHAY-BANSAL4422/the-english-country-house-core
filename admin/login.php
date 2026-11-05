<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
if (current_admin()) redirect(base_url('admin/index.php'));
$err = ''; $ok = false; $mode = get('mode','password'); $otpStep = false; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim(post('email')));
    if (post('mode') === 'otp') {
        $mode = 'otp';
        if (post('otp') !== '') {
            [$okv, $err] = admin_verify_otp($email, post('otp'));
            if ($okv) redirect(base_url('admin/index.php'));
            $otpStep = true;
        } else {
            [$ok, $err] = admin_send_otp($email);
            $otpStep = $ok;
        }
    } else {
        [$okl, $err] = admin_login_attempt($email, post('password'));
        if ($okl) redirect(base_url('admin/index.php'));
    }
}
?><!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>"></head>
<body><div class="login-wrap"><div class="login-box">
  <div style="text-align:center;margin-bottom:22px">
    <i class="fa-solid fa-fan" style="font-size:38px;color:var(--p)"></i>
    <h2 style="margin-top:10px"><?= e(setting('site_name')) ?></h2>
    <p style="color:var(--mut);font-size:13px">Admin &amp; Team Panel</p>
  </div>
  <div class="tabs" style="justify-content:center">
    <a href="?mode=password" class="<?= $mode!=='otp'?'active':'' ?>">🔑 Password</a>
    <a href="?mode=otp" class="<?= $mode==='otp'?'active':'' ?>">✉ Email OTP</a>
  </div>
  <?php if ($err): ?><div class="alert <?= $ok?'ok':'err' ?>"><?= e($err) ?></div><?php endif; ?>
  <?php if ($mode === 'otp'): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="mode" value="otp">
      <?php if (!$otpStep): ?>
        <label>Team / Admin Email</label><input class="input" type="email" name="email" value="<?= e($email) ?>" required autofocus>
        <button class="btn" style="width:100%;margin-top:18px;padding:12px">Send Login Code</button>
      <?php else: ?>
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <label>Enter the 6-digit code sent to <?= e($email) ?></label>
        <input class="input" name="otp" required pattern="[0-9]{6}" maxlength="6" style="letter-spacing:8px;font-size:20px;text-align:center" autofocus autocomplete="one-time-code">
        <button class="btn" style="width:100%;margin-top:18px;padding:12px">Verify &amp; Sign In</button>
        <p style="text-align:center;margin-top:10px;font-size:13px"><a href="?mode=otp">Resend / change email</a></p>
      <?php endif; ?>
    </form>
  <?php else: ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="mode" value="password">
      <label>Email</label><input class="input" type="email" name="email" required autofocus>
      <label>Password</label><input class="input" type="password" name="password" required>
      <button class="btn" style="width:100%;margin-top:18px;padding:12px">Sign In</button>
    </form>
  <?php endif; ?>
</div></div></body></html>
