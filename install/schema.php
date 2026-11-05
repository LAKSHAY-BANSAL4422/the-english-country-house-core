<?php
/**
 * Schema migrations. Key = version number, value = list of SQL statements.
 * New releases append higher version numbers — existing installs upgrade automatically.
 */
$T = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

return [

1 => [

"CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(120) PRIMARY KEY,
  svalue MEDIUMTEXT
) $T",

"CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT '',
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','admin','team') NOT NULL DEFAULT 'team',
  permissions TEXT,
  avatar VARCHAR(255) DEFAULT '',
  active TINYINT(1) DEFAULT 1,
  chat_available TINYINT(1) DEFAULT 0,
  chat_max INT DEFAULT 3,
  otp_code VARCHAR(255) DEFAULT NULL,
  otp_expires DATETIME DEFAULT NULL,
  otp_attempts INT DEFAULT 0,
  last_seen DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT '',
  password VARCHAR(255) DEFAULT NULL,
  email_verified TINYINT(1) DEFAULT 0,
  google_id VARCHAR(64) DEFAULT NULL,
  otp_code VARCHAR(255) DEFAULT NULL,
  otp_expires DATETIME DEFAULT NULL,
  otp_attempts INT DEFAULT 0,
  address VARCHAR(255) DEFAULT '',
  city VARCHAR(100) DEFAULT '',
  newsletter TINYINT(1) DEFAULT 1,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL
) $T",

"CREATE TABLE IF NOT EXISTS amenities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  icon VARCHAR(80) DEFAULT 'fa-star',
  image VARCHAR(255) DEFAULT '',
  description TEXT,
  show_home TINYINT(1) DEFAULT 1,
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1
) $T",

"CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  short_desc VARCHAR(500) DEFAULT '',
  description MEDIUMTEXT,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  weekend_price DECIMAL(10,2) DEFAULT 0,
  extra_adult_price DECIMAL(10,2) DEFAULT 0,
  child_price DECIMAL(10,2) DEFAULT 0,
  base_occupancy INT DEFAULT 2,
  max_adults INT DEFAULT 3,
  max_children INT DEFAULT 2,
  total_units INT DEFAULT 1,
  pets_allowed TINYINT(1) DEFAULT 0,
  room_size VARCHAR(60) DEFAULT '',
  bed_type VARCHAR(80) DEFAULT '',
  view_type VARCHAR(80) DEFAULT '',
  images TEXT,
  amenity_ids TEXT,
  ical_token VARCHAR(64) DEFAULT '',
  featured TINYINT(1) DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  sort INT DEFAULT 0,
  seo_title VARCHAR(255) DEFAULT '',
  seo_desc VARCHAR(500) DEFAULT '',
  seo_keywords VARCHAR(500) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS pricing_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT DEFAULT 0,
  name VARCHAR(160) NOT NULL,
  rule_type ENUM('season','occasion','special','weekend') DEFAULT 'season',
  date_from DATE NULL,
  date_to DATE NULL,
  days_of_week VARCHAR(30) DEFAULT '',
  price_mode ENUM('set','percent_inc','percent_dec','amount_inc','amount_dec') DEFAULT 'set',
  value DECIMAL(10,2) DEFAULT 0,
  extra_adult_price DECIMAL(10,2) DEFAULT NULL,
  child_price DECIMAL(10,2) DEFAULT NULL,
  min_nights INT DEFAULT 1,
  priority INT DEFAULT 10,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(20) NOT NULL UNIQUE,
  user_id INT DEFAULT NULL,
  room_id INT NOT NULL,
  checkin DATE NOT NULL,
  checkout DATE NOT NULL,
  nights INT NOT NULL DEFAULT 1,
  adults INT DEFAULT 2,
  children INT DEFAULT 0,
  guest_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) DEFAULT '',
  phone VARCHAR(30) DEFAULT '',
  address VARCHAR(255) DEFAULT '',
  special_request TEXT,
  price_breakup TEXT,
  subtotal DECIMAL(10,2) DEFAULT 0,
  tax DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  amount_paid DECIMAL(10,2) DEFAULT 0,
  payment_method VARCHAR(30) DEFAULT '',
  payment_status ENUM('unpaid','pending','paid','partial','refund_initiated','refunded','failed') DEFAULT 'unpaid',
  status ENUM('inquiry','pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
  source VARCHAR(30) DEFAULT 'online',
  admin_notes TEXT,
  cancel_reason VARCHAR(500) DEFAULT '',
  cancelled_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_room_dates (room_id, checkin, checkout),
  INDEX idx_status (status)
) $T",

"CREATE TABLE IF NOT EXISTS booking_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  gateway VARCHAR(30) NOT NULL,
  txn_id VARCHAR(190) DEFAULT '',
  order_ref VARCHAR(190) DEFAULT '',
  amount DECIMAL(10,2) DEFAULT 0,
  currency VARCHAR(10) DEFAULT 'INR',
  status ENUM('created','pending','success','failed','refunded') DEFAULT 'created',
  payload MEDIUMTEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_b (booking_id)
) $T",

"CREATE TABLE IF NOT EXISTS refunds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  payment_id INT DEFAULT NULL,
  amount DECIMAL(10,2) DEFAULT 0,
  reason VARCHAR(500) DEFAULT '',
  status ENUM('pending','processing','completed','rejected','manual') DEFAULT 'pending',
  gateway VARCHAR(30) DEFAULT '',
  gateway_refund_id VARCHAR(190) DEFAULT '',
  initiated_by VARCHAR(30) DEFAULT 'user',
  admin_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL
) $T",

"CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) DEFAULT '',
  email VARCHAR(190) DEFAULT '',
  phone VARCHAR(30) DEFAULT '',
  source VARCHAR(40) DEFAULT 'contact',
  room_id INT DEFAULT NULL,
  booking_id INT DEFAULT NULL,
  message TEXT,
  stage ENUM('new','contacted','follow_up','quoted','converted','lost') DEFAULT 'new',
  assigned_to INT DEFAULT NULL,
  tags VARCHAR(255) DEFAULT '',
  score INT DEFAULT 0,
  meta TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_stage (stage), INDEX idx_phone (phone)
) $T",

"CREATE TABLE IF NOT EXISTS lead_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  admin_id INT DEFAULT NULL,
  type ENUM('remark','call','mail','whatsapp','stage','followup','action','system') DEFAULT 'remark',
  content TEXT,
  due_at DATETIME NULL,
  done TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_l (lead_id), INDEX idx_due (due_at, done)
) $T",

"CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  page_type ENUM('system','custom','policy','location') DEFAULT 'custom',
  banner_image VARCHAR(255) DEFAULT '',
  content MEDIUMTEXT,
  sections MEDIUMTEXT,
  seo_title VARCHAR(255) DEFAULT '',
  seo_desc VARCHAR(500) DEFAULT '',
  seo_keywords VARCHAR(500) DEFAULT '',
  schema_json MEDIUMTEXT,
  show_in_menu TINYINT(1) DEFAULT 0,
  menu_order INT DEFAULT 50,
  active TINYINT(1) DEFAULT 1,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS blog_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  description VARCHAR(500) DEFAULT '',
  seo_title VARCHAR(255) DEFAULT '',
  seo_desc VARCHAR(500) DEFAULT '',
  active TINYINT(1) DEFAULT 1
) $T",

"CREATE TABLE IF NOT EXISTS blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  category_id INT DEFAULT NULL,
  excerpt VARCHAR(600) DEFAULT '',
  content MEDIUMTEXT,
  image VARCHAR(255) DEFAULT '',
  author VARCHAR(120) DEFAULT '',
  tags VARCHAR(400) DEFAULT '',
  status ENUM('draft','published') DEFAULT 'draft',
  published_at DATETIME NULL,
  views INT DEFAULT 0,
  seo_title VARCHAR(255) DEFAULT '',
  seo_desc VARCHAR(500) DEFAULT '',
  seo_keywords VARCHAR(500) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cat (category_id), INDEX idx_status (status)
) $T",

"CREATE TABLE IF NOT EXISTS faqs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(500) NOT NULL,
  answer TEXT,
  category VARCHAR(120) DEFAULT 'General',
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1
) $T",

"CREATE TABLE IF NOT EXISTS forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  fields MEDIUMTEXT,
  submit_label VARCHAR(80) DEFAULT 'Submit',
  success_msg VARCHAR(500) DEFAULT 'Thank you! We will get back to you shortly.',
  email_to VARCHAR(190) DEFAULT '',
  create_lead TINYINT(1) DEFAULT 1,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS form_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  data MEDIUMTEXT,
  page VARCHAR(255) DEFAULT '',
  ip VARCHAR(64) DEFAULT '',
  lead_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS social_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform ENUM('facebook','instagram','linkedin','x') NOT NULL,
  label VARCHAR(160) DEFAULT '',
  access_token TEXT,
  refresh_token TEXT,
  account_ref VARCHAR(190) DEFAULT '',
  extra TEXT,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS social_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platforms VARCHAR(120) NOT NULL,
  content TEXT,
  media VARCHAR(255) DEFAULT '',
  media_type ENUM('none','image','video') DEFAULT 'none',
  link VARCHAR(500) DEFAULT '',
  scheduled_at DATETIME NOT NULL,
  status ENUM('draft','scheduled','publishing','published','partial','failed') DEFAULT 'scheduled',
  results TEXT,
  created_by INT DEFAULT NULL,
  published_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sched (status, scheduled_at)
) $T",

"CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  source ENUM('google','manual') DEFAULT 'manual',
  author VARCHAR(160) DEFAULT '',
  avatar VARCHAR(400) DEFAULT '',
  rating DECIMAL(2,1) DEFAULT 5.0,
  content TEXT,
  review_date DATE NULL,
  external_id VARCHAR(190) DEFAULT '',
  active TINYINT(1) DEFAULT 1,
  sort INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS popups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  content TEXT,
  image VARCHAR(255) DEFAULT '',
  form_id INT DEFAULT NULL,
  delay_seconds INT DEFAULT 5,
  pageview_count INT DEFAULT 1,
  duration_seconds INT DEFAULT 0,
  date_from DATE NULL,
  date_to DATE NULL,
  pages VARCHAR(500) DEFAULT '',
  show_once_hours INT DEFAULT 24,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_key VARCHAR(64) NOT NULL UNIQUE,
  ip VARCHAR(64) DEFAULT '',
  user_agent VARCHAR(500) DEFAULT '',
  device ENUM('desktop','mobile','tablet','bot') DEFAULT 'desktop',
  first_page VARCHAR(255) DEFAULT '',
  referrer VARCHAR(500) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NOT NULL,
  page VARCHAR(255) DEFAULT '',
  referrer VARCHAR(500) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_v (visitor_id), INDEX idx_d (created_at)
) $T",

