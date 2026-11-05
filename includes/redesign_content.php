<?php
/**
 * ============================================================
 *  REDESIGN — presentation content & fallbacks
 *  ------------------------------------------------------------
 *  Presentation-only file added by the 2026 restyle. No database
 *  access, no business logic. Everything here is either copy that
 *  has no home in the CMS yet, or a fallback shown only when the
 *  CMS has no data for a section.
 *
 *  Search for "REPLACE CONTENT" to find every placeholder.
 * ============================================================
 */

function rd_content(): array {
    static $C = null;
    if ($C !== null) return $C;
    $C = [
        /* ---- announcement bar (centre text) ---- REPLACE CONTENT */
        'announcement'   => 'Enquire direct for our best rates',   // round 10: "· free cancellation" removed (round 4: was "Book direct…")

        /* ---- logo on the white navbar ----
           Settings → logo_header is the LIGHT logo (made for the old dark header),
           so the white navbar uses the dark logo file. Leave '' to use logo_header. */
        'nav_logo'       => 'assets/img/brand/logo.png',

        /* ---- hero ----
           The title / button come from the CMS hero section (Admin → Pages → Home, or the live editor).
           These are used only when the CMS field is empty. REPLACE CONTENT */
        'hero_title'     => 'Where Centuries of English Hospitality Meet Modern Comfort',
        'hero_subtitle'  => 'A boutique luxury resort on Dhanaulti Road, Mussoorie',   // round 14 (SEO): shown inside the home H1
        'hero_cta'       => 'Explore Our Rooms',
        'hero_cta_url'   => 'rooms.php',
        /* REPLACE HERO VIDEO HERE: drop the file at /assets/video/hero.mp4 — it is picked up automatically */
        'hero_video'     => 'assets/video/hero.mp4',
        'hero_poster'    => 'assets/img/redesign/hero-poster.jpg',
        /* round 11: full name shown in the home video title ("Discover Tranquility At …") */
        'hero_brand'     => 'The English Country House Resort & Spa',
        /* round 11: the hotel's Instagram profile (used in the footer when Admin → Settings has no real profile link) */
        'instagram_url'  => 'https://www.instagram.com/the_english_country_house?psln=NjBqN2x1Y21oa2Rt',
        /* round 18: the hotel's YouTube channel (footer icon + hotel data), its exact Google Maps pin, the live address
           (no www) and its travel-site listings. Admin → Settings / Admin → SEO values win when they are filled in. */
        'youtube_url'    => 'https://www.youtube.com/channel/UC2gqY5kRhs_b_S_DHC7TRkA',
        /* round 19: note under the list on the Amenities page */
        'amenities_note' => 'Please note: some of these amenities are chargeable. Ask us for details when you enquire.',
        'maps_url'       => 'https://maps.app.goo.gl/wir4DvE8x6ssCw98A',
        'live_url'       => 'https://theenglishcountryhouse.com',
        'listing_urls'   => [
            'https://www.makemytrip.com/hotels/the_english_country_house-details-mussoorie.html',
            'https://www.agoda.com/en-in/the-english-country-house/hotel/mussoorie-in.html',
            'https://www.tripadvisor.in/Hotel_Review-g297687-d33245995-Reviews-The_English_Country_House_Resort_Spa-Dehradun_Dehradun_District_Uttarakhand.html',
            'https://www.goibibo.com/hotels/the-english-country-house-hotel-in-mussoorie-3626688042859743024/',
            'https://www.easemytrip.com/hotels/the-english-country-house-8113868/',
        ],

        /* ---- section headings used when the CMS section has none ---- */
        'rooms_title'     => 'Our Rooms',
        'amenities_title' => 'Amenities & Services',
        'amenities_sub'   => 'An outdoor jacuzzi, pool, sauna and more: everything you need for a slow, restorative stay in the hills.',   // round 14 (SEO document)
        'reviews_title'   => 'What Our Guests Say',
        
        /* ---- About / Sustainability / FAQ tabs ---- REPLACE CONTENT */
        'about_image'     => 'assets/img/redesign/about.jpg',
        'about_fallback_title' => 'A Country House in the Hills',
        'about_fallback'  => '<p>The English Country House is a boutique retreat on the Dhanaulti Road above Mussoorie: five individually styled rooms, a farm-to-table kitchen and gardens that look out across the Himalayan foothills.</p>',
        'sustain_title'   => 'Caring for the Hills We Call Home',
        // round 14: SEO document wording; the earlier placeholder claimed practices nobody has confirmed (rainwater, LED, filtered water)
        'sustain_text'    => '<p>We keep things small and close to the source. The vegetables in our kitchen come from the Polly House, our own organic greenhouse, and an on-site charging station welcomes electric cars.</p>',
        'faq_fallback'    => [
            ['question'=>'What are the check-in and check-out times?', 'answer'=>'Check-in is from 2:00 PM and check-out is until 11:00 AM.'],
            ['question'=>'Are pets allowed?', 'answer'=>'Selected rooms are pet friendly. Look for the "Pet friendly" note on each room.'],
            ['question'=>'How far is the house from Mussoorie Mall Road?', 'answer'=>'About 30 minutes by car along the Dhanaulti Road.'],
            ['question'=>'Can I cancel my booking?', 'answer'=>'Please see our refund policy for the cancellation terms.'],
        ],

        /* ---- amenity tiles used only if no amenity has a photo yet ---- REPLACE CONTENT */
        'amenity_fallback' => [
            ['name'=>'Spa & Wellness',       'url'=>'amenities.php', 'image'=>''],
            ['name'=>'Fine Dining',          'url'=>'menu.php',      'image'=>''],
            ['name'=>'Gardens & Grounds',    'url'=>'gallery.php',   'image'=>''],
            ['name'=>'Weddings & Events',    'url'=>'contact.php',   'image'=>''],
            ['name'=>'Meetings & Retreats',  'url'=>'contact.php',   'image'=>''],
            ['name'=>'Other Services',       'url'=>'amenities.php', 'image'=>''],
        ],

        /* testimonials: only genuine rows from the reviews table are shown (round 3 — the demo fallback was removed) */

        /* ---- home Welcome section (round 2) ----
           Eyebrow, heading, text and button come from the home page's CTA section in the CMS when filled in;
           these are the fallbacks. REPLACE CONTENT */
        'welcome_eyebrow' => 'Welcome',
        'welcome_title'   => 'Welcome to the English Country House',
        'welcome_accent'  => 'English Country House',          // the phrase set in italic serif inside the heading
        'welcome_text'    => '<p>A boutique luxury resort on the Dhanaulti Road with five individually styled rooms, an outdoor jacuzzi, and a kitchen that cooks from its own greenhouse. Slow mornings, long dinners and a team that remembers how you take your tea.</p>',   // round 14 (SEO document)
        'welcome_cta'     => 'Make an enquiry',                    // round 4: was "Book your stay today"
        'welcome_badge'   => 'Make an enquiry · Make an enquiry · ',
        'welcome_img_main'  => 'assets/img/redesign/welcome-main.jpg',
        'welcome_img_small' => 'assets/img/redesign/welcome-small.jpg',
        // round 14: alt text describing what the photos show
        'welcome_alt_main'  => 'Bedroom with a timber ceiling and a window over the hills at The English Country House, Mussoorie',
        'welcome_alt_small' => 'Outdoor dining deck under string lights at dusk at The English Country House, Mussoorie',
        'about_alt'         => 'Bonfire with wooden chairs above the hills at dusk at The English Country House, Mussoorie',
        /* round 16: the About page photo (owner's "About us Photo"); database update v16 puts it on the About page's image + text section */
        'about_page_image'  => 'assets/img/redesign/about-us.jpg',
        'about_page_alt'    => 'The English Country House at dusk: the lit house above the swimming pool, with the hillside behind, Mussoorie',
        /* stat strip: 'auto' values are counted from the database (rooms, amenities, review rating); the rest are dummy numbers */
        'welcome_stats'   => [
            ['value'=>'auto:rooms',     'label'=>'Individually styled rooms'],
            ['value'=>'auto:amenities', 'label'=>'Amenities & experiences', 'suffix'=>'+'],   // round 16: "16+", the house has more than the ones with photos
            ['value'=>'auto:rating',    'label'=>'Average guest rating', 'suffix'=>'★'],
            ['value'=>'2000',           'label'=>'Metres above sea level', 'suffix'=>'m'],   // REPLACE CONTENT (dummy)
        ],

        /* ---- WhatsApp enquiry (round 4) ----
           Every public "Book" button now opens the enquiry form, which builds a WhatsApp message in the browser and sends
           the guest to wa.me — nothing is saved to the database. The old booking system (booking.php, pay.php, the
           booking modal markup, admin) is left in place but no longer linked from the public site.
           enquiry_wa_number: CONFIGURE — international format, digits only (e.g. 91XXXXXXXXXX). Leave '' to use
           Admin → Settings → "WhatsApp number (with country code)". If neither holds a valid number the form shows a
           "WhatsApp number not configured" notice instead of sending guests to a wrong number. */
        'enquiry_wa_number'        => '',   // round 5: '' = the official number ECH_WHATSAPP_NUMBER (includes/ech_contact.php)

        /* ---- old hotel number still written in CMS text (round 4/5) ----
           The official number lives in includes/ech_contact.php (ECH_WHATSAPP_NUMBER / ECH_PHONE_DISPLAY), used by
           wa_link(), the settings reader and this file. rd_phone_start() additionally rewrites any retired number
           (ECH_OLD_NUMBERS) that is typed into CMS page text, meta descriptions or links, in the page output only. */
        'enquiry_cta'              => 'Enquire Now',

        /* ---- cinematic video page headers (hero video over the banner photo) ----
           Subtitles are used only when the page's banner subtitle is empty in the CMS. REPLACE CONTENT */
        'cine_pages'      => ['gallery', 'amenities', 'rooms', 'about', 'contact'],
        'cine_subtitles'  => [
            // round 14: keyword sublines (they sit inside the page's H1); used only when Admin → Pages has no banner subtitle
            'gallery'   => 'A photo tour of our boutique resort in Mussoorie',
            'amenities' => 'Pool, outdoor jacuzzi, sauna and slow days in Mussoorie',
            'rooms'     => 'Five uniquely themed luxury retreats in Mussoorie',
            'about'     => 'A boutique luxury resort on Dhanaulti Road, Mussoorie',
            'contact'   => 'Village Kaplani, Dhanaulti Road, Mussoorie',
        ],

        /* ---- footer ---- REPLACE CONTENT */
        'credit_html'     => 'Website By Lakshay Bansal &amp; Yash Saraswat',   // round 17: the owner's credit (round 14 had removed the "Website by Your Studio" placeholder)
        'newsletter_text' => 'Seasonal offers and news from the house, a few times a year.',
        'payment_icons'   => ['fa-cc-visa'=>'Visa', 'fa-cc-mastercard'=>'Mastercard', 'fa-cc-amex'=>'American Express', 'fa-google-pay'=>'Google Pay'],
    ];
    return $C;
}
function rd(string $key, $default = '') { return rd_content()[$key] ?? $default; }

