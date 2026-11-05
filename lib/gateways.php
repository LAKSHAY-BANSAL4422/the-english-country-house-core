<?php
/**
 * Payment gateway integrations (Razorpay, Stripe, PayPal, Paytm, UPI/QR, Pay-at-Hotel)
 * Pure-cURL implementations — no SDK dependencies, shared-hosting friendly.
 */

function gateways_enabled(): array {
    $g = [];
    if (setting('pay_online_enabled','1')==='1') {
        if (setting('pay_razorpay_enabled')==='1' && setting('pay_razorpay_key')) $g['razorpay'] = ['label'=>'Card / UPI / NetBanking (Razorpay)','icon'=>'fa-bolt','desc'=>'Instant & secure — cards, UPI apps, netbanking'];
        if (setting('pay_stripe_enabled')==='1' && setting('pay_stripe_sk')) $g['stripe'] = ['label'=>'International Card (Stripe)','icon'=>'fa-credit-card','desc'=>'Visa · Mastercard · Amex, worldwide'];
        if (setting('pay_paypal_enabled')==='1' && setting('pay_paypal_client')) $g['paypal'] = ['label'=>'PayPal','icon'=>'fa-brands fa-paypal','desc'=>'Pay with your PayPal balance or card'];
        if (setting('pay_paytm_enabled')==='1' && setting('pay_paytm_mid')) $g['paytm'] = ['label'=>'Paytm','icon'=>'fa-wallet','desc'=>'Paytm wallet, UPI & cards'];
    }
    if (setting('pay_upi_enabled')==='1' && (setting('pay_upi_id') || setting('pay_upi_qr'))) $g['upi'] = ['label'=>'UPI / Scan QR Code','icon'=>'fa-qrcode','desc'=>'Scan our QR with any UPI app, then enter the reference no.'];
    if (setting('pay_cod_enabled')==='1') $g['cod'] = ['label'=>setting('pay_cod_label','Pay at Hotel'),'icon'=>'fa-hotel','desc'=>'Reserve now, pay when you arrive — no advance needed'];
    return $g;
}

/* ==================== RAZORPAY ==================== */
function razorpay_auth(): string { return setting('pay_razorpay_key').':'.setting('pay_razorpay_secret'); }

function razorpay_create_order(array $booking): array {
    $r = http_request('https://api.razorpay.com/v1/orders', 'POST', json_encode([
        'amount' => (int)round($booking['total']*100), 'currency' => setting('currency','INR'),
        'receipt' => $booking['booking_code'], 'notes' => ['booking'=>$booking['booking_code']],
    ]), ['Content-Type: application/json'], razorpay_auth());
    if (($r['json']['id'] ?? '') === '') return ['error'=>'Razorpay order failed: '.($r['json']['error']['description'] ?? $r['error'] ?: 'HTTP '.$r['code'])];
    return ['order_id'=>$r['json']['id']];
}
function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool {
    return hash_equals(hash_hmac('sha256', $orderId.'|'.$paymentId, setting('pay_razorpay_secret')), $signature);
}
function razorpay_refund(string $paymentId, float $amount): array {
    $r = http_request("https://api.razorpay.com/v1/payments/$paymentId/refund", 'POST',
        json_encode(['amount'=>(int)round($amount*100),'speed'=>'normal']), ['Content-Type: application/json'], razorpay_auth());
    if (($r['json']['id'] ?? '') === '') return ['error'=>$r['json']['error']['description'] ?? 'Refund failed (HTTP '.$r['code'].')'];
    return ['refund_id'=>$r['json']['id'],'status'=>$r['json']['status'] ?? 'processed'];
}

