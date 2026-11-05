<?php
/**
 * ============================================================
 *  ROUND 14 — ON-PAGE SEO CONTENT (database part)
 *  ------------------------------------------------------------
 *  Meta titles, meta descriptions and keywords for every public page, plus the page text that lives in the
 *  database (banner sublines, About page sections, location pages, FAQ answers). Applied once by database
 *  update v14 (install/schema.php) and on a fresh install (end of install/seed.php).
 *
 *  Meta tags are always written, because the SEO team supplied them for this release. Page text is only
 *  replaced while it still holds the original wording, so anything already edited in Admin → Pages is kept.
 *  Everything can be edited afterwards in Admin (Pages → SEO fields, Blog, FAQs, Settings → SEO).
 *
 *  Room pages and the Our Menu page keep their meta in code: data/rooms.php and menu.php.
 *  In page text, {URL:file.php} becomes that page's address when the page is shown (includes/page_render.php).
 * ============================================================
 */

function seo_meta(): array {
    return [
        // Home (also the site-wide default in Settings → SEO): from the SEO content document (v2), page 1
        'site' => [
            'Boutique Luxury Resort in Mussoorie | English Country House',
            "Boutique luxury resort in Mussoorie on Dhanaulti Road: themed suites with valley views, jacuzzi, sauna, pool and farm-to-table dining.",
            'boutique luxury resort in Mussoorie, resort on Dhanaulti Road, luxury resort in Mussoorie, resort with jacuzzi in Mussoorie, pet friendly resort in Mussoorie, honeymoon resort in Mussoorie',
        ],
        'pages' => [
            'home' => [
                'Boutique Luxury Resort in Mussoorie | English Country House',
                "Boutique luxury resort in Mussoorie on Dhanaulti Road: themed suites with valley views, jacuzzi, sauna, pool and farm-to-table dining.",
                'boutique luxury resort in Mussoorie, resort on Dhanaulti Road, luxury resort in Mussoorie, resort with jacuzzi in Mussoorie, pet friendly resort in Mussoorie, honeymoon resort in Mussoorie',
            ],
            // SEO content document (v2), page 2
            'rooms' => [
                'Luxury Rooms & Suites in Mussoorie | English Country House',
                'Five themed luxury rooms in Mussoorie: a four-poster suite, a Victorian suite, a pet-friendly lodge and wooden tree houses with valley and mountain views.',
                'luxury rooms in Mussoorie, valley view rooms in Mussoorie, four-poster suite in Mussoorie, pet friendly room in Mussoorie, tree house stay in Mussoorie',
            ],
            // the pages below were not in the document; written in the same style (titles up to 60 characters, descriptions up to 155)
            'about' => [
                'About Our Boutique Resort | English Country House, Mussoorie',
                "The story of The English Country House, a boutique luxury resort on Mussoorie's Dhanaulti Road: five themed rooms, a greenhouse kitchen and hill views.",
                'boutique resort in Mussoorie, about The English Country House, luxury resort on Dhanaulti Road, English country house Mussoorie',
            ],
            'amenities' => [
                'Pool, Jacuzzi & Sauna in Mussoorie | English Country House',
                'Swimming pool, outdoor jacuzzi, sauna, gym, yoga, bonfire, restaurant, games and EV charging at The English Country House on Dhanaulti Road, Mussoorie.',
                'resort with pool in Mussoorie, resort with jacuzzi in Mussoorie, sauna in Mussoorie, resort amenities Mussoorie, EV charging Mussoorie',
            ],
            'gallery' => [
                'Photo Gallery: Resort in Mussoorie | English Country House',
                'Photos of our themed rooms, tree houses, pool, outdoor jacuzzi, dining and hill views at The English Country House, Dhanaulti Road, Mussoorie.',
                'Mussoorie resort photos, luxury resort in Mussoorie gallery, tree house Mussoorie photos, resort with valley view Mussoorie',
            ],
            'explore' => [
                'Places to Visit in Mussoorie | English Country House',
                'Lal Tibba, Landour, Gun Hill, Kempty Falls, Mall Road and more: places to visit in Mussoorie, with distances from our resort on Dhanaulti Road.',
                'places to visit in Mussoorie, things to do in Mussoorie, Mussoorie sightseeing, waterfalls near Mussoorie, Landour, Lal Tibba',
            ],
            'contact' => [
                'Contact & Directions | English Country House, Mussoorie',
                'Call or WhatsApp +91 80761 85555, email info@theenglishcountryhouse.com, or find us at Village Kaplani, Dhanaulti Road, Mussoorie 248179.',
                'English Country House contact, resort on Dhanaulti Road Mussoorie, Village Kaplani Mussoorie, Mussoorie resort phone number',
            ],
            'faqs' => [
                'FAQs: Your Stay in Mussoorie | English Country House',
                'Check-in times, pet-friendly rooms, dining, the pool and jacuzzi, EV charging and events: answers for your stay at The English Country House.',
                'English Country House FAQs, pet friendly resort Mussoorie, resort check-in time Mussoorie, resort with jacuzzi Mussoorie',
            ],
            'blog' => [
                'Mussoorie Travel Blog | The English Country House',
                'Mussoorie travel guides, things to do and stories from our boutique resort on Dhanaulti Road, from the Polly House greenhouse to slow hill days.',
                'Mussoorie travel blog, things to do in Mussoorie, Mussoorie travel guide, Dhanaulti Road resort',
            ],
            'booking' => [
                'Enquire About Your Stay | The English Country House',
                'Send us your dates and we will reply on WhatsApp with availability and rates for The English Country House, a boutique resort in Mussoorie.',
                '',
            ],
            'hotel-near-dhanaulti' => [
                'Hotel & Resort Near Dhanaulti | English Country House',
                'A boutique luxury resort about 8 km from Dhanaulti: themed rooms, pool, outdoor jacuzzi, sauna and farm-to-table dining. Enquire direct.',
                'hotel near Dhanaulti, resort near Dhanaulti, stay near Dhanaulti, luxury hotel Dhanaulti road',
            ],
            'resort-near-mussoorie-mall-road' => [
                'Resort Near Mussoorie Mall Road | English Country House',
                'Stay about 30 minutes from Mussoorie Mall Road at a quiet boutique resort on Dhanaulti Road, with valley-view rooms, an outdoor jacuzzi and a pool.',
                'resort near Mussoorie Mall Road, hotel near Mall Road Mussoorie, stay near Mall Road Mussoorie',
            ],
            'stay-near-jabarkhet-nature-reserve' => [
                'Stay Near Jabarkhet Nature Reserve | English Country House',
                'A boutique resort about 10 minutes from Jabarkhet Nature Reserve: forest walks by day, then a jacuzzi, sauna and farm-to-table dinner at the house.',
                'stay near Jabarkhet Nature Reserve, hotel near Jabarkhet, resort near Jabarkhet Mussoorie',
            ],
            'terms-and-conditions' => [
                'Terms & Conditions | The English Country House',
                'The terms that apply when you enquire about or book a stay at The English Country House, Dhanaulti Road, Mussoorie.',
                '',
            ],
            'privacy-policy' => [
                'Privacy Policy | The English Country House',
                'How The English Country House collects, uses and protects the details you share when you enquire about or book a stay with us in Mussoorie.',
                '',
            ],
            'refund-policy' => [
                'Refund & Cancellation Policy | The English Country House',
                'How cancellations and refunds work for stays at The English Country House, a boutique resort on Dhanaulti Road, Mussoorie.',
                '',
            ],
        ],
        // blog posts by slug (the post title is the H1 on the page)
        'posts' => [
            'top-10-things-to-do-in-mussoorie' => [
                'Top 10 Things to Do in Mussoorie | English Country House',
                "From Camel's Back Road walks to Jabarkhet Nature Reserve: our list of the best things to do in Mussoorie, from a resort on Dhanaulti Road.",
                'things to do in Mussoorie, places to visit in Mussoorie, Mussoorie travel guide',
            ],
            'farm-to-table-polly-house' => [
                'Farm-to-Table in Mussoorie: Our Polly House Greenhouse',
                'How the Polly House, our organic greenhouse, brings fresh vegetables from the garden to the plate at our multi-cuisine restaurant in Mussoorie.',
                'farm to table Mussoorie, organic food Mussoorie, restaurant on Dhanaulti Road, Polly House',
            ],
        ],
        // blog categories by slug (blog.php adds " | The English Country House" to the title)
        'categories' => [
            'travel-guide' => ['Mussoorie Travel Guides', 'Travel guides to Mussoorie: things to do, viewpoints, waterfalls and day trips from our boutique resort on Dhanaulti Road.'],
            'resort-life'  => ['Resort Life in Mussoorie', 'Life at our boutique resort in Mussoorie: the Polly House greenhouse, our kitchen, the seasons and slow days on Dhanaulti Road.'],
        ],
    ];
}

