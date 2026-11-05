<?php
/**
 * Front-end renderer for drag-and-drop page sections.
 * Usage: $sections = json_decode($page['sections'],true); include this; render_sections($sections);
 */

/**
 * Section type registry — drives BOTH the back-end Section Builder and the front-end live editor.
 * fields: title, subtitle, content (visual editor), raw (plain textarea), image, video, buttons, items (repeatable cards), colors
 * opts:   extra per-type options  key => [label, kind(select|number|checkbox|text), choices|null, default]
 */
function section_types(): array {
    static $T = null;
    if ($T !== null) return $T;
    $T = [
      'hero' => ['label'=>'Hero Banner','icon'=>'fa-image','desc'=>'Full-width photo OR video background with headline, tagline, text & buttons',
                 'fields'=>['title','subtitle','content','image','video','buttons','colors'],
                 'opts'=>['height'=>['Height','select',['full'=>'Full screen','medium'=>'Medium','short'=>'Short'],'full'],
                          'align'=>['Text align','select',['center'=>'Center','left'=>'Left'],'center'],
                          'valign'=>['Text position (vertical)','select',['middle'=>'Middle','bottom'=>'Bottom'],'middle'],
                          'title_size'=>['Heading size','select',['xs'=>'Compact (≈36 px)','sm'=>'Small (≈42 px)','md'=>'Medium (≈48 px)','lg'=>'Large (≈64 px)','xl'=>'Extra large (≈76 px)'],'lg'],
                          'text_size'=>['Text size','select',['sm'=>'Small (14.5 px)','md'=>'Medium (16 px)','lg'=>'Large (17 px)','xl'=>'Extra large (19 px)'],'lg'],
                          'text_width'=>['Text block width','select',['narrow'=>'Narrow (560 px)','medium'=>'Medium (720 px)','wide'=>'Wide (900 px)'],'medium']]],
      'booking_bar' => ['label'=>'Quick Booking Bar','icon'=>'fa-calendar-check','desc'=>'Room / dates / guests search bar','fields'=>['colors'],'opts'=>[]],
      'rooms' => ['label'=>'Rooms Grid','icon'=>'fa-bed','desc'=>'All active rooms with photo & buttons (prices are not shown — guests enquire)',
                  'fields'=>['title','subtitle','colors'],
                  'opts'=>['columns'=>['Rooms per row (desktop)','select',['2'=>'2','3'=>'3','4'=>'4'],'3'],'slide_secs'=>['Auto-slide photos every (seconds, 0 = only on hover)','number',null,4],'limit'=>['Max rooms (0 = all)','number',null,0],'show_book'=>['Show Book button','checkbox',null,0],'show_explore'=>['Show Explore (room page) button','checkbox',null,0],'show_enquiry'=>['Show Enquiry button','checkbox',null,1],'show_details'=>['Show bed / view / size','checkbox',null,1]]],
      'amenities' => ['label'=>'Amenities List','icon'=>'fa-spa','desc'=>'Every amenity — photo cards or icon cards',
                  'fields'=>['title','subtitle','colors'],'opts'=>['layout'=>['Layout','select',['auto'=>'Photo if available, else icon','photos'=>'Photos only','icons'=>'Icon cards only'],'auto']]],
      'amenities_gallery' => ['label'=>'Amenities Showcase','icon'=>'fa-images','desc'=>'Latest amenity photos in a 3-column grid; click opens a sliding popup with Book button',
                  'fields'=>['title','subtitle','buttons','colors'],'opts'=>['limit'=>['Tiles on the page','number',null,6],'columns'=>['Columns','select',['3'=>'3','2'=>'2','4'=>'4'],'3']]],
      'facilities' => ['label'=>'Facilities Icons','icon'=>'fa-icons','desc'=>'Icon + name grid of facilities','fields'=>['title','subtitle','colors'],'opts'=>['limit'=>['Max items','number',null,16]]],
      'reviews' => ['label'=>'Guest Reviews','icon'=>'fa-star','desc'=>'Review cards slider','fields'=>['title','subtitle','colors'],'opts'=>['limit'=>['Max reviews','number',null,10]]],
      'blog' => ['label'=>'Latest Blog','icon'=>'fa-newspaper','desc'=>'Newest blog posts (3 cards)','fields'=>['title','subtitle','colors'],'opts'=>['limit'=>['Posts','number',null,3]]],
      'blog_list' => ['label'=>'Blog — Full List','icon'=>'fa-list','desc'=>'All posts with category filter & pagination','fields'=>['title','subtitle','colors'],'opts'=>['per_page'=>['Posts per page','number',null,9]]],
      'faq' => ['label'=>'FAQ Accordion','icon'=>'fa-circle-question','desc'=>'Top FAQs accordion','fields'=>['title','subtitle','colors'],'opts'=>['limit'=>['Max questions','number',null,8]]],
      'faq_full' => ['label'=>'FAQ — All, by Category','icon'=>'fa-circle-question','desc'=>'Every FAQ grouped by category + help buttons','fields'=>['title','subtitle','buttons','colors'],'opts'=>[]],
      'cta' => ['label'=>'Call-To-Action Band','icon'=>'fa-bullhorn','desc'=>'Photo band with headline & buttons','fields'=>['title','subtitle','content','image','buttons','colors'],'opts'=>[]],
      'text' => ['label'=>'Rich Text','icon'=>'fa-align-left','desc'=>'Heading + visual-editor content','fields'=>['title','subtitle','content','buttons','colors'],
                 'opts'=>['width'=>['Width','select',['narrow'=>'Narrow (reading)','wide'=>'Wide'],'narrow'],'align'=>['Align','select',['left'=>'Left','center'=>'Center'],'left']]],
      'image_text' => ['label'=>'Image + Text','icon'=>'fa-table-columns','desc'=>'Photo beside heading, text & buttons','fields'=>['title','subtitle','content','image','buttons','colors'],
                 'opts'=>['side'=>['Image side','select',['right'=>'Right','left'=>'Left'],'right']]],
      'cards' => ['label'=>'Feature Cards','icon'=>'fa-table-cells-large','desc'=>'Icon / photo cards — vision, mission, why choose us…','fields'=>['title','subtitle','items','colors'],
                 'opts'=>['columns'=>['Columns','select',['auto'=>'Auto','2'=>'2','3'=>'3','4'=>'4'],'auto']]],
      'gallery' => ['label'=>'Image Gallery','icon'=>'fa-images','desc'=>'Photos from the Gallery manager (or a category)','fields'=>['title','subtitle','raw','image','colors'],
                 'opts'=>['limit'=>['Max photos','number',null,12],'link'=>['Show “View full gallery” button','checkbox',null,1]]],
      'gallery_full' => ['label'=>'Gallery — Full Page','icon'=>'fa-images','desc'=>'All gallery photos with category filter & lightbox','fields'=>['title','subtitle','colors'],'opts'=>[]],
      'video' => ['label'=>'Video','icon'=>'fa-video','desc'=>'YouTube / Vimeo link or uploaded MP4, with text','fields'=>['title','subtitle','content','video','image','colors'],
                 'opts'=>['layout'=>['Layout','select',['center'=>'Video centered','split'=>'Video beside text'],'center']]],
      'contact_info' => ['label'=>'Contact Info Cards','icon'=>'fa-address-card','desc'=>'Address, phone & email cards from Settings','fields'=>['title','subtitle','colors'],'opts'=>[]],
      'contact_form' => ['label'=>'Contact Form + Map','icon'=>'fa-envelope','desc'=>'Form-builder form beside Google map','fields'=>['title','subtitle','content','colors'],
                 'opts'=>['form_id'=>['Form','form',null,0],'show_map'=>['Show map','checkbox',null,1]]],
      'map' => ['label'=>'Map','icon'=>'fa-map-location-dot','desc'=>'Google map embed from Settings','fields'=>['title','subtitle','colors'],'opts'=>['height'=>['Height (px)','number',null,420]]],
      'menu_preview' => ['label'=>'Restaurant Highlights','icon'=>'fa-utensils','desc'=>'Bestseller dishes + View Menu button','fields'=>['title','subtitle','content','colors'],'opts'=>['limit'=>['Dishes','number',null,6]]],
      'attractions_map' => ['label'=>'Attractions Map','icon'=>'fa-map-location-dot','desc'=>'Interactive map: the house + nearby attractions with a clickable list (Explore Mussoorie)','fields'=>['title','subtitle','content','colors'],
                 'opts'=>['height'=>['Map height (px)','number',null,460],'limit'=>['Attractions on the map (0 = all)','number',null,0]]],   // round 11: was 12, which hid the 13th and 14th places
      'attractions' => ['label'=>'Attractions Cards','icon'=>'fa-mountain-sun','desc'=>'Nearby sightseeing spots with distance & travel time — numbered photo cards, duo, compact strip or list','fields'=>['title','subtitle','content','buttons','colors'],
                 'opts'=>['layout'=>['Layout','select',['featured'=>'Numbered photo cards (2 per row)','duo'=>'Two big photos + text (e.g. waterfalls)','strip'=>'Compact strip — 4 per row with distance & time','list'=>'Simple list / table'],'featured'],
                          'category'=>['Show','select',['all'=>'All attractions','featured'=>'Featured only','no_waterfall'=>'Everything except waterfalls','viewpoint'=>'Viewpoints','historic'=>'Historic sites','waterfall'=>'Waterfalls','nature'=>'Nature & wildlife','garden'=>'Gardens','lake'=>'Lakes','market'=>'Markets & cafés','other'=>'Other'],'all'],
                          'limit'=>['Max items (0 = all)','number',null,8]]],
      // REDESIGN: presentation-only section (stored in the same pages.sections JSON — no schema change)
      'about_tabs' => ['label'=>'About / Sustainability / FAQ Tabs','icon'=>'fa-folder-open','desc'=>'Pill tabs: About text (from the About page), Sustainability text (this section) and FAQs',
                 'fields'=>['title','content','image','colors'],'opts'=>['faq_limit'=>['FAQ questions','number',null,6]]],
      'html' => ['label'=>'Custom HTML','icon'=>'fa-code','desc'=>'Your own HTML / embed code','fields'=>['raw','colors'],'opts'=>[]],
    ];
    return $T;
}
function sec_opt(array $s, string $key) {
    $def = section_types()[$s['type'] ?? 'text']['opts'][$key][3] ?? null;
    $v = $s['opts'][$key] ?? null;
    return ($v === null || $v === '') ? $def : $v;
}
function sec_wrap_attrs(array $s, int $i): string {
    $style = '';
    if (!empty($s['bg'])) $style .= 'background:'.e($s['bg']).';';
    if (!empty($s['tx'])) $style .= 'color:'.e($s['tx']).';';
    return 'class="page-section sec-'.e($s['type'] ?? 'text').'" id="sec-'.$i.'" data-sec="'.$i.'" data-type="'.e($s['type'] ?? 'text').'"'.($style ? ' style="'.$style.'"' : '');
}
function render_sections(array $sections, bool $editable = false): void {
    $rdInject = $GLOBALS['RD_INJECT'] ?? null;   // REDESIGN: optional extra section set by a page template (see index.php)
    foreach ($sections as $i => $s) {
        if ($rdInject && in_array($s['type'] ?? '', $rdInject['before'], true)) { rd_render_injected($rdInject); $rdInject = null; }
        if (empty($s['visible']) && !$editable) continue;
        echo '<div '.sec_wrap_attrs($s, (int)$i).(empty($s['visible']) ? ' data-hidden="1"' : '').'>';
        $fn = 'sec_'.preg_replace('/[^a-z_]/','', $s['type'] ?? 'text');
        if (function_exists($fn)) $fn($s);
        else echo '<section><div class="container"><p>Unknown section type.</p></div></section>';
        echo '</div>';
    }
    // nothing to insert before? add it at the end — but never during the separate "hero only" pass
    if ($rdInject && array_filter($sections, fn($x) => ($x['type'] ?? '') !== 'hero')) rd_render_injected($rdInject);
}
/** REDESIGN: render a template-injected section. No data-sec attribute, so the live editor (which indexes
 *  .page-section[data-sec]) ignores it and saved section positions stay correct. */
