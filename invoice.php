<?php
/** Printable GST invoice with logo (guest link uses signed hash; admin/team see all) */
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/auth.php';
db(); sess_start();
$booking = q_row("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.booking_code=?", [get('code')]);
if (!$booking) die('Invoice not found');
$isAdmin = (bool)current_admin();
$isOwner = current_user() && ((int)current_user()['id'] === (int)$booking['user_id'] || strtolower(current_user()['email']) === strtolower($booking['email']));
$sigOk = hash_equals(md5($booking['booking_code'].APP_SECRET), get('e'));
if (!$isAdmin && !$isOwner && !$sigOk) die('Access denied');
$lines = json_decode($booking['price_breakup'] ?: '[]', true) ?: [];
$c = setting('color_primary'); $s = setting('color_secondary');
$taxHalf = round((float)$booking['tax'] / 2, 2);
?><!DOCTYPE html><html><head>
<?= gtm_head() ?><meta charset="utf-8"><title>Invoice <?= e($booking['booking_code']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;color:#2b333d;max-width:840px;margin:0 auto;padding:0 14px;background:#f4f2ec}
.sheet{background:#fff;margin:22px auto;border-radius:12px;overflow:hidden;box-shadow:0 12px 40px rgba(15,34,56,.12)}
.inv-top{background:linear-gradient(135deg,<?= $s ?>,#1d3a5f);color:#fff;padding:28px 32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px}
.inv-top h1{margin:0;font-family:Georgia,serif;font-size:24px}
.inv-top .tagline{font-size:12px;color:#c9d5e4;letter-spacing:2px;text-transform:uppercase}
.inv-badge{background:<?= $c ?>;color:#fff;padding:8px 22px;border-radius:24px;font-weight:700;letter-spacing:2px}
.inv-meta{display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;padding:22px 32px;border-bottom:1px solid #eee;font-size:13.5px}
.inv-meta b{color:<?= $s ?>}
.paidchip{display:inline-block;padding:4px 14px;border-radius:14px;font-size:12px;background:#e6f7ee;color:#1d7d4d;font-weight:700}
.paidchip.unpaid{background:#fef4e5;color:#c07d1a}
table{width:100%;border-collapse:collapse;font-size:13.5px}
.items{padding:8px 32px 26px}
.items th{background:#f6f3ee;color:#55606d;text-align:left;padding:10px 12px;border-bottom:2px solid <?= $c ?>}
.items td{padding:10px 12px;border-bottom:1px solid #f0ede6}
.tot-row td{font-weight:700;font-size:15px;background:#fdf9f1}
.inv-note{padding:0 32px 26px;font-size:12px;color:#8a93a0}
.inv-foot{background:<?= $s ?>;color:#c9d5e4;padding:16px 32px;font-size:12px;text-align:center;line-height:1.7}
@media print{body{background:#fff}.noprint{display:none}.sheet{box-shadow:none;margin:0}}
</style></head><body>
<?= gtm_body() ?>
<div class="sheet">
  <div class="inv-top">
    <div style="display:flex;align-items:center;gap:16px">
      <?php if (setting('logo_header')): ?><img src="<?= base_url(setting('logo_header')) ?>" style="max-height:64px;background:rgba(255,255,255,.12);padding:6px;border-radius:8px"><?php endif; ?>
      <div><h1><?= e(setting('site_name')) ?></h1><div class="tagline"><?= e(setting('site_tagline')) ?></div></div>
    </div>
    <div style="text-align:right">
      <div class="inv-badge">TAX INVOICE</div>
      <p style="font-size:12.5px;margin:10px 0 0;color:#c9d5e4">Invoice # <b style="color:#fff">INV-<?= e($booking['booking_code']) ?></b><br>Date: <?= date('d M Y', strtotime($booking['created_at'])) ?></p>
    </div>
  </div>
  <div class="inv-meta">
    <div><b>Billed To</b><br><?= e($booking['guest_name']) ?><br><?= e($booking['phone']) ?> · <?= e($booking['email']) ?><?= $booking['address']?'<br>'.e($booking['address']):'' ?></div>
    <div><b>From</b><br><?= e(setting('site_name')) ?><br><?= nl2br(e(setting('contact_address'))) ?><?= setting('gstin') ? '<br><b>GSTIN:</b> '.e(setting('gstin')) : '' ?></div>
    <div><b>Stay Details</b><br><?= e($booking['room_name']) ?><br>
      <?= date('D, d M Y', strtotime($booking['checkin'])) ?> → <?= date('D, d M Y', strtotime($booking['checkout'])) ?><br>
      <?= $booking['nights'] ?> night(s) · <?= $booking['adults'] ?>A<?= $booking['children'] ? '+'.$booking['children'].'C' : '' ?><br>
      <span class="paidchip <?= $booking['payment_status']!=='paid'?'unpaid':'' ?>"><?= strtoupper(e($booking['payment_status'])) ?></span></div>
  </div>
  <div class="items">
    <table>
      <tr><th>Night</th><th>Room</th><th>Extra Adult</th><th>Child</th><th style="text-align:right">Amount</th></tr>
      <?php foreach ($lines as $l): ?>
      <tr><td><?= date('D, d M Y', strtotime($l['date'])) ?><?= $l['rule'] ? ' <small style="color:'.$c.'">('.e($l['rule']).')</small>' : '' ?></td>
        <td><?= money($l['room']) ?></td><td><?= money($l['extra_adult']) ?></td><td><?= money($l['child']) ?></td>
        <td style="text-align:right"><?= money($l['total']) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="4" style="text-align:right">Subtotal</td><td style="text-align:right"><?= money($booking['subtotal']) ?></td></tr>
      <tr><td colspan="4" style="text-align:right">CGST (<?= (float)setting('tax_percent')/2 ?>%)</td><td style="text-align:right"><?= money($taxHalf) ?></td></tr>
      <tr><td colspan="4" style="text-align:right">SGST (<?= (float)setting('tax_percent')/2 ?>%)</td><td style="text-align:right"><?= money((float)$booking['tax'] - $taxHalf) ?></td></tr>
      <tr class="tot-row"><td colspan="4" style="text-align:right">Grand Total</td><td style="text-align:right"><?= money($booking['total']) ?></td></tr>
      <?php if ((float)$booking['amount_paid'] > 0): ?>
      <tr><td colspan="4" style="text-align:right">Paid via <?= strtoupper(e($booking['payment_method'])) ?></td><td style="text-align:right"><?= money($booking['amount_paid']) ?></td></tr>
      <?php endif; ?>
    </table>
  </div>
  <div class="inv-note"><?= e(setting('invoice_notes')) ?><br>This is a computer-generated invoice and does not require a signature.</div>
  <div class="inv-foot"><?= e(setting('site_name')) ?> · ☎ <a href="tel:<?= e(tel_digits(setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a> · ✉ <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a> · <?= e(str_replace(["\r","\n"], ' ', setting('contact_address'))) ?></div>
</div>
<div class="noprint" style="text-align:center;margin:18px 0">
  <button onclick="print()" style="background:<?= $c ?>;color:#fff;border:none;padding:13px 34px;border-radius:8px;cursor:pointer;font-size:15px">🖨 Print / Save as PDF</button>
</div>
</body></html>