/** Round 11: the hero title names the house in full — "The English Country House" becomes "The English Country House Resort & Spa"
    (only where the full name is not already there). */
function rd_hero_brand(string $title): string {
    return (string)preg_replace('/The English Country House(?!\s+Resort)/i', rd('hero_brand'), $title);
}
/** Round 11: footer social links. Facebook is not shown; Instagram uses the Settings value only when it is a real profile
    (the install default "https://instagram.com/" is not), otherwise the hotel's profile above. */
function rd_social_url(string $k): string {
    $v = trim((string)setting('social_'.$k));
    if ($k === 'facebook') return '';
    if ($k === 'instagram' && !preg_match('~instagram\.com/[A-Za-z0-9_.]+~i', $v)) return rd('instagram_url');
    if ($k === 'youtube' && !preg_match('~youtube\.com/[A-Za-z0-9_@]~i', $v)) return rd('youtube_url');   // round 18
    return $v;
}

/** REDESIGN (round 4): WhatsApp number for the enquiry form, digits only in international format, or '' when none is
 *  configured. The override in rd_content() wins, else the existing wa_number setting. A 10-digit number gets the 91
 *  country code, the same rule as wa_link(). */
function rd_enquiry_number(): string {
    $n = preg_replace('/\D/', '', (string)(rd('enquiry_wa_number') ?: ech_whatsapp()));
    if (strlen($n) === 10) $n = '91'.$n;
    return strlen($n) >= 11 && strlen($n) <= 15 ? $n : '';
}
/** Links that still point at the old booking page (CMS buttons such as the home hero's "Book Your Stay") become
 *  enquiry links: returns [label, attributes]. A "Book …" label reads "Enquire Now"; any other URL is untouched. */
