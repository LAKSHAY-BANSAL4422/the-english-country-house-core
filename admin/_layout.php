<?php
/** Admin layout: header/sidebar/footer + global alerts (waiting chats, follow-ups due) */

/** Sub-pages shown under a sidebar item (tabs of that module, or the real pages / rooms) — the active one is highlighted */
function admin_submenu(string $key): array {
    global $m;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $tab = get('tab', '');
    $tabs = function (array $list, string $default) use ($key, $m, $tab): array {
        $out = []; $cur = $m === $key ? ($tab ?: $default) : '';
        foreach ($list as $k => $label) $out[] = ['?m='.$key.'&tab='.$k, $label, $cur === $k];
        return $out;
    };
    $out = [];
    switch ($key) {
        case 'cms':
            $cur = $m === 'cms' ? (int)(get('builder') ?: get('edit') ?: 0) : 0;
            foreach (q_all("SELECT id,title,page_type FROM pages WHERE active=1 AND page_type<>'location' ORDER BY FIELD(page_type,'system','custom','policy'), menu_order, id LIMIT 14") as $pg)
                $out[] = ['?m=cms&builder='.$pg['id'], $pg['title'], $cur === (int)$pg['id']];
            $out[] = ['?m=cms&new=1', '+ New page', false];
            break;
        case 'rooms':
            $cur = $m === 'rooms' ? (int)get('edit', 0) : 0;
            foreach (q_all("SELECT id,name FROM rooms ORDER BY sort,id") as $r) $out[] = ['?m=rooms&edit='.$r['id'], $r['name'], $cur === (int)$r['id']];
            $out[] = ['?m=rooms&new=1', '+ Add room', false];
            break;
        case 'menu':      $out = $tabs(['orders'=>'Live Orders','items'=>'Menu Items','sections'=>'Sections','qr'=>'QR Codes','settings'=>'Page & Settings'], 'orders'); break;
        case 'settings':  $out = $tabs(['general'=>'General & Contact','booking'=>'Booking · Refunds · GST','payments'=>'Payment Gateways','login'=>'Login & Social','email'=>'Email / SMTP','whatsapp'=>'WhatsApp Button','chat'=>'Live Chat','media'=>'Photos & Video','google'=>'Google APIs','scripts'=>'Custom Scripts'], 'general'); break;
        case 'seo':       $out = $tabs(['seo'=>'SEO','aeo'=>'AEO & GEO','kw'=>'Keyword Research'], 'seo'); break;
        case 'documents': $out = $tabs(['letterhead'=>'Letterheads','invoice'=>'Invoices','mou'=>'MOUs'], 'letterhead'); break;
        case 'ota':       $out = $tabs(['credentials'=>'Credentials','rooms'=>'Room Mapping','rates'=>'Rate Plan Mapping','sync'=>'Calendar Sync'], 'credentials'); break;
        case 'social':    $out = $tabs(['posts'=>'Scheduled Posts','accounts'=>'Connected Accounts'], 'posts'); break;
        case 'blog':      $out = $tabs(['posts'=>'Posts','cats'=>'Categories'], 'posts'); break;
        case 'chatbot':   $out = $tabs(['qa'=>'Bot Auto-Responses','cr'=>'Agent Canned Replies'], 'qa'); break;
    }
    return $cache[$key] = $out;
}

