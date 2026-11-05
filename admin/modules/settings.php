<?php
/** Site settings — tabbed: general, booking/refund/GST, payments, social login, email, whatsapp, chat, google, scripts */
$TABS = ['general'=>'General & Contact','booking'=>'Booking · Refunds · GST','payments'=>'Payment Gateways','login'=>'Login & Social','email'=>'Email / SMTP','whatsapp'=>'WhatsApp Button','chat'=>'Live Chat','media'=>'Photos & Video','google'=>'Google APIs','scripts'=>'Custom Scripts'];
$tab = get('tab','general');
$flash = '';
$FIELDS = [
 'general' => ['site_name','site_tagline','contact_phone','contact_phone2','contact_email','contact_address','map_embed','social_instagram','social_linkedin','social_x','social_youtube','booking_notify_email'],
 'booking' => ['currency','currency_symbol','tax_percent','tax_label','gstin','invoice_notes','checkin_time','checkout_time','refund_days','refund_percent','refund_auto','advance_percent'],
 'payments'=> ['pay_online_enabled','pay_razorpay_enabled','pay_razorpay_key','pay_razorpay_secret','pay_stripe_enabled','pay_stripe_pk','pay_stripe_sk','pay_paypal_enabled','pay_paypal_client','pay_paypal_secret','pay_paypal_mode','pay_paypal_currency','pay_paypal_fx','pay_paytm_enabled','pay_paytm_mid','pay_paytm_key','pay_paytm_website','pay_paytm_mode','pay_upi_enabled','pay_upi_id','pay_upi_name','pay_cod_enabled','pay_cod_label'],
 'login'   => ['booking_otp_required','google_client_id','google_client_secret'],
 'email'   => ['smtp_enabled','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure','mail_from','mail_from_name'],
 'whatsapp'=> ['wa_enabled','wa_number','wa_message','wa_position','wa_show_mobile','wa_show_desktop'],
 'chat'    => ['chat_enabled','chat_greeting','chat_wait_seconds','chat_idle_seconds','chat_offline_msg'],
 'google'  => ['google_places_key','google_place_id','recaptcha_site','recaptcha_secret','google_drive_api_key'],
 'scripts' => ['body_scripts'],
 'media'   => ['upload_max_px','upload_quality','media_webp','media_webp_quality','media_video_server','media_video_compress_mb','media_video_height','media_video_crf','ffmpeg_path'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        foreach ($FIELDS[$tab] ?? [] as $k) if (isset($_POST[$k])) setting_save($k, $_POST[$k]);
        $qr = handle_upload('pay_upi_qr', 'qr'); if ($qr) setting_save('pay_upi_qr', $qr);
        $flash = 'Settings saved.';
    }
    if (post('do') === 'test_mail') {
        $ok = send_email(post('test_to') ?: $ADMIN['email'], 'Test email — '.setting('site_name'), '<p>SMTP is working! 🎉</p>', 'test');
        $flash = $ok ? 'Test email sent — check the inbox.' : 'Sending failed — check SMTP settings and the Email Log below.';
    }
}
$s = fn($k,$d='')=>e(setting($k,$d));
$chk = fn($k)=>setting($k)==='1' ? 'checked' : '';
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs"><?php foreach ($TABS as $k=>$v): ?><a href="?m=settings&tab=<?= $k ?>" class="<?= $tab===$k?'active':'' ?>"><?= $v ?></a><?php endforeach; ?></div>