function rd_render_injected(array &$inj): void {
    if (!empty($GLOBALS['RD_INJECT_DONE'])) return;
    $GLOBALS['RD_INJECT_DONE'] = true;
    foreach ($inj['sections'] ?? [$inj['section']] as $s) {   // one section, or several in order
        echo '<div class="page-section sec-'.e($s['type']).'" data-rd-injected="1">';
        $fn = 'sec_'.preg_replace('/[^a-z_]/', '', $s['type']);
        if (function_exists($fn)) $fn($s);
        echo '</div>';
    }
}
/** Embeddable video HTML for a YouTube / Vimeo URL or an uploaded file */
function video_embed(string $url, string $file = '', string $poster = ''): string {
    if ($file !== '') return '<video controls preload="metadata" playsinline '.($poster ? 'poster="'.e(base_url($poster)).'"' : '').' style="width:100%;border-radius:14px;background:#000"><source src="'.e(base_url($file)).'"></video>';
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m))
        return '<div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/'.e($m[1]).'?rel=0" title="Video" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m))
        return '<div class="video-frame"><iframe src="https://player.vimeo.com/video/'.e($m[1]).'" title="Video" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url))
        return '<video controls preload="metadata" playsinline style="width:100%;border-radius:14px;background:#000"><source src="'.e($url).'"></video>';
    return '<p><a class="btn btn-outline" href="'.e($url).'" target="_blank" rel="noopener"><i class="fa-solid fa-play"></i> Watch video</a></p>';
}
function sec_head(array $s): void {
    if (!empty($s['subtitle'])) echo '<p class="section-tag reveal">'.e($s['subtitle']).'</p>';
    if (!empty($s['title'])) echo '<h2 class="section-title reveal">'.e($s['title']).'</h2>';
}
function sec_btns(array $s, string $extra=''): string {
    $out = '';   // REDESIGN (round 4): buttons to the booking page open the WhatsApp enquiry form instead (rd_cms_link)
    if (!empty($s['btn1_label'])) { [$l, $a] = rd_cms_link((string)($s['btn1_url'] ?? '#'), $s['btn1_label']); $out .= '<a class="btn btn-primary btn-lg" '.$a.'>'.e($l).'</a> '; }
    if (!empty($s['btn2_label'])) { [$l, $a] = rd_cms_link((string)($s['btn2_url'] ?? '#'), $s['btn2_label']); $out .= '<a class="btn btn-outline btn-lg" style="'.$extra.'" '.$a.'>'.e($l).'</a>'; }
    return $out;
}

/* ---------- hero (REDESIGN: full-bleed video/photo, centred H1 + subtitle strip + outline CTA) ---------- */
function sec_hero(array $s): void {
    // background priority: CMS section photo → Settings "hero" photo (if changed from the stock placeholder) → redesign poster
    $poster = !empty($s['image']) ? base_url($s['image']) : (setting('img_hero') !== '' ? site_img('hero') : base_url(rd('hero_poster')));
    $vFile = (string)($s['video_file'] ?? ''); $vUrl = trim((string)($s['video_url'] ?? ''));
    $yt = preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $vUrl, $m) ? $m[1] : '';
    $vimeo = preg_match('~vimeo\.com/(?:video/)?(\d+)~', $vUrl, $m) ? $m[1] : '';
    $direct = $vFile !== '' ? base_url($vFile) : (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $vUrl) ? $vUrl : '');
    // REDESIGN: a file dropped at /assets/video/hero.mp4 always wins (that is the documented way to set the hero video);
    // without it the CMS video (upload / YouTube / Vimeo) is used as before. ?v=filemtime so a replaced file is never served stale.
    $slotFile = ROOT_PATH.'/'.rd('hero_video');
    $slot = is_file($slotFile) ? base_url(rd('hero_video')).'?v='.filemtime($slotFile) : '';
    if ($slot !== '') { $yt = $vimeo = ''; $direct = ''; }
    $title = rd_hero_brand(trim((string)($s['title'] ?? '')) !== '' ? $s['title'] : rd('hero_title'));
    $sub = trim((string)($s['subtitle'] ?? '')) !== '' ? $s['subtitle'] : rd('hero_subtitle');
    $cta = !empty($s['btn1_label']) ? rd_cms_link((string)($s['btn1_url'] ?? 'rooms.php'), $s['btn1_label']) : rd_cms_link(rd('hero_cta_url'), rd('hero_cta'));   // round 4: booking URL → enquiry form
    $h = sec_opt($s, 'height'); ?>
    <div class="rd-hero rd-hero--<?= e($h) ?>">
      <div class="rd-hero__media" style="background-image:url('<?= e($poster) ?>')">
        <?php if ($yt !== ''): ?>
          <div class="hero-video hero-yt" data-yt="<?= e($yt) ?>" data-poster="<?= e($poster) ?>"><div class="hero-yt-player"></div></div>
        <?php elseif ($vimeo !== ''): ?>
          <div class="hero-video hero-yt playing"><iframe src="https://player.vimeo.com/video/<?= e($vimeo) ?>?background=1&autoplay=1&muted=1&loop=1" title="Background video" allow="autoplay" tabindex="-1" loading="lazy"></iframe></div>
        <?php else: ?>
          <!-- REPLACE HERO VIDEO HERE: upload /assets/video/hero.mp4 (or set a video on the hero section in the CMS) -->
          <video class="hero-video rd-hero__video" autoplay muted loop playsinline preload="auto" poster="<?= e($poster) ?>" disablepictureinpicture aria-hidden="true" tabindex="-1">
            <?php if ($direct !== '' || $slot !== ''): ?><?= rd_hero_sources($slot ?: $direct) ?><?php endif; /* round 17: smaller video on phones */ ?>
          </video>
        <?php endif; ?>
      </div>
      <?php if ($direct !== '' || $slot !== ''): ?><button type="button" class="rd-hero__vbtn" aria-label="Pause background video" aria-pressed="false"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path class="i-pause" d="M7 5h3v14H7zM14 5h3v14h-3z" fill="currentColor"/><path class="i-play" d="M8 5v14l11-7z" fill="currentColor"/></svg></button><?php endif; ?>
      <div class="rd-hero__content">
        <!-- REPLACE CONTENT: hero title / subtitle / button come from the CMS hero section, with fallbacks in includes/redesign_content.php -->
        <?php /* round 14: title and subline in one H1 (same look) so the home heading names the resort and Mussoorie */ ?>
        <h1 class="rd-h1"><span class="rd-hero__title"><?= e($title) ?></span><?php if ($sub !== ''): ?> <span class="rd-hero__sub"><span><?= e($sub) ?></span></span><?php endif; ?></h1>
        <div class="rd-hero__actions">
          <a class="rd-btn rd-btn--ghost" <?= $cta[1] ?>><span><?= e($cta[0]) ?></span><?= rd_icon('arrow') ?></a>
          <?php if (!empty($s['btn2_label'])): [$l2, $a2] = rd_cms_link((string)($s['btn2_url'] ?? '#'), $s['btn2_label']); ?><a class="rd-btn rd-btn--ghost" <?= $a2 ?>><span><?= e($l2) ?></span><?= rd_icon('arrow') ?></a><?php endif; ?>
        </div>
      </div>
    </div>
<?php }

/** REDESIGN: centred section heading with optional one-line subtitle and a right-aligned "View all" link */
function rd_sechead(string $title, string $sub = '', string $link = '', string $linkLabel = 'View all'): void { ?>
    <div class="rd-sechead<?= $link ? ' rd-sechead--link' : '' ?> reveal">
      <h2 class="rd-sechead__title"><?= e($title) ?></h2>
      <?php if ($sub !== ''): ?><p class="rd-sechead__sub"><?= e($sub) ?></p><?php endif; ?>
      <?php if ($link): ?><a class="rd-sechead__link" href="<?= e($link) ?>"><?= e($linkLabel) ?></a><?php endif; ?>
    </div>
<?php }


