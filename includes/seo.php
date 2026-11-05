<?php
/**
 * ============================================================
 *  Round 17 — technical SEO for the public site
 *  ------------------------------------------------------------
 *  header.php calls seo_head($PAGE) once per page. It prints:
 *    - one canonical address per page (tracking codes and stray parameters removed; Admin → SEO "Main web address" wins)
 *    - robots (index with large image previews, or noindex for private pages), hreflang en-IN + x-default
 *    - Open Graph + Twitter cards with image size and description
 *    - preload of the page's main photo (faster Largest Contentful Paint)
 *    - Search Console / Bing verification tags (Admin → SEO)
 *    - ONE linked JSON-LD @graph: WebSite, Hotel/Resort (address, geo, amenities, rooms), WebPage,
 *      BreadcrumbList and the page's own entities (HotelRoom, FAQPage, BlogPosting, Restaurant, TouristAttraction …)
 *  Pages pass extra data in $PAGE:
 *    seo_type     home | rooms | room | faqs | blog | post | location | policy | menu | amenities | gallery | explore | contact | about | page
 *    crumbs       the visible breadcrumb trail (same shape as render_banner(): 'Label' or ['Label', url]); Home is added
 *    nodes        extra JSON-LD nodes (arrays) for this page
 *    faqs         [[question, answer], …] shown on the page → FAQPage
 *    lcp          path or URL of the first large photo (preloaded with high priority)
 *    canonical    full URL, when the page knows its own address better than the generic rule
 *    og_type      website | article, article_* for blog posts
 *    noindex      true for private pages
 * ============================================================
 */
require_once __DIR__.'/redesign_content.php';   // rd(), rd_blog_image_path(), rd_gallery_data()

/** The address every canonical, sitemap and schema URL is built on: Admin → SEO "Main web address" (e.g. https://theenglishcountryhouse.com) or this request's host */
function seo_base(): string {
    $fixed = rtrim(trim((string)setting('seo_site_url')), '/');
    if (preg_match('~^https?://[^/\s]+~i', $fixed)) return $fixed;
    // round 18: on the live domain (with or without www) the address is always https://theenglishcountryhouse.com
    if (preg_match('~^(www\.)?theenglishcountryhouse\.com$~i', (string)($_SERVER['HTTP_HOST'] ?? ''))) return rd('live_url');
    return rtrim(base_url(''), '/');
}
/** Any site URL (or path from the site root) on the main web address */
function seo_abs(string $url): string {
    if ($url === '') return '';
    $local = rtrim(base_url(''), '/');
    if (str_starts_with($url, $local)) return seo_base().substr($url, strlen($local));
    if (preg_match('~^https?://~i', $url)) return $url;
    return seo_base().'/'.ltrim($url, '/');
}
function seo_home(): string { return seo_base().'/'; }
/** Round 18: the resort's Google Maps link (Admin → SEO "Google Business Profile link", else the exact pin sent by the hotel) */
function seo_map_url(): string { return trim((string)setting('seo_gbp_url')) ?: (string)rd('maps_url'); }

/** Canonical address of the current request: the clean URL of the script plus the one parameter that picks the content */
function seo_canonical(array $P = []): string {
    if (!empty($P['canonical'])) return seo_abs($P['canonical']);
    $file = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    if ($file === 'index.php') return seo_home();
    $keep = ['room.php'=>'slug', 'post.php'=>'slug', 'page.php'=>'slug', 'blog.php'=>'category'][$file] ?? '';
    $v = $keep !== '' ? (string)($_GET[$keep] ?? '') : '';
    return seo_abs(u($file.($v !== '' && preg_match('/^[a-z0-9-]+$/i', $v) ? '?'.$keep.'='.$v : '')));
}

/** 301 to the canonical address when the same page was opened under another path
 *  (/room.php?slug=x, /room?slug=x, /room/x/, /index.php …). Query parameters other than the content one are kept. */
