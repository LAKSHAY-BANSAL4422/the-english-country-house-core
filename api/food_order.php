<?php
/**
 * Public food-ordering API
 *   POST action=place   → create order, alert staff (dashboard + WhatsApp + e-mail)
 *   GET  action=status  → live order status for the guest tracker (code + token)
 */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../lib/food.php';
db(); sess_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'status') {
    $o = q_row("SELECT * FROM food_orders WHERE order_code=? AND token=?", [get('code'), get('t')]);
    if (!$o) json_out(['ok'=>false,'error'=>'Order not found'], 404);
    $st = FOOD_STATUSES[$o['status']];
    $base = strtotime($o['accepted_at'] ?: $o['created_at']);
    $readyBy = $base + (int)$o['eta_minutes'] * 60;
    json_out(['ok'=>true,'code'=>$o['order_code'],'status'=>$o['status'],'label'=>$st[0],'color'=>$st[1],'icon'=>$st[2],
        'eta_minutes'=>(int)$o['eta_minutes'],'ready_by'=>date('h:i A', $readyBy),'seconds_left'=>max(0, $readyBy - time()),
        'total'=>money($o['total']),'room'=>$o['room_no']]);
}

if ($action === 'place') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok'=>false,'error'=>'POST only'], 405);
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)post('csrf'))) json_out(['ok'=>false,'error'=>'Your session expired — please refresh the page and try again.'], 403);
    $items = json_decode((string)($_POST['items'] ?? '[]'), true);
    $user = current_user();
    [$ok, $res] = food_place_order([
        'items'=>is_array($items) ? $items : [], 'room'=>post('room'), 'name'=>post('name'), 'phone'=>post('phone'),
        'email'=>post('email'), 'notes'=>post('notes'), 'source'=>post('source'),
    ], $user);
    if (!$ok) json_out(['ok'=>false,'error'=>$res]);

    $order = $res['order']; $lines = $res['items'];
    // round 5: the guest's "send order on WhatsApp" link always goes to the official hotel number (ECH_WHATSAPP_NUMBER);
    // staff alerts (cloud / gateway modes) still go to the numbers in Admin → Menu → WhatsApp numbers.
    $resp = [
        'ok'=>true, 'code'=>$order['order_code'], 'token'=>$order['token'], 'total'=>money($order['total']),
        'eta_minutes'=>(int)$order['eta_minutes'], 'ready_by'=>date('h:i A', time() + (int)$order['eta_minutes'] * 60),
        'track_url'=>food_status_url($order),
        'wa_auto'=>wa_auto_enabled(),
        'wa_link'=>wa_link(ech_whatsapp(), food_guest_message($order, $lines)),
        'wa_open'=>setting('menu_wa_guest_open', '1') === '1' && !wa_auto_enabled(),
    ];
    $_SESSION['food_orders'][] = $order['order_code'];

    // answer the guest immediately, then talk to WhatsApp / mail in the background where supported
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request')) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        session_write_close();
        fastcgi_finish_request();
        food_dispatch_notifications($order, $lines);
        exit;
    }
    food_dispatch_notifications($order, $lines);
    json_out($resp);
}

json_out(['ok'=>false,'error'=>'Unknown action'], 400);
