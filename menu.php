<?php
/** Our Menu — public restaurant menu with QR ordering to the room (also opened by scanning the menu QR) */
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/lib/food.php';
db(); sess_start();
if (setting('menu_enabled', '1') !== '1' && !current_admin()) { http_response_code(404); redirect(base_url('')); }

$user = current_user();
$room = mb_substr(trim((string)get('room')), 0, 100);
if ($room === '' && $user) $room = user_current_room($user);
$MENU_CTX = [
    'source' => get('src') === 'qr' ? 'qr' : ($user ? 'account' : 'website'),
    'room'   => $room,
    'name'   => $user['name'] ?? '',
    'phone'  => $user['phone'] ?? '',
    'email'  => $user['email'] ?? '',
];
$cats = menu_load(true);
$hero = setting('menu_hero_image') ?: 'assets/img/restaurant.jpg';
$theme = preg_match('/^#[0-9a-f]{6}$/i', setting('menu_theme_color')) ? setting('menu_theme_color') : '#163a2c';
$wait = menu_wait_minutes();
$call = setting('menu_order_phone') ?: setting('contact_phone');

/* Schema.org Menu (rich results / AEO) */
require_once __DIR__.'/includes/seo.php';
$schema = ['@type'=>'Restaurant','@id'=>seo_id('restaurant'),'name'=>setting('site_name').' — '.setting('menu_restaurant_name'),
    'containedInPlace'=>['@id'=>seo_id('hotel')],'priceRange'=>'₹₹','acceptsReservations'=>true,
    'servesCuisine'=>['Indian','Pahadi (Garhwali)','Chinese','Continental'],'telephone'=>$call,'url'=>seo_abs(u('menu.php')),
    'image'=>seo_abs($hero),'address'=>['@type'=>'PostalAddress','streetAddress'=>'Village Kaplani, Dhanaulti Road','addressLocality'=>'Mussoorie','addressRegion'=>'Uttarakhand','postalCode'=>'248179','addressCountry'=>'IN'],
    'hasMenu'=>['@type'=>'Menu','name'=>setting('menu_title','Our Menu'),'hasMenuSection'=>array_map(fn($c)=>[
        '@type'=>'MenuSection','name'=>$c['name'],'description'=>$c['subtitle'],
        'hasMenuItem'=>array_map(fn($i)=>['@type'=>'MenuItem','name'=>$i['name'],'description'=>$i['description'],
            'suitableForDiet'=>$i['diet']==='veg' ? 'https://schema.org/VegetarianDiet' : null,   // round 17: empty values are dropped by includes/seo.php
            'offers'=>['@type'=>'Offer','price'=>(float)$i['price'],'priceCurrency'=>'INR']], $c['items'])], $cats)]];
$itemCount = array_sum(array_map(fn($c)=>count($c['items']), $cats));

$PAGE = [
  // round 14 (SEO): title up to 60 characters, description up to 155
  'title' => 'Restaurant Menu in Mussoorie | English Country House',
  'desc'  => 'Our '.$itemCount.'-dish multi-cuisine menu: breakfast, tandoor starters, North Indian curries, pahadi specialities and desserts, served to your room.',
  'keywords' => 'restaurant in Mussoorie, restaurant menu Mussoorie, in-room dining Mussoorie, pahadi food Mussoorie, restaurant on Dhanaulti Road',
  'og_image' => $hero,
  // round 17: the restaurant joins the page's JSON-LD graph (linked to the hotel); breadcrumb + hero photo preloaded
  'nodes' => [json_decode(json_encode($schema), true)], 'seo_type' => 'menu', 'crumbs' => ['Our Menu'], 'lcp' => $hero,
  'head' => '<link rel="stylesheet" href="'.base_url('assets/css/menu.css').'?v='.@filemtime(ROOT_PATH.'/assets/css/menu.css').'.'.e(setting('cache_bust','1')).'">'
          .'<style>:root{--fm-green:'.e($theme).'}</style>',
];
include __DIR__.'/includes/header.php';
?>
<section class="fm-hero" style="padding-bottom:70px">
  <div class="fm-hero-bg" style="background-image:url('<?= e(base_url($hero)) ?>')"></div>
  <div class="fm-hero-in">
    <img class="fm-hero-logo" src="<?= base_url('assets/img/brand/logo-light.png') ?>" alt="<?= e(setting('site_name')) ?>">
    <?php /* round 14: the line above the title is inside the H1 (same look), so the heading names the house and the restaurant */ ?>
    <h1 class="rd-h1"><span class="rule"><?= e(setting('site_name')) ?> · <?= e(setting('menu_restaurant_name')) ?> </span><span class="fm-h1"><?= e(setting('menu_title','Our Menu')) ?></span></h1>
    <div class="fm-script"><?= e(setting('menu_tagline')) ?></div>
    <p><?= e(setting('menu_intro')) ?></p>
    <div class="fm-hero-pills">
      <span>⏱ ~<?= $wait ?> min</span><span>🛎 In-room dining</span><span>🌿 Veg &amp; non-veg</span><span>🏔 Pahadi specials</span>
      <?php if ($room !== ''): ?><span style="background:rgba(241,223,174,.25)">📍 <?= e($room) ?></span><?php endif; ?>
    </div>
    <div class="fm-hero-cta">
      <a class="fm-btn fm-btn-gold" href="#foodMenu"><i class="fa-solid fa-utensils"></i> Browse the menu</a>
      <a class="fm-btn fm-btn-light" href="tel:<?= e($call) ?>"><i class="fa-solid fa-phone"></i> Order now — call</a>
    </div>
  </div>
</section>

<div class="fm-wrap">
  <?php include __DIR__.'/includes/menu_view.php'; ?>

  <div class="fm" style="margin-top:0">
    <div class="fm-qrband reveal">
      <canvas data-qr="<?= e(u('menu.php').'?src=qr') ?>" data-qr-size="210" data-qr-label="SCAN FOR MENU"></canvas>
      <div>
        <div class="fm-script">Take the menu with you</div>
        <h3>Scan · Choose · We serve</h3>
        <p>Every room has this QR code. Scan it with your phone camera, pick your dishes and press <b>Order now</b> — your room, name and order reach our kitchen team on WhatsApp instantly.</p>
        <div class="fm-steps"><span><b>1</b> Scan the QR</span><span><b>2</b> Add dishes</span><span><b>3</b> Order to your room</span></div>
        <a class="fm-btn fm-btn-gold" href="tel:<?= e($call) ?>"><i class="fa-solid fa-phone"></i> Prefer to call? <?= e($call) ?></a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