function seo_redirect_canonical(string $canonical): void {
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) || setting('clean_urls', '1') !== '1') return;
    if (isset($_GET['edit']) || headers_sent()) return;
    $reqPath = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $local = rtrim(base_url(''), '/');
    $rel = substr($canonical, strlen(seo_base())) ?: '/';                // "/", "/rooms", "/room/tree-house-1" …
    $want = rtrim((string)parse_url($local, PHP_URL_PATH), '/').$rel;
    if ($reqPath === $want) return;
    $q = $_GET; unset($q['slug'], $q['category']);
    raw_output();
    header('Location: '.$local.$rel.($q ? '?'.http_build_query($q) : ''), true, 301);
    exit;
}

/** Width/height of a local image (cached) */
function seo_img_size(string $path): array {
    static $c = [];
    $p = preg_replace('~^'.preg_quote(rtrim(base_url(''), '/'), '~').'/~', '', strtok($path, '?'));
    if (preg_match('~^https?://~', $p)) return [0, 0];
    if (!isset($c[$p])) { $s = @getimagesize(ROOT_PATH.'/'.ltrim($p, '/')); $c[$p] = $s ? [(int)$s[0], (int)$s[1]] : [0, 0]; }
    return $c[$p];
}
function seo_image_obj(string $path, string $caption = ''): array {
    $o = ['@type'=>'ImageObject', 'url'=>seo_abs($path), 'contentUrl'=>seo_abs($path)];
    [$w, $h] = seo_img_size($path);
    if ($w) { $o['width'] = $w; $o['height'] = $h; }
    if ($caption !== '') $o['caption'] = $caption;
    return $o;
}
function seo_text(string $html, int $max = 0): string {
    $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', '</p> ', '</li> '], $html)), ENT_QUOTES, 'UTF-8')));
    if ($max && mb_strlen($t) > $max) $t = rtrim(mb_substr($t, 0, $max - 1)).'…';
    return $t;
}
function seo_phone(): string {
    $d = preg_replace('/\D/', '', (string)setting('contact_phone'));
    if (strlen($d) === 10) $d = '91'.$d;
    return $d !== '' ? '+'.$d : '';
}
function seo_id(string $frag): string { return seo_home().'#'.$frag; }

/** Location pages: the place each one targets (for "about" in the page data) */
function seo_location_places(): array {
    $mussoorie = ['@type'=>'City', 'name'=>'Mussoorie', 'containedInPlace'=>['@type'=>'State', 'name'=>'Uttarakhand']];
    return [
        'hotel-near-dhanaulti' => ['@type'=>'Place', 'name'=>'Dhanaulti', 'containedInPlace'=>['@type'=>'State', 'name'=>'Uttarakhand'], 'sameAs'=>'https://en.wikipedia.org/wiki/Dhanaulti'],
        'resort-near-mussoorie-mall-road' => ['@type'=>'TouristAttraction', 'name'=>'Mall Road, Mussoorie', 'geo'=>['@type'=>'GeoCoordinates', 'latitude'=>30.4597717, 'longitude'=>78.0644723], 'containedInPlace'=>$mussoorie],
        'stay-near-jabarkhet-nature-reserve' => ['@type'=>'TouristAttraction', 'name'=>'Jabarkhet Nature Reserve', 'containedInPlace'=>$mussoorie],
    ];
}

