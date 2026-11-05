<?php
/** Site Tools: one-click Under Construction mode, cache clear, blocked IPs (24h brute-force bans) */
require_admin('settings');
$flash = ''; $flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (post('do') === 'toggle_maintenance') {
        $on = setting('maintenance_mode') === '1' ? '0' : '1';
        setting_save('maintenance_mode', $on);
        $flash = $on === '1'
            ? '🚧 Under Construction mode is ON — visitors now see the Coming Soon page (you and your team still browse the site normally while logged in).'
            : '✅ Site is LIVE again — Under Construction mode turned off.';
        if ($on === '1') $flashType = 'warn';
    }

    if (post('do') === 'save_maintenance') {
        setting_save('maintenance_mode', post('maintenance_mode') ? '1' : '0');
        setting_save('maintenance_msg', post('maintenance_msg'));
        $flash = 'Maintenance settings saved.';
    }

    if (post('do') === 'clear_cache') {
        $done = [];
        if (function_exists('opcache_reset') && @opcache_reset()) $done[] = 'PHP OPcache';
        $GLOBALS['__settings_cache'] = null; $done[] = 'settings cache';
        setting_save('cache_bust', (string)time()); $done[] = 'CSS/JS versions bumped (browser caches refresh)';
        q("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
        q("DELETE FROM ip_bans WHERE until < NOW()"); $done[] = 'expired login attempts & bans';
        $n = 0;
        foreach (glob(ROOT_PATH.'/uploads/cache/*') ?: [] as $f) if (is_file($f) && @unlink($f)) $n++;
        if ($n) $done[] = "$n temp cache file(s)";
        $flash = '🧹 Cache cleared: '.implode(' · ', $done).'.';
    }

    if (post('do') === 'dismiss_upgrade') { q("DELETE FROM _meta WHERE mkey='upgrade_notice'"); $flash = 'Update note dismissed.'; }

    if (post('do') === 'apply_update') {
        $done = app_apply_update(db());
        log_activity((int)$ADMIN['id'], 'tools', 'apply_update', 'v'.APP_VERSION.' — '.implode(', ', $done));
        $flash = '✅ Updated to v'.APP_VERSION.' — '.implode(' · ', $done).'.';
    }

    if (post('do') === 'unban') {
        db_delete('ip_bans', 'ip=?', [post('ip')]);
        db_delete('login_attempts', 'ip=?', [post('ip')]);
        $flash = 'IP '.post('ip').' unblocked.';
    }

    if (post('do') === 'ban_ip' && post('ip')) {
        q("REPLACE INTO ip_bans (ip, reason, until) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))",
          [trim(post('ip')), 'Manually blocked by '.$ADMIN['name'], max(1,(int)post('hours', 24))]);
        $flash = 'IP '.e(post('ip')).' blocked.';
    }
}

$mOn   = setting('maintenance_mode') === '1';
$bans  = q_all("SELECT * FROM ip_bans ORDER BY banned_at DESC LIMIT 100");
$fails = q_all("SELECT ip, MAX(email) email, COUNT(*) c, MAX(created_at) last FROM login_attempts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY ip ORDER BY last DESC LIMIT 50");
?>
<?php if ($flash): ?><div class="alert <?= $flashType ?>"><?= $flash ?></div><?php endif; ?>

