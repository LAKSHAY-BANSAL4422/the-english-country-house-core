<?php
/** Email-OTP login & registration + Google social login */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
if (current_user()) redirect(base_url('account/index.php'));
$step = 'email'; $msg = ''; $ok = true; $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim(post('email')));
    if (post('otp') !== '') {
        [$ok, $msg] = verify_otp($email, post('otp'));
        if ($ok) redirect(base_url(get('next') ? ltrim(get('next'),'/') : 'account/index.php'));
        $step = 'otp';
    } else {
        [$ok, $msg] = send_otp($email, post('name'));
        $step = $ok ? 'otp' : 'email';
    }
}
if (get('gerr')) { $ok = false; $msg = get('gerr'); }
$_SESSION['oauth_state'] = $_SESSION['oauth_state'] ?? bin2hex(random_bytes(16));
$_SESSION['oauth_next'] = get('next');
$PAGE = ['title'=>'Login / Register | '.setting('site_name')];
include __DIR__.'/../includes/header.php';
?>
<div style="height:90px"></div>
<section>
  <div class="container" style="max-width:460px">
    <div class="card text-center">
      <i class="fa-regular fa-user" style="font-size:40px;color:var(--c-primary)"></i>
      <h2 class="mt-2">Login or Register</h2>
      <p style="color:#8a93a0;font-size:14px">No password needed — we'll email you a 6-digit one-time code.</p>
      <?php if ($msg): ?><div class="alert <?= $ok?'ok':'err' ?> mt-2"><?= e($msg) ?></div><?php endif; ?>
      <?php if (google_login_enabled()): ?>
        <a class="btn-google mt-2" href="<?= e(google_auth_url($_SESSION['oauth_state'])) ?>">
          <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.1 29.4 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.5l6.2 5.2C41.4 34.9 44 29.9 44 24c0-1.3-.1-2.6-.4-3.9z"/></svg>
          Continue with Google
        </a>
        <div class="or-sep"><span>OR EMAIL OTP</span></div>
      <?php endif; ?>
      <form method="post" action="?next=<?= e(get('next')) ?>" class="mt-2" style="text-align:left">
        <?= csrf_field() ?>
        <?php if ($step === 'email'): ?>
          <label>Your Name (new guests)</label>
          <input class="input mt-1 mb-2" type="text" name="name" placeholder="e.g. Sudish Kumar">
          <label class="req">Email Address</label>
          <input class="input mt-1" type="email" name="email" value="<?= e($email) ?>" required placeholder="you@example.com">
          <button class="btn btn-primary mt-2" style="width:100%" type="submit">Send OTP Code</button>
        <?php else: ?>
          <input type="hidden" name="email" value="<?= e($email) ?>">
          <label class="req">Enter the 6-digit code sent to <?= e($email) ?></label>
          <input class="input mt-1" type="text" name="otp" required pattern="[0-9]{6}" maxlength="6" style="letter-spacing:10px;font-size:22px;text-align:center" autofocus autocomplete="one-time-code">
          <button class="btn btn-primary mt-2" style="width:100%" type="submit">Verify &amp; Login</button>
          <p class="text-center mt-2" style="font-size:13px"><a href="<?= base_url('account/login.php') ?>">Use a different email</a></p>
        <?php endif; ?>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__.'/../includes/footer.php'; ?>