/** The resort itself — the same node on every page */
function seo_hotel_node(): array {
    $name = (string)setting('site_name');
    $imgs = array_values(array_filter([rd('hero_poster'), 'assets/img/redesign/about-us.jpg', 'assets/img/amenities/pool-1.jpg', 'assets/img/amenities/outdoor-jacuzzi-1.jpg'], fn($p) => is_file(ROOT_PATH.'/'.$p)));
    foreach (array_slice(public_rooms(), 0, 5) as $r) if ($r['cover_image'] !== '') $imgs[] = $r['cover_image'];
    $logo = is_file(ROOT_PATH.'/assets/img/brand/logo.png') ? 'assets/img/brand/logo.png' : (string)setting('logo_header');
    $h = [
        '@type'=>['Hotel', 'Resort'], '@id'=>seo_id('hotel'),
        'name'=>$name, 'alternateName'=>array_values(array_unique([rd('hero_brand'), $name.' Mussoorie'])),
        'description'=>(string)setting('seo_desc'),
        'url'=>seo_home(),
        'logo'=>seo_image_obj($logo) + ['@id'=>seo_id('logo')],
        'image'=>array_map(fn($p) => seo_abs($p), array_values(array_unique($imgs))),
        'telephone'=>seo_phone(), 'email'=>(string)setting('contact_email'),
        'address'=>['@type'=>'PostalAddress', 'streetAddress'=>'Village Kaplani, Dhanaulti Road', 'addressLocality'=>'Mussoorie',
                    'addressRegion'=>'Uttarakhand', 'postalCode'=>'248179', 'addressCountry'=>'IN'],
        'priceRange'=>(string)setting('seo_price_range', '₹₹₹'),
        'currenciesAccepted'=>'INR',
        'paymentAccepted'=>implode(', ', array_values((array)rd('payment_icons', []))),   // the cards shown in the footer
        'checkinTime'=>(string)setting('checkin_time', '14:00'), 'checkoutTime'=>(string)setting('checkout_time', '11:00'),
        'numberOfRooms'=>rooms_count(),   // round 19: the duplex is two of these rooms booked together
        'petsAllowed'=>(bool)array_filter(public_rooms(), fn($r) => !empty($r['pets_allowed'])),
        'openingHoursSpecification'=>['@type'=>'OpeningHoursSpecification', 'dayOfWeek'=>['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'], 'opens'=>'00:00', 'closes'=>'23:59'],
    ];
    if (setting('geo_lat') && setting('geo_lng')) {
        $h['geo'] = ['@type'=>'GeoCoordinates', 'latitude'=>(float)setting('geo_lat'), 'longitude'=>(float)setting('geo_lng')];
    }
    $h['hasMap'] = seo_map_url();   // round 18: the resort's exact Google Maps pin
    // amenities: the 16 on the Amenities page + what every room has (round 18: no breakfast; it depends on the package)
    $feat = [];
    require_once __DIR__.'/sections.php';   // amenity_data()
    foreach (amenity_data() as $a) $feat[] = $a['name'];
    foreach (['Pet-friendly rooms', 'Bathtub in every room', 'Farm-to-table restaurant'] as $x) $feat[] = $x;
    $h['amenityFeature'] = array_map(fn($n) => ['@type'=>'LocationFeatureSpecification', 'name'=>$n, 'value'=>true], array_values(array_unique($feat)));
    $h['containsPlace'] = array_map(fn($r) => ['@type'=>'HotelRoom', '@id'=>seo_abs(u('room.php?slug='.$r['slug'])).'#room', 'name'=>$r['name'], 'url'=>seo_abs(u('room.php?slug='.$r['slug']))], public_rooms());
    if (setting('menu_enabled', '1') === '1') $h['containsPlace'][] = ['@type'=>'Restaurant', '@id'=>seo_id('restaurant'), 'name'=>$h['name'].' Restaurant', 'url'=>seo_abs(u('menu.php'))];
    $same = [];
    foreach (['instagram', 'youtube', 'x', 'linkedin'] as $k) if (($s = rd_social_url($k)) !== '') $same[] = preg_replace('/\?.*$/', '', $s);
    $same[] = seo_map_url();
    foreach (preg_split('/\s+/', trim((string)setting('seo_same_as'))) as $s) if (preg_match('~^https?://~', $s)) $same[] = $s;
    foreach ((array)rd('listing_urls', []) as $s) $same[] = $s;   // round 18: MakeMyTrip, Agoda, TripAdvisor, Goibibo, EaseMyTrip
    if ($same) $h['sameAs'] = array_values(array_unique($same));
    // round 17: no aggregateRating here. The three quotes on the site are hand-entered, and Google does not allow a business
    // to rate itself with reviews it hosts ("self-serving reviews"); real Google reviews show from the Business Profile.
    return $h;
}

