<?php
/** Room detail — cover + full photo gallery (lightbox), video, highlights, amenities, booking card */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/sections.php';
$room = public_room((string)get('slug'));   // round 9: the five rooms in data/rooms.php (falls back to the rooms table without it)
if ($room && isset($room['redirect'])) { http_response_code(301); redirect(keep_query(u('room.php?slug='.$room['redirect']))); }
if (!$room || (!(int)$room['active'] && !current_admin())) { require __DIR__.'/404.php'; exit; }   // round 17: a real 404 page, not a redirect (Google calls that a "soft 404")
$imgs = room_images($room);
$cover = room_cover($room);
// cover first, then the rest in order
if ($cover !== '') $imgs = array_merge([$cover], array_values(array_filter($imgs, fn($i) => $i !== $cover)));
$amenIds = json_decode($room['amenity_ids'] ?: '[]', true) ?: [];
$amens = $amenIds ? q_all("SELECT * FROM amenities WHERE id IN (".implode(',', array_map('intval',$amenIds)).") AND active=1") : [];
$highlights = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($room['highlights'] ?? '')))));
$video = video_embed((string)($room['video_url'] ?? ''), (string)($room['video_file'] ?? ''), $cover);
$ph = base_url('assets/img/placeholder.jpg');
require_once __DIR__.'/includes/seo.php';
// round 17: HotelRoom data (size, bed, occupancy, view, amenities, photos) + room questions + breadcrumb, in includes/seo.php.
// round 5: no 'offers' price in structured data (room prices are not public)
$PAGE = ['title'=>$room['seo_title'] ?: $room['name'].' | '.setting('site_name'),'desc'=>$room['seo_desc'] ?: $room['short_desc'],
  'keywords'=>$room['seo_keywords'],'og_image'=>$imgs[0] ?? '', 'og_image_alt'=>$imgs ? room_photo_alt($room, $imgs[0]) : '',
  'seo_type'=>'room', 'crumbs'=>[['Rooms', u('rooms.php')], $room['name']], 'lcp'=>$imgs[0] ?? '', 'faqs'=>$room['faqs'] ?? [],
  'nodes'=>[seo_room_node($room)], 'main_entity'=>seo_abs(u('room.php?slug='.$room['slug'])).'#room',
  'modified'=>!empty($room['from_file']) ? @filemtime(ROOT_PATH.'/data/rooms.php') : ''];
include __DIR__.'/includes/header.php';
if (empty($room['from_file']) && ($adm = current_admin()) && admin_can($adm, 'rooms')): ?>
<a class="le-bar le-b le-start" style="position:fixed;left:50%;bottom:14px;transform:translateX(-50%);z-index:99990;background:#c9974b;color:#fff;border-radius:40px;padding:10px 18px;font:600 14px sans-serif;text-decoration:none;box-shadow:0 14px 40px rgba(0,0,0,.35)" href="<?= base_url('admin/index.php?m=rooms&edit='.(int)$room['id']) ?>"><i class="fa-solid fa-pen"></i>&nbsp; Edit this room (photos, video, text)</a>
<?php endif; ?>
<?php /* REDESIGN (round 3): cinematic video header, same as the other main pages (markup only — see includes/redesign_content.php) */
rd_cine_banner($imgs ? base_url($imgs[0]) : site_img('rooms_banner'), $room['name'], ($room['subline'] ?? '') !== '' ? $room['subline'] : implode(' · ', array_filter([$room['bed_type'] ?? '', $room['view_type'] ?? ''])), [['Rooms', u('rooms.php')], $room['name']], '', 'Our Rooms', false);   // round 14: keyword subline inside the H1 ?>

