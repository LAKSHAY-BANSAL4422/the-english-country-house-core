<?php
/**
 * ============================================================
 *  THE ROOM CATEGORIES (presentation data, rounds 7-9; round 19: six room cards, the sixth is the two tree houses as a duplex)
 *  ------------------------------------------------------------
 *  The public site shows exactly these rooms, in this order, whatever rooms exist in Admin → Rooms:
 *  room cards (home and /rooms), room pages (/room/{slug}), the Rooms menu, the enquiry forms, the sitemap
 *  and the "rooms" count. No database access here; the rooms table is not read or changed for these.
 *
 *  One entry per room, keyed by its web address (slug):
 *    name     shown everywhere on the public site
 *    size     area
 *    old      earlier web addresses of this room; they redirect here (301)
 *    pets, bed, view, sleeps, max_adults, max_children   shown on the cards and room page
 *    baths    number of washrooms, shown on the card and room page when set (round 19)
 *    combo    the rooms this option is made of (round 19): it is not counted as an extra room in "N rooms" figures
 *    short    one-line description (cards, page description)
 *    about    room page text (HTML)
 *    amenity_ids  ids from Admin → Amenities listed on the room page under "Room Amenities"
 *    seo_title, seo_desc, seo_keywords   the page's <title>, meta description and keywords (round 14)
 *    subline  keyword line under the room name in the page header, inside the H1 (round 14)
 *    alts     alt text for each photo, in the same order as photos (round 14)
 *    faqs     [question, answer] pairs for "Questions About {room}" (round 14)
 *    more     closing line under "More Rooms to Consider"; {amenities} {explore} {jabarkhet} {dhanaulti} become links (round 14)
 *    photos   paths from the site root, in the supplied order; the first is the main photo
 *             (each has a smaller copy, {file}-800.jpg, used by the card slideshows and thumbnail strip)
 *
 *  Descriptions, bed and view are the ones the site had for these rooms (seed data); edit them here.
 *  Delete this file to go back to the rooms in Admin → Rooms.
 * ============================================================
 */