/** A room (used on its own page, and referenced from the Rooms page and the hotel) */
function seo_room_node(array $r): array {
    $url = seo_abs(u('room.php?slug='.$r['slug']));
    $imgs = room_images($r);
    $n = ['@type'=>'HotelRoom', '@id'=>$url.'#room', 'name'=>$r['name'], 'url'=>$url,
          'description'=>seo_text((string)($r['description'] ?: $r['short_desc']), 600),
          'image'=>array_map(fn($i) => seo_image_obj($i, room_photo_alt($r, $i)), array_slice($imgs, 0, 8)),
          'containedInPlace'=>['@id'=>seo_id('hotel')],
          'petsAllowed'=>!empty($r['pets_allowed']),
          'occupancy'=>['@type'=>'QuantitativeValue', 'minValue'=>1, 'maxValue'=>max((int)$r['base_occupancy'], (int)$r['max_adults']), 'unitText'=>'adults']];
    if (($r['bed_type'] ?? '') !== '') $n['bed'] = ['@type'=>'BedDetails', 'numberOfBeds'=>preg_match('/^(two|2)\b/i', $r['bed_type']) ? 2 : 1, 'typeOfBed'=>trim(preg_replace('/^(two|2)\s+/i', '', $r['bed_type']))];
    if (!empty($r['baths'])) $n['numberOfBathroomsTotal'] = (int)$r['baths'];   // round 19
    if (preg_match('/([\d.]+)/', (string)$r['room_size'], $m)) $n['floorSize'] = ['@type'=>'QuantitativeValue', 'value'=>(float)$m[1], 'unitCode'=>'FTK', 'unitText'=>'sq ft'];
    $feat = [];
    if (($r['view_type'] ?? '') !== '') $feat[] = $r['view_type'];
    $feat[] = 'Bathtub';
    $ids = array_map('intval', json_decode($r['amenity_ids'] ?: '[]', true) ?: []);
    if ($ids) foreach (q_all("SELECT name FROM amenities WHERE active=1 AND id IN (".implode(',', $ids).")") as $a) $feat[] = $a['name'];
    $n['amenityFeature'] = array_map(fn($x) => ['@type'=>'LocationFeatureSpecification', 'name'=>$x, 'value'=>true], array_values(array_unique($feat)));
    return $n;
}

/** FAQPage node from question/answer pairs */
function seo_faq_node(array $pairs, string $id, string $url): ?array {
    $q = [];
    foreach ($pairs as $p) {
        [$qq, $aa] = isset($p['question']) ? [$p['question'], $p['answer']] : [$p[0] ?? '', $p[1] ?? ''];
        $aa = seo_text(function_exists('strip_free_cancellation') ? strip_free_cancellation((string)$aa) : (string)$aa);
        if (trim((string)$qq) !== '' && $aa !== '') $q[] = ['@type'=>'Question', 'name'=>seo_text((string)$qq), 'acceptedAnswer'=>['@type'=>'Answer', 'text'=>$aa]];
    }
    return $q ? ['@type'=>'FAQPage', '@id'=>$id, 'url'=>$url, 'mainEntity'=>$q, 'inLanguage'=>'en-IN', 'isPartOf'=>['@id'=>seo_id('website')]] : null;
}

/** Breadcrumb trail as [[label, url], …] with Home first */
function seo_crumbs(array $crumbs, string $canonical): array {
    $out = [['Home', seo_home()]];
    $n = count($crumbs);
    foreach (array_values($crumbs) as $i => $c) {
        if (is_array($c)) $out[] = [(string)$c[0], seo_abs((string)$c[1])];
        else $out[] = [(string)$c, $i === $n - 1 ? $canonical : ''];
    }
    return array_values(array_filter($out, fn($c) => $c[1] !== '' && $c[0] !== ''));
}