/* ==================== STRIPE ==================== */
function stripe_create_session(array $booking, array $room): array {
    $success = base_url('api/payment_callback.php?gw=stripe&code='.$booking['booking_code'].'&sid={CHECKOUT_SESSION_ID}');
    $cancel  = base_url('pay.php?code='.$booking['booking_code'].'&cancelled=1');
    $params = [
        'mode'=>'payment', 'success_url'=>$success, 'cancel_url'=>$cancel,
        'client_reference_id'=>$booking['booking_code'],
        'line_items[0][price_data][currency]'=>strtolower(setting('currency','INR')),
        'line_items[0][price_data][unit_amount]'=>(int)round($booking['total']*100),
        'line_items[0][price_data][product_data][name]'=>$room['name'].' — '.$booking['nights'].' night(s)',
        'line_items[0][quantity]'=>1,
        'customer_email'=>$booking['email'],
    ];
    $r = http_request('https://api.stripe.com/v1/checkout/sessions', 'POST', http_build_query($params),
        ['Content-Type: application/x-www-form-urlencoded'], setting('pay_stripe_sk').':');
    if (($r['json']['url'] ?? '') === '') return ['error'=>'Stripe error: '.($r['json']['error']['message'] ?? 'HTTP '.$r['code'])];
    return ['url'=>$r['json']['url'], 'session_id'=>$r['json']['id']];
}
function stripe_get_session(string $sid): array {
    $r = http_request('https://api.stripe.com/v1/checkout/sessions/'.$sid, 'GET', null, [], setting('pay_stripe_sk').':');
    return $r['json'] ?? [];
}
function stripe_refund(string $paymentIntent, float $amount): array {
    $r = http_request('https://api.stripe.com/v1/refunds', 'POST',
        http_build_query(['payment_intent'=>$paymentIntent,'amount'=>(int)round($amount*100)]),
        ['Content-Type: application/x-www-form-urlencoded'], setting('pay_stripe_sk').':');
    if (($r['json']['id'] ?? '') === '') return ['error'=>$r['json']['error']['message'] ?? 'Refund failed'];
    return ['refund_id'=>$r['json']['id'],'status'=>$r['json']['status'] ?? 'succeeded'];
}