<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save">
<?php if ($tab === 'general'): ?>
  <div class="frow">
    <div><label>Site Name</label><input class="input" name="site_name" value="<?= $s('site_name') ?>"></div>
    <div><label>Tagline</label><input class="input" name="site_tagline" value="<?= $s('site_tagline') ?>"></div>
    <div><label>Phone</label><input class="input" name="contact_phone" value="<?= $s('contact_phone') ?>"></div>
    <div><label>Phone 2</label><input class="input" name="contact_phone2" value="<?= $s('contact_phone2') ?>"></div>
    <div><label>Email</label><input class="input" name="contact_email" value="<?= $s('contact_email') ?>"></div>
    <div><label>Booking notification email</label><input class="input" name="booking_notify_email" value="<?= $s('booking_notify_email') ?>"></div>
  </div>
  <label>Address</label><textarea class="input" name="contact_address"><?= $s('contact_address') ?></textarea>
  <label>Google Map embed URL</label><input class="input" name="map_embed" value="<?= $s('map_embed') ?>">
  <div class="frow">
    <?php foreach (['instagram','linkedin','x','youtube'] as $sn): /* round 13: Facebook removed site-wide */ ?>
    <div><label><?= ucfirst($sn) ?> URL</label><input class="input" name="social_<?= $sn ?>" value="<?= $s('social_'.$sn) ?>"></div>
    <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'booking'): ?>
  <div class="frow4">
    <div><label>Currency code</label><input class="input" name="currency" value="<?= $s('currency') ?>"></div>
    <div><label>Currency symbol</label><input class="input" name="currency_symbol" value="<?= $s('currency_symbol') ?>"></div>
    <div><label><?= $s('tax_label','Tax') ?> %</label><input class="input" type="number" step="0.01" name="tax_percent" value="<?= $s('tax_percent') ?>"></div>
    <div><label>Tax label</label><input class="input" name="tax_label" value="<?= $s('tax_label') ?>"></div>
    <div><label>GSTIN (shown on invoice)</label><input class="input" name="gstin" value="<?= $s('gstin') ?>" placeholder="05XXXXX0000X1Z5"></div>
    <div><label>Check-in time</label><input class="input" name="checkin_time" value="<?= $s('checkin_time') ?>"></div>
    <div><label>Check-out time</label><input class="input" name="checkout_time" value="<?= $s('checkout_time') ?>"></div>
  </div>
  <label>Invoice footer note</label><input class="input" name="invoice_notes" value="<?= $s('invoice_notes') ?>">
  <h3 style="margin-top:16px">↩ Refund Rules (auto-scheduled on cancellation)</h3>
  <div class="frow3">
    <div><label>Refund window (days before check-in)</label><input class="input" type="number" name="refund_days" value="<?= $s('refund_days') ?>">
      <p class="hint">Cancellations made this many days (or more) before check-in are refunded. Change any time — the policy page and booking screens update automatically.</p></div>
    <div><label>Refund percentage</label><input class="input" type="number" name="refund_percent" value="<?= $s('refund_percent') ?>" max="100"></div>
    <div><label>Auto-process refunds</label><select class="input" name="refund_auto">
      <option value="1" <?= setting('refund_auto')==='1'?'selected':'' ?>>Yes — refund via gateway automatically & notify guest by email</option>
      <option value="0" <?= setting('refund_auto')!=='1'?'selected':'' ?>>No — queue for manual approval</option></select></div>
  </div>

<?php elseif ($tab === 'login'): ?>
  <h3>Booking Email Verification</h3>
  <label class="inline"><input type="hidden" name="booking_otp_required" value="0"><input type="checkbox" name="booking_otp_required" value="1" <?= $chk('booking_otp_required') ?>> Require email OTP verification before a guest can book (auto-creates account &amp; logs them in)</label>
  <h3 style="margin-top:18px">Google Social Login</h3>
  <p class="hint">Create OAuth credentials at <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> → OAuth client ID → Web application.<br>Authorized redirect URI: <code><?= base_url('account/google.php') ?></code></p>
  <div class="frow">
    <div><label>Google Client ID</label><input class="input" name="google_client_id" value="<?= $s('google_client_id') ?>"></div>
    <div><label>Google Client Secret</label><input class="input" name="google_client_secret" value="<?= $s('google_client_secret') ?>"></div>
  </div>
  <p class="hint">When filled, a "Continue with Google" button appears on the guest login page automatically. Admin &amp; team members can always log in with password <b>or email OTP</b> at <?= base_url('admin/login.php') ?>.</p>

