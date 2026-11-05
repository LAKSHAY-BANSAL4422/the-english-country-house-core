<?php
/** Home — 100% built from drag-and-drop sections (Admin → Pages / Builder → Home, or the live editor) */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
require_once __DIR__.'/includes/redesign_content.php';
$home = page_load('home') ?: ['id'=>0,'slug'=>'home','title'=>'Home','sections'=>'[]','content'=>'','active'=>1];
$PAGE = page_meta($home, ['title'=>setting('seo_title'),'desc'=>setting('seo_desc'),'keywords'=>setting('seo_keywords')]);
/* round 17 (technical SEO): the hero photo (video poster) is the first thing seen — preload it; same choice as sec_hero() */
$rdHero = current(array_filter(json_decode($home['sections'] ?? '[]', true) ?: [], fn($x) => ($x['type'] ?? '') === 'hero')) ?: [];
$PAGE['seo_type'] = 'home'; $PAGE['modified'] = $home['updated_at'] ?? '';
$PAGE['lcp'] = !empty($rdHero['image']) ? $rdHero['image'] : (setting('img_hero') !== '' ? site_img('hero') : rd('hero_poster'));
include __DIR__.'/includes/header.php';
/* REDESIGN: two template-added sections, inserted before the About tabs / guest reviews (see render_sections):
   1. the Welcome section — built from the home page's CTA section ("Welcome To The English Country House — Book Your
      Stay Today", edited as before in Admin → Pages → Home), with fallbacks in includes/redesign_content.php;
   2. the About / Sustainability / FAQ tabs, until an admin places that section in the builder (then the builder controls it). */
$rdHomeSecs = json_decode($home['sections'] ?? '[]', true) ?: [];
$rdCta = current(array_filter($rdHomeSecs, fn($x) => ($x['type'] ?? '') === 'cta')) ?: [];
$rdInject = [array_merge($rdCta, ['type'=>'welcome', 'visible'=>1])];
if (!in_array('about_tabs', array_column($rdHomeSecs, 'type'), true))
  $rdInject[] = ['type'=>'about_tabs','visible'=>1,'title'=>'About us','content'=>'','image'=>''];
$GLOBALS['RD_INJECT'] = ['before'=>['about_tabs','reviews','blog','cta'], 'sections'=>$rdInject];
render_page_body($home, [
  ['type'=>'hero','visible'=>1,'title'=>'Discover Tranquility At '.rd('hero_brand'),'subtitle'=>setting('site_tagline'),'content'=>setting('seo_desc'),'btn1_label'=>'Book Your Stay','btn1_url'=>'booking.php'],
  ['type'=>'booking_bar','visible'=>1],
  ['type'=>'rooms','visible'=>1,'title'=>'A Gander At Our Rooms','subtitle'=>'Discover Our Rooms'],
  ['type'=>'facilities','visible'=>1,'title'=>'Our Hotel Facilities','subtitle'=>'Our Facilities'],
]);
include __DIR__.'/includes/inquiry_modal.php'; include __DIR__.'/includes/footer.php';
