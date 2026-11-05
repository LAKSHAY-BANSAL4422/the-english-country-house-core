<?php
/** Explore Mussoorie — fully editable page (banner + attractions map + attraction cards + concierge CTA) */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
require_once __DIR__.'/install/explore_data.php';
require_once __DIR__.'/includes/seo.php';
/* round 17 (technical SEO): every attraction on the page as a TouristAttraction with its map position and distance from the resort */
$exAttr = q_all("SELECT * FROM attractions WHERE active=1 ORDER BY sort, id");
$exSeo = ['webpage'=>['mainEntity'=>['@type'=>'ItemList', 'name'=>'Places to visit near '.setting('site_name').', Mussoorie', 'numberOfItems'=>count($exAttr),
  'itemListElement'=>array_map(fn($i, $a) => ['@type'=>'ListItem', 'position'=>$i + 1, 'item'=>array_filter([
    '@type'=>'TouristAttraction', 'name'=>$a['name'], 'description'=>seo_text((string)$a['description'], 300) ?: null,
    'image'=>attraction_img($a) !== '' ? seo_abs(attraction_img($a)) : null,
    'geo'=>$a['lat'] !== null ? ['@type'=>'GeoCoordinates', 'latitude'=>(float)$a['lat'], 'longitude'=>(float)$a['lng']] : null,
    'hasMap'=>$a['maps_url'] ?: null,
    'containedInPlace'=>['@type'=>'City', 'name'=>'Mussoorie'],
    'additionalProperty'=>$a['distance_km'] !== '' ? ['@type'=>'PropertyValue', 'name'=>'Distance from the resort', 'value'=>$a['distance_km'].' km'] : null,
  ])], array_keys($exAttr), $exAttr)]]];
render_system_page('explore',
  ['title'=>'Explore Mussoorie — Attractions, Waterfalls & Viewpoints Near '.setting('site_name'),
   'desc'=>'Landour & Char Dukan, Gun Hill, Mall Road, Lal Tibba, Cloud End, George Everest House, Kempty, Bhatta & Jharipani Falls, Company Garden, Mussoorie Lake and more — distances and travel times from '.setting('site_name').', Dhanaulti Road, Mussoorie.',
   'h1'=>'Explore Mussoorie'],
  ['Explore Mussoorie'],
  explore_page_sections(),
  'hero', ['seo'=>$exSeo]);
