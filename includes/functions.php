<?php
require_once __DIR__.'/ech_contact.php';   // round 5: official WhatsApp number + shared phone validation
/** Core helper library */

/* ================= settings ================= */
function setting(string $key, $default = '') {
    if (!isset($GLOBALS['__settings_cache'])) {
        $GLOBALS['__settings_cache'] = [];
        foreach (q_all("SELECT skey,svalue FROM settings") as $r) $GLOBALS['__settings_cache'][$r['skey']] = $r['svalue'];
    }
    // round 5: a retired hotel number stored in a phone setting reads as the official one (includes/ech_contact.php)
    return array_key_exists($key, $GLOBALS['__settings_cache']) ? ech_contact_setting($key, $GLOBALS['__settings_cache'][$key]) : $default;
}
function setting_save(string $key, $value): void {
    q("REPLACE INTO settings (skey,svalue) VALUES (?,?)", [$key, (string)$value]);
    if (isset($GLOBALS['__settings_cache'])) $GLOBALS['__settings_cache'][$key] = (string)$value;
}

/* ================= output ================= */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return setting('currency_symbol','₹') . number_format((float)$n, (fmod((float)$n,1)==0.0?0:2)); }
/** Discard any HTML already buffered (admin layout) before sending a file / JSON / redirect */
function raw_output(): void { while (ob_get_level() > 0) ob_end_clean(); }
function json_out($data, int $code = 200): never {
    if (!headers_sent()) raw_output();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function redirect(string $url): never { if (!headers_sent()) raw_output(); header('Location: '.$url); exit; }
/** Round 18: a phone number in international digits for tel: links (+918076185555) */
function tel_digits(string $phone): string {
    $d = preg_replace('/\D/', '', $phone);
    if (strlen($d) === 10) $d = '91'.$d;
    elseif (strlen($d) === 11 && $d[0] === '0') $d = '91'.substr($d, 1);
    return $d === '' ? '' : '+'.$d;
}
/** Round 18: $url plus the current query string (campaign codes such as utm_source or gclid survive a redirect),
 *  minus the parameters in $drop that the new address already carries in its path */
function keep_query(string $url, array $drop = ['slug', 'category']): string {
    $q = $_GET; foreach ($drop as $k) unset($q[$k]);
    return $q ? $url.(str_contains($url, '?') ? '&' : '?').http_build_query($q) : $url;
}

/* ================= session / csrf ================= */
function sess_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
}
function csrf_token(): string {
    sess_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(20));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.csrf_token().'">'; }
function csrf_check(): void {
    sess_start();
    $t = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json') || isset($_POST['ajax']) || isset($_GET['ajax'])) json_out(['ok'=>false,'error'=>'Invalid session token. Refresh the page.'], 403);
        die('Invalid CSRF token. Please go back and retry.');
    }
}

/* ================= misc ================= */
function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/','-',$s);
    return trim($s,'-') ?: 'item-'.time();
}
function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k)
        if (!empty($_SERVER[$k])) return trim(explode(',', $_SERVER[$k])[0]);
    return '';
}
function device_type(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/bot|crawl|spider|slurp/', $ua)) return 'bot';
    if (preg_match('/ipad|tablet/', $ua)) return 'tablet';
    if (preg_match('/mobile|android|iphone/', $ua)) return 'mobile';
    return 'desktop';
}
function rand_code(int $len = 6): string {
    return str_pad((string)random_int(0, (10**$len)-1), $len, '0', STR_PAD_LEFT);
}
function post(string $k, $d=''){ return isset($_POST[$k]) ? (is_array($_POST[$k]) ? $_POST[$k] : trim((string)$_POST[$k])) : $d; }
function get(string $k, $d=''){ return isset($_GET[$k]) ? (is_array($_GET[$k]) ? $_GET[$k] : trim((string)$_GET[$k])) : $d; }

/* ================= uploads ================= */
function handle_upload(string $field, string $subdir, array $allowed = ['jpg','jpeg','png','webp','gif','svg','ico','mp4','webm','pdf']): string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $f = $_FILES[$field];
    if ($f['size'] > 64*1024*1024) return '';
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return '';
    if ($ext === 'svg' && preg_match('/<\?php|<script/i', (string)file_get_contents($f['tmp_name']))) return '';
    $dir = UPLOAD_PATH.'/'.$subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = date('ymd').'-'.substr(md5(uniqid('',true)),0,8).'.'.$ext;
    if (!move_uploaded_file($f['tmp_name'], $dir.'/'.$name)) return '';
    // automatic optimisation: photos are resized / recompressed + get a .webp twin; videos go through ffmpeg when available
    if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true) && !in_array($subdir, ['qr'], true)) {
        require_once ROOT_PATH.'/lib/media.php';
        try { media_optimize_image($dir.'/'.$name); } catch (Throwable $e) { error_log('[media] '.$e->getMessage()); }
    } elseif (in_array($ext, ['mp4','webm','mov','m4v'], true) && setting('media_video_server', '1') === '1'
              && !(($_POST['pre_compressed'] ?? '') === '1' && !in_array($ext, ['mov','m4v'], true))) {   // already shrunk in the browser → no need to re-encode again
        require_once ROOT_PATH.'/lib/media.php';
        try { $v = media_optimize_video($dir.'/'.$name); if (!empty($v['ok']) && $v['path'] !== $dir.'/'.$name) $name = basename($v['path']); } catch (Throwable $e) { error_log('[media] '.$e->getMessage()); }
    }
    return UPLOAD_URL.'/'.$subdir.'/'.$name;
}

