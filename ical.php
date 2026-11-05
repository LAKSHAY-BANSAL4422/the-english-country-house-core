<?php
/** Public iCal availability feed per room: /ical.php?room=slug&token=... — paste into OTA calendar-import */
require_once __DIR__.'/config.php';
require_once __DIR__.'/lib/ical.php';
db();
$room = q_row("SELECT * FROM rooms WHERE slug=?", [get('room')]);
if (!$room || !hash_equals((string)$room['ical_token'], get('token'))) { http_response_code(403); die('Invalid feed'); }
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="'.$room['slug'].'.ics"');
echo ical_export_room($room);
