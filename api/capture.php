<?php
/**
 * Abandoned-checkout / abandoned-form lead capture.
 * The booking page (and any form with data-capture) quietly posts name/phone/email
 * as the visitor types. If they never submit, their details are already saved as a
 * lead ("abandoned-checkout") so the team can follow up. If they do submit, the
 * normal booking/enquiry lead merges into the same record (same phone/email).
 */
require_once __DIR__.'/../config.php';
db();
sess_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok'=>false]);
// sendBeacon cannot set headers, so accept the CSRF token as a POST field
if (!hash_equals($_SESSION['csrf'] ?? '', post('csrf'))) json_out(['ok'=>false,'error'=>'csrf']);

$name  = mb_substr(trim(post('name')), 0, 120);
$phone = phone_check(post('phone'), false)['e164'];   // round 5: only a valid number (site-wide rule) is kept, normalised
$email = strtolower(trim(post('email')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

// need at least a usable phone or email — never store junk
if (strlen($phone) < 7 && $email === '') json_out(['ok'=>false]);

$ctx = [];
if (post('room'))     $ctx[] = 'Room: '.mb_substr(post('room'), 0, 80);
if (post('checkin'))  $ctx[] = 'Check-in: '.mb_substr(post('checkin'), 0, 20);
if (post('checkout')) $ctx[] = 'Check-out: '.mb_substr(post('checkout'), 0, 20);
if (post('guests'))   $ctx[] = 'Guests: '.mb_substr(post('guests'), 0, 20);
$pageUrl = mb_substr(post('page'), 0, 190);
$msg = '⏳ Auto-captured — visitor started '.(post('kind') === 'form' ? 'a form' : 'a booking').' but has not submitted yet.'
     .($ctx ? ' '.implode(' · ', $ctx) : '')
     .($pageUrl ? ' (page: '.$pageUrl.')' : '');

$roomId = (int)post('room_id') ?: null;

// same visitor keeps updating one lead per session
$sid = $_SESSION['capture_lead_id'] ?? 0;
if ($sid && ($l = q_row("SELECT * FROM leads WHERE id=?", [(int)$sid]))) {
    $upd = [];
    if ($name  && $name  !== $l['name'])  $upd['name']  = $name;
    if ($phone && $phone !== $l['phone']) $upd['phone'] = $phone;
    if ($email && $email !== $l['email']) $upd['email'] = $email;
    if ($roomId && $roomId !== (int)$l['room_id']) $upd['room_id'] = $roomId;
    if ($upd) {
        db_update('leads', $upd, 'id=?', [(int)$l['id']]);
        db_insert('lead_activities', ['lead_id'=>(int)$l['id'],'type'=>'system','content'=>'Visitor updated details before submitting: '.implode(', ', array_keys($upd)).($ctx ? ' · '.implode(' · ', $ctx) : '')]);
    }
    json_out(['ok'=>true,'lead_id'=>(int)$l['id']]);
}

$leadId = create_lead([
    'name'=>$name ?: 'Website Visitor', 'email'=>$email, 'phone'=>$phone,
    'source'=>'abandoned-checkout', 'room_id'=>$roomId, 'message'=>$msg,
    'meta'=>['page'=>$pageUrl, 'auto_captured'=>1],
]);
$_SESSION['capture_lead_id'] = $leadId;
json_out(['ok'=>true,'lead_id'=>$leadId]);