/* ==================== PAYPAL ==================== */
function paypal_base(): string { return setting('pay_paypal_mode')==='live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'; }
function paypal_token(): string {
    $r = http_request(paypal_base().'/v1/oauth2/token', 'POST', 'grant_type=client_credentials',
        ['Content-Type: application/x-www-form-urlencoded'], setting('pay_paypal_client').':'.setting('pay_paypal_secret'));
    return $r['json']['access_token'] ?? '';
}
function paypal_create_order(array $booking): array {
    $tok = paypal_token();
    if (!$tok) return ['error'=>'PayPal auth failed — check credentials'];
    $cur = setting('pay_paypal_currency','USD');
    $amt = $cur === setting('currency','INR') ? $booking['total'] : round($booking['total'] / max(1,(float)setting('pay_paypal_fx','83')), 2);
    $r = http_request(paypal_base().'/v2/checkout/orders', 'POST', json_encode([
        'intent'=>'CAPTURE',
        'purchase_units'=>[['reference_id'=>$booking['booking_code'],'amount'=>['currency_code'=>$cur,'value'=>number_format($amt,2,'.','')]]],
        'application_context'=>['return_url'=>base_url('api/payment_callback.php?gw=paypal&code='.$booking['booking_code']),
                                'cancel_url'=>base_url('pay.php?code='.$booking['booking_code'].'&cancelled=1'),
                                'brand_name'=>setting('site_name'),'user_action'=>'PAY_NOW'],
    ]), ['Content-Type: application/json','Authorization: Bearer '.$tok]);
    $approve = '';
    foreach (($r['json']['links'] ?? []) as $l) if (($l['rel'] ?? '') === 'approve') $approve = $l['href'];
    if (!$approve) return ['error'=>'PayPal order failed: '.($r['json']['message'] ?? 'HTTP '.$r['code'])];
    return ['url'=>$approve, 'order_id'=>$r['json']['id']];
}
function paypal_capture(string $orderId): array {
    $tok = paypal_token();
    $r = http_request(paypal_base()."/v2/checkout/orders/$orderId/capture", 'POST', '{}',
        ['Content-Type: application/json','Authorization: Bearer '.$tok]);
    $status = $r['json']['status'] ?? '';
    $capId = $r['json']['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
    return ['ok'=>$status === 'COMPLETED', 'capture_id'=>$capId, 'raw'=>$r['json']];
}
function paypal_refund(string $captureId, float $amount): array {
    $tok = paypal_token();
    $r = http_request(paypal_base()."/v2/payments/captures/$captureId/refund", 'POST', '{}',
        ['Content-Type: application/json','Authorization: Bearer '.$tok]);
    if (($r['json']['id'] ?? '') === '') return ['error'=>$r['json']['message'] ?? 'Refund failed'];
    return ['refund_id'=>$r['json']['id'],'status'=>$r['json']['status'] ?? 'COMPLETED'];
}

/* ==================== PAYTM ==================== */
function paytm_base(): string { return setting('pay_paytm_mode')==='production' ? 'https://securegw.paytm.in' : 'https://securegw-stage.paytm.in'; }
function paytm_generate_signature(string $params, string $key): string {
    $salt = substr(bin2hex(random_bytes(2)), 0, 4);
    $hash = hash('sha256', $params.'|'.$salt).$salt;
    $iv = '@@@@&&&&####$$$$';
    return base64_encode(openssl_encrypt($hash, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv));
}
function paytm_verify_signature(string $params, string $key, string $checksum): bool {
    $iv = '@@@@&&&&####$$$$';
    $dec = openssl_decrypt(base64_decode($checksum), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($dec === false || strlen($dec) < 4) return false;
    $salt = substr($dec, -4);
    return hash_equals(hash('sha256', $params.'|'.$salt).$salt, $dec);
}
function paytm_create_txn(array $booking): array {
    $mid = setting('pay_paytm_mid'); $orderId = $booking['booking_code'].'-'.time();
    $body = json_encode([
        'requestType'=>'Payment','mid'=>$mid,'websiteName'=>setting('pay_paytm_website','DEFAULT'),'orderId'=>$orderId,
        'callbackUrl'=>base_url('api/payment_callback.php?gw=paytm&code='.$booking['booking_code']),
        'txnAmount'=>['value'=>number_format($booking['total'],2,'.',''),'currency'=>'INR'],
        'userInfo'=>['custId'=>'CUST'.((int)($booking['user_id'] ?? 0) ?: rand(1000,9999)), 'email'=>$booking['email'],'mobile'=>$booking['phone']],
    ]);
    $sig = paytm_generate_signature($body, setting('pay_paytm_key'));
    $r = http_request(paytm_base()."/theia/api/v1/initiateTransaction?mid=$mid&orderId=$orderId", 'POST',
        json_encode(['body'=>json_decode($body,true),'head'=>['signature'=>$sig]]), ['Content-Type: application/json']);
    $token = $r['json']['body']['txnToken'] ?? '';
    if (!$token) return ['error'=>'Paytm init failed: '.($r['json']['body']['resultInfo']['resultMsg'] ?? 'HTTP '.$r['code'])];
    return ['txn_token'=>$token,'order_id'=>$orderId,'mid'=>$mid];
}
function paytm_refund(string $orderId, string $txnId, float $amount): array {
    $mid = setting('pay_paytm_mid');
    $body = json_encode(['mid'=>$mid,'txnType'=>'REFUND','orderId'=>$orderId,'txnId'=>$txnId,
        'refId'=>'REF'.time(),'refundAmount'=>number_format($amount,2,'.','')]);
    $sig = paytm_generate_signature($body, setting('pay_paytm_key'));
    $r = http_request(paytm_base().'/refund/apply', 'POST',
        json_encode(['body'=>json_decode($body,true),'head'=>['signature'=>$sig]]), ['Content-Type: application/json']);
    $st = $r['json']['body']['resultInfo']['resultStatus'] ?? '';
    if (!in_array($st, ['TXN_SUCCESS','PENDING','ACCEPTED_SUCCESS'])) return ['error'=>$r['json']['body']['resultInfo']['resultMsg'] ?? 'Refund failed'];
    return ['refund_id'=>$r['json']['body']['refundId'] ?? 'REF'.time(),'status'=>$st];
}

/* ==================== payment success (all gateways funnel here) ==================== */
function mark_booking_paid(array $booking, string $gateway, string $txnId, float $amount, array $payload = []): void {
    if ($booking['payment_status'] === 'paid') return;
    db_insert('booking_payments', ['booking_id'=>(int)$booking['id'],'gateway'=>$gateway,'txn_id'=>$txnId,
        'amount'=>$amount,'currency'=>setting('currency','INR'),'status'=>'success','payload'=>json_encode($payload)]);
    db_update('bookings', ['payment_status'=>'paid','amount_paid'=>$amount,'payment_method'=>$gateway,'status'=>'confirmed'], 'id=?', [(int)$booking['id']]);
    $room = q_row("SELECT * FROM rooms WHERE id=?", [(int)$booking['room_id']]);
    $b = array_merge($booking, ['payment_status'=>'paid','payment_method'=>$gateway]);
    send_booking_confirmation($b, $room);
    if (setting('booking_notify_email'))
        send_email(setting('booking_notify_email'), '💰 Payment received — '.$booking['booking_code'].' ('.money($amount).')',
            '<p>Booking <b>'.$booking['booking_code'].'</b> paid via <b>'.strtoupper($gateway).'</b>.</p>'.booking_email_summary($b, $room), 'booking');
    q("UPDATE leads SET stage='converted' WHERE booking_id=?", [(int)$booking['id']]);
}

/* ==================== refunds ==================== */
function refund_eligible(array $booking): array {
    $days = (int)setting('refund_days', 7);
    $daysLeft = (int)floor((strtotime($booking['checkin']) - strtotime(date('Y-m-d'))) / 86400);
    $eligible = $daysLeft >= $days && $booking['payment_status'] === 'paid';
    $pct = (float)setting('refund_percent', 100);
    return ['eligible'=>$eligible, 'days_left'=>$daysLeft, 'required_days'=>$days,
            'percent'=>$pct, 'amount'=>round((float)$booking['amount_paid'] * $pct / 100, 2)];
}
function process_refund(int $refundId): array {
    $ref = q_row("SELECT * FROM refunds WHERE id=?", [$refundId]);
    if (!$ref || in_array($ref['status'], ['completed','rejected'])) return [false, 'Refund not found / already handled'];
    $booking = q_row("SELECT * FROM bookings WHERE id=?", [(int)$ref['booking_id']]);
    $pay = q_row("SELECT * FROM booking_payments WHERE booking_id=? AND status='success' ORDER BY id DESC LIMIT 1", [(int)$ref['booking_id']]);
    if (!$booking) return [false, 'Booking missing'];
    $amount = (float)$ref['amount'];
    $result = ['error'=>'No online payment found — mark as manual refund'];
    if ($pay) {
        $payload = json_decode($pay['payload'] ?: '{}', true) ?: [];
        $result = match ($pay['gateway']) {
            'razorpay' => razorpay_refund($pay['txn_id'], $amount),
            'stripe'   => stripe_refund($payload['payment_intent'] ?? $pay['txn_id'], $amount),
            'paypal'   => paypal_refund($pay['txn_id'], $amount),
            'paytm'    => paytm_refund($pay['order_ref'], $pay['txn_id'], $amount),
            default    => ['error'=>'Gateway '.$pay['gateway'].' requires manual refund (UPI/cash) — transfer '.money($amount).' manually, then mark completed.'],
        };
    }
    if (isset($result['error'])) {
        db_update('refunds', ['status'=>'manual','gateway'=>$pay['gateway'] ?? ''], 'id=?', [$refundId]);
        return [false, $result['error']];
    }
    db_update('refunds', ['status'=>'completed','gateway'=>$pay['gateway'],'gateway_refund_id'=>$result['refund_id'],'processed_at'=>date('Y-m-d H:i:s')], 'id=?', [$refundId]);
    db_update('bookings', ['payment_status'=>'refunded'], 'id=?', [(int)$booking['id']]);
    if ($booking['email']) {
        send_email($booking['email'], '💸 Refund processed — '.$booking['booking_code'],
          '<p>Dear '.e($booking['guest_name']).',</p><p>Your refund of <b>'.money($amount).'</b> for booking <b>'.$booking['booking_code'].'</b> has been processed to your original payment method. It typically reflects in 5–7 working days.</p><p>Refund reference: '.e($result['refund_id']).'</p><p>We hope to host you again soon!</p>', 'refund');
    }
    return [true, 'Refund of '.money($amount).' processed (ref '.$result['refund_id'].')'];
}
function cancel_booking(array $booking, string $reason, string $by = 'user'): array {
    if (in_array($booking['status'], ['cancelled','checked_out'])) return [false, 'Booking cannot be cancelled'];
    db_update('bookings', ['status'=>'cancelled','cancel_reason'=>mb_substr($reason,0,490),'cancelled_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$booking['id']]);
    $el = refund_eligible($booking);
    $msg = 'Booking cancelled.';
    if ($booking['payment_status'] === 'paid') {
        if ($el['eligible']) {
            $rid = db_insert('refunds', ['booking_id'=>(int)$booking['id'],'amount'=>$el['amount'],'reason'=>$reason,'status'=>'pending','initiated_by'=>$by]);
            db_update('bookings', ['payment_status'=>'refund_initiated'], 'id=?', [(int)$booking['id']]);
            if (setting('refund_auto') === '1') { [$ok, $m] = process_refund($rid); $msg .= ' '.($ok ? $m : 'Refund queued: '.$m); }
            else $msg .= ' Refund of '.money($el['amount']).' has been initiated and will be processed by our team.';
        } else {
            $msg .= ' As per policy (cancellation within '.$el['required_days'].' days of check-in), the amount is non-refundable.';
        }
    }
    if ($booking['email']) {
        send_email($booking['email'], 'Booking cancelled — '.$booking['booking_code'],
          '<p>Dear '.e($booking['guest_name']).', your booking <b>'.$booking['booking_code'].'</b> has been cancelled.</p><p>'.$msg.'</p>', 'cancel');
    }
    if (setting('booking_notify_email'))
        send_email(setting('booking_notify_email'), '❌ Booking cancelled — '.$booking['booking_code'], '<p>Cancelled by '.$by.'. Reason: '.e($reason).'</p><p>'.$msg.'</p>', 'cancel');
    return [true, $msg];
}
