<?php
/**
 * ============================================================
 *  ROUND 18 — content corrections (database part)
 *  ------------------------------------------------------------
 *  Applied once by database update v18 (install/schema.php) and on a fresh install (end of install/seed.php).
 *  Safe to run more than once.
 *   - breakfast is not complimentary (it depends on the package): every "complimentary breakfast" claim is removed
 *   - refund window: 7 days before check-in (was 4)
 *   - Contact Form: no Website field
 *   - the resort's exact map pin (from the hotel's Google Maps link)
 * ============================================================
 */

function round18_apply(mysqli $db): void {
    $put = function (string $k, string $v) use ($db) { $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES (?,?)"); $st->bind_param('ss', $k, $v); $st->execute(); };
    $get = function (string $k) use ($db) { $r = $db->query("SELECT svalue FROM settings WHERE skey='".$db->real_escape_string($k)."'")->fetch_row(); return $r ? (string)$r[0] : null; };

    /* ---- breakfast: page text (location pages and anything else written with the old wording) ---- */
    $text = [
        '<li>A complimentary multi-cuisine breakfast with every stay.</li>' => '<li>Farm-to-table dining, with vegetables from our own organic greenhouse.</li>',
        '<li>A complimentary multi-cuisine breakfast, and dinner at our multi-cuisine restaurant.</li>' => '<li>Farm-to-table dining at our multi-cuisine restaurant, with vegetables from our own organic greenhouse.</li>',
        'private balconies with valley views, complimentary breakfast and pet-friendly tree houses' => 'private balconies with valley views, the farm-to-table restaurant and pet-friendly tree houses',
    ];
    $sel = $db->query("SELECT id, content, seo_desc FROM pages WHERE content LIKE '%reakfast%' OR seo_desc LIKE '%reakfast%'");
    $upd = $db->prepare("UPDATE pages SET content=?, seo_desc=? WHERE id=?");
    while ($r = $sel->fetch_assoc()) {
        $c = strtr((string)$r['content'], $text);
        $d = str_replace('Check-in times, pet-friendly rooms, breakfast, the pool', 'Check-in times, pet-friendly rooms, dining, the pool', (string)$r['seo_desc']);
        $id = (int)$r['id']; $upd->bind_param('ssi', $c, $d, $id); $upd->execute();
    }
    // the "Complimentary Breakfast" card (About page, "Why Choose Us") becomes "Farm-to-Table Dining"
    $sel = $db->query("SELECT id, sections FROM pages WHERE sections LIKE '%Complimentary Breakfast%'");
    $upd = $db->prepare("UPDATE pages SET sections=? WHERE id=?");
    while ($r = $sel->fetch_assoc()) {
        $secs = json_decode((string)$r['sections'], true) ?: [];
        foreach ($secs as &$sec) foreach ((array)($sec['items'] ?? []) as $k => $it)
            if (is_array($it) && trim((string)($it['title'] ?? '')) === 'Complimentary Breakfast')
                $sec['items'][$k] = ['icon'=>'fa-solid fa-seedling', 'title'=>'Farm-to-Table Dining', 'text'=>'Vegetables from the Polly House, our own organic greenhouse, go straight to the restaurant.'] + $it;
        unset($sec);
        $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $id = (int)$r['id'];
        $upd->bind_param('si', $j, $id); $upd->execute();
    }
    // FAQ "Is breakfast included in the tariff?" (answered "Yes, complimentary") is switched off; it can be reworded and switched on in Admin → FAQs
    $db->query("UPDATE faqs SET active=0 WHERE question LIKE '%breakfast%' AND answer LIKE '%omplimentary%'");
    // chatbot answer about food
    $db->query("UPDATE chatbot_qa SET answer=REPLACE(answer, 'Complimentary breakfast is included!', 'Breakfast depends on the package you book, so please ask when you enquire.') WHERE answer LIKE 'Complimentary breakfast is included!%'");
    // the "Breakfast" room amenity (it read as included in every room); the five rooms no longer list it (data/rooms.php)
    $db->query("UPDATE amenities SET active=0 WHERE name='Breakfast'");

    /* ---- refund window 7 days ---- */
    $put('refund_days', '7');
    $db->query("UPDATE faqs SET answer=REPLACE(answer, '4 or more days before check-in', '7 or more days before check-in') WHERE answer LIKE '%4 or more days before check-in%'");
    $db->query("UPDATE faqs SET answer=REPLACE(answer, 'Within 4 days of check-in', 'Within 7 days of check-in') WHERE answer LIKE '%Within 4 days of check-in%'");
    $db->query("UPDATE chatbot_qa SET answer=REPLACE(REPLACE(answer, '4+ days before check-in', '7+ days before check-in'), 'Within 4 days', 'Within 7 days') WHERE answer LIKE '%4+ days before check-in%'");

    /* ---- Contact Form: no Website field; Telephone takes the full row it leaves ---- */
    $sel = $db->query("SELECT id, fields FROM forms WHERE fields LIKE '%\"website\"%'");
    $upd = $db->prepare("UPDATE forms SET fields=? WHERE id=?");
    while ($r = $sel->fetch_assoc()) {
        $f = json_decode((string)$r['fields'], true) ?: [];
        $f = array_values(array_filter($f, fn($x) => ($x['name'] ?? '') !== 'website'));
        foreach ($f as &$x) if (($x['name'] ?? '') === 'phone') $x['half'] = false;
        unset($x);
        $j = json_encode($f); $id = (int)$r['id'];
        $upd->bind_param('si', $j, $id); $upd->execute();
    }

    /* ---- Privacy Policy: says what the enquiry forms save and that Google Tag Manager (Analytics, Ads, Meta) runs on the site.
            Replaced only while it is still the short text the site shipped with (an edited policy is kept). ---- */
    $pp = '<h3>Privacy Policy</h3>'
        .'<p>{SITE_NAME} respects your privacy. This policy explains what this website collects and how we use it.</p>'
        .'<h4>What we collect</h4><ul>'
        .'<li><strong>Enquiries and messages.</strong> When you fill in our enquiry or contact form we receive your name, phone number, email address, travel dates and message. A copy is saved in our own reservations system and emailed to our team, and you are then taken to WhatsApp to send the message to us.</li>'
        .'<li><strong>Bookings and payments.</strong> We keep the details needed for your stay. Payment details are processed securely by our payment gateway partners and are never stored on our servers.</li>'
        .'<li><strong>Website visits.</strong> We use Google Tag Manager to run Google Analytics, Google Ads and Meta (Facebook and Instagram) measurement tags. They use cookies and similar technology to count visits, see which pages are useful and measure our advertising. Your name, phone number and email address from our forms are not sent to them.</li>'
        .'</ul>'
        .'<h4>How we use it</h4><p>To answer your enquiry, look after your stay, improve our website and measure our advertising. We never sell your data.</p>'
        .'<h4>WhatsApp</h4><p>Messages you send us on WhatsApp are also covered by WhatsApp\'s own privacy policy.</p>'
        .'<h4>Your choices</h4><p>You can block or delete cookies in your browser settings, and turn off personalised ads in your Google and Meta account settings. To see, correct or delete the data we hold about you, write to us at {EMAIL} or call {PHONE}.</p>';
    $st = $db->prepare("UPDATE pages SET content=? WHERE slug='privacy-policy' AND CHAR_LENGTH(content) < 600 AND content LIKE '<h3>Privacy Policy</h3><p>We collect only the information needed to process your booking%' AND content LIKE '%You may request deletion of your data by writing to us at%'");
    $st->bind_param('s', $pp); $st->execute();

    /* ---- exact map pin (Google Maps place of the resort); only replaces the approximate values the site shipped with ---- */
    if (in_array((string)$get('geo_lat'), ['', '30.4265'], true) && in_array((string)$get('geo_lng'), ['', '78.1465'], true)) {
        $put('geo_lat', '30.4476835'); $put('geo_lng', '78.1430734');
    }
    if (in_array((string)$get('map_embed'), ['', 'https://www.google.com/maps?q=Mussoorie+Dhanaulti+Road&output=embed'], true))
        $put('map_embed', 'https://maps.google.com/maps?q=30.4476835,78.1430734&z=15&output=embed');
}
