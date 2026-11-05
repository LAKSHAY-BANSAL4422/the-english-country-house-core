<?php
/**
 * Master cron — run every 5 minutes (cPanel schedule: each 5 min, command: php /path/to/cron/run.php)
 * Or over HTTP: https://yoursite.com/cron/run.php?key=CRON_KEY
 * Tasks: publish due social posts · process pending refunds · follow-up email alerts ·
 *        OTA iCal availability sync · weekly newsletter · stale chats · daily Google reviews refresh
 */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/gateways.php';
require_once __DIR__.'/../lib/social.php';
require_once __DIR__.'/../lib/ical.php';
db();

if (PHP_SAPI !== 'cli' && (($_GET['key'] ?? '') !== CRON_KEY)) { http_response_code(403); die('Forbidden'); }
$log = [];

/* ---------- 1. publish due social posts ---------- */
$due = q_all("SELECT * FROM social_posts WHERE status IN ('scheduled','partial') AND scheduled_at <= NOW() LIMIT 10");
foreach ($due as $sp) {
    db_update('social_posts', ['status'=>'publishing'], 'id=?', [(int)$sp['id']]);
    $res = social_publish($sp);
    $okAll = !in_array(false, array_map(fn($r)=>(bool)($r['ok'] ?? false), $res));
    $anyOk = in_array(true, array_map(fn($r)=>(bool)($r['ok'] ?? false), $res));
    db_update('social_posts', ['results'=>json_encode($res),'status'=>$okAll?'published':($anyOk?'partial':'failed'),'published_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$sp['id']]);
    $log[] = "social #{$sp['id']}: ".($okAll?'published':'partial/failed');
}

/* ---------- 2. process pending refunds (auto mode) ---------- */
if (setting('refund_auto') === '1') {
    foreach (q_all("SELECT id FROM refunds WHERE status='pending' LIMIT 10") as $r) {
        [$ok, $msg] = process_refund((int)$r['id']);
        $log[] = "refund #{$r['id']}: $msg";
    }
}

/* ---------- 3. follow-up due → email the assigned team member ---------- */
$fus = q_all("SELECT la.*, l.name lead_name, l.phone, a.email a_email, a.name a_name
  FROM lead_activities la JOIN leads l ON l.id=la.lead_id LEFT JOIN admins a ON a.id=COALESCE(l.assigned_to, la.admin_id)
  WHERE la.type='followup' AND la.done=0 AND la.due_at <= NOW() AND la.due_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
foreach ($fus as $f) {
    $to = $f['a_email'] ?: setting('booking_notify_email');
    if ($to) send_email($to, '⏰ Follow-up due: '.$f['lead_name'],
        '<p>Follow-up due now for lead <b>'.e($f['lead_name']).'</b> ('.e($f['phone']).'):</p><p>'.e($f['content']).'</p>
         <p><a href="'.base_url('admin/index.php?m=leads&view='.$f['lead_id']).'">Open lead →</a></p>', 'followup');
    $log[] = "followup alert lead #{$f['lead_id']}";
}

/* ---------- 4. OTA iCal availability sync ---------- */
foreach (ical_sync_all() as $line) $log[] = 'ota: '.$line;

/* ---------- 5. weekly newsletter ---------- */
if (setting('newsletter_enabled') === '1'
    && (int)date('w') === (int)setting('newsletter_day', 5)
    && setting('newsletter_last_sent') !== date('Y-m-d')) {
    require_once ROOT_PATH.'/admin/modules/newsletter_build.php';
    $subs = q_all("SELECT email FROM users WHERE newsletter=1 AND email_verified=1 AND active=1");
    $n = 0;
    foreach ($subs as $s) { if (send_email($s['email'], setting('newsletter_subject'), cron_newsletter_build($s['email']), 'newsletter', false)) $n++; }
    setting_save('newsletter_last_sent', date('Y-m-d'));
    $log[] = "newsletter sent to $n subscriber(s)";
}

/* ---------- 6. mark stale chats missed / closed ---------- */
q("UPDATE visitor_chats SET status='missed', closed_at=NOW() WHERE status='open' AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
q("UPDATE visitor_chats SET status='closed', closed_at=NOW() WHERE status='active' AND GREATEST(COALESCE(last_visitor_msg_at,'2000-01-01'), COALESCE(last_agent_msg_at,'2000-01-01')) < DATE_SUB(NOW(), INTERVAL 6 HOUR)");

/* ---------- 7. refresh Google reviews (once a day) ---------- */
$last = setting('reviews_last_fetch', '');
if (setting('google_places_key') && setting('google_place_id') && $last !== date('Y-m-d')) {
    $r = http_request('https://maps.googleapis.com/maps/api/place/details/json?place_id='.urlencode(setting('google_place_id')).'&fields=reviews&reviews_sort=newest&key='.urlencode(setting('google_places_key')));
    foreach (($r['json']['result']['reviews'] ?? []) as $g) {
        $ext = 'g_'.md5(($g['author_name'] ?? '').($g['time'] ?? ''));
        if (q_row("SELECT id FROM reviews WHERE external_id=?", [$ext])) continue;
        db_insert('reviews', ['source'=>'google','author'=>$g['author_name'] ?? 'Google User','avatar'=>$g['profile_photo_url'] ?? '',
          'rating'=>(float)($g['rating'] ?? 5),'content'=>$g['text'] ?? '','review_date'=>date('Y-m-d', $g['time'] ?? time()),'external_id'=>$ext,'active'=>1]);
        $log[] = 'google review imported';
    }
    setting_save('reviews_last_fetch', date('Y-m-d'));
}

echo "OK ".date('c')."\n".implode("\n", $log)."\n";
