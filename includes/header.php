<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/redesign_content.php';   // REDESIGN: presentation copy + fallbacks (no DB access)
sess_start();
db();
rd_phone_start();   // REDESIGN (round 4): one hotel phone / WhatsApp number on every public page (in memory, no DB write)
maintenance_gate();

/* $PAGE may be set by the calling script: ['title','desc','keywords','schema','banner','crumb','og_image'] */
$PAGE = $PAGE ?? [];
$pageTitle = $PAGE['title'] ?? setting('seo_title');
$pageDesc  = $PAGE['desc'] ?? setting('seo_desc');
$pageKeys  = $PAGE['keywords'] ?? setting('seo_keywords');
require_once __DIR__.'/seo.php';   // round 17: technical SEO (canonical, robots, social cards, JSON-LD graph)
/* round 17: private pages (account, payment, booking confirmation, email preferences) are never indexed */
if (in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['booking-done.php','pay.php','invoice.php','unsubscribe.php','booking.php'], true)
    || preg_match('~/account/~', $_SERVER['SCRIPT_NAME'] ?? '') || (!empty($_GET['edit']))) $PAGE['noindex'] = true;
if (empty($PAGE['noindex'])) seo_redirect_canonical(seo_canonical($PAGE));   // one address per page (301 from /room.php?slug=…, /index.php …)
seo_headers($PAGE);

track_visit($_SERVER['REQUEST_URI'] ?? '/');

$menuPages = q_all("SELECT title,slug FROM pages WHERE show_in_menu=1 AND active=1 AND page_type<>'system' AND slug NOT IN ('home','about') ORDER BY menu_order");
$user = current_user();
$siteName = setting('site_name');
$curFile = basename($_SERVER['SCRIPT_NAME'] ?? '');

$menu = menu_items();
?><!DOCTYPE html>
<html lang="en-IN">
<head>
<?= gtm_head() ?>
<script>document.documentElement.classList.add('js')</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<?php if ($pageKeys): ?><meta name="keywords" content="<?= e($pageKeys) ?>"><?php endif; ?>
<?php seo_head($PAGE, $pageTitle, $pageDesc); ?>
<?php /* ---- GEO meta (local SEO) ---- */ if (setting('geo_lat')): ?>
<meta name="geo.region" content="<?= e(setting('geo_region','IN-UT')) ?>">
<meta name="geo.placename" content="<?= e(setting('geo_placename')) ?>">
<meta name="geo.position" content="<?= e(setting('geo_lat')) ?>;<?= e(setting('geo_lng')) ?>">
<meta name="ICBM" content="<?= e(setting('geo_lat')) ?>, <?= e(setting('geo_lng')) ?>">
<?php endif; ?>
<link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any"><?php if (setting('favicon')): ?><link rel="icon" type="image/png" href="<?= base_url(setting('favicon')) ?>"><link rel="apple-touch-icon" href="<?= base_url(is_file(ROOT_PATH.'/assets/img/icon-192.png') ? 'assets/img/icon-192.png' : setting('favicon')) ?>"><?php endif; ?>
<?php if (setting('pwa_enabled')==='1'): ?>
<link rel="manifest" href="<?= base_url('manifest.php') ?>">
<meta name="theme-color" content="<?= e(setting('color_secondary')) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(setting('pwa_short_name', $siteName)) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php /* REDESIGN: Poppins replaces the Settings fonts (Cormorant / Outfit / Great Vibes) on the public site */ ?>
<?php /* THEME: the floral theme's fonts (see assets/css/theme.css) */ ?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<?php /* round 17 (speed): the icon font (~300 KB of font files) no longer blocks the first paint or competes with the main photo;
         the menu, logo, heading and buttons do not use it. Icons fade in a moment later. */ ?>
<link rel="preload" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>?v=6.5.2" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>?v=6.5.2"></noscript>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/style.css') ?>.<?= e(setting('cache_bust','1')) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/redesign.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/redesign.css') ?>.<?= e(setting('cache_bust','1')) ?>">
<style>
:root{
 --c-primary:<?= e(setting('color_primary')) ?>;
 --c-primary-dark:<?= e(setting('color_primary_dark')) ?>;
 --c-secondary:<?= e(setting('color_secondary')) ?>;
 --c-accent:<?= e(setting('color_accent')) ?>;
 --c-bg:<?= e(setting('color_bg')) ?>;
 --c-bg-alt:<?= e(setting('color_bg_alt')) ?>;
 --c-text:<?= e(setting('color_text')) ?>;
 --c-heading:<?= e(setting('color_heading')) ?>;
 --c-footer-bg:<?= e(setting('color_footer_bg')) ?>;
 --c-footer-text:<?= e(setting('color_footer_text')) ?>;
 --c-btn-text:<?= e(setting('color_btn_text')) ?>;
 --f-heading:'<?= e(setting('font_heading','Cormorant Garamond')) ?>',serif;
 --f-body:'<?= e(setting('font_body','Outfit')) ?>',sans-serif;
 --f-accent:'<?= e(setting('font_accent','Great Vibes')) ?>',cursive;
}
</style>
<?php if (!empty($PAGE['schema'])): ?><script type="application/ld+json"><?= $PAGE['schema'] ?></script><?php endif; ?>
<?php if (setting('schema_org')): ?><script type="application/ld+json"><?= setting('schema_org') ?></script><?php endif; ?>
<?= $PAGE['head'] ?? '' ?>
<?php /* THEME: floral theme layer, after redesign.css and any page CSS (menu.css) so it can restyle both */ ?>
<link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/theme.css') ?>.<?= e(setting('cache_bust','1')) ?>">
<?= setting('ga_code') ?>
<?= setting('head_scripts') ?>
</head>
<body class="rd">
<?= gtm_body() ?>
<div id="preload"><div class="pl-logo"><i class="fa-solid fa-fan fa-spin"></i></div></div>
<a class="rd-skip" href="#rdMain">Skip to content</a>
<?php
/* ============================================================
 * REDESIGN — announcement bar + navbar + mobile drawer
 * Menu items still come from menu_items() + $menuPages exactly as before;
 * only the markup changed. Optional dropdown children:
 *   - any menu item that carries a "children" array (same shape: label/url/children) renders a dropdown;
 *   - the Rooms item lists the active rooms (read-only SELECT, same as the booking modal's list).
 * ============================================================ */
