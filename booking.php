<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/lib/gateways.php';
/* REDESIGN (round 4): online booking is replaced by the WhatsApp enquiry form. Anyone who still opens this page
   (old bookmarks, search results, newsletter links) is sent to the home page with the enquiry form open and the room
   preselected. Only this public entry point is disconnected: everything below (booking creation, OTP, payment) is
   unchanged and nothing in the database is touched. Delete these lines to switch online booking back on. */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(keep_query(rtrim(base_url(''), '/').'/?enquire=1'.((int)($_GET['room_id'] ?? 0) ? '&room_id='.(int)$_GET['room_id'] : ''), ['room_id', 'enquire']));
}
db(); sess_start();
$rooms = q_all("SELECT * FROM rooms WHERE active=1 ORDER BY sort,id");
$user = current_user();
$err = '';

/* -------- create booking -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $roomId = (int)post('room_id');
    $email = strtolower(trim(post('email')));
    $quote = quote_stay($roomId, post('checkin'), post('checkout'), max(1,(int)post('adults')), max(0,(int)post('children')));
    /* email verification requirement: logged-in matching email OR OTP-verified this session */
    $emailVerified = ($user && strtolower($user['email']) === $email)
                  || (($_SESSION['verified_email'] ?? '') === $email);
    /* mail server down? never lose a booking — accept it and flag it for a phone confirmation */
    $otpMailFailed = (($_SESSION['otp_mail_failed'] ?? '') === $email);
    if (isset($quote['error'])) $err = $quote['error'];
    elseif (!room_available($roomId, post('checkin'), post('checkout'))) $err = 'Sorry, this room just sold out for your dates. Please pick different dates or another room.';
    elseif (post('guest_name')==='' || !phone_check(post('phone'))['ok'] || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = post('guest_name')!=='' && filter_var($email, FILTER_VALIDATE_EMAIL) ? phone_check(post('phone'))['error'] : 'Please fill your name, valid email and mobile number.';   // round 5: site-wide phone rule
    elseif (setting('booking_otp_required','1') === '1' && !$emailVerified && !$otpMailFailed) $err = 'Please verify your email with the OTP code before confirming the booking.';
    else {
        $method = post('payment_method');
        if (!array_key_exists($method, gateways_enabled())) $err = 'Please choose a payment method.';
        else {
            $user = current_user(); // may have been created by OTP verification
            $code = new_booking_code();
            $bid = db_insert('bookings', [
                'booking_code'=>$code, 'user_id'=>$user['id'] ?? null, 'room_id'=>$roomId,
                'checkin'=>post('checkin'),'checkout'=>post('checkout'),'nights'=>$quote['nights'],
                'adults'=>(int)post('adults'),'children'=>(int)post('children'),
                'guest_name'=>post('guest_name'),'email'=>$email,'phone'=>phone_normalize(post('phone')),
                'address'=>post('address'),'special_request'=>($otpMailFailed && !$emailVerified ? '[Email NOT verified — OTP mail could not be sent, please confirm by phone] ' : '').post('special_request'),
                'price_breakup'=>json_encode($quote['lines']),'subtotal'=>$quote['subtotal'],'tax'=>$quote['tax'],'total'=>$quote['total'],
                'payment_method'=>$method,'payment_status'=>'unpaid','status'=>'pending','source'=>'online',
            ]);
            create_lead(['name'=>post('guest_name'),'email'=>$email,'phone'=>post('phone'),'source'=>'booking','room_id'=>$roomId,'booking_id'=>$bid,
                'message'=>'Online booking '.$code.' ('.post('checkin').' → '.post('checkout').') total '.money($quote['total'])]);
            if ($method === 'cod') {
                db_update('bookings', ['status'=>'confirmed'], 'id=?', [$bid]);
                $b = q_row("SELECT * FROM bookings WHERE id=?", [$bid]);
                $room = q_row("SELECT * FROM rooms WHERE id=?", [$roomId]);
                send_booking_confirmation($b, $room, 'Your booking is reserved! 🏡');
                if (setting('booking_notify_email')) send_email(setting('booking_notify_email'), '🔔 New booking (Pay at Hotel) — '.$code, booking_email_summary($b, $room), 'booking');
                redirect(u('booking-done.php?code='.$code));
            }
            redirect(u('pay.php?code='.$code));
        }
    }
}

