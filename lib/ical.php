<?php
/** iCal (RFC 5545) export/import for OTA availability sync */

/** Build iCal feed of a room's busy dates (bookings + blocks) — give this URL to OTAs (Airbnb etc.) */
function ical_export_room(array $room): string {
    $lines = ["BEGIN:VCALENDAR","VERSION:2.0","PRODID:-//".setting('site_name')."//Channel Manager//EN","CALSCALE:GREGORIAN"];
    $events = [];
    foreach (q_all("SELECT booking_code ref, checkin df, checkout dt FROM bookings WHERE room_id=? AND status IN ('pending','confirmed','checked_in') AND checkout >= CURDATE()", [(int)$room['id']]) as $b)
        $events[] = ['ref'=>'BOOK-'.$b['ref'], 'df'=>$b['df'], 'dt'=>$b['dt'], 'summary'=>'Booked (Direct)'];
    foreach (q_all("SELECT id, source, ref, date_from df, date_to dt FROM room_blocks WHERE room_id=? AND date_to >= CURDATE()", [(int)$room['id']]) as $bl)
        $events[] = ['ref'=>'BLK-'.$bl['id'], 'df'=>$bl['df'], 'dt'=>$bl['dt'], 'summary'=>'Blocked ('.$bl['source'].')'];
    foreach ($events as $ev) {
        $lines[] = "BEGIN:VEVENT";
        $lines[] = "UID:".$ev['ref']."@".parse_url(base_url(), PHP_URL_HOST);
        $lines[] = "DTSTAMP:".gmdate('Ymd\THis\Z');
        $lines[] = "DTSTART;VALUE=DATE:".date('Ymd', strtotime($ev['df']));
        $lines[] = "DTEND;VALUE=DATE:".date('Ymd', strtotime($ev['dt']));
        $lines[] = "SUMMARY:".$ev['summary'];
        $lines[] = "END:VEVENT";
    }
    $lines[] = "END:VCALENDAR";
    return implode("\r\n", $lines);
}

/** Parse an external iCal feed into [['from'=>Y-m-d,'to'=>Y-m-d,'uid'=>..,'summary'=>..], ...] */
function ical_parse(string $ics): array {
    $events = []; $cur = null;
    foreach (preg_split('/\r\n|\n|\r/', $ics) as $line) {
        $line = trim($line);
        if ($line === 'BEGIN:VEVENT') { $cur = ['uid'=>'','summary'=>'','from'=>'','to'=>'']; continue; }
        if ($line === 'END:VEVENT') { if ($cur && $cur['from'] && $cur['to']) $events[] = $cur; $cur = null; continue; }
        if ($cur === null) continue;
        if (preg_match('/^UID[^:]*:(.+)$/i', $line, $m)) $cur['uid'] = trim($m[1]);
        if (preg_match('/^SUMMARY[^:]*:(.+)$/i', $line, $m)) $cur['summary'] = trim($m[1]);
        if (preg_match('/^DTSTART[^:]*:(\d{8})/i', $line, $m)) $cur['from'] = substr($m[1],0,4).'-'.substr($m[1],4,2).'-'.substr($m[1],6,2);
        if (preg_match('/^DTEND[^:]*:(\d{8})/i', $line, $m)) $cur['to'] = substr($m[1],0,4).'-'.substr($m[1],4,2).'-'.substr($m[1],6,2);
    }
    return $events;
}

/** Pull one OTA room mapping's iCal feed and sync room_blocks. Returns [imported, message] */
function ical_sync_mapping(array $map, array $channel): array {
    if (!$map['ical_import_url']) return [0, 'No import URL set'];
    $r = http_request($map['ical_import_url']);
    if ($r['code'] !== 200 || stripos($r['body'], 'BEGIN:VCALENDAR') === false) return [0, 'Fetch failed (HTTP '.$r['code'].')'];
    $events = ical_parse($r['body']);
    $srcTag = 'ota:'.$channel['id'].':'.$map['id'];
    // replace this source's future blocks with the fresh feed
    db_delete('room_blocks', "room_id=? AND source=? AND date_to >= CURDATE()", [(int)$map['room_id'], $srcTag]);
    $n = 0;
    foreach ($events as $ev) {
        if ($ev['to'] < date('Y-m-d')) continue;
        // skip events that are our own exported bookings echoed back
        if (str_starts_with($ev['uid'], 'BOOK-') || str_starts_with($ev['uid'], 'BLK-')) continue;
        db_insert('room_blocks', ['room_id'=>(int)$map['room_id'],'date_from'=>$ev['from'],'date_to'=>$ev['to'],
            'source'=>$srcTag,'ref'=>substr($ev['uid'],0,180),'note'=>substr($channel['name'].': '.$ev['summary'],0,250)]);
        $n++;
    }
    db_update('ota_channels', ['last_sync'=>date('Y-m-d H:i:s')], 'id=?', [(int)$channel['id']]);
    return [$n, "$n busy period(s) imported from {$channel['name']}"];
}

/** Sync every active iCal mapping (used by cron + sync-now button) */
function ical_sync_all(): array {
    $log = [];
    $maps = q_all("SELECT m.*, c.name ch_name, c.id ch_id FROM ota_room_map m JOIN ota_channels c ON c.id=m.channel_id
                   WHERE m.active=1 AND c.active=1 AND c.sync_mode='ical' AND m.ical_import_url <> ''");
    foreach ($maps as $m) {
        $ch = ['id'=>$m['ch_id'], 'name'=>$m['ch_name']];
        [$n, $msg] = ical_sync_mapping($m, $ch);
        $log[] = $msg;
    }
    return $log;
}
