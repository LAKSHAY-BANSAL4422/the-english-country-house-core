<?php
/** llms.txt — AEO/GEO: tells AI answer engines about this hotel (editable in Admin → SEO)
 *  Round 17: {AUTO} in the Admin text is replaced with a summary built from the live site (rooms, amenities, FAQs,
 *  location pages, contact), so it never goes out of date. Text around {AUTO} is kept as written. */
require_once __DIR__.'/config.php'; db();
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__.'/includes/redesign_content.php'; rd_phone_start();   // REDESIGN (round 4): current hotel number
require_once __DIR__.'/includes/seo.php';
require_once __DIR__.'/includes/sections.php';   // amenity_data()

function llms_auto(): string {
    $L = fn(string $file) => seo_abs(u($file));
    $site = setting('site_name');
    $o = '# '.rd('hero_brand')."\n\n> ".setting('seo_desc')."\n\n";
    $o .= "## Key facts\n";
    $o .= "- Address: Village Kaplani, Dhanaulti Road, Mussoorie, Uttarakhand 248179, India\n";
    $o .= '- Map: '.seo_map_url().(setting('geo_lat') ? ' (map position '.setting('geo_lat').', '.setting('geo_lng').')' : '')."\n";
    $o .= '- YouTube: '.rd_social_url('youtube')."\n";
    $o .= '- Also listed on: '.implode(', ', (array)rd('listing_urls', []))."\n";
    $o .= '- Phone and WhatsApp: '.setting('contact_phone').' · Email: '.setting('contact_email')."\n";
    $o .= '- Check-in '.setting('checkin_time', '14:00').', check-out '.setting('checkout_time', '11:00')."\n";
    $o .= '- '.rooms_count()." themed rooms; every room has a bathtub; meals depend on the package booked\n";
    foreach (public_rooms() as $r) if (!empty($r['combo'])) $o .= '- '.$r['name'].': '.implode(' and ', array_map(fn($s) => public_room($s)['name'] ?? $s, $r['combo'])).' booked together as one duplex for '.(int)$r['base_occupancy']." guests, with ".(int)$r['baths']." washrooms\n";
    $pets = array_column(array_filter(public_rooms(), fn($r) => !empty($r['pets_allowed'])), 'name');
    if ($pets) $o .= '- Pet-friendly rooms: '.implode(', ', $pets)."\n";
    $o .= '- Bookings: enquire direct by WhatsApp or phone for the best rate ('.$L('contact.php').")\n\n";
    $o .= "## Rooms\n";
    foreach (public_rooms() as $r)
        $o .= '- ['.$r['name'].']('.$L('room.php?slug='.$r['slug']).'): '.trim(implode(', ', array_filter([$r['room_size'] ? preg_replace('/\s*sq\.?\s*ft\.?/i', ' sq ft', $r['room_size']) : '', $r['bed_type'] ? $r['bed_type'].' bed' : '', $r['view_type'], !empty($r['pets_allowed']) ? 'pet friendly' : '']))).'. '.$r['short_desc']."\n";
    $o .= "\n## Amenities\n- ".implode(', ', array_column(amenity_data(), 'name'))." ([amenities](".$L('amenities.php')."))\n\n";
    $o .= "## Pages\n";
    foreach ([['Rooms', 'rooms.php'], ['Amenities', 'amenities.php'], ['Restaurant menu', 'menu.php'], ['Explore Mussoorie (places to visit, distances)', 'explore.php'],
              ['Gallery', 'gallery.php'], ['FAQs', 'faqs.php'], ['Contact and directions', 'contact.php'], ['Blog', 'blog.php'], ['About', 'about.php']] as [$t, $f])
        $o .= "- [$t](".$L($f).")\n";
    foreach (q_all("SELECT title, slug FROM pages WHERE page_type='location' AND active=1 ORDER BY menu_order, id") as $p)
        $o .= '- ['.$p['title'].']('.$L('page.php?slug='.$p['slug']).")\n";
    $faqs = q_all("SELECT question, answer FROM faqs WHERE active=1 ORDER BY category, sort, id");
    if ($faqs) {
        $o .= "\n## Frequently asked questions\n";
        foreach ($faqs as $f) $o .= '- **'.seo_text($f['question']).'** '.seo_text($f['answer'])."\n";
    }
    return $o;
}

$txt = setting('llms_txt', '{AUTO}');
if (trim($txt) === '') $txt = '{AUTO}';
if (str_contains($txt, '{AUTO}')) $txt = str_replace('{AUTO}', rtrim(llms_auto()), $txt);
echo str_replace(['{BASE_URL}', '/booking.php'], [seo_base(), '/contact.php'], $txt)."\n";
