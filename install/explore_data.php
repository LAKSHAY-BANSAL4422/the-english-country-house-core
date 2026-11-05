<?php
/**
 * "Explore Mussoorie" — the attractions supplied by the client (10 at launch + 4 in round 11) (distances from the property on Dhanaulti Road)
 * plus approximate map coordinates (editable in Admin → Explore Mussoorie). Used by the first-install seed and by
 * migration v7 on existing sites (only rows that do not exist yet are added).
 */
function explore_default_attractions(): array {
    return [
      ['name'=>'Lal Tibba Scenic Point','category'=>'viewpoint','label'=>'Viewpoint','distance_km'=>'8.3','travel_time'=>'~25 mins','lat'=>30.4636,'lng'=>78.1000,'featured'=>1,
       'description'=>'Historic colonial-era vantage point and the highest peak in Mussoorie, offering panoramic Himalayan views.',
       'long_description'=>'<p>Perched in Landour at about 2,290 m, Lal Tibba is the highest point in Mussoorie. On a clear morning the telescope at the viewpoint picks out the snow peaks of Banderpunch, Kedarnath and Badrinath. Pair it with a walk through the old Landour bazaar and a cup of tea at a hillside café.</p>'],
      ['name'=>'Benog Wildlife Sanctuary','category'=>'nature','label'=>'Wildlife Sanctuary','distance_km'=>'6 to 7','travel_time'=>'~20 mins','lat'=>30.4680,'lng'=>78.0330,'featured'=>1,
       'description'=>'Scenic forest reserve featuring pine and fir trees, ideal for birdwatching, nature walks, and trekking.',
       'long_description'=>'<p>Part of the Rajaji National Park buffer, Benog is a quiet reserve of pine, deodar and oak. Look out for the Himalayan goral, red-billed blue magpies and, if you are lucky, the rare mountain quail. The trek to the Jwalaji Temple on Benog Tibba is a gentle 4 km climb with sweeping valley views.</p>'],
      ['name'=>'Cloud End','category'=>'historic','label'=>'Scenic Spot','distance_km'=>'5 to 6','travel_time'=>'~18 mins','lat'=>30.4600,'lng'=>78.0210,'featured'=>1,
       'description'=>'Historic heritage building surrounded by thick forests, marking the geographical end of Mussoorie with peaceful walking trails.',
       'long_description'=>'<p>Built in 1838 by a British major, Cloud End was one of the first four buildings in Mussoorie. Today it sits at the quiet western edge of the hill station, wrapped in thick forest with walking trails that feel a world away from the Mall Road crowds.</p>'],
      ['name'=>'George Everest House','category'=>'historic','label'=>'Historic Site','distance_km'=>'10 to 11','travel_time'=>'~30 mins','lat'=>30.4540,'lng'=>78.0335,'featured'=>1,
       'description'=>'Historic landmark and former estate of Sir George Everest, Surveyor-General of India, offering 360-degree valley views and gentle ridge walks.',
       'long_description'=>'<p>The restored Park Estate was the home and laboratory of Sir George Everest, after whom the world\'s highest peak is named. The open ridge around the house gives 360° views — the Doon valley on one side, the Aglar river valley and snow peaks on the other — and makes for a perfect sunset walk.</p>'],
      ['name'=>"Camel's Back Road",'category'=>'viewpoint','label'=>'Scenic Walk','distance_km'=>'9','travel_time'=>'~25 mins','lat'=>30.4630,'lng'=>78.0760,'featured'=>0,
       'description'=>"A peaceful, nature-wrapped walking trail named for a rock formation shaped like a camel's hump, popular for sunset views.",
       'long_description'=>'<p>A 3 km loop between Kulri and Library Bazaar, Camel\'s Back Road is Mussoorie\'s favourite evening stroll — horse rides, the old cemetery, and a famous sunset point looking out over the ranges.</p>'],
      ['name'=>'Bhatta Falls','category'=>'waterfall','label'=>'Waterfall','distance_km'=>'14.4','travel_time'=>'~40 mins','lat'=>30.4285,'lng'=>78.0515,'featured'=>1,
       'description'=>'A natural, cascading waterfall surrounded by greenery with a pool area, accessible via a short ropeway ride.',
       'long_description'=>'<p>On the Mussoorie–Dehradun road, Bhatta Falls tumbles into a series of natural pools that are ideal for a dip in summer. A short ropeway takes you down to the falls; there are changing rooms and small food stalls at the base.</p>'],
      ['name'=>'Jharipani Falls','category'=>'waterfall','label'=>'Waterfall','distance_km'=>'12 to 13','travel_time'=>'~35 mins','lat'=>30.4100,'lng'=>78.0850,'featured'=>1,
       'description'=>'A picturesque and less-crowded waterfall tucked away amidst Shivalik hills, reached via a scenic short trek.',
       'long_description'=>'<p>Quieter than its famous cousins, Jharipani Falls is reached by a 1.5 km walk from Jharipani village through oak forest. Best visited after the monsoon when the cascade is at its fullest.</p>'],
      ['name'=>'Company Garden','category'=>'garden','label'=>'Garden','distance_km'=>'7 to 8','travel_time'=>'~22 mins','lat'=>30.4650,'lng'=>78.0460,'featured'=>0,
       'description'=>'A popular colonial-period botanical garden featuring vibrant flowerbeds, an artificial boating lake, and open lawns.',
       'long_description'=>'<p>Laid out in the 1800s by the botanist Dr H. Fackner, the Municipal (Company) Garden is a riot of flowerbeds, a small boating lake, a wax museum and plenty of lawn for a family afternoon.</p>'],
      ['name'=>'Mussoorie Lake','category'=>'lake','label'=>'Lake','distance_km'=>'11 to 12','travel_time'=>'~30 mins','lat'=>30.4330,'lng'=>78.0790,'featured'=>0,
       'description'=>'A scenic man-made water reservoir surrounded by hills, offering boating, zip-lining, and water zorbing.',
       'long_description'=>'<p>A small, pretty reservoir on the Dehradun road with paddle boats, a zip-line across the water and views of the Doon valley lights after dark.</p>'],
      ['name'=>"Robber's Cave (Gucchupani)",'category'=>'nature','label'=>'Natural Wonder','distance_km'=>'25','travel_time'=>'~60 mins','lat'=>30.3760,'lng'=>78.0580,'featured'=>0,
       'description'=>'A natural river cave formation near Dehradun where a stream flows right through narrow rock-cut corridors — great for a unique outdoor walk.',
       'long_description'=>'<p>A 600 m long river cave outside Dehradun where you wade through ankle-deep cold water between towering limestone walls. Carry a change of clothes and sandals that can get wet.</p>'],
      // round 11: four more places from the client's list (distance and "Why Go" text exactly as supplied; no travel time was given)
      // round 13: pins and "Get Directions" use the exact Google Maps places the client sent
      ['name'=>'Landour & Char Dukan','category'=>'market','label'=>'Heritage & Cafés','distance_km'=>'8.7','travel_time'=>'','lat'=>30.4554533,'lng'=>78.1022729,'maps_url'=>'https://maps.app.goo.gl/dzoGdW4FWCYhHka48','featured'=>1,
       'description'=>'Enjoy pine-lined lanes, colonial-era charm, cosy cafés, and the famous pancakes at Char Dukan.','long_description'=>''],
      ['name'=>'Gun Hill','category'=>'viewpoint','label'=>'Viewpoint','distance_km'=>'11.6','travel_time'=>'','lat'=>30.4590275,'lng'=>78.0748906,'maps_url'=>'https://maps.app.goo.gl/JbHb65ZZDuL5nFnW8','featured'=>1,
       'description'=>'Take the ropeway or walk uphill for panoramic views of Mussoorie and the Doon Valley.','long_description'=>''],
      ['name'=>'Kempty Falls','category'=>'waterfall','label'=>'Waterfall','distance_km'=>'15–20','travel_time'=>'','lat'=>30.4877782,'lng'=>78.0366836,'maps_url'=>'https://maps.app.goo.gl/rqQ2LZnSTnepPuy9A','featured'=>1,
       'description'=>'Visit Mussoorie’s famous waterfall, surrounded by lush greenery, ideal for a refreshing nature outing.','long_description'=>''],
      ['name'=>'Mall Road','category'=>'market','label'=>'Shopping & Promenade','distance_km'=>'9–12','travel_time'=>'','lat'=>30.4597717,'lng'=>78.0644723,'maps_url'=>'https://maps.app.goo.gl/epPNYd4oZgfzXFCf7','featured'=>1,
       'description'=>'Explore local shops, sample street food, browse souvenirs, and enjoy Mussoorie’s lively evening atmosphere.','long_description'=>''],
    ];
}

