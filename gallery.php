<?php
/** Gallery — fully editable page (banner + gallery section with category filter & lightbox) */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
require_once __DIR__.'/includes/seo.php';
/* round 17 (technical SEO): the gallery photos with their descriptions, so they can show in Google Images */
$gaPhotos = [];
foreach (q_all("SELECT image src, title FROM gallery_images WHERE active=1 ORDER BY sort, id DESC") as $g) $gaPhotos[$g['src']] = $g['title'];
foreach (rd_gallery_data() as $g) $gaPhotos[$g['src']] ??= ($g['alt'] ?: $g['caption']);
$gaPhotos = array_filter($gaPhotos, fn($k) => is_file(ROOT_PATH.'/'.ltrim($k, '/')), ARRAY_FILTER_USE_KEY);
$gaSeo = ['webpage'=>['associatedMedia'=>array_map(fn($src, $cap) => seo_image_obj($src, (string)$cap), array_keys($gaPhotos), $gaPhotos)]];
render_system_page('gallery',
  ['title'=>'Gallery | '.setting('site_name'), 'desc'=>'A photo tour of '.setting('site_name').' — rooms, dining, amenities and the Mussoorie views.', 'h1'=>'Gallery'],
  ['Gallery'],
  [['type'=>'gallery_full','visible'=>1]], 'hero', ['seo'=>$gaSeo]);
