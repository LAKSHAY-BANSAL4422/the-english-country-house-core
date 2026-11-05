<?php
/**
 * GET api/availability.php?room_id=3[&months=6]
 *   → {ok, sold:["2026-11-20",…], from, to}   nights that are fully booked / blocked for that room
 * Without room_id: nights on which EVERY active room is sold out (used by the "Any room" calendar).
 * Also returns per-night prices (from the pricing calendar) so the calendar can show rates.
 */
require_once __DIR__.'/../config.php'; db();
header('Cache-Control: no-store');
$roomId = (int)get('room_id', 0);
$months = max(1, min(12, (int)get('months', 6)));
$from = date('Y-m-d'); $to = date('Y-m-d', strtotime("+$months months"));
$rooms = $roomId ? q_all("SELECT id,total_units,base_price FROM rooms WHERE id=? AND active=1", [$roomId]) : q_all("SELECT id,total_units,base_price FROM rooms WHERE active=1");
if (!$rooms) json_out(['ok'=>false,'error'=>'Room not found']);

$soldPerRoom = [];
foreach ($rooms as $r) {
    $units = max(1, (int)$r['total_units']);
    $count = [];   // night → occupied units
    foreach (q_all("SELECT checkin, checkout FROM bookings WHERE room_id=? AND status IN ('pending','confirmed','checked_in') AND checkout > ? AND checkin < ?", [(int)$r['id'], $from, $to]) as $b) {
        for ($d = strtotime($b['checkin']); $d < strtotime($b['checkout']); $d += 86400) { $k = date('Y-m-d', $d); $count[$k] = ($count[$k] ?? 0) + 1; }
    }
    foreach (q_all("SELECT date_from, date_to FROM room_blocks WHERE room_id=? AND date_to > ? AND date_from < ?", [(int)$r['id'], $from, $to]) as $b) {
        for ($d = strtotime($b['date_from']); $d < strtotime($b['date_to']); $d += 86400) { $k = date('Y-m-d', $d); $count[$k] = ($count[$k] ?? 0) + $units; }
    }
    $soldPerRoom[(int)$r['id']] = array_keys(array_filter($count, fn($c) => $c >= $units));
}
// one room → its sold nights; any room → nights where all rooms are sold
if ($roomId) $sold = $soldPerRoom[$roomId] ?? [];
else { $sold = null; foreach ($soldPerRoom as $list) $sold = $sold === null ? $list : array_values(array_intersect($sold, $list)); $sold = $sold ?: []; }
sort($sold);

// nightly rates for the calendar (single room only — cheapest rule-adjusted price per night)
$rates = [];
if ($roomId && function_exists('price_for_night')) {
    $room = q_row("SELECT * FROM rooms WHERE id=?", [$roomId]);
    for ($d = strtotime($from), $n = 0; $n < 93; $d += 86400, $n++) { $k = date('Y-m-d', $d); $p = price_for_night($room, $k); $rates[$k] = (int)round((float)($p['price'] ?? 0)); }
}
json_out(['ok'=>true,'room_id'=>$roomId,'from'=>$from,'to'=>$to,'sold'=>array_values($sold),'rates'=>$rates]);