"CREATE TABLE IF NOT EXISTS visitor_chats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chat_key VARCHAR(64) NOT NULL UNIQUE,
  visitor_key VARCHAR(64) DEFAULT '',
  name VARCHAR(160) DEFAULT '',
  phone VARCHAR(30) DEFAULT '',
  email VARCHAR(190) DEFAULT '',
  page VARCHAR(255) DEFAULT '',
  ip VARCHAR(64) DEFAULT '',
  mode ENUM('bot','waiting','agent','whatsapp','callback') DEFAULT 'bot',
  bot_step VARCHAR(30) DEFAULT 'greet',
  agent_id INT DEFAULT NULL,
  status ENUM('open','active','closed','missed','whatsapp') DEFAULT 'open',
  rating INT DEFAULT NULL,
  lead_id INT DEFAULT NULL,
  first_response_seconds INT DEFAULT NULL,
  assigned_at DATETIME NULL,
  last_visitor_msg_at DATETIME NULL,
  last_agent_msg_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  INDEX idx_status (status), INDEX idx_agent (agent_id)
) $T",

"CREATE TABLE IF NOT EXISTS visitor_chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chat_id INT NOT NULL,
  sender ENUM('visitor','agent','bot','system') NOT NULL,
  sender_id INT DEFAULT NULL,
  message TEXT,
  seen_by_agent TINYINT(1) DEFAULT 0,
  seen_by_visitor TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_c (chat_id)
) $T",

"CREATE TABLE IF NOT EXISTS canned_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  content TEXT,
  keywords VARCHAR(400) DEFAULT '',
  active TINYINT(1) DEFAULT 1
) $T",

"CREATE TABLE IF NOT EXISTS chatbot_qa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keywords VARCHAR(400) NOT NULL,
  answer TEXT,
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1
) $T",

"CREATE TABLE IF NOT EXISTS email_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) DEFAULT '',
  body MEDIUMTEXT,
  status ENUM('sent','failed','queued') DEFAULT 'queued',
  error VARCHAR(500) DEFAULT '',
  context VARCHAR(60) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS seo_keywords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seed VARCHAR(190) NOT NULL,
  suggestions MEDIUMTEXT,
  fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_seed (seed)
) $T",

/* ---------- OTA channel manager ---------- */
"CREATE TABLE IF NOT EXISTS ota_channels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  hotel_id VARCHAR(190) DEFAULT '',
  username VARCHAR(190) DEFAULT '',
  password VARCHAR(255) DEFAULT '',
  link VARCHAR(500) DEFAULT '',
  logo VARCHAR(255) DEFAULT '',
  notes TEXT,
  sync_mode ENUM('manual','ical') DEFAULT 'ical',
  active TINYINT(1) DEFAULT 1,
  sort INT DEFAULT 0,
  last_sync DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

"CREATE TABLE IF NOT EXISTS ota_room_map (
  id INT AUTO_INCREMENT PRIMARY KEY,
  channel_id INT NOT NULL,
  room_id INT NOT NULL,
  ota_room_id VARCHAR(190) DEFAULT '',
  ota_room_name VARCHAR(190) DEFAULT '',
  ical_import_url VARCHAR(600) DEFAULT '',
  active TINYINT(1) DEFAULT 1,
  INDEX idx_ch (channel_id), INDEX idx_rm (room_id)
) $T",

"CREATE TABLE IF NOT EXISTS ota_rate_map (
  id INT AUTO_INCREMENT PRIMARY KEY,
  channel_id INT NOT NULL,
  room_id INT NOT NULL,
  ota_rate_plan_id VARCHAR(190) DEFAULT '',
  rate_plan_name VARCHAR(190) DEFAULT '',
  price_mode ENUM('same','markup_percent','markup_amount','discount_percent') DEFAULT 'same',
  value DECIMAL(10,2) DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  INDEX idx_ch (channel_id)
) $T",

"CREATE TABLE IF NOT EXISTS room_blocks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  source VARCHAR(60) DEFAULT 'manual',
  ref VARCHAR(190) DEFAULT '',
  note VARCHAR(255) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_r (room_id, date_from, date_to)
) $T",

/* ---------- admin activity audit log ---------- */
"CREATE TABLE IF NOT EXISTS admin_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT DEFAULT NULL,
  module VARCHAR(60) DEFAULT '',
  action VARCHAR(60) DEFAULT '',
  detail TEXT,
  ip VARCHAR(64) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_a (admin_id), INDEX idx_m (module), INDEX idx_d (created_at)
) $T",

],

2 => [

/* ---------- gallery ---------- */
"CREATE TABLE IF NOT EXISTS gallery_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image VARCHAR(255) NOT NULL,
  title VARCHAR(190) DEFAULT '',
  category VARCHAR(120) DEFAULT 'General',
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $T",

/* ---------- document makers: letterhead / invoice / mou ---------- */
"CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doc_type ENUM('letterhead','invoice','mou') NOT NULL,
  ref_no VARCHAR(60) DEFAULT '',
  title VARCHAR(255) NOT NULL,
  party_name VARCHAR(190) DEFAULT '',
  party_details TEXT,
  doc_date DATE NULL,
  content MEDIUMTEXT,
  data MEDIUMTEXT,
  status VARCHAR(30) DEFAULT 'draft',
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_t (doc_type)
) $T",

],

3 => [

/* ---------- brute-force protection ---------- */
"CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(64) NOT NULL,
  email VARCHAR(190) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip, created_at)
) $T",

"INSERT IGNORE INTO settings (skey,svalue) VALUES
  ('clean_urls','1'),('maintenance_mode','0'),
  ('maintenance_msg','We are polishing something beautiful for you. The English Country House website will be back shortly — meanwhile you can call or WhatsApp us to book your stay.'),
  ('cache_bust','1')",

