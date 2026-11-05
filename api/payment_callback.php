<?php
/** Payment gateway callbacks / verification for all providers */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/gateways.php';
db();

$gw = $_REQUEST['gw'] ?? '';
$code = $_REQUEST['code'] ?? ($_POST['ORDERID'] ?? '');
$booking = q_row("SELECT * FROM bookings WHERE booking_code=?", [explode('-', (string)$code)[0] === $code ? $code : explode('-', (string)$code)[0]]);
if (!$booking && $gw !== 'paytm') json_out(['ok'=>false,'error'=>'Booking not found'], 404);

switch ($gw) {

case 'razorpay':
    $oid = post('razorpay_order_id'); $pid = post('razorpay_payment_id'); $sig = post('razorpay_signature');
    if (!razorpay_verify_signature($oid, $pid, $sig)) json_out(['ok'=>false,'error'=>'Signature mismatch']);
    mark_booking_paid($booking, 'razorpay', $pid, (float)$booking['total'], ['order_id'=>$oid]);
    json_out(['ok'=>true]);

case 'stripe':
    $sess = stripe_get_session(get('sid'));
    if (($sess['payment_status'] ?? '') === 'paid' && ($sess['client_reference_id'] ?? '') === $booking['booking_code']) {
        mark_booking_paid($booking, 'stripe', $sess['payment_intent'] ?? $sess['id'], (float)$booking['total'], ['payment_intent'=>$sess['payment_intent'] ?? '', 'session'=>$sess['id'] ?? '']);
        redirect(base_url('booking-done.php?code='.$booking['booking_code']));
    }
    redirect(base_url('pay.php?code='.$booking['booking_code'].'&cancelled=1'));

case 'paypal':
    $orderId = get('token');
    $cap = paypal_capture($orderId);
    if ($cap['ok']) {
        mark_booking_paid($booking, 'paypal', $cap['capture_id'], (float)$booking['total'], ['paypal_order'=>$orderId]);
        redirect(base_url('booking-done.php?code='.$booking['booking_code']));
    }
    redirect(base_url('pay.php?code='.$booking['booking_code'].'&cancelled=1'));

case 'paytm':
    $post = $_POST;
    $checksum = $post['CHECKSUMHASH'] ?? '';
    unset($post['CHECKSUMHASH']);
    $orderId = $post['ORDERID'] ?? '';
    $bookingCode = explode('-', $orderId)[0];
    $booking = q_row("SELECT * FROM bookings WHERE booking_code=?", [$bookingCode]);
    if (!$booking) die('Booking not found');
    $valid = paytm_verify_signature(implode('|', array_values($post)), setting('pay_paytm_key'), $checksum);
    if ($valid && ($post['STATUS'] ?? '') === 'TXN_SUCCESS' && (float)($post['TXNAMOUNT'] ?? 0) >= (float)$booking['total'] - 1) {
        q("UPDATE booking_payments SET status='success', txn_id=? WHERE booking_id=? AND gateway='paytm' AND order_ref=?",
          [$post['TXNID'] ?? '', (int)$booking['id'], $orderId]);
        mark_booking_paid($booking, 'paytm', $post['TXNID'] ?? '', (float)$booking['total'], $post);
        redirect(base_url('booking-done.php?code='.$booking['booking_code']));
    }
    redirect(base_url('pay.php?code='.$booking['booking_code'].'&cancelled=1'));

default:
    json_out(['ok'=>false,'error'=>'Unknown gateway'], 400);
}
