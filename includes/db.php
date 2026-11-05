<?php
require_once __DIR__.'/version.php';   // APP_VERSION — ships with the code, so it is always current even when config.php is kept
/** Database layer: mysqli wrapper + automatic schema migration */

function db(): mysqli {
    static $db = null;
    if ($db === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die(APP_DEBUG ? 'DB connection failed: '.htmlspecialchars($e->getMessage())
                          : 'Database connection failed. Please check config.php.');
        }
        $db->set_charset('utf8mb4');
        // keep MySQL NOW()/CURRENT_TIMESTAMP in the same timezone as PHP (APP_TIMEZONE) — shared hosts often run MySQL in UTC/US time
        try { $db->query("SET time_zone = '".date('P')."'"); } catch (mysqli_sql_exception $e) {}
        db_migrate($db);
        app_version_check($db);
    }
    return $db;
}

/** New code uploaded? → clear every cache once, bump asset versions and leave a note for the admin dashboard. */
function app_version_check(mysqli $db): void {
    if (!defined('APP_VERSION')) return;
    try {
        $r = $db->query("SELECT mval FROM _meta WHERE mkey='app_version'")->fetch_row();
        $installed = $r ? (string)$r[0] : '';
        if ($installed === APP_VERSION) return;
        if (!empty($GLOBALS['__fresh_install'])) { app_apply_update($db, true); return; }   // first install: nothing to confirm
        // new files detected: caches are refreshed right away so visitors get the new CSS/JS, and the admin
        // gets an "Update available — click Update" bar to finish the upgrade (defaults, content patches, version note)
        $p = $db->query("SELECT mval FROM _meta WHERE mkey='update_pending'")->fetch_row();
        if (!$p || (string)$p[0] !== APP_VERSION) {
            app_clear_caches($db);
            $st = $db->prepare("REPLACE INTO _meta (mkey,mval) VALUES ('update_pending',?)"); $v = APP_VERSION; $st->bind_param('s', $v); $st->execute();
        }
    } catch (Throwable $e) { /* never block a page because of the version note */ }
}

/** The admin's "Update now" button (and first install): migrations are re-checked, defaults re-applied, caches cleared, version recorded. */
function app_apply_update(mysqli $db, bool $fresh = false): array {
    $r = $db->query("SELECT mval FROM _meta WHERE mkey='app_version'")->fetch_row();
    $installed = $r ? (string)$r[0] : '';
    $done = [];
    db_migrate($db); $done[] = 'database schema checked';
    if (!$fresh) {
        try { require_once ROOT_PATH.'/install/page_defaults.php'; ensure_default_pages($db); $done[] = 'page defaults verified'; } catch (Throwable $e) {}
        try { require_once ROOT_PATH.'/install/explore_data.php'; if (function_exists('explore_seed_attractions')) { explore_seed_attractions($db); $done[] = 'Explore Mussoorie data verified'; } } catch (Throwable $e) {}
    }
    app_clear_caches($db); $done[] = 'all caches cleared';
    $schema = (string)($db->query("SELECT mval FROM _meta WHERE mkey='schema_version'")->fetch_row()[0] ?? '');
    $note = json_encode(['from'=>$installed ?: 'an earlier version', 'to'=>APP_VERSION, 'at'=>date('Y-m-d H:i:s'), 'schema'=>$schema]);
    $meta = ['app_version'=>APP_VERSION] + ($fresh ? [] : ['upgrade_notice'=>$note]);
    foreach ($meta as $k => $v) { $st = $db->prepare("REPLACE INTO _meta (mkey,mval) VALUES (?,?)"); $st->bind_param('ss', $k, $v); $st->execute(); }
    $db->query("DELETE FROM _meta WHERE mkey='update_pending'");
    if (!$fresh) { $st = $db->prepare("INSERT INTO admin_activity (admin_id, module, action, detail, ip) VALUES (0,'system','upgrade',?,'')"); $d = 'Website updated to v'.APP_VERSION.($installed !== '' ? ' (from v'.$installed.')' : '').' — '.implode(', ', $done); $st->bind_param('s', $d); try { $st->execute(); } catch (Throwable $e) {} }
    return $done;
}