function rd_is_booking_url(string $url): bool { return (bool)preg_match('~(^|/)booking(\.php)?($|[?#])~i', trim($url)); }
function rd_cms_link(string $url, string $label): array {
    if (!rd_is_booking_url($url)) return [$label, 'href="'.e(menu_url($url)).'"'];
    parse_str((string)parse_url($url, PHP_URL_QUERY), $qs);
    return [preg_match('/\bbook/i', $label) ? rd('enquiry_cta') : $label, rd_enquiry_attrs((int)($qs['room_id'] ?? 0))];
}
/** Rewrites retired hotel numbers left in CMS text (round 4/5). Phone settings are already mapped by setting(). */
function rd_phone_start(): void {
    static $done = false; if ($done) return; $done = true;
    ob_start('rd_phone_filter');
}
/** wa.me / api.whatsapp / tel: links get the official digits, visible text the display form. */
function rd_phone_filter(string $html): string {
    $intl = ech_whatsapp();
    foreach (ECH_OLD_NUMBERS as $old) {
        $pat = '(?:\+?91[\s-]?|0)?'.substr($old, 0, 4).'[\s-]?'.substr($old, 4, 1).'[\s-]?'.substr($old, 5, 2).'[\s-]?'.substr($old, 7);
        $pat2 = '(?:\+?91[\s-]?|0)?'.substr($old, 0, 5).'[\s-]?'.substr($old, 5);
        $html = preg_replace('~(wa\.me/|phone=|phone%3D)(?:\+?91|%2B91)?'.$old.'(?!\d)~', '${1}'.$intl, $html);
        $html = preg_replace('~tel:(?:\+?91|0)?'.$old.'(?!\d)~', 'tel:+'.$intl, $html);
        $html = preg_replace('~(?<![\d+])(?:'.$pat.'|'.$pat2.')(?!\d)~', ECH_PHONE_DISPLAY, $html);
    }
    $html = str_replace('tel:'.ECH_PHONE_DISPLAY, 'tel:+'.$intl, $html);   // tel: links built from the display form
    $html = preg_replace_callback('~href="tel:([^"]*)"~', fn($m) => 'href="tel:'.tel_digits(html_entity_decode($m[1])).'"', $html);   // round 18: tel:+918076185555
    return stripos($html, '<body') !== false ? rd_link_contacts($html) : $html;
}
/** Round 18: every phone number and email address shown as text on a page becomes a tel: / mailto: link (page text
 *  from Admin, policy pages, FAQ answers …). Text already inside a link, button, form control, script or style is left alone. */