/* ---------- quick booking bar ---------- */
function sec_booking_bar(array $s): void {
    // REDESIGN (round 4): online booking is replaced by the WhatsApp enquiry form, so this date bar (which submits to
    // booking.php) is not printed even if switched on in the CMS. The markup below is kept unchanged for reference.
    if (function_exists('live_edit_mode') && live_edit_mode()) echo '<section><div class="container"><div class="alert" style="text-align:center"><b>Quick booking bar</b> — not shown: bookings now go through the Enquire Now (WhatsApp) form.</div></div></section>';
    return;
    $rooms = q_all("SELECT id,name FROM rooms WHERE active=1 ORDER BY sort,id"); ?>
    <div class="container" id="quickbook">
      <form class="booking-bar" action="<?= u('booking.php') ?>" method="get" data-datepair>
        <div class="bb-grid">
          <div class="bb-field"><label>Room</label>
            <select name="room_id"><option value="">Any room</option>
              <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="bb-field"><label>Check-in</label><input type="date" name="checkin" data-min-today required></div>
          <div class="bb-field"><label>Check-out</label><input type="date" name="checkout" data-min-today required></div>
          <div class="bb-field"><label>Adults</label><select name="adults"><?php for($i=1;$i<=6;$i++): ?><option <?= $i==2?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
          <div class="bb-field"><label>Children</label><select name="children"><?php for($i=0;$i<=4;$i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
          <button class="btn btn-primary" type="submit">Check Rates</button>
        </div>
      </form>
    </div>
<?php }

/* ---------- rooms grid (REDESIGN: bordered cards + hover slideshow; query and loop unchanged) ---------- */
function sec_rooms(array $s): void {
    $lim = (int)sec_opt($s, 'limit');
    $rooms = public_rooms(); if ($lim > 0) $rooms = array_slice($rooms, 0, $lim);   // round 9: the five rooms in data/rooms.php
    $showBook = (int)sec_opt($s, 'show_book') === 1; $showDet = (int)sec_opt($s, 'show_details') === 1;
    $showExplore = (int)sec_opt($s, 'show_explore') === 1; $showEnq = (int)sec_opt($s, 'show_enquiry') === 1;
    $ph = base_url('assets/img/placeholder.jpg');
    $cols = (int)sec_opt($s, 'columns') ?: 4;
    $onRoomsPage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'rooms.php';
    // round 5: on the home page, phones show only the first rooms (CSS, .rd-rooms--trim) plus a "View All Rooms" button
    // to the existing /rooms page; tablets and desktops still show every room.
    $trim = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php' && count($rooms) > 3; ?>
    <section class="rd-section rd-rooms<?= $trim ? ' rd-rooms--trim' : '' ?>"><div class="rd-container">
      <?php rd_sechead(trim((string)($s['title'] ?? '')) !== '' ? $s['title'] : rd('rooms_title'), (string)($s['subtitle'] ?? ''), $onRoomsPage ? '' : u('rooms.php')); ?>
      <div class="rd-rooms__grid" style="--cols:<?= $cols ?>">
        <?php foreach ($rooms as $n => $r): $cover = room_cover($r); $pics = room_images($r); if ($cover && ($k = array_search($cover, $pics, true)) !== false) { unset($pics[$k]); array_unshift($pics, $cover); } $pics = array_values(array_slice($pics, 0, 8));
              if (count($pics) < 2) $pics = array_values(array_unique(array_merge($pics ?: ($cover ? [$cover] : []), rd_room_extra_images($r['slug'])))); // presentation fallback: assets/img/rooms/{slug}/
              if (!$pics) $pics = [''];
              $url = u('room.php?slug='.$r['slug']);
              $cat = array_filter([room_bed_label((string)$r['bed_type']), $r['view_type']]); ?>
        <article class="rd-room reveal" style="--d:<?= ($n % $cols) * 70 ?>ms">
          <div class="rd-room__media" <?= count($pics) > 1 ? 'data-rd-slides' : '' ?>>
            <a class="rd-room__link" href="<?= $url ?>" aria-label="<?= e($r['name']) ?> — photos, details and booking" tabindex="-1">
              <div class="rd-room__track">
                <?php foreach ($pics as $i => $pic): ?><img src="<?= $pic ? base_url(room_thumb($pic)) : $ph ?>" alt="<?= $pic ? e(room_photo_alt($r, $pic)) : '' ?>" loading="lazy" decoding="async" width="600" height="450"><?php endforeach; /* round 14: each photo has its own alt text */ ?>
              </div>
            </a>
            <?php if (count($pics) > 1): ?>
            <div class="rd-room__dots" aria-hidden="true"><?php foreach ($pics as $i => $pic): ?><i class="<?= $i === 0 ? 'is-on' : '' ?>"></i><?php endforeach; ?></div>
            <?php endif; ?>
            <?php if ($r['pets_allowed']): ?><span class="rd-room__badge">Pet friendly</span><?php endif; ?>
            <a class="rd-room__book" <?= rd_enquiry_attrs((int)$r['id']) ?> aria-label="Enquire about <?= e($r['name']) ?>"><?= rd_icon('arrow') ?></a>
          </div>
          <div class="rd-room__body">
            <h3 class="rd-room__name"><a href="<?= $url ?>"><?= e($r['name']) ?></a></h3>
            <?php if ($cat): ?><p class="rd-room__cat"><?= e(implode(' · ', $cat)) ?></p><?php endif; ?>
            <?php if ($showDet && ($r['room_size'] || $r['base_occupancy'])): ?><p class="rd-room__meta"><?= e(trim(($r['room_size'] ? $r['room_size'].' · ' : '').'Sleeps '.(int)$r['base_occupancy'].(!empty($r['baths']) ? ' · '.(int)$r['baths'].' washrooms' : ''))) ?></p><?php endif; ?>
            <p class="rd-room__desc"><?= e($r['short_desc']) ?></p>
            <div class="rd-room__foot">
              <?php /* round 5: room prices are not shown publicly — guests enquire for rates (Enquire link below / arrow) */ ?>
              <div class="rd-room__actions">
                <?php /* REDESIGN (round 4): "Enquire" and "Book" both open the WhatsApp enquiry form for this room (one link when both are switched on) */ ?>
                <?php if ($showEnq || $showBook): ?><a class="rd-link" <?= rd_enquiry_attrs((int)$r['id']) ?>>Enquire</a><?php endif; ?>
                <?php if ($showExplore): ?><a class="rd-link" href="<?= $url ?>">Explore</a><?php endif; ?>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php if ($trim): ?><p class="rd-rooms__all"><a class="rd-btn rd-btn--dark rd-btn--arrow" href="<?= u('rooms.php') ?>"><span>View All Rooms</span><?= rd_icon('arrow') ?></a></p><?php endif; ?>
    </div></section>
<?php }


/* ---------- round 6: public amenity list from data/amenities.php (the supplied set) — falls back to Admin → Amenities ---------- */
function amenity_data(): array {
    static $rows = null;
    if ($rows !== null) return $rows;
    $file = __DIR__.'/../data/amenities.php';
    $list = is_file($file) ? include $file : [];
    $rows = [];
    foreach (is_array($list) ? $list : [] as $i => $a) {
        $photos = array_values(array_filter((array)($a['photos'] ?? []), 'is_string'));
        $rows[] = ['id'=>$i + 1, 'name'=>(string)$a['name'], 'icon'=>(string)($a['icon'] ?? 'fa-star'), 'image'=>$photos[0] ?? '', 'images'=>json_encode($photos),
                   'description'=>(string)($a['description'] ?? ''), 'alt'=>array_values((array)($a['alt'] ?? [])), 'home'=>(int)($a['home'] ?? 0), 'sort'=>$i, 'active'=>1, 'show_home'=>1];
    }
    return $rows;
}
/** smaller copy of a supplied amenity photo ({file}-800.jpg) when it exists, else the photo itself */
function amenity_thumb(string $img): string {
    $small = preg_replace('/\.jpe?g$/i', '-800.jpg', $img);
    return $small !== $img && is_file(__DIR__.'/../'.$small) ? $small : $img;
}
function amenity_alt(array $a, int $k = 0): string {
    return (string)($a['alt'][$k] ?? $a['name']);
}

/* ---------- amenities showcase: latest photos in a 3-column grid → sliding popup with Book button ---------- */
function amenity_slides(): array {
    static $slides = null;
    if ($slides !== null) return $slides;
    $slides = [];
    foreach (amenity_data() ?: q_all("SELECT * FROM amenities WHERE active=1 ORDER BY id DESC") as $a) {
        $imgs = json_decode($a['images'] ?? '[]', true); $imgs = is_array($imgs) ? array_values(array_filter($imgs, 'is_string')) : [];
        if ($a['image'] !== '' && !in_array($a['image'], $imgs, true)) array_unshift($imgs, $a['image']);
        foreach ($imgs as $k => $im) $slides[] = ['id'=>(int)$a['id'],'src'=>base_url($im),'title'=>$a['name'],'text'=>$a['description'],'alt'=>amenity_alt($a, $k)];
    }
    return $slides;
}
function amenity_slider_once(): void {
    static $done = false; if ($done) return; $done = true;
    $slides = amenity_slides(); if (!$slides) return; ?>
    <div class="am-slider" id="amSlider" aria-hidden="true">
      <button type="button" class="am-x" data-am-close aria-label="Close">&times;</button>
      <button type="button" class="am-nav am-prev" data-am-prev aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
      <div class="am-stage"><img id="amImg" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="<?= e(($slides[0]['alt'] ?? '') ?: 'Amenities at '.setting('site_name')) ?>"><div class="am-cap"><span class="am-count" id="amCount"></span><p class="am-title" id="amTitle" role="heading" aria-level="3"></p><p id="amText"></p>
        <div class="am-cta"><a class="btn btn-primary" <?= rd_enquiry_attrs() ?>><i class="fa-brands fa-whatsapp"></i> <?= e(rd('enquiry_cta')) ?></a><a class="btn btn-outline" style="color:#fff;border-color:#fff" href="<?= u('amenities.php') ?>">All Amenities</a></div></div></div>
      <button type="button" class="am-nav am-next" data-am-next aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
      <div class="am-thumbs" id="amThumbs"></div>
    </div>
    <script>window.ECH_AMENITIES = <?= json_encode($slides, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;</script>
<?php }
function sec_amenities_gallery(array $s): void {
    $lim = max(1, (int)sec_opt($s, 'limit'));
    if ($data = amenity_data()) {   // round 6: the supplied amenities marked for the home page, in their 'home' order
        $rows = array_filter($data, fn($a) => $a['home'] > 0 && $a['image'] !== '');
        usort($rows, fn($x, $y) => $x['home'] <=> $y['home']);
        $rows = array_slice($rows, 0, $lim);
    } else $rows = q_all("SELECT * FROM amenities WHERE active=1 AND image<>'' ORDER BY id DESC LIMIT ".$lim);
    $tiles = [];
    foreach ($rows as $a) $tiles[] = ['name'=>$a['name'], 'img'=>base_url($data ? amenity_thumb($a['image']) : $a['image']), 'alt'=>$data ? amenity_alt($a) : '', 'href'=>u('amenities.php'), 'am'=>(int)$a['id']];
    $more = $data && count($data) > count($tiles);   // round 6: home shows a selection → link to the full list
    if (!$tiles) foreach (rd('amenity_fallback', []) as $f)   // no amenity photos yet → placeholder tiles (REPLACE CONTENT)
        $tiles[] = ['name'=>$f['name'], 'img'=>base_url($f['image'] ?: 'assets/img/placeholder.jpg'), 'href'=>u($f['url']), 'am'=>0]; ?>
    <section class="rd-section rd-amen"><div class="rd-container">
      <?php rd_sechead(trim((string)($s['title'] ?? '')) !== '' ? $s['title'] : rd('amenities_title'), trim((string)($s['subtitle'] ?? '')) !== '' ? $s['subtitle'] : rd('amenities_sub')); ?>
      <div class="rd-bento">
        <?php foreach ($tiles as $i => $t): $wide = in_array($i % 6, [0, 5], true); ?>
        <a class="rd-bento__tile<?= $wide ? ' rd-bento__tile--wide' : '' ?> reveal" href="<?= e($t['href']) ?>" <?= $t['am'] ? 'data-am-open="'.$t['am'].'"' : '' ?> style="--d:<?= ($i % 3) * 70 ?>ms">
          <img src="<?= e($t['img']) ?>" alt="<?= e($t['alt'] ?? '') ?>" loading="lazy" decoding="async" width="900" height="600">
          <span class="rd-bento__label" data-label="<?= e($t['name']) ?>"><?= e($t['name']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($s['btn1_label']) || !empty($s['btn2_label'])): ?><p class="rd-center rd-mt"><?= sec_btns($s) ?></p>
      <?php elseif ($more): ?><p class="rd-amen__all"><a class="rd-btn rd-btn--dark rd-btn--arrow" href="<?= u('amenities.php') ?>"><span>View All Amenities</span><?= rd_icon('arrow') ?></a></p><?php endif; ?>
      <?php if ($rows) amenity_slider_once(); ?>
    </div></section>
<?php }

/* ---------- facilities icons ---------- */
function sec_facilities(array $s): void {
    $amenities = amenity_data() ? array_slice(amenity_data(), 0, max(1,(int)sec_opt($s,'limit'))) : q_all("SELECT * FROM amenities WHERE active=1 AND show_home=1 ORDER BY sort LIMIT ".max(1,(int)sec_opt($s,'limit'))); ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="fac-grid stagger">
        <?php foreach ($amenities as $a): ?>
        <div class="fac-item"><i class="fa-solid <?= e($a['icon']) ?>"></i><p><?= e($a['name']) ?></p></div>
        <?php endforeach; ?>
      </div>
    </div></section>
<?php }

/* ---------- reviews (REDESIGN: "What Our Guests Say" — rotating avatar ring + cross-fading quotes; same query) ---------- */
function sec_reviews(array $s): void {
    // REDESIGN (round 3): "Guest book" — one large serif quote at a time with a slow progress-line timer, the guests as
    // tabs underneath, and the genuine average rating. Only real rows from the reviews table are shown (no demo reviews);
    // with none the section is not printed.
    $reviews = q_all("SELECT * FROM reviews WHERE active=1 ORDER BY sort, id DESC LIMIT ".max(1,(int)sec_opt($s,'limit')));
    if (!$reviews) { if (function_exists('live_edit_mode') && live_edit_mode()) echo '<section><div class="container"><div class="alert" style="text-align:center"><b>Guest reviews</b> — add reviews in Admin → Reviews and they appear here.</div></div></section>'; return; }
    $n = count($reviews);
    $avg = array_sum(array_map(fn($r) => (float)$r['rating'], $reviews)) / $n;
    $stars = function (float $r): string { $o = ''; for ($i = 0; $i < 5; $i++) $o .= $r - $i >= 1 ? '<i class="fa-solid fa-star"></i>' : ($r - $i >= .5 ? '<i class="fa-solid fa-star-half-stroke"></i>' : '<i class="fa-regular fa-star"></i>'); return $o; };
    $fmt = fn(float $r) => rtrim(rtrim(number_format($r, 1), '0'), '.');
    $title = trim((string)($s['title'] ?? '')) !== '' ? $s['title'] : rd('reviews_title');
    $eyebrow = trim((string)($s['subtitle'] ?? '')) !== '' ? $s['subtitle'] : 'Guest book';
    static $uid = 0; $uid++; ?>
    <section class="rd-gb" data-rd-guestbook aria-labelledby="rdgb<?= $uid ?>">
      <div class="rd-gb__inner">
        <header class="rd-gb__head">
          <p class="rd-gb__eyebrow"><?= e($eyebrow) ?></p>
          <h2 class="rd-gb__title" id="rdgb<?= $uid ?>"><?= e($title) ?></h2>
          <div class="rd-gb__score">
            <b><?= e(number_format($avg, 1)) ?></b>
            <span><span class="rd-gb__stars" aria-hidden="true"><?= $stars($avg) ?></span><small>Average of <?= $n ?> guest review<?= $n === 1 ? '' : 's' ?></small></span>
          </div>
        </header>
        <div class="rd-gb__stage">
          <span class="rd-gb__mark" aria-hidden="true">&ldquo;</span>
          <div class="rd-gb__slides">
            <?php foreach ($reviews as $k => $rv):
                $meta = implode(' · ', array_filter([$rv['source'] === 'google' ? 'Google review' : '', $rv['review_date'] ? date('F Y', strtotime($rv['review_date'])) : ''])); ?>
            <figure class="rd-gb__slide<?= $k === 0 ? ' is-active' : '' ?>" id="rdgb<?= $uid ?>-<?= $k ?>" role="group" aria-roledescription="review" aria-label="<?= $k + 1 ?> of <?= $n ?>"<?= $k ? ' hidden' : '' ?>>
              <div class="rd-gb__stars" role="img" aria-label="Rated <?= $fmt((float)$rv['rating']) ?> out of 5"><?= $stars((float)$rv['rating']) ?></div>
              <blockquote class="rd-gb__quote"><p id="rdgb<?= $uid ?>-<?= $k ?>-q"><?= nl2br(e($rv['content'])) ?></p></blockquote>
              <?php /* REDESIGN (round 4): long reviews are clamped to a few lines by CSS (full text stays in the page);
                       redesign.js shows this button only when the text is actually cut off */ ?>
              <button type="button" class="rd-gb__more" aria-expanded="false" aria-controls="rdgb<?= $uid ?>-<?= $k ?>-q">Read more</button>
              <figcaption class="rd-gb__by"><b><?= e($rv['author']) ?></b><?php if ($meta !== ''): ?><span><?= e($meta) ?></span><?php endif; ?></figcaption>
            </figure>
            <?php endforeach; ?>
          </div>
          <?php if ($n > 1): ?>
          <div class="rd-gb__nav">
            <div class="rd-gb__tabs" aria-label="Choose a review">
              <?php foreach ($reviews as $k => $rv): ?>
              <button type="button" class="rd-gb__tab<?= $k === 0 ? ' is-active' : '' ?>" aria-controls="rdgb<?= $uid ?>-<?= $k ?>" aria-current="<?= $k === 0 ? 'true' : 'false' ?>" aria-label="Review by <?= e($rv['author']) ?>">
                <span class="rd-gb__face"><?php if (!empty($rv['avatar'])): ?><img src="<?= e(preg_match('#^https?://#', $rv['avatar']) ? $rv['avatar'] : base_url($rv['avatar'])) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><?= e(mb_strtoupper(mb_substr($rv['author'], 0, 1))) ?><?php endif; ?></span>
                <span class="rd-gb__name"><?= e($rv['author']) ?></span>
                <span class="rd-gb__bar" aria-hidden="true"><i></i></span>
              </button>
              <?php endforeach; ?>
            </div>
            <div class="rd-gb__arrows">
              <span class="rd-gb__count" aria-hidden="true"><b data-rd-gb-now>01</b> / <?= str_pad((string)$n, 2, '0', STR_PAD_LEFT) ?></span>
              <button type="button" class="rd-gb__arrow" data-rd-prev aria-label="Previous review"><?= rd_icon('arrowl') ?></button>
              <button type="button" class="rd-gb__arrow" data-rd-next aria-label="Next review"><?= rd_icon('arrow') ?></button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
<?php }

/* ---------- REDESIGN: About / Sustainability / FAQ pill tabs (new presentation-only section type) ----------
 * About tab   = the About page's own intro text (Admin → Pages → About, or the live editor) — edited exactly as today
 * Sustainability = this section's "content" field (falls back to copy in includes/redesign_content.php)
 * FAQ tab     = the faqs table (same data as the FAQ page) */
function sec_about_tabs(array $s): void {
    static $uid = 0; $uid++;
    $about = q_row("SELECT content FROM pages WHERE slug='about'");
    $aboutHtml = $about && !html_is_empty($about['content']) ? str_replace(['{SITE_NAME}','{PHONE}','{EMAIL}'], [setting('site_name'), setting('contact_phone'), setting('contact_email')], (string)$about['content'])
               : '<h3>'.e(rd('about_fallback_title')).'</h3>'.rd('about_fallback');
    $sustain = !html_is_empty($s['content'] ?? '') ? (string)$s['content'] : '<h3>'.e(rd('sustain_title')).'</h3>'.rd('sustain_text');
    $img = !empty($s['image']) ? base_url($s['image']) : (setting('img_about') !== '' ? site_img('about') : base_url(rd('about_image')));
    $faqs = q_all("SELECT question, answer FROM faqs WHERE active=1 ORDER BY sort, id LIMIT ".max(1, (int)sec_opt($s, 'faq_limit')));
    if (!$faqs) $faqs = rd('faq_fallback', []);
    $tabs = ['about'=>'About Us', 'sustain'=>'Sustainability', 'faq'=>'FAQ']; ?>
    <section class="rd-section rd-tabs" data-rd-tabs aria-label="<?= e(trim((string)($s['title'] ?? '')) ?: 'About us') ?>">
      <div class="rd-container">
        <div class="rd-tabs__bar reveal" role="tablist">
          <?php $f = true; foreach ($tabs as $k => $label): ?>
          <button type="button" class="rd-tabs__tab<?= $f ? ' is-active' : '' ?>" role="tab" id="rdtab-<?= $uid.$k ?>" aria-controls="rdpanel-<?= $uid.$k ?>" aria-selected="<?= $f ? 'true' : 'false' ?>" tabindex="<?= $f ? '0' : '-1' ?>"><?= e($label) ?></button>
          <?php $f = false; endforeach; ?>
        </div>
        <div class="rd-tabs__panels reveal">
          <div class="rd-tabs__panel is-active" role="tabpanel" id="rdpanel-<?= $uid ?>about" aria-labelledby="rdtab-<?= $uid ?>about" tabindex="0">
            <div class="rd-tabcard">
              <div class="rd-tabcard__text">
                <div class="rd-tabcard__clamp rich"><?= $aboutHtml ?></div>
                <a class="rd-btn rd-btn--ghost rd-tabcard__more" href="<?= u('about.php') ?>"><span>Read our story</span><?= rd_icon('arrow') ?></a>
              </div>
              <div class="rd-tabcard__media"><img src="<?= e($img) ?>" alt="<?= e(empty($s['image']) && setting('img_about') === '' ? rd('about_alt') : setting('site_name').', Mussoorie') ?>" loading="lazy" width="1200" height="800"></div>
            </div>
          </div>
          <div class="rd-tabs__panel" role="tabpanel" id="rdpanel-<?= $uid ?>sustain" aria-labelledby="rdtab-<?= $uid ?>sustain" tabindex="0" hidden>
            <div class="rd-tabcard">
              <div class="rd-tabcard__text rich"><?= $sustain ?><!-- REPLACE CONTENT (edit this section's text in the builder) --></div>
              <div class="rd-tabcard__media"><img src="<?= e(base_url(rd('hero_poster'))) ?>" alt="<?= e(setting('site_name').' in the hills on the Dhanaulti Road, Mussoorie') ?>" loading="lazy" width="1200" height="800"></div>
            </div>
          </div>
          <div class="rd-tabs__panel" role="tabpanel" id="rdpanel-<?= $uid ?>faq" aria-labelledby="rdtab-<?= $uid ?>faq" tabindex="0" hidden>
            <div class="rd-tabcard rd-tabcard--faq">
              <div class="rd-faq">
                <h3 class="rd-sr">Frequently asked questions</h3>
                <?php foreach ($faqs as $i => $fq): $qid = 'rdfaq'.$uid.'-'.$i; ?>
                <div class="rd-faq__item">
                  <h4 class="rd-faq__h"><button type="button" class="rd-faq__q" aria-expanded="false" aria-controls="<?= $qid ?>"><span><?= e($fq['question']) ?></span><i class="rd-faq__icon" aria-hidden="true"></i></button></h4>
                  <div class="rd-faq__a" id="<?= $qid ?>" role="region"><div><p><?= nl2br(e($fq['answer'])) ?></p></div></div>
                </div>
                <?php endforeach; ?>
              </div>
              <p class="rd-faq__more"><a class="rd-btn rd-btn--ghost" href="<?= u('faqs.php') ?>"><span>All FAQs</span><?= rd_icon('arrow') ?></a></p>
            </div>
          </div>
        </div>
      </div>
    </section>
<?php }

/* ---------- blog preview ---------- */
function sec_blog(array $s): void {
    $posts = q_all("SELECT p.*, c.name cat_name FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.published_at DESC LIMIT ".max(1,(int)sec_opt($s,'limit')));
    if (!$posts) return;
    $ph = base_url('assets/img/placeholder.jpg'); ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="blog-grid stagger">
        <?php foreach ($posts as $p): ?>
        <a class="blog-card" href="<?= u('post.php?slug='.$p['slug']) ?>">
          <div class="bc-img"><img src="<?= e(rd_blog_image($p, $ph)) ?>" alt="<?= e($p['title']) ?>" loading="lazy"></div>
          <div class="bc-body">
            <span class="bc-cat"><?= e($p['cat_name'] ?: 'Journal') ?></span>
            <h3><?= e($p['title']) ?></h3>
            <p class="bc-meta"><i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($p['published_at'])) ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div></section>
<?php }

/* ---------- FAQ accordion ---------- */
function sec_faq(array $s): void {
    $faqs = q_all("SELECT * FROM faqs WHERE active=1 ORDER BY sort, id LIMIT ".max(1,(int)sec_opt($s,'limit'))); ?>
    <section><div class="container" style="max-width:860px">
      <?php sec_head($s); ?>
      <?php foreach ($faqs as $f): ?>
      <div class="faq-item reveal">
        <div class="faq-q"><?= e($f['question']) ?> <i class="fa-solid fa-chevron-down"></i></div>
        <div class="faq-a"><p><?= nl2br(e($f['answer'])) ?></p></div>
      </div>
      <?php endforeach; ?>
    </div></section>
<?php }

/* ---------- CTA band ---------- */
function sec_cta(array $s): void {
    // REDESIGN (round 2): the "Book Your Stay" banners at the bottom of pages are retired site-wide — the navbar
    // "Book Now" button is the booking call-to-action. A CTA whose button goes to the booking page is not printed
    // (the section stays saved in the CMS, so re-enabling is a one-line change here). CTAs to anything else, e.g.
    // "Call Concierge" on Explore, still show. The home page's CTA data feeds the Welcome section instead (sec_welcome).
    if (preg_match('~(^|/)booking(\.php)?($|[?#])~', trim((string)($s['btn1_url'] ?? '')))) {
        if (function_exists('live_edit_mode') && live_edit_mode()) echo '<section><div class="container"><div class="alert" style="text-align:center"><b>Book Your Stay banner</b> — hidden on the live site by the redesign (the navbar Enquire Now button replaces it).</div></div></section>';
        return;
    }
    $bg = !empty($s['image']) ? base_url($s['image']) : (setting('img_hero') !== '' ? site_img('hero') : base_url(rd('hero_poster')));   // REDESIGN: real photo instead of the stock placeholder ?>
    <div class="cta-band" style="background-image:url('<?= e($bg) ?>')">
      <div class="container reveal">
        <?php if (!empty($s['subtitle'])): ?><p class="section-tag" style="color:var(--c-accent)"><?= e($s['subtitle']) ?></p><?php endif; ?>
        <h2><?= e($s['title'] ?? '') ?></h2>
        <?php if (!empty($s['content'])): ?><div style="margin-bottom:18px"><?= $s['content'] ?></div><?php endif; ?>
        <?= sec_btns($s, 'margin-left:10px;color:#fff;border-color:#fff') ?>
      </div>
    </div>
<?php }

/* ---------- REDESIGN (round 2): home "Welcome" section ----------
 * Injected on the home page by index.php (no data-sec, so the live editor's section indexes are untouched).
 * Data = the home page's CTA section (title / subtitle / content / button label), fallbacks in redesign_content.php.
 * The button opens the WhatsApp enquiry form, like the navbar "Enquire Now" (round 4). */
function sec_welcome(array $s): void {
    $title = trim(preg_replace('/\s*[—–-]\s*book your stay.*$/iu', '', (string)($s['title'] ?? '')));
    if ($title === '') $title = rd('welcome_title');
    $accent = rd('welcome_accent');
    $h = e($title);
    if ($accent !== '' && stripos($h, e($accent)) !== false) $h = preg_replace('/'.preg_quote(e($accent), '/').'/i', '<em>$0</em>', $h, 1);
    $eyebrow = rd('welcome_eyebrow');
    $text = !html_is_empty($s['content'] ?? '') ? (string)$s['content'] : rd('welcome_text');
    $cta = rd('welcome_cta');
    $stats = [];
    foreach (rd('welcome_stats', []) as $st) {
        $v = (string)$st['value']; $dec = 0;
        if ($v === 'auto:rooms') $v = (string)rooms_count();   // round 19: the duplex is not an extra room
        elseif ($v === 'auto:amenities') $v = (string)(count(amenity_data()) ?: (int)q_val("SELECT COUNT(*) FROM amenities WHERE active=1"));
        elseif ($v === 'auto:rating') { $r = (float)q_val("SELECT AVG(rating) FROM reviews WHERE active=1"); $v = $r > 0 ? number_format($r, 1, '.', '') : ''; $dec = 1; }
        if ($v === '' || (float)$v <= 0) continue;
        $stats[] = ['v'=>$v, 'dec'=>$dec, 'suffix'=>$st['suffix'] ?? '', 'label'=>$st['label']];
    }
    $badgeTxt = str_repeat(rd('welcome_badge'), 1); ?>
    <section class="rd-welcome" data-rd-welcome aria-labelledby="rdWelcomeTitle">
      <div class="rd-container rd-welcome__grid">
        <div class="rd-welcome__text">
          <!-- REPLACE CONTENT: eyebrow / heading / text / button label — heading + text come from the home CTA section in the CMS -->
          <p class="rd-welcome__eyebrow"><?= e($eyebrow) ?></p>
          <h2 class="rd-welcome__title" id="rdWelcomeTitle"><?= $h ?></h2>
          <div class="rd-welcome__copy"><?= $text ?></div>
          <a class="rd-btn rd-btn--dark rd-btn--arrow rd-welcome__cta" <?= rd_enquiry_attrs() ?>><span><?= e($cta) ?></span><?= rd_icon('arrow') ?></a>
        </div>
        <div class="rd-welcome__media">
          <span class="rd-welcome__frame" aria-hidden="true" data-rd-parallax="-0.04"></span>
          <figure class="rd-welcome__img rd-welcome__img--main" data-rd-parallax="0.05"><img src="<?= e(base_url(rd('welcome_img_main'))) ?>" alt="<?= e(rd('welcome_alt_main')) ?>" width="1050" height="1400" loading="lazy" decoding="async"></figure>
          <figure class="rd-welcome__img rd-welcome__img--small" data-rd-parallax="-0.08"><img src="<?= e(base_url(rd('welcome_img_small'))) ?>" alt="<?= e(rd('welcome_alt_small')) ?>" width="900" height="600" loading="lazy" decoding="async"></figure>
          <a class="rd-welcome__badge" <?= rd_enquiry_attrs() ?> aria-label="Make an enquiry">
            <svg viewBox="0 0 120 120" aria-hidden="true" focusable="false"><defs><path id="rdBadgePath" d="M60 60m-45 0a45 45 0 1 1 90 0a45 45 0 1 1-90 0"/></defs><text><textPath href="#rdBadgePath" textLength="282"><?= e(mb_strtoupper($badgeTxt)) ?></textPath></text></svg>
            <span class="rd-welcome__badge-ico"><?= rd_icon('arrow') ?></span>
          </a>
        </div>
      </div>
      <?php if ($stats): ?>
      <div class="rd-container">
        <!-- REPLACE CONTENT: stat strip (dummy numbers are marked in includes/redesign_content.php → welcome_stats) -->
        <dl class="rd-welcome__stats">
          <?php foreach ($stats as $st): ?>
          <div class="rd-stat"><dt class="rd-stat__label"><?= e($st['label']) ?></dt><dd class="rd-stat__num"><span data-rd-count="<?= e($st['v']) ?>" data-decimals="<?= (int)$st['dec'] ?>"><?= e($st['v']) ?></span><?= e($st['suffix']) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
      <?php endif; ?>
    </section>
<?php }

/* ---------- rich text ---------- */
function sec_text(array $s): void {
    $w = sec_opt($s,'width') === 'wide' ? '' : 'max-width:900px;'; $al = sec_opt($s,'align') === 'center' ? 'text-align:center;' : ''; ?>
    <section><div class="container reveal" style="<?= $w.$al ?>">
      <?php sec_head($s); ?>
      <div class="rich" style="color:inherit"><?= $s['content'] ?? '' ?></div>
      <?php if (!empty($s['btn1_label']) || !empty($s['btn2_label'])): ?><div class="mt-3" style="<?= $al ?>"><?= sec_btns($s) ?></div><?php endif; ?>
    </div></section>
<?php }

/* ---------- image + text split ---------- */
function sec_image_text(array $s): void {
    // round 14: without a photo of its own (and no About photo in Admin → Gallery) show the real bonfire photo, not the stock placeholder
    // round 16: a photo path whose file is missing on the server counts as no photo (it showed as a broken image on About)
    if (!empty($s['image']) && !preg_match('#^(https?:)?//#', (string)$s['image']) && !is_file(ROOT_PATH.'/'.ltrim((string)$s['image'], '/'))) $s['image'] = '';
    $stock = empty($s['image']) && (setting('img_about') === '' || !is_file(ROOT_PATH.'/'.ltrim(setting('img_about'), '/'))) && function_exists('rd');
    $img = !empty($s['image']) ? base_url($s['image']) : ($stock ? base_url(rd('about_image')) : site_img('about')); $left = sec_opt($s,'side') === 'left'; ?>
    <section><div class="container split <?= $left ? 'split-rev' : '' ?>">
      <div class="reveal-left">
        <?php if (!empty($s['subtitle'])): ?><p class="section-tag" style="text-align:left"><?= e($s['subtitle']) ?></p><?php endif; ?>
        <h2 style="font-size:clamp(26px,4vw,40px)"><?= e($s['title'] ?? '') ?></h2>
        <div class="mt-2 rich" style="color:#68717d"><?= $s['content'] ?? '' ?></div>
        <?php if (!empty($s['btn1_label']) || !empty($s['btn2_label'])): ?><div class="mt-2"><?= sec_btns($s) ?></div><?php endif; ?>
      </div>
      <div class="reveal-right"><img src="<?= e($img) ?>" alt="<?= e($stock ? rd('about_alt') : (function_exists('rd') && ($s['image'] ?? '') === rd('about_page_image') ? rd('about_page_alt') : ($s['title'] ?? ''))) ?>" loading="lazy"></div>
    </div></section>
<?php }

/* ---------- gallery (from Gallery manager, or comma-separated paths in content) ---------- */
function sec_gallery(array $s): void {
    $imgs = [];
    $raw = trim($s['content'] ?? '');
    if ($raw !== '' && str_contains($raw, '/')) {
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $p) $imgs[] = ['image'=>$p,'title'=>''];
    } else {
        // pull from Gallery manager — content may name a category
        $imgs = $raw !== ''
            ? q_all("SELECT image,title FROM gallery_images WHERE active=1 AND category=? ORDER BY sort, id DESC LIMIT ".max(1,(int)sec_opt($s,'limit')), [$raw])
            : q_all("SELECT image,title FROM gallery_images WHERE active=1 ORDER BY sort, id DESC LIMIT ".max(1,(int)sec_opt($s,'limit')));
    }
    if (!empty($s['image'])) array_unshift($imgs, ['image'=>$s['image'],'title'=>'']);
    if (!$imgs) return; ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="amen-grid stagger">
        <?php foreach ($imgs as $im): ?>
        <div class="amen-card" onclick="openLightbox('<?= e(base_url($im['image'])) ?>','<?= e(addslashes($im['title'] ?? '')) ?>')"><img src="<?= base_url($im['image']) ?>" alt="<?= e($im['title'] ?? '') ?>" loading="lazy">
          <?php if (!empty($im['title'])): ?><div class="ac-label"><?= e($im['title']) ?></div><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
      <?php if ((int)sec_opt($s,'link') === 1): ?><p class="text-center mt-2"><a class="btn btn-outline btn-sm" href="<?= u('gallery.php') ?>">View Full Gallery</a></p><?php endif; ?>
      <?php lightbox_once(); ?>
    </div></section>
<?php }

/* ---------- raw HTML ---------- */
function sec_html(array $s): void { echo $s['content'] ?? ''; }

/* ---------- shared lightbox (printed once per page) ---------- */
function lightbox_once(): void {
    static $done = false; if ($done) return; $done = true; ?>
    <div class="modal" id="lightbox" onclick="this.classList.remove('show')">
      <div style="max-width:min(960px,94vw)"><img id="lbImg" src="" style="width:100%;border-radius:10px" alt="">
      <p id="lbCap" style="color:#fff;text-align:center;margin-top:10px"></p></div>
    </div>
    <script>function openLightbox(src,cap){document.getElementById('lbImg').src=src;document.getElementById('lbCap').textContent=cap||'';document.getElementById('lightbox').classList.add('show');}</script>
<?php }

/* ---------- amenities — full list ---------- */
function sec_amenities(array $s): void {
    $amens = amenity_data() ?: q_all("SELECT * FROM amenities WHERE active=1 ORDER BY sort,id");
    $layout = sec_opt($s,'layout'); ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="amen-grid stagger">
        <?php foreach ($amens as $a): $photo = $a['image'] !== '' && $layout !== 'icons'; if ($layout === 'photos' && !$photo) continue; ?>
          <?php if ($photo): ?>
            <a class="amen-card" href="<?= u('amenities.php') ?>" data-am-open="<?= (int)$a['id'] ?>"><img src="<?= base_url(isset($a['alt']) ? amenity_thumb($a['image']) : $a['image']) ?>" alt="<?= e(isset($a['alt']) ? amenity_alt($a) : $a['name']) ?>" loading="lazy"><div class="ac-label"><?= e($a['name']) ?></div></a>
          <?php else: ?>
            <div class="amen-icon-card">
              <span class="aic-icon"><i class="fa-solid <?= e($a['icon']) ?>"></i></span>
              <h4><?= e($a['name']) ?></h4><p><?= e($a['description'] ?: 'Available for all our guests.') ?></p>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php /* round 19: note under the list on the Amenities page (text: rd('amenities_note')) */
            if (rd('amenities_note') !== '' && (($GLOBALS['LIVE_PAGE']['slug'] ?? '') === 'amenities' || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'amenities.php')): ?>
      <p class="rd-amen-note reveal"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <?= e(rd('amenities_note')) ?></p>
      <?php endif; ?>
      <?php amenity_slider_once(); ?>
    </div></section>
<?php }

/* ---------- feature cards (repeatable items) ---------- */
function sec_cards(array $s): void {
    $items = is_array($s['items'] ?? null) ? $s['items'] : [];
    $cols = sec_opt($s,'columns'); $style = $cols !== 'auto' ? 'grid-template-columns:repeat('.(int)$cols.',1fr)' : ''; ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="value-cards stagger" <?= $style ? 'style="'.$style.'"' : '' ?>>
        <?php foreach ($items as $it): ?>
        <div class="value-card">
          <?php if (!empty($it['image'])): ?><img src="<?= base_url($it['image']) ?>" alt="<?= e($it['title'] ?? '') ?>" loading="lazy" style="width:100%;height:170px;object-fit:cover;border-radius:8px;margin-bottom:14px">
          <?php elseif (!empty($it['icon'])): ?><i class="<?= str_contains($it['icon'],'fa-') ? e($it['icon']) : 'fa-solid '.e($it['icon']) ?>"></i><?php endif; ?>
          <h4><?= e($it['title'] ?? '') ?></h4>
          <p style="font-size:14px;color:#68717d"><?= nl2br(e($it['text'] ?? '')) ?></p>
          <?php if (!empty($it['url']) && !empty($it['link_label'])): ?><a class="btn btn-outline btn-sm mt-2" href="<?= e(menu_url($it['url'])) ?>"><?= e($it['link_label']) ?></a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div></section>
<?php }

/* ---------- video ---------- */
function sec_video(array $s): void {
    $embed = video_embed((string)($s['video_url'] ?? ''), (string)($s['video_file'] ?? ''), (string)($s['image'] ?? ''));
    $split = sec_opt($s,'layout') === 'split'; ?>
    <section><div class="container <?= $split ? 'split' : '' ?>" style="<?= $split ? '' : 'max-width:960px' ?>">
      <?php if ($split): ?>
        <div class="reveal-left"><?php sec_head($s); ?><div class="rich" style="color:#68717d"><?= $s['content'] ?? '' ?></div></div>
        <div class="reveal-right"><?= $embed ?: '<p class="hint">Add a YouTube link or upload a video in the editor.</p>' ?></div>
      <?php else: ?>
        <?php sec_head($s); ?>
        <div class="reveal"><?= $embed ?: '<p style="text-align:center;color:#8a93a0">Add a YouTube link or upload a video in the editor.</p>' ?></div>
        <?php if (!empty($s['content'])): ?><div class="rich mt-3" style="text-align:center;color:#68717d"><?= $s['content'] ?></div><?php endif; ?>
      <?php endif; ?>
    </div></section>
<?php }

/* ---------- contact info cards ---------- */
function sec_contact_info(array $s): void { ?>
    <section style="padding-bottom:30px"><div class="container">
      <?php sec_head($s); ?>
      <div class="value-cards stagger">
        <div class="value-card"><i class="fa-solid fa-location-dot"></i><h4>Our Location</h4><p style="font-size:14px;color:#68717d"><?= nl2br(e(setting('contact_address'))) ?></p><?php /* round 18: the resort's exact Google Maps pin */ ?><p style="font-size:14px;margin-top:6px"><a href="<?= e(function_exists('seo_map_url') ? seo_map_url() : rd('maps_url')) ?>" target="_blank" rel="noopener">View on Google Maps</a></p></div>
        <div class="value-card"><i class="fa-solid fa-phone"></i><h4>Our Phone</h4><p style="font-size:14px"><a href="tel:<?= e(setting('contact_phone')) ?>">Call: <?= e(setting('contact_phone')) ?></a></p></div>
        <div class="value-card"><i class="fa-regular fa-envelope"></i><h4>Our Email</h4><p style="font-size:14px"><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p></div>
      </div>
    </div></section>
<?php }

/* ---------- contact form (+ map) ---------- */
function sec_contact_form(array $s): void {
    $fid = (int)sec_opt($s,'form_id');
    $form = $fid ? q_row("SELECT * FROM forms WHERE id=? AND active=1", [$fid]) : q_row("SELECT * FROM forms WHERE active=1 ORDER BY id LIMIT 1");
    $fields = $form ? (json_decode($form['fields'], true) ?: []) : [];
    $map = (int)sec_opt($s,'show_map') === 1 && setting('map_embed') !== ''; ?>
    <section style="padding-top:10px"><div class="container">
      <div class="<?= $map ? 'grid-2' : '' ?>" style="<?= $map ? '' : 'max-width:760px;margin:0 auto' ?>">
        <div class="card reveal-left">
          <?php if (!empty($s['title'])): ?><h3><?= e($s['title']) ?></h3><?php endif; ?>
          <?php if (!empty($s['content'])): ?><div class="rich" style="font-size:13.5px;color:#8a93a0;margin-bottom:18px"><?= $s['content'] ?></div><?php endif; ?>
          <?php if ($form): ?>
          <?php /* round 18: after the entry is saved, GTM gets form_submit_success (form_name contact_page on the Contact page) and the
                   guest is sent to WhatsApp with the message filled in (assets/js/main.js, submitBuiltForm) */
                $gaSlug = (string)($GLOBALS['LIVE_PAGE']['slug'] ?? '');
                $gaName = ['contact'=>'contact_page', 'home'=>'home_enquiry'][$gaSlug] ?? ($gaSlug !== '' ? str_replace('-', '_', $gaSlug).'_form' : 'form_'.$form['id']); ?>
          <form onsubmit="return submitBuiltForm(this)" data-form="<?= $form['id'] ?>" data-ga-name="<?= e($gaName) ?>" data-wa="<?= e(rd_enquiry_number()) ?>" data-capture>
            <input type="text" name="website_hp" value="" style="display:none" tabindex="-1" autocomplete="off">
            <div class="form-grid">
              <?php foreach ($fields as $f): ?>
                <div class="<?= empty($f['half']) ? 'full' : '' ?>">
                  <label class="<?= !empty($f['required'])?'req':'' ?>"><?= e($f['label']) ?></label>
                  <?php if ($f['type']==='textarea'): ?>
                    <textarea name="<?= e($f['name']) ?>" <?= !empty($f['required'])?'required':'' ?>></textarea>
                  <?php elseif ($f['type']==='select' && !empty($f['options'])): ?>
                    <select name="<?= e($f['name']) ?>" <?= !empty($f['required'])?'required':'' ?>>
                      <?php foreach (explode(',', $f['options']) as $op): ?><option><?= e(trim($op)) ?></option><?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <input type="<?= e($f['type']) ?>" name="<?= e($f['name']) ?>" <?= !empty($f['required'])?'required':'' ?>>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (setting('recaptcha_site')): ?>
              <div class="g-recaptcha mt-2" data-sitekey="<?= e(setting('recaptcha_site')) ?>"></div>
              <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php endif; ?>
            <button class="btn btn-primary mt-2" type="submit"><?= e($form['submit_label']) ?></button>
            <div class="form-msg"></div>
          </form>
          <?php else: ?><p class="hint">Create a form under Admin → Form Builder to show it here.</p><?php endif; ?>
        </div>
        <?php if ($map): ?>
        <div class="reveal-right">
          <iframe src="<?= e(setting('map_embed')) ?>" style="width:100%;height:100%;min-height:420px;border:0;border-radius:10px" loading="lazy" title="Map"></iframe>
        </div>
        <?php endif; ?>
      </div>
    </div></section>
<?php }

/* ---------- map only ---------- */
function sec_map(array $s): void {
    if (setting('map_embed') === '') return; ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <iframe src="<?= e(setting('map_embed')) ?>" style="width:100%;height:<?= max(200,(int)sec_opt($s,'height')) ?>px;border:0;border-radius:12px" loading="lazy" title="Map"></iframe>
    </div></section>
<?php }

/* ---------- FAQ — every question grouped by category ---------- */
function sec_faq_full(array $s): void {
    $faqs = q_all("SELECT * FROM faqs WHERE active=1 ORDER BY category, sort, id");
    $byCat = []; foreach ($faqs as $f) $byCat[$f['category']][] = $f; ?>
    <section><div class="container" style="max-width:860px">
      <?php sec_head($s); ?>
      <?php foreach ($byCat as $cat => $items): ?>
        <?php if (count($byCat) > 1): ?><h3 class="reveal mt-3 mb-2" style="color:var(--c-primary)"><?= e($cat) ?></h3><?php endif; ?>
        <?php foreach ($items as $f): ?>
        <div class="faq-item reveal">
          <div class="faq-q"><?= e($f['question']) ?> <i class="fa-solid fa-chevron-down"></i></div>
          <div class="faq-a"><p><?= nl2br(e($f['answer'])) ?></p></div>
        </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="text-center mt-3 reveal">
        <p style="color:#68717d">Still have a question?</p>
        <?php if (!empty($s['btn1_label'])): [$l1, $a1] = rd_cms_link((string)($s['btn1_url'] ?? 'contact.php'), $s['btn1_label']); ?><a class="btn btn-primary mt-2" <?= $a1 ?>><?= e($l1) ?></a><?php endif; ?>
        <a class="btn mt-2" style="background:#25d366;color:#fff" href="<?= e(wa_link('', 'Hi! I have a question about '.setting('site_name'))) ?>" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      </div>
    </div></section>
<?php }

/* ---------- blog — full list with category pills + pagination ---------- */
function sec_blog_list(array $s): void {
    $cats = q_all("SELECT c.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id=c.id AND p.status='published') cnt FROM blog_categories c WHERE c.active=1 ORDER BY c.name");
    $catSlug = get('category');
    $cat = $catSlug ? q_row("SELECT * FROM blog_categories WHERE slug=? AND active=1", [$catSlug]) : null;
    $per = max(3, (int)sec_opt($s,'per_page')); $pageNo = max(1, (int)get('p', 1)); $off = ($pageNo-1)*$per;
    $where = "p.status='published'" . ($cat ? " AND p.category_id=".(int)$cat['id'] : '');
    $total = (int)q_val("SELECT COUNT(*) FROM blog_posts p WHERE $where");
    $posts = q_all("SELECT p.*, c.name cat_name, c.slug cat_slug FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE $where ORDER BY p.published_at DESC LIMIT $per OFFSET $off");
    $ph = base_url('assets/img/placeholder.jpg'); ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <div class="cat-pills reveal">
        <a href="<?= u('blog.php') ?>" class="<?= !$cat?'active':'' ?>">All</a>
        <?php foreach ($cats as $c): ?><a href="<?= u('blog.php?category='.$c['slug']) ?>" class="<?= $cat && $cat['id']==$c['id']?'active':'' ?>"><?= e($c['name']) ?> (<?= $c['cnt'] ?>)</a><?php endforeach; ?>
      </div>
      <?php if (!$posts): ?><p class="text-center" style="color:#8a93a0;padding:40px 0">No posts yet — check back soon!</p><?php endif; ?>
      <div class="blog-grid stagger">
        <?php foreach ($posts as $p): ?>
        <a class="blog-card" href="<?= u('post.php?slug='.$p['slug']) ?>">
          <div class="bc-img"><img src="<?= e(rd_blog_image($p, $ph)) ?>" alt="<?= e($p['title']) ?>" loading="lazy"></div>
          <div class="bc-body">
            <span class="bc-cat"><?= e($p['cat_name'] ?: 'Journal') ?></span>
            <h3><?= e($p['title']) ?></h3>
            <p style="font-size:14px;color:#68717d"><?= e(mb_strimwidth($p['excerpt'],0,120,'…')) ?></p>
            <p class="bc-meta mt-1"><i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($p['published_at'])) ?> · <i class="fa-regular fa-eye"></i> <?= $p['views'] ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php $pages = (int)ceil($total/$per); if ($pages > 1): ?>
      <div class="text-center mt-3">
        <?php for ($i=1;$i<=$pages;$i++): ?><a class="btn <?= $i===$pageNo?'btn-primary':'btn-outline' ?> btn-sm" href="<?= u('blog.php?p='.$i.($cat?'&category='.$cat['slug']:'')) ?>"><?= $i ?></a><?php endfor; ?>
      </div>
      <?php endif; ?>
    </div></section>
<?php }

