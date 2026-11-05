<?php
/** First-install seed data (runs once, from db_migrate). $db = mysqli */

$ins = function(string $table, array $data) use ($db) {
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".rtrim(str_repeat('?,',count($cols)),',').")";
    $stmt = $db->prepare($sql);
    $types=''; foreach ($data as $v) $types .= is_int($v)?'i':(is_float($v)?'d':'s');
    $vals = array_values($data);
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    return $db->insert_id;
};

/* ---------- default super admin (email: admin@example.com / pass: admin123 — CHANGE!) ---------- */
$ins('admins', ['name'=>'Super Admin','email'=>'admin@example.com','password'=>password_hash('admin123', PASSWORD_DEFAULT),'role'=>'superadmin','permissions'=>'["*"]','chat_available'=>0]);

/* ---------- settings ---------- */
$settings = [
 'site_name' => 'The English Country House',
 'site_tagline' => 'Resort & Spa — Mussoorie',
 'contact_phone' => '9456765555',
 'contact_phone2' => '',
 'contact_email' => 'info@theenglishcountryhouse.com',
 'contact_address' => "Village Kaplani Dhanaulti Road,\nMussoorie, Uttarakhand 248179, India",
 'map_embed' => 'https://www.google.com/maps?q=Mussoorie+Dhanaulti+Road&output=embed',
 'social_instagram' => 'https://www.instagram.com/the_english_country_house?psln=NjBqN2x1Y21oa2Rt',
 'social_linkedin' => '', 'social_x' => '', 'social_youtube' => '',
 'currency' => 'INR', 'currency_symbol' => '₹',
 'tax_percent' => '12', 'tax_label' => 'GST', 'gstin' => '',
 'invoice_notes' => 'Thank you for choosing us! For any billing query call our reservation desk.',
 'advance_percent' => '100',
 'checkin_time' => '14:00', 'checkout_time' => '11:00',
 /* refund policy */
 'refund_days' => '7', 'refund_percent' => '100', 'refund_auto' => '1',
 /* booking verification */
 'booking_otp_required' => '1',
 /* logos */
 'logo_header'=>'assets/img/brand/logo-light.png', 'logo_header_w'=>'0', 'logo_header_h'=>'64',
 'logo_footer'=>'assets/img/brand/logo-light.png', 'logo_footer_w'=>'0', 'logo_footer_h'=>'110', 'favicon'=>'assets/img/brand/favicon.png',
 /* appearance — 11 colors + fonts */
 'color_primary' => '#c9974b', 'color_primary_dark' => '#a87a2f',
 'color_secondary' => '#0f2238', 'color_accent' => '#e8c88f',
 'color_bg' => '#ffffff', 'color_bg_alt' => '#f6f3ee',
 'color_text' => '#333b45', 'color_heading' => '#182635',
 'color_footer_bg' => '#0f2238', 'color_footer_text' => '#cfd8e3',
 'color_btn_text' => '#ffffff',
 'font_heading' => 'Cormorant Garamond', 'font_body' => 'Outfit', 'font_accent' => 'Great Vibes',
 'appearance_preset' => 'royal-gold',
 /* header & footer */
 'menu_items' => json_encode([
    ['label'=>'Home','url'=>'index.php','visible'=>1],
    ['label'=>'About us','url'=>'about.php','visible'=>1],
    ['label'=>'Rooms','url'=>'rooms.php','visible'=>1],
    ['label'=>'Amenities','url'=>'amenities.php','visible'=>1],
    ['label'=>'Explore Mussoorie','url'=>'explore.php','visible'=>1],
    ['label'=>'Blog','url'=>'blog.php','visible'=>1],
    ["label"=>"FAQ's",'url'=>'faqs.php','visible'=>1],
    ['label'=>'Our Menu','url'=>'menu.php','visible'=>1],
    ['label'=>'Contact','url'=>'contact.php','visible'=>1],
 ], JSON_UNESCAPED_UNICODE),
 'footer_about' => 'A boutique luxury retreat perched in the tranquil hills of Mussoorie — five uniquely themed rooms, farm-to-table dining and warm English-country hospitality.',
 'footer_links' => json_encode([
    ['label'=>'Our Rooms','url'=>'rooms.php','visible'=>1],
    ['label'=>'Book Your Stay','url'=>'booking.php','visible'=>1],
    ['label'=>'Our Menu','url'=>'menu.php','visible'=>1],
    ['label'=>'Explore Mussoorie','url'=>'explore.php','visible'=>1],
    ['label'=>'Blog','url'=>'blog.php','visible'=>1],
    ["label"=>"FAQ's",'url'=>'faqs.php','visible'=>1],
 ], JSON_UNESCAPED_UNICODE),
 /* SMTP */
 'smtp_enabled'=>'0','smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'','smtp_secure'=>'tls',
 'mail_from'=>'no-reply@theenglishcountryhouse.com','mail_from_name'=>'The English Country House',
 /* payment gateways */
 'pay_online_enabled'=>'1',
 'pay_razorpay_enabled'=>'0','pay_razorpay_key'=>'','pay_razorpay_secret'=>'',
 'pay_stripe_enabled'=>'0','pay_stripe_pk'=>'','pay_stripe_sk'=>'',
 'pay_paypal_enabled'=>'0','pay_paypal_client'=>'','pay_paypal_secret'=>'','pay_paypal_mode'=>'sandbox',
 'pay_paytm_enabled'=>'0','pay_paytm_mid'=>'','pay_paytm_key'=>'','pay_paytm_website'=>'DEFAULT','pay_paytm_mode'=>'stage',
 'pay_upi_enabled'=>'1','pay_upi_id'=>'','pay_upi_name'=>'The English Country House','pay_upi_qr'=>'',
 'pay_cod_enabled'=>'1','pay_cod_label'=>'Pay at Hotel',
 /* social login */
 'google_client_id'=>'','google_client_secret'=>'',
 /* whatsapp */
 'wa_enabled'=>'1','wa_number'=>'919456765555','wa_message'=>'Hi! I would like to know more about The English Country House.','wa_position'=>'right','wa_show_mobile'=>'1','wa_show_desktop'=>'1',
 /* chat */
 'chat_enabled'=>'1','chat_wait_seconds'=>'45','chat_idle_seconds'=>'90',
 'chat_offline_msg'=>'Our team is currently offline. You can reach us instantly on WhatsApp, or leave your number and we will call you back.',
 'chat_greeting'=>'Hello! 👋 Welcome to The English Country House. How can I help you today?',
 /* newsletter */
 'newsletter_enabled'=>'1','newsletter_day'=>'5',
 'newsletter_subject'=>'This weekend in the hills 🏔 — offers & moments from The English Country House',
 /* google */
 'google_places_key'=>'','google_place_id'=>'','recaptcha_site'=>'','recaptcha_secret'=>'',
 'ga_code'=>'','head_scripts'=>'','body_scripts'=>'',
 /* seo / aeo / geo */
 'seo_title' => 'The English Country House Resort & Spa | Boutique Luxury Hotel in Mussoorie',
 'seo_desc' => 'Boutique luxury retreat in Mussoorie with five uniquely themed rooms, private balconies, pool, jacuzzi, sauna, multi-cuisine restaurant and mountain views. Book your stay today.',
 'seo_keywords' => 'hotel in mussoorie, resort in mussoorie, luxury stay mussoorie, boutique hotel dhanaulti, pet friendly resort mussoorie',
 'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /team/\nDisallow: /api/\nDisallow: /account/\nDisallow: /cron/\nDisallow: /install/\nDisallow: /pay\nDisallow: /booking-done\nDisallow: /invoice\nDisallow: /unsubscribe\nDisallow: /*?*edit=1\n\nSitemap: {BASE_URL}/sitemap.xml",   // round 17
 'schema_org' => '',
 'geo_lat' => '30.4265', 'geo_lng' => '78.1465', 'geo_region' => 'IN-UT', 'geo_placename' => 'Mussoorie, Uttarakhand',
 'llms_txt' => "{AUTO}",   // round 17: built from the live site by llms.php (rooms, amenities, FAQs, pages, contact)
 'pwa_enabled' => '1', 'pwa_short_name' => 'ECH Resort',
 /* platform behaviour */
 'clean_urls' => '1',
 'maintenance_mode' => '0',
 'maintenance_msg' => 'We are polishing something beautiful. Our new website is under construction — call or WhatsApp us to book your stay meanwhile!',
 'cache_bust' => '1',
 'booking_notify_email' => 'info@theenglishcountryhouse.com',
];
foreach ($settings as $k=>$v) { $stmt=$db->prepare("REPLACE INTO settings (skey,svalue) VALUES (?,?)"); $stmt->bind_param('ss',$k,$v); $stmt->execute(); }