function rd_link_contacts(string $html): string {
    $parts = preg_split('~(<!--.*?-->|<[^>]*>)~s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $skip = 0;
    foreach ($parts as $i => $p) {
        if ($p === '') continue;
        if ($p[0] === '<') {
            if (preg_match('~^<(/?)(a|script|style|textarea|title|button|select|noscript|svg)\b~i', $p, $m) && !str_ends_with($p, '/>'))
                $skip = max(0, $skip + ($m[1] === '/' ? -1 : 1));
            continue;
        }
        if ($skip > 0 || !preg_match('~@|\d{5}~', $p)) continue;
        $p = preg_replace_callback('~(?<![\w.+-])[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}(?![\w-])~',
            fn($m) => '<a href="mailto:'.$m[0].'">'.$m[0].'</a>', $p);
        $p = preg_replace_callback('~(?<![\d+\w])(?:\+91[\s-]?|0)?[6-9]\d{4}[\s-]?\d{5}(?![\d\w])~',
            fn($m) => '<a href="tel:'.tel_digits($m[0]).'">'.$m[0].'</a>', $p);
        $parts[$i] = $p;
    }
    return implode('', $parts);
}

/** href + data attributes for an "Enquire" link: JS opens the enquiry form (with the room preselected when given);
 *  without JS the link still reaches WhatsApp with a short greeting (or the contact page when no number is set). */
function rd_enquiry_attrs(int $roomId = 0): string {
    $n = rd_enquiry_number();
    $href = $n !== '' ? 'https://wa.me/'.$n.'?text='.rawurlencode("Hello English Country House,\n\nI would like to enquire about a stay at your property.") : u('contact.php');
    return 'href="'.e($href).'" data-enquire'.($roomId ? ' data-room="'.$roomId.'"' : '');
}

/** Inline SVG icons used by the redesign (small, no extra requests). */
function rd_icon(string $name): string {
    $i = [
        'phone'   => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z" fill="currentColor"/>',
        'mail'    => '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm8 7.2L4.8 7v10h14.4V7z" fill="currentColor"/>',
        'chev'    => '<path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevr'   => '<path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'user'    => '<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4 21c1.2-4 4.3-6 8-6s6.8 2 8 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'arrow'   => '<path d="M4 12h15m-5-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'arrowl'  => '<path d="M20 12H5m5-6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'cal'     => '<rect x="3.5" y="5" width="17" height="15.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 10h17M8 3v4m8-4v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'globe'   => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="rd-ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'.($i[$name] ?? '').'</svg>';
}

/**
 * Extra room photos by folder convention (presentation fallback, used only when a room has fewer than 2 photos in the CMS):
 * drop images into assets/img/rooms/{room-slug}/ (jpg, jpeg, png or webp) and they join that room's card slideshow.
 */
function rd_room_extra_images(string $slug): array {
    if (!preg_match('/^[a-z0-9-]+$/i', $slug)) return [];
    $files = glob(ROOT_PATH.'/assets/img/rooms/'.$slug.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    sort($files);
    return array_map(fn($f) => 'assets/img/rooms/'.$slug.'/'.basename($f), $files);
}

/**
 * Gallery links (round 2): every "Gallery" link in the header, drawer and footer quick links goes to the site's own
 * /gallery page. Items are read from the existing settings exactly as before; this only rewrites the URL of an item
 * that is labelled or linked as a gallery (e.g. an external photo album) and adds a Gallery item when none exists.
 * The saved settings are not changed.
 */
function rd_gallery_menu(array $items, array $after = ['amenities.php', 'amenities']): array {
    $found = false;
    foreach ($items as &$it) {
        if (preg_match('~gallery~i', (string)($it['label'] ?? '').' '.(string)($it['url'] ?? ''))) { $found = true; $it['url'] = 'gallery.php'; }
    }
    unset($it);
    if ($found) return $items;
    $add = ['label'=>'Gallery', 'url'=>'gallery.php', 'visible'=>1];
    foreach ($items as $i => $it) {
        if (in_array(basename((string)parse_url((string)($it['url'] ?? ''), PHP_URL_PATH)), $after, true)) { array_splice($items, $i + 1, 0, [$add]); return $items; }
    }
    $items[] = $add;
    return $items;
}

/* ---------- Gallery page helpers (presentation only) ---------- */
function rd_gallery_categories(): array { return ['Rooms', 'Dining', 'Gardens & Grounds', 'Weddings & Events', 'Spa & Wellness', 'Nearby']; }
function rd_slug(string $s): string { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace('&', 'and', $s))), '-'); }
/** curated photos from data/gallery.php */
function rd_gallery_data(): array {
    $f = ROOT_PATH.'/data/gallery.php';
    $d = is_file($f) ? (include $f) : [];
    $out = [];
    foreach (is_array($d) ? $d : [] as $g) if (!empty($g['src'])) $out[] = ['src'=>(string)$g['src'], 'category'=>rd_gallery_category((string)($g['category'] ?? '')), 'caption'=>(string)($g['caption'] ?? ''), 'alt'=>(string)($g['alt'] ?? '')];
    return $out;
}
/** map a free-text category (e.g. from Admin → Gallery) onto one of the page's pills */
function rd_gallery_category(string $c): string {
    foreach (rd_gallery_categories() as $k) if (strcasecmp($c, $k) === 0) return $k;
    $map = ['Rooms'=>'room|suite|lodge|bed|bath|cottage|tree ?house', 'Dining'=>'dining|restaurant|food|kitchen|cafe|bar|breakfast|menu',
            'Weddings & Events'=>'wedding|event|party|birthday|celebrat|anniversar|decor', 'Spa & Wellness'=>'spa|wellness|pool|jacuzzi|sauna|steam|gym|fitness|yoga',
            'Nearby'=>'nearby|explore|attraction|mussoorie|waterfall|falls|trek|lake', 'Gardens & Grounds'=>'garden|ground|lawn|exterior|outdoor|view|green|deck'];
    foreach ($map as $k => $re) if (preg_match('/'.$re.'/i', $c)) return $k;
    return 'Gardens & Grounds';
}
/** smaller copy in assets/img/gallery/{name}-{w}.jpg if it exists */
function rd_gallery_thumb(string $src, int $w): string {
    $t = 'assets/img/gallery/'.pathinfo($src, PATHINFO_FILENAME).'-'.$w.'.jpg';
    return is_file(ROOT_PATH.'/'.$t) ? $t : '';
}

