<?php
/** Auto-generated sitemap.xml (rewritten from /sitemap.xml via .htaccess)
 *  Round 17 (technical SEO):
 *   - only real, indexable addresses: the built-in pages (Rooms, About …) are no longer listed a second time as /p/rooms etc.,
 *     and /p/booking (a redirect) is gone; Explore Mussoorie is added
 *   - every address carries its last change date (lastmod) and its photos (Google image sitemap)
 *   - addresses use Admin → SEO "Main web address" when it is set
 *   changefreq / priority are left out: Google ignores them. */
require_once __DIR__.'/config.php'; db();
require_once __DIR__.'/includes/seo.php';
require_once __DIR__.'/includes/sections.php';   // amenity_data()
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$pages = [];
foreach (q_all("SELECT slug, page_type, updated_at, banner_image, active FROM pages") as $p) $pages[$p['slug']] = $p;
$mod = fn(string $slug, string $file = '') => max(strtotime($pages[$slug]['updated_at'] ?? '2000-01-01'), $file !== '' ? (int)@filemtime(ROOT_PATH.'/'.$file) : 0);
$img = fn(array $paths) => array_values(array_unique(array_filter($paths, fn($p) => $p !== '' && is_file(ROOT_PATH.'/'.ltrim($p, '/')))));
$on = fn(string $slug) => !isset($pages[$slug]) || (int)$pages[$slug]['active'] === 1;

$rooms = public_rooms();
$urls = [];
$urls[] = ['loc'=>seo_home(), 'mod'=>$mod('home', 'index.php'), 'img'=>$img([rd('hero_poster'), 'assets/img/redesign/welcome-main.jpg', 'assets/img/redesign/welcome-small.jpg'])];
if ($on('about'))     $urls[] = ['loc'=>seo_abs(u('about.php')), 'mod'=>$mod('about'), 'img'=>$img(['assets/img/redesign/about-us.jpg'])];
if ($on('rooms'))     $urls[] = ['loc'=>seo_abs(u('rooms.php')), 'mod'=>$mod('rooms', 'data/rooms.php'), 'img'=>$img(array_column($rooms, 'cover_image'))];
foreach ($rooms as $r)   // round 9: the five rooms in data/rooms.php
    $urls[] = ['loc'=>seo_abs(u('room.php?slug='.$r['slug'])), 'mod'=>(int)@filemtime(ROOT_PATH.'/data/rooms.php'), 'img'=>$img(room_images($r))];
if ($on('amenities')) $urls[] = ['loc'=>seo_abs(u('amenities.php')), 'mod'=>$mod('amenities', 'data/amenities.php'), 'img'=>$img(array_merge(...array_map(fn($a) => json_decode($a['images'], true) ?: [], amenity_data() ?: [['images'=>'[]']])))];
if (setting('menu_enabled', '1') === '1') $urls[] = ['loc'=>seo_abs(u('menu.php')), 'mod'=>(int)@filemtime(ROOT_PATH.'/menu.php'), 'img'=>$img([setting('menu_hero_image') ?: 'assets/img/restaurant.jpg'])];
if ($on('gallery')) {
    $g = array_column(q_all("SELECT image FROM gallery_images WHERE active=1"), 'image');
    foreach (rd_gallery_data() as $x) $g[] = $x['src'];
    $urls[] = ['loc'=>seo_abs(u('gallery.php')), 'mod'=>$mod('gallery', 'data/gallery.php'), 'img'=>$img($g)];
}
if ($on('explore'))   $urls[] = ['loc'=>seo_abs(u('explore.php')), 'mod'=>$mod('explore'), 'img'=>$img(array_map(fn($a) => preg_replace('~^'.preg_quote(rtrim(base_url(''), '/'), '~').'/~', '', attraction_img($a)), q_all("SELECT * FROM attractions WHERE active=1")))];
if ($on('contact'))   $urls[] = ['loc'=>seo_abs(u('contact.php')), 'mod'=>$mod('contact')];
if ($on('faqs'))      $urls[] = ['loc'=>seo_abs(u('faqs.php')), 'mod'=>$mod('faqs')];
$posts = q_all("SELECT p.*, c.slug cat_slug FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.published_at DESC");
$lastPost = $posts ? max(array_map(fn($p) => strtotime($p['published_at'] ?: $p['created_at']), $posts)) : 0;
if ($on('blog'))      $urls[] = ['loc'=>seo_abs(u('blog.php')), 'mod'=>max($mod('blog'), $lastPost)];
foreach (q_all("SELECT id, slug FROM blog_categories WHERE active=1") as $c) {
    $cp = array_filter($posts, fn($p) => (int)$p['category_id'] === (int)$c['id']);
    if ($cp) $urls[] = ['loc'=>seo_abs(u('blog.php?category='.$c['slug'])), 'mod'=>max(array_map(fn($p) => strtotime($p['published_at'] ?: $p['created_at']), $cp))];
}
foreach ($posts as $p)
    $urls[] = ['loc'=>seo_abs(u('post.php?slug='.$p['slug'])), 'mod'=>strtotime($p['published_at'] ?: $p['created_at']), 'img'=>$img([rd_blog_image_path($p)])];
// location, policy and any custom pages; never the built-in ones (they are listed above under their own address)
foreach ($pages as $slug => $p) {
    if ((int)$p['active'] !== 1 || $p['page_type'] === 'system') continue;
    $urls[] = ['loc'=>seo_abs(u('page.php?slug='.$slug)), 'mod'=>strtotime($p['updated_at']), 'img'=>$img([(string)$p['banner_image']])];
}

echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
$seen = [];
foreach ($urls as $u) {
    if (isset($seen[$u['loc']])) continue; $seen[$u['loc']] = 1;
    echo '  <url><loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'.(!empty($u['mod']) ? '<lastmod>'.date('c', (int)$u['mod']).'</lastmod>' : '');
    foreach (array_slice($u['img'] ?? [], 0, 50) as $i) echo '<image:image><image:loc>'.htmlspecialchars(seo_abs($i), ENT_XML1).'</image:loc></image:image>';
    echo "</url>\n";
}
echo "</urlset>\n";