<?php elseif ($tab === 'payments'): ?>
  <div style="background:linear-gradient(135deg,#f7f3ea,#fdfbf6);border:1px solid #e7dcc3;border-radius:12px;padding:16px 18px;margin-bottom:20px">
    <h3 style="margin-top:0">💳 Payment options shown at checkout</h3>
    <p class="hint" style="margin-bottom:12px">Master switches — what guests can choose on the booking page. (Also quick-toggle from the Dashboard.)</p>
    <div class="frow3">
      <label class="inline" style="font-size:14px;background:#fff;border:1px solid var(--bd);border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:center;cursor:pointer">
        <input type="hidden" name="pay_online_enabled" value="0"><input type="checkbox" name="pay_online_enabled" value="1" <?= $chk('pay_online_enabled') ?>>
        <span><b>🌐 Pay Online</b><br><small class="hint">Razorpay / Stripe / PayPal / Paytm (configured below)</small></span></label>
      <label class="inline" style="font-size:14px;background:#fff;border:1px solid var(--bd);border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:center;cursor:pointer">
        <input type="hidden" name="pay_upi_enabled" value="0"><input type="checkbox" name="pay_upi_enabled" value="1" <?= $chk('pay_upi_enabled') ?>>
        <span><b>📲 UPI / QR Code</b><br><small class="hint">Guest scans your uploaded QR &amp; enters reference no.</small></span></label>
      <label class="inline" style="font-size:14px;background:#fff;border:1px solid var(--bd);border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:center;cursor:pointer">
        <input type="hidden" name="pay_cod_enabled" value="0"><input type="checkbox" name="pay_cod_enabled" value="1" <?= $chk('pay_cod_enabled') ?>>
        <span><b>🏨 Pay at Hotel</b><br><small class="hint">Visit &amp; pay on arrival — no advance</small></span></label>
    </div>
  </div>
  <h3>Razorpay <label class="inline" style="float:right"><input type="hidden" name="pay_razorpay_enabled" value="0"><input type="checkbox" name="pay_razorpay_enabled" value="1" <?= $chk('pay_razorpay_enabled') ?>> Enabled</label></h3>
  <div class="frow"><div><label>Key ID</label><input class="input" name="pay_razorpay_key" value="<?= $s('pay_razorpay_key') ?>"></div>
  <div><label>Key Secret</label><input class="input" name="pay_razorpay_secret" value="<?= $s('pay_razorpay_secret') ?>"></div></div>
  <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
  <h3>Stripe <label class="inline" style="float:right"><input type="hidden" name="pay_stripe_enabled" value="0"><input type="checkbox" name="pay_stripe_enabled" value="1" <?= $chk('pay_stripe_enabled') ?>> Enabled</label></h3>
  <div class="frow"><div><label>Publishable key</label><input class="input" name="pay_stripe_pk" value="<?= $s('pay_stripe_pk') ?>"></div>
  <div><label>Secret key</label><input class="input" name="pay_stripe_sk" value="<?= $s('pay_stripe_sk') ?>"></div></div>
  <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
  <h3>PayPal <label class="inline" style="float:right"><input type="hidden" name="pay_paypal_enabled" value="0"><input type="checkbox" name="pay_paypal_enabled" value="1" <?= $chk('pay_paypal_enabled') ?>> Enabled</label></h3>
  <div class="frow4"><div><label>Client ID</label><input class="input" name="pay_paypal_client" value="<?= $s('pay_paypal_client') ?>"></div>
  <div><label>Secret</label><input class="input" name="pay_paypal_secret" value="<?= $s('pay_paypal_secret') ?>"></div>
  <div><label>Mode</label><select class="input" name="pay_paypal_mode"><option <?= setting('pay_paypal_mode')==='sandbox'?'selected':'' ?>>sandbox</option><option <?= setting('pay_paypal_mode')==='live'?'selected':'' ?>>live</option></select></div>
  <div><label>Charge currency / ₹-rate</label><div style="display:flex;gap:6px"><input class="input" name="pay_paypal_currency" value="<?= $s('pay_paypal_currency','USD') ?>" style="width:70px"><input class="input" name="pay_paypal_fx" value="<?= $s('pay_paypal_fx','83') ?>" title="INR per unit"></div></div></div>
  <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
  <h3>Paytm <label class="inline" style="float:right"><input type="hidden" name="pay_paytm_enabled" value="0"><input type="checkbox" name="pay_paytm_enabled" value="1" <?= $chk('pay_paytm_enabled') ?>> Enabled</label></h3>
  <div class="frow4"><div><label>Merchant ID</label><input class="input" name="pay_paytm_mid" value="<?= $s('pay_paytm_mid') ?>"></div>
  <div><label>Merchant Key</label><input class="input" name="pay_paytm_key" value="<?= $s('pay_paytm_key') ?>"></div>
  <div><label>Website</label><input class="input" name="pay_paytm_website" value="<?= $s('pay_paytm_website','DEFAULT') ?>"></div>
  <div><label>Mode</label><select class="input" name="pay_paytm_mode"><option <?= setting('pay_paytm_mode')==='stage'?'selected':'' ?>>stage</option><option <?= setting('pay_paytm_mode')==='production'?'selected':'' ?>>production</option></select></div></div>
  <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
  <h3>UPI / QR Code <small class="hint" style="float:right">switch on/off in the panel above</small></h3>
  <div class="frow3"><div><label>UPI ID</label><input class="input" name="pay_upi_id" value="<?= $s('pay_upi_id') ?>" placeholder="hotel@upi"></div>
  <div><label>Payee name</label><input class="input" name="pay_upi_name" value="<?= $s('pay_upi_name') ?>"></div>
  <div><label>QR code image</label><input class="input" type="file" name="pay_upi_qr" accept="image/*">
    <?php if (setting('pay_upi_qr')): ?><img class="imgthumb" style="margin-top:6px" src="<?= base_url(setting('pay_upi_qr')) ?>"><?php endif; ?></div></div>
  <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
  <h3>Pay at Hotel (visit &amp; pay) <small class="hint" style="float:right">switch on/off in the panel above</small></h3>
  <div class="frow"><div><label>Label shown to guests</label><input class="input" name="pay_cod_label" value="<?= $s('pay_cod_label') ?>"></div></div>