/* ================= email (native SMTP client + mail() fallback) ================= */
function send_email(string $to, string $subject, string $html, string $context = '', bool $wrap = true): bool {
    $fromEmail = setting('mail_from','no-reply@localhost');
    $fromName  = setting('mail_from_name', setting('site_name','Hotel'));
    $ok = false; $err = '';
    $body = $wrap ? email_template($subject, $html) : $html;
    if (setting('smtp_enabled') === '1' && setting('smtp_host')) {
        [$ok, $err] = smtp_send($to, $subject, $body, $fromEmail, $fromName);
    } else {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: $fromName <$fromEmail>\r\n";
        $ok = @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers);
        if (!$ok) $err = 'mail() returned false';
    }
    db_insert('email_log', ['to_email'=>$to,'subject'=>$subject,'body'=>$body,'status'=>$ok?'sent':'failed','error'=>$err,'context'=>$context]);
    return $ok;
}
function smtp_send(string $to, string $subject, string $html, string $fromEmail, string $fromName): array {
    $host = setting('smtp_host'); $port = (int)setting('smtp_port', 587);
    $user = setting('smtp_user'); $pass = setting('smtp_pass'); $secure = setting('smtp_secure','tls');
    $prefix = $secure === 'ssl' ? 'ssl://' : '';
    $fp = @stream_socket_client($prefix.$host.':'.$port, $errno, $errstr, 15);
    if (!$fp) return [false, "connect: $errstr"];
    stream_set_timeout($fp, 15);
    $read = function() use ($fp) { $d=''; while ($l = fgets($fp, 515)) { $d .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $d; };
    $cmd  = function($c) use ($fp,$read) { fwrite($fp, $c."\r\n"); return $read(); };
    $read();
    $cmd('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost'));
    if ($secure === 'tls') {
        $r = $cmd('STARTTLS');
        if (!str_starts_with($r,'220')) { fclose($fp); return [false,'STARTTLS refused']; }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return [false,'TLS handshake failed']; }
        $cmd('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }
    if ($user !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (!str_starts_with($r,'235')) { fclose($fp); return [false,'auth failed']; }
    }
    $cmd("MAIL FROM:<$fromEmail>");
    $r = $cmd("RCPT TO:<$to>");
    if (!str_starts_with($r,'250')) { fclose($fp); return [false,'recipient refused: '.trim($r)]; }
    $cmd('DATA');
    $headers = "From: =?UTF-8?B?".base64_encode($fromName)."?= <$fromEmail>\r\nTo: <$to>\r\n"
        ."Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\n"
        ."Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\nDate: ".date('r')."\r\n";
    $r = $cmd($headers."\r\n".chunk_split(base64_encode($html))."\r\n.");
    $cmd('QUIT'); fclose($fp);
    return [str_starts_with($r,'250'), str_starts_with($r,'250') ? '' : trim($r)];
}
function email_template(string $title, string $inner): string {
    $c = setting('color_primary','#c9974b'); $site = e(setting('site_name'));
    $lgp = setting('logo_print') ?: setting('logo_header');
    $logo = $lgp ? '<img src="'.base_url($lgp).'" style="max-height:70px" alt="'.$site.'">' : '<h2 style="color:'.$c.';margin:0;font-family:Georgia,serif">'.$site.'</h2>';
    return '<div style="background:#f4f1ea;padding:24px 8px;font-family:Segoe UI,Arial,sans-serif">
    <div style="max-width:620px;margin:auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #eee">
      <div style="padding:22px;text-align:center;border-bottom:3px solid '.$c.'">'.$logo.'</div>
      <div style="padding:26px;color:#333;font-size:15px;line-height:1.65">'.$inner.'</div>
      <div style="padding:16px;background:#0f2238;color:#cfd8e3;text-align:center;font-size:12px">'
      .$site.' · '.nl2br(e(setting('contact_address'))).'<br>☎ '.e(setting('contact_phone')).' · ✉ '.e(setting('contact_email')).'</div>
    </div></div>';
}

/** Designed marketing/booking email: hero band + facility icons + CTA buttons */
function email_fancy(string $heroTitle, string $heroSub, string $inner, array $buttons = [], bool $facilities = true): string {
    $c = setting('color_primary','#c9974b'); $s = setting('color_secondary','#0f2238');
    $site = e(setting('site_name'));
    $logo = setting('logo_header') ? '<img src="'.base_url(setting('logo_header')).'" style="max-height:56px" alt="'.$site.'">' : '<div style="font-family:Georgia,serif;font-size:24px;color:#fff">❦ '.$site.'</div>';
    $btnHtml = '';
    foreach ($buttons as $bx) {
        $btnHtml .= '<a href="'.$bx[1].'" style="display:inline-block;background:'.($bx[2] ?? $c).';color:#fff;text-decoration:none;padding:13px 30px;border-radius:6px;font-size:14px;letter-spacing:1px;margin:6px 6px 0 0;font-weight:600">'.$bx[0].'</a>';
    }
    $fac = '';
    if ($facilities) {
        $items = q_all("SELECT name, icon FROM amenities WHERE active=1 AND show_home=1 ORDER BY sort LIMIT 8");
        $em = ['fa-snowflake'=>'❄️','fa-utensils'=>'🍽️','fa-wifi'=>'📶','fa-person-swimming'=>'🏊','fa-charging-station'=>'🔌','fa-square-parking'=>'🅿️','fa-book'=>'📚','fa-dumbbell'=>'🏋️','fa-spa'=>'🧘','fa-basketball'=>'🏀','fa-hot-tub-person'=>'🛁','fa-mountain'=>'🧗','fa-temperature-high'=>'🧖','fa-bowl-food'=>'🥗','fa-circle-dot'=>'🎱','fa-mountain-sun'=>'🏔️'];
        $cells = '';
        foreach ($items as $ix=>$it) {
            $cells .= '<td align="center" style="padding:10px 4px;font-size:12px;color:#5b6470;width:25%"><div style="font-size:26px">'.($em[$it['icon']] ?? '✨').'</div>'.e($it['name']).'</td>';
            if ($ix % 4 === 3) $cells .= '</tr><tr>';
        }
        $fac = '<div style="padding:8px 22px 20px"><p style="text-align:center;color:'.$s.';font-weight:600;font-size:14px;letter-spacing:2px;text-transform:uppercase;margin:16px 0 4px">Our Facilities &amp; Services</p>
        <table width="100%" cellpadding="0" cellspacing="0"><tr>'.$cells.'</tr></table></div>';
    }
    return '<div style="background:#efece4;padding:24px 8px;font-family:Segoe UI,Arial,sans-serif">
    <div style="max-width:640px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e8e2d6">
      <div style="background:linear-gradient(135deg,'.$s.',#1d3a5f);padding:30px 24px;text-align:center">'.$logo.'
        <h1 style="color:#fff;font-family:Georgia,serif;font-size:26px;margin:16px 0 6px">'.$heroTitle.'</h1>
        <p style="color:'.setting('color_accent','#e8c88f').';font-size:13px;letter-spacing:2px;text-transform:uppercase;margin:0">'.$heroSub.'</p></div>
      <div style="padding:26px 26px 10px;color:#39414c;font-size:15px;line-height:1.7">'.$inner.'
        <div style="text-align:center;margin:18px 0 8px">'.$btnHtml.'</div></div>'
      .$fac.
      '<div style="padding:18px;background:'.$s.';color:#cfd8e3;text-align:center;font-size:12px;line-height:1.7">'
      .$site.' · '.nl2br(e(setting('contact_address'))).'<br>☎ '.e(setting('contact_phone')).' · ✉ '.e(setting('contact_email'))
      .'<br><a href="'.base_url().'" style="color:'.setting('color_accent','#e8c88f').'">Visit our website</a></div>
    </div></div>';
}

/* ================= HTTP helper ================= */
function http_request(string $url, string $method = 'GET', $body = null, array $headers = [], ?string $basicAuth = null): array {
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_CUSTOMREQUEST=>$method];
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : $body;
    if ($headers) $opts[CURLOPT_HTTPHEADER] = $headers;
    if ($basicAuth) $opts[CURLOPT_USERPWD] = $basicAuth;
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code'=>$code, 'body'=>$resp === false ? '' : $resp, 'error'=>$err, 'json'=>json_decode((string)$resp, true)];
}