/* ---------- gallery — full page with category filter + lightbox ---------- */
function sec_gallery_full(array $s): void {
    // REDESIGN (round 2): masonry grid + category pills that filter in place + full-screen lightbox (assets/js/redesign.js §9).
    // Photos = Admin → Gallery uploads (same query as before) followed by the curated list in data/gallery.php.
    $rows = q_all("SELECT * FROM gallery_images WHERE active=1 ORDER BY sort, id DESC");
    $photos = [];
    foreach ($rows as $g) $photos[] = ['src'=>$g['image'], 'category'=>rd_gallery_category((string)$g['category']), 'caption'=>(string)$g['title'], 'alt'=>(string)$g['title']];
    $seen = array_column($photos, 'src');
    foreach (rd_gallery_data() as $g) if (!in_array($g['src'], $seen, true)) $photos[] = $g;
    $photos = array_values(array_filter($photos, fn($p) => is_file(ROOT_PATH.'/'.ltrim($p['src'], '/'))));
    $counts = array_count_values(array_column($photos, 'category'));
    $pills = array_values(array_filter(rd_gallery_categories(), fn($c) => !empty($counts[$c])));
    $want = rd_slug((string)get('cat'));
    $active = in_array($want, array_map('rd_slug', $pills), true) ? $want : 'all';
    $page = count($photos) > 30 ? 24 : 0;   // "Load more" only for 30+ photos ?>
    <section class="rd-section rd-gal" data-rd-gallery data-page="<?= $page ?>">
      <div class="rd-container">
        <?php if (trim((string)($s['title'] ?? '')) !== ''): ?><?php rd_sechead($s['title'], (string)($s['subtitle'] ?? '')); ?><?php endif; ?>
        <?php if (!$photos): ?><p class="rd-gal__empty">Photos coming soon.</p><?php else: ?>
        <div class="rd-gal__pills" role="toolbar" aria-label="Filter photos by category">
          <button type="button" class="rd-gal__pill<?= $active === 'all' ? ' is-active' : '' ?>" data-filter="all" aria-pressed="<?= $active === 'all' ? 'true' : 'false' ?>">All</button>
          <?php foreach ($pills as $c): $k = rd_slug($c); ?>
          <button type="button" class="rd-gal__pill<?= $active === $k ? ' is-active' : '' ?>" data-filter="<?= e($k) ?>" aria-pressed="<?= $active === $k ? 'true' : 'false' ?>"><?= e($c) ?></button>
          <?php endforeach; ?>
        </div>
        <div class="rd-gal__grid" data-rd-gal-grid>
          <?php foreach ($photos as $i => $p): $src = ltrim($p['src'], '/'); $sz = @getimagesize(ROOT_PATH.'/'.$src) ?: [4, 3];
                $t640 = rd_gallery_thumb($src, 640); $t1280 = rd_gallery_thumb($src, 1280); $cap = $p['caption'] !== '' ? $p['caption'] : $p['category']; ?>
          <a class="rd-gal__tile" href="<?= e(base_url($src)) ?>" data-cat="<?= e(rd_slug($p['category'])) ?>" data-caption="<?= e($cap) ?>" style="--ar:<?= (int)$sz[0] ?>/<?= (int)$sz[1] ?>" aria-label="<?= e($cap) ?> — open photo">
            <img src="<?= e(base_url($t640 ?: $src)) ?>"<?php if ($t640 && $t1280): ?> srcset="<?= e(base_url($t640)) ?> 640w, <?= e(base_url($t1280)) ?> 1280w" sizes="(max-width: 600px) 50vw, (max-width: 1024px) 50vw, 25vw"<?php endif; ?> alt="<?= e($p['alt'] !== '' ? $p['alt'] : $cap) ?>" width="<?= (int)$sz[0] ?>" height="<?= (int)$sz[1] ?>" loading="<?= $i < 8 ? 'eager' : 'lazy' ?>" decoding="async">
            <span class="rd-gal__cap"><span><?= e($cap) ?></span><svg class="rd-ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          </a>
          <?php endforeach; ?>
        </div>
        <p class="rd-gal__empty" data-rd-gal-empty hidden>No photos in this category yet.</p>
        <div class="rd-gal__more" data-rd-gal-morewrap hidden><button type="button" class="rd-btn rd-btn--line" data-rd-gal-more>Load more photos</button></div>
        <?php endif; ?>
        <!-- REPLACE CONTENT: closing call-to-action -->
        <div class="rd-gal__cta">
          <p class="rd-eyebrow">Your stay</p>
          <h2>Picture yourself here</h2>
          <p>Five rooms, one kitchen garden and a view that changes by the hour. Send us an enquiry for availability and our best rates, or ask us anything.</p>
          <div class="rd-gal__ctabtns">
            <a class="rd-btn rd-btn--dark rd-btn--arrow" <?= rd_enquiry_attrs() ?>><span>Make an enquiry</span><?= rd_icon('arrow') ?></a>
            <a class="rd-btn rd-btn--line" href="<?= u('contact.php') ?>">Contact us</a>
          </div>
        </div>
      </div>
      <?php if ($photos): ?>
      <div class="rd-lb" data-rd-lb role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
        <div class="rd-lb__stage" data-rd-lb-stage>
          <figure class="rd-lb__fig"><img class="rd-lb__img" data-rd-lb-img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt=""><figcaption class="rd-lb__cap" data-rd-lb-cap></figcaption></figure>
        </div>
        <p class="rd-lb__count" data-rd-lb-count aria-live="polite"></p>
        <button type="button" class="rd-lb__btn rd-lb__close" data-rd-lb-close aria-label="Close photo viewer"><?= rd_icon('close') ?></button>
        <button type="button" class="rd-lb__btn rd-lb__prev" data-rd-lb-prev aria-label="Previous photo"><?= rd_icon('arrowl') ?></button>
        <button type="button" class="rd-lb__btn rd-lb__next" data-rd-lb-next aria-label="Next photo"><?= rd_icon('arrow') ?></button>
      </div>
      <?php endif; ?>
    </section>
<?php }

