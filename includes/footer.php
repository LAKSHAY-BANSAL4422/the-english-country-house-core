<?php /* expects header.php already included */
$policyPages = q_all("SELECT title,slug FROM pages WHERE page_type='policy' AND active=1 ORDER BY menu_order");
$locPages = q_all("SELECT title,slug FROM pages WHERE page_type='location' AND active=1 ORDER BY menu_order, id LIMIT 8");
?>
</main>
<?php /* ============================================================
 * REDESIGN — 5-column footer. Data sources unchanged:
 * Settings (logo_footer, footer_about, contact_*, social_*), footer_links(), policy + location pages.
 * ============================================================ */
$rdPhones = array_values(array_filter([setting('contact_phone'), setting('contact_phone2')]));
// round 11: Facebook removed (the page is not in use); Instagram always links to the hotel's profile unless Admin → Settings has another one
$rdSocial = ['instagram'=>'fa-instagram','youtube'=>'fa-youtube','x'=>'fa-x-twitter','linkedin'=>'fa-linkedin-in'];
$rdSite = preg_replace('~^https?://~', '', rtrim(base_url(''), '/'));
?>
<footer class="rd-footer site-footer">
  <div class="rd-footer__grid">
    <div class="rd-footer__col rd-footer__col--brand">
      <a class="rd-footer__logo" href="<?= base_url('') ?>" aria-label="<?= e(setting('site_name')) ?> — home">
        <?php if (setting('logo_footer')): ?>
          <img src="<?= base_url(setting('logo_footer')) ?>" alt="<?= e(setting('site_name')) ?>" width="234" height="346" loading="lazy">
        <?php else: ?><span><?= e(setting('site_name')) ?></span><?php endif; ?>
      </a>
      <?php if (setting('footer_about')): ?><p class="rd-footer__about"><?= e(setting('footer_about')) ?></p><?php endif; ?>
      <div class="rd-footer__social">
        <?php foreach ($rdSocial as $k => $ic): $su = rd_social_url($k); if ($su === '') continue; if ($k === 'youtube'): /* round 18: the official YouTube icon (full colour, unaltered) */ ?>
          <a class="rd-footer__yt" href="<?= e($su) ?>" target="_blank" rel="noopener noreferrer" aria-label="The English Country House on YouTube"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 159 110" width="29" height="20" aria-hidden="true" focusable="false"><path d="m154 17.5c-1.82-6.73-7.07-12-13.8-13.8-9.04-3.49-96.6-5.2-122 0.1-6.73 1.82-12 7.07-13.8 13.8-4.08 17.9-4.39 56.6 0.1 74.9 1.82 6.73 7.07 12 13.8 13.8 17.9 4.12 103 4.7 122 0 6.73-1.82 12-7.07 13.8-13.8 4.35-19.5 4.66-55.8-0.1-75z" fill="#f00"/><path d="m105 55-40.8-23.4v46.8z" fill="#fff"/></svg></a>
        <?php else: ?>
          <a href="<?= e($su) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($k)) ?>"><i class="fa-brands <?= $ic ?>" aria-hidden="true"></i></a>
        <?php endif; endforeach; ?>
      </div>
      <?php if (setting('menu_enabled','1') === '1' && setting('menu_show_footer_qr','1') === '1'): ?>
      <a class="rd-footer__qr f-menuqr" href="<?= u('menu.php') ?>" title="Open our menu">
        <canvas data-qr-lazy="<?= e(u('menu.php').'?src=qr') ?>" width="64" height="64"></canvas>
        <span><b>Our Menu</b><small>Scan or tap to order</small></span>
      </a>
      <?php endif; ?>
    </div>

    <div class="rd-footer__col">
      <h2 class="rd-footer__title">Contact Us</h2>
      <ul class="rd-footer__list">
        <?php foreach ($rdPhones as $ph): ?><li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $ph)) ?>"><?= e($ph) ?></a></li><?php endforeach; ?>
        <?php if (setting('contact_email')): ?><li><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
      </ul>
      <?php if (setting('contact_address')): ?><address class="rd-footer__address"><?= nl2br(e(str_replace('\n', "\n", setting('contact_address')))) ?></address><?php endif; ?>
      <ul class="rd-footer__list"><li><a href="<?= base_url('') ?>"><?= e($rdSite) ?></a></li></ul>
    </div>

    <div class="rd-footer__col">
      <h2 class="rd-footer__title">Information</h2>
      <ul class="rd-footer__list">
        <?php foreach ($policyPages as $pp): ?><li><a href="<?= u('page.php?slug='.$pp['slug']) ?>"><?= e($pp['title']) ?></a></li><?php endforeach; ?>
      </ul>
      <?php if ($locPages): ?>
      <h3 class="rd-footer__title rd-footer__title--sub">Nearby Stays</h3>
      <ul class="rd-footer__list">
        <?php foreach ($locPages as $lp): ?><li><a href="<?= u('page.php?slug='.$lp['slug']) ?>"><?= e($lp['title']) ?></a></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <div class="rd-footer__col">
      <h2 class="rd-footer__title">Quick Links</h2>
      <ul class="rd-footer__list">
        <?php foreach (rd_gallery_menu(footer_links(), ['amenities.php', 'amenities', 'rooms.php', 'rooms']) as $fl): [$flLabel, $flAttrs] = rd_cms_link((string)$fl['url'], (string)$fl['label']); /* round 4: booking link → enquiry form */ ?><li><a <?= $flAttrs ?>><?= e($flLabel) ?></a></li><?php endforeach; ?>
      </ul>
    </div>

    <div class="rd-footer__col">
      <h2 class="rd-footer__title">Newsletter</h2>
      <p class="rd-footer__text"><?= e(rd('newsletter_text')) ?><!-- REPLACE CONTENT --></p>
      <?php /* PLACEHOLDER: the site has no public newsletter sign-up (opt-in lives in the guest account).
               This form posts nowhere; redesign.js shows a short note instead. Wire it to a real handler later. */ ?>
      <form class="rd-news" data-rd-newsletter novalidate>
        <label class="rd-sr" for="rdNewsEmail">Email address</label>
        <span class="rd-news__ico"><?= rd_icon('mail') ?></span>
        <input class="rd-news__input" id="rdNewsEmail" type="email" placeholder="Enter your email" autocomplete="email">
        <button class="rd-news__btn" type="submit" aria-label="Subscribe"><?= rd_icon('arrow') ?></button>
      </form>
      <p class="rd-news__msg" role="status" aria-live="polite"></p>
    </div>
  </div>

  <div class="rd-footer__bottom">
    <p class="rd-footer__copy">© <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.</p>
    <p class="rd-footer__credit"><?= rd('credit_html') ?></p><?php /* round 17: "Website By Lakshay Bansal & Yash Saraswat" (includes/redesign_content.php, credit_html) */ ?>
    <div class="rd-footer__meta">
      <span class="rd-footer__pay" aria-label="Payment methods">
        <?php foreach (rd('payment_icons', []) as $ic => $lbl): ?><i class="fa-brands <?= e($ic) ?>" title="<?= e($lbl) ?>" aria-hidden="true"></i><?php endforeach; ?>
      </span>
      <?php /* PLACEHOLDER: the site is English-only — this is not a working language switcher */ ?>
      <span class="rd-lang" title="English only"><?= rd_icon('globe') ?> English</span>
    </div>
  </div>