/* ================= pricing engine ================= */
function price_for_night(array $room, string $date): array {
    $base = (float)$room['base_price'];
    $extraA = (float)$room['extra_adult_price'];
    $child = (float)$room['child_price'];
    $dow = (int)date('w', strtotime($date));
    $ruleName = null; $minNights = 1;

    static $rules = null;
    if ($rules === null) $rules = q_all("SELECT * FROM pricing_rules WHERE active=1 ORDER BY priority DESC, id DESC");

    $applied = null;
    foreach ($rules as $r) {
        if ((int)$r['room_id'] !== 0 && (int)$r['room_id'] !== (int)$room['id']) continue;
        $dateOk = true;
        if ($r['date_from'] && $date < $r['date_from']) $dateOk = false;
        if ($r['date_to'] && $date > $r['date_to']) $dateOk = false;
        if (!$dateOk) continue;
        if ($r['days_of_week'] !== '' && !in_array((string)$dow, explode(',', $r['days_of_week']), true)) continue;
        if ($r['rule_type'] === 'weekend' && $r['days_of_week'] === '' && !in_array($dow, [5,6])) continue;
        $applied = $r; break;
    }
    if ($applied) {
        $v = (float)$applied['value'];
        $base = match ($applied['price_mode']) {
            'set' => $v,
            'percent_inc' => $base * (1 + $v/100),
            'percent_dec' => $base * (1 - $v/100),
            'amount_inc' => $base + $v,
            'amount_dec' => max(0, $base - $v),
            default => $base,
        };
        if ($applied['extra_adult_price'] !== null && $applied['extra_adult_price'] !== '') $extraA = (float)$applied['extra_adult_price'];
        if ($applied['child_price'] !== null && $applied['child_price'] !== '') $child = (float)$applied['child_price'];
        $ruleName = $applied['name'];
        $minNights = max(1,(int)$applied['min_nights']);
    } elseif ((float)$room['weekend_price'] > 0 && in_array($dow, [5,6])) {
        $base = (float)$room['weekend_price']; $ruleName = 'Weekend';
    }
    return ['price'=>round($base,2), 'extra_adult'=>$extraA, 'child'=>$child, 'rule'=>$ruleName, 'min_nights'=>$minNights];
}

