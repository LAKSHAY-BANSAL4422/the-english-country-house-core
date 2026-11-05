-- ============================================================
--  The English Country House — Hotel Booking & Management Suite v5.2.4
--  Ready-to-import database (schema v10 + demo content + full restaurant menu + Explore Mussoorie)
--  ------------------------------------------------------------
--  FRESH INSTALL ONLY. If your site already has data, do NOT import this:
--  just upload the new files — the app upgrades your existing database
--  automatically (v6 → v10) the first time a page loads, clears the caches
--  and shows a green "Website updated" note in the admin. Nothing is lost.
--  HOW TO USE (cPanel): phpMyAdmin → empty database → Import → this file,
--  then set config.php. Default admin: admin@example.com / admin123 (change it!)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_meta` (
  `mkey` varchar(64) NOT NULL,
  `mval` text DEFAULT NULL,
  PRIMARY KEY (`mkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `_meta` WRITE;
/*!40000 ALTER TABLE `_meta` DISABLE KEYS */;
INSERT INTO `_meta` VALUES
('app_version','5.2.4'),
('schema_version','10');
/*!40000 ALTER TABLE `_meta` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admin_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `module` varchar(60) DEFAULT '',
  `action` varchar(60) DEFAULT '',
  `detail` text DEFAULT NULL,
  `ip` varchar(64) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_a` (`admin_id`),
  KEY `idx_m` (`module`),
  KEY `idx_d` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admin_activity` WRITE;
/*!40000 ALTER TABLE `admin_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_activity` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','team') NOT NULL DEFAULT 'team',
  `permissions` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT '',
  `active` tinyint(1) DEFAULT 1,
  `chat_available` tinyint(1) DEFAULT 0,
  `chat_max` int(11) DEFAULT 3,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `last_seen` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES
(1,'Super Admin','admin@example.com','','$2y$12$EhfIMG.zBmQddmt0PkgAdeqOfjJ88HcRhgakjspsevWsPibpf/uSi','superadmin','[\"*\"]','',1,0,3,NULL,NULL,0,NULL,'2026-10-17 19:49:52');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `icon` varchar(80) DEFAULT 'fa-star',
  `image` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `show_home` tinyint(1) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `images` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `amenities` WRITE;
/*!40000 ALTER TABLE `amenities` DISABLE KEYS */;
INSERT INTO `amenities` VALUES
(1,'Air Conditioner','fa-snowflake','','',1,0,1,NULL),
(2,'Breakfast','fa-utensils','','',1,1,1,NULL),
(3,'Fiber Wifi','fa-wifi','','',1,2,1,NULL),
(4,'Swimming Pool','fa-person-swimming','','',1,3,1,NULL),
(5,'EV Charging Station','fa-charging-station','','',1,4,1,NULL),
(6,'Parking Space','fa-square-parking','','',1,5,1,NULL),
(7,'Library','fa-book','','',1,6,1,NULL),
(8,'Fitness / Gym','fa-dumbbell','','',1,7,1,NULL),
(9,'Yoga House','fa-spa','','',1,8,1,NULL),
(10,'Basketball Court','fa-basketball','','',1,9,1,NULL),
(11,'Outdoor Jacuzzi','fa-hot-tub-person','','',1,10,1,NULL),
(12,'Wall Climbing','fa-mountain','','',1,11,1,NULL),
(13,'Sauna & Steam Bath','fa-temperature-high','','',1,12,1,NULL),
(14,'Multi-cuisine Restaurant','fa-bowl-food','','',1,13,1,NULL),
(15,'Snooker Room','fa-circle-dot','','',1,14,1,NULL),
(16,'Mountain View Deck','fa-mountain-sun','','',1,15,1,NULL),
(17,'Green House','fa-seedling','','',1,16,1,NULL),
(18,'Table Tennis','fa-table-tennis-paddle-ball','','',1,17,1,NULL);
/*!40000 ALTER TABLE `amenities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attractions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attractions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `slug` varchar(190) DEFAULT '',
  `category` varchar(40) DEFAULT 'other',
  `label` varchar(80) DEFAULT '',
  `description` text DEFAULT NULL,
  `long_description` mediumtext DEFAULT NULL,
  `distance_km` varchar(30) DEFAULT '',
  `travel_time` varchar(60) DEFAULT '',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `maps_url` varchar(500) DEFAULT '',
  `image` varchar(255) DEFAULT '',
  `images` text DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`sort`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attractions` WRITE;
/*!40000 ALTER TABLE `attractions` DISABLE KEYS */;
INSERT INTO `attractions` VALUES
(1,'Lal Tibba Scenic Point','lal-tibba-scenic-point','viewpoint','Viewpoint','Historic colonial-era vantage point and the highest peak in Mussoorie, offering panoramic Himalayan views.','<p>Perched in Landour at about 2,290 m, Lal Tibba is the highest point in Mussoorie. On a clear morning the telescope at the viewpoint picks out the snow peaks of Banderpunch, Kedarnath and Badrinath. Pair it with a walk through the old Landour bazaar and a cup of tea at a hillside café.</p>','8.3','~25 mins',30.463600,78.100000,'','','[]',1,1,1,'2026-10-17 14:19:53'),
(2,'Benog Wildlife Sanctuary','benog-wildlife-sanctuary','nature','Wildlife Sanctuary','Scenic forest reserve featuring pine and fir trees, ideal for birdwatching, nature walks, and trekking.','<p>Part of the Rajaji National Park buffer, Benog is a quiet reserve of pine, deodar and oak. Look out for the Himalayan goral, red-billed blue magpies and, if you are lucky, the rare mountain quail. The trek to the Jwalaji Temple on Benog Tibba is a gentle 4 km climb with sweeping valley views.</p>','6 to 7','~20 mins',30.468000,78.033000,'','','[]',1,1,2,'2026-10-17 14:19:53'),
(3,'Cloud End','cloud-end','historic','Scenic Spot','Historic heritage building surrounded by thick forests, marking the geographical end of Mussoorie with peaceful walking trails.','<p>Built in 1838 by a British major, Cloud End was one of the first four buildings in Mussoorie. Today it sits at the quiet western edge of the hill station, wrapped in thick forest with walking trails that feel a world away from the Mall Road crowds.</p>','5 to 6','~18 mins',30.460000,78.021000,'','','[]',1,1,3,'2026-10-17 14:19:53'),
(4,'George Everest House','george-everest-house','historic','Historic Site','Historic landmark and former estate of Sir George Everest, Surveyor-General of India, offering 360-degree valley views and gentle ridge walks.','<p>The restored Park Estate was the home and laboratory of Sir George Everest, after whom the world\'s highest peak is named. The open ridge around the house gives 360° views — the Doon valley on one side, the Aglar river valley and snow peaks on the other — and makes for a perfect sunset walk.</p>','10 to 11','~30 mins',30.454000,78.033500,'','','[]',1,1,4,'2026-10-17 14:19:53'),
(5,'Camel\'s Back Road','camel-s-back-road','viewpoint','Scenic Walk','A peaceful, nature-wrapped walking trail named for a rock formation shaped like a camel\'s hump, popular for sunset views.','<p>A 3 km loop between Kulri and Library Bazaar, Camel\'s Back Road is Mussoorie\'s favourite evening stroll — horse rides, the old cemetery, and a famous sunset point looking out over the ranges.</p>','9','~25 mins',30.463000,78.076000,'','','[]',0,1,5,'2026-10-17 14:19:53'),
(6,'Bhatta Falls','bhatta-falls','waterfall','Waterfall','A natural, cascading waterfall surrounded by greenery with a pool area, accessible via a short ropeway ride.','<p>On the Mussoorie–Dehradun road, Bhatta Falls tumbles into a series of natural pools that are ideal for a dip in summer. A short ropeway takes you down to the falls; there are changing rooms and small food stalls at the base.</p>','14.4','~40 mins',30.428500,78.051500,'','','[]',1,1,6,'2026-10-17 14:19:53'),
(7,'Jharipani Falls','jharipani-falls','waterfall','Waterfall','A picturesque and less-crowded waterfall tucked away amidst Shivalik hills, reached via a scenic short trek.','<p>Quieter than its famous cousins, Jharipani Falls is reached by a 1.5 km walk from Jharipani village through oak forest. Best visited after the monsoon when the cascade is at its fullest.</p>','12 to 13','~35 mins',30.410000,78.085000,'','','[]',1,1,7,'2026-10-17 14:19:53'),
(8,'Company Garden','company-garden','garden','Garden','A popular colonial-period botanical garden featuring vibrant flowerbeds, an artificial boating lake, and open lawns.','<p>Laid out in the 1800s by the botanist Dr H. Fackner, the Municipal (Company) Garden is a riot of flowerbeds, a small boating lake, a wax museum and plenty of lawn for a family afternoon.</p>','7 to 8','~22 mins',30.465000,78.046000,'','','[]',0,1,8,'2026-10-17 14:19:53'),
(9,'Mussoorie Lake','mussoorie-lake','lake','Lake','A scenic man-made water reservoir surrounded by hills, offering boating, zip-lining, and water zorbing.','<p>A small, pretty reservoir on the Dehradun road with paddle boats, a zip-line across the water and views of the Doon valley lights after dark.</p>','11 to 12','~30 mins',30.433000,78.079000,'','','[]',0,1,9,'2026-10-17 14:19:53'),
(10,'Robber\'s Cave (Gucchupani)','robber-s-cave-gucchupani','nature','Natural Wonder','A natural river cave formation near Dehradun where a stream flows right through narrow rock-cut corridors — great for a unique outdoor walk.','<p>A 600 m long river cave outside Dehradun where you wade through ankle-deep cold water between towering limestone walls. Carry a change of clothes and sandals that can get wet.</p>','25','~60 mins',30.376000,78.058000,'','','[]',0,1,10,'2026-10-17 14:19:53');
/*!40000 ALTER TABLE `attractions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` varchar(500) DEFAULT '',
  `seo_title` varchar(255) DEFAULT '',
  `seo_desc` varchar(500) DEFAULT '',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES
(1,'Travel Guide','travel-guide','Explore Mussoorie & around','','',1),
(2,'Resort Life','resort-life','Life at The English Country House','','',1);
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `excerpt` varchar(600) DEFAULT '',
  `content` mediumtext DEFAULT NULL,
  `image` varchar(255) DEFAULT '',
  `author` varchar(120) DEFAULT '',
  `tags` varchar(400) DEFAULT '',
  `status` enum('draft','published') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT '',
  `seo_desc` varchar(500) DEFAULT '',
  `seo_keywords` varchar(500) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_cat` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES
(1,'Top 10 Things To Do In Mussoorie This Season','top-10-things-to-do-in-mussoorie',1,'From Camel\'s Back Road walks to Jabarkhet Nature Reserve — the definitive list for your Mussoorie holiday.','<p>Mussoorie, the Queen of Hills, offers something for every kind of traveller. Here are our favourite experiences, minutes away from The English Country House…</p><p>1. Sunrise at our mountain-view deck. 2. A guided walk in Jabarkhet Nature Reserve. 3. Maggi point on Tehri Road. 4. Camel\'s Back Road. 5. Landour bakehouse trail…</p>','','ECH Team','mussoorie,travel,things to do','published','2026-10-17 19:49:53',0,'','','','2026-10-17 19:49:53'),
(2,'Farm-To-Table: Inside Our Polly House Greenhouse','farm-to-table-polly-house',2,'How our on-site organic greenhouse brings the freshest produce straight to your plate.','<p>The Polly House is our very own organic vegetable greenhouse where fresh produce is grown on-site. Every salad, every garnish in our multi-cuisine restaurant starts its journey here…</p>','','ECH Team','food,organic,restaurant','published','2026-10-17 19:49:53',0,'','','','2026-10-17 19:49:53');
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `booking_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `gateway` varchar(30) NOT NULL,
  `txn_id` varchar(190) DEFAULT '',
  `order_ref` varchar(190) DEFAULT '',
  `amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `status` enum('created','pending','success','failed','refunded') DEFAULT 'created',
  `payload` mediumtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_b` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `booking_payments` WRITE;
/*!40000 ALTER TABLE `booking_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `checkin` date NOT NULL,
  `checkout` date NOT NULL,
  `nights` int(11) NOT NULL DEFAULT 1,
  `adults` int(11) DEFAULT 2,
  `children` int(11) DEFAULT 0,
  `guest_name` varchar(160) NOT NULL,
  `email` varchar(190) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `special_request` text DEFAULT NULL,
  `price_breakup` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(30) DEFAULT '',
  `payment_status` enum('unpaid','pending','paid','partial','refund_initiated','refunded','failed') DEFAULT 'unpaid',
  `status` enum('inquiry','pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
  `source` varchar(30) DEFAULT 'online',
  `admin_notes` text DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT '',
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `idx_room_dates` (`room_id`,`checkin`,`checkout`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `canned_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `canned_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL,
  `content` text DEFAULT NULL,
  `keywords` varchar(400) DEFAULT '',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `canned_replies` WRITE;
/*!40000 ALTER TABLE `canned_replies` DISABLE KEYS */;
INSERT INTO `canned_replies` VALUES
(1,'Greeting','Hello {name}! Thank you for contacting The English Country House. This is {agent} — how may I assist you today?','hello,hi,greeting',1),
(2,'Room rates','Our tariff starts at ₹8,500 per night for the Victorian Suites and goes up to ₹10,500 for the Charlotte Tree Houses. May I know your travel dates so I can share the exact price and best offer?','price,rate,tariff,cost',1),
(3,'Availability check','I\'d be happy to check availability for you, {name}. Could you share your check-in date, check-out date and number of guests?','available,availability,dates',1),
(4,'Pet policy','We\'re a pet-friendly resort! Elizabeth Lodge and our two Charlotte Tree Houses welcome pets at no extra charge. The Victorian Suites don\'t allow pets.','pet,dog',1),
(5,'Directions','We\'re located at Village Kaplani, Dhanaulti Road, Mussoorie 248179 — I can WhatsApp you a Google Maps pin. Would that help?','location,reach,address',1),
(6,'Callback offer','I can arrange a quick call from our reservations team. Is this a good time to call you on {phone}?','call,callback,talk',1);
/*!40000 ALTER TABLE `canned_replies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `chatbot_qa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_qa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keywords` varchar(400) NOT NULL,
  `answer` text DEFAULT NULL,
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chatbot_qa` WRITE;
/*!40000 ALTER TABLE `chatbot_qa` DISABLE KEYS */;
INSERT INTO `chatbot_qa` VALUES
(1,'price,cost,tariff,rate,charges','Our rooms start at ₹8,500/night (seasonal rates apply). Would you like me to connect you with our team for the best available offer for your dates?',0,1),
(2,'pet,dog,cat','We are pet friendly! 🐾 Elizabeth Lodge and our Charlotte Tree Houses welcome pets. Victorian Suites do not allow pets.',1,1),
(3,'location,address,where,reach,directions','We are at Village Kaplani, Dhanaulti Road, Mussoorie, Uttarakhand 248179 — about 30 minutes from Mussoorie Mall Road.',2,1),
(4,'breakfast,food,restaurant,dinner,meal','Complimentary breakfast is included! Our multi-cuisine restaurant serves global and local dishes made with organic produce from our own Polly House greenhouse.',3,1),
(5,'book,booking,reserve,availability,available','You can book directly on our website — just tap \"Book Your Stay\" — or I can connect you to our team right now for assistance.',4,1),
(6,'cancel,refund','Bookings cancelled 4+ days before check-in get an automatic full refund. Within 4 days they are non-refundable.',5,1),
(7,'pool,swimming','Yes, we have a beautiful swimming pool, plus an outdoor jacuzzi, sauna and steam bath!',6,1);
/*!40000 ALTER TABLE `chatbot_qa` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_type` enum('letterhead','invoice','mou') NOT NULL,
  `ref_no` varchar(60) DEFAULT '',
  `title` varchar(255) NOT NULL,
  `party_name` varchar(190) DEFAULT '',
  `party_details` text DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `data` mediumtext DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_t` (`doc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(190) NOT NULL,
  `subject` varchar(255) DEFAULT '',
  `body` mediumtext DEFAULT NULL,
  `status` enum('sent','failed','queued') DEFAULT 'queued',
  `error` varchar(500) DEFAULT '',
  `context` varchar(60) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `email_log` WRITE;
/*!40000 ALTER TABLE `email_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `answer` text DEFAULT NULL,
  `category` varchar(120) DEFAULT 'General',
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'What are the check-in and check-out timings?','Check-in is at 1:00 PM and check-out is at 11:00 AM. Early check-in / late check-out is subject to availability.','Booking',0,1),
(2,'Is the resort pet friendly?','Yes! Elizabeth Lodge and both Charlotte Tree Houses are pet friendly. Victorian Suites do not allow pets.','Rooms',1,1),
(3,'Is breakfast included in the tariff?','Yes, a complimentary multi-cuisine breakfast is included with every stay.','Dining',2,1),
(4,'Do you have EV charging?','Yes, we provide an on-site EV charging station for eco-conscious travellers.','Facilities',3,1),
(5,'What is the cancellation policy?','Cancellations made 4 or more days before check-in receive a full refund automatically. Within 4 days of check-in the booking is non-refundable.','Booking',4,1),
(6,'Do you host events and corporate retreats?','Yes, we host private parties, celebrations and corporate events. Contact us for a customised quote.','Events',5,1);
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `food_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `food_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `variant` varchar(120) DEFAULT '',
  `diet` varchar(10) DEFAULT 'veg',
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `food_order_items` WRITE;
/*!40000 ALTER TABLE `food_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `food_order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `food_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `food_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(20) NOT NULL,
  `token` varchar(40) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_no` varchar(120) DEFAULT '',
  `guest_name` varchar(140) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `email` varchar(190) DEFAULT '',
  `notes` varchar(600) DEFAULT '',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `item_count` int(11) DEFAULT 0,
  `status` enum('new','accepted','preparing','ready','delivered','cancelled') DEFAULT 'new',
  `eta_minutes` int(11) DEFAULT 30,
  `source` varchar(20) DEFAULT 'website',
  `wa_status` varchar(255) DEFAULT '',
  `handled_by` int(11) DEFAULT NULL,
  `ip` varchar(64) DEFAULT '',
  `accepted_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `idx_status` (`status`,`created_at`),
  KEY `idx_user` (`user_id`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `food_orders` WRITE;
/*!40000 ALTER TABLE `food_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `food_orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `data` mediumtext DEFAULT NULL,
  `page` varchar(255) DEFAULT '',
  `ip` varchar(64) DEFAULT '',
  `lead_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `fields` mediumtext DEFAULT NULL,
  `submit_label` varchar(80) DEFAULT 'Submit',
  `success_msg` varchar(500) DEFAULT 'Thank you! We will get back to you shortly.',
  `email_to` varchar(190) DEFAULT '',
  `create_lead` tinyint(1) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES
(1,'Contact Form','[{\"type\":\"text\",\"label\":\"First Name\",\"name\":\"first_name\",\"required\":true,\"half\":true},{\"type\":\"text\",\"label\":\"Last Name\",\"name\":\"last_name\",\"required\":true,\"half\":true},{\"type\":\"email\",\"label\":\"Email Address\",\"name\":\"email\",\"required\":true,\"half\":true},{\"type\":\"text\",\"label\":\"Subject\",\"name\":\"subject\",\"required\":true,\"half\":true},{\"type\":\"tel\",\"label\":\"Telephone\",\"name\":\"phone\",\"required\":true,\"half\":true},{\"type\":\"text\",\"label\":\"Website\",\"name\":\"website\",\"required\":false,\"half\":true},{\"type\":\"textarea\",\"label\":\"Comments \\/ Questions\",\"name\":\"message\",\"required\":true,\"half\":false}]','Send Message','Thank you for reaching out! Our team will get back to you shortly.','info@theenglishcountryhouse.com',1,1,'2026-10-17 19:49:53');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `gallery_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  `title` varchar(190) DEFAULT '',
  `category` varchar(120) DEFAULT 'General',
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `source_url` varchar(500) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_src` (`source_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `gallery_images` WRITE;
/*!40000 ALTER TABLE `gallery_images` DISABLE KEYS */;
INSERT INTO `gallery_images` VALUES
(1,'assets/img/restaurant.jpg','Our Multicuisine Restaurant','Restaurant',1,1,'2026-10-17 19:49:52','');
/*!40000 ALTER TABLE `gallery_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ip_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_bans` (
  `ip` varchar(64) NOT NULL,
  `reason` varchar(190) DEFAULT '',
  `banned_at` datetime DEFAULT current_timestamp(),
  `until` datetime NOT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ip_bans` WRITE;
/*!40000 ALTER TABLE `ip_bans` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_bans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `lead_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('remark','call','mail','whatsapp','stage','followup','action','system') DEFAULT 'remark',
  `content` text DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `done` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_l` (`lead_id`),
  KEY `idx_due` (`due_at`,`done`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `lead_activities` WRITE;
/*!40000 ALTER TABLE `lead_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_activities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) DEFAULT '',
  `email` varchar(190) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `source` varchar(40) DEFAULT 'contact',
  `room_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `stage` enum('new','contacted','follow_up','quoted','converted','lost') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `tags` varchar(255) DEFAULT '',
  `score` int(11) DEFAULT 0,
  `meta` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stage` (`stage`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `email` varchar(190) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `menu_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(140) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `subtitle` varchar(255) DEFAULT '',
  `icon` varchar(60) DEFAULT 'fa-utensils',
  `image` varchar(255) DEFAULT '',
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `advance_order` tinyint(1) DEFAULT 0,
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`active`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `menu_categories` WRITE;
/*!40000 ALTER TABLE `menu_categories` DISABLE KEYS */;
INSERT INTO `menu_categories` VALUES
(1,'Breakfast','breakfast','Available from 9:00 am to 11:00 am','fa-mug-saucer','','09:00:00','11:00:00',0,10,1,'2026-10-17 19:49:52'),
(2,'Soups','soups','Warm bowls for the hills','fa-bowl-food','',NULL,NULL,0,20,1,'2026-10-17 19:49:52'),
(3,'Snacks & Starters','snacks-starters','Small plates, big flavours','fa-pepper-hot','',NULL,NULL,0,30,1,'2026-10-17 19:49:52'),
(4,'Main Course — Veg','main-course-veg','Slow-cooked comfort from our kitchen','fa-leaf','',NULL,NULL,0,40,1,'2026-10-17 19:49:52'),
(5,'Main Course — Non-Veg','main-course-non-veg','Hearty classics, tandoor to wok','fa-drumstick-bite','',NULL,NULL,0,50,1,'2026-10-17 19:49:52'),
(6,'Accompaniments','accompaniments','Breads, rice & raita','fa-bread-slice','',NULL,NULL,0,60,1,'2026-10-17 19:49:52'),
(7,'Desserts','desserts','A sweet finish','fa-ice-cream','',NULL,NULL,0,70,1,'2026-10-17 19:49:52'),
(8,'Beverages','beverages','Hot brews, cool sips','fa-mug-hot','assets/img/menu/beverages.jpg',NULL,NULL,0,80,1,'2026-10-17 19:49:52'),
(9,'Flavours of the Himalayas','flavours-of-the-himalayas','Advance order — please order a few hours ahead','fa-mountain-sun','',NULL,NULL,1,90,1,'2026-10-17 19:49:52');
/*!40000 ALTER TABLE `menu_categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` varchar(600) DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diet` enum('veg','nonveg','egg','both') DEFAULT 'veg',
  `variants` varchar(500) DEFAULT '',
  `tags` varchar(160) DEFAULT '',
  `image` varchar(255) DEFAULT '',
  `sort` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `available` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cat` (`category_id`,`active`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES
(1,1,'Eggs to Your Choice','Fried, scrambled, boiled or masala eggs served with toast & butter',350.00,'egg','Fried, Scrambled, Boiled, Masala','','',10,1,1,'2026-10-17 19:49:52'),
(2,1,'Cornflakes & Choco’s','Crunchy cereal served with hot or chilled milk',250.00,'veg','Cornflakes - Hot Milk, Cornflakes - Chilled Milk, Choco’s - Hot Milk, Choco’s - Chilled Milk','','',20,1,1,'2026-10-17 19:49:52'),
(3,1,'Sliced Seasonal Fresh Fruit','Watermelon, apple, banana & papaya — whatever the season brings',320.00,'veg','','','',30,1,1,'2026-10-17 19:49:52'),
(4,1,'English Platter','Chicken sausage, exotic sautéed vegetables, baked beans, roasted potatoes, eggs to order & toast',895.00,'nonveg','','Chef’s Special','',40,1,1,'2026-10-17 19:49:52'),
(5,1,'Pancake','Fluffy pancakes served with Nutella, maple syrup or chocolate',410.00,'egg','Nutella, Maple Syrup, Chocolate','','',50,1,1,'2026-10-17 19:49:52'),
(6,1,'Sautéed Seasonal Vegetables','Fresh seasonal vegetables sautéed with herbs and light seasoning',275.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(7,1,'Mashed Potato','Smooth, creamy mashed potatoes finished with butter',325.00,'veg','Garlic, Plain','','',70,1,1,'2026-10-17 19:49:52'),
(8,1,'Potato Wedges','Golden potato wedges seasoned with herbs and spices',300.00,'veg','','','',80,1,1,'2026-10-17 19:49:52'),
(9,1,'Idli','Steamed rice & lentil cakes with slow-simmered vegetable sambar and traditional coconut chutney',290.00,'veg','','','',90,1,1,'2026-10-17 19:49:52'),
(10,1,'Vada','Crisp urad dal vadas with spice-infused vegetable sambar and handcrafted coconut chutney',290.00,'veg','','','',100,1,1,'2026-10-17 19:49:52'),
(11,1,'Choice of Dosa','Crispy golden dosa served with sambar & coconut chutney',380.00,'veg','Masala, Plain, Cheese, Onion','Bestseller','',110,1,1,'2026-10-17 19:49:52'),
(12,1,'Poori with Aloo Bhaji','Deep-fried Indian bread with spiced potato curry',480.00,'veg','','','',120,1,1,'2026-10-17 19:49:52'),
(13,1,'Paratha','Griddle-cooked North-Indian stuffed bread served with yogurt',275.00,'veg','Aloo, Pyaaz, Gobi, Paneer','Bestseller','',130,1,1,'2026-10-17 19:49:52'),
(14,1,'Poha','Flattened rice tossed with onion, mustard seeds and curry leaves',390.00,'veg','Plain, Vegetable, Paneer','','',140,1,1,'2026-10-17 19:49:52'),
(15,1,'Maggi','Classic instant noodles cooked with aromatic spices and fresh vegetables',190.00,'veg','Plain, Vegetable, Paneer','','',150,1,1,'2026-10-17 19:49:52'),
(16,1,'Besan Chilla','Savoury gram-flour pancakes blended with fresh vegetables and spices',290.00,'veg','','','',160,1,1,'2026-10-17 19:49:52'),
(17,2,'Cream of Tomato','Smooth tomato soup with a rich, creamy texture and mild seasoning',270.00,'veg','','','',10,1,1,'2026-10-17 19:49:52'),
(18,2,'Sweet Corn Soup','Comforting soup with sweet corn, vegetables and delicate seasoning',345.00,'both','Veg, Chicken','','',20,1,1,'2026-10-17 19:49:52'),
(19,2,'Manchow Soup','Spicy Indo-Chinese soup topped with crispy noodles',345.00,'both','Veg, Chicken','','',30,1,1,'2026-10-17 19:49:52'),
(20,2,'Hot & Sour Soup','Tangy, peppery Indo-Chinese soup with vegetables and soy',345.00,'both','Veg, Chicken','','',40,1,1,'2026-10-17 19:49:52'),
(21,2,'Cream of Mushroom','Velvety soup of fresh mushrooms finished with cream',345.00,'both','Veg, Chicken','','',50,1,1,'2026-10-17 19:49:52'),
(22,2,'Clear Soup','Light, clear broth with fresh vegetables and gentle seasoning',345.00,'both','Veg, Chicken','','',60,1,1,'2026-10-17 19:49:52'),
(23,3,'Hara Bhara Kabab','Crispy spinach & green-pea kebabs served with mint chutney',450.00,'veg','','','',10,1,1,'2026-10-17 19:49:52'),
(24,3,'Crispy Corn','Crispy sweet corn tossed with aromatic spices',390.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(25,3,'French Fries','Golden fries with the topping of your choice',390.00,'veg','Plain, Peri Peri, Cheesy','','',30,1,1,'2026-10-17 19:49:52'),
(26,3,'Honey Chilli Potato','Golden fries tossed in a sweet & spicy honey-chilli sauce',300.00,'veg','','Bestseller','',40,1,1,'2026-10-17 19:49:52'),
(27,3,'Pakora','Crispy fritters with Indian spices',390.00,'both','Veg, Chicken, Mix','','',50,1,1,'2026-10-17 19:49:52'),
(28,3,'Corn Cheese Balls','Sweet corn & cheese balls, golden and crisp',460.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(29,3,'Chilli Paneer','Crispy paneer cubes with peppers & onions in a tangy chilli sauce',530.00,'veg','','','',70,1,1,'2026-10-17 19:49:52'),
(30,3,'Chilli Mushroom','Button mushrooms coated in a spicy, flavourful chilli glaze',420.00,'veg','','','',80,1,1,'2026-10-17 19:49:52'),
(31,3,'Chilli Manchurian','Golden Manchurian balls stir-fried with peppers and onions',490.00,'veg','','','',90,1,1,'2026-10-17 19:49:52'),
(32,3,'Chilli Manchurian (Veg / Non-Veg)','Crispy balls tossed in a spicy Indo-Chinese sauce',480.00,'both','Veg, Non-Veg','','',100,1,1,'2026-10-17 19:49:52'),
(33,3,'Spring Roll','Crunchy pastry rolls stuffed with seasoned filling',420.00,'both','Veg, Chicken','','',110,1,1,'2026-10-17 19:49:52'),
(34,3,'Momos','Steamed dumplings filled with a savoury stuffing',450.00,'both','Veg, Non-Veg','Bestseller','',120,1,1,'2026-10-17 19:49:52'),
(35,3,'Cheese Burst Balls','Crispy golden potato bites with a molten cheese centre',460.00,'veg','','','',130,1,1,'2026-10-17 19:49:52'),
(36,3,'Nachos & Salsa','Crispy nachos served with fresh, tangy, mildly spicy salsa',460.00,'veg','','','',140,1,1,'2026-10-17 19:49:52'),
(37,3,'Mushroom Tikka','Marinated mushrooms chargrilled with aromatic spices',480.00,'veg','','','',150,1,1,'2026-10-17 19:49:52'),
(38,3,'Paneer Tikka','Spiced paneer cubes grilled to smoky perfection',590.00,'veg','Plain, Malai, Achari','Chef’s Special','',160,1,1,'2026-10-17 19:49:52'),
(39,3,'Soya Chaap','Soya chaap in your choice of flavour',480.00,'veg','Malai, Achari, Masala','','',170,1,1,'2026-10-17 19:49:52'),
(40,3,'Chicken Popcorn','Bite-sized crispy chicken pieces, seasoned and fried to perfection',640.00,'nonveg','','','',180,1,1,'2026-10-17 19:49:52'),
(41,3,'Tandoori Chicken (Half)','Tender chicken marinated in spiced yogurt and roasted in the tandoor',550.00,'nonveg','','','',190,1,1,'2026-10-17 19:49:52'),
(42,3,'Tandoori Chicken (Full)','A whole portion of tender chicken marinated in spiced yogurt and roasted in the tandoor',950.00,'nonveg','','Chef’s Special','',200,1,1,'2026-10-17 19:49:52'),
(43,3,'Chicken Tikka','Boneless chicken marinated in aromatic spices and grilled in the tandoor',620.00,'nonveg','Plain, Malai, Achari, Afghani','','',210,1,1,'2026-10-17 19:49:52'),
(44,3,'Chicken Wings','Crispy fried wings seasoned with a bold blend of aromatic spices',630.00,'nonveg','','','',220,1,1,'2026-10-17 19:49:52'),
(45,3,'Chilli Chicken','Crispy chicken tossed with bell peppers, onions and a spicy Indo-Chinese sauce',610.00,'nonveg','','','',230,1,1,'2026-10-17 19:49:52'),
(46,3,'Peanut Masala','Crunchy roasted peanuts tossed with onions, tomatoes, fresh herbs and mild spices',160.00,'veg','','','',240,1,1,'2026-10-17 19:49:52'),
(47,3,'Papad','Crispy roasted papad',160.00,'veg','Plain, Masala','','',250,1,1,'2026-10-17 19:49:52'),
(48,3,'Veg Thin Crust Pizza','Crisp thin crust topped with fresh vegetables, tomato sauce and melted cheese',630.00,'veg','','','',260,1,1,'2026-10-17 19:49:52'),
(49,3,'Chicken Thin Crust Pizza','Crisp thin crust topped with tender chicken, tomato sauce and fresh vegetables',630.00,'nonveg','','','',270,1,1,'2026-10-17 19:49:52'),
(50,3,'Sandwich','Grilled toasted bread with the filling of your choice',370.00,'both','Veg, Paneer, Chicken','','',280,1,1,'2026-10-17 19:49:52'),
(51,4,'Dal Makhani','Creamy slow-cooked black lentils simmered with butter and cream',535.00,'veg','','Bestseller','',10,1,1,'2026-10-17 19:49:52'),
(52,4,'Dal Tadka','Yellow lentils tempered with garlic, cumin and aromatic Indian spices',460.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(53,4,'Dal Fry','Yellow lentils cooked with onions, tomatoes and a flavourful tempering',480.00,'veg','','','',30,1,1,'2026-10-17 19:49:52'),
(54,4,'Kadai Paneer','Cottage cheese & bell peppers tossed in a spicy, aromatic kadai masala',640.00,'veg','','','',40,1,1,'2026-10-17 19:49:52'),
(55,4,'Paneer Lababdar','Soft cottage cheese in a rich, creamy tomato & cashew gravy',640.00,'veg','','','',50,1,1,'2026-10-17 19:49:52'),
(56,4,'Matar Paneer','Cottage cheese & green peas in a spiced tomato-onion gravy',690.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(57,4,'Paneer Butter Masala','Soft cottage-cheese cubes simmered in a rich, creamy tomato-butter gravy',655.00,'veg','','Bestseller','',70,1,1,'2026-10-17 19:49:52'),
(58,4,'Shahi Paneer','Tender cottage cheese in a rich, creamy cashew-tomato gravy',680.00,'veg','','','',80,1,1,'2026-10-17 19:49:52'),
(59,4,'Masala Mushroom','Fresh mushrooms & green peas cooked in a flavourful onion-tomato gravy',620.00,'veg','','','',90,1,1,'2026-10-17 19:49:52'),
(60,4,'Tawa Chaap','Soya chaap cooked on a hot griddle with spicy masala, onions and capsicum',600.00,'veg','','','',100,1,1,'2026-10-17 19:49:52'),
(61,4,'Mix Vegetables','Seasonal garden vegetables in a mildly spiced onion-tomato gravy',510.00,'veg','','','',110,1,1,'2026-10-17 19:49:52'),
(62,4,'Aloo Gobi','Potatoes & cauliflower cooked with turmeric, herbs and aromatic spices',460.00,'veg','','','',120,1,1,'2026-10-17 19:49:52'),
(63,4,'Chana Masala','Chickpeas simmered in a flavourful onion-tomato gravy',495.00,'veg','','','',130,1,1,'2026-10-17 19:49:52'),
(64,4,'Grilled Veg Platter','Assorted seasonal vegetables, perfectly grilled and lightly seasoned',650.00,'veg','','','',140,1,1,'2026-10-17 19:49:52'),
(65,4,'Pasta Alfredo','Pasta tossed in a rich, creamy Alfredo sauce',650.00,'veg','','','',150,1,1,'2026-10-17 19:49:52'),
(66,4,'Mac & Cheese','Creamy macaroni baked with rich, melted cheese',580.00,'veg','','','',160,1,1,'2026-10-17 19:49:52'),
(67,4,'Garlic Bread','Crispy toasted bread topped with aromatic garlic butter and herbs',210.00,'veg','','','',170,1,1,'2026-10-17 19:49:52'),
(68,4,'Garlic Bread with Cheese','Toasted bread topped with garlic and melted cheese',325.00,'veg','','','',180,1,1,'2026-10-17 19:49:52'),
(69,4,'Noodles','Stir-fried noodles tossed with garlic, fresh vegetables and chilli sauce',480.00,'veg','Veg, Chilli Garlic','','',190,1,1,'2026-10-17 19:49:52'),
(70,4,'Veg Hakka Noodles','Wok-tossed noodles with fresh vegetables, classic Hakka style',480.00,'veg','','','',200,1,1,'2026-10-17 19:49:52'),
(71,4,'Veg Fried Rice','Fragrant rice stir-fried with fresh vegetables, herbs and light Asian seasoning',420.00,'veg','','','',210,1,1,'2026-10-17 19:49:52'),
(72,4,'Veg Schezwan Fried Rice','Stir-fried rice with fresh vegetables and bold Schezwan sauce',460.00,'veg','','','',220,1,1,'2026-10-17 19:49:52'),
(73,4,'Jeera Aloo','Crispy potatoes tossed with cumin seeds, fresh herbs and aromatic spices',490.00,'veg','','','',230,1,1,'2026-10-17 19:49:52'),
(74,5,'Butter Chicken','Tandoori chicken simmered in a rich, creamy tomato-butter gravy',800.00,'nonveg','','Bestseller','',10,1,1,'2026-10-17 19:49:52'),
(75,5,'Chicken Stroganoff','Tender chicken in a creamy, rich sauce with mushrooms and aromatic herbs',850.00,'nonveg','','','',20,1,1,'2026-10-17 19:49:52'),
(76,5,'Kadai Chicken','Chicken & bell peppers tossed in a spicy, aromatic kadai masala gravy',755.00,'nonveg','','','',30,1,1,'2026-10-17 19:49:52'),
(77,5,'Chicken Curry','Home-style chicken curry in a rich onion-tomato gravy with aromatic spices',720.00,'nonveg','','','',40,1,1,'2026-10-17 19:49:52'),
(78,5,'Chicken Kali Mirch','Tender chicken in a creamy black-pepper gravy with Indian spices',625.00,'nonveg','','','',50,1,1,'2026-10-17 19:49:52'),
(79,5,'Malai Chicken','Succulent chicken cooked in a creamy, mildly spiced malai sauce',770.00,'nonveg','','','',60,1,1,'2026-10-17 19:49:52'),
(80,5,'Egg Curry','Boiled eggs simmered in a rich, spicy Punjabi onion-tomato gravy',490.00,'egg','','','',70,1,1,'2026-10-17 19:49:52'),
(81,5,'Bhuna Chicken','Chicken slow-cooked with onions, tomatoes and aromatic spices for a rich, robust flavour',800.00,'nonveg','','','',80,1,1,'2026-10-17 19:49:52'),
(82,5,'Chicken Noodles','Stir-fried noodles tossed with tender chicken and fresh vegetables',540.00,'nonveg','','','',90,1,1,'2026-10-17 19:49:52'),
(83,5,'Chicken Fried Rice','Fragrant rice stir-fried with tender chicken and fresh vegetables',480.00,'nonveg','','','',100,1,1,'2026-10-17 19:49:52'),
(84,5,'Chicken Schezwan Fried Rice','Stir-fried rice with tender chicken, vegetables and bold Schezwan sauce',520.00,'nonveg','','','',110,1,1,'2026-10-17 19:49:52'),
(85,5,'Chicken Hakka Noodles','Wok-tossed noodles with tender chicken and fresh vegetables',540.00,'nonveg','','','',120,1,1,'2026-10-17 19:49:52'),
(86,5,'Chicken Garlic Noodles','Stir-fried noodles with tender chicken, fresh vegetables and aromatic garlic',640.00,'nonveg','','','',130,1,1,'2026-10-17 19:49:52'),
(87,5,'Chicken Pasta in Pink Sauce','Tender chicken and pasta tossed in a creamy tomato & cream sauce',850.00,'nonveg','','','',140,1,1,'2026-10-17 19:49:52'),
(88,5,'Grilled Chicken','Juicy grilled chicken served with your choice of rich, creamy sauce',850.00,'nonveg','Pepper Sauce, Garlic Butter Sauce','Chef’s Special','',150,1,1,'2026-10-17 19:49:52'),
(89,5,'Chicken Alfredo Pasta','Pasta and tender chicken tossed in a creamy Alfredo sauce with herbs',850.00,'nonveg','','','',160,1,1,'2026-10-17 19:49:52'),
(90,6,'Tandoori Roti','Traditional whole-wheat bread, freshly baked in the tandoor',65.00,'veg','','','',10,1,1,'2026-10-17 19:49:52'),
(91,6,'Plain Roti','Soft, freshly made whole-wheat Indian bread',50.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(92,6,'Butter Roti','Fresh whole-wheat roti brushed with butter',60.00,'veg','','','',30,1,1,'2026-10-17 19:49:52'),
(93,6,'Plain Naan','Soft, fluffy bread freshly baked in the tandoor',85.00,'veg','','','',40,1,1,'2026-10-17 19:49:52'),
(94,6,'Butter Naan','Soft naan brushed with melted butter',100.00,'veg','','','',50,1,1,'2026-10-17 19:49:52'),
(95,6,'Garlic Naan','Soft naan topped with fresh garlic and herbs',125.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(96,6,'Lachha Paratha','Flaky, layered Indian flatbread cooked to perfection',110.00,'veg','','','',70,1,1,'2026-10-17 19:49:52'),
(97,6,'Stuffed Kulcha / Paratha','Flaky bread stuffed with seasoned onion or paneer',130.00,'veg','Onion, Paneer','','',80,1,1,'2026-10-17 19:49:52'),
(98,6,'Steamed Rice','Fluffy, perfectly steamed basmati rice',305.00,'veg','','','',90,1,1,'2026-10-17 19:49:52'),
(99,6,'Jeera Rice','Aromatic basmati rice tempered with cumin and light spices',370.00,'veg','','','',100,1,1,'2026-10-17 19:49:52'),
(100,6,'Peas Pulao','Fragrant basmati rice cooked with green peas and mild spices',460.00,'veg','','','',110,1,1,'2026-10-17 19:49:52'),
(101,6,'Vegetable Pulao','Fragrant rice cooked with fresh vegetables and mild spices',450.00,'veg','','','',120,1,1,'2026-10-17 19:49:52'),
(102,6,'Curd Rice','Creamy rice blended with fresh curd and gentle seasoning',400.00,'veg','','','',130,1,1,'2026-10-17 19:49:52'),
(103,6,'Chicken Biryani','Aromatic basmati rice layered with tender chicken and traditional spices',655.00,'nonveg','','Bestseller','',140,1,1,'2026-10-17 19:49:52'),
(104,6,'Egg Fried Rice','Wok-tossed rice with scrambled egg, vegetables & light seasoning',470.00,'egg','','','',150,1,1,'2026-10-17 19:49:52'),
(105,6,'Plain Curd','Fresh, creamy homemade-style curd',300.00,'veg','','','',160,1,1,'2026-10-17 19:49:52'),
(106,6,'Boondi Raita','Creamy curd mixed with soft boondi and mild spices',380.00,'veg','','','',170,1,1,'2026-10-17 19:49:52'),
(107,6,'Mix Veg Raita','Fresh curd blended with assorted vegetables and light seasoning',430.00,'veg','','','',180,1,1,'2026-10-17 19:49:52'),
(108,6,'Pineapple Raita','Creamy curd combined with juicy pineapple and mild seasoning',390.00,'veg','','','',190,1,1,'2026-10-17 19:49:52'),
(109,7,'Gulab Jamun','Soft, warm dumplings soaked in fragrant sugar syrup',200.00,'veg','','','',10,1,1,'2026-10-17 19:49:52'),
(110,7,'Kheer','Traditional rice pudding slow-cooked with milk, sugar and aromatic spices',350.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(111,7,'Brownie with Ice Cream','Warm, rich chocolate brownie served with creamy ice cream',360.00,'egg','','Bestseller','',30,1,1,'2026-10-17 19:49:52'),
(112,7,'Ice Cream with Hot Chocolate Sauce & Nuts','Creamy ice cream topped with warm chocolate sauce and crunchy nuts',430.00,'veg','','','',40,1,1,'2026-10-17 19:49:52'),
(113,7,'Chocolate Fizz Ice Cream','Creamy chocolate ice cream with a fizzy, refreshing twist',190.00,'veg','','','',50,1,1,'2026-10-17 19:49:52'),
(114,7,'Strawberry Fizz Ice Cream','Creamy strawberry ice cream with a refreshing fizzy twist',190.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(115,7,'Vanilla Ice Cream','Smooth, classic vanilla ice cream',180.00,'veg','','','',70,1,1,'2026-10-17 19:49:52'),
(116,7,'Cake','Soft, moist cake freshly prepared — a delightful treat',850.00,'egg','','','',80,1,1,'2026-10-17 19:49:52'),
(117,8,'Tea','',180.00,'veg','','','',10,1,1,'2026-10-17 19:49:52'),
(118,8,'Coffee','',180.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(119,8,'Milk','',180.00,'veg','','','',30,1,1,'2026-10-17 19:49:52'),
(120,8,'Hot Chocolate','',280.00,'veg','','','',40,1,1,'2026-10-17 19:49:52'),
(121,8,'Cappuccino','',280.00,'veg','','','',50,1,1,'2026-10-17 19:49:52'),
(122,8,'Latte','',280.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(123,8,'Americano','',280.00,'veg','','','',70,1,1,'2026-10-17 19:49:52'),
(124,8,'Espresso','',280.00,'veg','','','',80,1,1,'2026-10-17 19:49:52'),
(125,8,'Cold Coffee','',280.00,'veg','','','',90,1,1,'2026-10-17 19:49:52'),
(126,8,'Chocolate Milkshake','',280.00,'veg','','','',100,1,1,'2026-10-17 19:49:52'),
(127,8,'Mocktail','',280.00,'veg','','','',110,1,1,'2026-10-17 19:49:52'),
(128,8,'Soft Drink','',140.00,'veg','','','',120,1,1,'2026-10-17 19:49:52'),
(129,8,'Schweppes','',140.00,'veg','','','',130,1,1,'2026-10-17 19:49:52'),
(130,8,'Don Chico','',140.00,'veg','','','',140,1,1,'2026-10-17 19:49:52'),
(131,8,'ABC Juice','Apple, beetroot & carrot',140.00,'veg','','','',150,1,1,'2026-10-17 19:49:52'),
(132,8,'Watermelon Juice','',140.00,'veg','','','',160,1,1,'2026-10-17 19:49:52'),
(133,8,'Apple Juice','',140.00,'veg','','','',170,1,1,'2026-10-17 19:49:52'),
(134,8,'Pineapple Juice','',140.00,'veg','','','',180,1,1,'2026-10-17 19:49:52'),
(135,8,'Mineral Water','',60.00,'veg','','','',190,1,1,'2026-10-17 19:49:52'),
(136,9,'Pahadi Thali','A wholesome Garhwali platter of seasonal hill specialities',895.00,'veg','','Chef’s Special','',10,1,1,'2026-10-17 19:49:52'),
(137,9,'Kandali Ka Saag','Stinging-nettle greens — a treasured Garhwali delicacy',200.00,'veg','','','',20,1,1,'2026-10-17 19:49:52'),
(138,9,'Jhangora','Barnyard millet — light, nutty and wholesome',200.00,'veg','','','',30,1,1,'2026-10-17 19:49:52'),
(139,9,'Mandua Ki Roti','Finger-millet flatbread, the traditional roti of the hills',200.00,'veg','','','',40,1,1,'2026-10-17 19:49:52'),
(140,9,'Pahadi Rajma Rasila','Slow-cooked hill rajma in a rich, home-style gravy',560.00,'veg','','','',50,1,1,'2026-10-17 19:49:52'),
(141,9,'Pahadi Aloo Ke Gutke','Hill potatoes tossed with local spices and jakhiya tempering',895.00,'veg','','','',60,1,1,'2026-10-17 19:49:52'),
(142,9,'Tamatar Ka Jhol','Light, tangy Garhwali tomato curry',200.00,'veg','','','',70,1,1,'2026-10-17 19:49:52');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ota_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ota_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `hotel_id` varchar(190) DEFAULT '',
  `username` varchar(190) DEFAULT '',
  `password` varchar(255) DEFAULT '',
  `link` varchar(500) DEFAULT '',
  `logo` varchar(255) DEFAULT '',
  `notes` text DEFAULT NULL,
  `sync_mode` enum('manual','ical') DEFAULT 'ical',
  `active` tinyint(1) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `last_sync` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ota_channels` WRITE;
/*!40000 ALTER TABLE `ota_channels` DISABLE KEYS */;
INSERT INTO `ota_channels` VALUES
(1,'Expedia','115190547','info@theenglishcountryhouse.com','','https://www.expedia.co.in','','','ical',1,0,NULL,'2026-10-17 19:49:53'),
(2,'Airbnb','689425165','','','https://www.airbnb.co.in','','','ical',1,1,NULL,'2026-10-17 19:49:53'),
(3,'Goibibo / MakeMyTrip','1000641708','info@theenglishcountryhouse.com','','https://in.goibibo.com','','','ical',1,2,NULL,'2026-10-17 19:49:53'),
(4,'Desiya','00212100','info@theenglishcountryhouse.com','','https://www.desiya.com','','','ical',1,3,NULL,'2026-10-17 19:49:53'),
(5,'EaseMyTrip','SHL-2505121857524951','info@theenglishcountryhouse.com','','https://www.easemytrip.com','','','ical',1,4,NULL,'2026-10-17 19:49:53'),
(6,'Agoda','71119791','info@theenglishcountryhouse.com','','https://www.agoda.com','','','ical',1,5,NULL,'2026-10-17 19:49:53'),
(7,'Cleartrip','392209','info@theenglishcountryhouse.com','','https://www.cleartrip.com','','','ical',1,6,NULL,'2026-10-17 19:49:53'),
(8,'CultureTravel','698eec646ca67d6f2e24d91f','info@theenglishcountryhouse.com','','https://culturetravel.in','','','ical',1,7,NULL,'2026-10-17 19:49:53'),
(9,'Booking.com','','','','https://www.booking.com','','','ical',1,8,NULL,'2026-10-17 19:49:53');
/*!40000 ALTER TABLE `ota_channels` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ota_rate_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ota_rate_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `ota_rate_plan_id` varchar(190) DEFAULT '',
  `rate_plan_name` varchar(190) DEFAULT '',
  `price_mode` enum('same','markup_percent','markup_amount','discount_percent') DEFAULT 'same',
  `value` decimal(10,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ch` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ota_rate_map` WRITE;
/*!40000 ALTER TABLE `ota_rate_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `ota_rate_map` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ota_room_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ota_room_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `ota_room_id` varchar(190) DEFAULT '',
  `ota_room_name` varchar(190) DEFAULT '',
  `ical_import_url` varchar(600) DEFAULT '',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ch` (`channel_id`),
  KEY `idx_rm` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ota_room_map` WRITE;
/*!40000 ALTER TABLE `ota_room_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `ota_room_map` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `page_type` enum('system','custom','policy','location') DEFAULT 'custom',
  `banner_image` varchar(255) DEFAULT '',
  `content` mediumtext DEFAULT NULL,
  `sections` mediumtext DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT '',
  `seo_desc` varchar(500) DEFAULT '',
  `seo_keywords` varchar(500) DEFAULT '',
  `schema_json` mediumtext DEFAULT NULL,
  `show_in_menu` tinyint(1) DEFAULT 0,
  `menu_order` int(11) DEFAULT 50,
  `active` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `banner_title` varchar(190) DEFAULT '',
  `banner_subtitle` varchar(255) DEFAULT '',
  `show_banner` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'Home','home','system','','','[{\"type\":\"hero\",\"visible\":1,\"title\":\"Discover Tranquility At The English Country House\",\"subtitle\":\"\",\"content\":\"A boutique luxury retreat in Mussoorie featuring five uniquely themed rooms with private balconies and elegant bathtubs — pool, jacuzzi, sauna, steam bath and a scenic mountain-view deck.\",\"image\":\"\",\"btn1_label\":\"Book Your Stay\",\"btn1_url\":\"booking.php\",\"btn2_label\":\"\",\"btn2_url\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"height\":\"full\",\"align\":\"left\",\"title_size\":\"md\",\"text_size\":\"md\",\"text_width\":\"medium\"}},{\"type\":\"booking_bar\",\"visible\":0,\"title\":\"\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"rooms\",\"visible\":1,\"title\":\"The Grandeur Of Our Rooms\",\"subtitle\":\"Discover Our Rooms\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"columns\":\"4\",\"show_explore\":0,\"slide_secs\":4}},{\"type\":\"amenities_gallery\",\"visible\":1,\"title\":\"Enjoy Our Amenities\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"#f6f3ee\",\"tx\":\"\"},{\"type\":\"facilities\",\"visible\":0,\"title\":\"Our Hotel Facilities\",\"subtitle\":\"Our Facilities\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"reviews\",\"visible\":1,\"title\":\"What Our Guests Say\",\"subtitle\":\"Guest Love\",\"content\":\"\",\"image\":\"\",\"bg\":\"#f6f3ee\",\"tx\":\"\"},{\"type\":\"blog\",\"visible\":1,\"title\":\"Latest From Our Blog\",\"subtitle\":\"From The Journal\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Welcome To The English Country House — Book Your Stay Today.\",\"subtitle\":\"Book Now!\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Book Your Stay\",\"btn1_url\":\"booking.php\",\"bg\":\"\",\"tx\":\"\"}]','The English Country House Resort & Spa | Mussoorie','','','',0,1,1,'2026-10-17 19:49:53','Home','',1),
(2,'About Us','about','system','','<h2 style=\"text-align:center\">Let You Know About Us</h2><p><strong>The English Country House – A Luxurious Escape in the Heart of Mussoorie</strong></p>\n<p>Perched in the tranquil hills of Mussoorie, The English Country House is a boutique luxury resort that blends old-world charm with modern comfort. With just five uniquely themed bedrooms—each featuring a private balcony, elegant bathroom with a bathtub, and curated interiors—this retreat offers intimacy, privacy, and personalized indulgence.</p>\n<p>Guests can relax and rejuvenate with top-tier amenities, including a gym, swimming pool, outdoor jacuzzi, sauna, and steam bath. The lush gardens, hammock corners, daybeds, and mountain-view deck provide plenty of spaces to unwind in nature\'s embrace.</p>\n<p>Adventure and activity await with cycling trails, a basketball court, and scenic outdoor areas. EV charging is also available for eco-conscious travelers.</p>\n<p>One of our proud highlights is the Polly House—our very own organic vegetable greenhouse, where fresh produce is grown on-site to bring farm-to-table freshness straight to your plate. These ingredients are featured in our multi-cuisine restaurant, which serves a delicious variety of global and local dishes.</p>\n<p>Whether you\'re seeking a peaceful getaway, a wellness break, or a taste of hillside luxury, The English Country House invites you to experience Mussoorie in its most refined and refreshing form.</p>','[{\"type\":\"image_text\",\"visible\":1,\"title\":\"Elevate Your Stay With Us Today.\",\"subtitle\":\"\",\"image\":\"\",\"content\":\"<p>Immerse yourself in unmatched comfort and luxury with our world-class hospitality. Experience personalized services and premium amenities for a truly memorable stay.<\\/p><div class=\\\"icon-feature\\\"><i class=\\\"fa-solid fa-water-ladder\\\"><\\/i><div><h4>Classy Facilities<\\/h4><p>Experience world-class amenities designed for ultimate comfort and elegance.<\\/p><\\/div><\\/div><div class=\\\"icon-feature\\\"><i class=\\\"fa-solid fa-bed\\\"><\\/i><div><h4>Luxury Lifestyle<\\/h4><p>Embrace a refined and opulent way of living.<\\/p><\\/div><\\/div>\",\"bg\":\"#f6f3ee\",\"tx\":\"\"},{\"type\":\"cards\",\"visible\":1,\"title\":\"The English Country House Is An Attractive Hotel Since 2024\",\"subtitle\":\"Our Story\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"items\":[{\"icon\":\"fa-regular fa-circle-dot\",\"title\":\"Our Vision\",\"text\":\"To redefine luxury hospitality with elegance and personalized experiences.\"},{\"icon\":\"fa-solid fa-bullseye\",\"title\":\"Our Mission\",\"text\":\"To provide world-class comfort, exceptional service, and unforgettable stays.\"},{\"icon\":\"fa-regular fa-building\",\"title\":\"Our Value\",\"text\":\"Commitment to excellence, warm hospitality, and guest satisfaction at every step.\"}]},{\"type\":\"cards\",\"visible\":1,\"title\":\"Why Choose Us?\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"#f3e9d7\",\"tx\":\"\",\"items\":[{\"icon\":\"fa-solid fa-square-parking\",\"title\":\"Secure & Convenient Parking\",\"text\":\"Benefit from hassle-free, secure parking available on-site.\"},{\"icon\":\"fa-solid fa-mug-hot\",\"title\":\"Complimentary Breakfast\",\"text\":\"Start your day right with a delicious, free breakfast included in your stay.\"},{\"icon\":\"fa-regular fa-moon\",\"title\":\"Prime Location\",\"text\":\"Perfectly located close to major attractions — ideal for leisure and business travellers.\"},{\"icon\":\"fa-regular fa-star\",\"title\":\"Comfort & Convenience\",\"text\":\"Well-appointed rooms with everything you need for a comfortable stay.\"}]},{\"type\":\"cta\",\"visible\":1,\"title\":\"Ready For A Hillside Escape?\",\"subtitle\":\"Book Direct — Best Rates\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Book Your Stay\",\"btn1_url\":\"booking.php\",\"btn2_label\":\"Explore Rooms\",\"btn2_url\":\"rooms.php\",\"bg\":\"\",\"tx\":\"\"}]','About Us | The English Country House Mussoorie','','','',1,2,1,'2026-10-17 19:49:53','About Us','Our story, our hills, our hospitality',1),
(3,'Terms & Conditions','terms-and-conditions','policy','','<h3>Terms &amp; Conditions</h3><p>Check-in time is 1:00 PM and check-out is 11:00 AM. Valid government photo ID is required for all guests at check-in. The resort reserves the right of admission. Outside food and alcohol are not permitted in common areas. Pets are welcome only in designated pet-friendly rooms (Elizabeth Lodge and Charlotte Tree Houses). Damage to resort property will be charged at actuals. All disputes are subject to Dehradun jurisdiction.</p>','[]','Terms & Conditions','','','',0,90,1,'2026-10-17 19:49:53','Terms & Conditions','',1),
(4,'Privacy Policy','privacy-policy','policy','','<h3>Privacy Policy</h3><p>We collect only the information needed to process your booking and improve your stay — your name, contact details and stay preferences. We never sell your data. Payment details are processed securely by our payment gateway partners and are never stored on our servers. You may request deletion of your data by writing to us at info@theenglishcountryhouse.com.</p>','[]','Privacy Policy','','','',0,91,1,'2026-10-17 19:49:53','Privacy Policy','',1),
(5,'Refund & Cancellation Policy','refund-policy','policy','','<h3>Refund &amp; Cancellation Policy</h3><p>Cancellations made <strong>{REFUND_DAYS} days or more before check-in</strong> are eligible for a {REFUND_PERCENT}% refund, processed automatically to the original payment method within 5–7 working days. Cancellations made within {REFUND_DAYS} days of check-in are non-refundable. In case of no-show the full booking amount is forfeited. You will be notified of every step on your registered email.</p>','[]','Refund & Cancellation Policy','','','',0,92,1,'2026-10-17 19:49:53','Refund & Cancellation Policy','',1),
(6,'Hotel Near Dhanaulti','hotel-near-dhanaulti','location','','<p>Looking for a luxury stay near <strong>Dhanaulti</strong>? The English Country House is just 8 km away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Dhanaulti love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>','[{\"type\":\"rooms\",\"visible\":1,\"title\":\"Rooms Available For Your Stay\",\"subtitle\":\"Choose Your Room\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Book Your Stay Near Dhanaulti\",\"subtitle\":\"Best Rate — Book Direct\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Check Availability\",\"btn1_url\":\"booking.php\",\"bg\":\"\",\"tx\":\"\"}]','Hotel Near Dhanaulti | The English Country House','Book a boutique luxury stay near Dhanaulti — pool, jacuzzi, themed rooms and farm-to-table dining at The English Country House, Mussoorie.','dhanaulti hotel, stay near dhanaulti','',0,60,1,'2026-10-17 19:49:53','Hotel Near Dhanaulti','',1),
(7,'Resort Near Mussoorie Mall Road','resort-near-mussoorie-mall-road','location','','<p>Looking for a luxury stay near <strong>Mussoorie Mall Road</strong>? The English Country House is just 30 min drive away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Mussoorie Mall Road love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>','[{\"type\":\"rooms\",\"visible\":1,\"title\":\"Rooms Available For Your Stay\",\"subtitle\":\"Choose Your Room\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Book Your Stay Near Mussoorie Mall Road\",\"subtitle\":\"Best Rate — Book Direct\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Check Availability\",\"btn1_url\":\"booking.php\",\"bg\":\"\",\"tx\":\"\"}]','Resort Near Mussoorie Mall Road | The English Country House','Book a boutique luxury stay near Mussoorie Mall Road — pool, jacuzzi, themed rooms and farm-to-table dining at The English Country House, Mussoorie.','mussoorie mall road hotel, stay near mussoorie mall road','',0,60,1,'2026-10-17 19:49:53','Resort Near Mussoorie Mall Road','',1),
(8,'Stay Near Jabarkhet Nature Reserve','stay-near-jabarkhet-nature-reserve','location','','<p>Looking for a luxury stay near <strong>Jabarkhet Nature Reserve</strong>? The English Country House is just 10 min drive away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p><p>Guests visiting Jabarkhet Nature Reserve love our private balconies with valley views, complimentary breakfast and pet-friendly tree houses. Book direct for the best rates.</p>','[{\"type\":\"rooms\",\"visible\":1,\"title\":\"Rooms Available For Your Stay\",\"subtitle\":\"Choose Your Room\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Book Your Stay Near Jabarkhet Nature Reserve\",\"subtitle\":\"Best Rate — Book Direct\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Check Availability\",\"btn1_url\":\"booking.php\",\"bg\":\"\",\"tx\":\"\"}]','Stay Near Jabarkhet Nature Reserve | The English Country House','Book a boutique luxury stay near Jabarkhet Nature Reserve — pool, jacuzzi, themed rooms and farm-to-table dining at The English Country House, Mussoorie.','jabarkhet nature reserve hotel, stay near jabarkhet nature reserve','',0,60,1,'2026-10-17 19:49:53','Stay Near Jabarkhet Nature Reserve','',1),
(9,'Our Rooms','rooms','system','','','[{\"type\":\"rooms\",\"visible\":1,\"title\":\"A Gander At Our Rooms\",\"subtitle\":\"Discover Our Room\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"show_book\":1,\"show_details\":1}},{\"type\":\"cta\",\"visible\":1,\"title\":\"Not Sure Which Room Suits You?\",\"subtitle\":\"We Are Happy To Help\",\"content\":\"<p>Call us or send an enquiry — we will recommend the perfect room for your dates and group.<\\/p>\",\"image\":\"\",\"btn1_label\":\"Check Availability\",\"btn1_url\":\"booking.php\",\"btn2_label\":\"Contact Us\",\"btn2_url\":\"contact.php\",\"bg\":\"\",\"tx\":\"\"}]','Our Rooms | The English Country House','Explore five uniquely themed luxury rooms — Victorian Suites, Elizabeth Lodge and Charlotte Tree Houses — each with a private balcony and elegant bathtub.','','',0,3,1,'2026-10-17 19:49:53','Our Rooms','Five uniquely themed retreats',1),
(10,'Amenities','amenities','system','','','[{\"type\":\"amenities\",\"visible\":1,\"title\":\"Enjoy Our Amenities\",\"subtitle\":\"Enjoy Every Moment\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Experience It All — Book Your Stay\",\"subtitle\":\"Relax · Rejuvenate · Repeat\",\"content\":\"\",\"image\":\"\",\"btn1_label\":\"Book Now\",\"btn1_url\":\"booking.php\",\"bg\":\"\",\"tx\":\"\"}]','Amenities & Facilities | The English Country House','Pool, outdoor jacuzzi, sauna, steam bath, gym, yoga house, basketball court, snooker, library, EV charging and more at The English Country House, Mussoorie.','','',0,4,1,'2026-10-17 19:49:53','Our Amenities','Everything for a perfect stay',1),
(11,'Contact Us','contact','system','','','[{\"type\":\"text\",\"visible\":1,\"title\":\"\",\"subtitle\":\"Contact Us\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"content\":\"<p style=\\\"text-align:center\\\">At The English Country House, we\'re here to ensure your stay is nothing short of unforgettable. Whether you have questions, need help with reservations, or want to learn more about our exceptional holiday home, we\'re just a message away.<\\/p>\"},{\"type\":\"contact_info\",\"visible\":1,\"title\":\"Get In Touch With Us For Quick Support.\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"},{\"type\":\"contact_form\",\"visible\":1,\"title\":\"Your Details\",\"subtitle\":\"\",\"content\":\"<p>Let us know how to get back to you.<\\/p>\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"show_map\":1}},{\"type\":\"attractions\",\"visible\":1,\"title\":\"More To Explore\",\"subtitle\":\"Nearby Experiences\",\"image\":\"\",\"bg\":\"#f6f3ee\",\"tx\":\"\",\"content\":\"<p>From iconic viewpoints to cascading waterfalls, discover the best of Mussoorie — all within easy reach of the house.<\\/p>\",\"btn1_label\":\"Explore Mussoorie\",\"btn1_url\":\"explore.php\",\"opts\":{\"layout\":\"strip\",\"category\":\"featured\",\"limit\":4}}]','Contact Us | The English Country House','Reach The English Country House, Village Kaplani Dhanaulti Road, Mussoorie. Call 9456765555 or write to info@theenglishcountryhouse.com.','','',0,8,1,'2026-10-17 19:49:53','Contact Us','We are just a message away',1),
(12,'Explore Mussoorie','explore','system','','<p style=\"text-align:center;font-size:18px;max-width:760px;margin:0 auto\">Breathtaking landscapes, timeless colonial charm and unforgettable experiences await you in the Queen of the Hills — every spot below is within an easy drive of the house, and our concierge will happily plan the day for you.</p>','[{\"type\":\"attractions_map\",\"visible\":1,\"title\":\"Attractions Map\",\"subtitle\":\"Explore The Hidden Gems\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"content\":\"<p>From panoramic viewpoints to lush forests and cascading waterfalls, discover the best of Mussoorie with our curated guide to must-visit attractions — all within easy reach of the house.<\\/p>\"},{\"type\":\"attractions\",\"visible\":1,\"title\":\"Must-Visit Attractions\",\"subtitle\":\"Viewpoints · Heritage · Nature\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"layout\":\"featured\",\"category\":\"no_waterfall\",\"limit\":8}},{\"type\":\"attractions\",\"visible\":1,\"title\":\"Waterfalls\",\"subtitle\":\"Natural Wonders\",\"image\":\"\",\"bg\":\"#f6f3ee\",\"tx\":\"\",\"opts\":{\"layout\":\"duo\",\"category\":\"waterfall\",\"limit\":4},\"content\":\"<p>Feel the purity of nature at Bhatta Falls and Jharipani Falls, where cascading waters and serene surroundings create the perfect escape.<\\/p>\"},{\"type\":\"cta\",\"visible\":1,\"title\":\"Let Our Concierge Guide You\",\"subtitle\":\"Need A Personal Recommendation?\",\"content\":\"<p>Custom itineraries · local insights · exclusive experiences — plan your perfect Mussoorie days with our team.<\\/p>\",\"image\":\"\",\"btn1_label\":\"Call Concierge\",\"btn1_url\":\"tel:9456765555\",\"btn2_label\":\"WhatsApp Us\",\"btn2_url\":\"https:\\/\\/wa.me\\/919456765555\",\"bg\":\"\",\"tx\":\"\"}]','Explore Mussoorie — Attractions, Waterfalls & Viewpoints | The English Country House','Lal Tibba, Cloud End, George Everest House, Benog Sanctuary, Bhatta & Jharipani Falls, Company Garden, Mussoorie Lake and Robber\'s Cave — distances and travel times from The English Country House, Dhanaulti Road, Mussoorie.','','',0,5,1,'2026-10-17 19:49:53','Explore Mussoorie','Viewpoints · Waterfalls · Colonial Heritage',1),
(13,'FAQ\'s','faqs','system','','','[{\"type\":\"faq_full\",\"visible\":1,\"title\":\"Frequently Asked Questions\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"btn1_label\":\"Contact Us\",\"btn1_url\":\"contact.php\"}]','FAQ\'s | The English Country House','Frequently asked questions about staying at The English Country House — check-in, pets, dining, refunds and more.','','',0,6,1,'2026-10-17 19:49:53','FAQ\'s','Answers to common questions',1),
(14,'Blog','blog','system','','','[{\"type\":\"blog_list\",\"visible\":1,\"title\":\"\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\",\"opts\":{\"per_page\":9}}]','Blog | The English Country House','Travel guides, resort stories and Mussoorie tips from The English Country House.','','',0,5,1,'2026-10-17 19:49:53','Our Blog','Stories from the hills',1),
(15,'Gallery','gallery','system','','','[{\"type\":\"gallery_full\",\"visible\":1,\"title\":\"\",\"subtitle\":\"\",\"content\":\"\",\"image\":\"\",\"bg\":\"\",\"tx\":\"\"}]','Gallery | The English Country House','A photo tour of The English Country House — rooms, dining, amenities and the Mussoorie views.','','',0,7,1,'2026-10-17 19:49:53','Gallery','A photo tour of the house',1),
(16,'Book Your Stay','booking','system','','','[]','Book Your Stay | The English Country House','Book direct at The English Country House, Mussoorie — live availability, seasonal rates and instant confirmation.','','',0,9,1,'2026-10-17 19:49:53','Book Your Stay','Best rates when you book direct',1);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `popups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `popups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(190) NOT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT '',
  `form_id` int(11) DEFAULT NULL,
  `delay_seconds` int(11) DEFAULT 5,
  `pageview_count` int(11) DEFAULT 1,
  `duration_seconds` int(11) DEFAULT 0,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `pages` varchar(500) DEFAULT '',
  `show_once_hours` int(11) DEFAULT 24,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `popups` WRITE;
/*!40000 ALTER TABLE `popups` DISABLE KEYS */;
INSERT INTO `popups` VALUES
(1,'Monsoon Special — 15% Off!','<p>Book 2 nights or more this monsoon and get <strong>15% off</strong> + complimentary bonfire evening. Limited period offer!</p>','',NULL,6,1,0,NULL,NULL,'',24,1,'2026-10-17 19:49:53');
/*!40000 ALTER TABLE `popups` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT 0,
  `name` varchar(160) NOT NULL,
  `rule_type` enum('season','occasion','special','weekend') DEFAULT 'season',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `days_of_week` varchar(30) DEFAULT '',
  `price_mode` enum('set','percent_inc','percent_dec','amount_inc','amount_dec') DEFAULT 'set',
  `value` decimal(10,2) DEFAULT 0.00,
  `extra_adult_price` decimal(10,2) DEFAULT NULL,
  `child_price` decimal(10,2) DEFAULT NULL,
  `min_nights` int(11) DEFAULT 1,
  `priority` int(11) DEFAULT 10,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pricing_rules` WRITE;
/*!40000 ALTER TABLE `pricing_rules` DISABLE KEYS */;
INSERT INTO `pricing_rules` VALUES
(1,0,'Weekend Rate','weekend',NULL,NULL,'5,6','percent_inc',20.00,NULL,NULL,1,5,1,'2026-10-17 19:49:53'),
(2,0,'Peak Summer Season','season','2026-05-01','2026-06-30','','percent_inc',25.00,NULL,NULL,1,10,1,'2026-10-17 19:49:53'),
(3,0,'Christmas & New Year','occasion','2026-12-24','2027-01-02','','percent_inc',40.00,NULL,NULL,2,20,1,'2026-10-17 19:49:53'),
(4,0,'Monsoon Special Offer','special','2026-07-15','2026-08-31','','percent_dec',15.00,NULL,NULL,1,15,1,'2026-10-17 19:49:53');
/*!40000 ALTER TABLE `pricing_rules` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` varchar(500) DEFAULT '',
  `status` enum('pending','processing','completed','rejected','manual') DEFAULT 'pending',
  `gateway` varchar(30) DEFAULT '',
  `gateway_refund_id` varchar(190) DEFAULT '',
  `initiated_by` varchar(30) DEFAULT 'user',
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` enum('google','manual') DEFAULT 'manual',
  `author` varchar(160) DEFAULT '',
  `avatar` varchar(400) DEFAULT '',
  `rating` decimal(2,1) DEFAULT 5.0,
  `content` text DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `external_id` varchar(190) DEFAULT '',
  `active` tinyint(1) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,'manual','Ananya Sharma','',5.0,'A slice of England in the hills! The Victorian Suite was stunning, the bathtub with a view was unforgettable and the staff went above and beyond.','2026-10-17','',1,0,'2026-10-17 19:49:53'),
(2,'manual','Rohit Malhotra','',5.0,'Best boutique property in Mussoorie, hands down. Loved the farm-to-table food from their own greenhouse. The jacuzzi under the stars is a must.','2026-10-17','',1,1,'2026-10-17 19:49:53'),
(3,'manual','Priya & Karan','',4.5,'Celebrated our anniversary at the Charlotte Tree House — private, romantic and those valley views! They even arranged a candle-light dinner on the deck.','2026-10-17','',1,2,'2026-10-17 19:49:53');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `room_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `source` varchar(60) DEFAULT 'manual',
  `ref` varchar(190) DEFAULT '',
  `note` varchar(255) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_r` (`room_id`,`date_from`,`date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `room_blocks` WRITE;
/*!40000 ALTER TABLE `room_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_blocks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `short_desc` varchar(500) DEFAULT '',
  `description` mediumtext DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `weekend_price` decimal(10,2) DEFAULT 0.00,
  `extra_adult_price` decimal(10,2) DEFAULT 0.00,
  `child_price` decimal(10,2) DEFAULT 0.00,
  `base_occupancy` int(11) DEFAULT 2,
  `max_adults` int(11) DEFAULT 3,
  `max_children` int(11) DEFAULT 2,
  `total_units` int(11) DEFAULT 1,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `room_size` varchar(60) DEFAULT '',
  `bed_type` varchar(80) DEFAULT '',
  `view_type` varchar(80) DEFAULT '',
  `images` text DEFAULT NULL,
  `amenity_ids` text DEFAULT NULL,
  `ical_token` varchar(64) DEFAULT '',
  `featured` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT '',
  `seo_desc` varchar(500) DEFAULT '',
  `seo_keywords` varchar(500) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  `cover_image` varchar(255) DEFAULT '',
  `video_url` varchar(500) DEFAULT '',
  `video_file` varchar(255) DEFAULT '',
  `highlights` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES
(1,'Victorian Suite I','victorian-suite-i','A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.','<p>A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',8500.00,10200.00,1500.00,800.00,2,3,2,1,0,'320 sq.ft','King Four-Poster','Valley View','[]','[1,2,3,4,11,13,14]','ea8b990917bf9c19fd98fb4e8a76406e',1,1,0,'Victorian Suite I | The English Country House Mussoorie','A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.','','2026-10-17 19:49:53','','','',NULL),
(2,'Victorian Suite II','victorian-suite-ii','Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.','<p>Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',8500.00,10200.00,1500.00,800.00,2,3,2,1,0,'320 sq.ft','King Four-Poster','Valley View','[]','[1,2,3,4,11,13,14]','154d76866c598e2b84a26cc02f085081',1,1,1,'Victorian Suite II | The English Country House Mussoorie','Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.','','2026-10-17 19:49:53','','','',NULL),
(3,'Elizabeth Lodge','elizabeth-lodge','A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.','<p>A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',9500.00,11400.00,1500.00,800.00,2,3,2,1,1,'320 sq.ft','King','Mountain View','[]','[1,2,3,4,11,13,14]','7149c71ceb6708eb213d3a40f635c9a8',1,1,2,'Elizabeth Lodge | The English Country House Mussoorie','A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.','','2026-10-17 19:49:53','','','',NULL),
(4,'Charlotte Tree House I','charlotte-tree-house-i','A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.','<p>A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',10500.00,12600.00,1500.00,800.00,2,3,2,1,1,'320 sq.ft','Queen','Valley View','[]','[1,2,3,4,11,13,14]','c3058fbc5f22d68a8e06f81c61b430b3',1,1,3,'Charlotte Tree House I | The English Country House Mussoorie','A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.','','2026-10-17 19:49:53','','','',NULL),
(5,'Charlotte Tree House II','charlotte-tree-house-ii','Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.','<p>Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.</p><p>Each of our five uniquely themed bedrooms features a private balcony, elegant bathroom with a bathtub, and curated interiors — this retreat offers intimacy, privacy, and personalized indulgence.</p>',10500.00,12600.00,1500.00,800.00,2,3,2,1,1,'320 sq.ft','Queen','Garden & Valley View','[]','[1,2,3,4,11,13,14]','cfe9437a637f7a3020e9c72c3beac938',1,1,4,'Charlotte Tree House II | The English Country House Mussoorie','Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.','','2026-10-17 19:49:53','','','',NULL);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `seo_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seed` varchar(190) NOT NULL,
  `suggestions` mediumtext DEFAULT NULL,
  `fetched_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seed` (`seed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `seo_keywords` WRITE;
/*!40000 ALTER TABLE `seo_keywords` DISABLE KEYS */;
/*!40000 ALTER TABLE `seo_keywords` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `skey` varchar(120) NOT NULL,
  `svalue` mediumtext DEFAULT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('advance_percent','100'),
('appearance_preset','royal-gold'),
('banner_overlay','0.55'),
('body_scripts',''),
('book_float_enabled','1'),
('book_modal_enabled','1'),
('booking_notify_email','info@theenglishcountryhouse.com'),
('booking_otp_required','1'),
('cache_bust','1792246793'),
('chat_enabled','1'),
('chat_greeting','Hello! 👋 Welcome to The English Country House. How can I help you today?'),
('chat_idle_seconds','90'),
('chat_offline_msg','Our team is currently offline. You can reach us instantly on WhatsApp, or leave your number and we will call you back.'),
('chat_wait_seconds','45'),
('checkin_time','13:00'),
('checkout_time','11:00'),
('clean_urls','1'),
('color_accent','#e8c88f'),
('color_bg','#ffffff'),
('color_bg_alt','#f6f3ee'),
('color_btn_text','#ffffff'),
('color_footer_bg','#0f2238'),
('color_footer_text','#cfd8e3'),
('color_heading','#182635'),
('color_primary','#c9974b'),
('color_primary_dark','#a87a2f'),
('color_secondary','#0f2238'),
('color_text','#333b45'),
('contact_address','Village Kaplani Dhanaulti Road,\nMussoorie, Uttarakhand 248179, India'),
('contact_email','info@theenglishcountryhouse.com'),
('contact_phone','9456765555'),
('contact_phone2',''),
('currency','INR'),
('currency_symbol','₹'),
('favicon','assets/img/brand/favicon.png'),
('ffmpeg_path',''),
('font_accent','Great Vibes'),
('font_body','Outfit'),
('font_heading','Cormorant Garamond'),
('footer_about','A boutique luxury retreat perched in the tranquil hills of Mussoorie — five uniquely themed rooms, farm-to-table dining and warm English-country hospitality.'),
('footer_links','[{\"label\":\"Our Rooms\",\"url\":\"rooms.php\",\"visible\":1},{\"label\":\"Book Your Stay\",\"url\":\"booking.php\",\"visible\":1},{\"label\":\"Our Menu\",\"url\":\"menu.php\",\"visible\":1},{\"label\":\"Explore Mussoorie\",\"url\":\"explore.php\",\"visible\":1},{\"label\":\"Blog\",\"url\":\"blog.php\",\"visible\":1},{\"label\":\"FAQ\'s\",\"url\":\"faqs.php\",\"visible\":1}]'),
('ga_code',''),
('geo_lat','30.4265'),
('geo_lng','78.1465'),
('geo_placename','Mussoorie, Uttarakhand'),
('geo_region','IN-UT'),
('google_client_id',''),
('google_client_secret',''),
('google_place_id',''),
('google_places_key',''),
('gstin',''),
('head_scripts',''),
('invoice_notes','Thank you for choosing us! For any billing query call our reservation desk.'),
('live_editor_enabled','1'),
('llms_txt','# The English Country House Resort & Spa\n\nBoutique luxury resort in Mussoorie, Uttarakhand, India with 5 uniquely themed rooms (Victorian Suites, Elizabeth Lodge, Charlotte Tree Houses), pool, jacuzzi, sauna, farm-to-table multi-cuisine restaurant, and pet-friendly stays.\n\n- Rooms & tariffs: {BASE_URL}/rooms.php\n- Book online: {BASE_URL}/booking.php\n- FAQs: {BASE_URL}/faqs.php\n- Contact: {BASE_URL}/contact.php · Phone: 9456765555'),
('logo_footer','assets/img/brand/logo-light.png'),
('logo_footer_h','110'),
('logo_footer_w','0'),
('logo_header','assets/img/brand/logo-light.png'),
('logo_header_h','64'),
('logo_header_w','0'),
('logo_print','assets/img/brand/logo.png'),
('mail_from','no-reply@theenglishcountryhouse.com'),
('mail_from_name','The English Country House'),
('maintenance_mode','0'),
('maintenance_msg','We are polishing something beautiful. Our new website is under construction — call or WhatsApp us to book your stay meanwhile!'),
('map_embed','https://www.google.com/maps?q=Mussoorie+Dhanaulti+Road&output=embed'),
('media_video_compress_mb','6'),
('media_video_crf','27'),
('media_video_height','720'),
('media_video_server','1'),
('media_webp','1'),
('media_webp_quality','78'),
('menu_alert_email','1'),
('menu_alert_repeat','1'),
('menu_enabled','1'),
('menu_enforce_timings','0'),
('menu_hero_image','assets/img/restaurant.jpg'),
('menu_intro','Freshly prepared in our kitchen and served to your room, the deck or the restaurant. Choose your dishes, tell us your room — we will take it from there.'),
('menu_items','[{\"label\":\"Home\",\"url\":\"index.php\",\"visible\":1},{\"label\":\"About us\",\"url\":\"about.php\",\"visible\":1},{\"label\":\"Rooms\",\"url\":\"rooms.php\",\"visible\":1},{\"label\":\"Amenities\",\"url\":\"amenities.php\",\"visible\":1},{\"label\":\"Explore Mussoorie\",\"url\":\"explore.php\",\"visible\":1},{\"label\":\"Blog\",\"url\":\"blog.php\",\"visible\":1},{\"label\":\"FAQ\'s\",\"url\":\"faqs.php\",\"visible\":1},{\"label\":\"Our Menu\",\"url\":\"menu.php\",\"visible\":1},{\"label\":\"Contact\",\"url\":\"contact.php\",\"visible\":1}]'),
('menu_kitchen_from',''),
('menu_kitchen_to',''),
('menu_min_order','0'),
('menu_order_phone','9456765555'),
('menu_ordering_enabled','1'),
('menu_price_note','All prices are in ₹ (INR). Government taxes as applicable.'),
('menu_qr_color','#163a2c'),
('menu_qr_logo','assets/img/brand/logo.png'),
('menu_qr_rooms',''),
('menu_restaurant_name','Multicuisine Restaurant'),
('menu_room_required','1'),
('menu_show_footer_qr','1'),
('menu_tagline','Savor the Oak. Discover the Ivy.'),
('menu_tax_label','GST'),
('menu_tax_percent','0'),
('menu_theme_color','#163a2c'),
('menu_title','Our Menu'),
('menu_wa_cc','91'),
('menu_wa_cloud_lang','en'),
('menu_wa_cloud_phone_id',''),
('menu_wa_cloud_template',''),
('menu_wa_cloud_token',''),
('menu_wa_gateway_body',''),
('menu_wa_gateway_method','GET'),
('menu_wa_gateway_url',''),
('menu_wa_guest_open','1'),
('menu_wa_mode','link'),
('menu_wa_numbers','[{\"name\":\"Reception\",\"number\":\"9456765555\",\"active\":1}]'),
('menu_wait_minutes','30'),
('menu_wait_note','Every dish is cooked fresh to order'),
('newsletter_day','5'),
('newsletter_enabled','1'),
('newsletter_subject','This weekend in the hills 🏔 — offers & moments from The English Country House'),
('pay_cod_enabled','1'),
('pay_cod_label','Pay at Hotel'),
('pay_online_enabled','1'),
('pay_paypal_client',''),
('pay_paypal_enabled','0'),
('pay_paypal_mode','sandbox'),
('pay_paypal_secret',''),
('pay_paytm_enabled','0'),
('pay_paytm_key',''),
('pay_paytm_mid',''),
('pay_paytm_mode','stage'),
('pay_paytm_website','DEFAULT'),
('pay_razorpay_enabled','0'),
('pay_razorpay_key',''),
('pay_razorpay_secret',''),
('pay_stripe_enabled','0'),
('pay_stripe_pk',''),
('pay_stripe_sk',''),
('pay_upi_enabled','1'),
('pay_upi_id',''),
('pay_upi_name','The English Country House'),
('pay_upi_qr',''),
('pwa_enabled','1'),
('pwa_short_name','ECH Resort'),
('recaptcha_secret',''),
('recaptcha_site',''),
('refund_auto','1'),
('refund_days','4'),
('refund_percent','100'),
('robots_txt','User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /team/\nDisallow: /api/\nSitemap: {BASE_URL}/sitemap.xml'),
('schema_org',''),
('seo_desc','Boutique luxury retreat in Mussoorie with five uniquely themed rooms, private balconies, pool, jacuzzi, sauna, multi-cuisine restaurant and mountain views. Book your stay today.'),
('seo_keywords','hotel in mussoorie, resort in mussoorie, luxury stay mussoorie, boutique hotel dhanaulti, pet friendly resort mussoorie'),
('seo_title','The English Country House Resort & Spa | Boutique Luxury Hotel in Mussoorie'),
('site_name','The English Country House'),
('site_tagline','Resort & Spa — Mussoorie'),
('smtp_enabled','0'),
('smtp_host',''),
('smtp_pass',''),
('smtp_port','587'),
('smtp_secure','tls'),
('smtp_user',''),
('social_instagram','https://www.instagram.com/the_english_country_house?psln=NjBqN2x1Y21oa2Rt'),
('social_linkedin',''),
('social_x',''),
('social_youtube',''),
('tax_label','GST'),
('tax_percent','12'),
('upload_max_px','2000'),
('upload_quality','86'),
('wa_enabled','1'),
('wa_message','Hi! I would like to know more about The English Country House.'),
('wa_number','919456765555'),
('wa_position','right'),
('wa_show_desktop','1'),
('wa_show_mobile','1');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `social_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` enum('facebook','instagram','linkedin','x') NOT NULL,
  `label` varchar(160) DEFAULT '',
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `account_ref` varchar(190) DEFAULT '',
  `extra` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `social_accounts` WRITE;
/*!40000 ALTER TABLE `social_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_accounts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `social_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platforms` varchar(120) NOT NULL,
  `content` text DEFAULT NULL,
  `media` varchar(255) DEFAULT '',
  `media_type` enum('none','image','video') DEFAULT 'none',
  `link` varchar(500) DEFAULT '',
  `scheduled_at` datetime NOT NULL,
  `status` enum('draft','scheduled','publishing','published','partial','failed') DEFAULT 'scheduled',
  `results` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sched` (`status`,`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `social_posts` WRITE;
/*!40000 ALTER TABLE `social_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_posts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT '',
  `password` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `google_id` varchar(64) DEFAULT NULL,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `address` varchar(255) DEFAULT '',
  `city` varchar(100) DEFAULT '',
  `newsletter` tinyint(1) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `visitor_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `sender` enum('visitor','agent','bot','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `seen_by_agent` tinyint(1) DEFAULT 0,
  `seen_by_visitor` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_c` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `visitor_chat_messages` WRITE;
/*!40000 ALTER TABLE `visitor_chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitor_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `visitor_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_key` varchar(64) NOT NULL,
  `visitor_key` varchar(64) DEFAULT '',
  `name` varchar(160) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `email` varchar(190) DEFAULT '',
  `page` varchar(255) DEFAULT '',
  `ip` varchar(64) DEFAULT '',
  `mode` enum('bot','waiting','agent','whatsapp','callback') DEFAULT 'bot',
  `bot_step` varchar(30) DEFAULT 'greet',
  `agent_id` int(11) DEFAULT NULL,
  `status` enum('open','active','closed','missed','whatsapp') DEFAULT 'open',
  `rating` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `first_response_seconds` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `last_visitor_msg_at` datetime DEFAULT NULL,
  `last_agent_msg_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_key` (`chat_key`),
  KEY `idx_status` (`status`),
  KEY `idx_agent` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `visitor_chats` WRITE;
/*!40000 ALTER TABLE `visitor_chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitor_chats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_key` varchar(64) NOT NULL,
  `ip` varchar(64) DEFAULT '',
  `user_agent` varchar(500) DEFAULT '',
  `device` enum('desktop','mobile','tablet','bot') DEFAULT 'desktop',
  `first_page` varchar(255) DEFAULT '',
  `referrer` varchar(500) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `visitor_key` (`visitor_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `page` varchar(255) DEFAULT '',
  `referrer` varchar(500) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_v` (`visitor_id`),
  KEY `idx_d` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS=1;