/** The head block: canonical, robots, social cards, preload, verification, JSON-LD */
function seo_head(array $P, string $title, string $desc): void {
    $canonical = seo_canonical($P);
    $noindex = !empty($P['noindex']);
    $type = (string)($P['seo_type'] ?? 'page');
    $site = (string)setting('site_name');
    $ogImgPath = (string)(($P['og_image'] ?? '') ?: ($P['lcp'] ?? ''));
    if ($ogImgPath === '' || !is_file(ROOT_PATH.'/'.ltrim(preg_replace('~^'.preg_quote(rtrim(base_url(''), '/'), '~').'/~', '', $ogImgPath), '/'))) $ogImgPath = rd('hero_poster');
    $ogImg = seo_abs(preg_match('~^https?://~', $ogImgPath) ? $ogImgPath : base_url($ogImgPath));
    [$ow, $oh] = seo_img_size($ogImgPath);
    $ogAlt = (string)($P['og_image_alt'] ?? $title);
    $ogType = (string)($P['og_type'] ?? 'website');

    if (!$noindex) echo '<link rel="canonical" href="'.e($canonical).'">'."\n";
    echo '<meta name="robots" content="'.($noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1').'">'."\n";
    if (!$noindex) {
        echo '<link rel="alternate" hreflang="en-IN" href="'.e($canonical).'">'."\n";
        echo '<link rel="alternate" hreflang="x-default" href="'.e($canonical).'">'."\n";
    }
    if (($v = trim((string)setting('seo_gsc_verify'))) !== '') echo '<meta name="google-site-verification" content="'.e($v).'">'."\n";
    if (($v = trim((string)setting('seo_bing_verify'))) !== '') echo '<meta name="msvalidate.01" content="'.e($v).'">'."\n";
    // Open Graph (WhatsApp, LinkedIn, X, Pinterest, iMessage …) + Twitter card
    $og = ['og:locale'=>'en_IN', 'og:type'=>$ogType, 'og:site_name'=>$site, 'og:title'=>$title, 'og:description'=>$desc, 'og:url'=>$canonical,
           'og:image'=>$ogImg, 'og:image:secure_url'=>str_starts_with($ogImg, 'https://') ? $ogImg : '', 'og:image:width'=>$ow ?: '', 'og:image:height'=>$oh ?: '',
           'og:image:alt'=>$ogAlt, 'og:image:type'=>preg_match('/\.png$/i', $ogImg) ? 'image/png' : 'image/jpeg'];
    foreach ($og as $k => $v) if ((string)$v !== '') echo '<meta property="'.$k.'" content="'.e($v).'">'."\n";
    foreach ((array)($P['article'] ?? []) as $k => $vals) foreach ((array)$vals as $v) if ((string)$v !== '') echo '<meta property="article:'.e($k).'" content="'.e($v).'">'."\n";
    foreach (['twitter:card'=>'summary_large_image', 'twitter:title'=>$title, 'twitter:description'=>$desc, 'twitter:image'=>$ogImg, 'twitter:image:alt'=>$ogAlt] as $k => $v)
        echo '<meta name="'.$k.'" content="'.e($v).'">'."\n";
    // Largest Contentful Paint: start downloading the first big photo with the HTML instead of after the CSS
    if (!empty($P['lcp'])) {
        $l = preg_match('~^https?://~', $P['lcp']) ? $P['lcp'] : base_url($P['lcp']);
        $set = !empty($P['lcp_srcset']) ? ' imagesrcset="'.e($P['lcp_srcset']).'" imagesizes="'.e((string)($P['lcp_sizes'] ?? '100vw')).'"' : '';
        echo '<link rel="preload" as="image" href="'.e($l).'"'.$set.' fetchpriority="high">'."\n";
    }

    if ($noindex) return;
    // ---- JSON-LD graph ----
    $title0 = preg_replace('/\s*[|—–-]\s*(The )?English Country House.*$/iu', '', $title) ?: $title;
    $pageTypes = ['home'=>'WebPage', 'about'=>'AboutPage', 'contact'=>'ContactPage', 'faqs'=>'FAQPage', 'rooms'=>'CollectionPage',
                  'blog'=>'CollectionPage', 'gallery'=>'ImageGallery', 'explore'=>'CollectionPage', 'room'=>'ItemPage', 'post'=>'WebPage'];
    $wp = ['@type'=>$pageTypes[$type] ?? 'WebPage', '@id'=>$canonical.'#webpage', 'url'=>$canonical, 'name'=>$title, 'headline'=>$title0, 'description'=>$desc,
           'isPartOf'=>['@id'=>seo_id('website')], 'inLanguage'=>'en-IN',
           'primaryImageOfPage'=>seo_image_obj($ogImgPath), 'about'=>['@id'=>seo_id('hotel')]];
    if (!empty($P['modified'])) $wp['dateModified'] = date('c', is_numeric($P['modified']) ? (int)$P['modified'] : strtotime((string)$P['modified']));
    $graph = [
        ['@type'=>'WebSite', '@id'=>seo_id('website'), 'url'=>seo_home(), 'name'=>$site, 'alternateName'=>rd('hero_brand'),
         'description'=>(string)setting('seo_desc'), 'publisher'=>['@id'=>seo_id('hotel')], 'inLanguage'=>'en-IN'],
        seo_hotel_node(),
    ];
    if ($type !== 'home') {
        $bc = seo_crumbs((array)($P['crumbs'] ?? []), $canonical);
        if (count($bc) > 1) {
            $graph[] = ['@type'=>'BreadcrumbList', '@id'=>$canonical.'#breadcrumb', 'itemListElement'=>array_map(fn($i, $c) => ['@type'=>'ListItem', 'position'=>$i + 1, 'name'=>$c[0], 'item'=>$c[1]], array_keys($bc), $bc)];
            $wp['breadcrumb'] = ['@id'=>$canonical.'#breadcrumb'];
        }
    }
    if ($type === 'faqs' && !empty($P['faqs'])) {   // the FAQs page itself is the FAQPage
        $f = seo_faq_node($P['faqs'], $canonical.'#webpage', $canonical);
        if ($f) $wp['mainEntity'] = $f['mainEntity'];
    } elseif (!empty($P['faqs']) && ($f = seo_faq_node($P['faqs'], $canonical.'#faq', $canonical))) {
        $graph[] = $f;
        $wp['hasPart'] = ['@id'=>$canonical.'#faq'];
    }
    if (!empty($P['main_entity'])) $wp['mainEntity'] = ['@id'=>$P['main_entity']];
    if (in_array($type, ['about', 'contact', 'home', 'amenities'], true)) $wp['mainEntity'] = ['@id'=>seo_id('hotel')];
    if (!empty($P['webpage'])) $wp = array_merge($wp, $P['webpage']);
    if ($type === 'location' && ($pl = seo_location_places()[(string)($P['slug'] ?? '')] ?? null)) $wp['about'] = [['@id'=>seo_id('hotel')], $pl];
    $graph[] = $wp;
    foreach ((array)($P['nodes'] ?? []) as $n) if (is_array($n) && $n) $graph[] = $n;
    echo '<script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org', '@graph'=>seo_clean($graph)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)."</script>\n";
}