"CREATE TABLE IF NOT EXISTS ip_bans (
  ip VARCHAR(64) PRIMARY KEY,
  reason VARCHAR(190) DEFAULT '',
  banned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  until DATETIME NOT NULL
) $T",

],

/* ============ v4 — payment-option master switch + eye-catching typography ============ */
4 => [

"INSERT IGNORE INTO settings (skey,svalue) VALUES
  ('pay_online_enabled','1'),
  ('font_accent','Great Vibes')",

/* upgrade installs still on the old default (dull) fonts to the new pairing;
   custom font choices are left untouched */
"UPDATE settings SET svalue='Cormorant Garamond' WHERE skey='font_heading' AND svalue='Playfair Display'",
"UPDATE settings SET svalue='Outfit' WHERE skey='font_body' AND svalue='Jost'",

],

/* ============ v5 — Restaurant: Our Menu, QR ordering, food orders, WhatsApp routing ============ */
5 => [

"CREATE TABLE IF NOT EXISTS menu_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  subtitle VARCHAR(255) DEFAULT '',
  icon VARCHAR(60) DEFAULT 'fa-utensils',
  image VARCHAR(255) DEFAULT '',
  time_from TIME NULL,
  time_to TIME NULL,
  advance_order TINYINT(1) DEFAULT 0,
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sort (active, sort)
) $T",

"CREATE TABLE IF NOT EXISTS menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  description VARCHAR(600) DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  diet ENUM('veg','nonveg','egg','both') DEFAULT 'veg',
  variants VARCHAR(500) DEFAULT '',
  tags VARCHAR(160) DEFAULT '',
  image VARCHAR(255) DEFAULT '',
  sort INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  available TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cat (category_id, active, sort)
) $T",

"CREATE TABLE IF NOT EXISTS food_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(20) NOT NULL UNIQUE,
  token VARCHAR(40) NOT NULL,
  user_id INT NULL,
  room_no VARCHAR(120) DEFAULT '',
  guest_name VARCHAR(140) DEFAULT '',
  phone VARCHAR(30) DEFAULT '',
  email VARCHAR(190) DEFAULT '',
  notes VARCHAR(600) DEFAULT '',
  subtotal DECIMAL(10,2) DEFAULT 0,
  tax DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  item_count INT DEFAULT 0,
  status ENUM('new','accepted','preparing','ready','delivered','cancelled') DEFAULT 'new',
  eta_minutes INT DEFAULT 30,
  source VARCHAR(20) DEFAULT 'website',
  wa_status VARCHAR(255) DEFAULT '',
  handled_by INT NULL,
  ip VARCHAR(64) DEFAULT '',
  accepted_at DATETIME NULL,
  delivered_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status, created_at),
  INDEX idx_user (user_id), INDEX idx_phone (phone)
) $T",

"CREATE TABLE IF NOT EXISTS food_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  item_id INT NULL,
  name VARCHAR(200) NOT NULL,
  variant VARCHAR(120) DEFAULT '',
  diet VARCHAR(10) DEFAULT 'veg',
  qty INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  INDEX idx_order (order_id)
) $T",

"INSERT IGNORE INTO settings (skey,svalue) VALUES
  ('menu_enabled','1'),('menu_ordering_enabled','1'),
  ('menu_title','Our Menu'),('menu_restaurant_name','Multicuisine Restaurant'),
  ('menu_tagline','Savor the Oak. Discover the Ivy.'),
  ('menu_intro','Freshly prepared in our kitchen and served to your room, the deck or the restaurant. Choose your dishes, tell us your room — we will take it from there.'),
  ('menu_hero_image','assets/img/restaurant.jpg'),
  ('menu_price_note','All prices are in ₹ (INR). Government taxes as applicable.'),
  ('menu_tax_percent','0'),('menu_tax_label','GST'),
  ('menu_wait_minutes','30'),('menu_wait_note','Every dish is cooked fresh to order'),
  ('menu_order_phone','9456765555'),
  ('menu_kitchen_from',''),('menu_kitchen_to',''),('menu_enforce_timings','0'),
  ('menu_min_order','0'),('menu_room_required','1'),
  ('menu_wa_numbers','[{\"name\":\"Reception\",\"number\":\"9456765555\",\"active\":1}]'),
  ('menu_wa_mode','link'),('menu_wa_cc','91'),('menu_wa_guest_open','1'),
  ('menu_wa_cloud_token',''),('menu_wa_cloud_phone_id',''),('menu_wa_cloud_template',''),('menu_wa_cloud_lang','en'),
  ('menu_wa_gateway_url',''),('menu_wa_gateway_method','GET'),('menu_wa_gateway_body',''),
  ('menu_alert_email','1'),('menu_alert_repeat','1'),
  ('menu_qr_logo','assets/img/brand/logo.png'),('menu_qr_color','#163a2c'),('menu_qr_rooms',''),
  ('menu_theme_color','#163a2c'),('menu_show_footer_qr','1'),
  ('logo_print','assets/img/brand/logo.png')",

"ALTER TABLE gallery_images ADD COLUMN source_url VARCHAR(500) DEFAULT ''",
"ALTER TABLE gallery_images ADD INDEX idx_src (source_url(191))",