/** Page text: [slug => [field => [original text, new text]]] — replaced only while the field still holds the original */
function seo_page_text(): array {
    $loc = fn(string $lead, string $h2, array $points, string $close) => '<p>'.$lead.'</p><h2>'.$h2.'</h2><ul><li>'.implode('</li><li>', $points).'</li></ul><p>'.$close.'</p>';
    $rooms = 'Choose a four-poster <a href="{URL:room.php?slug=victorian-suite-1}">Victorian suite</a>, the mountain-view <a href="{URL:room.php?slug=elizabeth-lodge}">Elizabeth Lodge</a> or one of our two wooden <a href="{URL:room.php?slug=tree-house-1}">tree houses</a>. Tell us your dates and we will reply on WhatsApp with availability and rates.';
    return [
        'rooms'     => ['banner_subtitle' => ['Five uniquely themed retreats', 'Five uniquely themed luxury retreats in Mussoorie']],
        'about'     => ['banner_subtitle' => ['Our story, our hills, our hospitality', 'A boutique luxury resort on Dhanaulti Road, Mussoorie']],
        'amenities' => ['banner_subtitle' => ['Everything for a perfect stay', 'Pool, outdoor jacuzzi, sauna and slow days in Mussoorie']],
        'contact'   => ['banner_subtitle' => ['We are just a message away', 'Village Kaplani, Dhanaulti Road, Mussoorie']],
        'explore'   => ['banner_subtitle' => ['Viewpoints · Waterfalls · Colonial Heritage', 'Places to visit near our resort on Dhanaulti Road']],
        'faqs'      => ['banner_subtitle' => ['Answers to common questions', 'Your stay in Mussoorie, answered'],
                        'title' => ["FAQ's", 'FAQs'], 'banner_title' => ["FAQ's", 'FAQs']],
        'blog'      => ['banner_subtitle' => ['Stories from the hills', 'Mussoorie travel guides and stories from the house']],
        'gallery'   => ['banner_subtitle' => ['A photo tour of the house', 'A photo tour of our boutique resort in Mussoorie']],
        'terms-and-conditions' => ['banner_subtitle' => ['', 'The English Country House, Mussoorie']],
        'privacy-policy'       => ['banner_subtitle' => ['', 'The English Country House, Mussoorie']],
        'refund-policy'        => ['banner_subtitle' => ['', 'The English Country House, Mussoorie']],
        'hotel-near-dhanaulti' => [
            'title' => ['Hotel Near Dhanaulti', 'Resort Near Dhanaulti'],
            'banner_title' => ['Hotel Near Dhanaulti', 'Resort Near Dhanaulti'],
            'banner_subtitle' => ['', 'A boutique luxury resort on the Dhanaulti Road'],
            'content' => ['<p>Looking for a luxury stay near <strong>Dhanaulti</strong>? The English Country House is just 8 km away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Dhanaulti love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>',
                $loc('Looking for a luxury stay near <strong>Dhanaulti</strong>? The English Country House sits on the Dhanaulti Road in Village Kaplani, about 8 km from Dhanaulti. It is a boutique resort with five individually themed rooms, a swimming pool, an outdoor jacuzzi, a sauna and steam bath, and a multi-cuisine restaurant that cooks with vegetables from our own greenhouse.',
                    'Why guests on a Dhanaulti trip stay with us',
                    ['Close to Dhanaulti, with Mussoorie\'s viewpoints, Landour and the Mall Road also within a drive.', 'Valley and mountain views from the rooms, and a bonfire in the evening.', 'Farm-to-table dining, with vegetables from our own organic greenhouse.', 'Three pet-friendly rooms: Elizabeth Lodge, Tree House 1 and Tree House 2.'],
                    $rooms)],
        ],
        'resort-near-mussoorie-mall-road' => [
            'banner_subtitle' => ['', 'A quiet boutique resort on Dhanaulti Road'],
            'content' => ['<p>Looking for a luxury stay near <strong>Mussoorie Mall Road</strong>? The English Country House is just 30 min drive away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Mussoorie Mall Road love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>',
                $loc('Looking for a luxury stay near <strong>Mussoorie Mall Road</strong>? The English Country House is about a 30-minute drive from the Mall Road, on the quieter Dhanaulti Road side of Mussoorie. Spend the day on the promenade, then come back to a boutique resort with five themed rooms, a swimming pool, an outdoor jacuzzi, a sauna and farm-to-table dining.',
                    'Close to town, away from the crowds',
                    ['About 30 minutes to the Mall Road, with Landour, Char Dukan and Gun Hill also within a drive.', 'Quiet evenings by the bonfire and in the outdoor jacuzzi.', 'Farm-to-table dining, with vegetables from our own organic greenhouse.', 'Pet-friendly rooms if your dog is travelling with you.'],
                    $rooms.' For ideas on what to see, read our guide to <a href="{URL:explore.php}">places to visit in Mussoorie</a>.')],
        ],
        'stay-near-jabarkhet-nature-reserve' => [
            'banner_subtitle' => ['', 'A boutique resort minutes from the forest'],
            'content' => ['<p>Looking for a luxury stay near <strong>Jabarkhet Nature Reserve</strong>? The English Country House is just 10 min drive away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Jabarkhet Nature Reserve love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>',
                $loc('Looking for a luxury stay near <strong>Jabarkhet Nature Reserve</strong>? The English Country House is about a 10-minute drive from the reserve, on the Dhanaulti Road. Walk the forest trails in the morning, then come back to a boutique resort with five themed rooms, a swimming pool, an outdoor jacuzzi, a sauna and steam bath, and farm-to-table dining.',
                    'A quiet base near the forest',
                    ['About 10 minutes to Jabarkhet Nature Reserve.', 'Valley and mountain views from the rooms.', 'Farm-to-table dining at our multi-cuisine restaurant, with vegetables from our own organic greenhouse.', 'Pet-friendly rooms for guests travelling with a dog. Please check the reserve\'s own rules before taking pets on the trails.'],
                    $rooms)],
        ],
    ];
}