<div class="grid2">
  <div class="card">
    <h3>🚧 Under Construction mode</h3>
    <p class="hint">One click switches the whole public website to a branded "Coming Soon" page (HTTP 503 so Google knows it's temporary). Logged-in admins &amp; team keep seeing the real site. Secret preview link for anyone else: <code><?= e(base_url('?preview=')) ?>YOUR_CRON_KEY</code></p>
    <p style="margin:14px 0">
      Current status:
      <?php if ($mOn): ?><span class="badge red" style="font-size:14px">UNDER CONSTRUCTION — visitors see Coming Soon page</span>
      <?php else: ?><span class="badge green" style="font-size:14px">LIVE — website is public</span><?php endif; ?>
    </p>
    <form method="post" style="margin-bottom:18px"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_maintenance">
      <button class="btn <?= $mOn ? '' : 'danger' ?>" style="<?= $mOn?'background:#27ae60;color:#fff':'' ?>">
        <?= $mOn ? '✅ Turn OFF — make site live' : '🚧 Turn ON — show Under Construction' ?>
      </button>
    </form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_maintenance">
      <input type="hidden" name="maintenance_mode" value="<?= $mOn ? 1 : 0 ?>">
      <label>Message shown on the Coming Soon page</label>
      <textarea class="input" name="maintenance_msg" rows="3"><?= e(setting('maintenance_msg')) ?></textarea>
      <button class="btn sm" style="margin-top:8px">Save message</button>
    </form>
  </div>

  <div class="card">
    <h3>🧹 Cache clear</h3>
    <p class="hint">Clears PHP OPcache, the settings cache, expired security records and temp files — and bumps the CSS/JS version number so every visitor's browser fetches fresh styles &amp; scripts immediately (no Ctrl-F5 needed). Use after editing pages, theme colors or uploading new images if changes don't show.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="clear_cache">
      <button class="btn">🧹 Clear all caches now</button>
    </form>
    <p class="hint" style="margin-top:10px">Current asset version: <code><?= e(setting('cache_bust','1')) ?></code></p>

    <h3 style="margin-top:22px">🔄 Website update</h3>
    <p class="hint">Installed: <b>v<?= e((string)q_val("SELECT mval FROM _meta WHERE mkey='app_version'", [], '') ?: '—') ?></b> · files on server: <b>v<?= e(APP_VERSION) ?></b> · database schema <b>v<?= e((string)q_val("SELECT mval FROM _meta WHERE mkey='schema_version'", [], '')) ?></b>.
      After uploading new files, an <i>Update available</i> bar appears at the top of every admin page — or run it from here any time (safe to repeat: it re-checks the database, re-applies missing defaults and clears caches).</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="apply_update"><button class="btn" style="background:#e67e22">🔄 Run update / repair now</button></form>

    <h3 style="margin-top:22px">🛡 Block an IP manually</h3>
    <form method="post" class="grid2" style="grid-template-columns:2fr 1fr auto;gap:8px;align-items:end"><?= csrf_field() ?><input type="hidden" name="do" value="ban_ip">
      <div><label>IP address</label><input class="input" name="ip" placeholder="e.g. 203.0.113.9" required></div>
      <div><label>Hours</label><input class="input" type="number" name="hours" value="24" min="1"></div>
      <button class="btn danger">Block</button>
    </form>
  </div>
</div>

<?php require_once ROOT_PATH.'/lib/media.php'; $ffm = media_ffmpeg(); $imgCount = count(media_scan_images()); ?>
<div class="card" style="margin-top:18px" id="mediaOpt">
  <h3>⚡ Optimise existing photos (faster loading)</h3>
  <p class="hint">Every <b>new</b> upload is optimised automatically (resized to max <?= (int)setting('upload_max_px','2000') ?> px, recompressed, EXIF-rotated, plus a <b>.webp twin</b> that browsers download instead of the JPG/PNG — typically 30–70 % smaller with no visible quality loss).
    This button does the same for the <b><?= $imgCount ?> photos already in your uploads folder</b> (gallery, rooms, pages, amenities, blog…). Safe to run any time — files that are already optimised are skipped. Videos: <?= $ffm ? '<span style="color:#1d7d4d">ffmpeg found at <code>'.e($ffm).'</code> — uploaded videos are re-encoded to lean 720p MP4 on the server.</span>' : '<span style="color:#b9600d">ffmpeg is not available on this server — videos are compressed in the browser before upload instead (automatic).</span>' ?></p>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <button class="btn" type="button" id="optStart">⚡ Optimise all photos now</button>
    <label class="inline" style="margin:0"><input type="checkbox" id="optForce"> re-do files that were optimised before</label>
    <span id="optStatus" class="hint"></span>
  </div>
  <div id="optBar" style="display:none;margin-top:12px;height:10px;background:#eef1f4;border-radius:6px;overflow:hidden"><div id="optFill" style="height:100%;width:0;background:linear-gradient(90deg,#1d7d4d,#27ae60);transition:.3s"></div></div>
  <div id="optLog" class="hint" style="margin-top:10px;max-height:180px;overflow:auto;font-size:12px"></div>
  <script>
  (function(){
    const b = document.getElementById('optStart'), st = document.getElementById('optStatus'), bar = document.getElementById('optBar'), fill = document.getElementById('optFill'), log = document.getElementById('optLog');
    const hb = n => n >= 1048576 ? (n/1048576).toFixed(1)+' MB' : Math.round(n/1024)+' KB';
    let saved = 0;
    async function step(next, force){
      const fd = new FormData(); fd.append('action','media_optimize'); fd.append('csrf', ADMIN.csrf); fd.append('offset', next); fd.append('force', force ? 1 : 0);
      const r = await fetch(ADMIN.base + '/admin/ajax.php', {method:'POST', body: fd}); const d = await r.json();
      if (!d.ok) { st.innerHTML = '<span style="color:#c0392b">' + (d.error || 'Failed') + '</span>'; b.disabled = false; return; }
      saved += d.saved || 0;
      (d.items || []).forEach(it => { const l = document.createElement('div'); l.textContent = it.file + ' — ' + hb(it.before) + ' → ' + hb(it.after) + (it.webp ? ' · webp ' + hb(it.webp) : '') + (it.note ? ' (' + it.note + ')' : ''); log.prepend(l); });
      fill.style.width = (d.total ? Math.round(d.next / d.total * 100) : 100) + '%';
      st.textContent = d.next + ' / ' + d.total + ' photos · saved ' + hb(saved) + ' so far';
      if (!d.finished) return step(d.next, force);
      st.innerHTML = '<b style="color:#1d7d4d">✓ Done — ' + d.total + ' photos checked, ' + hb(saved) + ' saved. Browsers now get the WebP versions automatically.</b>'; b.disabled = false;
    }
    b.addEventListener('click', () => { b.disabled = true; saved = 0; log.innerHTML = ''; bar.style.display = 'block'; fill.style.width = '0'; st.textContent = 'Starting…'; step(0, document.getElementById('optForce').checked); });
  })();
  </script>
</div>

<div class="card" style="margin-top:18px">
  <h3>⛔ Blocked IPs (auto 24-hour ban after 3 wrong passwords)</h3>
  <p class="hint">Any visitor who enters a wrong password or wrong OTP code 3 times within 24 hours is blocked automatically for 24 hours — on the admin login, team login and guest login alike. Bans expire on their own; unblock earlier here.</p>
  <?php if (!$bans): ?><p class="hint">No IPs are currently blocked. 🎉</p>
  <?php else: ?>
  <table class="tbl"><tr><th>IP</th><th>Reason</th><th>Blocked at</th><th>Until</th><th>Status</th><th></th></tr>
    <?php foreach ($bans as $b): $live = strtotime($b['until']) > time(); ?>
    <tr><td><b><?= e($b['ip']) ?></b></td><td><?= e($b['reason']) ?></td>
      <td><?= date('d M Y h:i A', strtotime($b['banned_at'])) ?></td>
      <td><?= date('d M Y h:i A', strtotime($b['until'])) ?></td>
      <td><span class="badge <?= $live?'red':'green' ?>"><?= $live?'blocked':'expired' ?></span></td>
      <td><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="unban"><input type="hidden" name="ip" value="<?= e($b['ip']) ?>"><button class="btn sm ghost">Unblock</button></form></td></tr>
    <?php endforeach; ?></table>
  <?php endif; ?>

  <?php if ($fails): ?>
  <h3 style="margin-top:20px">Recent failed login attempts (last 24h)</h3>
  <table class="tbl"><tr><th>IP</th><th>Last email tried</th><th>Attempts</th><th>Last attempt</th></tr>
    <?php foreach ($fails as $f): ?>
    <tr><td><?= e($f['ip']) ?></td><td><?= e($f['email']) ?></td><td><?= (int)$f['c'] ?> / 3</td><td><?= date('d M h:i A', strtotime($f['last'])) ?></td></tr>
    <?php endforeach; ?></table>
  <?php endif; ?>
</div>
