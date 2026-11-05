<?php
require_once __DIR__.'/config.php'; db();
$booking = q_row("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.booking_code=?", [get('code')]);
if (!$booking) redirect(base_url());
$upi = get('upi');
$PAGE = ['title'=>'Booking '.($booking['payment_status']==='paid'?'Confirmed':'Received').' | '.setting('site_name')];
include __DIR__.'/includes/header.php';
?>
<div style="height:90px"></div>
<section>
  <div class="container text-center" style="max-width:640px">
    <div class="card">
      <?php if ($booking['payment_status'] === 'paid'): ?>
        <i class="fa-solid fa-circle-check" style="font-size:64px;color:#2e8b57"></i>
        <h2 class="mt-2">Booking Confirmed! 🎉</h2>
        <p style="color:#68717d">A confirmation email with your invoice has been sent to <b><?= e($booking['email']) ?></b>.</p>
      <?php elseif ($upi): ?>
        <i class="fa-solid fa-hourglass-half" style="font-size:64px;color:var(--c-primary)"></i>
        <h2 class="mt-2">Payment Under Verification</h2>
        <p style="color:#68717d">Thank you! We are verifying your UPI payment — you'll receive a confirmation email at <b><?= e($booking['email']) ?></b> shortly (usually within 30 minutes).</p>
      <?php else: ?>
        <i class="fa-solid fa-circle-check" style="font-size:64px;color:var(--c-primary)"></i>
        <h2 class="mt-2">Booking Reserved!</h2>
        <p style="color:#68717d">Your stay is reserved — payment of <b><?= money($booking['total']) ?></b> is due at check-in. A confirmation email has been sent to <b><?= e($booking['email']) ?></b>.</p>
      <?php endif; ?>
      <table class="tbl mt-3" style="text-align:left">
        <tr><th>Booking ID</th><td><b><?= e($booking['booking_code']) ?></b></td></tr>
        <tr><th>Room</th><td><?= e($booking['room_name']) ?></td></tr>
        <tr><th>Stay</th><td><?= date('D, d M Y', strtotime($booking['checkin'])) ?> → <?= date('D, d M Y', strtotime($booking['checkout'])) ?> (<?= $booking['nights'] ?> nights)</td></tr>
        <tr><th>Guests</th><td><?= $booking['adults'] ?> adult(s)<?= $booking['children'] ? ', '.$booking['children'].' child(ren)' : '' ?></td></tr>
        <tr><th>Total</th><td><?= money($booking['total']) ?> <span class="badge <?= $booking['payment_status']==='paid'?'green':'orange' ?>"><?= e($booking['payment_status']) ?></span></td></tr>
      </table>
      <div class="mt-3">
        <a class="btn btn-primary" href="<?= base_url('invoice.php?code='.$booking['booking_code'].'&e='.md5($booking['booking_code'].APP_SECRET)) ?>" target="_blank"><i class="fa-regular fa-file-lines"></i> View Invoice</a>
        <a class="btn btn-outline" href="<?= base_url('account/index.php') ?>">My Bookings</a>
        <a class="btn" style="background:#25d366;color:#fff" href="<?= e(wa_link('', 'Hi! My booking ID is '.$booking['booking_code'])) ?>" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp Us</a>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>