function quote_stay(int $roomId, string $checkin, string $checkout, int $adults = 2, int $children = 0): array {
    $room = q_row("SELECT * FROM rooms WHERE id=? AND active=1", [$roomId]);
    if (!$room) return ['error'=>'Room not found'];
    $in = strtotime($checkin); $out = strtotime($checkout);
    if (!$in || !$out || $out <= $in) return ['error'=>'Invalid dates'];
    if ($in < strtotime(date('Y-m-d'))) return ['error'=>'Check-in date is in the past'];
    $nights = (int)round(($out - $in) / 86400);
    if ($nights > 30) return ['error'=>'Maximum stay is 30 nights'];
    if ($adults > (int)$room['max_adults']) return ['error'=>'Max '.$room['max_adults'].' adults for this room'];
    if ($children > (int)$room['max_children']) return ['error'=>'Max '.$room['max_children'].' children for this room'];

    $extraAdults = max(0, $adults - (int)$room['base_occupancy']);
    $lines = []; $subtotal = 0; $minNightsReq = 1;
    for ($d = $in; $d < $out; $d += 86400) {
        $date = date('Y-m-d', $d);
        $p = price_for_night($room, $date);
        $night = $p['price'] + $extraAdults * $p['extra_adult'] + $children * $p['child'];
        $lines[] = ['date'=>$date, 'room'=>$p['price'], 'extra_adult'=>$extraAdults * $p['extra_adult'], 'child'=>$children * $p['child'], 'total'=>round($night,2), 'rule'=>$p['rule']];
        $subtotal += $night;
        $minNightsReq = max($minNightsReq, $p['min_nights']);
    }
    if ($nights < $minNightsReq) return ['error'=>"Minimum $minNightsReq nights required for the selected dates"];
    $taxP = (float)setting('tax_percent', 0);
    $tax = round($subtotal * $taxP / 100, 2);
    return ['room'=>$room,'nights'=>$nights,'adults'=>$adults,'children'=>$children,'extra_adults'=>$extraAdults,
            'lines'=>$lines,'subtotal'=>round($subtotal,2),'tax_percent'=>$taxP,'tax'=>$tax,'total'=>round($subtotal+$tax,2)];
}

/** Units of the room still free for every night of the range (bookings + OTA/manual blocks) */
function room_available(int $roomId, string $checkin, string $checkout): bool {
    $room = q_row("SELECT total_units FROM rooms WHERE id=?", [$roomId]);
    if (!$room) return false;
    // any OTA / manual block overlapping = room fully blocked for those dates
    $blocked = (int)q_val("SELECT COUNT(*) FROM room_blocks WHERE room_id=? AND date_from < ? AND date_to > ?",
        [$roomId, $checkout, $checkin], 0);
    if ($blocked > 0 && (int)$room['total_units'] <= 1) return false;
    $booked = (int)q_val(
        "SELECT COALESCE(MAX(cnt),0) FROM (
           SELECT checkin d, (SELECT COUNT(*) FROM bookings b2 WHERE b2.room_id=? AND b2.status IN ('pending','confirmed','checked_in')
                    AND b2.checkin < ? AND b2.checkout > ? AND b2.checkin <= b1.checkin AND b2.checkout > b1.checkin) cnt
           FROM bookings b1 WHERE b1.room_id=? AND b1.status IN ('pending','confirmed','checked_in') AND b1.checkin < ? AND b1.checkout > ?
         ) t", [$roomId, $checkout, $checkin, $roomId, $checkout, $checkin], 0);
    return ($booked + $blocked) < (int)$room['total_units'];
}

