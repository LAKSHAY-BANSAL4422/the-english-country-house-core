<?php
/** Kitchen Order Ticket (KOT) / guest bill for a food order — 80 mm thermal or A4 */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../lib/food.php';
db(); sess_start();
$A = require_admin('menu');
$o = q_row("SELECT * FROM food_orders WHERE id=?", [(int)get('id')]);
if (!$o) die('Order not found');
$items = food_order_items((int)$o['id']);
$bill = get('bill') === '1';
?><!DOCTYPE html><html><head><meta charset="utf-8"><title><?= $bill ? 'Bill' : 'KOT' ?> <?= e($o['order_code']) ?></title>
<style>
*{box-sizing:border-box}body{font-family:'Courier New',monospace;margin:0;background:#eee}
.t{width:80mm;margin:14px auto;background:#fff;padding:12px 12px 18px;color:#000;font-size:13px}
.c{text-align:center}.b{font-weight:700}.big{font-size:20px}.room{font-size:22px;font-weight:700;border:2px solid #000;padding:6px;margin:8px 0;text-align:center}
hr{border:0;border-top:1px dashed #000;margin:8px 0}table{width:100%;border-collapse:collapse}td{padding:3px 0;vertical-align:top}
.r{text-align:right}.note{border:1px solid #000;padding:5px;margin-top:6px}
.bar{text-align:center;margin:10px}.bar button,.bar a{font:14px sans-serif;padding:8px 16px;margin:0 4px;cursor:pointer}
img{max-height:56px}
@media print{body{background:#fff}.bar{display:none}.t{margin:0;width:100%}}
</style></head><body>
<div class="bar"><button onclick="print()">🖨 Print</button>
  <a href="?id=<?= (int)$o['id'] ?>&bill=<?= $bill ? 0 : 1 ?>"><?= $bill ? 'Show KOT' : 'Show guest bill' ?></a></div>
<div class="t">
  <div class="c"><?php if (setting('logo_print')): ?><img src="<?= base_url(setting('logo_print')) ?>" alt=""><br><?php endif; ?>
    <span class="b"><?= e(setting('site_name')) ?></span><br><?= e(setting('menu_restaurant_name')) ?></div>
  <hr>
  <div class="c big b"><?= $bill ? 'BILL' : 'KITCHEN ORDER TICKET' ?></div>
  <div class="c">#<?= e($o['order_code']) ?> · <?= date('d M Y h:i A', strtotime($o['created_at'])) ?></div>
  <div class="room">ROOM: <?= e($o['room_no'] ?: '—') ?></div>
  <div>Guest: <?= e($o['guest_name']) ?><br>Phone: <?= e($o['phone']) ?></div>
  <hr>
  <table>
    <?php foreach ($items as $it): ?>
    <tr><td class="b" style="width:30px"><?= (int)$it['qty'] ?>×</td>
        <td><?= e($it['name']) ?><?= $it['variant'] !== '' ? '<br><small>» '.e($it['variant']).'</small>' : '' ?> <small>[<?= $it['diet']==='veg'?'V':($it['diet']==='egg'?'E':($it['diet']==='both'?'V/NV':'NV')) ?>]</small></td>
        <?php if ($bill): ?><td class="r"><?= money($it['line_total']) ?></td><?php endif; ?></tr>
    <?php endforeach; ?>
  </table>
  <?php if ($o['notes'] !== ''): ?><div class="note"><b>NOTE:</b> <?= e($o['notes']) ?></div><?php endif; ?>
  <hr>
  <?php if ($bill): ?>
    <table>
      <?php if ((float)$o['tax'] > 0): ?>
      <tr><td>Subtotal</td><td class="r"><?= money($o['subtotal']) ?></td></tr>
      <tr><td><?= e(setting('menu_tax_label','GST')) ?> <?= e(setting('menu_tax_percent')) ?>%</td><td class="r"><?= money($o['tax']) ?></td></tr>
      <?php endif; ?>
      <tr><td class="b big">TOTAL</td><td class="r b big"><?= money($o['total']) ?></td></tr>
    </table>
    <?php if (setting('gstin')): ?><div class="c">GSTIN: <?= e(setting('gstin')) ?></div><?php endif; ?>
    <div class="c" style="margin-top:8px">Thank you — enjoy your meal!<br>Guest signature: ____________</div>
  <?php else: ?>
    <div>Items: <b><?= (int)$o['item_count'] ?></b> · ETA <?= (int)$o['eta_minutes'] ?> min · Status: <?= e(FOOD_STATUSES[$o['status']][0]) ?></div>
  <?php endif; ?>
</div>
<?php if (get('auto')): ?><script>print()</script><?php endif; ?>
</body></html>