/**
 * Blog photos: the Polly House greenhouse post ("Farm-To-Table: Inside Our Polly House Greenhouse" / "From Table to
 * Table Inside Our Polly House Greenhouse") always uses the supplied farm photo, even when the CMS has another image on
 * that post (round 3 request). Matched by slug or by title. Round 16: the same for "Top 10 Things to Do in Mussoorie",
 * which always uses the Mussoorie photo. Every other post keeps its own CMS image or the placeholder.
 */
function rd_blog_image(array $p, string $fallback = ''): string {
    $img = rd_blog_image_path($p);
    return $img !== '' ? base_url($img) : $fallback;
}
/** The same choice as a path from the site root ('' = no photo), for the share image and the article data on the post page */
function rd_blog_image_path(array $p): string {
    $farm = 'assets/img/redesign/blog/farm.jpg';
    $isGreenhouse = ($p['slug'] ?? '') === 'farm-to-table-polly-house'
        || preg_match('/pol+y[\s-]*house|green[\s-]*house/i', (string)($p['title'] ?? ''));
    if ($isGreenhouse && is_file(ROOT_PATH.'/'.$farm)) return $farm;
    // round 16: "Top 10 Things to Do in Mussoorie" always uses the owner's Mussoorie photo (the CMS one showed the restaurant)
    $mussoorie = 'assets/img/redesign/blog/mussoorie.jpg';
    $isTop10 = ($p['slug'] ?? '') === 'top-10-things-to-do-in-mussoorie' || preg_match('/top\s*10\s+things\s+to\s+do/i', (string)($p['title'] ?? ''));
    if ($isTop10 && is_file(ROOT_PATH.'/'.$mussoorie)) return $mussoorie;
    return (string)($p['image'] ?? '');
}