/** Insert the default attractions that are missing (matched by name). Returns the number added. */
function explore_seed_attractions(mysqli $db): int {
    $added = 0; $sort = (int)($db->query("SELECT COALESCE(MAX(sort),0) FROM attractions")->fetch_row()[0] ?? 0);
    $chk = $db->prepare("SELECT id FROM attractions WHERE name=?");
    $ins = $db->prepare("INSERT INTO attractions (name,slug,category,label,description,long_description,distance_km,travel_time,lat,lng,maps_url,image,images,featured,active,sort) VALUES (?,?,?,?,?,?,?,?,?,?,?,'','[]',?,1,?)");
    // photos are not stored here: attraction_img() picks up assets/img/explore/<slug>.jpg when no photo is set in Admin
    foreach (explore_default_attractions() as $a) {
        $chk->bind_param('s', $a['name']); $chk->execute();
        if ($chk->get_result()->fetch_assoc()) continue;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $a['name']))), '-');
        $sort++;
        $maps = $a['maps_url'] ?? '';
        $ins->bind_param('ssssssssddsii', $a['name'], $slug, $a['category'], $a['label'], $a['description'], $a['long_description'], $a['distance_km'], $a['travel_time'], $a['lat'], $a['lng'], $maps, $a['featured'], $sort);
        $ins->execute(); $added++;
    }
    return $added;
}

