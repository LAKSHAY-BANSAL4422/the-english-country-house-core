<?php
/** Google OAuth callback — social login/registration */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();

$fail = fn($m) => redirect(base_url('account/login.php?gerr='.urlencode($m)));

if (!google_login_enabled()) $fail('Google login is not configured.');
if (get('error')) $fail('Google login cancelled.');
$code = get('code');
if (!$code || !hash_equals($_SESSION['oauth_state'] ?? '-', get('state'))) $fail('Invalid login state — please retry.');

$tok = http_request('https://oauth2.googleapis.com/token', 'POST', [
    'code'=>$code, 'client_id'=>setting('google_client_id'), 'client_secret'=>setting('google_client_secret'),
    'redirect_uri'=>base_url('account/google.php'), 'grant_type'=>'authorization_code',
]);
$access = $tok['json']['access_token'] ?? '';
if (!$access) $fail('Google token exchange failed: '.($tok['json']['error_description'] ?? 'unknown'));

$info = http_request('https://www.googleapis.com/oauth2/v2/userinfo', 'GET', null, ['Authorization: Bearer '.$access]);
$g = $info['json'] ?? [];
$email = strtolower(trim($g['email'] ?? ''));
if (!$email) $fail('Could not read your Google email.');

$u = q_row("SELECT * FROM users WHERE email=?", [$email]);
if (!$u) {
    $uid = db_insert('users', ['name'=>$g['name'] ?? ucfirst(explode('@',$email)[0]), 'email'=>$email,
        'email_verified'=>1, 'google_id'=>(string)($g['id'] ?? '')]);
} else {
    $uid = (int)$u['id'];
    db_update('users', ['email_verified'=>1, 'google_id'=>(string)($g['id'] ?? $u['google_id'])], 'id=?', [$uid]);
}
user_login($uid);
$_SESSION['verified_email'] = $email;
$next = $_SESSION['oauth_next'] ?? '';
unset($_SESSION['oauth_state'], $_SESSION['oauth_next']);
redirect(base_url($next ? ltrim($next,'/') : 'account/index.php'));