/** Section text: [slug => [[section type, field, original, new], …]] — first section of that type whose field still holds the original */
function seo_section_text(): array {
    $card = fn($icon, $t, $x) => ['icon'=>$icon, 'title'=>$t, 'text'=>$x];
    return [
        'home'  => [['rooms', 'subtitle', 'Discover Our Rooms', 'Five individually styled luxury rooms in Mussoorie']],
        'rooms' => [['rooms', 'subtitle', 'Discover Our Room', 'Valley views, mountain views, four-poster beds and wooden tree houses: find the room that suits your trip.']],
        'about' => [
            ['image_text', 'title', 'Elevate Your Stay With Us Today.', 'Slow Days on the Dhanaulti Road'],
            ['image_text', 'content',
                '<p>Immerse yourself in unmatched comfort and luxury with our world-class hospitality. Experience personalized services and premium amenities for a truly memorable stay.</p><div class="icon-feature"><i class="fa-solid fa-water-ladder"></i><div><h4>Classy Facilities</h4><p>Experience world-class amenities designed for ultimate comfort and elegance.</p></div></div><div class="icon-feature"><i class="fa-solid fa-bed"></i><div><h4>Luxury Lifestyle</h4><p>Embrace a refined and opulent way of living.</p></div></div>',
                '<p>We keep the house small on purpose. Five rooms means mornings are quiet, the kitchen knows your name and there is always a seat by the bonfire.</p><div class="icon-feature"><i class="fa-solid fa-water-ladder"></i><div><h4>Wellness on Site</h4><p>A swimming pool, an outdoor jacuzzi, a sauna and steam bath, a gym and yoga.</p></div></div><div class="icon-feature"><i class="fa-solid fa-seedling"></i><div><h4>From Our Greenhouse</h4><p>Vegetables from the Polly House, our organic greenhouse, go straight to the restaurant.</p></div></div>'],
            ['cards', 'title', 'The English Country House Is An Attractive Hotel Since 2024', 'A Boutique Luxury Resort in Mussoorie'],
            ['cards', 'items',
                [$card('fa-regular fa-circle-dot', 'Our Vision', 'To redefine luxury hospitality with elegance and personalized experiences.'), $card('fa-solid fa-bullseye', 'Our Mission', 'To provide world-class comfort, exceptional service, and unforgettable stays.'), $card('fa-regular fa-building', 'Our Value', 'Commitment to excellence, warm hospitality, and guest satisfaction at every step.')],
                [$card('fa-solid fa-bed', 'Our Rooms', 'Five individually themed rooms, from a Victorian four-poster suite to wooden tree houses.'), $card('fa-solid fa-utensils', 'Our Kitchen', 'Multi-cuisine dining, with vegetables from our own organic greenhouse.'), $card('fa-solid fa-mug-hot', 'Our Hospitality', 'A small team that remembers how you take your tea.')]],
            ['cards', 'items',
                [$card('fa-solid fa-square-parking', 'Secure & Convenient Parking', 'Benefit from hassle-free, secure parking available on-site.'), $card('fa-solid fa-mug-hot', 'Complimentary Breakfast', 'Start your day right with a delicious, free breakfast included in your stay.'), $card('fa-regular fa-moon', 'Prime Location', 'Perfectly located close to major attractions — ideal for leisure and business travellers.'), $card('fa-regular fa-star', 'Comfort & Convenience', 'Well-appointed rooms with everything you need for a comfortable stay.')],
                [$card('fa-solid fa-square-parking', 'Secure & Convenient Parking', 'Benefit from hassle-free, secure parking available on-site.'), $card('fa-solid fa-seedling', 'Farm-to-Table Dining', 'Vegetables from the Polly House, our own organic greenhouse, go straight to the restaurant.'), $card('fa-solid fa-mountain-sun', 'On the Dhanaulti Road', 'A quiet setting with valley and mountain views, and Mussoorie\'s viewpoints within a drive.'), $card('fa-solid fa-paw', 'Pet Friendly', 'Three of our five rooms welcome dogs: Elizabeth Lodge, Tree House 1 and Tree House 2.')]],
        ],
        'contact' => [['text', 'content', 'our exceptional holiday home', 'our boutique resort in Mussoorie', 'part']],
    ];
}

