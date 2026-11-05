<?php
/**
 * Restaurant / in-room dining engine:
 * menu loading, order placement (server-side pricing), WhatsApp routing to
 * unlimited staff numbers (Cloud API / any HTTP gateway / click-to-chat),
 * e-mail alert, order status helpers.
 */

const FOOD_STATUSES = [
    'new'       => ['New',        '#e67e22', 'fa-bell'],
    'accepted'  => ['Accepted',   '#2980b9', 'fa-thumbs-up'],
    'preparing' => ['Preparing',  '#8e44ad', 'fa-fire-burner'],
    'ready'     => ['On the way', '#16a085', 'fa-person-walking'],
    'delivered' => ['Delivered',  '#27ae60', 'fa-circle-check'],
    'cancelled' => ['Cancelled',  '#c0392b', 'fa-circle-xmark'],
];
const FOOD_DIETS = ['veg'=>'Veg', 'nonveg'=>'Non-Veg', 'egg'=>'Contains Egg', 'both'=>'Veg / Non-Veg options'];

/* ---------------- menu ---------------- */
function menu_load(bool $publicOnly = true): array {
    $cats = q_all("SELECT * FROM menu_categories ".($publicOnly ? "WHERE active=1 " : "")."ORDER BY sort, id");
    $items = q_all("SELECT * FROM menu_items ".($publicOnly ? "WHERE active=1 " : "")."ORDER BY sort, id");
    $by = [];
    foreach ($items as $it) $by[(int)$it['category_id']][] = $it;
    foreach ($cats as &$c) $c['items'] = $by[(int)$c['id']] ?? [];
    unset($c);
    if ($publicOnly) $cats = array_values(array_filter($cats, fn($c) => count($c['items']) > 0));
    return $cats;
}
function menu_variants(array $item): array {
    return array_values(array_filter(array_map('trim', explode(',', (string)$item['variants'])), 'strlen'));
}
function menu_time_label(?string $from, ?string $to): string {
    if (!$from || !$to) return '';
    return date('g:i a', strtotime($from)).' – '.date('g:i a', strtotime($to));
}
/** Is a category orderable right now? (only enforced when the setting is on) */
function menu_cat_open(array $cat): bool {
    if (setting('menu_enforce_timings') !== '1' || empty($cat['time_from']) || empty($cat['time_to'])) return true;
    $now = date('H:i:s');
    return $now >= $cat['time_from'] && $now <= $cat['time_to'];
}
/** Kitchen ordering hours: [open?, message] */
function menu_kitchen_open(): array {
    if (setting('menu_ordering_enabled') !== '1') return [false, 'Online ordering is paused right now — please call us to order.'];
    $f = setting('menu_kitchen_from'); $t = setting('menu_kitchen_to');
    if ($f === '' || $t === '') return [true, ''];
    $now = date('H:i');
    $open = $f <= $t ? ($now >= $f && $now <= $t) : ($now >= $f || $now <= $t);   // supports overnight hours
    return [$open, $open ? '' : 'Our kitchen takes orders from '.date('g:i a', strtotime($f)).' to '.date('g:i a', strtotime($t)).'. Please call us for anything now.'];
}
function menu_wait_minutes(): int { return max(5, (int)setting('menu_wait_minutes', '30')); }

