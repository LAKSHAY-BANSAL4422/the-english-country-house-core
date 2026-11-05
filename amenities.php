<?php
/** Amenities — fully editable page (banner + sections) */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
render_system_page('amenities',
  ['title'=>'Amenities & Facilities | '.setting('site_name'), 'desc'=>'Pool, outdoor jacuzzi, sauna, gym, yoga, restaurant, patio, bonfire, pool table, basketball court, trampoline and EV charging at '.setting('site_name').', Mussoorie.', 'h1'=>'Our Amenities'],
  ['Amenities'],
  [['type'=>'amenities','visible'=>1,'title'=>'Enjoy Our Amenities','subtitle'=>'Enjoy Every Moment']]);