/** robots.txt as shipped (round 17): private areas and one-off pages are kept out of search; everything else, CSS/JS and photos are open */
function robots_default(): string {
    return "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /team/\nDisallow: /api/\nDisallow: /account/\nDisallow: /cron/\nDisallow: /install/\n"
         ."Disallow: /pay\nDisallow: /booking-done\nDisallow: /invoice\nDisallow: /unsubscribe\nDisallow: /*?*edit=1\n\nSitemap: {BASE_URL}/sitemap.xml";
}

/** Drop empty values (null, '', []) from the JSON-LD so validators see no blank properties */
function seo_clean(array $a): array {
    $out = [];
    foreach ($a as $k => $v) {
        if (is_array($v)) $v = seo_clean($v);
        if ($v === null || $v === '' || $v === []) continue;
        $out[$k] = $v;
    }
    return array_is_list($a) ? array_values($out) : $out;
}

/** X-Robots-Tag for private pages (sent before any output) */
function seo_headers(array $P): void {
    if (headers_sent()) return;
    if (!empty($P['noindex'])) header('X-Robots-Tag: noindex, follow');
    // v21: HSTS from PHP. The .htaccess rule (env=HTTPS) never reached browsers on the live host (audit 2 Nov 2026).
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
          || (string)($_SERVER['REQUEST_SCHEME'] ?? '') === 'https' || (string)($_SERVER['SERVER_PORT'] ?? '') === '443';
    if ($https) header('Strict-Transport-Security: max-age=31536000');
}