<?php elseif ($tab === 'email'): ?>
  <label class="inline"><input type="hidden" name="smtp_enabled" value="0"><input type="checkbox" name="smtp_enabled" value="1" <?= $chk('smtp_enabled') ?>> Use SMTP (recommended — otherwise PHP mail() is used)</label>
  <div class="frow4">
    <div><label>SMTP Host</label><input class="input" name="smtp_host" value="<?= $s('smtp_host') ?>" placeholder="smtp.hostinger.com"></div>
    <div><label>Port</label><input class="input" name="smtp_port" value="<?= $s('smtp_port') ?>"></div>
    <div><label>Security</label><select class="input" name="smtp_secure"><option <?= setting('smtp_secure')==='tls'?'selected':'' ?>>tls</option><option <?= setting('smtp_secure')==='ssl'?'selected':'' ?>>ssl</option></select></div>
    <div><label>Username</label><input class="input" name="smtp_user" value="<?= $s('smtp_user') ?>"></div>
    <div><label>Password</label><input class="input" type="password" name="smtp_pass" value="<?= $s('smtp_pass') ?>"></div>
    <div><label>From email</label><input class="input" name="mail_from" value="<?= $s('mail_from') ?>"></div>
    <div><label>From name</label><input class="input" name="mail_from_name" value="<?= $s('mail_from_name') ?>"></div>
  </div>

