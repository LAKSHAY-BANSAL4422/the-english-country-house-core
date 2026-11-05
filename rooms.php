<?php
/** Rooms — banner, sections (rooms grid, CTA…) all editable in Admin → Pages / Builder or the live editor */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
require_once __DIR__.'/includes/seo.php';
/* round 14 (SEO): "Which Room Is Right for You?" and "Room Questions" after the room cards (before the closing call to action) */
$rqSizes = implode(', ', array_map(fn($r) => $r['name'].' '.preg_replace('/\s*sq\.?\s*ft\.?/i', ' sq ft', $r['room_size']), array_filter(public_rooms(), fn($r) => empty($r['combo']))));   // round 19: the five rooms (the duplex has its own answer)
$GLOBALS['RD_INJECT'] = ['before'=>['cta'], 'sections'=>[
  ['type'=>'room_guide', 'title'=>'Which Room Is Right for You?'],
  ['type'=>'qa', 'title'=>'Room Questions', 'items'=>[
    ['Which rooms are pet friendly?', 'Elizabeth Lodge, Charlotte Tree House 1 and Charlotte Tree House 2, and so the Charlotte Tree House Duplex. Please tell us about your pet when you enquire.'],
    ['Do you have a room for a family or a group of four?', 'Yes. The Charlotte Tree House Duplex is Charlotte Tree House 1 and Charlotte Tree House 2 booked together: 295.91 sq ft, two Queen-bed rooms and two separate washrooms, for four guests.'],
    ['Which rooms have valley views and which have mountain views?', 'Victorian Suite 1, Victorian Suite 2 and Charlotte Tree House 1 face the valley. Elizabeth Lodge faces the mountains. Charlotte Tree House 2 has garden and valley views, with sunsets.'],
    ['How big are the rooms?', $rqSizes.'. Victorian Suite 2 is the largest and Charlotte Tree House 1 the most compact.'],
    ['Do the rooms have bathtubs?', 'Yes. Every one of our five rooms has a bathtub.'],
  ], 'content'=>'More answers on our <a href="'.u('faqs.php').'">FAQs page</a>.'],
]];
/* round 17 (technical SEO): the five rooms as a list Google can read, and the Room Questions as FAQ data */
$rqSeo = ['faqs'=>$GLOBALS['RD_INJECT']['sections'][1]['items'], 'webpage'=>['mainEntity'=>['@type'=>'ItemList', 'numberOfItems'=>count(public_rooms()),
  'itemListElement'=>array_map(fn($i, $r) => ['@type'=>'ListItem', 'position'=>$i + 1, 'url'=>seo_abs(u('room.php?slug='.$r['slug'])), 'name'=>$r['name'],
    'item'=>['@type'=>'HotelRoom', '@id'=>seo_abs(u('room.php?slug='.$r['slug'])).'#room', 'name'=>$r['name'], 'description'=>$r['short_desc'], 'image'=>seo_abs($r['cover_image'])]], array_keys(public_rooms()), public_rooms())]]];
render_system_page('rooms',
  ['title'=>'Luxury Rooms & Suites in Mussoorie | English Country House', 'desc'=>'Five themed luxury rooms in Mussoorie: a four-poster suite, a Victorian suite, a pet-friendly lodge and wooden tree houses with valley and mountain views.', 'h1'=>'Our Rooms'],
  ['Rooms'],
  [['type'=>'rooms','visible'=>1,'title'=>'A Gander At Our Rooms','subtitle'=>'Discover Our Room']],
  'rooms_banner', ['seo'=>$rqSeo]);
