<?php
/** Contact — fully editable page (banner + info cards + form/map sections) */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
render_system_page('contact',
  ['title'=>'Contact Us | '.setting('site_name'), 'desc'=>'Reach '.setting('site_name').', Village Kaplani Dhanaulti Road, Mussoorie. Call '.setting('contact_phone').' or write to '.setting('contact_email').'.', 'h1'=>'Contact Us'],
  ['Contact Us'],
  [['type'=>'contact_info','visible'=>1,'title'=>'Get In Touch With Us For Quick Support.'],['type'=>'contact_form','visible'=>1,'title'=>'Your Details']],
  'rooms_banner');