/* ---------- amenities ---------- */
$amen = [
 ['Air Conditioner','fa-snowflake'],['Breakfast','fa-utensils'],['Fiber Wifi','fa-wifi'],['Swimming Pool','fa-person-swimming'],
 ['EV Charging Station','fa-charging-station'],['Parking Space','fa-square-parking'],['Library','fa-book'],['Fitness / Gym','fa-dumbbell'],
 ['Yoga House','fa-spa'],['Basketball Court','fa-basketball'],['Outdoor Jacuzzi','fa-hot-tub-person'],['Wall Climbing','fa-mountain'],
 ['Sauna & Steam Bath','fa-temperature-high'],['Multi-cuisine Restaurant','fa-bowl-food'],['Snooker Room','fa-circle-dot'],['Mountain View Deck','fa-mountain-sun'],
 ['Green House','fa-seedling'],['Table Tennis','fa-table-tennis-paddle-ball'],
];
$i=0; foreach ($amen as $a) { $ins('amenities',['name'=>$a[0],'icon'=>$a[1],'sort'=>$i++,'show_home'=>1,'active'=>1,'description'=>'','image'=>'']); }

/* ---------- rooms ---------- */
$rooms = [
 ['Victorian Suite I','victorian-suite-i',0,8500,'A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.','King Four-Poster','Valley View'],
 ['Victorian Suite II','victorian-suite-ii',0,8500,'Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.','King Four-Poster','Valley View'],
 ['Elizabeth Lodge','elizabeth-lodge',1,9500,'A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.','King','Mountain View'],
 ['Charlotte Tree House I','charlotte-tree-house-i',1,10500,'A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.','Queen','Valley View'],
 ['Charlotte Tree House II','charlotte-tree-house-ii',1,10500,'Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.','Queen','Garden & Valley View'],
];
$s=0;
foreach ($rooms as $r) {
    $ins('rooms',[ 'name'=>$r[0],'slug'=>$r[1],'pets_allowed'=>$r[2],'base_price'=>(float)$r[3],
      'weekend_price'=>(float)($r[3]*1.2),'extra_adult_price'=>1500.0,'child_price'=>800.0,
      'base_occupancy'=>2,'max_adults'=>3,'max_children'=>2,'total_units'=>1,
      'short_desc'=>$r[4],'description'=>'<p>'.$r[4].'</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',
      'bed_type'=>$r[5],'view_type'=>$r[6],'room_size'=>'320 sq.ft',
      'images'=>'[]','amenity_ids'=>'[1,2,3,4,11,13,14]','featured'=>1,'active'=>1,'sort'=>$s++,
      'ical_token'=>bin2hex(random_bytes(16)),
      'seo_title'=>$r[0].' | The English Country House Mussoorie','seo_desc'=>$r[4],'seo_keywords'=>'' ]);
}