/* ================= leads ================= */
function create_lead(array $d): int {
    $phone = phone_normalize($d['phone'] ?? '');   // round 5: one stored format (+91XXXXXXXXXX) so duplicates match
    if ($phone || !empty($d['email'])) {
        $existing = q_row("SELECT id FROM leads WHERE ((phone<>'' AND phone=?) OR (email<>'' AND email=?)) AND stage NOT IN ('converted','lost') AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY id DESC LIMIT 1",
            [$phone, $d['email'] ?? '']);
        if ($existing) {
            db_insert('lead_activities', ['lead_id'=>(int)$existing['id'],'type'=>'system','content'=>'New enquiry ('.($d['source'] ?? 'website').'): '.mb_substr((string)($d['message'] ?? ''),0,500)]);
            $merge = [];
            if (!empty($d['booking_id'])) $merge['booking_id'] = (int)$d['booking_id'];
            if (!empty($d['room_id'])) $merge['room_id'] = (int)$d['room_id'];
            if ($merge) db_update('leads', $merge, 'id=?', [(int)$existing['id']]);
            return (int)$existing['id'];
        }
    }
    $id = db_insert('leads', [
        'name'=>$d['name'] ?? '', 'email'=>$d['email'] ?? '', 'phone'=>$phone,
        'source'=>$d['source'] ?? 'contact', 'room_id'=>isset($d['room_id'])?(int)$d['room_id']:null,
        'booking_id'=>isset($d['booking_id'])?(int)$d['booking_id']:null,
        'message'=>$d['message'] ?? '', 'stage'=>'new', 'meta'=>json_encode($d['meta'] ?? []),
    ]);
    db_insert('lead_activities', ['lead_id'=>$id,'type'=>'system','content'=>'Lead created from '.($d['source'] ?? 'website')]);
    return $id;
}

/* ================= Google Tag Manager (round 18; round 19: Google's standard snippet; v20: the marketing team's Google tag) =================
 * The marketing team's container. gtm_head() is printed immediately after <head> on every page (includes/header.php,
 * invoice.php, the maintenance page), exactly as GTM's "Install manually" screen gives it (Google tag for GTM-NPSXP9J7),
 * once per page. gtm_body() keeps GTM's <noscript> fallback right after <body>; it only runs for visitors without
 * JavaScript, so it never counts a visit twice. GA4, Google Ads and Meta are set up inside GTM, so the site carries no
 * other gtag / Ads / Pixel code. The site's form events (form_submit_success) still go into the same dataLayer. */
const GTM_ID = 'GTM-NPSXP9J7';
function gtm_head(): string {
    return '<script async src="https://www.googletagmanager.com/gtm.js?id='.GTM_ID.'"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag(\'js\', new Date());
</script>
';
}
function gtm_body(): string {
    return '<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.GTM_ID.'"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
';
}

/* ================= visitor tracking ================= */
function track_visit(string $page): void {
    if (device_type() === 'bot') return;
    sess_start();
    $key = $_COOKIE['ech_vk'] ?? '';
    if (!$key) { $key = bin2hex(random_bytes(16)); setcookie('ech_vk', $key, time()+86400*365, '/'); }
    $v = q_row("SELECT id FROM visitors WHERE visitor_key=?", [$key]);
    if (!$v) {
        $vid = db_insert('visitors', ['visitor_key'=>$key,'ip'=>client_ip(),'user_agent'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,490),
            'device'=>device_type(),'first_page'=>$page,'referrer'=>substr($_SERVER['HTTP_REFERER'] ?? '',0,490)]);
    } else $vid = (int)$v['id'];
    db_insert('visits', ['visitor_id'=>$vid,'page'=>substr($page,0,250),'referrer'=>substr($_SERVER['HTTP_REFERER'] ?? '',0,490)]);
    $_SESSION['pv_count'] = ($_SESSION['pv_count'] ?? 0) + 1;
}

/* ================= whatsapp ================= */
function wa_link(string $phone = '', string $text = ''): string {
    // round 5: every guest-facing WhatsApp link goes to the official number (ECH_WHATSAPP_NUMBER) unless another
    // current number is passed explicitly; a retired number is never used.
    $phone = preg_replace('/[^0-9]/','', ($phone !== '' && !ech_is_old_number($phone)) ? $phone : ech_whatsapp());
    if (strlen($phone) === 10) $phone = '91'.$phone;
    return 'https://wa.me/'.$phone.($text ? '?text='.rawurlencode($text) : '');
}

/* ================= booking helpers ================= */
function new_booking_code(): string {
    do { $c = 'ECH'.date('y').strtoupper(substr(bin2hex(random_bytes(4)),0,5)); }
    while (q_row("SELECT id FROM bookings WHERE booking_code=?", [$c]));
    return $c;
}
function booking_email_summary(array $b, array $room): string {
    return '<table style="width:100%;border-collapse:collapse;font-size:14px">'.
      implode('', array_map(fn($r)=>'<tr><td style="padding:8px 12px;border:1px solid #eee;background:#faf7f2;width:42%"><b>'.$r[0].'</b></td><td style="padding:8px 12px;border:1px solid #eee">'.$r[1].'</td></tr>', [
        ['Booking ID', e($b['booking_code'])], ['Room', e($room['name'])],
        ['Check-in', date('D, d M Y', strtotime($b['checkin'])).' · from '.e(setting('checkin_time','2 PM'))],
        ['Check-out', date('D, d M Y', strtotime($b['checkout'])).' · till '.e(setting('checkout_time','11 AM'))],
        ['Guests', $b['adults'].' Adult(s)'.($b['children']?', '.$b['children'].' Child(ren)':'')],
        ['Nights', $b['nights']], ['Total', money($b['total'])],
        ['Payment', e(strtoupper($b['payment_method'] ?: '—')).' · '.e($b['payment_status'])],
      ])).'</table>';
}