/** FAQ answers to correct (old → new) and FAQs to add when no FAQ with that question exists */
function seo_faqs(): array {
    return [
        'fix' => [
            ['Yes! Elizabeth Lodge and both Charlotte Tree Houses are pet friendly. Victorian Suites do not allow pets.', 'Yes. Elizabeth Lodge, Tree House 1 and Tree House 2 are pet friendly. The Victorian Suites do not allow pets.'],
        ],
        'add' => [
            ['Where is the resort?', 'In Village Kaplani on the Dhanaulti Road, Mussoorie, about 30 minutes by car from the Mall Road.', 'General'],
            ['Do you have a jacuzzi, sauna and swimming pool?', 'Yes. We have an outdoor jacuzzi, a sauna and steam bath, and a swimming pool.', 'Facilities'],
            ['Do the rooms have bathtubs?', 'Yes. Every one of our five rooms has a bathtub.', 'Rooms'],
        ],
    ];
}

/** Round 15: Victorian Suite 2 has a king bed, not a four-poster. Text that database update v14 wrote with the plural
 *  "four-poster suites" is corrected where it is still unchanged (database update v15). */
function seo_fix_round15(mysqli $db): void {
    $old = 'Five themed luxury rooms in Mussoorie: Victorian four-poster suites, a pet-friendly lodge and wooden tree houses with valley and mountain views.';
    $new = 'Five themed luxury rooms in Mussoorie: a four-poster suite, a Victorian suite, a pet-friendly lodge and wooden tree houses with valley and mountain views.';
    $st = $db->prepare("UPDATE pages SET seo_desc=? WHERE slug='rooms' AND seo_desc=?"); $st->bind_param('ss', $new, $old); $st->execute();
    $r = $db->query("SELECT id, sections FROM pages WHERE slug='about'")->fetch_assoc();
    if ($r && strpos((string)$r['sections'], 'Five individually themed rooms, from Victorian four-poster suites to wooden tree houses.') !== false) {
        $secs = json_decode((string)$r['sections'], true) ?: [];
        foreach ($secs as &$sec) foreach ((array)($sec['items'] ?? []) as $k => $it)
            if (($it['text'] ?? '') === 'Five individually themed rooms, from Victorian four-poster suites to wooden tree houses.') $sec['items'][$k]['text'] = 'Five individually themed rooms, from a Victorian four-poster suite to wooden tree houses.';
        unset($sec);
        $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $id = (int)$r['id'];
        $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $st->bind_param('si', $j, $id); $st->execute();
    }
}

