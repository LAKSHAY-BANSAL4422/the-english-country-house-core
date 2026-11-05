<?php
/** One-click newsletter unsubscribe */
require_once __DIR__.'/config.php'; db();
$email = strtolower(trim(get('email')));
$ok = $email && hash_equals(unsub_token($email), get('t'));
if ($ok) q("UPDATE users SET newsletter=0 WHERE email=?", [$email]);
$PAGE = ['title'=>'Email Preferences | '.setting('site_name')];
include __DIR__.'/includes/header.php';
?>
<div style="height:90px"></div>
<section><div class="container text-center" style="max-width:520px"><div class="card">
  <?php if ($ok): ?>
    <i class="fa-regular fa-envelope-open" style="font-size:50px;color:var(--c-primary)"></i>
    <h2 class="mt-2">You're unsubscribed</h2>
    <p style="color:#68717d"><?= e($email) ?> will no longer receive our weekly emails. Booking confirmations will still be delivered.</p>
  <?php else: ?>
    <h2>Invalid link</h2><p style="color:#68717d">This unsubscribe link is not valid.</p>
  <?php endif; ?>
  <a class="btn btn-primary mt-3" href="<?= base_url() ?>">Back to website</a>
</div></div></section>
<?php include __DIR__.'/includes/footer.php'; ?>