/* ---------- OTA channels (from your channel sheet — enter passwords in Admin → OTA Channels) ---------- */
$otas = [
 ['Expedia','115190547','info@theenglishcountryhouse.com','https://www.expedia.co.in'],
 ['Airbnb','689425165','','https://www.airbnb.co.in'],
 ['Goibibo / MakeMyTrip','1000641708','info@theenglishcountryhouse.com','https://in.goibibo.com'],
 ['Desiya','00212100','info@theenglishcountryhouse.com','https://www.desiya.com'],
 ['EaseMyTrip','SHL-2505121857524951','info@theenglishcountryhouse.com','https://www.easemytrip.com'],
 ['Agoda','71119791','info@theenglishcountryhouse.com','https://www.agoda.com'],
 ['Cleartrip','392209','info@theenglishcountryhouse.com','https://www.cleartrip.com'],
 ['CultureTravel','698eec646ca67d6f2e24d91f','info@theenglishcountryhouse.com','https://culturetravel.in'],
 ['Booking.com','','','https://www.booking.com'],
];
$i=0; foreach ($otas as $o) $ins('ota_channels',['name'=>$o[0],'hotel_id'=>$o[1],'username'=>$o[2],'password'=>'','link'=>$o[3],'notes'=>'','sync_mode'=>'ical','active'=>1,'sort'=>$i++]);

