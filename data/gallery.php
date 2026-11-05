<?php
/**
 * ============================================================
 *  GALLERY PHOTOS (presentation data, round 2 redesign)
 *  ------------------------------------------------------------
 *  Shown on /gallery together with any photos uploaded in Admin → Gallery
 *  (those come first). No database access here.
 *
 *  One row per photo:
 *    src      path from the site root (jpg / png / webp)
 *    category one of: Rooms, Dining, Gardens & Grounds, Weddings & Events, Spa & Wellness, Nearby
 *    caption  short line shown on hover and in the lightbox
 *    alt      description for screen readers and search (round 14: ends "at The English Country House, Mussoorie")
 *             (round 14: room captions use the current room names)
 *
 *  Smaller copies for fast loading live in assets/img/gallery/{file name}-640.jpg and -1280.jpg.
 *  They are optional: without them the original file is used.
 *
 *  REPLACE CONTENT: captions are first drafts (Nearby captions are generic on purpose: name the places once confirmed); swap in new photos as they arrive.
 * ============================================================
 */
return [
    // Rooms
    ['src'=>'uploads/rooms/261017-023a45bf.jpg', 'category'=>'Rooms', 'caption'=>'Victorian Suite 1',            'alt'=>'Four-poster bed with gilded Victorian furniture and a hill view at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-0616c11e.jpg', 'category'=>'Rooms', 'caption'=>'Tree House',                  'alt'=>'Wood-panelled bedroom with a wide window over the valley at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-3725d0c5.jpg', 'category'=>'Rooms', 'caption'=>'Victorian Suite 2',           'alt'=>'Twin beds with carved white-and-gold headboards at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-541f5280.jpg', 'category'=>'Rooms', 'caption'=>'A bath with a view',           'alt'=>'Free-standing bathtub with rose petals beside a mountain window at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-3a1d19ce.jpg', 'category'=>'Rooms', 'caption'=>'Morning light, Victorian Suite', 'alt'=>'Bright bedroom with a sofa by a large window at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-687c5760.jpg', 'category'=>'Rooms', 'caption'=>'Elizabeth Lodge',              'alt'=>'Pine-clad bedroom with a mountain view and floral cushions at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-780221bf.jpg', 'category'=>'Rooms', 'caption'=>'Tub by the window',            'alt'=>'Bathtub facing a window over the Himalayan foothills at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-96de0a99.jpg', 'category'=>'Rooms', 'caption'=>'Tree House bedroom',           'alt'=>'Wooden bedroom with a wall-mounted TV and a valley window at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-8d67a1b6.jpg', 'category'=>'Rooms', 'caption'=>'Garden-view bathroom',         'alt'=>'Bathroom with a bathtub, green tiles and a garden view at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-ade0f56a.jpg', 'category'=>'Rooms', 'caption'=>'Charlotte Tree House 2',                'alt'=>'Wood-panelled bedroom with French windows at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-b4bcc962.jpg', 'category'=>'Rooms', 'caption'=>'Four-poster suite',            'alt'=>'White four-poster bed and antique furniture at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-c1233d12.jpg', 'category'=>'Rooms', 'caption'=>'Loft with a view',             'alt'=>'Bedroom with a wooden staircase and a panoramic window at The English Country House, Mussoorie'],
    ['src'=>'uploads/rooms/261017-d65109d4.jpg', 'category'=>'Rooms', 'caption'=>'Victorian Suite lounge',       'alt'=>'Suite with a chaise longue and a gilded bed at The English Country House, Mussoorie'],
    // Dining
    ['src'=>'assets/img/restaurant.jpg',           'category'=>'Dining', 'caption'=>'Our multi-cuisine restaurant', 'alt'=>'Restaurant dining room with set tables and chandeliers at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-dbad5720.jpg', 'category'=>'Dining', 'caption'=>'Dinner on the deck',         'alt'=>'Covered terrace laid for dinner under string lights at dusk at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-fde26836.jpg', 'category'=>'Dining', 'caption'=>'The dining room',            'alt'=>'Bright dining room with windows on three sides at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261017-53384474.jpg',     'category'=>'Dining', 'caption'=>'The restaurant at night',     'alt'=>'Glass-fronted restaurant lit up in the evening at The English Country House, Mussoorie'],
    // Gardens & Grounds
    ['src'=>'uploads/amenities/261017-86553574.jpg', 'category'=>'Gardens & Grounds', 'caption'=>'Fire pit at blue hour',   'alt'=>'Stone fire pit with chairs overlooking the hills at dusk at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-02f9dac1.jpg',     'category'=>'Gardens & Grounds', 'caption'=>'The flower garden',       'alt'=>'Beds of pink and purple flowers in front of the house at The English Country House, Mussoorie'],
    ['src'=>'assets/img/redesign/blog/farm.jpg',     'category'=>'Gardens & Grounds', 'caption'=>'The Polly House greenhouse', 'alt'=>'Chef picking fresh greens in the greenhouse at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-36937b2d.jpg', 'category'=>'Gardens & Grounds', 'caption'=>'Garden corner',           'alt'=>'Stone wall, white picket gate and potted plants at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-8d14263b.jpg', 'category'=>'Gardens & Grounds', 'caption'=>'Play lawn',               'alt'=>'Lawn with a trampoline and a sports court framed by trees at The English Country House, Mussoorie'],
    // Weddings & Events
    ['src'=>'uploads/gallery/261017-d4437b24.jpeg',  'category'=>'Weddings & Events', 'caption'=>'Birthday on the terrace', 'alt'=>'Terrace decorated with balloons and a Happy Birthday banner at The English Country House, Mussoorie'],
    ['src'=>'uploads/gallery/261017-5601c426.jpg',   'category'=>'Weddings & Events', 'caption'=>'Celebrations in the garden', 'alt'=>'Couple posing by a decorated swing with a mountain view at The English Country House, Mussoorie'],
    // Spa & Wellness
    ['src'=>'uploads/amenities/261017-43b810f0.jpg', 'category'=>'Spa & Wellness', 'caption'=>'Jacuzzi under the stars', 'alt'=>'Outdoor jacuzzi at night above the lights of the valley at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-4e902c38.jpeg','category'=>'Spa & Wellness', 'caption'=>'Sauna & steam bath',      'alt'=>'Wooden sauna cabin with a glass door at The English Country House, Mussoorie'],
    ['src'=>'uploads/amenities/261017-d48b9788.jpg', 'category'=>'Spa & Wellness', 'caption'=>'Yoga house',              'alt'=>'Guests doing yoga in a sunlit room with an arched door at The English Country House, Mussoorie'],
    // Nearby
    ['src'=>'uploads/pages/261018-433da7cf.jpg', 'category'=>'Nearby', 'caption'=>'Waterfall near Mussoorie', 'alt'=>'Waterfall dropping into a green pool at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-1c359940.jpg', 'category'=>'Nearby', 'caption'=>'A waterfall in the hills', 'alt'=>'Tall waterfall between rocky cliffs at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-5d2bc24e.jpg', 'category'=>'Nearby', 'caption'=>'Forested valleys', 'alt'=>'Forested valley under a clear blue sky at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-40df80bc.png', 'category'=>'Nearby', 'caption'=>'Hilltop viewpoint café','alt'=>'Hilltop viewpoint café with a flag and pine trees at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-23f16c15.jpg', 'category'=>'Nearby', 'caption'=>'Among the deodars','alt'=>'Rocky outcrop among deodar trees at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-b9e96083.jpg', 'category'=>'Nearby', 'caption'=>'Hidden falls',            'alt'=>'Water rushing through a rocky gorge at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-c2d0ef69.jpg', 'category'=>'Nearby', 'caption'=>'Boating lake','alt'=>'Boating lake surrounded by trees at The English Country House, Mussoorie'],
    ['src'=>'uploads/pages/261018-dc1da0b9.jpg', 'category'=>'Nearby', 'caption'=>'Ridge-top ruins','alt'=>'Hilltop ruins on a green ridge under clouds at The English Country House, Mussoorie'],
];