$rdRoomsMenu = public_rooms();
$rdNav = [];
/* REDESIGN (round 4): "Explore Mussoorie" always sits immediately after Gallery (desktop + drawer share this list).
   Display order only — the saved menu setting is not changed. */
$rdMenuItems = array_values(rd_gallery_menu($menu));
$rdIsGal = fn($mi) => preg_match('~(^|/)gallery(\.php)?$~i', (string)(parse_url($mi['url'] ?? '', PHP_URL_PATH) ?? '')) || strcasecmp(trim($mi['label'] ?? ''), 'Gallery') === 0;
$rdIsExp = fn($mi) => preg_match('~(^|/)explore(\.php)?$~i', (string)(parse_url($mi['url'] ?? '', PHP_URL_PATH) ?? '')) || stripos($mi['label'] ?? '', 'explore') === 0;
$rdExpI = null; $rdGalI = null;
foreach ($rdMenuItems as $i => $mi) { if ($rdExpI === null && $rdIsExp($mi)) $rdExpI = $i; if ($rdGalI === null && $rdIsGal($mi)) $rdGalI = $i; }
if ($rdExpI !== null && $rdGalI !== null && $rdExpI !== $rdGalI + 1) {
    $rdExp = array_splice($rdMenuItems, $rdExpI, 1);
    if ($rdExpI < $rdGalI) $rdGalI--;
    array_splice($rdMenuItems, $rdGalI + 1, 0, $rdExp);
}
foreach ($rdMenuItems as $mi) {   // REDESIGN: Gallery → internal /gallery page
    $path = basename(parse_url($mi['url'], PHP_URL_PATH) ?? '');
    $kids = is_array($mi['children'] ?? null) ? $mi['children'] : [];
    if (!$kids && $path === 'rooms.php' && $rdRoomsMenu) {
        $kids[] = ['label'=>'All Rooms', 'url'=>'rooms.php'];
        foreach ($rdRoomsMenu as $rr) $kids[] = ['label'=>$rr['name'], 'url'=>'room.php?slug='.$rr['slug']];
    }
    $rdNav[] = ['label'=>$mi['label'], 'href'=>menu_url($mi['url']), 'active'=>$path === $curFile, 'children'=>$kids];
}
foreach ($menuPages as $mp) $rdNav[] = ['label'=>$mp['title'], 'href'=>u('page.php?slug='.$mp['slug']), 'active'=>false, 'children'=>[]];

