<?php
/** Admin & Team panel router — modules live in admin/modules/ */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../lib/gateways.php';
db(); sess_start();
$ADMIN = require_admin();

$MODULES = [
  /* key => [label, icon, permission, section] */
  'dashboard'   => ['Dashboard','fa-gauge-high','dashboard','Main'],
  'bookings'    => ['Bookings','fa-calendar-check','bookings','Main'],
  'leads'       => ['Lead Manager','fa-filter-circle-dollar','leads','Main'],
  'menu'        => ['Restaurant & Food Orders','fa-utensils','menu','Main'],
  'chat'        => ['Live Chat (Visitors)','fa-comments','chat','Main'],
  'chat_history'=> ['Chat History','fa-clock-rotate-left','chat','Main'],
  'ota'         => ['OTA Channels','fa-tower-broadcast','ota','Main'],
  'documents'   => ['Documents (LH/Inv/MOU)','fa-file-contract','documents','Main'],
  'rooms'       => ['Rooms','fa-bed','rooms','Website'],
  'pricing'     => ['Pricing Calendar','fa-tags','rooms','Website'],
  'amenities'   => ['Amenities','fa-spa','amenities','Website'],
  'explore'     => ['Explore Mussoorie','fa-mountain-sun','cms','Website'],
  'cms'         => ['Pages / Builder','fa-file-lines','cms','Website'],
  'gallery'     => ['Gallery','fa-images','gallery','Website'],
  'locations'   => ['Location Pages','fa-map-location-dot','cms','Website'],
  'blog'        => ['Blog','fa-newspaper','blog','Website'],
  'faqs'        => ['FAQs','fa-circle-question','faqs','Website'],
  'reviews'     => ['Reviews','fa-star','reviews','Website'],
  'forms'       => ['Form Builder','fa-table-list','forms','Marketing'],
  'popups'      => ['Popups','fa-window-restore','popups','Marketing'],
  'social'      => ['Social Scheduler','fa-share-nodes','social','Marketing'],
  'newsletter'  => ['Weekly Newsletter','fa-envelope-open-text','social','Marketing'],
  'chatbot'     => ['Chatbot & Replies','fa-robot','chat','Marketing'],
  'seo'         => ['SEO / AEO / GEO','fa-magnifying-glass-chart','seo','Settings'],
  'appearance'  => ['Appearance','fa-palette','appearance','Settings'],
  'header_footer'=>['Header & Footer','fa-table-columns','appearance','Settings'],
  'settings'    => ['Site Settings','fa-gear','settings','Settings'],
  'tools'       => ['Site Tools','fa-screwdriver-wrench','settings','Settings'],
  'team'        => ['Team Members','fa-users-gear','team','Settings'],
  'activity'    => ['Activity Log','fa-list-check','activity','Settings'],
  'profile'     => ['My Profile','fa-user','dashboard','Settings'],
];

$m = get('m', 'dashboard');
if (!isset($MODULES[$m]) || !admin_can($ADMIN, $MODULES[$m][2])) {
    $m = 'profile';
    foreach ($MODULES as $key=>$def) if (admin_can($ADMIN, $def[2])) { $m = $key; break; }
}

/* -------- automatic audit log for every admin action -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do'])) {
    $detailBits = [];
    foreach (['id','name','title','booking_id','lead_id','status','stage','room_id','preset','question'] as $k)
        if (!empty($_POST[$k]) && !is_array($_POST[$k])) $detailBits[] = "$k=".mb_substr((string)$_POST[$k],0,80);
    log_activity((int)$ADMIN['id'], $m, (string)$_POST['do'], implode(' · ', $detailBits));
}

$MODULE_TITLE = $MODULES[$m][0];
$file = __DIR__.'/modules/'.$m.'.php';
if (!is_file($file)) die('Module missing: '.e($m));
require __DIR__.'/_layout.php';
ob_start();                       // lets modules send CSV downloads / JSON / redirects after the layout started
admin_header($MODULE_TITLE);
require $file;
admin_footer();
ob_end_flush();