</footer>

<?php /* ---- app-like bottom navigation on mobile ---- */ ?>
<?php
$bnChat = setting('chat_enabled') === '1';
$bnWa   = false;   // WhatsApp lives in the floating button on the right (all devices) — not in the bottom bar
$bnMenu = setting('menu_enabled','1') === '1';
$bnAcct = !$bnChat || !$bnWa || !$bnMenu;   // keep 6 items max: Account stays only when a slot is free (it is always in the header too)
?>
<nav class="bottom-nav <?= ($bnChat && $bnWa && $bnMenu) ? 'six' : '' ?>">
  <a href="<?= base_url('') ?>" class="<?= $curFile==='index.php'?'on':'' ?>"><i class="fa-solid fa-house"></i><span>Home</span></a>
  <a href="<?= u('rooms.php') ?>" class="<?= in_array($curFile,['rooms.php','room.php'])?'on':'' ?>"><i class="fa-solid fa-bed"></i><span>Rooms</span></a>
  <a <?= rd_enquiry_attrs() ?> class="bn-book"><i class="fa-brands fa-whatsapp"></i><span>Enquire</span></a><?php /* round 4: was "Book" → booking modal */ ?>
  <?php if ($bnMenu): ?><a href="<?= u('menu.php') ?>" class="<?= $curFile==='menu.php'?'on':'' ?>"><i class="fa-solid fa-utensils"></i><span>Menu</span></a><?php endif; ?>
  <?php if ($bnChat): ?><a href="#chat" class="bn-chat" id="bnChat" role="button"><i class="fa-regular fa-comments"></i><span>Chat</span><em class="bn-badge" id="bnChatBadge" style="display:none">1</em></a><?php endif; ?>
  <?php if ($bnWa): ?><a href="<?= e(wa_link('', setting('wa_message'))) ?>" class="bn-wa" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i><span>WhatsApp</span></a><?php endif; ?>
  <?php if ($bnAcct): ?><a href="<?= base_url('account/index.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '','account')?'on':'' ?>"><i class="fa-regular fa-user"></i><span>Account</span></a><?php endif; ?>
</nav>

