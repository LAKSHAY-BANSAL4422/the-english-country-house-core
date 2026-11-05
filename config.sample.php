<?php
/* v20 SAMPLE ONLY. On the live site KEEP the existing config.php (it has the real database password).
   Use this file only for a brand-new install: copy it to config.php and fill in the four DB values,
   APP_SECRET and CRON_KEY. APP_DEBUG stays off in production. */
/**
 * ============================================================
 *  The English Country House — Hotel Booking & Management Suite
 *  Core configuration file
 *  ------------------------------------------------------------
 *  Edit the values below to match your hosting account.
 *  On shared hosting (cPanel) create a MySQL database + user,
 *  grant ALL privileges, then fill the 4 DB_* constants.
 * ============================================================
 */

/* ---------- DATABASE ---------- */
define('DB_HOST', getenv('ECH_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('ECH_DB_NAME') ?: 'YOUR-DATABASE-NAME');
define('DB_USER', getenv('ECH_DB_USER') ?: 'YOUR-DATABASE-USER');
define('DB_PASS', getenv('ECH_DB_PASS') ?: 'YOUR-DATABASE-PASSWORD');

/* ---------- SITE URL (no trailing slash) ---------- */
// Leave AUTO to detect automatically, or hard-code e.g. https://theenglishcountryhouse.com
define('BASE_URL_MODE', 'AUTO');
define('BASE_URL_FIXED', 'https://example.com');

/* ---------- SECURITY ---------- */
define('APP_SECRET', getenv('ECH_SECRET') ?: 'REPLACE-WITH-A-LONG-RANDOM-STRING');
define('SESSION_NAME', 'ECHSESS');

/* ---------- ENVIRONMENT ---------- */
define('APP_DEBUG', getenv('ECH_DEBUG') === '1');   // set to false in production
define('APP_TIMEZONE', 'Asia/Kolkata');

/* ---------- CRON ---------- */
// Append ?key=CRON_KEY when calling cron scripts over HTTP
define('CRON_KEY', getenv('ECH_CRON_KEY') ?: 'REPLACE-WITH-A-RANDOM-KEY');

/* ============================================================
 *  Nothing below normally needs editing
 * ============================================================ */
date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else { error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('display_errors', '0'); ini_set('log_errors','1'); }

define('ROOT_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');

function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        if (BASE_URL_MODE === 'AUTO') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir   = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            foreach (['/admin/modules','/admin','/team','/account','/api','/cron','/install'] as $sub) {
                if ($sub !== '' && str_ends_with($dir, $sub)) { $dir = substr($dir, 0, -strlen($sub)); break; }
            }
            $base = ($https ? 'https://' : 'http://') . $host . $dir;
        } else {
            $base = rtrim(BASE_URL_FIXED, '/');
        }
    }
    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';
