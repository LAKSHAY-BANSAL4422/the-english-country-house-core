<?php
/**
 * ============================================================
 *  AMENITIES (round 6: the amenity set supplied in "exp & amenities.zip")
 *  ------------------------------------------------------------
 *  While this file returns a list, the public Amenities page and the home page
 *  Amenities section show these amenities instead of the rows in Admin → Amenities.
 *  The database is not read or changed for them. Delete this file (or make it
 *  return []) to go back to Admin → Amenities.
 *  Room pages still list each room's amenities from the database.
 *
 *  One row per amenity, in the order shown on the Amenities page:
 *    name         as supplied (folder name in the ZIP)
 *    icon         Font Awesome icon, used only by the icon-card layout
 *    description  none were supplied, so left empty
 *    home         position among the home page tiles (1 = first), 0 = Amenities page only.
 *                 On desktop tiles 1 and 6 are the wide ones.
 *    photos       paths from the site root, first = main photo. Each has a smaller
 *                 copy next to it named {file}-800.jpg, used on cards and tiles.
 *    alt          one description per photo, for screen readers
 * ============================================================
 */
return [
    ['name'=>'Pool',             'icon'=>'fa-person-swimming', 'description'=>'', 'home'=>1,
     'photos'=>['assets/img/amenities/pool-1.jpg', 'assets/img/amenities/pool-2.jpg'],
     'alt'=>['Guest relaxing in the outdoor swimming pool beside a green living wall', 'Aerial view of the swimming pool and sun deck']],
    ['name'=>'Outdoor Jacuzzi',  'icon'=>'fa-hot-tub-person',  'description'=>'', 'home'=>2,
     'photos'=>['assets/img/amenities/outdoor-jacuzzi-1.jpg', 'assets/img/amenities/outdoor-jacuzzi-2.jpg'],
     'alt'=>['Guest in the outdoor jacuzzi at dusk with the hills behind', 'Lit outdoor jacuzzi at night above the valley lights']],
    ['name'=>'Sauna',            'icon'=>'fa-temperature-high','description'=>'', 'home'=>3,
     'photos'=>['assets/img/amenities/sauna-1.jpg'],
     'alt'=>['Wood-lined sauna cabin with a glass door and a mountain view']],
    ['name'=>'Gym',              'icon'=>'fa-dumbbell',        'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/gym-1.jpg'],
     'alt'=>['Gym with weights and machines under a wooden ceiling']],
    ['name'=>'Yoga',             'icon'=>'fa-spa',             'description'=>'', 'home'=>5,
     'photos'=>['assets/img/amenities/yoga-1.jpg'],
     'alt'=>['Two guests meditating under a carved wooden archway']],
    ['name'=>'Restaurant',       'icon'=>'fa-bowl-food',       'description'=>'', 'home'=>4,
     'photos'=>['assets/img/amenities/restaurant-1.jpg', 'assets/img/amenities/restaurant-2.jpg'],
     'alt'=>['Restaurant dining room with chandeliers and valley windows', 'Restaurant lounge and buffet counter']],
    ['name'=>'Patio',            'icon'=>'fa-chair',           'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/patio-1.jpg', 'assets/img/amenities/patio-2.jpg'],
     'alt'=>['Covered patio laid for dinner under string lights at dusk', 'Patio tables set for dinner beside the hillside view']],
    ['name'=>'Bonfire',          'icon'=>'fa-fire',            'description'=>'', 'home'=>6,
     'photos'=>['assets/img/amenities/bonfire-1.jpg'],
     'alt'=>['Bonfire pit ringed by wooden chairs on a terrace at dusk']],
    ['name'=>'Pool Table',       'icon'=>'fa-circle-dot',      'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/pool-table-1.jpg'],
     'alt'=>['Pool table in a games room with a fireplace']],
    ['name'=>'Basketball Court', 'icon'=>'fa-basketball',      'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/basketball-court-1.jpg'],
     'alt'=>['Fenced hillside basketball court with a mountain view']],
    ['name'=>'Trampoline',       'icon'=>'fa-child-reaching',  'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/trampoline-1.jpg'],
     'alt'=>['Trampoline on the lawn beside the court, under a fruit tree']],
    ['name'=>'EV Charging',      'icon'=>'fa-charging-station','description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/ev-charging-1.jpg'],
     'alt'=>['Wall-mounted EV charger beside a stone wall and potted plants']],
    /* round 10: three more amenities, photos supplied by the owner */
    ['name'=>'Library',          'icon'=>'fa-book-open',       'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/library-1.jpg'],
     'alt'=>['Library with white bookshelves, armchairs and a reading table by a valley window']],
    ['name'=>'Machan',           'icon'=>'fa-campground',      'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/machan-1.jpg'],
     'alt'=>['Raised wooden machan with a gabled roof, lit at night under a starry sky']],
    ['name'=>'Indoor Jacuzzi',   'icon'=>'fa-hot-tub-person',  'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/indoor-jacuzzi-1.jpg'],
     'alt'=>['Indoor jacuzzi tub in a wood-panelled room with blue patterned tiles']],
    /* round 16: Steam, photo supplied by the owner */
    ['name'=>'Steam',            'icon'=>'fa-cloud',           'description'=>'', 'home'=>0,
     'photos'=>['assets/img/amenities/steam-1.jpg'],
     'alt'=>['Glass steam shower cabin beside a jacuzzi tub, with blue patterned tiles and a wooden towel stand']],
];