/* load the restaurant menu, brand logo, nav link + gallery photo (only where still empty) */
function (mysqli $db) {
    $cnt = (int)$db->query("SELECT COUNT(*) FROM menu_categories")->fetch_row()[0];
    if ($cnt === 0) {
        $data = require ROOT_PATH.'/install/menu_data.php';
        $cs = $db->prepare("INSERT INTO menu_categories (name,slug,subtitle,icon,time_from,time_to,advance_order,image,sort,active) VALUES (?,?,?,?,?,?,?,?,?,1)");
        $is = $db->prepare("INSERT INTO menu_items (category_id,name,description,price,diet,variants,tags,sort,active,available) VALUES (?,?,?,?,?,?,?,?,1,1)");
        foreach ($data as $ci => [$cname, $csub, $cicon, $tf, $tt, $adv, $cimg, $items]) {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $cname) ?: $cname)), '-');
            $tf = $tf ?: null; $tt = $tt ?: null; $sort = ($ci + 1) * 10;
            $cs->bind_param('ssssssisi', $cname, $slug, $csub, $cicon, $tf, $tt, $adv, $cimg, $sort);
            $cs->execute();
            $catId = $db->insert_id;
            foreach ($items as $ii => [$n, $p, $d, $diet, $var, $tags]) {
                $p = (float)$p; $s = ($ii + 1) * 10;
                $is->bind_param('issdsssi', $catId, $n, $d, $p, $diet, $var, $tags, $s);
                $is->execute();
            }
        }
    }
    $get = function (string $k) use ($db) {
        $st = $db->prepare("SELECT svalue FROM settings WHERE skey=?"); $st->bind_param('s', $k); $st->execute();
        $r = $st->get_result()->fetch_row(); return $r ? (string)$r[0] : null;
    };
    $put = function (string $k, string $v) use ($db) {
        $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES (?,?)"); $st->bind_param('ss', $k, $v); $st->execute();
    };
    // brand logo (from the client's menu artwork) where no logo has been uploaded yet
    if (($get('logo_header') ?? '') === '') { $put('logo_header', 'assets/img/brand/logo-light.png'); $put('logo_header_h', '64'); $put('logo_header_w', '0'); }
    if (($get('logo_footer') ?? '') === '') { $put('logo_footer', 'assets/img/brand/logo-light.png'); $put('logo_footer_h', '110'); $put('logo_footer_w', '0'); }
    if (($get('favicon') ?? '') === '')     { $put('favicon', 'assets/img/brand/favicon.png'); }
    // "Our Menu" in header navigation + footer quick links (existing installs)
    foreach (['menu_items' => 'Contact', 'footer_links' => null] as $key => $before) {
        $raw = $get($key);
        if ($raw === null) continue;                       // fresh install: seed adds it
        $list = json_decode($raw, true) ?: [];
        foreach ($list as $m) if (($m['url'] ?? '') === 'menu.php') continue 2;
        $entry = ['label' => 'Our Menu', 'url' => 'menu.php', 'visible' => 1];
        $pos = count($list);
        if ($before) foreach ($list as $i => $m) if (strcasecmp($m['label'] ?? '', $before) === 0) { $pos = $i; break; }
        array_splice($list, $pos, 0, [$entry]);
        $put($key, json_encode($list, JSON_UNESCAPED_UNICODE));
    }
    // restaurant photo into the gallery
    $g = $db->query("SELECT COUNT(*) FROM gallery_images WHERE image='assets/img/restaurant.jpg'")->fetch_row()[0];
    if (!(int)$g) $db->query("INSERT INTO gallery_images (image,title,category,sort,active) VALUES ('assets/img/restaurant.jpg','Our Multicuisine Restaurant','Restaurant',1,1)");
},

],

/* ============ v6 — every page editable: banners, sections for all pages, room gallery & video, live editor ============ */
6 => [

"ALTER TABLE pages ADD COLUMN banner_title VARCHAR(190) DEFAULT ''",
"ALTER TABLE pages ADD COLUMN banner_subtitle VARCHAR(255) DEFAULT ''",
"ALTER TABLE pages ADD COLUMN show_banner TINYINT(1) DEFAULT 1",
"ALTER TABLE rooms ADD COLUMN cover_image VARCHAR(255) DEFAULT ''",
"ALTER TABLE rooms ADD COLUMN video_url VARCHAR(500) DEFAULT ''",
"ALTER TABLE rooms ADD COLUMN video_file VARCHAR(255) DEFAULT ''",
"ALTER TABLE rooms ADD COLUMN highlights TEXT",
"ALTER TABLE amenities ADD COLUMN images TEXT",

"INSERT IGNORE INTO settings (skey,svalue) VALUES ('live_editor_enabled','1'),('banner_overlay','0.55'),('upload_max_px','2000'),('upload_quality','86')",

/* create the missing system pages (Rooms, Amenities, Contact, FAQ, Blog, Gallery, Booking) and give
   About / location pages their default editable sections — existing content is never overwritten */
function (mysqli $db) {
    $n = (int)$db->query("SELECT COUNT(*) FROM pages")->fetch_row()[0];
    if ($n === 0) return;                      // fresh install: seed.php does this after creating its pages
    require_once ROOT_PATH.'/install/page_defaults.php';
    ensure_default_pages($db);
},

],

