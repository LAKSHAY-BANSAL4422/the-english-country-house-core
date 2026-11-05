<?php
/** Dashboard: KPIs + visitor analytics graphs + payment-option quick toggles */
$payFlash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('do') === 'pay_toggle' && admin_can($ADMIN,'settings')) {
    csrf_check();
    $map = ['cod'=>'pay_cod_enabled','online'=>'pay_online_enabled','upi'=>'pay_upi_enabled'];
    if (isset($map[post('opt')])) {
        $key = $map[post('opt')];
        $new = setting($key,'1') === '1' ? '0' : '1';
        setting_save($key, $new);
        $labels = ['cod'=>'Pay at Hotel','online'=>'Pay Online','upi'=>'UPI / QR'];
        $payFlash = $labels[post('opt')].' is now '.($new==='1'?'ENABLED':'DISABLED').' at checkout.';
    }
}
$today = date('Y-m-d');
$k = [
  'visits_today' => (int)q_val("SELECT COUNT(*) FROM visits WHERE DATE(created_at)=?", [$today], 0),
  'visitors_30'  => (int)q_val("SELECT COUNT(DISTINCT visitor_id) FROM visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", [], 0),
  'bookings_m'   => (int)q_val("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','checked_in','checked_out') AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())", [], 0),
  'revenue_m'    => (float)q_val("SELECT COALESCE(SUM(amount_paid),0) FROM bookings WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())", [], 0),
  'leads_new'    => (int)q_val("SELECT COUNT(*) FROM leads WHERE stage='new'", [], 0),
  'leads_m'      => (int)q_val("SELECT COUNT(*) FROM leads WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())", [], 0),
  'chats_open'   => (int)q_val("SELECT COUNT(*) FROM visitor_chats WHERE status IN ('open','active')", [], 0),
  'checkins'     => (int)q_val("SELECT COUNT(*) FROM bookings WHERE checkin=? AND status IN ('confirmed')", [$today], 0),
];
$days = []; $visitSeries = []; $leadSeries = []; $bookSeries = [];
for ($i=29; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days")); $days[] = date('d M', strtotime($d));
    $visitSeries[$d]=0; $leadSeries[$d]=0; $bookSeries[$d]=0;
}
foreach (q_all("SELECT DATE(created_at) d, COUNT(*) c FROM visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") as $r) if(isset($visitSeries[$r['d']])) $visitSeries[$r['d']] = (int)$r['c'];
foreach (q_all("SELECT DATE(created_at) d, COUNT(*) c FROM leads  WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") as $r) if(isset($leadSeries[$r['d']])) $leadSeries[$r['d']] = (int)$r['c'];
foreach (q_all("SELECT DATE(created_at) d, COUNT(*) c FROM bookings WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") as $r) if(isset($bookSeries[$r['d']])) $bookSeries[$r['d']] = (int)$r['c'];
$devices = q_all("SELECT device, COUNT(*) c FROM visitors GROUP BY device");
$topPages = q_all("SELECT page, COUNT(*) c FROM visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY page ORDER BY c DESC LIMIT 8");
$srcLeads = q_all("SELECT source, COUNT(*) c FROM leads GROUP BY source ORDER BY c DESC");
$recentBookings = q_all("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY b.id DESC LIMIT 8");
$dueFollowups = q_all("SELECT la.*, l.name lead_name, l.phone FROM lead_activities la JOIN leads l ON l.id=la.lead_id WHERE la.type='followup' AND la.done=0 AND la.due_at <= DATE_ADD(NOW(), INTERVAL 1 DAY) ORDER BY la.due_at LIMIT 8");
require_once ROOT_PATH.'/lib/gateways.php';
$gwOn = gateways_enabled();
$onlineLive = (bool)array_intersect(array_keys($gwOn), ['razorpay','stripe','paypal','paytm']);
$payOpts = [
  'online' => ['🌐 Pay Online', setting('pay_online_enabled','1')==='1', $onlineLive ? 'Live at checkout: '.implode(', ', array_intersect(['razorpay','stripe','paypal','paytm'], array_keys($gwOn))) : '⚠ ON but no gateway keys yet — add Razorpay / Stripe / PayPal / Paytm keys in Settings → Payments', !$onlineLive],
  'upi'    => ['📲 UPI / QR Code', setting('pay_upi_enabled')==='1', isset($gwOn['upi']) ? 'Live at checkout ✓' : '⚠ ON but hidden — enter your UPI ID or upload the QR in Settings → Payments', !isset($gwOn['upi'])],
  'cod'    => ['🏨 Pay at Hotel', setting('pay_cod_enabled')==='1', 'Guest reserves now & pays on arrival', false],
];
?>
<?php if ($payFlash): ?><div class="alert ok"><?= e($payFlash) ?></div><?php endif; ?>
<?php if (admin_can($ADMIN,'settings')): ?>
<div class="card" style="margin-bottom:16px;padding:14px 18px">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <b style="font-size:14px">💳 Payment options at checkout:</b>
    <?php foreach ($payOpts as $opt=>$o): ?>
    <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="do" value="pay_toggle"><input type="hidden" name="opt" value="<?= $opt ?>">
      <button type="submit" title="<?= e($o[2]) ?> — click to turn <?= $o[1]?'OFF':'ON' ?>"
        style="border:0;cursor:pointer;border-radius:20px;padding:8px 15px;font-size:12.5px;font-weight:600;color:#fff;background:<?= $o[1] ? ($o[3] ? '#e67e22' : '#27ae60') : '#95a5a6' ?>">
        <?= $o[0] ?> — <?= $o[1] ? ($o[3] ? 'ON · not set up' : 'ON') : 'OFF' ?></button>
    </form>
    <?php endforeach; ?>
    <a class="btn sm ghost" href="?m=settings&tab=payments">⚙ Gateway keys &amp; QR upload</a>
  </div>
  <?php $warn = array_filter($payOpts, fn($o) => $o[1] && $o[3]); if ($warn): ?>
  <p class="hint" style="margin:8px 0 0;color:#b9600d"><i class="fa-solid fa-triangle-exclamation"></i> <?= implode(' · ', array_map(fn($o) => strip_tags($o[0]).': '.$o[2], $warn)) ?>. Guests currently see: <b><?= $gwOn ? implode(', ', array_map(fn($g) => $g['label'], $gwOn)) : 'NO payment method — bookings are blocked!' ?></b></p>
  <?php endif; ?>