function admin_header(string $title): void {
    global $ADMIN, $MODULES, $m;
    $waiting = (int)q_val("SELECT COUNT(*) FROM visitor_chats WHERE status='active' AND agent_id=? AND id IN (SELECT chat_id FROM visitor_chat_messages WHERE sender='visitor' AND seen_by_agent=0)", [(int)$ADMIN['id']], 0)
             + (int)q_val("SELECT COUNT(*) FROM visitor_chats WHERE status='open' AND mode IN ('waiting')", [], 0);
    $newLeads = (int)q_val("SELECT COUNT(*) FROM leads WHERE stage='new'", [], 0);
    $newFood = admin_can($ADMIN, 'menu') ? (int)q_val("SELECT COUNT(*) FROM food_orders WHERE status='new'", [], 0) : 0;
    ?><!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?> — Admin</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>?v=6.5.2">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/editor.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/editor.css') ?>">
    <script src="<?= base_url('assets/vendor/chart.umd.min.js') ?>?v=4.4.3"></script>
    </head><body>
    <div class="layout">
    <aside class="sidebar">
      <div class="sb-brand"><i class="fa-solid fa-fan"></i> <?= e(setting('site_name')) ?><small><?= e(ucfirst($ADMIN['role'])) ?> Panel — <?= e($ADMIN['name']) ?></small></div>
      <div class="sb-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="sbSearch" placeholder="Find a menu…" autocomplete="off"></div>
      <nav class="sb-nav" id="sbNav">
        <?php $sec = '';
        foreach ($MODULES as $key=>$def):
            if (!admin_can($ADMIN, $def[2])) continue;
            if ($def[3] !== $sec) { $sec = $def[3]; echo '<div class="sb-sec">'.$sec.'</div>'; } ?>
          <?php $sub = admin_submenu($key); ?>
        <a href="?m=<?= $key ?>" class="<?= $m===$key?'active':'' ?><?= $sub ? ' has-sub' : '' ?>"><i class="fa-solid <?= $def[1] ?>"></i> <?= $def[0] ?>
            <?php if ($key==='chat' && $waiting): ?><span class="cnt"><?= $waiting ?></span><?php endif; ?>
            <?php if ($key==='leads' && $newLeads): ?><span class="cnt" style="background:#3498db"><?= $newLeads ?></span><?php endif; ?>
            <?php if ($key==='menu'): ?><span class="cnt" id="sbFood" style="background:#e67e22;<?= $newFood ? '' : 'display:none' ?>"><?= $newFood ?></span><?php endif; ?>
            <?php if ($sub): ?><span class="sb-tgl" title="Show / hide sub-pages"><i class="fa-solid fa-chevron-down"></i></span><?php endif; ?>
          </a>
          <?php if ($sub): ?>
          <div class="sb-sub<?= $m===$key ? ' open' : '' ?>">
            <?php foreach ($sub as $link): ?>
              <a href="<?= e($link[0]) ?>" class="<?= $link[2] ? 'active' : '' ?>"><?= e($link[1]) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
      <div style="padding:14px 20px;border-top:1px solid rgba(255,255,255,.08)">
        <a href="<?= base_url() ?>" target="_blank" style="color:#8a99ab;font-size:12.5px"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Website</a><br>
        <a href="<?= base_url('admin/logout.php') ?>" style="color:#8a99ab;font-size:12.5px"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <div style="color:#5c6b7c;font-size:11px;margin-top:8px">v<?= defined('APP_VERSION') ? APP_VERSION : '' ?> · DB v<?= e((string)q_val("SELECT mval FROM _meta WHERE mkey='schema_version'", [], '')) ?></div>
      </div>
    </aside>
    <div class="main">
      <div class="topbar">
        <h1><?= e($title) ?></h1>
        <div class="tb-right">
          <?php if (admin_can($ADMIN,'settings')): $mOn = setting('maintenance_mode')==='1'; ?>
          <form method="post" action="?m=tools" style="display:inline;margin:0">
            <?= csrf_field() ?><input type="hidden" name="do" value="toggle_maintenance">
            <button type="submit" title="<?= $mOn?'Site is UNDER CONSTRUCTION — click to go live':'One-click Under Construction mode' ?>"
              style="border:0;cursor:pointer;border-radius:20px;padding:7px 13px;font-size:12.5px;font-weight:600;color:#fff;background:<?= $mOn?'#c0392b':'#2c3e50' ?>">
              🚧 <?= $mOn?'Under Construction — ON':'Site Live' ?>
            </button>
          </form>
          <form method="post" action="?m=tools" style="display:inline;margin:0">
            <?= csrf_field() ?><input type="hidden" name="do" value="clear_cache">
            <button type="submit" title="Clear cache (opcache + settings + asset versions)"
              style="border:0;cursor:pointer;border-radius:20px;padding:7px 13px;font-size:12.5px;font-weight:600;color:#fff;background:#16a085">🧹 Clear Cache</button>
          </form>
          <?php endif; ?>
          <div class="avail-toggle <?= $ADMIN['chat_available'] ? 'on':'' ?>" id="availToggle" title="Toggle chat availability">
            <span class="avail-dot"></span><span id="availLbl"><?= $ADMIN['chat_available'] ? 'Available for chat' : 'Away' ?></span>
          </div>
          <span style="font-size:13px;color:var(--mut)"><i class="fa-regular fa-clock"></i> <?= date('d M Y') ?></span>
        </div>
      </div>
      <div class="content">
    <?php
    // "update available" bar — new files were uploaded; one click finishes the upgrade (schema check, defaults, caches, version)
    $upPending = (string)q_val("SELECT mval FROM _meta WHERE mkey='update_pending'", [], '');
    if ($upPending !== '' && admin_can($ADMIN, 'settings')): ?>
      <div class="alert warn" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;border-left:4px solid #e67e22">
        <div style="flex:1">🔄 <b>Update available — website files v<?= e($upPending) ?> have been uploaded</b> (installed: <?= ($iv = (string)q_val("SELECT mval FROM _meta WHERE mkey='app_version'", [], '')) !== '' ? 'v'.e($iv) : 'an earlier version' ?>).
          Click <b>Update now</b> to finish: the database is checked &amp; upgraded, page defaults are verified, all caches are cleared. Nothing you entered is changed.</div>
        <form method="post" action="?m=tools" style="margin:0"><?= csrf_field() ?><input type="hidden" name="do" value="apply_update"><button class="btn" type="submit" style="background:#e67e22">🔄 Update now</button></form>
      </div>
    <?php endif;
    // "website updated" banner — shown once after the Update finished
    $upNote = json_decode((string)q_val("SELECT mval FROM _meta WHERE mkey='upgrade_notice'", [], ''), true);
    if ($upNote && admin_can($ADMIN, 'settings')): $schemaUp = json_decode((string)q_val("SELECT mval FROM _meta WHERE mkey='schema_upgrade'", [], ''), true); ?>
      <div class="alert ok" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;border-left:4px solid #1d7d4d">
        <div style="flex:1">✅ <b>Website updated to v<?= e($upNote['to']) ?></b> on <?= date('d M Y, h:i A', strtotime($upNote['at'])) ?>
          <?= ($upNote['from'] ?? '') ? '(from '.(preg_match('/^\d/', $upNote['from']) ? 'v' : '').e($upNote['from']).')' : '' ?> —
          database <?= $schemaUp && strtotime($schemaUp['at']) >= strtotime($upNote['at']) - 120 ? 'upgraded (schema v'.e($schemaUp['from']).' → v'.e($schemaUp['to']).')' : 'checked (schema v'.e($upNote['schema'] ?? '').', no change needed)' ?>
          and <b>all caches cleared</b>. Everything you had entered is kept.
          <a href="<?= base_url('README.md') ?>" target="_blank" style="margin-left:6px">What's new →</a></div>
        <form method="post" action="?m=tools" style="margin:0"><?= csrf_field() ?><input type="hidden" name="do" value="dismiss_upgrade"><button class="btn sm ghost" type="submit">Got it</button></form>
      </div>
    <?php endif;
}

