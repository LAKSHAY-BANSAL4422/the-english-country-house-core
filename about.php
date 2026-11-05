<?php
/** About — banner + intro content + editable sections */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
render_system_page('about',
  ['title'=>'About Us | '.setting('site_name').' Mussoorie', 'h1'=>'About Us'],
  ['About Us'],
  [['type'=>'cta','visible'=>1,'title'=>'Ready For A Hillside Escape?','subtitle'=>'Book Direct — Best Rates','btn1_label'=>'Book Your Stay','btn1_url'=>'booking.php']]);
