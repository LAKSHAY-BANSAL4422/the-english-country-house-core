<?php
/**
 * Default page rows + drag-and-drop sections for every public page.
 * Used by the first-install seed AND by migration v6 on existing sites, so that
 * Rooms, Amenities, Contact, FAQ, Blog, Gallery and About all become fully
 * editable (banner, sections, content) without losing anything that is already there.
 *
 * ensure_default_pages($db): creates missing system pages, fills empty
 * `sections` with the default layout, and never overwrites edited content.
 */

function page_default_definitions(): array {
    $site = 'The English Country House';
    return [
      'about' => [
        'title'=>'About Us','banner_title'=>'About Us','banner_subtitle'=>'Our story, our hills, our hospitality','menu_order'=>2,'show_in_menu'=>1,
        'seo_title'=>'About Us | '.$site.' Mussoorie',
        'sections'=>[
          ['type'=>'image_text','visible'=>1,'title'=>'Elevate Your Stay With Us Today.','subtitle'=>'','image'=>'assets/img/redesign/about-us.jpg',   // round 16: owner's About photo
           'content'=>'<p>Immerse yourself in unmatched comfort and luxury with our world-class hospitality. Experience personalized services and premium amenities for a truly memorable stay.</p><div class="icon-feature"><i class="fa-solid fa-water-ladder"></i><div><h4>Classy Facilities</h4><p>Experience world-class amenities designed for ultimate comfort and elegance.</p></div></div><div class="icon-feature"><i class="fa-solid fa-bed"></i><div><h4>Luxury Lifestyle</h4><p>Embrace a refined and opulent way of living.</p></div></div>',
           'bg'=>'#f6f3ee','tx'=>''],
          ['type'=>'cards','visible'=>1,'title'=>$site.' Is An Attractive Hotel Since 2024','subtitle'=>'Our Story','content'=>'','image'=>'','bg'=>'','tx'=>'',
           'items'=>[
             ['icon'=>'fa-regular fa-circle-dot','title'=>'Our Vision','text'=>'To redefine luxury hospitality with elegance and personalized experiences.'],
             ['icon'=>'fa-solid fa-bullseye','title'=>'Our Mission','text'=>'To provide world-class comfort, exceptional service, and unforgettable stays.'],
             ['icon'=>'fa-regular fa-building','title'=>'Our Value','text'=>'Commitment to excellence, warm hospitality, and guest satisfaction at every step.'],
           ]],
          ['type'=>'cards','visible'=>1,'title'=>'Why Choose Us?','subtitle'=>'','content'=>'','image'=>'','bg'=>'#f3e9d7','tx'=>'',
           'items'=>[
             ['icon'=>'fa-solid fa-square-parking','title'=>'Secure & Convenient Parking','text'=>'Benefit from hassle-free, secure parking available on-site.'],
             ['icon'=>'fa-solid fa-mug-hot','title'=>'Complimentary Breakfast','text'=>'Start your day right with a delicious, free breakfast included in your stay.'],
             ['icon'=>'fa-regular fa-moon','title'=>'Prime Location','text'=>'Perfectly located close to major attractions — ideal for leisure and business travellers.'],
             ['icon'=>'fa-regular fa-star','title'=>'Comfort & Convenience','text'=>'Well-appointed rooms with everything you need for a comfortable stay.'],
           ]],
          ['type'=>'cta','visible'=>1,'title'=>'Ready For A Hillside Escape?','subtitle'=>'Book Direct — Best Rates','content'=>'','image'=>'','btn1_label'=>'Book Your Stay','btn1_url'=>'booking.php','btn2_label'=>'Explore Rooms','btn2_url'=>'rooms.php','bg'=>'','tx'=>''],
        ]],
      'rooms' => [
        'title'=>'Our Rooms','banner_title'=>'Our Rooms','banner_subtitle'=>'Five uniquely themed retreats','menu_order'=>3,'show_in_menu'=>0,
        'seo_title'=>'Our Rooms | '.$site,
        'seo_desc'=>'Explore five uniquely themed luxury rooms — Victorian Suites, Elizabeth Lodge and Charlotte Tree Houses — each with a private balcony and elegant bathtub.',
        'sections'=>[
          ['type'=>'rooms','visible'=>1,'title'=>'A Gander At Our Rooms','subtitle'=>'Discover Our Room','content'=>'','image'=>'','bg'=>'','tx'=>'','opts'=>['show_book'=>0,'show_details'=>1]],
          ['type'=>'cta','visible'=>1,'title'=>'Not Sure Which Room Suits You?','subtitle'=>'We Are Happy To Help','content'=>'<p>Call us or send an enquiry — we will recommend the perfect room for your dates and group.</p>','image'=>'','btn1_label'=>'Check Availability','btn1_url'=>'booking.php','btn2_label'=>'Contact Us','btn2_url'=>'contact.php','bg'=>'','tx'=>''],
        ]],
      'amenities' => [
        'title'=>'Amenities','banner_title'=>'Our Amenities','banner_subtitle'=>'Everything for a perfect stay','menu_order'=>4,'show_in_menu'=>0,
        'seo_title'=>'Amenities & Facilities | '.$site,
        'seo_desc'=>'Pool, outdoor jacuzzi, sauna, steam bath, gym, yoga house, basketball court, snooker, library, EV charging and more at The English Country House, Mussoorie.',
        'sections'=>[
          ['type'=>'amenities','visible'=>1,'title'=>'Enjoy Our Amenities','subtitle'=>'Enjoy Every Moment','content'=>'','image'=>'','bg'=>'','tx'=>''],
          ['type'=>'cta','visible'=>1,'title'=>'Experience It All — Book Your Stay','subtitle'=>'Relax · Rejuvenate · Repeat','content'=>'','image'=>'','btn1_label'=>'Book Now','btn1_url'=>'booking.php','bg'=>'','tx'=>''],
        ]],
      'contact' => [
        'title'=>'Contact Us','banner_title'=>'Contact Us','banner_subtitle'=>'We are just a message away','menu_order'=>8,'show_in_menu'=>0,
        'seo_title'=>'Contact Us | '.$site,
        'seo_desc'=>'Reach The English Country House, Village Kaplani Dhanaulti Road, Mussoorie. Call 9456765555 or write to info@theenglishcountryhouse.com.',
        'sections'=>[
          ['type'=>'text','visible'=>1,'title'=>'','subtitle'=>'Contact Us','image'=>'','bg'=>'','tx'=>'',
           'content'=>'<p style="text-align:center">At The English Country House, we\'re here to ensure your stay is nothing short of unforgettable. Whether you have questions, need help with reservations, or want to learn more about our exceptional holiday home, we\'re just a message away.</p>'],
          ['type'=>'contact_info','visible'=>1,'title'=>'Get In Touch With Us For Quick Support.','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>''],
          ['type'=>'contact_form','visible'=>1,'title'=>'Your Details','subtitle'=>'','content'=>'<p>Let us know how to get back to you.</p>','image'=>'','bg'=>'','tx'=>'','opts'=>['show_map'=>1]],
          ['type'=>'attractions','visible'=>1,'title'=>'More To Explore','subtitle'=>'Nearby Experiences','image'=>'','bg'=>'#f6f3ee','tx'=>'',
           'content'=>'<p>From iconic viewpoints to cascading waterfalls, discover the best of Mussoorie — all within easy reach of the house.</p>',
           'btn1_label'=>'Explore Mussoorie','btn1_url'=>'explore.php','opts'=>['layout'=>'strip','category'=>'featured','limit'=>4]],
        ]],
      'explore' => [
        'title'=>'Explore Mussoorie','banner_title'=>'Explore Mussoorie','banner_subtitle'=>'Viewpoints · Waterfalls · Colonial Heritage','menu_order'=>5,'show_in_menu'=>0,
        'content'=>'<p style="text-align:center;font-size:18px;max-width:760px;margin:0 auto">Breathtaking landscapes, timeless colonial charm and unforgettable experiences await you in the Queen of the Hills — every spot below is within an easy drive of the house, and our concierge will happily plan the day for you.</p>',
        'seo_title'=>'Explore Mussoorie — Attractions, Waterfalls & Viewpoints | '.$site,
        'seo_desc'=>'Lal Tibba, Cloud End, George Everest House, Benog Sanctuary, Bhatta & Jharipani Falls, Company Garden, Mussoorie Lake and Robber\'s Cave — distances and travel times from The English Country House, Dhanaulti Road, Mussoorie.',
        'sections'=>(function () { require_once __DIR__.'/explore_data.php'; return explore_page_sections(); })()],
      'faqs' => [
        'title'=>"FAQ's",'banner_title'=>"FAQ's",'banner_subtitle'=>'Answers to common questions','menu_order'=>6,'show_in_menu'=>0,
        'seo_title'=>"FAQ's | ".$site,
        'seo_desc'=>'Frequently asked questions about staying at The English Country House — check-in, pets, dining, refunds and more.',
        'sections'=>[
          ['type'=>'faq_full','visible'=>1,'title'=>'Frequently Asked Questions','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>'','btn1_label'=>'Contact Us','btn1_url'=>'contact.php'],
        ]],
      'blog' => [
        'title'=>'Blog','banner_title'=>'Our Blog','banner_subtitle'=>'Stories from the hills','menu_order'=>5,'show_in_menu'=>0,
        'seo_title'=>'Blog | '.$site,
        'seo_desc'=>'Travel guides, resort stories and Mussoorie tips from The English Country House.',
        'sections'=>[
          ['type'=>'blog_list','visible'=>1,'title'=>'','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>'','opts'=>['per_page'=>9]],
        ]],
      'gallery' => [
        'title'=>'Gallery','banner_title'=>'Gallery','banner_subtitle'=>'A photo tour of the house','menu_order'=>7,'show_in_menu'=>0,
        'seo_title'=>'Gallery | '.$site,
        'seo_desc'=>'A photo tour of The English Country House — rooms, dining, amenities and the Mussoorie views.',
        'sections'=>[
          ['type'=>'gallery_full','visible'=>1,'title'=>'','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>''],
        ]],
      'booking' => [
        'title'=>'Book Your Stay','banner_title'=>'Book Your Stay','banner_subtitle'=>'Best rates when you book direct','menu_order'=>9,'show_in_menu'=>0,
        'seo_title'=>'Book Your Stay | '.$site,
        'seo_desc'=>'Book direct at The English Country House, Mussoorie — live availability, seasonal rates and instant confirmation.',
        'sections'=>[]],
    ];
}

/** Sections auto-added to a location page that has none */
function location_default_sections(string $title): array {
    $place = str_replace(['Hotel Near ','Resort Near ','Stay Near '], '', $title);
    return [
      ['type'=>'rooms','visible'=>1,'title'=>'Rooms Available For Your Stay','subtitle'=>'Choose Your Room','content'=>'','image'=>'','bg'=>'','tx'=>''],
      ['type'=>'cta','visible'=>1,'title'=>'Book Your Stay Near '.$place,'subtitle'=>'Best Rate — Book Direct','content'=>'','image'=>'','btn1_label'=>'Check Availability','btn1_url'=>'booking.php','bg'=>'','tx'=>''],
    ];
}

function ensure_default_pages(mysqli $db): void {
    $defs = page_default_definitions();
    $sel = $db->prepare("SELECT id, sections, banner_title, content FROM pages WHERE slug=?");
    $ins = $db->prepare("INSERT INTO pages (title,slug,page_type,content,banner_image,banner_title,banner_subtitle,show_banner,seo_title,seo_desc,seo_keywords,schema_json,show_in_menu,menu_order,active,sections) VALUES (?,?,'system',?,'',?,?,1,?,?,'','',?,?,1,?)");
    $updSec = $db->prepare("UPDATE pages SET sections=? WHERE id=?");
    $updBan = $db->prepare("UPDATE pages SET banner_title=?, banner_subtitle=? WHERE id=? AND (banner_title IS NULL OR banner_title='')");
    foreach ($defs as $slug => $d) {
        $sel->bind_param('s', $slug); $sel->execute();
        $row = $sel->get_result()->fetch_assoc();
        $json = json_encode($d['sections'], JSON_UNESCAPED_UNICODE);
        if (!$row) {
            $seoDesc = $d['seo_desc'] ?? ''; $content = $d['content'] ?? '';
            $ins->bind_param('sssssssiis', $d['title'], $slug, $content, $d['banner_title'], $d['banner_subtitle'], $d['seo_title'], $seoDesc, $d['show_in_menu'], $d['menu_order'], $json);
            $ins->execute();
            continue;
        }
        $cur = json_decode((string)$row['sections'], true);
        if (!is_array($cur) || !$cur) { $id = (int)$row['id']; $updSec->bind_param('si', $json, $id); $updSec->execute(); }
        elseif ($slug === 'contact' && !in_array('attractions', array_column($cur, 'type'), true)) {   // v7: "More to Explore" strip at the end of the Contact page
            foreach ($d['sections'] as $sec) if (($sec['type'] ?? '') === 'attractions') $cur[] = $sec;
            $j = json_encode(array_values($cur), JSON_UNESCAPED_UNICODE); $id = (int)$row['id']; $updSec->bind_param('si', $j, $id); $updSec->execute();
        }
        $id = (int)$row['id'];
        $updBan->bind_param('ssi', $d['banner_title'], $d['banner_subtitle'], $id); $updBan->execute();
    }
    // About: give the intro content its heading if it has none, so the classic text block keeps its look
    $r = $db->query("SELECT id, content FROM pages WHERE slug='about'")->fetch_assoc();
    if ($r && $r['content'] !== '' && stripos($r['content'], '<h2') === false) {
        $c = '<h2 style="text-align:center">Let You Know About Us</h2>'.$r['content'];
        $st = $db->prepare("UPDATE pages SET content=? WHERE id=?"); $id = (int)$r['id']; $st->bind_param('si', $c, $id); $st->execute();
    }
    // Home / location pages: banner title defaults + location layouts
    $res = $db->query("SELECT id, title, slug, page_type, sections, banner_title FROM pages WHERE page_type IN ('location','custom','policy') OR slug='home'");
    while ($p = $res->fetch_assoc()) {
        if (($p['banner_title'] ?? '') === '') { $st = $db->prepare("UPDATE pages SET banner_title=? WHERE id=?"); $id = (int)$p['id']; $st->bind_param('si', $p['title'], $id); $st->execute(); }
        if ($p['page_type'] === 'location') {
            $cur = json_decode((string)$p['sections'], true);
            if (!is_array($cur) || !$cur) { $j = json_encode(location_default_sections($p['title']), JSON_UNESCAPED_UNICODE); $id = (int)$p['id']; $updSec->bind_param('si', $j, $id); $updSec->execute(); }
        }
    }
}
