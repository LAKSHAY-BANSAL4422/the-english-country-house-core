<?php
/** Bookings: list, filters, detail, status/payment actions, refunds, CSV export */
$flash = ''; $flashOk = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $b = q_row("SELECT * FROM bookings WHERE id=?", [(int)post('booking_id')]);
    if ($b) switch (post('do')) {
        case 'status':
            db_update('bookings', ['status'=>post('status')], 'id=?', [(int)$b['id']]);
            if (post('status')==='confirmed' && $b['email']) {
                $room = q_row("SELECT * FROM rooms WHERE id=?", [(int)$b['room_id']]);
                send_booking_confirmation($b, $room);
            }
            $flash = 'Status updated.'; break;
        case 'mark_paid':
            q("UPDATE booking_payments SET status='success' WHERE booking_id=? AND status='pending'", [(int)$b['id']]);
            mark_booking_paid($b, post('method', $b['payment_method'] ?: 'upi'), post('txn','MANUAL'), (float)$b['total']);
            $flash = 'Marked paid & confirmation email sent.'; break;
        case 'cancel':
            [$flashOk, $flash] = cancel_booking($b, post('reason','Cancelled by admin'), 'admin'); break;
        case 'refund':
            $rid = db_insert('refunds', ['booking_id'=>(int)$b['id'],'amount'=>(float)post('amount', $b['amount_paid']),'reason'=>post('reason','Admin refund'),'status'=>'pending','initiated_by'=>'admin','admin_id'=>(int)$ADMIN['id']]);
            db_update('bookings', ['payment_status'=>'refund_initiated'], 'id=?', [(int)$b['id']]);
            [$flashOk, $flash] = process_refund($rid); break;
        case 'refund_done':
            db_update('refunds', ['status'=>'completed','processed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)post('refund_id')]);
            db_update('bookings', ['payment_status'=>'refunded'], 'id=?', [(int)$b['id']]);
            if ($b['email']) send_email($b['email'], '💸 Refund completed — '.$b['booking_code'], '<p>Your refund for booking '.$b['booking_code'].' has been transferred. It may take 5–7 working days to reflect.</p>', 'refund');
            $flash = 'Refund marked completed & guest notified.'; break;
        case 'note':
            db_update('bookings', ['admin_notes'=>post('notes')], 'id=?', [(int)$b['id']]);
            $flash = 'Notes saved.'; break;
    }
}

if (get('export') === 'csv') {
    raw_output(); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=bookings-'.date('Ymd').'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['Code','Guest','Email','Phone','Room','Checkin','Checkout','Nights','Adults','Children','Subtotal','Tax','Total','Paid','Method','PayStatus','Status','Source','Created'], ",", "\"", "\\");
    foreach (q_all("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY b.id DESC") as $r)
        fputcsv($out, [$r['booking_code'],$r['guest_name'],$r['email'],$r['phone'],$r['room_name'],$r['checkin'],$r['checkout'],$r['nights'],$r['adults'],$r['children'],$r['subtotal'],$r['tax'],$r['total'],$r['amount_paid'],$r['payment_method'],$r['payment_status'],$r['status'],$r['source'],$r['created_at']], ",", "\"", "\\");
    exit;
}

