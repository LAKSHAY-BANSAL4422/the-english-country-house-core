<?php
/** Gateway checkout page — dispatches booking to the chosen provider (Razorpay / Stripe / PayPal / Paytm / UPI-QR) with a Pay-at-Hotel fallback */
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/lib/gateways.php';
db(); sess_start();
$booking = q_row("SELECT * FROM bookings WHERE booking_code=?", [get('code')]);
if (!$booking) redirect(u('booking.php'));
if ($booking['payment_status'] === 'paid') redirect(u('booking-done.php?code='.$booking['booking_code']));
$room = q_row("SELECT * FROM rooms WHERE id=?", [(int)$booking['room_id']]);
$gw = $booking['payment_method'];
$gws = gateways_enabled();
$err = get('cancelled') ? 'Payment was cancelled — you can retry below or choose another method.' : '';

/* guest reports a UPI payment → pending verification by the hotel */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('upi_ref')) {
    csrf_check();
    db_insert('booking_payments', ['booking_id'=>(int)$booking['id'],'gateway'=>'upi','txn_id'=>post('upi_ref'),'amount'=>(float)$booking['total'],'status'=>'pending','payload'=>'{}']);
    db_update('bookings', ['payment_status'=>'pending','payment_method'=>'upi'], 'id=?', [(int)$booking['id']]);
    if (setting('booking_notify_email'))
        send_email(setting('booking_notify_email'), '🔎 UPI payment to verify — '.$booking['booking_code'],
            '<p>Guest reports UPI payment of '.money($booking['total']).'. UTR/Ref: <b>'.e(post('upi_ref')).'</b>. Verify in your bank app and confirm the booking in admin.</p>', 'payment');
    redirect(u('booking-done.php?code='.$booking['booking_code'].'&upi=1'));
}

/* guest switches to "Pay at Hotel" (always possible when it is enabled, even if a gateway is down) */
if (get('method') === 'cod' && isset($gws['cod']) && $booking['status'] !== 'cancelled') {
    db_update('bookings', ['payment_method'=>'cod','status'=>'confirmed'], 'id=?', [(int)$booking['id']]);
    $b = q_row("SELECT * FROM bookings WHERE id=?", [(int)$booking['id']]);
    send_booking_confirmation($b, $room, 'Your booking is reserved! 🏡');
    if (setting('booking_notify_email')) send_email(setting('booking_notify_email'), '🔔 New booking (Pay at Hotel) — '.$b['booking_code'], booking_email_summary($b, $room), 'booking');
    redirect(u('booking-done.php?code='.$booking['booking_code']));
}
if (get('method') && get('method') !== 'cod' && array_key_exists(get('method'), $gws)) {
    $gw = get('method');
    db_update('bookings', ['payment_method'=>$gw], 'id=?', [(int)$booking['id']]);
}
/* chosen gateway got switched off meanwhile → fall back to the first available one */
if (!array_key_exists($gw, $gws) || $gw === 'cod') { $alts = array_diff(array_keys($gws), ['cod']); $gw = $alts ? reset($alts) : ($gws ? 'cod' : ''); }
if ($gw === 'cod') redirect(u('pay.php?code='.$booking['booking_code'].'&method=cod'));

$gwData = []; $gwError = '';
if ($gw === 'razorpay') { $gwData = razorpay_create_order($booking); $gwError = $gwData['error'] ?? ''; if (!$gwError) db_insert('booking_payments',['booking_id'=>(int)$booking['id'],'gateway'=>'razorpay','order_ref'=>$gwData['order_id'],'amount'=>(float)$booking['total'],'status'=>'created','payload'=>'{}']); }
if ($gw === 'stripe')   { $gwData = stripe_create_session($booking, $room); $gwError = $gwData['error'] ?? ''; if (!$gwError) redirect($gwData['url']); }
if ($gw === 'paypal')   { $gwData = paypal_create_order($booking); $gwError = $gwData['error'] ?? ''; if (!$gwError) redirect($gwData['url']); }
if ($gw === 'paytm')    { $gwData = paytm_create_txn($booking); $gwError = $gwData['error'] ?? ''; if (!$gwError) db_insert('booking_payments',['booking_id'=>(int)$booking['id'],'gateway'=>'paytm','order_ref'=>$gwData['order_id'],'amount'=>(float)$booking['total'],'status'=>'created','payload'=>'{}']); }
if ($gwError) error_log('[pay] '.$gw.' '.$booking['booking_code'].': '.$gwError);

