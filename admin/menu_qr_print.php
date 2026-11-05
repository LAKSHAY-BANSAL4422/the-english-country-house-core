<?php
/** Printable table-tent / room cards with the branded menu QR (A4, 4 cards per page) */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
require_admin('menu');
$rooms = array_column(q_all("SELECT name FROM rooms WHERE active=1 ORDER BY sort, id"), 'name');
$extra = array_filter(array_map('trim', preg_split('/\r?\n/', setting('menu_qr_rooms'))));
$labels = array_merge([''], $rooms, $extra);      // '' = general card without a room
$base = u('menu.php');
$color = setting('menu_qr_color', '#163a2c');
$font = setting('font_heading', 'Cormorant Garamond'); $accent = setting('font_accent', 'Great Vibes');
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Menu QR cards — <?= e(setting('site_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($font) ?>:wght@500;700&family=<?= urlencode($accent) ?>&family=Outfit:wght@400;600&display=swap" rel="stylesheet">
<style>
@page{size:A4;margin:10mm}
*{box-sizing:border-box}body{margin:0;font-family:Outfit,Arial,sans-serif;background:#e9e6dd;color:#22302a}
.bar{position:sticky;top:0;background:#163a2c;color:#fff;padding:12px 20px;display:flex;gap:12px;align-items:center;z-index:5}
.bar button{background:#f1dfae;color:#163a2c;border:0;padding:9px 18px;border-radius:20px;font-weight:600;cursor:pointer}
.sheet{display:grid;grid-template-columns:1fr 1fr;gap:8mm;padding:10mm;max-width:210mm;margin:0 auto}
.card{background:#fffdf7;border:1.5px solid #d9c79a;border-radius:6mm;padding:8mm 6mm 6mm;text-align:center;position:relative;overflow:hidden;break-inside:avoid;min-height:128mm;display:flex;flex-direction:column;align-items:center}
.card::before{content:'';position:absolute;inset:2.5mm;border:1px solid #e8dcbc;border-radius:4.5mm;pointer-events:none}
.card .corner{position:absolute;width:30mm;opacity:.9}
.card .tl{top:-3mm;left:-3mm}.card .br{bottom:-3mm;right:-3mm}
.logo{height:20mm;margin-bottom:1mm}
.t1{font-family:'<?= e($font) ?>',serif;font-size:30pt;font-weight:700;color:<?= e($color) ?>;line-height:1}
.t2{font-family:'<?= e($accent) ?>',cursive;font-size:17pt;color:#9a7330;margin:1mm 0 3mm}
canvas{margin:1mm auto}
.room{margin-top:2mm;background:<?= e($color) ?>;color:#f1dfae;font-weight:600;padding:2mm 6mm;border-radius:10mm;font-size:12pt;letter-spacing:.5px}
.steps{font-size:9pt;color:#5d6a63;margin-top:3mm;letter-spacing:.3px}
.call{font-size:9.5pt;margin-top:1.5mm;color:#22302a}
@media print{body{background:#fff}.bar{display:none}.sheet{padding:0;gap:6mm}}
</style></head><body>
<div class="bar"><b>🔳 Menu QR cards</b><span><?= count($labels) ?> cards · general + one per room/table</span><button onclick="print()">🖨 Print / Save PDF</button></div>
<div class="sheet">
<?php foreach ($labels as $i => $lb): $url = $lb === '' ? $base.'?src=qr' : $base.'?room='.rawurlencode($lb).'&src=qr'; ?>
  <div class="card">
    <img class="corner tl" src="<?= base_url('assets/img/menu/corner-tl.png') ?>" alt="">
    <img class="corner br" src="<?= base_url('assets/img/menu/corner-br.png') ?>" alt="">
    <img class="logo" src="<?= base_url(setting('logo_print') ?: 'assets/img/brand/logo.png') ?>" alt="">
    <div class="t1"><?= e(setting('menu_title','Our Menu')) ?></div>
    <div class="t2"><?= e(setting('menu_tagline')) ?></div>
    <canvas data-qr="<?= e($url) ?>" data-qr-size="190"></canvas>
    <?php if ($lb !== ''): ?><div class="room"><?= e($lb) ?></div><?php endif; ?>
    <div class="steps">SCAN · CHOOSE YOUR DISHES · ORDER TO YOUR ROOM</div>
    <div class="call">or call <b><?= e(setting('menu_order_phone') ?: setting('contact_phone')) ?></b></div>
  </div>
<?php endforeach; ?>
</div>
<script>window.ECH_QR = {logo: '<?= e(base_url(setting('menu_qr_logo') ?: 'assets/img/brand/logo.png')) ?>', color: '<?= e($color) ?>'};</script>
<script src="<?= base_url('assets/js/vendor/qrcode.js') ?>"></script>
<script src="<?= base_url('assets/js/menu-qr.js') ?>"></script>
</body></html>