/* ============ v7 — Explore Mussoorie, booking calendar modal, media optimisation, content tweaks ============ */
7 => [

"CREATE TABLE IF NOT EXISTS attractions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) DEFAULT '',
  category VARCHAR(40) DEFAULT 'other',
  label VARCHAR(80) DEFAULT '',
  description TEXT,
  long_description MEDIUMTEXT,
  distance_km VARCHAR(30) DEFAULT '',
  travel_time VARCHAR(60) DEFAULT '',
  lat DECIMAL(10,6) NULL,
  lng DECIMAL(10,6) NULL,
  maps_url VARCHAR(500) DEFAULT '',
  image VARCHAR(255) DEFAULT '',
  images TEXT,
  featured TINYINT(1) DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  sort INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (active, sort), INDEX (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"INSERT IGNORE INTO settings (skey,svalue) VALUES ('book_modal_enabled','1'),('book_float_enabled','1'),('media_webp','1'),('media_webp_quality','78'),
  ('media_video_server','1'),('media_video_compress_mb','6'),('media_video_height','720'),('media_video_crf','27'),('ffmpeg_path',''),('upload_quality','82')",

function (mysqli $db) {
    $fresh = (int)$db->query("SELECT COUNT(*) FROM pages")->fetch_row()[0] === 0;   // fresh install: seed.php handles content
    require_once ROOT_PATH.'/install/explore_data.php';
    require_once ROOT_PATH.'/install/page_defaults.php';
    if ($fresh) return;
    explore_seed_attractions($db);
    ensure_default_pages($db);                         // creates the "Explore Mussoorie" page + contact strip if missing
    $get = function (string $k) use ($db) { $r = $db->query("SELECT svalue FROM settings WHERE skey='".$db->real_escape_string($k)."'")->fetch_row(); return $r ? $r[0] : null; };
    $put = function (string $k, string $v) use ($db) { $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES (?,?)"); $st->bind_param('ss', $k, $v); $st->execute(); };
    // navigation: "Explore Mussoorie" after Amenities (header) and in the footer quick links
    foreach (['menu_items' => 'Amenities', 'footer_links' => 'Our Menu'] as $key => $after) {
        $raw = $get($key); if ($raw === null) continue;
        $list = json_decode($raw, true) ?: [];
        foreach ($list as $m) if (($m['url'] ?? '') === 'explore.php') continue 2;
        $pos = count($list);
        foreach ($list as $i => $m) if (strcasecmp($m['label'] ?? '', $after) === 0) { $pos = $i + 1; break; }
        array_splice($list, $pos, 0, [['label' => 'Explore Mussoorie', 'url' => 'explore.php', 'visible' => 1]]);
        $put($key, json_encode($list, JSON_UNESCAPED_UNICODE));
    }
    // amenities the client listed that are still missing (photos are added in Admin → Amenities)
    $have = array_map('strtolower', array_column($db->query("SELECT name FROM amenities")->fetch_all(MYSQLI_ASSOC), 'name'));
    $max = (int)$db->query("SELECT COALESCE(MAX(sort),0) FROM amenities")->fetch_row()[0];
    foreach ([['Green House','fa-seedling','Our Polly House organic greenhouse — farm-to-table produce grown on site.','green'],
              ['Table Tennis','fa-table-tennis-paddle-ball','Indoor table tennis for a friendly rally any time of day.','table tennis'],
              ['Restaurant & Dining','fa-utensils','Multi-cuisine restaurant and scenic dining spaces across the house.','restaurant']] as $a) {
        $exists = false; foreach ($have as $h) if (str_contains($h, $a[3])) { $exists = true; break; }
        if ($exists) continue;
        $st = $db->prepare("INSERT INTO amenities (name,icon,description,image,images,show_home,sort,active) VALUES (?,?,?,'','[]',1,?,1)"); $max++; $st->bind_param('sssi', $a[0], $a[1], $a[2], $max); $st->execute();
    }
    // home page: hero text to the left without tagline, quick-booking bar hidden (the Book button opens the calendar), "Grandeur" heading
    $home = $db->query("SELECT id, sections FROM pages WHERE slug='home'")->fetch_assoc();
    if ($home) {
        $secs = json_decode((string)$home['sections'], true) ?: []; $changed = false;
        foreach ($secs as &$sec) {
            $t = $sec['type'] ?? '';
            if ($t === 'hero') { $sec['subtitle'] = ''; $sec['opts'] = ($sec['opts'] ?? []) + []; $sec['opts']['align'] = 'left'; $changed = true; }
            if ($t === 'booking_bar' && !empty($sec['visible'])) { $sec['visible'] = 0; $changed = true; }
            if ($t === 'rooms' && stripos((string)($sec['title'] ?? ''), 'gander') !== false) { $sec['title'] = 'The Grandeur Of Our Rooms'; $sec['subtitle'] = 'Discover Our Rooms'; $changed = true; }
        }
        unset($sec);
        if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$home['id']; $st->bind_param('si', $j, $id); $st->execute(); }
    }
    // same "Gander" heading on the Rooms page and anywhere else
    $res = $db->query("SELECT id, sections FROM pages WHERE slug<>'home' AND sections LIKE '%Gander%'");
    while ($p = $res->fetch_assoc()) {
        $j = str_replace(['A Gander At Our Rooms', 'Discover Our Room"'], ['The Grandeur Of Our Rooms', 'Discover Our Rooms"'], (string)$p['sections']);
        $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$p['id']; $st->bind_param('si', $j, $id); $st->execute();
    }
},

],

/* ============ v8 — hero: single "Book Your Stay" button (the Explore button is removed), self-hosted icons/map/chart libraries ============ */
8 => [
function (mysqli $db) {
    $home = $db->query("SELECT id, sections FROM pages WHERE slug='home'")->fetch_assoc();
    if (!$home) return;
    $secs = json_decode((string)$home['sections'], true) ?: []; $changed = false;
    foreach ($secs as &$sec) if (($sec['type'] ?? '') === 'hero' && stripos((string)($sec['btn2_label'] ?? ''), 'explore') !== false) { $sec['btn2_label'] = ''; $sec['btn2_url'] = ''; $changed = true; }
    unset($sec);
    if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$home['id']; $st->bind_param('si', $j, $id); $st->execute(); }
},
],