/* ---------- restaurant highlights ---------- */
function sec_menu_preview(array $s): void {
    if (setting('menu_enabled','1') !== '1') return;
    $lim = max(1,(int)sec_opt($s,'limit'));
    $dishes = q_all("SELECT i.*, c.name cat FROM menu_items i JOIN menu_categories c ON c.id=i.category_id WHERE i.active=1 AND c.active=1 ORDER BY (i.tags<>'') DESC, (i.image<>'') DESC, i.sort LIMIT $lim"); ?>
    <section><div class="container">
      <?php sec_head($s); ?>
      <?php if (!empty($s['content'])): ?><div class="rich text-center" style="max-width:760px;margin:0 auto 26px;color:#68717d"><?= $s['content'] ?></div><?php endif; ?>
      <div class="value-cards stagger">
        <?php foreach ($dishes as $d): ?>
        <div class="value-card" style="text-align:left">
          <?php if ($d['image']): ?><img src="<?= base_url($d['image']) ?>" alt="<?= e($d['name']) ?>" loading="lazy" style="width:100%;height:150px;object-fit:cover;border-radius:8px;margin-bottom:12px"><?php endif; ?>
          <h4><?= e($d['name']) ?> <span style="float:right;color:var(--c-primary)"><?= money($d['price']) ?></span></h4>
          <p style="font-size:13.5px;color:#68717d"><?= e($d['description'] ?: $d['cat']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="text-center mt-3"><a class="btn btn-primary" href="<?= u('menu.php') ?>"><i class="fa-solid fa-utensils"></i> View Full Menu &amp; Order</a></p>
    </div></section>
<?php }

/* ===================== v5.2 — Explore Mussoorie: attractions ===================== */
function attraction_categories(): array {
    return ['viewpoint'=>['Viewpoint','fa-binoculars'],'historic'=>['Historic Site','fa-landmark'],'waterfall'=>['Waterfall','fa-water'],'nature'=>['Nature & Wildlife','fa-tree'],
            'garden'=>['Garden','fa-seedling'],'lake'=>['Lake','fa-sailboat'],'market'=>['Market & Cafés','fa-mug-hot'],'adventure'=>['Adventure','fa-person-hiking'],'temple'=>['Temple','fa-place-of-worship'],'other'=>['Attraction','fa-location-dot']];
}
function attractions_fetch(string $cat = 'all', int $limit = 0): array {
    static $cols = null;
    if ($cols === null) $cols = (bool)q_val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='attractions'", [], 0);
    if (!$cols) return [];
    $w = "active=1"; $p = [];
    if ($cat === 'featured') $w .= " AND featured=1";
    elseif ($cat === 'no_waterfall') $w .= " AND category<>'waterfall'";
    elseif ($cat !== 'all' && $cat !== '') { $w .= " AND category=?"; $p[] = $cat; }
    return q_all("SELECT * FROM attractions WHERE $w ORDER BY sort, id".($limit > 0 ? " LIMIT ".(int)$limit : ""), $p);
}
function attraction_img(array $a): string {
    if (!empty($a['image'])) return base_url($a['image']);
    $imgs = json_decode($a['images'] ?? '[]', true) ?: [];
    if ($imgs) return base_url($imgs[0]);
    $f = attraction_default_img($a);
    return $f !== '' ? base_url($f) : '';
}
/** Round 11: a photo shipped with the site at assets/img/explore/<slug>.jpg is used when none is set in Admin → Explore Mussoorie */
function attraction_default_img(array $a): string {
    $slug = (string)($a['slug'] ?? '');
    if ($slug === '') return '';
    $f = 'assets/img/explore/'.basename($slug).'.jpg';
    return is_file(ROOT_PATH.'/'.$f) ? $f : '';
}
function attraction_dist(array $a): string { $d = trim((string)$a['distance_km']); return $d === '' ? '' : (preg_match('/^[\d.]+$/', $d) ? $d.' km' : $d.' km'); }
function attraction_maps_url(array $a): string {
    if (!empty($a['maps_url'])) return $a['maps_url'];
    $o = setting('geo_lat') && setting('geo_lng') ? setting('geo_lat').','.setting('geo_lng') : rawurlencode(setting('site_name').', Mussoorie');
    $d = (!empty($a['lat']) && !empty($a['lng'])) ? $a['lat'].','.$a['lng'] : rawurlencode($a['name'].', Mussoorie');
    return 'https://www.google.com/maps/dir/?api=1&origin='.$o.'&destination='.$d;
}
/** Detail popup + data (printed once per page) */
function attractions_modal_once(array $list): void {
    static $done = false; static $data = [];
    foreach ($list as $a) {
        $imgs = json_decode($a['images'] ?? '[]', true) ?: []; if (!empty($a['image']) && !in_array($a['image'], $imgs, true)) array_unshift($imgs, $a['image']);
        if (!$imgs && ($f = attraction_default_img($a)) !== '') $imgs = [$f];
        $data[(int)$a['id']] = ['id'=>(int)$a['id'],'name'=>$a['name'],'label'=>$a['label'] ?: (attraction_categories()[$a['category']][0] ?? 'Attraction'),'icon'=>attraction_categories()[$a['category']][1] ?? 'fa-location-dot',
            'desc'=>$a['description'],'long'=>$a['long_description'] ?: '','dist'=>attraction_dist($a),'time'=>$a['travel_time'],'imgs'=>array_map(fn($i) => base_url($i), $imgs),'maps'=>attraction_maps_url($a),'lat'=>(float)$a['lat'],'lng'=>(float)$a['lng']];
    }
    if ($done) { echo '<script>Object.assign(window.ECH_ATTR = window.ECH_ATTR || {}, '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).');</script>'; return; }
    $done = true; ?>
    <script>window.ECH_ATTR = Object.assign(window.ECH_ATTR || {}, <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);</script>
    <div class="at-modal" id="atModal" aria-hidden="true" role="dialog">
      <div class="at-box">
        <button type="button" class="at-x" data-at-close aria-label="Close">&times;</button>
        <div class="at-media"><img id="atImg" src="" alt=""><div class="at-thumbs" id="atThumbs"></div><span class="at-cat" id="atCat"></span></div>
        <div class="at-body">
          <h3 id="atName"></h3>
          <div class="at-facts"><span id="atDist"><i class="fa-solid fa-route"></i> <b></b> from the house</span><span id="atTime"><i class="fa-regular fa-clock"></i> <b></b> drive</span></div>
          <div class="at-text" id="atText"></div>
          <div class="at-actions"><a class="btn btn-primary" id="atDir" href="#" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right"></i> Get Directions</a>
            <a class="btn btn-outline" href="<?= e(wa_link('', 'Hi! Can you help us plan a visit to ')) ?>" id="atWa" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Ask Our Concierge</a></div>
        </div>
      </div>
    </div>
<?php }
/** Card used by every layout */
function attraction_card(array $a, int $n, string $layout): void {
    $img = attraction_img($a); $cats = attraction_categories(); $ci = $cats[$a['category']][1] ?? 'fa-location-dot';
    $label = $a['label'] ?: ($cats[$a['category']][0] ?? 'Attraction'); ?>
    <article class="at-card at-<?= e($layout) ?> <?= $img ? '' : 'no-img' ?>" data-at-open="<?= (int)$a['id'] ?>" tabindex="0" role="button" aria-label="<?= e($a['name']) ?>">
      <div class="at-img">
        <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($a['name']) ?>" loading="lazy"><?php else: ?><span class="at-ph"><i class="fa-solid <?= e($ci) ?>"></i></span><?php endif; ?>
        <?php if ($layout === 'featured'): ?><span class="at-num"><?= str_pad((string)$n, 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
      </div>
      <div class="at-info">
        <span class="at-tag"><i class="fa-solid <?= e($ci) ?>"></i> <?= e($label) ?></span>
        <h3><?= e($a['name']) ?></h3>
        <?php if ($layout !== 'strip'): ?><p><?= e(mb_strimwidth(strip_tags($a['description']), 0, $layout === 'featured' ? 140 : 110, '…')) ?></p><?php endif; ?>
        <div class="at-meta">
          <?php if (attraction_dist($a)): ?><span><i class="fa-solid fa-route"></i> <?= e(attraction_dist($a)) ?></span><?php endif; ?>
          <?php if ($a['travel_time']): ?><span><i class="fa-regular fa-clock"></i> <?= e($a['travel_time']) ?></span><?php endif; ?>
        </div>
        <?php /* REDESIGN: "Explore" sits outside .at-meta so it is never squeezed onto the meta row (it used to share the
                 row with distance/time, so short meta = inline button, long meta = wrapped button) */ ?>
        <span class="at-go">Explore <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </article>
<?php }
function sec_attractions(array $s): void {
    $layout = (string)sec_opt($s, 'layout'); $cat = (string)sec_opt($s, 'category'); $lim = (int)sec_opt($s, 'limit');
    $list = attractions_fetch($cat, $lim);
    // round 11: the two large photo panels of the "duo" layout go to places that have a photo
    if ($layout === 'duo') usort($list, fn($x, $y) => (attraction_img($y) !== '') <=> (attraction_img($x) !== ''));
    if (!$list) { if (function_exists('live_edit_mode') && live_edit_mode()) echo '<section><div class="container"><div class="alert" style="text-align:center">🏔 <b>Attractions</b> — nothing to show for this filter yet. Add places under Admin → Explore Mussoorie.</div></div></section>'; return; } ?>
    <section><div class="container">
      <?php if ($layout === 'duo'): ?>
        <div class="at-duo">
          <?php $n = 0; foreach (array_slice($list, 0, 2) as $a): $n++; $img = attraction_img($a); ?>
          <a class="at-duo-img reveal<?= $n === 2 ? '-right' : '-left' ?> <?= $img ? '' : 'no-img' ?>" data-at-open="<?= (int)$a['id'] ?>" href="#" style="<?= $img ? "background-image:url('".e($img)."')" : '' ?>"><span><?= e($a['name']) ?> <i class="fa-solid fa-arrow-right"></i></span></a>
          <?php if ($n === 1): ?>
          <div class="at-duo-text reveal">
            <?php sec_head($s); ?>
            <?php if (!empty($s['content'])): ?><div class="rich"><?= $s['content'] ?></div><?php endif; ?>
            <div class="at-duo-btns"><?php foreach ($list as $b): ?><button type="button" class="btn btn-outline btn-sm" data-at-open="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></button> <?php endforeach; ?></div>
            <?= sec_btns($s) ?>
          </div>
          <?php endif; endforeach; ?>
        </div>
      <?php else: ?>
        <?php if ($layout === 'strip'): ?>
          <div class="at-strip-head">
            <div><?php sec_head($s); ?></div>
            <?php if (!empty($s['content'])): ?><div class="at-strip-intro"><?= $s['content'] ?></div><?php endif; ?>
            <?php if (!empty($s['btn1_label'])): [$l1, $a1] = rd_cms_link((string)($s['btn1_url'] ?? 'explore.php'), $s['btn1_label']); ?><a class="at-strip-link" <?= $a1 ?>><?= e($l1) ?> <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="text-center"><?php sec_head($s); ?><?php if (!empty($s['content'])): ?><div class="section-intro reveal"><?= $s['content'] ?></div><?php endif; ?></div>
        <?php endif; ?>
        <?php if ($layout === 'list'): ?>
          <div class="at-list reveal">
            <?php foreach ($list as $a): ?>
            <div class="at-row" data-at-open="<?= (int)$a['id'] ?>" tabindex="0" role="button">
              <b><?= e($a['name']) ?></b><span class="at-row-desc"><?= e($a['description']) ?></span><span class="at-row-dist"><?= e(attraction_dist($a)) ?><?= $a['travel_time'] ? ' · '.e($a['travel_time']) : '' ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="at-grid at-grid-<?= e($layout) ?> stagger"><?php $n = 0; foreach ($list as $a) attraction_card($a, ++$n, $layout); ?></div>
        <?php endif; ?>
        <?php if ($layout !== 'strip' && (!empty($s['btn1_label']) || !empty($s['btn2_label']))): ?><p class="text-center mt-3"><?= sec_btns($s) ?></p><?php endif; ?>
      <?php endif; ?>
      <?php attractions_modal_once($list); ?>
    </div></section>
<?php }
function sec_attractions_map(array $s): void {
    $h = max(280, (int)sec_opt($s, 'height')); $lim = (int)sec_opt($s, 'limit');
    $list = array_values(array_filter(attractions_fetch('all', $lim), fn($a) => !empty($a['lat']) && !empty($a['lng'])));
    $all = attractions_fetch('all', $lim);
    $lat = (float)(setting('geo_lat') ?: 30.4265); $lng = (float)(setting('geo_lng') ?: 78.1465);
    $pins = array_map(fn($a) => ['id'=>(int)$a['id'],'name'=>$a['name'],'lat'=>(float)$a['lat'],'lng'=>(float)$a['lng'],'dist'=>attraction_dist($a),'cat'=>$a['category']], $list);
    static $leaflet = false; ?>
    <section><div class="container">
      <div class="at-map-wrap">
        <div class="at-map-side reveal-left">
          <?php sec_head($s); ?>
          <?php if (!empty($s['content'])): ?><div class="rich" style="color:#555e6a"><?= $s['content'] ?></div><?php endif; ?>
          <ul class="at-map-list">
            <li class="home"><i class="fa-solid fa-house-chimney"></i> <b><?= e(setting('site_name')) ?></b><small>Your base</small></li>
            <?php foreach ($all as $a): $c = attraction_categories()[$a['category']] ?? ['Attraction','fa-location-dot']; ?>
            <li data-at-pin="<?= (int)$a['id'] ?>" data-at-open="<?= (int)$a['id'] ?>" tabindex="0" role="button"><i class="fa-solid <?= e($c[1]) ?>"></i> <b><?= e($a['name']) ?></b><small><?= e(attraction_dist($a)) ?><?= $a['travel_time'] ? ' · '.e($a['travel_time']) : '' ?></small></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="at-map reveal-right" id="atMap<?= md5(json_encode($pins)) ?>" data-at-map data-h="<?= $h ?>" data-home='<?= e(json_encode(['lat'=>$lat,'lng'=>$lng,'name'=>setting('site_name')])) ?>' data-pins='<?= e(json_encode($pins, JSON_UNESCAPED_UNICODE)) ?>' style="height:<?= $h ?>px">
          <div class="at-map-fallback"><i class="fa-solid fa-map-location-dot"></i><p>Loading map…</p><a class="btn btn-outline btn-sm" href="https://www.google.com/maps/search/?api=1&query=<?= $lat ?>,<?= $lng ?>" target="_blank" rel="noopener">Open in Google Maps</a></div>
        </div>
      </div>
      <?php attractions_modal_once($all); ?>
      <?php if (!$leaflet): $leaflet = true; ?>
      <link rel="stylesheet" href="<?= base_url('assets/vendor/leaflet/leaflet.css') ?>?v=1.9.4">
      <script src="<?= base_url('assets/vendor/leaflet/leaflet.js') ?>?v=1.9.4" defer></script>
      <?php endif; ?>
    </div></section>
<?php }

/* ---------- round 14 (SEO): question-and-answer accordion on white, "which room" guide, more rooms ---------- */
/** Accordion of [question, answer] pairs (answers are plain text). Uses the same open/close script as the home FAQ tab. */
function sec_qa(array $s): void {
    static $uid = 0; $uid++;
    $items = array_values(array_filter((array)($s['items'] ?? []), fn($f) => is_array($f) && count($f) === 2));
    if (!$items) return; ?>
    <section class="rd-section rd-qa"><div class="rd-container rd-qa__wrap">
      <?php rd_sechead((string)($s['title'] ?? 'Questions'), (string)($s['subtitle'] ?? '')); ?>
      <div class="rd-faq rd-faq--light reveal">
        <?php foreach ($items as $i => [$q, $a]): $qid = 'rdqa'.$uid.'-'.$i; ?>
        <div class="rd-faq__item">
          <h3 class="rd-faq__h"><button type="button" class="rd-faq__q" aria-expanded="false" aria-controls="<?= $qid ?>"><span><?= e($q) ?></span><i class="rd-faq__icon" aria-hidden="true"></i></button></h3>
          <div class="rd-faq__a" id="<?= $qid ?>" role="region"><div><p><?= e($a) ?></p></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($s['content'])): ?><p class="rd-qa__note reveal"><?= $s['content'] ?></p><?php endif; ?>
    </div></section>
<?php }
/** Rooms page: which room suits which trip (links to the room pages) */
function sec_room_guide(array $s): void {
    $by = []; foreach (public_rooms() as $r) $by[$r['slug']] = $r;
    $link = fn(string $slug) => isset($by[$slug]) ? '<a href="'.u('room.php?slug='.$slug).'">'.e($by[$slug]['name']).'</a>' : '';
    $rows = [
      ['A four-poster for two', $link('victorian-suite-1')],
      ['Our largest room, with a TV lounge wall', $link('victorian-suite-2')],   // round 15: Victorian Suite 2 has a king bed, not a four-poster
      ['Mountain views and a pet-friendly stay', $link('elizabeth-lodge')],
      ['A wooden tree house, with your dog if you like', $link('tree-house-1').' or '.$link('tree-house-2')],
      ['Garden and sunset views', $link('tree-house-2')],
      ['A family or a group of four', $link('charlotte-tree-house-duplex')],   // round 19
    ]; ?>
    <section class="rd-section rd-guide"><div class="rd-container rd-guide__grid">
      <div class="rd-guide__intro reveal">
        <h2 class="rd-guide__title"><?= e($s['title'] ?? 'Which Room Is Right for You?') ?></h2>
        <p>Check-in is at <?= e(setting('checkin_time') ? date('g:i A', strtotime(setting('checkin_time'))) : '2:00 PM') ?> and check-out is at <?= e(setting('checkout_time') ? date('g:i A', strtotime(setting('checkout_time'))) : '11:00 AM') ?>. Tell us your dates and who is travelling, and we will recommend a room.</p>
      </div>
      <dl class="rd-guide__list reveal">
        <?php foreach ($rows as [$want, $rooms]): if (strip_tags($rooms) === '' ) continue; ?>
        <div class="rd-guide__row"><dt><?= e($want) ?></dt><dd><?= $rooms ?></dd></div>
        <?php endforeach; ?>
      </dl>
    </div></section>
<?php }
/** Room page: the other rooms as photo cards with a spec line */
function rd_more_rooms(array $cur): void {
    $others = array_values(array_filter(public_rooms(), fn($r) => $r['slug'] !== $cur['slug']));
    if (!$others) return; ?>
    <section class="rd-section rd-more"><div class="container">
      <?php rd_sechead('More Rooms to Consider', '', u('rooms.php'), 'View all rooms'); ?>
      <div class="rd-more__grid">
        <?php foreach ($others as $i => $r): $img = room_cover($r) ?: (room_images($r)[0] ?? ''); $url = u('room.php?slug='.$r['slug']);
              $spec = implode(' · ', array_filter([$r['bed_type'], $r['view_type'], $r['pets_allowed'] ? 'Pet friendly' : ''])); ?>
        <article class="rd-more__card reveal" style="--d:<?= ($i % 4) * 70 ?>ms">
          <a class="rd-more__media" href="<?= $url ?>" tabindex="-1" aria-hidden="true"><?php if ($img): ?><img src="<?= e(base_url(room_thumb($img))) ?>" alt="<?= e($r['name'] ?? '') ?>" loading="lazy" decoding="async" width="600" height="450"><?php endif; ?></a>
          <h3 class="rd-more__name"><a href="<?= $url ?>"><?= e($r['name']) ?></a></h3>
          <p class="rd-more__spec"><?= e($spec) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($cur['more'])): ?><p class="rd-more__line reveal"><?= rd_copy_links($cur['more']) ?></p><?php endif; ?>
    </div></section>
<?php }