</div>
<?php endif; ?>
<div class="kpis">
  <div class="kpi"><i class="fa-solid fa-eye"></i><div class="k-num"><?= number_format($k['visits_today']) ?></div><div class="k-lbl">Page views today</div></div>
  <div class="kpi"><i class="fa-solid fa-users"></i><div class="k-num"><?= number_format($k['visitors_30']) ?></div><div class="k-lbl">Unique visitors (30d)</div></div>
  <div class="kpi"><i class="fa-solid fa-calendar-check"></i><div class="k-num"><?= $k['bookings_m'] ?></div><div class="k-lbl">Bookings this month</div></div>
  <div class="kpi"><i class="fa-solid fa-indian-rupee-sign"></i><div class="k-num"><?= money($k['revenue_m']) ?></div><div class="k-lbl">Revenue this month</div></div>
  <div class="kpi"><i class="fa-solid fa-filter-circle-dollar"></i><div class="k-num"><?= $k['leads_new'] ?></div><div class="k-lbl">New leads (<?= $k['leads_m'] ?> this month)</div></div>
  <div class="kpi"><i class="fa-solid fa-door-open"></i><div class="k-num"><?= $k['checkins'] ?></div><div class="k-lbl">Check-ins today</div></div>
</div>

<?php if (admin_can($ADMIN, 'menu')):
  require_once ROOT_PATH.'/lib/food.php';
  $fk = q_row("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN status<>'cancelled' THEN total END),0) rev, SUM(status='new') fresh FROM food_orders WHERE DATE(created_at)=CURDATE()");
  $fOpen = q_all("SELECT * FROM food_orders WHERE status IN ('new','accepted','preparing','ready') ORDER BY FIELD(status,'new','accepted','preparing','ready'), id DESC LIMIT 6"); ?>
<div class="card" style="margin-bottom:16px;border-left:4px solid #163a2c">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <h3 style="margin:0">🍽 Food orders — today <?= (int)$fk['n'] ?> · <?= money($fk['rev']) ?>
      <?php if ((int)$fk['fresh']): ?><span class="badge orange"><?= (int)$fk['fresh'] ?> waiting to accept</span><?php endif; ?></h3>
    <div style="display:flex;gap:6px"><a class="btn sm" href="?m=menu">Open live orders →</a><a class="btn sm ghost" href="?m=menu&tab=items">Edit menu</a></div>
  </div>
  <?php if ($fOpen): ?>
  <div class="tbl-wrap" style="margin-top:10px"><table class="tbl"><tr><th>Order</th><th>Room</th><th>Guest</th><th>Items</th><th>Total</th><th>Status</th><th>Age</th></tr>
    <?php foreach ($fOpen as $fo): $st = FOOD_STATUSES[$fo['status']]; ?>
    <tr><td><b><?= e($fo['order_code']) ?></b></td><td><b><?= e($fo['room_no']) ?></b></td><td><?= e($fo['guest_name']) ?><br><small class="hint"><?= e($fo['phone']) ?></small></td>
      <td><?= (int)$fo['item_count'] ?></td><td><?= money($fo['total']) ?></td>
      <td><span class="badge" style="background:<?= $st[1] ?>;color:#fff"><?= $st[0] ?></span></td>
      <td><?= max(0, (int)floor((time() - strtotime($fo['created_at'])) / 60)) ?> min</td></tr>
    <?php endforeach; ?></table></div>
  <?php else: ?><p class="hint" style="margin-top:8px">No open food orders. New orders ring here and in every admin page.</p><?php endif; ?>