/** Default sections of the "Explore Mussoorie" page */
function explore_page_sections(): array {
    return [
      ['type'=>'attractions_map','visible'=>1,'title'=>'Attractions Map','subtitle'=>'Explore The Hidden Gems','image'=>'','bg'=>'','tx'=>'',
       'content'=>'<p>From panoramic viewpoints to lush forests and cascading waterfalls, discover the best of Mussoorie with our curated guide to must-visit attractions — all within easy reach of the house.</p>'],
      ['type'=>'attractions','visible'=>1,'title'=>'Must-Visit Attractions','subtitle'=>'Viewpoints · Heritage · Nature','content'=>'','image'=>'','bg'=>'','tx'=>'','opts'=>['layout'=>'featured','category'=>'no_waterfall','limit'=>0]],
      ['type'=>'attractions','visible'=>1,'title'=>'Waterfalls','subtitle'=>'Natural Wonders','image'=>'','bg'=>'#f6f3ee','tx'=>'','opts'=>['layout'=>'duo','category'=>'waterfall','limit'=>4],
       'content'=>'<p>Feel the purity of nature at Kempty Falls, Bhatta Falls and Jharipani Falls, where cascading waters and serene surroundings create the perfect escape.</p>'],
      ['type'=>'cta','visible'=>1,'title'=>'Let Our Concierge Guide You','subtitle'=>'Need A Personal Recommendation?','content'=>'<p>Custom itineraries · local insights · exclusive experiences — plan your perfect Mussoorie days with our team.</p>','image'=>'','btn1_label'=>'Call Concierge','btn1_url'=>'tel:9456765555','btn2_label'=>'WhatsApp Us','btn2_url'=>'https://wa.me/919456765555','bg'=>'','tx'=>''],
    ];
}