/** Beautiful booking confirmation email */
function send_booking_confirmation(array $b, array $room, string $headline = 'Your booking is confirmed! 🎉'): void {
    if (!$b['email']) return;
    $invoiceUrl = base_url('invoice.php?code='.$b['booking_code'].'&e='.md5($b['booking_code'].APP_SECRET));
    $inner = '<p>Dear <b>'.e($b['guest_name']).'</b>,</p>
      <p>Thank you for choosing '.e(setting('site_name')).'. We can\'t wait to welcome you to the hills! Here are your stay details:</p>'
      .booking_email_summary($b, $room).
      '<p style="font-size:13px;color:#78818d;margin-top:12px">📍 '.nl2br(e(setting('contact_address'))).'<br>
       🕐 Check-in from '.e(setting('checkin_time')).' · Check-out till '.e(setting('checkout_time')).'</p>';
    $html = email_fancy($headline, 'Booking '.$b['booking_code'], $inner, [
        ['📄 View Invoice', $invoiceUrl],
        ['🏡 Visit Website', base_url(), setting('color_secondary','#0f2238')],
        ['💬 WhatsApp Us', wa_link('', 'Hi! My booking ID is '.$b['booking_code']), '#25d366'],
    ]);
    send_email($b['email'], '✅ '.$headline.' — '.$b['booking_code'].' | '.setting('site_name'), $html, 'booking', false);
}

/* ================= permissions ================= */
function perm_list(): array {
    return [
        'dashboard'=>'Dashboard & Analytics','bookings'=>'Bookings','menu'=>'Restaurant Menu & Food Orders','leads'=>'Lead Management','chat'=>'Live Chat (Agent)',
        'rooms'=>'Rooms & Pricing','ota'=>'OTA Channel Manager','amenities'=>'Amenities','cms'=>'Pages / CMS / Builder','gallery'=>'Gallery','blog'=>'Blog','faqs'=>'FAQs',
        'forms'=>'Form Builder & Submissions','reviews'=>'Reviews','social'=>'Social Media Scheduler','popups'=>'Popups','documents'=>'Documents (Letterhead/Invoice/MOU)',
        'seo'=>'SEO / AEO / GEO','appearance'=>'Appearance & Header/Footer','settings'=>'Site Settings','team'=>'Team Management','payments'=>'Payments & Refunds','activity'=>'Activity Log',
    ];
}

/* ================= activity audit log ================= */
function log_activity(?int $adminId, string $module, string $action, string $detail = ''): void {
    db_insert('admin_activity', ['admin_id'=>$adminId,'module'=>substr($module,0,58),'action'=>substr($action,0,58),
        'detail'=>mb_substr($detail,0,2000),'ip'=>client_ip()]);
}

/* ================= SEO-friendly URLs ================= */
/**
 * u('rooms.php') → /rooms · u('index.php') → site root · u('room.php?slug=x') → /room/x
 * u('post.php?slug=x') → /post/x · u('page.php?slug=x') → /p/x · u('blog.php?category=x') → /blog/category/x
 * Honors the clean_urls setting (turn off in Settings if the host lacks mod_rewrite).
 */
function u(string $path = ''): string {
    if ($path === '' || $path === 'index.php' || $path === '/') return base_url('');
    if (preg_match('#^https?://#', $path)) return $path;
    if (setting('clean_urls', '1') !== '1') return base_url($path);
    $qs = '';
    $file = $path;
    if (str_contains($path, '?')) [$file, $qs] = explode('?', $path, 2);
    parse_str($qs, $q);
    $slugOf = fn($k) => isset($q[$k]) && preg_match('/^[a-z0-9-]+$/i', (string)$q[$k]);
    if ($file === 'room.php' && $slugOf('slug')) { $out = 'room/'.$q['slug']; unset($q['slug']); }
    elseif ($file === 'post.php' && $slugOf('slug')) { $out = 'post/'.$q['slug']; unset($q['slug']); }
    elseif ($file === 'page.php' && $slugOf('slug')) { $out = 'p/'.$q['slug']; unset($q['slug']); }
    elseif ($file === 'blog.php' && $slugOf('category')) { $out = 'blog/category/'.$q['category']; unset($q['category']); }
    elseif (preg_match('/^[a-z0-9-]+\.php$/i', $file)) { $out = substr($file, 0, -4); }
    else { $out = $file; }
    $left = http_build_query($q);
    return base_url($out . ($left ? '?'.$left : ''));
}