<?php if (setting('wa_enabled') === '1' && setting('wa_number')): ?>
<a class="wa-float wa-mid <?= setting('wa_position','right')==='left'?'wa-left':'' ?> <?= setting('wa_show_mobile')!=='1'?'hide-mobile':'' ?> <?= setting('wa_show_desktop')!=='1'?'hide-desktop':'' ?>"
   href="<?= e(wa_link('', setting('wa_message'))) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
  <i class="fa-brands fa-whatsapp"></i><span>WhatsApp</span>
</a>
<?php endif; ?>

<?php /* REDESIGN (round 4): the WhatsApp enquiry form that every public Enquire / former Book button opens. Client-side only. */
include __DIR__.'/enquiry_modal.php'; ?>

<?php /* ---- global "Book Your Stay" modal: visual calendar → booking page (opened by every Book button + the floating pill) ----
   REDESIGN (round 4): disconnected — nothing on the public site opens it any more, so its markup and book-modal.js are
   not printed ($rdBookModal = false). The code is kept; set $rdBookModal back to the original condition to restore it. */
$rdBookModal = false && !in_array($curFile, ['booking.php','pay.php','booking-done.php'], true) && setting('book_modal_enabled','1') === '1';
if ($rdBookModal):
  $bkRooms = public_rooms(); ?>
<?php /* REDESIGN (round 2): the floating "Book Your Stay" pill is removed site-wide; the navbar "Book Now" button stays.
         The admin setting book_float_enabled is untouched (it has no effect while this markup is out). */ ?>