<?php elseif ($tab === 'whatsapp'): ?>
  <label class="inline"><input type="hidden" name="wa_enabled" value="0"><input type="checkbox" name="wa_enabled" value="1" <?= $chk('wa_enabled') ?>> Show the floating WhatsApp button (sits on the side of the screen, vertically centred, on every page — desktop and mobile)</label>
  <div class="frow">
    <div><label>WhatsApp number (with country code)</label><input class="input" name="wa_number" value="<?= $s('wa_number') ?>" placeholder="919456765555"></div>
    <div><label>Side of the screen</label><select class="input" name="wa_position"><option value="right" <?= setting('wa_position','right')==='right'?'selected':'' ?>>Right side (middle)</option><option value="left" <?= setting('wa_position')==='left'?'selected':'' ?>>Left side (middle)</option></select></div>
  </div>
  <label>Pre-filled message</label><textarea class="input" name="wa_message"><?= $s('wa_message') ?></textarea>
  <label class="inline"><input type="hidden" name="wa_show_mobile" value="0"><input type="checkbox" name="wa_show_mobile" value="1" <?= $chk('wa_show_mobile') ?>> Show on mobile</label>
  <label class="inline"><input type="hidden" name="wa_show_desktop" value="0"><input type="checkbox" name="wa_show_desktop" value="1" <?= $chk('wa_show_desktop') ?>> Show on desktop</label>

<?php elseif ($tab === 'chat'): ?>
  <label class="inline"><input type="hidden" name="chat_enabled" value="0"><input type="checkbox" name="chat_enabled" value="1" <?= $chk('chat_enabled') ?>> Enable live chat widget</label>
  <label>Bot greeting message</label><textarea class="input" name="chat_greeting"><?= $s('chat_greeting') ?></textarea>
  <div class="frow">
    <div><label>Agent response wait (seconds before re-routing)</label><input class="input" type="number" name="chat_wait_seconds" value="<?= $s('chat_wait_seconds') ?>"></div>
    <div><label>Agent considered offline after (seconds idle)</label><input class="input" type="number" name="chat_idle_seconds" value="<?= $s('chat_idle_seconds') ?>"></div>
  </div>
  <label>Offline message (no agents available)</label><textarea class="input" name="chat_offline_msg"><?= $s('chat_offline_msg') ?></textarea>

<?php elseif ($tab === 'google'): ?>
  <div class="frow">
    <div><label>Google Places API key</label><input class="input" name="google_places_key" value="<?= $s('google_places_key') ?>">
      <p class="hint">Enable "Places API" in Google Cloud Console. Used to fetch your Google Business reviews.</p></div>
    <div><label>Place ID</label><input class="input" name="google_place_id" value="<?= $s('google_place_id') ?>">
      <p class="hint">Find yours at developers.google.com/maps/documentation/places/web-service/place-id</p></div>
    <div><label>reCAPTCHA site key</label><input class="input" name="recaptcha_site" value="<?= $s('recaptcha_site') ?>"></div>
    <div><label>reCAPTCHA secret</label><input class="input" name="recaptcha_secret" value="<?= $s('recaptcha_secret') ?>"></div>
    <div><label>Google Drive API key <small class="hint">(optional)</small></label><input class="input" name="google_drive_api_key" value="<?= $s('google_drive_api_key') ?>">
      <p class="hint">Only needed for very large shared folders. Gallery → Import from Google Drive works without it for folders shared as "Anyone with the link". Enable "Google Drive API" in Cloud Console and create an API key.</p></div>
  </div>