<section>
  <div class="container">
    <?php $n = count($imgs); if ($n): ?>
    <div class="rs reveal" data-rs>
      <div class="rs-stage">
        <?php foreach ($imgs as $k => $im): ?><img src="<?= base_url($im) ?>" alt="<?= e(room_photo_alt($room, $im)) ?>" loading="<?= $k < 2 ? 'eager' : 'lazy' ?>" class="<?= $k === 0 ? 'on' : '' ?>" data-full="<?= e(base_url($im)) ?>"><?php endforeach; ?>
        <?php if ($n > 1): ?>
        <button type="button" class="rs-nav rs-prev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="rs-nav rs-next" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
        <span class="rs-count"><b>1</b> / <?= $n ?></span>
        <?php endif; ?>
        <button type="button" class="rs-full" aria-label="View full screen" title="Full screen"><i class="fa-solid fa-expand"></i></button>
      </div>
      <?php if ($n > 1): ?>
      <div class="rs-thumbs">
        <?php foreach ($imgs as $k => $im): ?><img src="<?= base_url(room_thumb($im)) ?>" alt="" loading="lazy" data-k="<?= $k ?>" class="<?= $k === 0 ? 'on' : '' ?>"><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?><img src="<?= $ph ?>" alt="" style="border-radius:14px"><?php endif; ?>
    <?php lightbox_once(); ?>

    <div class="grid-2 mt-3">
      <div class="reveal-left">
        <span class="badge <?= $room['pets_allowed']?'green':'red' ?>"><?= $room['pets_allowed'] ? '🐾 Pet Friendly' : 'Pets not allowed' ?></span>
        <h2 class="mt-2"><?= e($room['name']) ?></h2>
        <div class="rc-meta mt-1">
          <span><i class="fa-solid fa-user-group"></i> Up to <?= $room['max_adults'] ?> adults<?= (int)$room['max_children'] > 0 ? ' + '.(int)$room['max_children'].' children' : '' ?></span>
          <?php if ($room['bed_type']): ?><span><i class="fa-solid fa-bed"></i> <?= e($room['bed_type']) ?></span><?php endif; ?>
          <?php if ($room['room_size']): ?><span><i class="fa-solid fa-ruler-combined"></i> <?= e($room['room_size']) ?></span><?php endif; ?>
          <?php if (!empty($room['baths'])): /* round 19 */ ?><span><i class="fa-solid fa-bath"></i> <?= (int)$room['baths'] ?> washrooms</span><?php endif; ?>
          <?php if ($room['view_type']): ?><span><i class="fa-solid fa-mountain-sun"></i> <?= e($room['view_type']) ?></span><?php endif; ?>
        </div>
        <div class="mt-2 rich rd-room-copy" style="color:#555e6a"><?= rd_copy_links((string)$room['description']) ?></div>
        <?php if ($highlights): ?>
        <h3 class="mt-3">Room Highlights</h3>
        <ul class="room-highlights"><?php foreach ($highlights as $h): ?><li><i class="fa-solid fa-check"></i> <?= e($h) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($amens): ?>
        <h3 class="mt-3">Room and Resort Amenities</h3>
        <div class="amen-pills">
          <?php foreach ($amens as $a): ?><span><i class="fa-solid <?= e($a['icon']) ?>"></i><?= e($a['name']) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php /* REDESIGN (round 4): no video on room detail pages — the Room Tour embed is not printed.
                 The room's video_url / video_file stay in the database and admin untouched. */
        if (false && $video): ?>
        <h3 class="mt-3"><i class="fa-solid fa-play" style="color:var(--c-primary)"></i> Room Tour</h3>
        <div class="mt-1"><?= $video ?></div>
        <?php endif; ?>
      </div>
      <div class="reveal-right">
        <div class="card rb-card" id="book">
          <?php /* round 5: no public room price — rates are sent on enquiry */ ?>
          <p class="rb-from">Rates &amp; availability</p>
          <p class="rb-price rd-enq-head">Enquire for this room</p>
          <?php /* REDESIGN (round 4): online booking is replaced by the WhatsApp enquiry form. The old date/guest booking
                   form below is kept in the file but not printed (main.js skips it when it is absent). */ ?>
          <div class="rd-enq-box">
            <p>Tell us your dates and we'll reply on WhatsApp with availability and the exact price for <?= e($room['name']) ?>.</p>
            <a class="rd-btn rd-btn--dark rd-btn--block" <?= rd_enquiry_attrs((int)$room['id']) ?>><?= e(rd('enquiry_cta')) ?></a>
          </div>
          <?php if (false): ?>
          <!-- booking form: dates + guests → live price & availability → Book Now goes to the booking page with everything filled in -->
          <form class="rb-form" action="<?= u('booking.php') ?>" method="get" data-room-book data-room="<?= (int)$room['id'] ?>">
            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
            <div><label for="rbIn">Check-in</label><input type="date" id="rbIn" name="checkin" required min="<?= date('Y-m-d') ?>"></div>
            <div><label for="rbOut">Check-out</label><input type="date" id="rbOut" name="checkout" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
            <div><label for="rbAd">Adults</label><select id="rbAd" name="adults"><?php for ($i = 1; $i <= max(1, (int)$room['max_adults']); $i++): ?><option <?= $i === min(2, max(1, (int)$room['max_adults'])) ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div><label for="rbCh">Children <small style="text-transform:none;letter-spacing:0">(5–12)</small></label><select id="rbCh" name="children"><?php for ($i = 0; $i <= max(0, (int)$room['max_children']); $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
            <div class="rb-quote" aria-live="polite">Select your dates to see the exact price — weekend, seasonal &amp; occasion rates apply automatically.</div>
            <button class="btn btn-primary btn-lg" type="submit" disabled><i class="fa-solid fa-calendar-check"></i> <span>Select dates</span></button>
          </form>
          <?php endif; ?>
          <?php /* round 10: "Free cancellation up to N days before check-in" note removed */ ?><?php /* round 5: extra adult / child nightly rates removed (no public room prices) */ ?>
          <a class="btn mt-2" style="width:100%;text-align:center;background:#25d366;color:#fff" href="<?= e(wa_link('', 'Hi! I am interested in '.$room['name'].' at '.setting('site_name'))) ?>" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp Us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php /* round 14 (SEO): room questions and the other rooms, with internal links */
if (!empty($room['faqs'])) sec_qa(['title'=>'Questions About '.$room['name'], 'items'=>$room['faqs']]);
rd_more_rooms($room); ?>

<?php include __DIR__.'/includes/inquiry_modal.php'; include __DIR__.'/includes/footer.php'; ?>