/* ---------- detail view ---------- */
if (get('view')) {
    $b = q_row("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=?", [(int)get('view')]);
    if (!$b) { echo '<div class="alert err">Booking not found</div>'; return; }
    $pays = q_all("SELECT * FROM booking_payments WHERE booking_id=? ORDER BY id DESC", [(int)$b['id']]);
    $refs = q_all("SELECT * FROM refunds WHERE booking_id=? ORDER BY id DESC", [(int)$b['id']]);
    $el = refund_eligible($b);
    ?>
    <?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
    <p><a href="?m=bookings">← All bookings</a></p>
    <div class="grid2" style="grid-template-columns:1.4fr 1fr">
      <div class="card">
        <h3><?= e($b['booking_code']) ?> — <?= e($b['room_name']) ?>
          <span class="badge <?= in_array($b['status'],['confirmed','checked_in'])?'green':($b['status']==='cancelled'?'red':'gray') ?>"><?= e($b['status']) ?></span>
          <span class="badge <?= $b['payment_status']==='paid'?'green':'orange' ?>"><?= e($b['payment_status']) ?></span></h3>
        <table class="tbl">
          <tr><th>Guest</th><td><?= e($b['guest_name']) ?> · <a href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a> · <?= e($b['email']) ?></td></tr>
          <tr><th>Stay</th><td><?= date('D d M Y', strtotime($b['checkin'])) ?> → <?= date('D d M Y', strtotime($b['checkout'])) ?> (<?= $b['nights'] ?> n)</td></tr>
          <tr><th>Guests</th><td><?= $b['adults'] ?> adults, <?= $b['children'] ?> children</td></tr>
          <tr><th>Amount</th><td><b><?= money($b['total']) ?></b> (paid <?= money($b['amount_paid']) ?> via <?= e(strtoupper($b['payment_method'] ?: '—')) ?>)</td></tr>
          <tr><th>Request</th><td><?= e($b['special_request'] ?: '—') ?></td></tr>
          <tr><th>Source</th><td><?= e($b['source']) ?> · <?= $b['created_at'] ?></td></tr>
          <?php if ($b['status']==='cancelled'): ?><tr><th>Cancelled</th><td><?= e($b['cancel_reason']) ?> (<?= $b['cancelled_at'] ?>)</td></tr><?php endif; ?>
        </table>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
          <a class="btn ghost sm" target="_blank" href="<?= base_url('invoice.php?code='.$b['booking_code']) ?>"><i class="fa-regular fa-file-lines"></i> Invoice</a>
          <a class="btn green sm" target="_blank" href="<?= e(wa_link($b['phone'], 'Hello '.$b['guest_name'].', regarding your booking '.$b['booking_code'].' at '.setting('site_name').': ')) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp Guest</a>
          <a class="btn ghost sm" href="mailto:<?= e($b['email']) ?>?subject=Booking <?= e($b['booking_code']) ?>"><i class="fa-regular fa-envelope"></i> Email</a>
        </div>
        <h4 style="margin-top:20px">Payments</h4>
        <table class="tbl"><tr><th>Gateway</th><th>Txn</th><th>Amount</th><th>Status</th><th>Date</th></tr>
        <?php foreach ($pays as $p): ?><tr><td><?= e($p['gateway']) ?></td><td style="font-size:12px"><?= e($p['txn_id'] ?: $p['order_ref']) ?></td><td><?= money($p['amount']) ?></td>
          <td><span class="badge <?= $p['status']==='success'?'green':($p['status']==='pending'?'orange':'gray') ?>"><?= e($p['status']) ?></span></td><td><?= $p['created_at'] ?></td></tr><?php endforeach; ?>
        <?php if(!$pays): ?><tr><td colspan="5" class="hint">No payment records</td></tr><?php endif; ?></table>
        <?php if ($refs): ?>
        <h4 style="margin-top:20px">Refunds</h4>
        <table class="tbl"><tr><th>Amount</th><th>Status</th><th>Ref</th><th>By</th><th></th></tr>
        <?php foreach ($refs as $r): ?><tr><td><?= money($r['amount']) ?></td>
          <td><span class="badge <?= $r['status']==='completed'?'green':($r['status']==='manual'?'orange':'gray') ?>"><?= e($r['status']) ?></span></td>
          <td style="font-size:12px"><?= e($r['gateway_refund_id'] ?: '—') ?></td><td><?= e($r['initiated_by']) ?></td>
          <td><?php if (in_array($r['status'],['pending','manual','processing'])): ?>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="refund_done"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="refund_id" value="<?= $r['id'] ?>">
            <button class="btn sm green" onclick="return confirm('Mark refund as completed (money transferred)?')">Mark Completed</button></form>
          <?php endif; ?></td></tr><?php endforeach; ?></table>
        <?php endif; ?>
      </div>
      <div>
        <div class="card"><h3>Actions</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="status"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <label>Change status</label>
            <div style="display:flex;gap:8px"><select class="input" name="status">
              <?php foreach (['pending','confirmed','checked_in','checked_out','cancelled','no_show'] as $s): ?>
              <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
            </select><button class="btn">Update</button></div>
          </form>
          <?php if ($b['payment_status'] !== 'paid' && $b['status'] !== 'cancelled'): ?>
          <form method="post" style="margin-top:14px" onsubmit="return confirm('Mark this booking as PAID? Confirmation email will be sent.')">
            <?= csrf_field() ?><input type="hidden" name="do" value="mark_paid"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <label>Verify &amp; mark paid (UPI / cash / bank)</label>
            <input class="input" name="txn" placeholder="UTR / reference (optional)">
            <button class="btn green" style="margin-top:8px;width:100%">✔ Mark Paid + Confirm</button>
          </form>
          <?php endif; ?>
          <?php if (!in_array($b['status'], ['cancelled','checked_out'])): ?>
          <form method="post" style="margin-top:14px" onsubmit="return confirm('Cancel this booking?')">
            <?= csrf_field() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <label>Cancel booking <?= $b['payment_status']==='paid' ? ($el['eligible'] ? '(auto-refund '.money($el['amount']).')' : '(outside refund window)') : '' ?></label>
            <input class="input" name="reason" placeholder="Reason">
            <button class="btn danger" style="margin-top:8px;width:100%">Cancel Booking</button>
          </form>
          <?php endif; ?>
          <?php if ($b['payment_status']==='paid'): ?>
          <form method="post" style="margin-top:14px" onsubmit="return confirm('Initiate refund now?')">
            <?= csrf_field() ?><input type="hidden" name="do" value="refund"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <label>Manual refund (any amount)</label>
            <input class="input" type="number" step="0.01" name="amount" value="<?= $b['amount_paid'] ?>" max="<?= $b['amount_paid'] ?>">
            <input class="input" name="reason" placeholder="Reason" style="margin-top:8px">
            <button class="btn sec" style="margin-top:8px;width:100%">↩ Refund via <?= e(strtoupper($b['payment_method'])) ?></button>
          </form>
          <?php endif; ?>
        </div>
        <div class="card"><h3>Admin Notes</h3>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="note"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <textarea class="input" name="notes"><?= e($b['admin_notes']) ?></textarea>
            <button class="btn ghost" style="margin-top:8px">Save Notes</button></form>
        </div>
      </div>
    </div>
    <?php return;
}

