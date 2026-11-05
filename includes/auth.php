<?php
/** Authentication: customers (email OTP + Google) + admins/team (password or email OTP) */

/* ---------- customer ---------- */
function current_user(): ?array {
    sess_start();
    if (empty($_SESSION['user_id'])) return null;
    static $u = false;
    if ($u === false) $u = q_row("SELECT * FROM users WHERE id=? AND active=1", [(int)$_SESSION['user_id']]);
    return $u ?: null;
}
function require_user(): array {
    $u = current_user();
    if (!$u) redirect(base_url('account/login.php?next='.urlencode($_SERVER['REQUEST_URI'] ?? '')));
    return $u;
}
function user_login(int $id): void {
    sess_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    q("UPDATE users SET last_login=NOW() WHERE id=?", [$id]);
}
function user_logout(): void { sess_start(); unset($_SESSION['user_id']); }

/** Send a login/registration OTP to email. Returns [ok, message]. */
function send_otp(string $email, string $name = ''): array {
    if ($ban = ip_banned()) return [false, 'Your IP is temporarily blocked until '.date('d M Y h:i A', strtotime($ban['until'])).' after repeated wrong attempts.'];
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Please enter a valid email address'];
    $u = q_row("SELECT * FROM users WHERE email=?", [$email]);
    if ($u && $u['otp_expires'] && strtotime($u['otp_expires']) > time() + 540) return [false, 'OTP already sent. Please wait a minute before requesting again.'];
    if (!$u) {
        $uid = db_insert('users', ['name'=>$name ?: ucfirst(explode('@',$email)[0]), 'email'=>$email]);
        $u = q_row("SELECT * FROM users WHERE id=?", [$uid]);
    } elseif ($name) {
        db_update('users', ['name'=>$name], 'id=?', [(int)$u['id']]);
    }
    $otp = rand_code(6);
    db_update('users', ['otp_code'=>password_hash($otp, PASSWORD_DEFAULT), 'otp_expires'=>date('Y-m-d H:i:s', time()+600), 'otp_attempts'=>0], 'id=?', [(int)$u['id']]);
    $ok = send_email($email, 'Your verification code — '.setting('site_name'),
        '<p>Hello '.e($u['name']).',</p><p>Your one-time verification code is:</p>
         <p style="font-size:32px;letter-spacing:8px;font-weight:bold;color:'.setting('color_primary').';text-align:center;background:#faf6ee;padding:14px;border-radius:8px">'.$otp.'</p>
         <p>This code is valid for 10 minutes. If you did not request it, you can safely ignore this email.</p>', 'otp');
    if (!$ok && APP_DEBUG) return [true, 'DEBUG OTP: '.$otp];
    if (!$ok) {
        sess_start();
        $_SESSION['otp_mail_failed'] = $email;   // booking.php lets the guest continue; the booking is flagged for a phone confirmation
        return [false, 'We could not send the email right now — no problem, you can still confirm your booking and we will verify it by phone.'];
    }
    return [true, 'We have emailed you a 6-digit code.'];
}
function verify_otp(string $email, string $code): array {
    $u = q_row("SELECT * FROM users WHERE email=?", [strtolower(trim($email))]);
    if (!$u || !$u['otp_code']) return [false, 'Please request a code first'];
    if ((int)$u['otp_attempts'] >= 5) return [false, 'Too many attempts. Request a new code.'];
    if (strtotime($u['otp_expires']) < time()) return [false, 'Code expired. Please request a new one.'];
    q("UPDATE users SET otp_attempts=otp_attempts+1 WHERE id=?", [(int)$u['id']]);
    if (!password_verify($code, $u['otp_code'])) {
        $banned = record_failed_login($email);
        return [false, $banned ? '⛔ 3 wrong attempts — your IP has been blocked for 24 hours.' : 'Incorrect code. Please check and retry.'];
    }
    clear_failed_logins();
    db_update('users', ['otp_code'=>null,'otp_expires'=>null,'otp_attempts'=>0,'email_verified'=>1], 'id=?', [(int)$u['id']]);
    user_login((int)$u['id']);
    $_SESSION['verified_email'] = strtolower(trim($email));
    return [true, 'Verified'];
}

/* ---------- Google social login ---------- */
function google_login_enabled(): bool { return setting('google_client_id') !== '' && setting('google_client_secret') !== ''; }
function google_auth_url(string $state): string {
    return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
        'client_id'=>setting('google_client_id'), 'redirect_uri'=>base_url('account/google.php'),
        'response_type'=>'code', 'scope'=>'openid email profile', 'state'=>$state, 'prompt'=>'select_account',
    ]);
}