/* ---------- pricing rules ---------- */
$ins('pricing_rules',['room_id'=>0,'name'=>'Weekend Rate','rule_type'=>'weekend','days_of_week'=>'5,6','price_mode'=>'percent_inc','value'=>20.0,'priority'=>5,'active'=>1]);
$ins('pricing_rules',['room_id'=>0,'name'=>'Peak Summer Season','rule_type'=>'season','date_from'=>date('Y').'-05-01','date_to'=>date('Y').'-06-30','price_mode'=>'percent_inc','value'=>25.0,'priority'=>10,'active'=>1]);
$ins('pricing_rules',['room_id'=>0,'name'=>'Christmas & New Year','rule_type'=>'occasion','date_from'=>date('Y').'-12-24','date_to'=>(date('Y')+1).'-01-02','price_mode'=>'percent_inc','value'=>40.0,'min_nights'=>2,'priority'=>20,'active'=>1]);
$ins('pricing_rules',['room_id'=>0,'name'=>'Monsoon Special Offer','rule_type'=>'special','date_from'=>date('Y').'-07-15','date_to'=>date('Y').'-08-31','price_mode'=>'percent_dec','value'=>15.0,'priority'=>15,'active'=>1]);

/* ---------- pages ---------- */
$about = '<p><strong>The English Country House – A Luxurious Escape in the Heart of Mussoorie</strong></p>
<p>Perched in the tranquil hills of Mussoorie, The English Country House is a boutique luxury resort that blends old-world charm with modern comfort. With just five uniquely themed bedrooms—each featuring a private balcony, elegant bathroom with a bathtub, and curated interiors—this retreat offers intimacy, privacy, and personalized indulgence.</p>
<p>Guests can relax and rejuvenate with top-tier amenities, including a gym, swimming pool, outdoor jacuzzi, sauna, and steam bath. The lush gardens, hammock corners, daybeds, and mountain-view deck provide plenty of spaces to unwind in nature\'s embrace.</p>
<p>Adventure and activity await with cycling trails, a basketball court, and scenic outdoor areas. EV charging is also available for eco-conscious travelers.</p>
<p>One of our proud highlights is the Polly House—our very own organic vegetable greenhouse, where fresh produce is grown on-site to bring farm-to-table freshness straight to your plate. These ingredients are featured in our multi-cuisine restaurant, which serves a delicious variety of global and local dishes.</p>
<p>Whether you\'re seeking a peaceful getaway, a wellness break, or a taste of hillside luxury, The English Country House invites you to experience Mussoorie in its most refined and refreshing form.</p>';