/** Same as the admin "Clear Cache" button: OPcache + settings cache + CSS/JS version bump + temp files. */
function app_clear_caches(mysqli $db): void {
    if (function_exists('opcache_reset')) @opcache_reset();
    $GLOBALS['__settings_cache'] = null;
    try { $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES ('cache_bust',?)"); $v = (string)time(); $st->bind_param('s', $v); $st->execute(); } catch (Throwable $e) {}
    foreach (glob(ROOT_PATH.'/uploads/cache/*') ?: [] as $f) if (is_file($f)) @unlink($f);
}

/** Run pending migrations (schema version stored in _meta table). */
function db_migrate(mysqli $db): void {
    $db->query("CREATE TABLE IF NOT EXISTS _meta (mkey VARCHAR(64) PRIMARY KEY, mval TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cur = 0;
    $res = $db->query("SELECT mval FROM _meta WHERE mkey='schema_version'");
    if ($row = $res->fetch_row()) $cur = (int)$row[0];
    $file = ROOT_PATH.'/install/schema.php';
    if (!is_file($file)) return;
    $migrations = require $file;           // [version => [sql, sql, ...]]
    ksort($migrations);
    $latest = $cur;
    foreach ($migrations as $ver => $stmts) {
        if ($ver <= $cur) continue;
        foreach ($stmts as $sql) {
            if ($sql instanceof Closure) {             // PHP-level data migration step
                try { $sql($db); }
                catch (Throwable $e) { if (APP_DEBUG) die("Migration v$ver step failed: ".htmlspecialchars($e->getMessage())); }
                continue;
            }
            try { $db->query($sql); }
            catch (mysqli_sql_exception $e) {
                if (!in_array($db->errno, [1050,1060,1061,1062,1091])) {
                    if (APP_DEBUG) die("Migration v$ver failed: ".htmlspecialchars($e->getMessage())."<br><pre>".htmlspecialchars($sql)."</pre>");
                }
            }
        }
        $latest = $ver;
    }
    if ($latest > $cur) {
        $stmt = $db->prepare("REPLACE INTO _meta (mkey,mval) VALUES ('schema_version',?)");
        $v = (string)$latest; $stmt->bind_param('s',$v); $stmt->execute();
        if ($cur === 0) { $GLOBALS['__fresh_install'] = true; $seed = ROOT_PATH.'/install/seed.php'; if (is_file($seed)) require $seed; }
        else { $stmt = $db->prepare("REPLACE INTO _meta (mkey,mval) VALUES ('schema_upgrade',?)"); $v = json_encode(['from'=>$cur,'to'=>$latest,'at'=>date('Y-m-d H:i:s')]); $stmt->bind_param('s',$v); $stmt->execute(); }
    }
}

/* ---------- query helpers ---------- */

function q(string $sql, array $params = []): mysqli_stmt {
    $stmt = db()->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}
function q_all(string $sql, array $params = []): array {
    $r = q($sql, $params)->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function q_row(string $sql, array $params = []): ?array {
    $r = q($sql, $params)->get_result();
    $row = $r ? $r->fetch_assoc() : null;
    return $row ?: null;
}
function q_val(string $sql, array $params = [], $default = null) {
    $row = q_row($sql, $params);
    return $row ? array_values($row)[0] : $default;
}
function db_insert(string $table, array $data): int {
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".rtrim(str_repeat('?,',count($cols)),',').")";
    q($sql, array_values($data));
    return db()->insert_id;
}
function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $set = implode(',', array_map(fn($c)=>"`$c`=?", array_keys($data)));
    q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
    return db()->affected_rows;
}
function db_delete(string $table, string $where, array $params = []): int {
    q("DELETE FROM `$table` WHERE $where", $params);
    return db()->affected_rows;
}