function admin_footer(): void {
    global $ADMIN; ?>
      </div></div></div>
    <div id="toasts"></div>
    <script>
    window.ADMIN = {base:'<?= base_url() ?>', csrf:'<?= csrf_token() ?>', id: <?= (int)$ADMIN['id'] ?>, upMaxPx:<?= (int)setting('upload_max_px','2000') ?>, upQuality:<?= (int)setting('upload_quality','86') ?>, videoCompressMB:<?= (int)setting('media_video_compress_mb','6') ?>, videoMaxH:<?= (int)setting('media_video_height','720') ?>};
    </script>
    <script src="<?= base_url('assets/js/editor.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/editor.js') ?>"></script>
    <script src="<?= base_url('assets/js/uploader.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/uploader.js') ?>"></script>
    <script src="<?= base_url('assets/js/builder.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/builder.js') ?>"></script>
    <script>
    function toast(msg, icon){
      const t = document.createElement('div'); t.className='toast';
      t.innerHTML = '<i class="fa-solid '+(icon||'fa-bell')+'"></i>'+msg;
      t.onclick = ()=>t.remove();
      document.getElementById('toasts').appendChild(t);
      setTimeout(()=>t.remove(), 8000);
    }
    function beep(){
      try{ const ctx = new (window.AudioContext||window.webkitAudioContext)();
        const o = ctx.createOscillator(), g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination); o.frequency.value = 880;
        g.gain.setValueAtTime(.25, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime+.6);
        o.start(); o.stop(ctx.currentTime+.6);}catch(e){}
    }
    const at = document.getElementById('availToggle');
    at && at.addEventListener('click', ()=>{
      const fd = new FormData(); fd.append('action','toggle_avail'); fd.append('csrf', ADMIN.csrf);
      fetch(ADMIN.base+'/api/agent_chat.php', {method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        at.classList.toggle('on', d.available===1);
        document.getElementById('availLbl').textContent = d.available===1 ? 'Available for chat' : 'Away';
      });
    });
    let lastAlertChat = 0, lastFood = -1;
    const baseTitle = document.title;
    function chime(){
      try { const ctx = new (window.AudioContext||window.webkitAudioContext)();
        [[784,0],[988,.18],[1175,.36]].forEach(([f,t]) => {
          const o = ctx.createOscillator(), g = ctx.createGain(); o.type = 'triangle';
          o.connect(g); g.connect(ctx.destination); o.frequency.value = f;
          g.gain.setValueAtTime(.0001, ctx.currentTime+t); g.gain.exponentialRampToValueAtTime(.35, ctx.currentTime+t+.02);
          g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime+t+.5); o.start(ctx.currentTime+t); o.stop(ctx.currentTime+t+.55); });
      } catch(e){}
    }
    function pollAlerts(){
      const fd = new FormData(); fd.append('action','alerts'); fd.append('csrf', ADMIN.csrf);
      fetch(ADMIN.base+'/api/agent_chat.php', {method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (!d.ok) return;
        if (d.waiting > 0 && d.latest_chat > lastAlertChat) {
          lastAlertChat = d.latest_chat;
          beep(); toast('💬 Visitor waiting in live chat — <a href="?m=chat" style="color:#f0c987">open console</a>', 'fa-comments');
          if (Notification && Notification.permission === 'granted') new Notification('Visitor waiting in live chat');
        }
        // 🍽 food orders: chime + toast + desktop notification; keeps ringing until accepted (if enabled)
        const sb = document.getElementById('sbFood');
        if (sb) { sb.textContent = d.food_new; sb.style.display = d.food_new > 0 ? '' : 'none'; }
        const foodMuted = (()=>{ try { return localStorage.getItem('fo_mute') === '1'; } catch(e) { return false; } })();
        if (d.food_new > 0) {
          if (d.food_latest > lastFood) {
            if (lastFood !== -1) {
              if (!foodMuted) chime();
              toast('🍽 <b>New food order!</b> ' + d.food_new + ' waiting — <a href="?m=menu" style="color:#f0c987">open orders</a>', 'fa-utensils');
              if (window.Notification && Notification.permission === 'granted') new Notification('🍽 New food order', {body: d.food_new + ' order(s) waiting to be accepted', tag: 'food'});
            } else { if (!foodMuted) chime(); toast('🍽 ' + d.food_new + ' food order(s) waiting to be accepted — <a href="?m=menu" style="color:#f0c987">open orders</a>', 'fa-utensils'); }
            lastFood = d.food_latest;
          } else if (d.food_repeat && !foodMuted) chime();
          document.title = '(' + d.food_new + ') 🍽 New order — ' + baseTitle;
        } else { document.title = baseTitle; if (lastFood === -1) lastFood = 0; }
        if (d.followups > 0 && !window.__fuToasted) { window.__fuToasted = true;
          toast('⏰ '+d.followups+' follow-up(s) due today — <a href="?m=leads&f=due" style="color:#f0c987">view</a>', 'fa-bell'); }
      }).catch(()=>{});
    }
    // sidebar: keep its scroll position between pages, show the active item, quick filter
    (function(){
      const sb = document.querySelector('.sidebar'), nav = document.getElementById('sbNav'), q = document.getElementById('sbSearch');
      if (!sb) return;
      try { const y = sessionStorage.getItem('sb_scroll'); if (y !== null) sb.scrollTop = +y; else { const a = nav.querySelector('a.active'); if (a) a.scrollIntoView({block:'center'}); } } catch(e){}
      const save = () => { try { sessionStorage.setItem('sb_scroll', sb.scrollTop); } catch(e){} };
      sb.addEventListener('scroll', save, {passive:true});
      nav.addEventListener('click', e => {
        const t = e.target.closest('.sb-tgl');
        if (t) { e.preventDefault(); e.stopPropagation(); const sub = t.closest('a').nextElementSibling; if (sub) sub.classList.toggle('open'); }
        save();
      });
      if (q) q.addEventListener('input', () => {
        const v = q.value.trim().toLowerCase();
        nav.querySelectorAll(':scope > a').forEach(a => { const sub = a.nextElementSibling && a.nextElementSibling.classList.contains('sb-sub') ? a.nextElementSibling : null;
          const hit = !v || a.textContent.toLowerCase().includes(v) || (sub && sub.textContent.toLowerCase().includes(v));
          a.style.display = hit ? '' : 'none'; if (sub) { sub.style.display = hit ? '' : 'none'; if (v && hit) sub.classList.add('open'); } });
        nav.querySelectorAll('.sb-sec').forEach(h => { let n = h.nextElementSibling, any = false; while (n && !n.classList.contains('sb-sec')) { if (n.style.display !== 'none') any = true; n = n.nextElementSibling; } h.style.display = any || !v ? '' : 'none'; });
      });
    })();
    if (window.Notification && Notification.permission === 'default') Notification.requestPermission();
    setInterval(pollAlerts, 12000); pollAlerts();
    </script>
    </body></html>
    <?php
}