/* default drag-and-drop sections for the Home page */
$homeSections = json_encode([
 ['type'=>'hero','visible'=>1,'title'=>'Discover Tranquility At The English Country House Resort & Spa','subtitle'=>'','content'=>'A boutique luxury retreat in Mussoorie featuring five uniquely themed rooms with private balconies and elegant bathtubs — pool, jacuzzi, sauna, steam bath and a scenic mountain-view deck.','image'=>'','btn1_label'=>'Book Your Stay','btn1_url'=>'booking.php','btn2_label'=>'','btn2_url'=>'','bg'=>'','tx'=>'','opts'=>['height'=>'full','align'=>'left','valign'=>'bottom','title_size'=>'md','text_size'=>'md','text_width'=>'medium']],
 ['type'=>'booking_bar','visible'=>0,'title'=>'','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>''],
 ['type'=>'rooms','visible'=>1,'title'=>'The Grandeur Of Our Rooms','subtitle'=>'Discover Our Rooms','content'=>'','image'=>'','bg'=>'','tx'=>'','opts'=>['columns'=>'4','show_explore'=>0,'show_book'=>0,'slide_secs'=>4]],
 ['type'=>'amenities_gallery','visible'=>1,'title'=>'Enjoy Our Amenities','subtitle'=>'','content'=>'','image'=>'','bg'=>'#f6f3ee','tx'=>''],
 ['type'=>'facilities','visible'=>0,'title'=>'Our Hotel Facilities','subtitle'=>'Our Facilities','content'=>'','image'=>'','bg'=>'','tx'=>''],
 ['type'=>'reviews','visible'=>1,'title'=>'What Our Guests Say','subtitle'=>'Guest Love','content'=>'','image'=>'','bg'=>'#f6f3ee','tx'=>''],
 ['type'=>'blog','visible'=>1,'title'=>'Latest From Our Blog','subtitle'=>'From The Journal','content'=>'','image'=>'','bg'=>'','tx'=>''],
 ['type'=>'cta','visible'=>1,'title'=>'Welcome To The English Country House — Book Your Stay Today.','subtitle'=>'Book Now!','content'=>'','image'=>'','btn1_label'=>'Book Your Stay','btn1_url'=>'booking.php','bg'=>'','tx'=>''],
], JSON_UNESCAPED_UNICODE);

$pgs = [
 ['Home','home','system','', '', 'The English Country House Resort & Spa | Mussoorie', 0, 1, $homeSections],
 ['About Us','about','system',$about,'','About Us | The English Country House Mussoorie', 1, 2, '[]'],
 ['Terms & Conditions','terms-and-conditions','policy','<h3>Terms &amp; Conditions</h3><p>Check-in time is 2:00 PM and check-out is 11:00 AM. Valid government photo ID is required for all guests at check-in. The resort reserves the right of admission. Outside food and alcohol are not permitted in common areas. Pets are welcome only in designated pet-friendly rooms (Elizabeth Lodge and Charlotte Tree Houses). Damage to resort property will be charged at actuals. All disputes are subject to Dehradun jurisdiction.</p>','','Terms & Conditions',0,90,'[]'],
 ['Privacy Policy','privacy-policy','policy','<h3>Privacy Policy</h3><p>We collect only the information needed to process your booking and improve your stay — your name, contact details and stay preferences. We never sell your data. Payment details are processed securely by our payment gateway partners and are never stored on our servers. You may request deletion of your data by writing to us at info@theenglishcountryhouse.com.</p>','','Privacy Policy',0,91,'[]'],
 ['Refund & Cancellation Policy','refund-policy','policy','<h3>Refund &amp; Cancellation Policy</h3><p>Cancellations made <strong>{REFUND_DAYS} days or more before check-in</strong> are eligible for a {REFUND_PERCENT}% refund, processed automatically to the original payment method within 5–7 working days. Cancellations made within {REFUND_DAYS} days of check-in are non-refundable. In case of no-show the full booking amount is forfeited. You will be notified of every step on your registered email.</p>','','Refund & Cancellation Policy',0,92,'[]'],
];
foreach ($pgs as $p) $ins('pages',['title'=>$p[0],'slug'=>$p[1],'page_type'=>$p[2],'content'=>$p[3],'banner_image'=>$p[4],'seo_title'=>$p[5],'show_in_menu'=>$p[6],'menu_order'=>$p[7],'active'=>1,'sections'=>$p[8],'seo_desc'=>'','seo_keywords'=>'','schema_json'=>'']);

/* Explore Mussoorie — the 10 attractions */
require_once __DIR__.'/explore_data.php';
explore_seed_attractions($db);

/* sample location pages */
$locs = [
 ['Hotel Near Dhanaulti','hotel-near-dhanaulti','Dhanaulti','8 km'],
 ['Resort Near Mussoorie Mall Road','resort-near-mussoorie-mall-road','Mussoorie Mall Road','30 min drive'],
 ['Stay Near Jabarkhet Nature Reserve','stay-near-jabarkhet-nature-reserve','Jabarkhet Nature Reserve','10 min drive'],
];
foreach ($locs as $L) {
  $ins('pages',['title'=>$L[0],'slug'=>$L[1],'page_type'=>'location',
   'content'=>'<p>Looking for a luxury stay near <strong>'.$L[2].'</strong>? The English Country House is just '.$L[3].' away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting '.$L[2].' love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>',
   'banner_image'=>'','seo_title'=>$L[0].' | The English Country House','seo_desc'=>'Book a boutique luxury stay near '.$L[2].' — pool, jacuzzi, themed rooms and farm-to-table dining at The English Country House, Mussoorie.','seo_keywords'=>strtolower($L[2]).' hotel, stay near '.strtolower($L[2]),'schema_json'=>'','show_in_menu'=>0,'menu_order'=>60,'active'=>1,'sections'=>'[]']);
}

/* every other public page (Rooms, Amenities, Contact, FAQ, Blog, Gallery, Booking) + About sections */
require_once ROOT_PATH.'/install/page_defaults.php';
ensure_default_pages($db);

/* ---------- blog ---------- */
$c1 = $ins('blog_categories',['name'=>'Travel Guide','slug'=>'travel-guide','description'=>'Explore Mussoorie & around','active'=>1]);
$c2 = $ins('blog_categories',['name'=>'Resort Life','slug'=>'resort-life','description'=>'Life at The English Country House','active'=>1]);
$ins('blog_posts',['title'=>'Top 10 Things To Do In Mussoorie This Season','slug'=>'top-10-things-to-do-in-mussoorie','category_id'=>$c1,'excerpt'=>'From Camel\'s Back Road walks to Jabarkhet Nature Reserve — the definitive list for your Mussoorie holiday.','content'=>'<p>Mussoorie, the Queen of Hills, offers something for every kind of traveller. Here are our favourite experiences, minutes away from The English Country House…</p><p>1. Sunrise at our mountain-view deck. 2. A guided walk in Jabarkhet Nature Reserve. 3. Maggi point on Tehri Road. 4. Camel\'s Back Road. 5. Landour bakehouse trail…</p>','author'=>'ECH Team','status'=>'published','published_at'=>date('Y-m-d H:i:s'),'tags'=>'mussoorie,travel,things to do','image'=>'','seo_title'=>'','seo_desc'=>'','seo_keywords'=>'']);
$ins('blog_posts',['title'=>'Farm-To-Table: Inside Our Polly House Greenhouse','slug'=>'farm-to-table-polly-house','category_id'=>$c2,'excerpt'=>'How our on-site organic greenhouse brings the freshest produce straight to your plate.','content'=>'<p>The Polly House is our very own organic vegetable greenhouse where fresh produce is grown on-site. Every salad, every garnish in our multi-cuisine restaurant starts its journey here…</p>','author'=>'ECH Team','status'=>'published','published_at'=>date('Y-m-d H:i:s'),'tags'=>'food,organic,restaurant','image'=>'','seo_title'=>'','seo_desc'=>'','seo_keywords'=>'']);

/* ---------- FAQs ---------- */
$faqs = [
 ['What are the check-in and check-out timings?','Check-in is at 2:00 PM and check-out is at 11:00 AM. Early check-in / late check-out is subject to availability.','Booking'],
 ['Is the resort pet friendly?','Yes! Elizabeth Lodge and both Charlotte Tree Houses are pet friendly. Victorian Suites do not allow pets.','Rooms'],
 ['Do you have EV charging?','Yes, we provide an on-site EV charging station for eco-conscious travellers.','Facilities'],
 ['What is the cancellation policy?','Cancellations made 7 or more days before check-in receive a full refund automatically. Within 7 days of check-in the booking is non-refundable.','Booking'],
 ['Do you host events and corporate retreats?','Yes, we host private parties, celebrations and corporate events. Contact us for a customised quote.','Events'],
];
$i=0; foreach ($faqs as $f) $ins('faqs',['question'=>$f[0],'answer'=>$f[1],'category'=>$f[2],'sort'=>$i++,'active'=>1]);

/* ---------- default contact form ---------- */
$fields = json_encode([
 ['type'=>'text','label'=>'First Name','name'=>'first_name','required'=>true,'half'=>true],
 ['type'=>'text','label'=>'Last Name','name'=>'last_name','required'=>true,'half'=>true],
 ['type'=>'email','label'=>'Email Address','name'=>'email','required'=>true,'half'=>true],
 ['type'=>'text','label'=>'Subject','name'=>'subject','required'=>true,'half'=>true],
 ['type'=>'tel','label'=>'Telephone','name'=>'phone','required'=>true,'half'=>false],
 ['type'=>'textarea','label'=>'Comments / Questions','name'=>'message','required'=>true,'half'=>false],
]);
$ins('forms',['name'=>'Contact Form','fields'=>$fields,'submit_label'=>'Send Message','success_msg'=>'Thank you for reaching out! Our team will get back to you shortly.','email_to'=>'info@theenglishcountryhouse.com','create_lead'=>1,'active'=>1]);

/* ---------- chatbot Q&A ---------- */
$qa = [
 ['price,cost,tariff,rate,charges','Our rooms start at ₹8,500/night (seasonal rates apply). Would you like me to connect you with our team for the best available offer for your dates?'],
 ['pet,dog,cat','We are pet friendly! 🐾 Elizabeth Lodge and our Charlotte Tree Houses welcome pets. Victorian Suites do not allow pets.'],
 ['location,address,where,reach,directions','We are at Village Kaplani, Dhanaulti Road, Mussoorie, Uttarakhand 248179 — about 30 minutes from Mussoorie Mall Road.'],
 ['breakfast,food,restaurant,dinner,meal','Breakfast depends on the package you book, so please ask when you enquire. Our multi-cuisine restaurant serves global and local dishes made with organic produce from our own Polly House greenhouse.'],
 ['book,booking,reserve,availability,available','You can book directly on our website — just tap "Book Your Stay" — or I can connect you to our team right now for assistance.'],
 ['cancel,refund','Bookings cancelled 7+ days before check-in get an automatic full refund. Within 7 days they are non-refundable.'],
 ['pool,swimming','Yes, we have a beautiful swimming pool, plus an outdoor jacuzzi, sauna and steam bath!'],
];
$i=0; foreach ($qa as $r) $ins('chatbot_qa',['keywords'=>$r[0],'answer'=>$r[1],'sort'=>$i++,'active'=>1]);

/* ---------- canned replies for agents ---------- */
$cr = [
 ['Greeting','Hello {name}! Thank you for contacting The English Country House. This is {agent} — how may I assist you today?','hello,hi,greeting'],
 ['Room rates','Our tariff starts at ₹8,500 per night for the Victorian Suites and goes up to ₹10,500 for the Charlotte Tree Houses. May I know your travel dates so I can share the exact price and best offer?','price,rate,tariff,cost'],
 ['Availability check','I\'d be happy to check availability for you, {name}. Could you share your check-in date, check-out date and number of guests?','available,availability,dates'],
 ['Pet policy','We\'re a pet-friendly resort! Elizabeth Lodge and our two Charlotte Tree Houses welcome pets at no extra charge. The Victorian Suites don\'t allow pets.','pet,dog'],
 ['Directions','We\'re located at Village Kaplani, Dhanaulti Road, Mussoorie 248179 — I can WhatsApp you a Google Maps pin. Would that help?','location,reach,address'],
 ['Callback offer','I can arrange a quick call from our reservations team. Is this a good time to call you on {phone}?','call,callback,talk'],
];
foreach ($cr as $r) $ins('canned_replies',['title'=>$r[0],'content'=>$r[1],'keywords'=>$r[2],'active'=>1]);

/* ---------- sample reviews ---------- */
$rv = [
 ['Ananya Sharma',5.0,'A slice of England in the hills! The Victorian Suite was stunning, the bathtub with a view was unforgettable and the staff went above and beyond.'],
 ['Rohit Malhotra',5.0,'Best boutique property in Mussoorie, hands down. Loved the farm-to-table food from their own greenhouse. The jacuzzi under the stars is a must.'],
 ['Priya & Karan',4.5,'Celebrated our anniversary at the Charlotte Tree House — private, romantic and those valley views! They even arranged a candle-light dinner on the deck.'],
];
$i=0; foreach ($rv as $r) $ins('reviews',['source'=>'manual','author'=>$r[0],'rating'=>(float)$r[1],'content'=>$r[2],'review_date'=>date('Y-m-d'),'active'=>1,'sort'=>$i++,'avatar'=>'','external_id'=>'']);

/* ---------- sample popup ---------- */
$ins('popups',['title'=>'Monsoon Special — 15% Off!','content'=>'<p>Book 2 nights or more this monsoon and get <strong>15% off</strong> + complimentary bonfire evening. Limited period offer!</p>','delay_seconds'=>6,'pageview_count'=>1,'duration_seconds'=>0,'show_once_hours'=>24,'pages'=>'','active'=>0,'image'=>'']);   // round 15: off (the site shows no pop-ups)

/* ---------- round 14: on-page SEO (meta tags and page text, same as database update v14) ---------- */
require_once ROOT_PATH.'/install/seo_content.php';
seo_apply($db);

/* ---------- round 18: content corrections (same as database update v18) ---------- */
require_once ROOT_PATH.'/install/round18.php';
round18_apply($db);

/* ---------- round 19: content corrections (same as database update v19) ---------- */
require_once ROOT_PATH.'/install/round19.php';
round19_apply($db);
