<?php
/** Round 17: "page not found" — a real 404 status (not a redirect), noindex, with links back into the site.
 *  Used by .htaccess (ErrorDocument 404) and by room.php / post.php / page.php / blog.php for unknown addresses. */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
http_response_code(404);
$PAGE = ['title'=>'Page Not Found | '.setting('site_name'), 'desc'=>'This page could not be found. Explore our rooms, amenities and places to visit near The English Country House, Mussoorie.',
         'keywords'=>'', 'noindex'=>true];
include __DIR__.'/includes/header.php';
render_banner(['id'=>0, 'slug'=>'404', 'title'=>'Page Not Found', 'banner_title'=>'Page Not Found', 'banner_subtitle'=>'Sorry, this page has moved or no longer exists', 'banner_image'=>''], ['Page not found']);
$nfLinks = [['Our Rooms', u('rooms.php')], ['Amenities', u('amenities.php')], ['Explore Mussoorie', u('explore.php')], ['Gallery', u('gallery.php')], ['FAQs', u('faqs.php')], ['Contact Us', u('contact.php')]];
foreach (public_rooms() as $r) $nfRooms[] = [$r['name'], u('room.php?slug='.$r['slug'])];
?>
<section class="page-intro">
  <div class="container rich" style="max-width:900px;color:#4a535f;text-align:center">
    <p>The address you followed may be old or mistyped. Here are the most visited pages of our boutique resort on the Dhanaulti Road, Mussoorie:</p>
    <p><?php foreach ($nfLinks as $i => $l): ?><?= $i ? ' · ' : '' ?><a href="<?= e($l[1]) ?>"><?= e($l[0]) ?></a><?php endforeach; ?></p>
    <?php if (!empty($nfRooms)): ?><p><b>Our rooms:</b> <?php foreach ($nfRooms as $i => $l): ?><?= $i ? ' · ' : '' ?><a href="<?= e($l[1]) ?>"><?= e($l[0]) ?></a><?php endforeach; ?></p><?php endif; ?>
    <p style="margin-top:24px"><a class="rd-btn rd-btn--dark" href="<?= base_url('') ?>"><span>Back to the home page</span></a></p>
  </div>
</section>
<?php include __DIR__.'/includes/footer.php';