return [
    // Victorian Suite 1.zip: room01.jpg … room06.jpg
    'victorian-suite-1' => [
        'name'=>'Victorian Suite 1', 'size'=>'323.1 sq. ft.', 'old'=>['victorian-suite-i'],
        'pets'=>0, 'bed'=>'King Four-Poster', 'view'=>'Valley View', 'sleeps'=>2, 'max_adults'=>3, 'max_children'=>2,
        'short'=>'A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.',
        'about'=>'<p>A regal four-poster suite with curated Victorian interiors, private balcony and an elegant bathtub.</p><p>A sage-green four-poster bed sits under a timber ceiling, with floral curtains at the glass doors. Step out onto the balcony and the valley fills the view all day.</p><p>This is a room for two who want a slow weekend. Take tea on the balcony, soak in the bathtub by the window, then head down to the outdoor jacuzzi or the bonfire after dinner.</p><p>Planning a honeymoon or an anniversary? Tell us when you enquire and we will help you plan the stay.</p>',   // round 14: copy from the SEO content document (v2)
        // round 14 (SEO): meta tags, the H1 subline, photo alt text (same order as photos), room questions, closing links line
        'seo_title'=>'Four-Poster Suite in Mussoorie | English Country House',
        'seo_desc'=>'A four-poster suite in Mussoorie with Victorian interiors, a private valley-view balcony and a bathtub. Enquire direct at The English Country House.',
        'seo_keywords'=>'four-poster suite in Mussoorie, honeymoon suite in Mussoorie, valley view suite in Mussoorie, Victorian suite in Mussoorie',
        'subline'=>'Four-poster suite with valley views in Mussoorie',
        'alts'=>[
            'Sage-green four-poster bed and floral curtains beside the valley-view balcony in Victorian Suite 1 at The English Country House, Mussoorie',
            'Four-poster bed, gilded sofa and writing desk in Victorian Suite 1 at The English Country House, Mussoorie',
            'King four-poster bed facing the TV wall and balcony doors in Victorian Suite 1 at The English Country House, Mussoorie',
            'Rose-petal bath in a freestanding bathtub by a valley-view window in Victorian Suite 1 at The English Country House, Mussoorie',
            'Green-tiled bathroom with a glass shower and bathtub in Victorian Suite 1 at The English Country House, Mussoorie',
            'Private balcony with chairs at sunset over the valley, Victorian Suite 1 at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['Is Victorian Suite 1 a good room for a honeymoon?', 'Yes. It has a four-poster bed, a private balcony, a bathtub and valley views. Tell us about the occasion when you enquire.'],
            ['What can I see from the room?', 'The valley, from the bed and from the balcony.'],
            ['Can I bring a pet?', 'Not in this room. Our pet-friendly rooms are Elizabeth Lodge, Charlotte Tree House 1 and Charlotte Tree House 2.'],
        ],
        'more'=>'Explore the outdoor jacuzzi, sauna and pool on our <a href="{amenities}">amenities</a> page, or see <a href="{explore}">places to visit near the resort</a>.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-01.jpg',
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-02.jpg',
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-03.jpg',
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-04.jpg',
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-05.jpg',
            'assets/img/room-photos/victorian-suite-1/victorian-suite-1-06.jpg',
        ],
    ],
    // Victorian Suite 2.zip: room01.jpg … room07.jpg
    'victorian-suite-2' => [
        'name'=>'Victorian Suite 2', 'size'=>'374 sq. ft.', 'old'=>['victorian-suite-ii'],
        'pets'=>0, 'bed'=>'King', 'view'=>'Valley View', 'sleeps'=>2, 'max_adults'=>3, 'max_children'=>2,
        'short'=>'Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.',
        'about'=>'<p>Victorian elegance with ornate panelling, a plush king bed, TV lounge wall and a private balcony.</p><p>At 374 sq ft, this is the largest of our five rooms. White panelling picked out in gold frames the walls, and a gilded, button-tufted headboard rises behind the bed. A writing table and a bench at the foot of the bed leave room to unpack and settle in.</p><p>Out on the private balcony, the valley opens below you. Later, the outdoor jacuzzi, the sauna and the steam bath are a short walk away.</p><p>Celebrating something? Tell us when you enquire, or plan your days with our guide to <a href="{explore}">places to visit in Mussoorie</a>.</p>',   // round 14: copy from the SEO content document (v2)
        // round 14 (SEO): meta tags, the H1 subline, photo alt text (same order as photos), room questions, closing links line
        'seo_title'=>'Victorian Suite in Mussoorie | English Country House',
        'seo_desc'=>'A Victorian suite in Mussoorie with ornate panelling, a gilded headboard, a TV lounge wall and a private valley-view balcony. Enquire directly.',
        'seo_keywords'=>'Victorian suite in Mussoorie, luxury suite in Mussoorie, valley view suite in Mussoorie, suite with balcony in Mussoorie',
        'subline'=>'Victorian suite with valley views in Mussoorie',
        'alts'=>[
            'King bed with a gilded, button-tufted headboard and white-and-gold panelling in Victorian Suite 2 at The English Country House, Mussoorie',
            'Bed, sofa and TV lounge wall with glass doors to the balcony in Victorian Suite 2 at The English Country House, Mussoorie',
            'Gilded king bed facing the TV lounge wall in Victorian Suite 2 at The English Country House, Mussoorie',
            'Blue-tiled bathroom with a clawfoot bathtub and glass shower in Victorian Suite 2 at The English Country House, Mussoorie',
            'Blue-tiled bathroom vanity with a white-framed mirror in Victorian Suite 2 at The English Country House, Mussoorie',
            'Covered seating area with sofas looking over the valley at sunset, Victorian Suite 2 at The English Country House, Mussoorie',
            'Sunset over the hills from the balcony of Victorian Suite 2 at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['Which is the largest room?', 'Victorian Suite 2 is the largest of our five rooms, at 374 sq ft.'],
            ['Does it have a balcony?', 'Yes, a private balcony facing the valley.'],
            ['Is there a TV?', 'Yes. The room has a TV lounge wall.'],
            ['Are pets allowed?', 'No. Elizabeth Lodge, Charlotte Tree House 1 and Charlotte Tree House 2 welcome pets.'],
        ],
        'more'=>'See the jacuzzi, sauna and pool on our <a href="{amenities}">amenities</a> page.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-01.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-02.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-03.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-04.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-05.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-06.jpg',
            'assets/img/room-photos/victorian-suite-2/victorian-suite-2-07.jpg',
        ],
    ],
    // Elizabeth Lodge.zip: room01.jpg … room05.jpg
    'elizabeth-lodge' => [
        'name'=>'Elizabeth Lodge', 'size'=>'227.96 sq. ft.', 'old'=>[],
        'pets'=>1, 'bed'=>'King', 'view'=>'Mountain View', 'sleeps'=>2, 'max_adults'=>3, 'max_children'=>2,
        'short'=>'A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.',
        'about'=>'<p>A pet-friendly lodge with panoramic mountain-view windows, gold-accented decor and a private deck.</p><p>Wide windows face the mountains, and a timber ceiling warms the white-panelled walls. A carved white bed, framed paintings and a few small ornaments give the room a lived-in, collected feel. In the bathroom, a freestanding bathtub sits by a window over the hills.</p><p>Book this room when your dog is coming too. Tell us about your pet when you enquire, and we will prepare the room.</p>',   // round 14: copy from the SEO content document (v2)
        // round 14 (SEO): meta tags, the H1 subline, photo alt text (same order as photos), room questions, closing links line
        'seo_title'=>'Pet-Friendly Room in Mussoorie | English Country House',
        'seo_desc'=>'A pet-friendly room in Mussoorie with panoramic mountain-view windows, gold-accented decor and a private deck. Enquire directly for dates and rates.',
        'seo_keywords'=>'pet friendly room in Mussoorie, pet friendly resort in Mussoorie, dog friendly stay in Mussoorie, mountain view room in Mussoorie',
        'subline'=>'Pet-friendly room with mountain views in Mussoorie',
        'alts'=>[
            'White four-poster bed under a timber ceiling with lanterns in Elizabeth Lodge at The English Country House, Mussoorie',
            'Carved white bed and armchairs beside glass doors in Elizabeth Lodge at The English Country House, Mussoorie',
            'Bed facing wide mountain-view windows in Elizabeth Lodge at The English Country House, Mussoorie',
            'Freestanding bathtub by a mountain-view window, with a bathrobe, in Elizabeth Lodge at The English Country House, Mussoorie',
            'Green-tiled bathroom with a glass shower and vanity in Elizabeth Lodge at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['Can I bring my dog to Elizabeth Lodge?', 'Yes. It is one of our three pet-friendly rooms, along with Charlotte Tree House 1 and Charlotte Tree House 2. Please tell us about your pet when you enquire.'],
            ['What is the view like?', 'Mountains, through wide windows and from the private deck.'],
            ['Does the room have a bathtub?', 'Yes, a freestanding bathtub beside a window that looks over the hills.'],
        ],
        'more'=>'Find Camel\'s Back Road and other <a href="{explore}">places to visit near the resort</a>.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/elizabeth-lodge/elizabeth-lodge-01.jpg',
            'assets/img/room-photos/elizabeth-lodge/elizabeth-lodge-02.jpg',
            'assets/img/room-photos/elizabeth-lodge/elizabeth-lodge-03.jpg',
            'assets/img/room-photos/elizabeth-lodge/elizabeth-lodge-04.jpg',
            'assets/img/room-photos/elizabeth-lodge/elizabeth-lodge-05.jpg',
        ],
    ],
    // Charlotte Tree House 1.zip: room01.jpg … room05.jpg
    'tree-house-1' => [
        'name'=>'Charlotte Tree House 1', 'size'=>'127.17 sq. ft.', 'old'=>['charlotte-tree-house-i'],
        'pets'=>1, 'bed'=>'Queen', 'view'=>'Valley View', 'sleeps'=>2, 'max_adults'=>3, 'max_children'=>2,
        'short'=>'A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.',
        'about'=>'<p>A warm wooden tree-house escape with floor-to-ceiling valley views. Pet friendly.</p><p>Honey-coloured wood covers the walls and ceiling. A wide window frames the valley, with two cane chairs beside it for morning tea, and floral cushions on the Queen bed add a soft English note. The bathroom has a clawfoot bathtub by a valley-view window.</p><p>It is the most compact of our five rooms, so it feels snug rather than small. It suits two who plan to spend their days outdoors: in the jacuzzi, around the bonfire or out on the hill roads. Your dog is welcome too.</p><p>Want a quiet base near the forest? See our <a href="{jabarkhet}">stay near Jabarkhet Nature Reserve</a>.</p>',   // round 14: copy from the SEO content document (v2)
        // round 14 (SEO): meta tags, the H1 subline, photo alt text (same order as photos), room questions, closing links line
        'seo_title'=>'Tree House Stay in Mussoorie | English Country House',
        'seo_desc'=>'A wooden tree house in Mussoorie with floor-to-ceiling valley views and a Queen bed. Pet friendly. Enquire direct at The English Country House.',
        'seo_keywords'=>'tree house stay in Mussoorie, wooden tree house in Mussoorie, pet friendly tree house in Mussoorie, valley view room in Mussoorie',
        'subline'=>'Tree house stay with valley views in Mussoorie',
        'alts'=>[
            'Wood-panelled bedroom with a valley-view window and two cane chairs in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Queen bed with floral cushions against honey-coloured wood panelling in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Queen bed facing the valley window and wall-mounted TV in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Bathroom with a clawfoot bathtub beside a valley-view window in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Bubble bath in a clawfoot bathtub overlooking the hills in Charlotte Tree House 1 at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['How big is Charlotte Tree House 1?', 'It is a Queen-bed room of 127.17 sq ft, the most compact of our five.'],
            ['Which view does it have?', 'The valley, through floor-to-ceiling windows.'],
            ['Can I bring my dog?', 'Yes. Dogs are welcome in this room. Please tell us about your pet when you enquire.'],
        ],
        'more'=>'Read about the outdoor jacuzzi, sauna and steam bath on our <a href="{amenities}">amenities</a> page.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/tree-house-1/tree-house-1-01.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-02.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-03.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-04.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-05.jpg',
        ],
    ],
    // Charlotte Tree House 2.zip: room01.jpg … room05.jpg
    'tree-house-2' => [
        'name'=>'Charlotte Tree House 2', 'size'=>'168.74 sq. ft.', 'old'=>['charlotte-tree-house-ii'],
        'pets'=>1, 'bed'=>'Queen', 'view'=>'Garden & Valley View', 'sleeps'=>2, 'max_adults'=>3, 'max_children'=>2,
        'short'=>'Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.',
        'about'=>'<p>Wood-panelled tree house with a garden-facing picture window and sunset views. Pet friendly.</p><p>The picture window and glass doors open onto the trees, so the garden and the valley fill the frame from the bed. Timber walls and a wooden ceiling keep the room warm when the evening turns cool.</p><p>At 168.74 sq ft it has a little more room than Charlotte Tree House 1. Watch the sunset from the cane chairs by the window, and bring your dog along if you like.</p><p>Staying on the Dhanaulti side of Mussoorie? See our <a href="{dhanaulti}">resort near Dhanaulti</a>.</p>',   // round 14: copy from the SEO content document (v2)
        // round 14 (SEO): meta tags, the H1 subline, photo alt text (same order as photos), room questions, closing links line
        'seo_title'=>'Garden View Tree House in Mussoorie | English Country House',
        'seo_desc'=>'A wood-panelled tree house in Mussoorie with a garden-facing picture window, valley views and sunset light. Pet friendly. Enquire directly.',
        'seo_keywords'=>'garden view tree house in Mussoorie, pet friendly tree house in Mussoorie, wood-panelled tree house in Mussoorie, sunset view room in Mussoorie',
        'subline'=>'Garden-view tree house in Mussoorie',
        'alts'=>[
            'Wood-panelled bedroom with a wide picture window over the garden and valley in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Queen bed, cane chairs and a wooden staircase beside a wide valley window in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Queen bed and glass doors opening to the hillside in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Blue-tiled bathroom with a rain shower and bathtub in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Freestanding bathtub beside an open door to the hillside in Charlotte Tree House 2 at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['How is Charlotte Tree House 2 different from Charlotte Tree House 1?', 'It is slightly larger, at 168.74 sq ft against 127.17 sq ft, and it looks over the garden as well as the valley.'],
            ['Can I see the sunset from the room?', 'Yes. The window faces the garden and valley, and the room catches the evening light.'],
            ['Are pets allowed?', 'Yes. Pets are welcome in this room. Please tell us about your pet when you enquire.'],
        ],
        'more'=>'Plan your days with our guide to <a href="{explore}">places to visit near the resort</a>.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/tree-house-2/tree-house-2-01.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-02.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-03.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-04.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-05.jpg',
        ],
    ],
    // round 19: Charlotte Tree House 1 and Charlotte Tree House 2 booked together as one duplex (an extra room option; the two
    // tree houses stay bookable on their own). Photos: Tree House 1 then Tree House 2, each in its own order.
    'charlotte-tree-house-duplex' => [
        'name'=>'Charlotte Tree House Duplex', 'size'=>'295.91 sq. ft.', 'old'=>[], 'baths'=>2, 'combo'=>['tree-house-1', 'tree-house-2'],
        'pets'=>1, 'bed'=>'Two Queen', 'view'=>'Valley & Garden View', 'sleeps'=>4, 'max_adults'=>4, 'max_children'=>0,
        'short'=>'Both Charlotte tree houses as one duplex for four: two wooden rooms, two washrooms, valley and garden views. Pet friendly.',
        'about'=>'<p>Book both of our Charlotte tree houses together and the whole wooden hideaway is yours. Charlotte Tree House 1 and Charlotte Tree House 2 become one duplex for four guests: two Queen-bed rooms, two separate washrooms and 295.91 sq ft of honey-coloured timber among the trees.</p><p>In the first room, a wide window frames the valley, with two cane chairs beside it for morning tea and a clawfoot bathtub looking out over the hills. In the second, a picture window and glass doors open onto the garden and the valley, and the room fills with light at sunset. Wooden walls and ceilings keep both rooms warm when the evening turns cool.</p><p>It is made for a family or a group of four who want to stay together while each pair keeps its own room and washroom. Spend the days in the outdoor jacuzzi, by the pool or out on the hill roads, then meet around the bonfire after dinner. Your dog is welcome too.</p><p>Want a quiet base near the forest? See our <a href="{jabarkhet}">stay near Jabarkhet Nature Reserve</a>.</p>',
        'seo_title'=>'Tree House Duplex for 4 in Mussoorie | English Country House',
        'seo_desc'=>'Two wooden tree houses as one duplex in Mussoorie: two Queen rooms, two washrooms, valley and garden views. Sleeps 4, pet friendly. Enquire direct.',
        'seo_keywords'=>'tree house duplex in Mussoorie, family tree house in Mussoorie, room for 4 in Mussoorie, pet friendly tree house in Mussoorie',
        'subline'=>'Two tree houses, one duplex for four in Mussoorie',
        'alts'=>[
            'Wood-panelled bedroom with a valley-view window and two cane chairs in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Queen bed with floral cushions against honey-coloured wood panelling in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Queen bed facing the valley window and wall-mounted TV in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Bathroom with a clawfoot bathtub beside a valley-view window in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Bubble bath in a clawfoot bathtub overlooking the hills in Charlotte Tree House 1 at The English Country House, Mussoorie',
            'Wood-panelled bedroom with a wide picture window over the garden and valley in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Queen bed, cane chairs and a wooden staircase beside a wide valley window in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Queen bed and glass doors opening to the hillside in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Blue-tiled bathroom with a rain shower and bathtub in Charlotte Tree House 2 at The English Country House, Mussoorie',
            'Freestanding bathtub beside an open door to the hillside in Charlotte Tree House 2 at The English Country House, Mussoorie',
        ],
        'faqs'=>[
            ['Who is the Charlotte Tree House Duplex for?', 'Families or groups of four. It is Charlotte Tree House 1 and Charlotte Tree House 2 booked together: two Queen-bed rooms with two separate washrooms.'],
            ['How big is it?', '295.91 sq ft in all: 127.17 sq ft in Charlotte Tree House 1 and 168.74 sq ft in Charlotte Tree House 2.'],
            ['Can we bring our dog?', 'Yes. Both tree houses are pet friendly. Please tell us about your pet when you enquire.'],
        ],
        'more'=>'Read about the outdoor jacuzzi, sauna and pool on our <a href="{amenities}">amenities</a> page, or plan your days with our guide to <a href="{explore}">places to visit near the resort</a>.',
        'amenity_ids'=>[1, 3, 4, 11, 13, 14],
        'photos'=>[
            'assets/img/room-photos/tree-house-1/tree-house-1-01.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-02.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-03.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-04.jpg',
            'assets/img/room-photos/tree-house-1/tree-house-1-05.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-01.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-02.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-03.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-04.jpg',
            'assets/img/room-photos/tree-house-2/tree-house-2-05.jpg',
        ],
    ],
];