</div>
<?php endif; ?>
<div class="grid2">
  <div class="card"><h3>📈 Visitors, Leads &amp; Bookings — last 30 days</h3><canvas id="chMain" height="120"></canvas></div>
  <div class="card"><h3>📱 Devices &amp; Lead Sources</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div><canvas id="chDev"></canvas></div><div><canvas id="chSrc"></canvas></div>
    </div>
  </div>
</div>

<div class="grid2">
  <div class="card"><h3>🔗 Top Pages (30 days)</h3>
    <table class="tbl"><tr><th>Page</th><th style="text-align:right">Views</th></tr>
    <?php foreach ($topPages as $p): ?><tr><td><?= e($p['page']) ?></td><td style="text-align:right"><?= $p['c'] ?></td></tr><?php endforeach; ?></table>
  </div>
  <div class="card"><h3>⏰ Follow-ups due</h3>
    <?php if (!$dueFollowups): ?><p class="hint">Nothing due — great job! 🎉</p><?php endif; ?>
    <table class="tbl"><?php foreach ($dueFollowups as $f): ?>
      <tr><td><b><?= e($f['lead_name']) ?></b><br><small class="hint"><?= e(mb_substr($f['content'],0,60)) ?></small></td>
      <td><?= date('d M h:i A', strtotime($f['due_at'])) ?></td>
      <td><a class="btn sm" href="?m=leads&view=<?= $f['lead_id'] ?>">Open</a></td></tr>
    <?php endforeach; ?></table>
  </div>
</div>

<div class="card"><h3>🛎 Recent Bookings</h3>
  <div class="tbl-wrap"><table class="tbl">
  <tr><th>Code</th><th>Guest</th><th>Room</th><th>Stay</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
  <?php foreach ($recentBookings as $b): ?>
  <tr><td><b><?= e($b['booking_code']) ?></b></td><td><?= e($b['guest_name']) ?><br><small class="hint"><?= e($b['phone']) ?></small></td>
    <td><?= e($b['room_name']) ?></td><td><?= date('d M', strtotime($b['checkin'])) ?> → <?= date('d M', strtotime($b['checkout'])) ?></td>
    <td><?= money($b['total']) ?></td>
    <td><span class="badge <?= $b['payment_status']==='paid'?'green':($b['payment_status']==='refunded'?'blue':'orange') ?>"><?= e($b['payment_status']) ?></span></td>
    <td><span class="badge <?= in_array($b['status'],['confirmed','checked_in'])?'green':($b['status']==='cancelled'?'red':'gray') ?>"><?= e($b['status']) ?></span></td>
    <td><a class="btn sm ghost" href="?m=bookings&view=<?= $b['id'] ?>">View</a></td></tr>
  <?php endforeach; ?></table></div>
</div>

<script>
new Chart(document.getElementById('chMain'), {type:'line', data:{labels: <?= json_encode($days) ?>,
  datasets:[
   {label:'Page views', data: <?= json_encode(array_values($visitSeries)) ?>, borderColor:'#c9974b', backgroundColor:'rgba(201,151,75,.12)', fill:true, tension:.35},
   {label:'Leads', data: <?= json_encode(array_values($leadSeries)) ?>, borderColor:'#3498db', tension:.35},
   {label:'Bookings', data: <?= json_encode(array_values($bookSeries)) ?>, borderColor:'#2ecc71', tension:.35}
  ]}, options:{plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('chDev'), {type:'doughnut', data:{labels: <?= json_encode(array_column($devices,'device')) ?>,
  datasets:[{data: <?= json_encode(array_map('intval', array_column($devices,'c'))) ?>, backgroundColor:['#c9974b','#3498db','#2ecc71','#95a5a6']}]},
  options:{plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('chSrc'), {type:'doughnut', data:{labels: <?= json_encode(array_column($srcLeads,'source')) ?>,
  datasets:[{data: <?= json_encode(array_map('intval', array_column($srcLeads,'c'))) ?>, backgroundColor:['#0f2238','#c9974b','#3498db','#2ecc71','#e67e22','#9b59b6','#e74c3c']}]},
  options:{plugins:{legend:{position:'bottom'}}}});
</script>
