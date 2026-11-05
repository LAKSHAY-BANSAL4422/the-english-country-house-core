<?php
/** Live price quote for the booking page */
require_once __DIR__.'/../config.php';
db();
$roomId = (int)get('room_id');
$q = quote_stay($roomId, get('checkin'), get('checkout'), max(1,(int)get('adults',2)), max(0,(int)get('children',0)));
if (isset($q['error'])) json_out(['ok'=>false,'error'=>$q['error']]);
json_out([
  'ok'=>true,
  'lines'=>array_map(fn($l)=>['date'=>date('D, d M', strtotime($l['date'])),'rule'=>$l['rule'],'total_f'=>money($l['total'])], $q['lines']),
  'subtotal_f'=>money($q['subtotal']),
  'tax_percent'=>$q['tax_percent'], 'tax_label'=>setting('tax_label','Tax'), 'tax_f'=>money($q['tax']),
  'total'=>$q['total'], 'total_f'=>money($q['total']),
  'nights'=>$q['nights'],
  'available'=>room_available($roomId, get('checkin'), get('checkout')),
]);
