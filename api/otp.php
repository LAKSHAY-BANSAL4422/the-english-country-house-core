<?php
/** AJAX email-OTP endpoint for booking verification + login (auto-login on verify) */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
sess_start(); db();
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) json_out(['ok'=>false,'message'=>'Session expired — refresh the page.'], 403);

$action = post('action');
if ($action === 'send') {
    [$ok, $msg] = send_otp(post('email'), post('name'));
    json_out(['ok'=>$ok, 'message'=>$msg]);
}
if ($action === 'verify') {
    [$ok, $msg] = verify_otp(post('email'), post('code'));
    json_out(['ok'=>$ok, 'message'=>$msg, 'logged_in'=>$ok]);
}
json_out(['ok'=>false,'message'=>'unknown'], 400);