<div class="bk-modal" id="bookModal" aria-hidden="true" role="dialog" aria-label="Book your stay">
  <div class="bk-box">
    <button type="button" class="bk-x" data-book-close aria-label="Close">&times;</button>
    <div class="bk-head"><span class="bk-eyebrow">Check availability &amp; rates</span><h3>Book Your Stay</h3></div>
    <form class="bk-form" id="bkForm" action="<?= u('booking.php') ?>" method="get">
      <div class="bk-row">
        <label>Room</label>
        <select name="room_id" id="bkRoom">
          <option value="">Any room — show me the best option</option>
          <?php foreach ($bkRooms as $r): ?><option value="<?= (int)$r['id'] ?>" data-max-adults="<?= (int)$r['max_adults'] ?>" data-max-children="<?= (int)$r['max_children'] ?>"><?= e($r['name']) ?> — from <?= money($r['base_price']) ?>/night</option><?php endforeach; ?>
        </select>
      </div>
      <div class="bk-dates">
        <button type="button" class="bk-date on" data-which="checkin"><small>Check-in</small><b id="bkInLbl">Select a date</b></button>
        <span class="bk-arrow"><i class="fa-solid fa-arrow-right-long"></i></span>
        <button type="button" class="bk-date" data-which="checkout"><small>Check-out</small><b id="bkOutLbl">Select a date</b></button>
      </div>
      <div class="bk-cal" id="bkCal" data-sym="<?= e(setting('currency_symbol','₹')) ?>"></div>
      <div class="bk-legend"><span><i class="lg lg-sel"></i> Your stay</span><span><i class="lg lg-sold"></i> Sold out</span><span><i class="lg lg-today"></i> Today</span></div>
      <input type="hidden" name="checkin" id="bkIn"><input type="hidden" name="checkout" id="bkOut">
      <div class="bk-guests">
        <label>Adults <select name="adults" id="bkAdults"><?php for ($i = 1; $i <= 6; $i++): ?><option <?= $i === 2 ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></label>
        <label>Children <small>(5–12 yrs)</small> <select name="children" id="bkChildren"><?php for ($i = 0; $i <= 4; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></label>
      </div>
      <div class="bk-foot">
        <span class="bk-sum" id="bkSum">Pick your check-in and check-out dates</span>
        <button class="btn btn-primary" type="submit" id="bkGo" disabled>Check Rates &amp; Book <i class="fa-solid fa-arrow-right"></i></button>
      </div>
      <p class="bk-note"><i class="fa-solid fa-shield-halved"></i> Best rate guaranteed · <a href="<?= e(wa_link('', 'Hi! I would like to book a stay.')) ?>" target="_blank" rel="noopener">or WhatsApp us</a></p>
    </form>
  </div>
</div>
<?php endif; ?>

<?php
/* ---- promotional popups ---- */
$today = date('Y-m-d');
$popups = q_all("SELECT * FROM popups WHERE active=1 AND (date_from IS NULL OR date_from<=?) AND (date_to IS NULL OR date_to>=?)", [$today, $today]);
$pvCount = (int)($_SESSION['pv_count'] ?? 1);
$curPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$noPopupHere = in_array($curFile, ['booking.php','pay.php','booking-done.php','menu.php','invoice.php','login.php','food.php'], true) || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/account/');
foreach ($popups as $pu):
  if ($noPopupHere && $pu['pages'] === '') continue;   // checkout & account pages stay clear of promotions
  if ($pu['pages'] !== '') {
      $match = false;
      foreach (array_map('trim', explode(',', $pu['pages'])) as $pat) if ($pat !== '' && str_contains($curPath, $pat)) { $match = true; break; }
      if (!$match) continue;
  }
  if ($pvCount < (int)$pu['pageview_count']) continue;
  $form = $pu['form_id'] ? q_row("SELECT * FROM forms WHERE id=? AND active=1", [(int)$pu['form_id']]) : null;
?>
<div class="promo-popup" id="popup<?= $pu['id'] ?>" data-delay="<?= (int)$pu['delay_seconds'] ?>" data-duration="<?= (int)$pu['duration_seconds'] ?>" data-once="<?= (int)$pu['show_once_hours'] ?>">
  <div class="promo-box">
    <button class="promo-close" onclick="closePopup(<?= $pu['id'] ?>)">&times;</button>
    <?php if ($pu['image']): ?><img src="<?= base_url($pu['image']) ?>" alt=""><?php endif; ?>
    <div class="promo-body">
      <h3><?= e($pu['title']) ?></h3>
      <div><?= $pu['content'] ?></div>
      <?php if ($form): $ffields = json_decode($form['fields'], true) ?: []; ?>
      <form class="promo-form" onsubmit="return submitBuiltForm(this)" data-form="<?= $form['id'] ?>">
        <?php foreach ($ffields as $ff): if(in_array($ff['type'],['textarea'])): ?>
          <textarea name="<?= e($ff['name']) ?>" placeholder="<?= e($ff['label']) ?>" <?= !empty($ff['required'])?'required':'' ?>></textarea>
        <?php else: ?>
          <input type="<?= e($ff['type']) ?>" name="<?= e($ff['name']) ?>" placeholder="<?= e($ff['label']) ?>" <?= !empty($ff['required'])?'required':'' ?>>
        <?php endif; endforeach; ?>
        <button class="btn btn-primary" type="submit"><?= e($form['submit_label']) ?></button>
        <div class="form-msg"></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if (setting('chat_enabled') === '1') include __DIR__.'/chat_widget.php'; ?>

<script>window.ECH = {base: '<?= base_url() ?>', csrf: '<?= csrf_token() ?>'};</script>
<script src="<?= base_url('assets/js/phone.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/phone.js') ?>.<?= e(setting('cache_bust','1')) ?>"></script><?php /* round 5: site-wide phone validation */ ?>
<script src="<?= base_url('assets/js/main.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/main.js') ?>.<?= e(setting('cache_bust','1')) ?>"></script>
<script src="<?= base_url('assets/js/redesign.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/redesign.js') ?>.<?= e(setting('cache_bust','1')) ?>"></script>
<?php if ($rdBookModal): ?><script src="<?= base_url('assets/js/book-modal.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/book-modal.js') ?>.<?= e(setting('cache_bust','1')) ?>" defer></script><?php endif; ?>
<?php if (setting('menu_enabled','1') === '1' && setting('menu_show_footer_qr','1') === '1'): ?>
<script>
/* lazy-load the branded menu QR in the footer only when it scrolls into view */
(function(){
  var cv = document.querySelector('canvas[data-qr-lazy]'); if (!cv || !('IntersectionObserver' in window)) return;
  window.ECH_QR = window.ECH_QR || {logo: '<?= e(base_url(setting('menu_qr_logo') ?: 'assets/img/brand/logo.png')) ?>', color: '<?= e(setting('menu_qr_color','#163a2c')) ?>'};
  function load(src){ return new Promise(function(res){ var s=document.createElement('script'); s.src=src; s.onload=res; s.onerror=res; document.body.appendChild(s); }); }
  var io = new IntersectionObserver(function(en){
    if (!en[0].isIntersecting) return; io.disconnect();
    var go = function(){ ECHQR.draw(cv, cv.dataset.qrLazy, {size: 78}); };
    if (window.ECHQR) return go();
    (window.qrcode ? Promise.resolve() : load('<?= base_url('assets/js/vendor/qrcode.js') ?>'))
      .then(function(){ return load('<?= base_url('assets/js/menu-qr.js') ?>'); }).then(go);
  }, {rootMargin: '200px'});
  io.observe(cv);
})();
</script>
<?php endif; ?>
<?php if (setting('pwa_enabled')==='1'): ?>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?= base_url('sw.js') ?>').catch(()=>{});</script>
<?php endif; ?>
<?= setting('body_scripts') ?>
<?php if (function_exists('live_editor_on')) include __DIR__.'/live_editor.php'; ?>
</body>
</html>