$selRoom = (int)get('room_id', post('room_id', 0));
$needOtp = setting('booking_otp_required','1') === '1' && !$user;
$PAGE = ['title'=>'Book Your Stay | '.setting('site_name'),'desc'=>'Check availability, view live seasonal pricing and book your room online at The English Country House, Mussoorie.'];
include __DIR__.'/includes/header.php';
?>
<?php require_once __DIR__.'/includes/page_render.php';
$bpg = page_load('booking') ?: ['id'=>0,'title'=>'Book Your Stay','banner_title'=>'Book Your Stay','banner_subtitle'=>'','banner_image'=>'','show_banner'=>1,'content'=>'','sections'=>'[]'];
$GLOBALS['LIVE_PAGE'] = $bpg;
render_banner($bpg, ['Booking'], 'rooms_banner'); ?>
<section>
  <div class="container">
    <?php if ($err): ?><div class="alert err" id="bookErr" style="scroll-margin-top:110px"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></div><script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('bookErr').scrollIntoView({behavior:'smooth',block:'center'}));</script><?php endif; ?>
    <form method="post" id="quoteForm" data-datepair>
      <?= csrf_field() ?>
      <input type="hidden" id="emailVerifiedFlag" value="0">
      <div class="grid-2">
        <div class="card reveal-left">
          <h3>1. Choose Your Stay</h3>
          <div class="form-grid mt-2">
            <div class="full"><label class="req">Room</label>
              <select name="room_id" onchange="fetchQuote()" required>
                <option value="">— Select a room —</option>
                <?php foreach ($rooms as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selRoom==$r['id']?'selected':'' ?>><?= e($r['name']) ?> (from <?= money($r['base_price']) ?>/night<?= $r['pets_allowed']?' · Pet friendly':'' ?>)</option>
                <?php endforeach; ?>
              </select></div>
            <div><label class="req">Check-in</label><input type="date" name="checkin" value="<?= e(post('checkin', get('checkin'))) ?>" data-min-today onchange="fetchQuote()" required></div>
            <div><label class="req">Check-out</label><input type="date" name="checkout" value="<?= e(post('checkout', get('checkout'))) ?>" data-min-today onchange="fetchQuote()" required></div>
            <div><label>Adults</label><select name="adults" onchange="fetchQuote()"><?php for($i=1;$i<=6;$i++): ?><option <?= (int)post('adults', get('adults',2))===$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div><label>Children (5–12 yrs)</label><select name="children" onchange="fetchQuote()"><?php for($i=0;$i<=4;$i++): ?><option <?= (int)post('children', get('children',0))===$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
          </div>
          <h3 class="mt-3">2. Guest Details</h3>
          <div class="form-grid mt-2">
            <div><label class="req">Full Name</label><input type="text" name="guest_name" value="<?= e(post('guest_name', $user['name'] ?? '')) ?>" required></div>
            <div><label class="req">Mobile</label><input type="tel" name="phone" value="<?= e(post('phone', $user['phone'] ?? '')) ?>" required inputmode="tel" autocomplete="tel"></div>
            <div><label class="req">Email</label><input type="email" name="email" value="<?= e(post('email', $user['email'] ?? '')) ?>" required <?= $user?'readonly':'' ?>></div>
            <div><label>City</label><input type="text" name="address" value="<?= e(post('address', $user['city'] ?? '')) ?>"></div>
            <div class="full"><label>Special Request</label><textarea name="special_request" placeholder="Occasion, pets, early check-in…"><?= e(post('special_request')) ?></textarea></div>
          </div>

          <?php if ($needOtp): ?>
          <div class="verify-box" id="verifyBox">
            <b><i class="fa-solid fa-envelope-circle-check" style="color:var(--c-primary)"></i> Verify your email to confirm booking</b>
            <p style="font-size:13px;color:#78818d;margin-top:4px">We'll email you a 6-digit code — verifying also creates your account &amp; logs you in automatically.</p>
            <div class="verify-row">
              <button type="button" class="btn btn-outline btn-sm" onclick="sendBookingOtp()">Send OTP Code</button>
              <span id="verifyState" style="font-size:13px;color:#68717d;align-self:center"></span>
            </div>
            <div class="verify-row" id="verifyCodeRow" style="display:none">
              <input type="text" id="otpCode" class="input" placeholder="6-digit code" maxlength="6" pattern="[0-9]{6}" style="letter-spacing:6px;text-align:center">
              <button type="button" class="btn btn-primary btn-sm" onclick="verifyBookingOtp()">Verify</button>
            </div>
          </div>
          <?php elseif ($user): ?>
          <p class="price-note"><i class="fa-solid fa-circle-check" style="color:#1d7d4d"></i> Logged in as <b><?= e($user['email']) ?></b> — email verified.</p>
          <?php endif; ?>

          <h3 class="mt-3">3. Choose how you'd like to pay</h3>
          <p class="price-note">You can switch the payment method any time before confirming — and again on the payment page.</p>
          <div class="pay-opts mt-2">
            <?php $first = true; foreach (gateways_enabled() as $key=>$g): ?>
            <label class="pay-opt" data-method="<?= $key ?>">
              <input type="radio" name="payment_method" value="<?= $key ?>" <?= $first?'checked':'' ?> required>
              <span class="po-icon"><i class="fa-solid <?= e($g['icon']) ?>"></i></span>
              <span class="po-body"><b><?= e($g['label']) ?></b><small><?= e($g['desc'] ?? '') ?></small></span>
              <span class="po-check"><i class="fa-solid fa-circle-check"></i></span>
            </label>
            <?php $first = false; endforeach; ?>
            <?php if (!gateways_enabled()): ?><p class="price-note">⚠ No payment methods are enabled right now — please contact us to book.</p><?php endif; ?>
          </div>
          <?php if (array_key_exists('upi', gateways_enabled()) && setting('pay_upi_qr')): ?>
          <div id="upiPreview" class="upi-preview" style="display:none">
            <img src="<?= base_url(setting('pay_upi_qr')) ?>" alt="UPI QR code">
            <div><b>Scan &amp; pay with any UPI app</b><br><small>GPay · PhonePe · Paytm · BHIM<?= setting('pay_upi_id') ? ' — or pay to <b>'.e(setting('pay_upi_id')).'</b>' : '' ?>.<br>You'll enter the UPI reference no. on the next page.</small></div>
          </div>
          <script>
          document.addEventListener('DOMContentLoaded', function(){
            const prev = document.getElementById('upiPreview');
            document.querySelectorAll('input[name=payment_method]').forEach(r => r.addEventListener('change', () => {
              prev.style.display = (document.querySelector('input[name=payment_method]:checked')||{}).value === 'upi' ? 'flex' : 'none';
            }));
            const c = document.querySelector('input[name=payment_method]:checked');
            if (c && c.value === 'upi') prev.style.display = 'flex';
          });
          </script>
          <?php endif; ?>
        </div>
        <div>
          <div class="card reveal-right" style="position:sticky;top:100px">
            <h3>Your Price</h3>
            <div id="quoteBox" class="mt-2"><p style="color:#8a93a0">Select room &amp; dates to see live pricing — weekend, season and occasion rates apply automatically.</p></div>
            <button class="btn btn-primary btn-lg mt-2" id="proceedBtn" type="submit" style="width:100%" disabled data-pay="Confirm &amp; Pay" data-cod="Confirm Reservation"><i class="fa-solid fa-lock"></i> <span>Select room &amp; dates</span></button>
            <p style="font-size:12.5px;color:#98a0ab;margin-top:12px"><i class="fa-solid fa-shield-halved"></i> By booking you agree to our <a href="<?= u('page.php?slug=terms-and-conditions') ?>">Terms</a> &amp; <a href="<?= u('page.php?slug=refund-policy') ?>">Refund Policy</a>.</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>