/** Room the logged-in guest is staying in today (for auto-fill) */
function user_current_room(?array $user): string {
    if (!$user) return '';
    $today = date('Y-m-d');
    $b = q_row("SELECT r.name FROM bookings b JOIN rooms r ON r.id=b.room_id
                WHERE (b.user_id=? OR b.email=?) AND b.status IN ('confirmed','checked_in') AND b.checkin<=? AND b.checkout>=?
                ORDER BY b.status='checked_in' DESC, b.id DESC LIMIT 1", [(int)$user['id'], $user['email'], $today, $today]);
    return $b['name'] ?? '';
}

/* ---------------- WhatsApp routing ---------------- */
function menu_wa_numbers(bool $activeOnly = true): array {
    $list = json_decode(setting('menu_wa_numbers', '[]'), true) ?: [];
    $list = array_values(array_filter($list, fn($n) => !empty($n['number']) && (!$activeOnly || !empty($n['active']))));
    return $list;
}
function wa_normalize(string $num): string {
    $d = preg_replace('/\D/', '', $num);
    $cc = preg_replace('/\D/', '', setting('menu_wa_cc', '91'));
    if (strlen($d) === 10 && $cc) $d = $cc.$d;
    if (str_starts_with($d, '0') && strlen($d) === 11 && $cc) $d = $cc.substr($d, 1);
    return $d;
}
/** Send one WhatsApp text via the configured provider. Returns [ok, info]. */
function wa_send_one(string $to, string $text): array {
    $mode = setting('menu_wa_mode', 'link');
    $to = wa_normalize($to);
    if ($mode === 'cloud') {
        $tok = setting('menu_wa_cloud_token'); $pid = setting('menu_wa_cloud_phone_id');
        if (!$tok || !$pid) return [false, 'Cloud API token / phone-number ID missing'];
        $tpl = trim(setting('menu_wa_cloud_template'));
        if ($tpl !== '') {
            // template variables may not contain new lines → flatten
            $flat = trim(preg_replace('/\s*\n+\s*/', ' | ', $text));
            $payload = ['messaging_product'=>'whatsapp','to'=>$to,'type'=>'template','template'=>[
                'name'=>$tpl,'language'=>['code'=>setting('menu_wa_cloud_lang','en')],
                'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>mb_substr($flat,0,1000)]]]]]];
        } else {
            $payload = ['messaging_product'=>'whatsapp','to'=>$to,'type'=>'text','text'=>['preview_url'=>false,'body'=>mb_substr($text,0,4000)]];
        }
        $r = http_request('https://graph.facebook.com/v20.0/'.rawurlencode($pid).'/messages', 'POST', json_encode($payload),
            ['Authorization: Bearer '.$tok, 'Content-Type: application/json']);
        $ok = $r['code'] >= 200 && $r['code'] < 300 && !empty($r['json']['messages'][0]['id']);
        return [$ok, $ok ? 'sent ('.$r['json']['messages'][0]['id'].')' : 'HTTP '.$r['code'].' '.mb_substr($r['json']['error']['message'] ?? $r['error'] ?: (string)$r['body'], 0, 160)];
    }
    if ($mode === 'gateway') {
        $url = trim(setting('menu_wa_gateway_url'));
        if ($url === '') return [false, 'Gateway URL missing'];
        $rep = fn(string $s, bool $enc) => strtr($s, ['{phone}'=>$enc ? rawurlencode($to) : $to, '{phone10}'=>$enc ? rawurlencode(substr($to,-10)) : substr($to,-10),
                                                      '{message}'=>$enc ? rawurlencode($text) : $text]);
        $method = strtoupper(setting('menu_wa_gateway_method', 'GET'));
        $body = trim(setting('menu_wa_gateway_body'));
        if ($method === 'GET') {
            $r = http_request($rep($url, true));
        } else {
            $isJson = $body !== '' && $body[0] === '{';
            $b = $body === '' ? http_build_query(['to'=>$to,'body'=>$text])
               : ($isJson ? strtr($body, ['{phone}'=>$to, '{phone10}'=>substr($to,-10), '{message}'=>substr(json_encode($text, JSON_UNESCAPED_UNICODE),1,-1)])
                          : $rep($body, true));
            $r = http_request($rep($url, true), 'POST', $b, [$isJson ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded']);
        }
        $low = strtolower((string)$r['body']);
        $bad = preg_match('/"success"\s*:\s*false|"status"\s*:\s*"(error|failed|fail)"|"error"\s*:\s*"[^"]+"/', $low);
        $ok = $r['code'] >= 200 && $r['code'] < 300 && !$bad;
        return [$ok, 'HTTP '.$r['code'].' '.mb_substr(trim((string)($r['error'] ?: $r['body'])), 0, 140)];
    }
    return [false, 'click-to-chat mode (no automatic sending)'];
}
/** Send to every active staff number. Returns [ [name, number, ok, info], ... ] */
function wa_send_all(string $text): array {
    $out = [];
    foreach (menu_wa_numbers() as $n) {
        [$ok, $info] = wa_send_one($n['number'], $text);
        $out[] = ['name'=>$n['name'] ?? '', 'number'=>$n['number'], 'ok'=>$ok, 'info'=>$info];
    }
    return $out;
}
function wa_auto_enabled(): bool { return in_array(setting('menu_wa_mode', 'link'), ['cloud', 'gateway'], true); }

/* ---------------- orders ---------------- */
function food_order_code(): string {
    do { $c = 'FO'.date('md').'-'.random_int(1000, 9999); } while (q_val("SELECT id FROM food_orders WHERE order_code=?", [$c]));
    return $c;
}
function food_order_items(int $orderId): array { return q_all("SELECT * FROM food_order_items WHERE order_id=? ORDER BY id", [$orderId]); }
function food_diet_dot(string $diet): string {
    return ['veg'=>'🟢','nonveg'=>'🔴','egg'=>'🟡','both'=>'🟢🔴'][$diet] ?? '';
}
/** WhatsApp / text summary of an order */
function food_order_message(array $o, array $items): string {
    $L = [];
    $L[] = '🍽 *NEW FOOD ORDER* — '.$o['order_code'];
    $L[] = '🏠 Room: *'.($o['room_no'] !== '' ? $o['room_no'] : '—').'*';
    $L[] = '👤 Name: '.$o['guest_name'];
    $L[] = '📞 Phone: '.$o['phone'];
    $L[] = '━━━━━━━━━━━━━━';
    foreach ($items as $i => $it) {
        $L[] = ($i + 1).'. '.$it['name'].($it['variant'] !== '' ? ' ('.$it['variant'].')' : '').' × '.$it['qty'].' — '.money($it['line_total']);
    }
    $L[] = '━━━━━━━━━━━━━━';
    if ((float)$o['tax'] > 0) {
        $L[] = 'Subtotal: '.money($o['subtotal']);
        $L[] = setting('menu_tax_label','GST').' '.rtrim(rtrim(setting('menu_tax_percent'), '0'), '.').'%: '.money($o['tax']);
    }
    $L[] = '*Total: '.money($o['total']).'*  ('.$o['item_count'].' item'.($o['item_count'] == 1 ? '' : 's').')';
    if ($o['notes'] !== '') $L[] = '📝 Note: '.$o['notes'];
    $L[] = '⏱ Est. time: '.$o['eta_minutes'].' min';
    $L[] = '🕒 '.date('d M Y, h:i A', strtotime($o['created_at'] ?? 'now'));
    $L[] = 'Manage: '.base_url('admin/index.php?m=menu');
    return implode("\n", $L);
}
/** Guest-facing click-to-chat message (no admin link) */
function food_guest_message(array $o, array $items): string {
    $msg = food_order_message($o, $items);
    $msg = preg_replace('/\nManage: .*$/', '', $msg);
    return str_replace('*NEW FOOD ORDER*', '*Food order*', $msg);
}
function food_status_token(array $o): string { return $o['token']; }
function food_status_url(array $o): string { return u('menu.php').'?track='.$o['order_code'].'&t='.$o['token']; }

/**
 * Place an order. $in = [items => [[id, qty, variant]], room, name, phone, email, notes, source]
 * Prices & availability are always re-read from the database.
 * Returns [true, order+items] or [false, error message].
 */
function food_place_order(array $in, ?array $user = null): array {
    [$open, $msg] = menu_kitchen_open();
    if (!$open) return [false, $msg];

    $name  = mb_substr(trim((string)($in['name'] ?? '')), 0, 120);
    $phone = preg_replace('/[^0-9+]/', '', (string)($in['phone'] ?? ''));
    $room  = mb_substr(trim((string)($in['room'] ?? '')), 0, 110);
    $email = strtolower(trim((string)($in['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
    $notes = mb_substr(trim((string)($in['notes'] ?? '')), 0, 500);
    if ($name === '') return [false, 'Please enter your name.'];
    $pc = phone_check((string)($in['phone'] ?? ''));   // round 5: site-wide phone rule
    if (!$pc['ok']) return [false, $pc['error']];
    $phone = $pc['e164'];
    if ($room === '' && setting('menu_room_required', '1') === '1') return [false, 'Please tell us your room number (or table).'];

    // rate-limit: 6 orders / 15 min / IP
    $ip = client_ip();
    if ((int)q_val("SELECT COUNT(*) FROM food_orders WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip], 0) >= 6)
        return [false, 'Too many orders in a short time — please call us to add more.'];

    $raw = $in['items'] ?? [];
    if (!is_array($raw) || !$raw) return [false, 'Your order is empty — add a dish first.'];
    $lines = []; $subtotal = 0.0; $count = 0;
    foreach (array_slice($raw, 0, 60) as $r) {
        $id = (int)($r['id'] ?? 0); $qty = max(1, min(30, (int)($r['qty'] ?? 1)));
        $it = q_row("SELECT i.*, c.active cat_active, c.time_from, c.time_to, c.name cat_name FROM menu_items i JOIN menu_categories c ON c.id=i.category_id WHERE i.id=?", [$id]);
        if (!$it || !$it['active'] || !$it['cat_active']) return [false, 'One of the dishes is no longer on the menu — please refresh.'];
        if (!$it['available']) return [false, $it['name'].' is sold out right now — please remove it.'];
        if (!menu_cat_open($it)) return [false, $it['name'].' ('.$it['cat_name'].') is served '.menu_time_label($it['time_from'], $it['time_to']).' only.'];
        $variant = trim((string)($r['variant'] ?? ''));
        $vars = menu_variants($it);
        if ($vars && !in_array($variant, $vars, true)) $variant = $vars[0];
        if (!$vars) $variant = '';
        $line = round((float)$it['price'] * $qty, 2);
        $lines[] = ['item_id'=>(int)$it['id'],'name'=>$it['name'],'variant'=>$variant,'diet'=>$it['diet'],'qty'=>$qty,'price'=>(float)$it['price'],'line_total'=>$line];
        $subtotal += $line; $count += $qty;
    }
    $min = (float)setting('menu_min_order', '0');
    if ($min > 0 && $subtotal < $min) return [false, 'Minimum order value is '.money($min).'.'];
    $taxP = (float)setting('menu_tax_percent', '0');
    $tax = round($subtotal * $taxP / 100, 2);

    $order = [
        'order_code'=>food_order_code(), 'token'=>bin2hex(random_bytes(8)),
        'user_id'=>$user['id'] ?? null, 'room_no'=>$room, 'guest_name'=>$name, 'phone'=>$phone, 'email'=>$email ?: ($user['email'] ?? ''),
        'notes'=>$notes, 'subtotal'=>$subtotal, 'tax'=>$tax, 'total'=>round($subtotal + $tax, 2), 'item_count'=>$count,
        'status'=>'new', 'eta_minutes'=>menu_wait_minutes(), 'source'=>in_array($in['source'] ?? '', ['qr','account','website'], true) ? $in['source'] : 'website',
        'ip'=>$ip,
    ];
    $oid = db_insert('food_orders', $order);
    foreach ($lines as $l) db_insert('food_order_items', $l + ['order_id'=>$oid]);
    $order = q_row("SELECT * FROM food_orders WHERE id=?", [$oid]);
    if ($user && !empty($user['id']) && empty($user['phone'])) db_update('users', ['phone'=>$phone], 'id=?', [(int)$user['id']]);
    return [true, ['order'=>$order, 'items'=>food_order_items($oid)]];
}

/** Everything that happens after the guest gets a response: WhatsApp to staff + e-mail alert */
function food_dispatch_notifications(array $order, array $items): void {
    $text = food_order_message($order, $items);
    $status = 'link-mode';
    if (wa_auto_enabled()) {
        $res = wa_send_all($text);
        $okN = count(array_filter($res, fn($r) => $r['ok']));
        $status = $okN.'/'.count($res).' WhatsApp sent';
        if ($okN < count($res)) $status .= ' · '.implode('; ', array_map(fn($r) => $r['number'].': '.$r['info'], array_filter($res, fn($r) => !$r['ok'])));
    }
    db_update('food_orders', ['wa_status'=>mb_substr($status, 0, 250)], 'id=?', [(int)$order['id']]);
    if (setting('menu_alert_email', '1') === '1' && setting('contact_email')) {
        $rows = '';
        foreach ($items as $it) $rows .= '<tr><td style="padding:6px 8px;border-bottom:1px solid #eee">'.food_diet_dot($it['diet']).' '.e($it['name']).($it['variant'] !== '' ? ' <small>('.e($it['variant']).')</small>' : '').'</td><td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center">× '.(int)$it['qty'].'</td><td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">'.money($it['line_total']).'</td></tr>';
        send_email(setting('contact_email'), '🍽 New food order '.$order['order_code'].' — Room '.$order['room_no'],
            '<h2 style="margin:0 0 6px">New food order '.e($order['order_code']).'</h2>
             <p><b>Room:</b> '.e($order['room_no']).' &nbsp; <b>Guest:</b> '.e($order['guest_name']).' &nbsp; <b>Phone:</b> '.e($order['phone']).'</p>
             <table style="width:100%;border-collapse:collapse;font-size:14px">'.$rows.'
             <tr><td colspan="2" style="padding:8px;text-align:right"><b>Total</b></td><td style="padding:8px;text-align:right"><b>'.money($order['total']).'</b></td></tr></table>'
             .($order['notes'] !== '' ? '<p><b>Note:</b> '.e($order['notes']).'</p>' : '').
             '<p><a href="'.base_url('admin/index.php?m=menu').'" style="background:#163a2c;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">Open orders →</a></p>', 'food-order');
    }
}