/* ---------- admin / team ---------- */
function current_admin(): ?array {
    sess_start();
    if (empty($_SESSION['admin_id'])) return null;
    static $a = false;
    if ($a === false) {
        $a = q_row("SELECT * FROM admins WHERE id=? AND active=1", [(int)$_SESSION['admin_id']]);
        if ($a) q("UPDATE admins SET last_seen=NOW() WHERE id=?", [(int)$a['id']]);
    }
    return $a ?: null;
}
function require_admin(?string $perm = null): array {
    $a = current_admin();
    if (!$a) redirect(base_url('admin/login.php'));
    if ($perm && !admin_can($a, $perm)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>403 — No access</h2><p>You do not have permission for this module. Ask your administrator.</p><a href="'.base_url('admin/index.php').'">← Back</a></div>');
    }
    return $a;
}
function admin_can(array $a, string $perm): bool {
    if (in_array($a['role'], ['superadmin','admin'])) return true;
    $perms = json_decode($a['permissions'] ?? '[]', true) ?: [];
    return in_array('*', $perms) || in_array($perm, $perms);
}
function admin_login_attempt(string $email, string $pass): array {
    sess_start();
    if ($ban = ip_banned()) return [false, '⛔ Your IP is blocked until '.date('d M Y h:i A', strtotime($ban['until'])).' after 3 wrong password attempts.'];
    $a = q_row("SELECT * FROM admins WHERE email=? AND active=1", [strtolower(trim($email))]);
    if (!$a || !password_verify($pass, $a['password'])) {
        $banned = record_failed_login($email);
        return [false, $banned ? '⛔ 3 wrong attempts — your IP has been blocked for 24 hours.' : 'Invalid email or password. Warning: 3 wrong attempts will block your IP for 24 hours.'];
    }
    clear_failed_logins();
    admin_do_login((int)$a['id']);
    return [true, 'ok'];
}
function admin_do_login(int $id): void {
    sess_start();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $id;
    log_activity($id, 'auth', 'login', 'Panel login');
}
/** Email-OTP login for admin & team members */
function admin_send_otp(string $email): array {
    if ($ban = ip_banned()) return [false, 'Your IP is temporarily blocked until '.date('d M Y h:i A', strtotime($ban['until'])).'.'];
    $a = q_row("SELECT * FROM admins WHERE email=? AND active=1", [strtolower(trim($email))]);
    if (!$a) return [false, 'No team account found for this email'];
    if ($a['otp_expires'] && strtotime($a['otp_expires']) > time() + 540) return [false, 'OTP already sent — wait a minute before retrying.'];
    $otp = rand_code(6);
    db_update('admins', ['otp_code'=>password_hash($otp, PASSWORD_DEFAULT),'otp_expires'=>date('Y-m-d H:i:s', time()+600),'otp_attempts'=>0], 'id=?', [(int)$a['id']]);
    $ok = send_email($a['email'], 'Panel login code — '.setting('site_name'),
        '<p>Hello '.e($a['name']).',</p><p>Your one-time login code for the admin/team panel:</p>
         <p style="font-size:32px;letter-spacing:8px;font-weight:bold;color:'.setting('color_primary').';text-align:center;background:#faf6ee;padding:14px;border-radius:8px">'.$otp.'</p>
         <p>Valid for 10 minutes. If this wasn\'t you, please alert your administrator.</p>', 'admin-otp');
    if (!$ok && APP_DEBUG) return [true, 'DEBUG OTP: '.$otp];
    return [$ok, $ok ? 'Login code emailed — check your inbox.' : 'Could not send email. Use password login or check SMTP settings.'];
}
function admin_verify_otp(string $email, string $code): array {
    if ($ban = ip_banned()) return [false, 'Your IP is temporarily blocked until '.date('d M Y h:i A', strtotime($ban['until'])).'.'];
    $a = q_row("SELECT * FROM admins WHERE email=? AND active=1", [strtolower(trim($email))]);
    if (!$a || !$a['otp_code']) return [false, 'Request a code first'];
    if ((int)$a['otp_attempts'] >= 5) return [false, 'Too many attempts — request a new code.'];
    if (strtotime($a['otp_expires']) < time()) return [false, 'Code expired — request a new one.'];
    q("UPDATE admins SET otp_attempts=otp_attempts+1 WHERE id=?", [(int)$a['id']]);
    if (!password_verify($code, $a['otp_code'])) {
        $banned = record_failed_login($email);
        return [false, $banned ? '⛔ 3 wrong attempts — your IP has been blocked for 24 hours.' : 'Incorrect code.'];
    }
    clear_failed_logins();
    db_update('admins', ['otp_code'=>null,'otp_expires'=>null,'otp_attempts'=>0], 'id=?', [(int)$a['id']]);
    admin_do_login((int)$a['id']);
    return [true, 'ok'];
}
function admin_logout(): void { sess_start(); unset($_SESSION['admin_id']); }
