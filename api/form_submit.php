<?php
/** Handles form-builder submissions, contact form and booking enquiries → leads + email */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
sess_start(); db();
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) json_out(['ok'=>false,'message'=>'Session expired — refresh the page.'], 403);

$fk = 'form_ts';
$_SESSION[$fk] = array_filter($_SESSION[$fk] ?? [], fn($t)=>$t > time()-300);
if (count($_SESSION[$fk]) >= 8) json_out(['ok'=>false,'message'=>'Too many submissions. Please wait a few minutes.']);
$_SESSION[$fk][] = time();

$formKey = post('form_id');
$page = substr(post('page','/'),0,250);

if (post('website_hp') !== '') json_out(['ok'=>true,'message'=>'Thank you!']);

if (setting('recaptcha_secret') && post('g-recaptcha-response')) {
    $r = http_request('https://www.google.com/recaptcha/api/siteverify','POST', ['secret'=>setting('recaptcha_secret'),'response'=>post('g-recaptcha-response'),'remoteip'=>client_ip()]);
    if (empty($r['json']['success'])) json_out(['ok'=>false,'message'=>'reCAPTCHA verification failed.']);
}

/* -------- special: booking enquiry -------- */
if ($formKey === 'inquiry') {
    $name = post('name'); $phone = post('phone');
    if ($name === '') json_out(['ok'=>false,'message'=>'Please fill your name and a valid mobile number.']);
    $pc = phone_check($phone);   // round 5: site-wide phone rule (includes/ech_contact.php)
    if (!$pc['ok']) json_out(['ok'=>false,'message'=>$pc['error']]);
    $phone = $pc['e164'];
    $roomId = (int)post('room_id');
    $room = null; foreach ($roomId ? public_rooms() : [] as $pr) if ((int)$pr['id'] === $roomId) $room = $pr;   // round 9: rooms from data/rooms.php
    $msg = 'Booking enquiry'.($room ? ' for '.$room['name'] : '').' | Check-in: '.post('checkin').' → '.post('checkout')
         .' | Adults: '.(int)post('adults').' Children: '.(int)post('children')
         .(post('message') ? ' | '.post('message') : '');
    $leadId = create_lead(['name'=>$name,'email'=>post('email'),'phone'=>$phone,'source'=>'inquiry','room_id'=>$roomId ?: null,'message'=>$msg,
        'meta'=>['checkin'=>post('checkin'),'checkout'=>post('checkout'),'adults'=>post('adults'),'children'=>post('children'),'page'=>$page]]);
    if (setting('booking_notify_email')) {
        send_email(setting('booking_notify_email'), '🔔 New Booking Enquiry — '.$name,
          '<p><b>New enquiry received</b> (Lead #'.$leadId.')</p><p>'.e($msg).'</p><p>📞 '.e($phone).' · ✉ '.e(post('email')).'</p>
           <p><a href="'.base_url('admin/index.php?m=leads&view='.$leadId).'">Open in Lead Manager →</a></p>', 'inquiry');
    }
    json_out(['ok'=>true,'message'=>'Thank you '.e($name).'! Your enquiry is received — our team will contact you shortly.']);
}

/* -------- round 18: WhatsApp enquiry ("Make an Enquiry" / Book Now) — the backup copy saved before the guest goes to WhatsApp -------- */
if ($formKey === 'wa_enquiry') {
    $name = mb_substr(post('name'), 0, 120); $phone = post('phone');
    if ($name === '') json_out(['ok'=>false,'message'=>'Please fill your name and a valid mobile number.']);
    $pc = phone_check($phone);
    if (!$pc['ok']) json_out(['ok'=>false,'message'=>$pc['error']]);
    $phone = $pc['e164'];
    $roomId = (int)post('room_id');
    $room = null; foreach ($roomId ? public_rooms() : [] as $pr) if ((int)$pr['id'] === $roomId) $room = $pr;
    $msg = 'WhatsApp enquiry'.($room ? ' for '.$room['name'] : '')
         .(post('checkin') ? ' | Check-in: '.post('checkin') : '').(post('checkout') ? ' | Check-out: '.post('checkout') : '')
         .(post('guests') ? ' | Guests: '.mb_substr(post('guests'), 0, 40) : '')
         .(post('message') ? ' | '.mb_substr(post('message'), 0, 2000) : '');
    $leadId = create_lead(['name'=>$name,'email'=>mb_substr(post('email'), 0, 190),'phone'=>$phone,'source'=>'whatsapp','room_id'=>$room ? $roomId : null,'message'=>$msg,
        'meta'=>['checkin'=>post('checkin'),'checkout'=>post('checkout'),'guests'=>post('guests'),'page'=>$page,'form'=>'WhatsApp enquiry']]);
    $to = setting('booking_notify_email') ?: setting('contact_email');
    if ($to) {
        send_email($to, '🔔 New WhatsApp Enquiry — '.$name,
          '<p><b>A guest filled in the enquiry form and was sent to WhatsApp</b> (Lead #'.$leadId.'). If no WhatsApp message arrives, contact them directly.</p><p>'.e($msg).'</p><p>📞 '.e($phone).(post('email') ? ' · ✉ '.e(post('email')) : '').'</p>
           <p><a href="'.base_url('admin/index.php?m=leads&view='.$leadId).'">Open in Lead Manager →</a></p>', 'inquiry');
    }
    json_out(['ok'=>true,'message'=>'Saved']);
}

/* -------- builder forms -------- */
$form = q_row("SELECT * FROM forms WHERE id=? AND active=1", [(int)$formKey]);
if (!$form) json_out(['ok'=>false,'message'=>'Form not found.']);
$fields = json_decode($form['fields'], true) ?: [];
$data = []; $missing = [];
foreach ($fields as $f) {
    $v = trim((string)post($f['name']));
    if (!empty($f['required']) && $v === '') $missing[] = $f['label'];
    $data[$f['label']] = mb_substr($v, 0, 2000);
    // round 5: phone fields (type tel, or named phone / telephone / mobile) follow the site-wide phone rule
    if ($v !== '' && (($f['type'] ?? '') === 'tel' || in_array($f['name'] ?? '', ['phone', 'telephone', 'mobile'], true))) {
        $pc = phone_check($v);
        if (!$pc['ok']) json_out(['ok'=>false,'message'=>$f['label'].': '.$pc['error']]);
    }
}
if ($missing) json_out(['ok'=>false,'message'=>'Please fill: '.implode(', ', $missing)]);

$leadId = null;
if ((int)$form['create_lead'] === 1) {
    $name = trim((post('name') ?: post('first_name').' '.post('last_name')));
    $leadId = create_lead(['name'=>$name ?: 'Website Visitor','email'=>post('email'),'phone'=>post('phone') ?: post('telephone'),
        'source'=>'contact','message'=>post('message') ?: json_encode($data, JSON_UNESCAPED_UNICODE),'meta'=>['form'=>$form['name'],'page'=>$page]]);
}
db_insert('form_submissions', ['form_id'=>(int)$form['id'],'data'=>json_encode($data, JSON_UNESCAPED_UNICODE),'page'=>$page,'ip'=>client_ip(),'lead_id'=>$leadId]);

if ($form['email_to']) {
    $rows = '';
    foreach ($data as $k=>$v) $rows .= '<tr><td style="padding:6px 10px;border:1px solid #eee;background:#faf7f2"><b>'.e($k).'</b></td><td style="padding:6px 10px;border:1px solid #eee">'.nl2br(e($v)).'</td></tr>';
    send_email($form['email_to'], '📩 '.$form['name'].' — new submission', '<table style="width:100%;border-collapse:collapse;font-size:14px">'.$rows.'</table><p>Page: '.e($page).' · IP: '.e(client_ip()).'</p>', 'form');
}
json_out(['ok'=>true,'message'=>$form['success_msg']]);