/** Write all of the above. Safe to run more than once. */
function seo_apply(mysqli $db): void {
    $m = seo_meta();
    $set = $db->prepare("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
    foreach (array_combine(['seo_title','seo_desc','seo_keywords'], $m['site']) as $k => $v) { $set->bind_param('ss', $k, $v); $set->execute(); }

    $st = $db->prepare("UPDATE pages SET seo_title=?, seo_desc=?, seo_keywords=? WHERE slug=?");
    foreach ($m['pages'] as $slug => [$t, $d, $k]) { $st->bind_param('ssss', $t, $d, $k, $slug); $st->execute(); }
    $st = $db->prepare("UPDATE blog_posts SET seo_title=?, seo_desc=?, seo_keywords=? WHERE slug=?");
    foreach ($m['posts'] as $slug => [$t, $d, $k]) { $st->bind_param('ssss', $t, $d, $k, $slug); $st->execute(); }
    $st = $db->prepare("UPDATE blog_categories SET seo_title=?, seo_desc=? WHERE slug=?");
    foreach ($m['categories'] as $slug => [$t, $d]) { $st->bind_param('sss', $t, $d, $slug); $st->execute(); }
    // the travel guide post: "This Season" dropped so the title does not go stale (document, page 1)
    $db->query("UPDATE blog_posts SET title='Top 10 Things to Do in Mussoorie' WHERE slug='top-10-things-to-do-in-mussoorie' AND title='Top 10 Things To Do In Mussoorie This Season'");

    foreach (seo_page_text() as $slug => $fields) foreach ($fields as $f => [$old, $new]) {
        if (!in_array($f, ['title','banner_title','banner_subtitle','content'], true)) continue;
        $st = $db->prepare("UPDATE pages SET `$f`=? WHERE slug=? AND COALESCE(`$f`,'')=?");
        $st->bind_param('sss', $new, $slug, $old); $st->execute();
    }

    $sel = $db->prepare("SELECT id, sections FROM pages WHERE slug=?");
    $upd = $db->prepare("UPDATE pages SET sections=? WHERE id=?");
    foreach (seo_section_text() as $slug => $changes) {
        $sel->bind_param('s', $slug); $sel->execute(); $row = $sel->get_result()->fetch_assoc();
        if (!$row) continue;
        $secs = json_decode((string)$row['sections'], true) ?: []; $changed = false;
        foreach ($changes as $c) {
            [$type, $field, $old, $new] = $c; $part = ($c[4] ?? '') === 'part';
            foreach ($secs as &$s) {
                if (($s['type'] ?? '') !== $type) continue;
                $cur = $s[$field] ?? null;
                if ($part && is_string($cur) && strpos($cur, $old) !== false) { $s[$field] = str_replace($old, $new, $cur); $changed = true; break; }
                if (!$part && $cur === $old) { $s[$field] = $new; $changed = true; break; }
            }
            unset($s);
        }
        if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $id = (int)$row['id']; $upd->bind_param('si', $j, $id); $upd->execute(); }
    }

    // About text (also shown in the home page's About tab): heading and the "Heart of Mussoorie" line from the document
    foreach ([['<h2 style="text-align:center">Let You Know About Us</h2>', '<h2 style="text-align:center">A Little About Us</h2>'],
              ['A Luxurious Escape in the Heart of Mussoorie', 'A Luxurious Escape on the Dhanaulti Road, Mussoorie']] as [$old, $new]) {
        $st = $db->prepare("UPDATE pages SET content=REPLACE(content, ?, ?) WHERE slug='about'"); $st->bind_param('ss', $old, $new); $st->execute();
    }

    $f = seo_faqs();
    $st = $db->prepare("UPDATE faqs SET answer=? WHERE answer=?");
    foreach ($f['fix'] as [$old, $new]) { $st->bind_param('ss', $new, $old); $st->execute(); }
    $has = $db->prepare("SELECT COUNT(*) FROM faqs WHERE question=?");
    $ins = $db->prepare("INSERT INTO faqs (question, answer, category, sort, active) VALUES (?,?,?,?,1)");
    $sort = (int)($db->query("SELECT COALESCE(MAX(sort),0) FROM faqs")->fetch_row()[0] ?? 0);
    foreach ($f['add'] as [$q, $a, $cat]) {
        $has->bind_param('s', $q); $has->execute();
        if ((int)$has->get_result()->fetch_row()[0] > 0) continue;
        $sort++; $ins->bind_param('sssi', $q, $a, $cat, $sort); $ins->execute();
    }

    // room names: the tree houses were renamed in round 8 ("Charlotte Tree House I/II" → "Tree House 1/2"); public text still had the old names
    foreach ([['pages', 'content', 'Elizabeth Lodge and Charlotte Tree Houses', 'Elizabeth Lodge, Tree House 1 and Tree House 2'],
              ['chatbot_qa', 'answer', 'Elizabeth Lodge and our Charlotte Tree Houses', 'Elizabeth Lodge, Tree House 1 and Tree House 2'],
              ['canned_replies', 'content', 'Elizabeth Lodge and our two Charlotte Tree Houses', 'Elizabeth Lodge, Tree House 1 and Tree House 2'],
              ['canned_replies', 'content', 'for the Charlotte Tree Houses', 'for the Tree Houses']] as [$t, $c, $old, $new]) {
        try { $st = $db->prepare("UPDATE `$t` SET `$c`=REPLACE(`$c`, ?, ?) WHERE `$c` LIKE CONCAT('%', ?, '%')"); $st->bind_param('sss', $old, $new, $old); $st->execute(); }
        catch (Throwable $e) { /* table missing on an older install: nothing to rename */ }
    }

    // footer blurb (document, page 1: footer)
    $old = 'A boutique luxury retreat perched in the tranquil hills of Mussoorie — five uniquely themed rooms, farm-to-table dining and warm English-country hospitality.';
    $new = 'A boutique luxury resort in the tranquil hills of Mussoorie: five individually themed rooms, farm-to-table dining and warm English-country hospitality.';
    $st = $db->prepare("UPDATE settings SET svalue=? WHERE skey='footer_about' AND svalue=?"); $st->bind_param('ss', $new, $old); $st->execute();

    // menu and footer labels: "FAQ's" → "FAQs" (document: "Write FAQs without the apostrophe")
    foreach (['menu_items', 'footer_links'] as $k) {
        $r = $db->query("SELECT svalue FROM settings WHERE skey='$k'")->fetch_row();
        if (!$r) continue;
        $items = json_decode((string)$r[0], true);
        if (!is_array($items)) continue;
        $ch = false;
        foreach ($items as &$it) if (($it['label'] ?? '') === "FAQ's") { $it['label'] = 'FAQs'; $ch = true; }
        unset($it);
        if ($ch) { $v = json_encode($items, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); $set->bind_param('ss', $k, $v); $set->execute(); }
    }
}