/**
 * Cinematic page header: the hero video (/assets/video/hero.mp4, if present) over the banner photo, a dark overlay,
 * eyebrow, large title, subtitle and breadcrumbs. The photo is the poster and the fallback when the video cannot play.
 * Behaviour (muted autoplay, pause when hidden/off-screen, pause button, reduced motion) is in redesign.js §8.
 * $crumbs: list of 'Label' or ['Label', url]; $attrs: extra attributes for the wrapper (e.g. data-banner for the live editor).
 */
/** Round 17 (speed): <source> tags for the hero video. Phones (under 768 px) get hero-mobile.mp4 (960 px, 2.7 MB instead of 7.4 MB)
 *  when it sits next to hero.mp4; the full video stays first so a browser that ignores "media" keeps playing the full one. */
function rd_hero_sources(string $url): string {
    $full = '<source src="'.e($url).'" type="video/mp4"';
    $mob = preg_replace('~\.mp4$~', '-mobile.mp4', ROOT_PATH.'/'.rd('hero_video'));
    if (!str_contains($url, rd('hero_video')) || !is_file($mob)) return $full.'>';
    return $full.' media="(min-width: 768px)"><source src="'.e(base_url(preg_replace('~\.mp4$~', '-mobile.mp4', rd('hero_video'))).'?v='.filemtime($mob)).'" type="video/mp4">';
}
function rd_cine_banner(string $img, string $title, string $sub = '', array $crumbs = [], string $attrs = '', string $eyebrow = 'The English Country House', bool $video = true): void {
    // REDESIGN (round 4): $video = false gives the same header as a still photo (used on room detail pages)
    $vf = ROOT_PATH.'/'.rd('hero_video');
    $vid = $video && is_file($vf) ? base_url(rd('hero_video')).'?v='.filemtime($vf) : ''; ?>
    <div class="page-banner rd-cine" <?= $attrs ?> style="background-image:url('<?= e($img) ?>')">
      <?php if ($vid): ?><div class="rd-hero"><div class="rd-hero__media"><video class="hero-video rd-hero__video" autoplay muted loop playsinline preload="auto" poster="<?= e($img) ?>" disablepictureinpicture aria-hidden="true" tabindex="-1"><?= rd_hero_sources($vid) ?></video></div><button type="button" class="rd-hero__vbtn" aria-label="Pause background video" aria-pressed="false"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path class="i-pause" d="M7 5h3v14H7zM14 5h3v14h-3z" fill="currentColor"/><path class="i-play" d="M8 5v14l11-7z" fill="currentColor"/></svg></button></div><?php endif; ?>
      <div class="pb-inner rd-cine__inner">
        <?php /* round 14: one H1 holds the eyebrow, the title and the subline, so the page heading carries the keywords;
                 the three parts look exactly as before (spans, see "round 14" in redesign.css) */ ?>
        <h1 class="rd-h1"><?php if ($eyebrow !== ''): ?><span class="rd-cine__eyebrow"><?= e($eyebrow) ?> </span><?php endif; ?>
          <span class="rd-cine__title<?= mb_strlen($title) > 14 ? ' rd-cine__title--long' : '' ?>"><?= e($title) ?></span>
          <?php if ($sub !== ''): ?><span class="rd-cine__sub"><?= e($sub) ?></span><?php endif; ?></h1>
        <div class="crumbs"><a href="<?= base_url('') ?>">Home</a>
          <?php foreach ($crumbs as $c): ?> › <?= is_array($c) ? '<a href="'.e($c[1]).'">'.e($c[0]).'</a>' : e($c) ?><?php endforeach; ?>
        </div>
      </div>
    </div>
<?php }