/* ============ v9 — home page: "Our Hotel Facilities" icon section hidden; WhatsApp becomes the right-side floating button ============ */
9 => [
function (mysqli $db) {
    $home = $db->query("SELECT id, sections FROM pages WHERE slug='home'")->fetch_assoc();
    if (!$home) return;
    $secs = json_decode((string)$home['sections'], true) ?: []; $changed = false;
    foreach ($secs as &$sec) if (($sec['type'] ?? '') === 'facilities' && !empty($sec['visible'])) { $sec['visible'] = 0; $changed = true; }
    unset($sec);
    if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$home['id']; $st->bind_param('si', $j, $id); $st->execute(); }
    // the floating WhatsApp button is shown on phones and desktops (it replaced the bottom-bar item)
    foreach (['wa_show_mobile'=>'1','wa_show_desktop'=>'1','wa_position'=>'right'] as $k => $v) { $st = $db->prepare("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)"); $st->bind_param('ss', $k, $v); $st->execute(); }
},
],

/* ============ v10 — home: 4 rooms per row, smaller hero text (admin-adjustable), Explore button off on room cards ============ */
10 => [
function (mysqli $db) {
    $res = $db->query("SELECT id, slug, sections FROM pages WHERE sections LIKE '%\"rooms\"%' OR sections LIKE '%\"hero\"%'");
    while ($p = $res->fetch_assoc()) {
        $secs = json_decode((string)$p['sections'], true) ?: []; $changed = false;
        foreach ($secs as &$sec) {
            $t = $sec['type'] ?? '';
            if ($t === 'rooms') { $sec['opts'] = (array)($sec['opts'] ?? []); $sec['opts']['show_explore'] = 0; if ($p['slug'] === 'home') $sec['opts']['columns'] = '4'; $changed = true; }
            if ($t === 'hero' && $p['slug'] === 'home') { $sec['opts'] = (array)($sec['opts'] ?? []); $sec['opts'] += ['title_size'=>'md', 'text_size'=>'md', 'text_width'=>'medium']; $changed = true; }
        }
        unset($sec);
        if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$p['id']; $st->bind_param('si', $j, $id); $st->execute(); }
    }
},
],

/* ============ v11 — room cards open the room page (no Book button); home hero text at the bottom-left ============ */
11 => [
function (mysqli $db) {
    $res = $db->query("SELECT id, slug, sections FROM pages WHERE sections LIKE '%\"rooms\"%' OR slug='home'");
    while ($p = $res->fetch_assoc()) {
        $secs = json_decode((string)$p['sections'], true) ?: []; $changed = false;
        foreach ($secs as &$sec) {
            $t = $sec['type'] ?? '';
            if ($t === 'rooms') { $sec['opts'] = (array)($sec['opts'] ?? []); $sec['opts']['show_book'] = 0; $changed = true; }
            if ($t === 'hero' && $p['slug'] === 'home') { $sec['opts'] = (array)($sec['opts'] ?? []); $sec['opts']['valign'] = 'bottom'; $sec['opts']['align'] = 'left'; $changed = true; }
        }
        unset($sec);
        if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$p['id']; $st->bind_param('si', $j, $id); $st->execute(); }
    }
},
],

/* ============ v12 — round 11: four more Explore Mussoorie places, full name in the home hero title, Instagram link, no Facebook ============ */
12 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/explore_data.php';
    explore_seed_attractions($db);                     // Landour & Char Dukan, Gun Hill, Kempty Falls, Mall Road (only if missing)
    $res = $db->query("SELECT id, slug, sections FROM pages WHERE slug IN ('home','explore')");
    while ($p = $res->fetch_assoc()) {
        $secs = json_decode((string)$p['sections'], true) ?: []; $changed = false;
        foreach ($secs as &$sec) {
            $t = $sec['type'] ?? '';
            if ($t === 'hero' && $p['slug'] === 'home' && isset($sec['title'])) {
                $nt = preg_replace('/The English Country House(?!\s+Resort)/i', 'The English Country House Resort & Spa', (string)$sec['title']);
                if ($nt !== $sec['title']) { $sec['title'] = $nt; $changed = true; }
            }
            if ($p['slug'] === 'explore' && $t === 'attractions_map' && (int)($sec['opts']['limit'] ?? 0) === 12) { $sec['opts']['limit'] = 0; $changed = true; }   // 12 hid two of the 14 places
            if ($p['slug'] === 'explore' && $t === 'attractions') {
                $sec['opts'] = (array)($sec['opts'] ?? []);
                // the "Must-Visit" grid stopped at 8 places: show them all so the new ones are not cut off
                if (($sec['opts']['category'] ?? '') === 'no_waterfall' && (int)($sec['opts']['limit'] ?? 0) === 8) { $sec['opts']['limit'] = 0; $changed = true; }
                if (($sec['opts']['category'] ?? '') === 'waterfall' && strpos((string)($sec['content'] ?? ''), 'Kempty') === false) {
                    $sec['content'] = str_replace('at Bhatta Falls and Jharipani Falls', 'at Kempty Falls, Bhatta Falls and Jharipani Falls', (string)($sec['content'] ?? '')); $changed = true;
                }
            }
        }
        unset($sec);
        if ($changed) { $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $id = (int)$p['id']; $st->bind_param('si', $j, $id); $st->execute(); }
    }
    // Instagram: the hotel's profile replaces the empty install default; Facebook is cleared (the page is not in use)
    $ig = 'https://www.instagram.com/the_english_country_house?psln=NjBqN2x1Y21oa2Rt';
    $st = $db->prepare("UPDATE settings SET svalue=? WHERE skey='social_instagram' AND (svalue='' OR svalue REGEXP '^https?://(www\\.)?instagram\\.com/?$')"); $st->bind_param('s', $ig); $st->execute();
    $db->query("INSERT INTO settings (skey,svalue) VALUES ('social_facebook','') ON DUPLICATE KEY UPDATE svalue=''");
},
],