<?php elseif ($tab === 'media'): require_once ROOT_PATH.'/lib/media.php'; $ffm = media_ffmpeg(); ?>
  <div class="alert" style="margin-bottom:14px">⚡ <b>Everything you upload is optimised automatically</b> — photos are straightened, resized, recompressed and get a WebP twin; videos are compressed in your browser before upload (and again with ffmpeg on the server when available). These are the dials; the defaults are tuned for "smaller files, no visible quality loss".</div>
  <h4>📷 Photos</h4>
  <div class="frow">
    <div><label>Longest side (px)</label><input class="input" type="number" name="upload_max_px" min="800" max="4000" value="<?= $s('upload_max_px','2000') ?>"><p class="hint">2000 px is plenty for full-width banners on 4K screens. Phones shoot 4000+ px — those are shrunk.</p></div>
    <div><label>JPEG quality (50–95)</label><input class="input" type="number" name="upload_quality" min="50" max="95" value="<?= $s('upload_quality','82') ?>"><p class="hint">82 = visually identical to the original at roughly half the size. Use 88–90 for a photography portfolio.</p></div>
    <div><label>WebP twin quality</label><input class="input" type="number" name="media_webp_quality" min="50" max="95" value="<?= $s('media_webp_quality','78') ?>"></div>
  </div>
  <label class="inline"><input type="hidden" name="media_webp" value="0"><input type="checkbox" name="media_webp" value="1" <?= setting('media_webp','1')==='1'?'checked':'' ?>> Create a .webp twin of every JPG/PNG and serve it to browsers that accept it (25–35 % smaller again — recommended)</label>
  <p class="hint">Already have hundreds of photos uploaded? <a href="?m=tools#mediaOpt">Site Tools → Optimise all photos now</a>.</p>
  <h4 style="margin-top:18px">🎬 Videos</h4>
  <div class="frow">
    <div><label>Compress in browser when bigger than (MB)</label><input class="input" type="number" name="media_video_compress_mb" min="1" max="200" value="<?= $s('media_video_compress_mb','6') ?>"><p class="hint">The admin's browser re-encodes the clip to 720p before uploading — a 120 MB phone video becomes ~8 MB. Takes about as long as the clip itself.</p></div>
    <div><label>Max height (px)</label><select class="input" name="media_video_height"><?php foreach ([480,540,720,1080] as $hh): ?><option value="<?= $hh ?>" <?= (int)setting('media_video_height','720')===$hh?'selected':'' ?>><?= $hh ?>p</option><?php endforeach; ?></select><p class="hint">720p is the sweet spot for a hero background.</p></div>
    <div><label>Server quality (CRF 18 best · 35 smallest)</label><input class="input" type="number" name="media_video_crf" min="18" max="35" value="<?= $s('media_video_crf','27') ?>"></div>
  </div>
  <label class="inline"><input type="hidden" name="media_video_server" value="0"><input type="checkbox" name="media_video_server" value="1" <?= setting('media_video_server','1')==='1'?'checked':'' ?>> Also re-encode on the server with ffmpeg when available (H.264 MP4, plays everywhere, "faststart" for instant playback)</label>
  <div class="frow" style="margin-top:8px">
    <div><label>ffmpeg path <small class="hint">(optional)</small></label><input class="input" name="ffmpeg_path" value="<?= $s('ffmpeg_path') ?>" placeholder="/usr/bin/ffmpeg"><p class="hint">Status: <?= $ffm ? '<b style="color:#1d7d4d">found — '.e($ffm).'</b>' : '<b style="color:#b9600d">not found on this server</b> (that\'s normal on shared hosting — browser compression covers it; ask your host if they can enable ffmpeg)' ?></p></div>
  </div>
<?php elseif ($tab === 'scripts'): ?>
  <label>Scripts before &lt;/body&gt; (chat pixels, marketing tags…)</label>
  <textarea class="input" name="body_scripts" style="font-family:monospace;min-height:140px"><?= $s('body_scripts') ?></textarea>
<?php endif; ?>
  <button class="btn" style="margin-top:16px">Save Settings</button>
</form>

<?php if ($tab === 'email'): ?>
<div class="card"><h3>Send test email</h3>
  <form method="post" style="display:flex;gap:10px;align-items:end"><?= csrf_field() ?><input type="hidden" name="do" value="test_mail">
    <div style="flex:1"><label>To</label><input class="input" name="test_to" value="<?= e($ADMIN['email']) ?>"></div>
    <button class="btn sec">Send Test</button></form>
  <h3 style="margin-top:18px">Recent Email Log</h3>
  <table class="tbl"><tr><th>To</th><th>Subject</th><th>Status</th><th>Error</th><th>Date</th></tr>
  <?php foreach (q_all("SELECT * FROM email_log ORDER BY id DESC LIMIT 15") as $el): ?>
  <tr><td><?= e($el['to_email']) ?></td><td><?= e($el['subject']) ?></td>
    <td><span class="badge <?= $el['status']==='sent'?'green':'red' ?>"><?= e($el['status']) ?></span></td>
    <td style="font-size:12px"><?= e($el['error']) ?></td><td><?= $el['created_at'] ?></td></tr>
  <?php endforeach; ?></table>
</div>
<?php endif; ?>