/* ================= brute-force protection (24h IP ban after 3 wrong passwords) ================= */
function ip_banned(?string $ip = null): ?array {
    $ip = $ip ?: client_ip();
    if ($ip === '') return null;
    return q_row("SELECT * FROM ip_bans WHERE ip=? AND until > NOW()", [$ip]);
}
function record_failed_login(string $email): bool {
    $ip = client_ip();
    if ($ip === '') return false;
    db_insert('login_attempts', ['ip'=>$ip, 'email'=>mb_substr($email,0,180)]);
    $fails = (int)q_val("SELECT COUNT(*) FROM login_attempts WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)", [$ip], 0);
    if ($fails >= 3) {
        q("REPLACE INTO ip_bans (ip, reason, until) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))",
          [$ip, '3 wrong password attempts ('.mb_substr($email,0,60).')']);
        log_activity(null, 'security', 'ip_banned', "IP $ip blocked 24h after 3 failed logins ($email)");
        return true;
    }
    return false;
}
function clear_failed_logins(): void {
    $ip = client_ip();
    if ($ip !== '') db_delete('login_attempts', 'ip=?', [$ip]);
}

/* ================= maintenance / under-construction mode ================= */
function maintenance_gate(): void {
    if (setting('maintenance_mode') !== '1') return;
    if (current_admin()) return;                       // admins browse normally
    if (($_GET['preview'] ?? '') === CRON_KEY) return; // secret preview link
    http_response_code(503);
    header('Retry-After: 86400');
    $logo = setting('logo_header') ? '<img src="'.base_url(setting('logo_header')).'" style="max-height:90px;margin-bottom:18px" alt="">' : '';
    echo '<!DOCTYPE html><html lang="en"><head>'."\n".gtm_head().'<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>'.e(setting('site_name')).' — Coming Soon</title><meta name="robots" content="noindex">
    <style>body{margin:0;font-family:Georgia,serif;background:linear-gradient(135deg,'.e(setting('color_secondary','#0f2238')).',#1d3a5f);color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:20px}
    .box{max-width:640px}.fan{font-size:54px;color:'.e(setting('color_primary','#c9974b')).';animation:spin 9s linear infinite;display:inline-block}
    @keyframes spin{to{transform:rotate(360deg)}}
    h1{font-size:clamp(28px,5vw,44px);margin:14px 0}.rule{letter-spacing:5px;font-size:12px;text-transform:uppercase;color:'.e(setting('color_accent','#e8c88f')).'}
    p{color:#cdd8e5;font-size:17px;line-height:1.7}
    .btns a{display:inline-block;margin:8px;padding:13px 30px;border-radius:6px;text-decoration:none;font-family:sans-serif;font-size:14px;letter-spacing:1px}
    .call{background:'.e(setting('color_primary','#c9974b')).';color:#fff}.wa{background:#25d366;color:#fff}</style></head>
    <body>'."\n".gtm_body().'<div class="box">'.$logo.'<div class="fan">❋</div>
    <div class="rule">'.e(setting('site_tagline')).'</div>
    <h1>'.e(setting('site_name')).'<br>Under Construction</h1>
    <p>'.e(setting('maintenance_msg')).'</p>
    <div class="btns"><a class="call" href="tel:'.e(tel_digits(setting('contact_phone'))).'">☎ Call '.e(setting('contact_phone')).'</a>
    <a class="wa" href="'.e(wa_link('', 'Hi! I would like to book a stay.')).'">WhatsApp Us</a></div>
    </div></body></html>';
    exit;
}

/* ================= header/footer menus ================= */
function menu_items(): array {
    $items = json_decode(setting('menu_items','[]'), true) ?: [];
    return array_values(array_filter($items, fn($m)=>!empty($m['visible'])));
}
function footer_links(): array {
    $items = json_decode(setting('footer_links','[]'), true) ?: [];
    return array_values(array_filter($items, fn($m)=>!empty($m['visible'])));
}
/** Site-wide photos that can be swapped from Admin → Gallery ("Use this image for…") */
function site_img_slots(): array {
    return ['hero'=>['Home hero & default page banner','assets/img/hero.jpg'], 'rooms_banner'=>['Rooms / booking / contact banner','assets/img/rooms-banner.jpg'],
            'about'=>['About-us photo','assets/img/about.jpg']];
}
function site_img(string $key): string {
    $slots = site_img_slots();
    $v = setting('img_'.$key);
    return base_url($v !== '' ? $v : ($slots[$key][1] ?? 'assets/img/placeholder.jpg'));
}
/** Room photos helpers: cover image (explicit or first photo) + ordered gallery */
function room_images(array $r): array {
    $imgs = json_decode($r['images'] ?? '[]', true);
    return is_array($imgs) ? array_values(array_filter($imgs, 'is_string')) : [];
}
function room_cover(array $r): string {
    if (!empty($r['cover_image'])) return $r['cover_image'];
    $imgs = room_images($r);
    return $imgs[0] ?? '';
}
/**
 * Round 9: the public site's rooms come from data/rooms.php (the five room categories, in that file's order),
 * not from the rooms table, so the cards, room pages, menu and forms always show exactly those rooms with their own
 * photos, whatever rows exist in Admin → Rooms. The rows are shaped like database rows so the existing templates
 * work unchanged. Without the file (or if it is empty) the active database rooms are used, as before.
 */
function room_file(): array {
    static $list = null;
    if ($list === null) { $file = ROOT_PATH.'/data/rooms.php'; $list = is_file($file) ? include $file : []; if (!is_array($list)) $list = []; }
    return $list;
}
function public_rooms(): array {
    static $rows = null;
    if ($rows !== null) return $rows;
    if (!room_file()) return $rows = q_all("SELECT * FROM rooms WHERE active=1 ORDER BY sort, id");
    $rows = []; $n = 0;
    foreach (room_file() as $slug => $d) {
        $photos = array_values(array_filter((array)($d['photos'] ?? []), 'is_string'));
        $rows[] = ['id'=>++$n, 'slug'=>(string)$slug, 'name'=>(string)$d['name'], 'room_size'=>(string)($d['size'] ?? ''),
            'short_desc'=>(string)($d['short'] ?? ''), 'description'=>(string)($d['about'] ?? ''), 'pets_allowed'=>(int)($d['pets'] ?? 0),
            'bed_type'=>(string)($d['bed'] ?? ''), 'view_type'=>(string)($d['view'] ?? ''), 'base_occupancy'=>(int)($d['sleeps'] ?? 2),
            'max_adults'=>(int)($d['max_adults'] ?? 2), 'max_children'=>(int)($d['max_children'] ?? 0),
            'images'=>json_encode($photos), 'cover_image'=>$photos[0] ?? '', 'amenity_ids'=>json_encode(array_map('intval', (array)($d['amenity_ids'] ?? []))),
            'highlights'=>'', 'video_url'=>'', 'video_file'=>'',
            // round 14: SEO meta, H1 subline, photo alt text, room questions and closing links line from the file
            'seo_title'=>(string)($d['seo_title'] ?? ''), 'seo_desc'=>(string)($d['seo_desc'] ?? ''), 'seo_keywords'=>(string)($d['seo_keywords'] ?? ''),
            'subline'=>(string)($d['subline'] ?? ''), 'photo_alts'=>array_combine($photos, array_pad(array_slice(array_map('strval', (array)($d['alts'] ?? [])), 0, count($photos)), count($photos), '')) ?: [],
            'faqs'=>array_values(array_filter((array)($d['faqs'] ?? []), fn($f) => is_array($f) && count($f) === 2)), 'more'=>(string)($d['more'] ?? ''),
            'active'=>1, 'sort'=>$n, 'from_file'=>1, 'old_slugs'=>array_map('strval', (array)($d['old'] ?? [])),
            // round 19: washrooms, and the rooms a combined option is made of (the Charlotte Tree House Duplex)
            'baths'=>(int)($d['baths'] ?? 0), 'combo'=>array_map('strval', (array)($d['combo'] ?? []))];
    }
    return $rows;
}
/** Round 19: how many rooms the resort has. A combined option (the duplex = two tree houses) is not an extra room. */
function rooms_count(): int { return count(array_filter(public_rooms(), fn($r) => empty($r['combo']))); }
/** Round 19: "Queen bed", "Two Queen beds" */
function room_bed_label(string $bed): string { return $bed === '' ? '' : $bed.(preg_match('/^(two|three|four|[2-9])\b/i', $bed) ? ' beds' : ' bed'); }
/** one public room by slug; ['redirect' => new slug] for an earlier address of a room; null when unknown */
function public_room(string $slug): ?array {
    if (!room_file()) return q_row("SELECT * FROM rooms WHERE slug=?", [$slug]);
    foreach (public_rooms() as $r) {
        if ($r['slug'] === $slug) return $r;
        if (in_array($slug, $r['old_slugs'], true)) return ['redirect'=>$r['slug']];
    }
    return null;
}
/** round 14: alt text for a room photo (data/rooms.php 'alts'); falls back to the room name */
function room_photo_alt(array $r, string $img): string {
    $a = $r['photo_alts'][$img] ?? '';
    return $a !== '' ? $a : $r['name'].' at '.setting('site_name').', Mussoorie';
}
/** round 14: {amenities} {explore} {jabarkhet} {dhanaulti} {rooms} {faqs} in room copy become the page addresses */
function rd_copy_links(string $html): string {
    return strtr($html, ['{amenities}'=>u('amenities.php'), '{explore}'=>u('explore.php'), '{rooms}'=>u('rooms.php'), '{faqs}'=>u('faqs.php'),
        '{jabarkhet}'=>u('page.php?slug=stay-near-jabarkhet-nature-reserve'), '{dhanaulti}'=>u('page.php?slug=hotel-near-dhanaulti')]);
}
/** smaller copy of a supplied room photo ({file}-800.jpg) when it exists, else the photo itself */
function room_thumb(string $img): string {
    $small = preg_replace('/\.jpe?g$/i', '-800.jpg', $img);
    return ($small !== $img && is_file(ROOT_PATH.'/'.$small)) ? $small : $img;
}
function menu_url(string $u): string {
    return preg_match('#^(https?://|tel:|mailto:|sms:|\#|/)#i',$u) ? $u : u($u);
}

/* ================= newsletter ================= */
function unsub_token(string $email): string { return substr(md5(strtolower($email).'|unsub|'.APP_SECRET), 0, 20); }