/* ---------- list ---------- */
$w = ["1=1"]; $p = [];
if (get('status')) { $w[] = "b.status=?"; $p[] = get('status'); }
if (get('pay'))    { $w[] = "b.payment_status=?"; $p[] = get('pay'); }
if (get('q'))      { $w[] = "(b.booking_code LIKE ? OR b.guest_name LIKE ? OR b.phone LIKE ? OR b.email LIKE ?)"; $qq='%'.get('q').'%'; array_push($p,$qq,$qq,$qq,$qq); }
if (get('from'))   { $w[] = "b.checkin >= ?"; $p[] = get('from'); }
if (get('to'))     { $w[] = "b.checkin <= ?"; $p[] = get('to'); }
$where = implode(' AND ', $w);
$pageNo = max(1,(int)get('p',1)); $per=25; $off=($pageNo-1)*$per;
$total = (int)q_val("SELECT COUNT(*) FROM bookings b WHERE $where", $p);
$rows = q_all("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE $where ORDER BY b.id DESC LIMIT $per OFFSET $off", $p);
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="card">
<form class="filters" method="get"><input type="hidden" name="m" value="bookings">
  <div><label>Search</label><input class="input" name="q" value="<?= e(get('q')) ?>" placeholder="code / guest / phone"></div>
  <div><label>Status</label><select class="input" name="status"><option value="">All</option>
    <?php foreach (['inquiry','pending','confirmed','checked_in','checked_out','cancelled','no_show'] as $s): ?><option <?= get('status')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div><label>Payment</label><select class="input" name="pay"><option value="">All</option>
    <?php foreach (['unpaid','pending','paid','refund_initiated','refunded'] as $s): ?><option <?= get('pay')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div><label>Check-in from</label><input class="input" type="date" name="from" value="<?= e(get('from')) ?>"></div>
  <div><label>to</label><input class="input" type="date" name="to" value="<?= e(get('to')) ?>"></div>
  <button class="btn">Filter</button>
  <a class="btn ghost" href="?m=bookings&export=csv"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
</form>
<div class="tbl-wrap"><table class="tbl">
<tr><th>Code</th><th>Guest</th><th>Room</th><th>Stay</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $b): ?>
<tr>
  <td><b><?= e($b['booking_code']) ?></b><br><small class="hint"><?= date('d M', strtotime($b['created_at'])) ?> · <?= e($b['source']) ?></small></td>
  <td><?= e($b['guest_name']) ?><br><small class="hint"><?= e($b['phone']) ?></small></td>
  <td><?= e($b['room_name']) ?></td>
  <td><?= date('d M', strtotime($b['checkin'])) ?> → <?= date('d M Y', strtotime($b['checkout'])) ?></td>
  <td><?= money($b['total']) ?></td>
  <td><span class="badge <?= $b['payment_status']==='paid'?'green':($b['payment_status']==='refunded'?'blue':'orange') ?>"><?= e(str_replace('_',' ',$b['payment_status'])) ?></span><br><small class="hint"><?= e($b['payment_method']) ?></small></td>
  <td><span class="badge <?= in_array($b['status'],['confirmed','checked_in'])?'green':($b['status']==='cancelled'?'red':'gray') ?>"><?= e($b['status']) ?></span></td>
  <td><a class="btn sm ghost" href="?m=bookings&view=<?= $b['id'] ?>">Manage</a></td>
</tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="8" class="hint">No bookings found.</td></tr><?php endif; ?>
</table></div>
<?php $pages=(int)ceil($total/$per); if ($pages>1): ?><div class="pagination">
<?php for($i=1;$i<=$pages;$i++): $qs=$_GET; $qs['p']=$i; ?><a class="<?= $i===$pageNo?'cur':'' ?>" href="?<?= http_build_query($qs) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
</div>