$PAGE = ['title'=>'Complete Payment | '.setting('site_name'), 'noindex'=>true];
include __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/page_render.php';
$bpg = page_load('booking') ?: ['id'=>0,'title'=>'Book Your Stay','banner_title'=>'Book Your Stay','banner_subtitle'=>'','banner_image'=>'','show_banner'=>1,'content'=>'','sections'=>'[]'];
$bpg['banner_title'] = 'Complete Your Payment'; $bpg['banner_subtitle'] = 'Almost there — step 2 of 2';
render_banner($bpg, ['Booking', 'Payment'], 'rooms_banner');
$lines = json_decode($booking['price_breakup'] ?: '[]', true) ?: [];
?>
<section>
  <div class="container">
    <div class="pay-steps"><span class="done"><i class="fa-solid fa-check"></i> Stay details</span><span class="done"><i class="fa-solid fa-check"></i> Guest details</span><span class="now"><i class="fa-solid fa-credit-card"></i> Payment</span></div>
    <?php if ($err): ?><div class="alert err"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></div><?php endif; ?>
    <?php if ($gwError): ?><div class="alert err"><i class="fa-solid fa-triangle-exclamation"></i> This payment method is temporarily unavailable (<?= e($gwError) ?>). Please choose another option below<?= isset($gws['cod']) ? ' — or simply <b>reserve now &amp; pay at the hotel</b>' : '' ?>.</div><?php endif; ?>
    <div class="grid-2 pay-grid">
      <div class="card reveal-left">
        <h3><i class="fa-solid fa-lock" style="color:var(--c-primary)"></i> Secure payment</h3>
        <p class="price-note">Booking <b><?= e($booking['booking_code']) ?></b> is held for you while you pay.</p>

        <?php if ($gw === 'razorpay' && !$gwError): ?>
          <div class="pay-panel">
            <p>Pay <b><?= money($booking['total']) ?></b> with UPI, cards, net-banking or wallets — the secure Razorpay window opens automatically.</p>
            <button class="btn btn-primary btn-lg mt-2" id="rzpBtn" style="width:100%"><i class="fa-solid fa-bolt"></i> Pay Securely with Razorpay</button>
          </div>
          <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
          <script>
          const rzp = new Razorpay({
            key: '<?= e(setting('pay_razorpay_key')) ?>',
            amount: <?= (int)round($booking['total']*100) ?>, currency: '<?= e(setting('currency','INR')) ?>',
            name: '<?= e(setting('site_name')) ?>', description: '<?= e($room['name']) ?> — <?= $booking['nights'] ?> night(s)',
            order_id: '<?= e($gwData['order_id']) ?>',
            prefill: {name: '<?= e($booking['guest_name']) ?>', email: '<?= e($booking['email']) ?>', contact: '<?= e($booking['phone']) ?>'},
            theme: {color: '<?= e(setting('color_primary')) ?>'},
            modal: {ondismiss: function(){ document.getElementById('rzpBtn').textContent = 'Retry payment with Razorpay'; }},
            handler: function (resp) {
              const fd = new FormData();
              fd.append('gw','razorpay'); fd.append('code','<?= e($booking['booking_code']) ?>');
              fd.append('razorpay_order_id', resp.razorpay_order_id);
              fd.append('razorpay_payment_id', resp.razorpay_payment_id);
              fd.append('razorpay_signature', resp.razorpay_signature);
              fetch('<?= base_url('api/payment_callback.php') ?>', {method:'POST', body: fd})
                .then(r=>r.json()).then(d=>{
                  if (d.ok) location.href = '<?= u('booking-done.php?code='.$booking['booking_code']) ?>';
                  else alert('Verification failed: ' + d.error);
                });
            }
          });
          document.getElementById('rzpBtn').onclick = () => rzp.open();
          setTimeout(()=>rzp.open(), 600);
          </script>

        <?php elseif ($gw === 'paytm' && !$gwError): ?>
          <div class="pay-panel">
            <p>Pay <b><?= money($booking['total']) ?></b> with Paytm wallet, UPI or cards.</p>
            <button class="btn btn-primary btn-lg mt-2" id="ptmBtn" style="width:100%"><i class="fa-solid fa-wallet"></i> Pay with Paytm</button>
          </div>
          <script src="<?= setting('pay_paytm_mode')==='production' ? 'https://securegw.paytm.in' : 'https://securegw-stage.paytm.in' ?>/merchantpgpui/checkoutjs/merchants/<?= e($gwData['mid']) ?>.js" crossorigin="anonymous"></script>
          <script>
          function startPaytm(){
            window.Paytm && window.Paytm.CheckoutJS && window.Paytm.CheckoutJS.init({
              root:'', flow:'DEFAULT',
              data:{orderId:'<?= e($gwData['order_id']) ?>', token:'<?= e($gwData['txn_token']) ?>', tokenType:'TXN_TOKEN', amount:'<?= number_format($booking['total'],2,'.','') ?>'},
              handler:{transactionStatus:function(){}, notifyMerchant:function(){}}
            }).then(()=>window.Paytm.CheckoutJS.invoke());
          }
          document.getElementById('ptmBtn').onclick = startPaytm;
          window.addEventListener('load', ()=>setTimeout(startPaytm, 800));
          </script>

        <?php elseif ($gw === 'upi'): ?>
          <div class="pay-panel upi-pay">
            <?php if (setting('pay_upi_qr')): ?><img src="<?= base_url(setting('pay_upi_qr')) ?>" alt="UPI QR" class="upi-qr-big"><?php endif; ?>
            <div class="upi-steps">
              <p><b>1.</b> Scan the QR with GPay / PhonePe / Paytm / BHIM<?= setting('pay_upi_id') ? ' or pay to <b class="upi-id">'.e(setting('pay_upi_id')).'</b>'.(setting('pay_upi_name') ? ' ('.e(setting('pay_upi_name')).')' : '') : '' ?>.</p>
              <p><b>2.</b> Pay exactly <b><?= money($booking['total']) ?></b>.</p>
              <p><b>3.</b> Enter the UPI reference / UTR number below — we confirm within 30 minutes.</p>
              <?php if (setting('pay_upi_id')): ?>
              <a class="btn btn-primary mt-1 upi-open" href="upi://pay?pa=<?= rawurlencode(setting('pay_upi_id')) ?>&pn=<?= rawurlencode(setting('pay_upi_name') ?: setting('site_name')) ?>&am=<?= number_format((float)$booking['total'], 2, '.', '') ?>&cu=INR&tn=<?= rawurlencode('Booking '.$booking['booking_code']) ?>"><i class="fa-solid fa-mobile-screen"></i> Open UPI App (mobile)</a>
              <?php endif; ?>
            </div>
          </div>
          <form method="post" class="mt-3">
            <?= csrf_field() ?>
            <label class="req">After paying, enter your UPI Transaction / UTR number</label>
            <input class="input mt-1" type="text" name="upi_ref" required minlength="6" placeholder="e.g. 4157 2890 1234">
            <button class="btn btn-primary btn-lg mt-2" type="submit" style="width:100%"><i class="fa-solid fa-circle-check"></i> I Have Paid — Submit For Verification</button>
          </form>

        <?php elseif ($gw === ''): ?>
          <div class="alert err">No online payment method is available right now. Please <a href="<?= u('contact.php') ?>">contact us</a> to complete your booking <b><?= e($booking['booking_code']) ?></b>.</div>
        <?php endif; ?>

        <?php $others = array_filter($gws, fn($k) => $k !== $gw, ARRAY_FILTER_USE_KEY); if ($others): ?>
        <h4 class="mt-3">Or choose a different method</h4>
        <div class="pay-opts mt-1">
          <?php foreach ($others as $k=>$g): ?>
            <a class="pay-opt" href="<?= u('pay.php?code='.$booking['booking_code'].'&method='.$k) ?>">
              <span class="po-icon"><i class="fa-solid <?= e($g['icon']) ?>"></i></span>
              <span class="po-body"><b><?= e($g['label']) ?></b><small><?= e($g['desc'] ?? '') ?></small></span>
              <span class="po-go"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="card reveal-right" style="position:sticky;top:100px">
          <h3>Your Booking</h3>
          <table class="tbl mt-2">
            <tr><th>Room</th><td><?= e($room['name']) ?></td></tr>
            <tr><th>Stay</th><td><?= date('D, d M', strtotime($booking['checkin'])) ?> → <?= date('D, d M Y', strtotime($booking['checkout'])) ?><br><small><?= $booking['nights'] ?> night(s) · <?= $booking['adults'] ?> adult(s)<?= $booking['children'] ? ', '.$booking['children'].' child(ren)' : '' ?></small></td></tr>
            <tr><th>Guest</th><td><?= e($booking['guest_name']) ?><br><small><?= e($booking['email']) ?></small></td></tr>
            <?php foreach ($lines as $l): ?><tr><td><?= !empty($l['date']) ? date('D, d M', strtotime($l['date'])) : '' ?><?= !empty($l['rule']) ? ' <small style="color:var(--c-primary)">('.e($l['rule']).')</small>' : '' ?></td><td style="text-align:right"><?= money((float)($l['total'] ?? 0)) ?></td></tr><?php endforeach; ?>
            <tr><td>Subtotal</td><td style="text-align:right"><?= money($booking['subtotal']) ?></td></tr>
            <tr><td><?= e(setting('tax_label','GST')) ?></td><td style="text-align:right"><?= money($booking['tax']) ?></td></tr>
            <tr><td style="font-size:17px"><b>Total to pay</b></td><td style="text-align:right;font-size:22px;font-family:var(--f-heading)"><b><?= money($booking['total']) ?></b></td></tr>
          </table>
          <p style="font-size:12.5px;color:#98a0ab;margin-top:12px"><i class="fa-solid fa-shield-halved"></i> 256-bit encrypted.</p>
          <p style="font-size:12.5px;color:#98a0ab"><i class="fa-brands fa-whatsapp" style="color:#25d366"></i> Need help? <a href="<?= e(wa_link('', 'Hi! I need help paying for booking '.$booking['booking_code'])) ?>" target="_blank">WhatsApp us</a> or call <a href="tel:<?= e(setting('contact_phone')) ?>"><?= e(setting('contact_phone')) ?></a>.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>
