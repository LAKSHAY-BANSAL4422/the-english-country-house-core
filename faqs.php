<?php
/** FAQ — fully editable page; FAQ rich-result schema generated automatically */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
$faqs = q_all("SELECT question, answer FROM faqs WHERE active=1 ORDER BY category, sort, id");
render_system_page('faqs',
  ['title'=>'FAQs | '.setting('site_name'), 'desc'=>'Frequently asked questions about staying at '.setting('site_name').' — check-in, pets, dining, refunds and more.', 'h1'=>'FAQs'],
  ['FAQs'],   // round 14: no apostrophe (SEO document)
  [['type'=>'faq_full','visible'=>1,'title'=>'Frequently Asked Questions','btn1_label'=>'Contact Us','btn1_url'=>'contact.php']],
  'hero', ['seo'=>['faqs'=>$faqs]]);   // round 17: the page's FAQPage data is built in includes/seo.php