/* recursive renderer: desktop dropdown panels (with flyouts) or mobile accordion */
$rdMenuList = function (array $items, int $depth, string $mode) use (&$rdMenuList) {
    static $uid = 0;
    foreach ($items as $it):
        $kids = array_values(array_filter($it['children'] ?? [], fn($k) => !isset($k['visible']) || !empty($k['visible'])));
        $href = $it['href'] ?? menu_url((string)($it['url'] ?? '#'));
        $id = 'rdsub' . $mode[0] . (++$uid);
        if ($mode === 'desktop'): ?>
        <li class="rd-menu__item<?= $kids ? ' rd-menu__item--has-sub' : '' ?><?= !empty($it['active']) ? ' is-current' : '' ?>">
          <a class="rd-menu__link" href="<?= e($href) ?>" <?= !empty($it['active']) ? ' aria-current="page"' : '' ?>><span class="rd-navtext" data-text="<?= e($it['label']) ?>"><?= e($it['label']) ?></span></a>
          <?php if ($kids): ?>
          <button type="button" class="rd-menu__toggle" aria-expanded="false" aria-controls="<?= $id ?>" aria-label="Show <?= e($it['label']) ?> menu"><?= rd_icon($depth ? 'chevr' : 'chev') ?></button>
          <ul class="rd-dd<?= $depth ? ' rd-dd--fly' : '' ?>" id="<?= $id ?>">
            <?php $rdMenuList($kids, $depth + 1, $mode); ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php else: ?>
        <li class="rd-acc__item">
          <div class="rd-acc__row">
            <a class="rd-acc__link" href="<?= e($href) ?>"<?= !empty($it['active']) ? ' aria-current="page"' : '' ?>><span class="rd-navtext" data-text="<?= e($it['label']) ?>"><?= e($it['label']) ?></span></a>
            <?php if ($kids): ?><button type="button" class="rd-acc__toggle" aria-expanded="false" aria-controls="<?= $id ?>" aria-label="Show <?= e($it['label']) ?> menu"><?= rd_icon('chev') ?></button><?php endif; ?>
          </div>
          <?php if ($kids): ?><div class="rd-acc__panel" id="<?= $id ?>"><ul class="rd-acc__sub"><?php $rdMenuList($kids, $depth + 1, $mode); ?></ul></div><?php endif; ?>
        </li>
        <?php endif;
    endforeach;
};
$rdLogo = rd('nav_logo') ?: setting('logo_header');
$rdPhone = setting('contact_phone'); $rdEmail = setting('contact_email');
$rdAcctUrl = base_url($user ? 'account/index.php' : 'account/login.php');
?>
<div class="rd-announce" role="region" aria-label="Contact and offers">
  <div class="rd-announce__inner">
    <?php if ($rdPhone): ?><a class="rd-announce__item" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $rdPhone)) ?>"><?= rd_icon('phone') ?><span><?= e($rdPhone) ?></span></a><?php else: ?><span></span><?php endif; ?>
    <p class="rd-announce__promo"><?= e(rd('announcement')) ?><!-- REPLACE CONTENT (includes/redesign_content.php) --></p>
    <?php if ($rdEmail): ?><a class="rd-announce__item" href="mailto:<?= e($rdEmail) ?>"><?= rd_icon('mail') ?><span><?= e($rdEmail) ?></span></a><?php else: ?><span></span><?php endif; ?>
  </div>
</div>

<div class="rd-header" id="rdHeader">
  <header class="rd-nav" id="siteHeader">
    <div class="rd-nav__inner">
      <a class="rd-nav__brand" href="<?= base_url('') ?>" aria-label="<?= e($siteName) ?> — home">
        <?php if ($rdLogo): ?><img src="<?= base_url($rdLogo) ?>" alt="<?= e($siteName) ?>" width="234" height="346"><?php else: ?><span class="rd-nav__brand-text"><?= e($siteName) ?></span><?php endif; ?>
      </a>
      <nav class="rd-nav__menu" aria-label="Main">
        <ul class="rd-menu" id="mainNav"><?php $rdMenuList($rdNav, 0, 'desktop'); ?></ul>
      </nav>
      <div class="rd-nav__actions">
        <a class="rd-iconbtn" href="<?= e($rdAcctUrl) ?>" aria-label="<?= $user ? 'My account' : 'Log in or register' ?>" title="<?= $user ? 'My account' : 'Log in / Register' ?>"><?= rd_icon('user') ?></a>
        <a class="rd-btn rd-btn--dark rd-nav__book" <?= rd_enquiry_attrs() ?>><?= e(rd('enquiry_cta')) ?></a><?php /* round 4: was "Book Now" → booking modal */ ?>
        <button type="button" class="rd-burger" id="rdBurger" aria-label="Open menu" aria-expanded="false" aria-controls="rdDrawer"><span></span><span></span><span></span></button>
      </div>
    </div>
  </header>
</div>

<div class="rd-drawer" id="rdDrawer" aria-hidden="true">
  <div class="rd-drawer__scrim" data-rd-close></div>
  <div class="rd-drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="rd-drawer__head">
      <?php if ($rdLogo): ?><img src="<?= base_url($rdLogo) ?>" alt="<?= e($siteName) ?>" width="40" height="59"><?php endif; ?>
      <button type="button" class="rd-iconbtn" data-rd-close aria-label="Close menu"><?= rd_icon('close') ?></button>
    </div>
    <ul class="rd-acc"><?php $rdMenuList($rdNav, 0, 'mobile'); ?></ul>
    <div class="rd-drawer__foot">
      <a class="rd-btn rd-btn--dark rd-btn--block" <?= rd_enquiry_attrs() ?>><?= e(rd('enquiry_cta')) ?></a>
      <a class="rd-btn rd-btn--line rd-btn--block" href="<?= e($rdAcctUrl) ?>"><?= rd_icon('user') ?> <?= $user ? e(explode(' ', $user['name'])[0]) : 'Log in / Register' ?></a>
      <?php if ($rdPhone): ?><a class="rd-drawer__contact" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $rdPhone)) ?>"><?= rd_icon('phone') ?> <?= e($rdPhone) ?></a><?php endif; ?>
      <?php if ($rdEmail): ?><a class="rd-drawer__contact" href="mailto:<?= e($rdEmail) ?>"><?= rd_icon('mail') ?> <?= e($rdEmail) ?></a><?php endif; ?>
    </div>
  </div>
</div>
<main id="rdMain">