/* ============ v13 — round 13: exact map positions for the four round-11 places; Facebook settings removed ============ */
13 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/explore_data.php';
    $st = $db->prepare("UPDATE attractions SET lat=?, lng=?, maps_url=? WHERE name=?");
    foreach (explore_default_attractions() as $a) {
        if (empty($a['maps_url'])) continue;       // only the places the client sent Google Maps links for
        $st->bind_param('ddss', $a['lat'], $a['lng'], $a['maps_url'], $a['name']); $st->execute();
    }
    $db->query("DELETE FROM settings WHERE skey='social_facebook'");
},
],

/* ============ v14 — round 14: on-page SEO (meta titles, descriptions and keywords on every page; page text, see install/seo_content.php) ============ */
14 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/seo_content.php';
    seo_apply($db);
},
],

/* ============ v15 — round 15: Victorian Suite 2 is a king bed, not a four-poster (text written by v14); no pop-ups ============ */
15 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/seo_content.php';
    seo_fix_round15($db);
    $db->query("UPDATE popups SET active=0 WHERE active=1");   // the site shows no pop-ups (the "Monsoon Special" offer was the only one)
},
],

/* ============ v16 — round 16: the owner's About us photo on the About page (it showed a broken image) ============ */
16 => [
function (mysqli $db) {
    $r = $db->query("SELECT id, sections FROM pages WHERE slug='about'")->fetch_assoc();
    if (!$r) return;
    $secs = json_decode((string)$r['sections'], true) ?: [];
    foreach ($secs as &$sec) if (($sec['type'] ?? '') === 'image_text') { $sec['image'] = 'assets/img/redesign/about-us.jpg'; break; }   // the first image + text section only
    unset($sec);
    $j = json_encode(array_values($secs), JSON_UNESCAPED_UNICODE); $id = (int)$r['id'];
    $st = $db->prepare("UPDATE pages SET sections=? WHERE id=?"); $st->bind_param('si', $j, $id); $st->execute();
},
],

/* ============ v17 — round 17: technical SEO (robots.txt keeps private pages out of search; llms.txt built from the live site) ============ */
17 => [
function (mysqli $db) {
    $get = function (string $k) use ($db) { $r = $db->query("SELECT svalue FROM settings WHERE skey='".$db->real_escape_string($k)."'")->fetch_row(); return $r ? (string)$r[0] : null; };
    $put = function (string $k, string $v) use ($db) { $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES (?,?)"); $st->bind_param('ss', $k, $v); $st->execute(); };
    // robots.txt: replaced only while it is still the text the site shipped with (an edited one is kept)
    $robots = $get('robots_txt');
    $old = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /team/\nDisallow: /api/\nSitemap: {BASE_URL}/sitemap.xml";
    if ($robots === null || trim(str_replace("\r", '', $robots)) === $old)
        $put('robots_txt', "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /team/\nDisallow: /api/\nDisallow: /account/\nDisallow: /cron/\nDisallow: /install/\nDisallow: /pay\nDisallow: /booking-done\nDisallow: /invoice\nDisallow: /unsubscribe\nDisallow: /*?*edit=1\n\nSitemap: {BASE_URL}/sitemap.xml");
    // llms.txt: the shipped text still named "Charlotte Tree Houses" and the old .php addresses → the live summary
    $llms = $get('llms_txt');
    if ($llms === null || trim($llms) === '' || (str_contains($llms, 'Charlotte Tree House') && str_contains($llms, 'rooms.php'))) $put('llms_txt', '{AUTO}');
},
],

/* ============ v18 — round 18: no "complimentary breakfast", refunds 7 days before check-in, no Website field on the Contact Form,
                  the exact map pin (see install/round18.php) ============ */
18 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/round18.php';
    round18_apply($db);
},
],

/* ============ v19 — round 19: "Charlotte Tree House 1/2", check-in 2:00 PM, breakfast FAQ "depends on the package"
                  (see install/round19.php) ============ */
19 => [
function (mysqli $db) {
    require_once ROOT_PATH.'/install/round19.php';
    round19_apply($db);
},
],

/* ============ v20 — v21 build (audit 2 Nov 2026): home meta description under 135 characters (only while it is still the shipped text) ============ */
20 => [
function (mysqli $db) {
    $old = "A boutique luxury resort on Mussoorie's Dhanaulti Road: themed suites with valley views, outdoor jacuzzi, sauna, pool and farm-to-table dining.";
    $new = "Boutique luxury resort in Mussoorie on Dhanaulti Road: themed suites with valley views, jacuzzi, sauna, pool and farm-to-table dining.";
    $st = $db->prepare("UPDATE settings SET svalue=? WHERE skey='seo_desc' AND svalue=?"); $st->bind_param('ss', $new, $old); $st->execute();
    $st = $db->prepare("UPDATE pages SET seo_desc=? WHERE slug='home' AND seo_desc=?"); $st->bind_param('ss', $new, $old); $st->execute();
},
],
];
