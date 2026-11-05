<?php
/** Customer dashboard: bookings, cancel + auto refund, profile */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../lib/gateways.php';
db(); sess_start();
$u = require_user();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'cancel') {
        $b = q_row("SELECT * FROM bookings WHERE id=? AND (user_id=? OR email=?)", [(int)post('booking_id'), (int)$u['id'], $u['email']]);
        if ($b) { [$okc, $flash] = cancel_booking($b, post('reason','Cancelled by guest'), 'user'); }
    }
    if (post('do') === 'profile' && !phone_check(post('phone'), false)['ok']) {   // round 5: optional phone, checked when filled
        $flash = phone_check(post('phone'), false)['error'];
    } elseif (post('do') === 'profile') {
        db_update('users', ['name'=>post('name'),'phone'=>phone_normalize(post('phone')),'city'=>post('city'),'address'=>post('address'),'newsletter'=>(int)(bool)post('newsletter')], 'id=?', [(int)$u['id']]);
        $flash = 'Profile updated.';
        $u = q_row("SELECT * FROM users WHERE id=?", [(int)$u['id']]);
    }
}
$bookings = q_all("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.user_id=? OR b.email=? ORDER BY b.id DESC", [(int)$u['id'], $u['email']]);
$PAGE = ['title'=>'My Account | '.setting('site_name')];
include __DIR__.'/../includes/header.php';
$badge = fn($s)=>match($s){'confirmed','paid','checked_in'=>'green','cancelled','failed'=>'red','pending','unpaid','refund_initiated'=>'orange','refunded'=>'blue',default=>'gray'};
?>
<div style="height:90px"></div>
<section>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <h2>Hello, <?= e($u['name']) ?> 👋</h2>
      <a class="btn btn-outline btn-sm" href="<?= base_url('account/logout.php') ?>">Logout</a>
    </div>
    <?php if ($flash): ?><div class="alert ok mt-2"><?= e($flash) ?></div><?php endif; ?>

    <?php if (setting('menu_enabled','1') === '1'): ?>
    <a href="<?= base_url('account/food.php') ?>" class="mt-3" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,#163a2c,#21533f);color:#fff;border-radius:18px;padding:18px 22px;box-shadow:0 14px 34px rgba(14,38,29,.3);text-decoration:none">
      <span style="width:58px;height:58px;border-radius:50%;background:rgba(241,223,174,.18);display:flex;align-items:center;justify-content:center;font-size:26px">🍽</span>
      <span style="flex:1;min-width:200px"><b style="font-family:var(--f-heading);font-size:26px;display:block;color:#fff">Food Menu</b>
        <span style="color:#cfe0d6;font-size:14px">Order breakfast, snacks &amp; dinner to your room — your name, phone &amp; room are filled in for you. Approx. <?= (int)setting('menu_wait_minutes','30') ?> min.</span></span>
      <span style="background:#f1dfae;color:#163a2c;font-weight:700;padding:10px 18px;border-radius:30px">Open menu →</span>
    </a>
    <?php endif; ?>

    <div class="grid-2 mt-3" style="grid-template-columns:2fr 1fr">
      <div>
        <h3 class="mb-2">My Bookings</h3>
        <?php if (!$bookings): ?>
          <div class="card"><p>No bookings yet. <a <?= rd_enquiry_attrs() ?>><b>Enquire about your first stay →</b></a></p></div>
        <?php endif; ?>
        <?php foreach ($bookings as $b): $el = refund_eligible($b); ?>
        <div class="card mb-2">
          <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <div>
              <b style="font-size:17px;color:var(--c-heading)"><?= e($b['room_name']) ?></b>
              <span class="badge <?= $badge($b['status']) ?>"><?= e($b['status']) ?></span>
              <span class="badge <?= $badge($b['payment_status']) ?>"><?= e(str_replace('_',' ',$b['payment_status'])) ?></span>
              <p style="font-size:13.5px;color:#68717d;margin-top:6px">
                #<?= e($b['booking_code']) ?> · <?= date('d M Y', strtotime($b['checkin'])) ?> → <?= date('d M Y', strtotime($b['checkout'])) ?> ·
                <?= $b['adults'] ?>A<?= $b['children']?'+'.$b['children'].'C':'' ?> · <b><?= money($b['total']) ?></b></p>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
              <a class="btn btn-outline btn-sm" target="_blank" href="<?= base_url('invoice.php?code='.$b['booking_code'].'&e='.md5($b['booking_code'].APP_SECRET)) ?>">Invoice</a>
              <?php if ($b['payment_status']==='unpaid' && !in_array($b['status'],['cancelled']) && $b['payment_method']!=='cod'): ?>
                <a class="btn btn-primary btn-sm" href="<?= base_url('pay.php?code='.$b['booking_code']) ?>">Pay Now</a>
              <?php endif; ?>
              <?php if (!in_array($b['status'],['cancelled','checked_out','checked_in'])): ?>
                <form method="post" onsubmit="return confirm('<?= $b['payment_status']==='paid' ? ($el['eligible'] ? 'Cancel this booking? '.money($el['amount']).' will be auto-refunded to your payment method.' : 'Cancel this booking? As per policy (within '.$el['required_days'].' days of check-in) the amount is NON-refundable.') : 'Cancel this booking?' ?>')">
                  <?= csrf_field() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                  <button class="btn btn-sm" style="background:#fdecec;color:#c0392b">Cancel</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($b['payment_status']==='paid' && !in_array($b['status'],['cancelled','checked_out'])): ?>
            <p style="font-size:12.5px;color:<?= $el['eligible']?'#1d7d4d':'#c07d1a' ?>;margin-top:8px">
              <i class="fa-solid fa-rotate-left"></i>
              <?= $el['eligible'] ? 'Refund available as per our refund policy — '.$el['days_left'].' days to check-in (refund '.money($el['amount']).' auto-processed).' : 'Non-refundable now (less than '.$el['required_days'].' days to check-in).' ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div>
        <div class="card">
          <h3>My Profile</h3>
          <form method="post" class="mt-2">
            <?= csrf_field() ?><input type="hidden" name="do" value="profile">
            <label>Name</label><input class="input mt-1 mb-2" name="name" value="<?= e($u['name']) ?>" required>
            <label>Email</label><input class="input mt-1 mb-2" value="<?= e($u['email']) ?>" disabled>
            <label>Phone</label><input class="input mt-1 mb-2" type="tel" name="phone" value="<?= e($u['phone']) ?>" inputmode="tel" autocomplete="tel">
            <label>City</label><input class="input mt-1 mb-2" name="city" value="<?= e($u['city']) ?>">
            <label>Address</label><input class="input mt-1 mb-2" name="address" value="<?= e($u['address']) ?>">
            <label style="display:flex;gap:8px;align-items:center;font-weight:400"><input type="checkbox" name="newsletter" value="1" <?= $u['newsletter']?'checked':'' ?>> Send me weekly offers &amp; updates</label>
            <button class="btn btn-primary btn-sm mt-2" type="submit">Save Profile</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__.'/../includes/footer.php'; ?>
