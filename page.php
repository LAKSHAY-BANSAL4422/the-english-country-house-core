<?php
/** Custom / policy / location pages — banner + intro content + editable sections */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
require_once __DIR__.'/install/page_defaults.php';
$pg = q_row("SELECT * FROM pages WHERE slug=?", [get('slug')]);
if (!$pg || (!(int)$pg['active'] && !current_admin())) { require __DIR__.'/404.php'; exit; }   // round 17: a real 404 page (was a redirect home)
// round 17: the built-in pages (Rooms, About, Contact …) have their own address; /p/rooms etc. showed the same page twice → 301
$sysUrl = ['home'=>'', 'about'=>'about.php', 'rooms'=>'rooms.php', 'amenities'=>'amenities.php', 'contact'=>'contact.php', 'explore'=>'explore.php',
           'faqs'=>'faqs.php', 'blog'=>'blog.php', 'gallery'=>'gallery.php'];
if (isset($sysUrl[$pg['slug']])) { raw_output(); header('Location: '.keep_query($sysUrl[$pg['slug']] === '' ? rtrim(base_url(''), '/').'/' : u($sysUrl[$pg['slug']])), true, 301); exit; }
// REDESIGN (round 4): the "booking" page record only holds the old booking page's banner/SEO; on its own it is an empty
// page, so /p/booking goes to the WhatsApp enquiry form like booking.php does. The record itself is unchanged.
if ($pg['slug'] === 'booking') redirect(keep_query(rtrim(base_url(''), '/').'/?enquire=1', ['slug', 'enquire']));
$PAGE = page_meta($pg, ['title'=>$pg['title'].' | '.setting('site_name')]);
// round 17 (technical SEO): breadcrumb, page type (location pages name their place), banner photo preloaded
$PAGE += ['seo_type'=>in_array($pg['page_type'], ['location', 'policy'], true) ? $pg['page_type'] : 'page', 'slug'=>$pg['slug'],
          'crumbs'=>[$pg['title']], 'modified'=>$pg['updated_at'] ?? ''];
if ((int)($pg['show_banner'] ?? 1) !== 0) $PAGE['lcp'] = banner_image_url($pg);
include __DIR__.'/includes/header.php';
render_banner($pg, [$pg['title']]);
render_page_body($pg, $pg['page_type'] === 'location' ? location_default_sections($pg['title']) : []);
include __DIR__.'/includes/inquiry_modal.php'; include __DIR__.'/includes/footer.php';
