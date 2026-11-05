<?php
/** Agent-side chat API (team/admin console) */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
header('Content-Type: application/json; charset=utf-8');
$A = current_admin();
if (!$A) json_out(['ok'=>false,'error'=>'auth'], 401);
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) json_out(['ok'=>false,'error'=>'csrf'], 403);

$isBoss = in_array($A['role'], ['superadmin','admin']);
$action = post('action');

function agent_suggestions(int $chatId, array $A): array {
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [$chatId]);
    $lastMsg = q_row("SELECT message FROM visitor_chat_messages WHERE chat_id=? AND sender='visitor' ORDER BY id DESC LIMIT 1", [$chatId]);
    $text = mb_strtolower($lastMsg['message'] ?? '');
    $fill = fn($s) => str_replace(['{name}','{agent}','{phone}'], [$chat['name'] ?: 'there', explode(' ',$A['name'])[0], $chat['phone'] ?: 'your number'], $s);
    $suggs = [];
    foreach (q_all("SELECT * FROM canned_replies WHERE active=1") as $cr) {
        foreach (array_map('trim', explode(',', mb_strtolower($cr['keywords']))) as $kw)
            if ($kw !== '' && $text !== '' && str_contains($text, $kw)) { $suggs[] = ['title'=>$cr['title'],'text'=>$fill($cr['content'])]; break; }
    }
    if (!$suggs) foreach (q_all("SELECT * FROM canned_replies WHERE active=1 ORDER BY id LIMIT 3") as $cr) $suggs[] = ['title'=>$cr['title'],'text'=>$fill($cr['content'])];
    foreach (q_all("SELECT * FROM chatbot_qa WHERE active=1") as $qa) {
        foreach (array_map('trim', explode(',', mb_strtolower($qa['keywords']))) as $kw)
            if ($kw !== '' && $text !== '' && str_contains($text, $kw)) { $suggs[] = ['title'=>'FAQ answer','text'=>$fill($qa['answer'])]; break; }
    }
    $tips = [];
    if (str_contains($text,'price') || str_contains($text,'rate') || str_contains($text,'cost') || str_contains($text,'tariff'))
        $tips[] = "💰 Pricing question — don't quote flat numbers. Ask for exact dates & guests first, then quote with the seasonal calendar and mention the current offer.";
    if (str_contains($text,'urgent') || str_contains($text,'today') || str_contains($text,'now') || str_contains($text,'immediately'))
        $tips[] = "⚡ Urgent visitor — offer a phone call right away and keep replies short.";
    if (str_contains($text,'complain') || str_contains($text,'bad') || str_contains($text,'refund') || str_contains($text,'cancel'))
        $tips[] = "⚠ Possible complaint/cancellation — stay empathetic, don't argue, collect booking ID, and escalate to admin if unresolved.";
    if (str_contains($text,'pet') || str_contains($text,'dog'))
        $tips[] = "🐾 Pets — only Elizabeth Lodge & Charlotte Tree Houses allow pets. Confirm pet type/size.";
    if (str_contains($text,'wedding') || str_contains($text,'event') || str_contains($text,'corporate') || str_contains($text,'party'))
        $tips[] = "🎉 Event lead (high value!) — collect date, headcount and budget, then schedule a callback with management.";
    if (!$tips) $tips[] = "💡 Greet by name, ask travel dates + guests early, and always end with a next step (payment link, callback or WhatsApp follow-up).";
    if (!$chat['phone']) $tips[] = "📱 No phone captured yet — politely ask for their number so we can follow up (it becomes a lead automatically).";
    return ['suggestions'=>array_slice($suggs,0,5), 'tips'=>$tips];
}

switch ($action) {

case 'toggle_avail':
    $new = (int)!((int)$A['chat_available']);
    db_update('admins', ['chat_available'=>$new,'last_seen'=>date('Y-m-d H:i:s')], 'id=?', [(int)$A['id']]);
    json_out(['ok'=>true,'available'=>$new]);

case 'alerts':
    $waiting = q_row("SELECT COUNT(*) c, COALESCE(MAX(id),0) mx FROM visitor_chats WHERE status='active' AND agent_id=? AND id IN (SELECT chat_id FROM visitor_chat_messages WHERE sender='visitor' AND seen_by_agent=0)", [(int)$A['id']]);
    $queue = q_row("SELECT COUNT(*) c, COALESCE(MAX(id),0) mx FROM visitor_chats WHERE status='open' AND bot_step='contact'");
    $fu = (int)q_val("SELECT COUNT(*) FROM lead_activities WHERE type='followup' AND done=0 AND due_at <= NOW()", [], 0);
    $food = admin_can($A, 'menu') ? q_row("SELECT COUNT(*) c, COALESCE(MAX(id),0) mx FROM food_orders WHERE status='new'") : ['c'=>0,'mx'=>0];
    json_out(['ok'=>true, 'waiting'=>(int)$waiting['c'] + (int)$queue['c'], 'latest_chat'=>max((int)$waiting['mx'], (int)$queue['mx']), 'followups'=>$fu,
              'food_new'=>(int)$food['c'], 'food_latest'=>(int)$food['mx'], 'food_repeat'=>setting('menu_alert_repeat','1') === '1']);

case 'list':
    $mineOnly = !$isBoss;
    $rows = q_all("SELECT vc.*, a.name agent_name,
        (SELECT message FROM visitor_chat_messages WHERE chat_id=vc.id ORDER BY id DESC LIMIT 1) last_msg,
        (SELECT COUNT(*) FROM visitor_chat_messages WHERE chat_id=vc.id AND sender='visitor' AND seen_by_agent=0) unread
        FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id
        WHERE vc.status IN ('open','active') ".($mineOnly ? "AND (vc.agent_id=? OR vc.agent_id IS NULL)" : "")."
        ORDER BY unread DESC, vc.id DESC LIMIT 60", $mineOnly ? [(int)$A['id']] : []);
    json_out(['ok'=>true,'chats'=>array_map(fn($c)=>[
        'id'=>(int)$c['id'],'name'=>$c['name'] ?: 'Visitor #'.$c['id'],'phone'=>$c['phone'],'status'=>$c['status'],'mode'=>$c['mode'],
        'agent'=>$c['agent_name'],'mine'=>(int)$c['agent_id'] === (int)$A['id'],'unread'=>(int)$c['unread'],
        'last'=>mb_substr((string)$c['last_msg'],0,60),'time'=>date('h:i A', strtotime($c['created_at']))], $rows)]);

case 'open':
    $id = (int)post('chat_id');
    $chat = q_row("SELECT vc.*, a.name agent_name FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id WHERE vc.id=?", [$id]);
    if (!$chat) json_out(['ok'=>false]);
    if (!$isBoss && $chat['agent_id'] && (int)$chat['agent_id'] !== (int)$A['id']) json_out(['ok'=>false,'error'=>'Assigned to another agent']);
    if (!$chat['agent_id'] && in_array($chat['status'], ['open']) && $chat['mode'] !== 'whatsapp') {
        db_update('visitor_chats', ['agent_id'=>(int)$A['id'],'mode'=>'agent','status'=>'active','assigned_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
        db_insert('visitor_chat_messages', ['chat_id'=>$id,'sender'=>'system','message'=>'Connecting you to '.$A['name'].'…','seen_by_agent'=>1]);
        $chat = q_row("SELECT vc.*, a.name agent_name FROM visitor_chats vc LEFT JOIN admins a ON a.id=vc.agent_id WHERE vc.id=?", [$id]);
    }
    q("UPDATE visitor_chat_messages SET seen_by_agent=1 WHERE chat_id=?", [$id]);
    $msgs = q_all("SELECT m.*, a.name agent_name FROM visitor_chat_messages m LEFT JOIN admins a ON a.id=m.sender_id AND m.sender='agent' WHERE m.chat_id=? AND m.id > ? ORDER BY m.id", [$id, (int)post('last_id',0)]);
    $extra = agent_suggestions($id, $A);
    $lead = $chat['lead_id'] ? q_row("SELECT id,stage FROM leads WHERE id=?", [(int)$chat['lead_id']]) : null;
    json_out(['ok'=>true,
      'chat'=>['id'=>(int)$chat['id'],'name'=>$chat['name'],'phone'=>$chat['phone'],'email'=>$chat['email'],'page'=>$chat['page'],
               'ip'=>$chat['ip'],'status'=>$chat['status'],'mode'=>$chat['mode'],'agent'=>$chat['agent_name'],'rating'=>$chat['rating'],
               'lead_id'=>$lead['id'] ?? null,'lead_stage'=>$lead['stage'] ?? null,'started'=>date('d M h:i A', strtotime($chat['created_at']))],
      'messages'=>array_map(fn($m)=>['id'=>(int)$m['id'],'sender'=>$m['sender'],'message'=>$m['message'],'agent_name'=>$m['agent_name'],'time'=>date('h:i A', strtotime($m['created_at']))], $msgs),
      'suggestions'=>$extra['suggestions'], 'tips'=>$extra['tips']]);

case 'reply':
    $id = (int)post('chat_id');
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [$id]);
    $text = mb_substr(trim(post('message')), 0, 2000);
    if (!$chat || $text === '') json_out(['ok'=>false]);
    if (!$isBoss && (int)$chat['agent_id'] !== (int)$A['id']) json_out(['ok'=>false,'error'=>'Not your chat']);
    db_insert('visitor_chat_messages', ['chat_id'=>$id,'sender'=>'agent','sender_id'=>(int)$A['id'],'message'=>$text,'seen_by_agent'=>1]);
    $upd = ['last_agent_msg_at'=>date('Y-m-d H:i:s'),'status'=>'active'];
    if ($chat['first_response_seconds'] === null && $chat['assigned_at'])
        $upd['first_response_seconds'] = max(0, time() - strtotime($chat['assigned_at']));
    db_update('visitor_chats', $upd, 'id=?', [$id]);
    db_update('admins', ['last_seen'=>date('Y-m-d H:i:s')], 'id=?', [(int)$A['id']]);
    json_out(['ok'=>true]);

case 'transfer':
    $id = (int)post('chat_id'); $to = (int)post('to_agent');
    $target = q_row("SELECT * FROM admins WHERE id=? AND active=1", [$to]);
    if (!$target) json_out(['ok'=>false,'error'=>'Agent not found']);
    db_update('visitor_chats', ['agent_id'=>$to,'assigned_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
    db_insert('visitor_chat_messages', ['chat_id'=>$id,'sender'=>'system','message'=>'Transferring you to '.$target['name'].'…','seen_by_agent'=>1]);
    json_out(['ok'=>true]);

case 'close':
    $id = (int)post('chat_id');
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [$id]);
    if (!$chat) json_out(['ok'=>false]);
    if (!$isBoss && (int)$chat['agent_id'] !== (int)$A['id']) json_out(['ok'=>false,'error'=>'Not your chat']);
    db_insert('visitor_chat_messages', ['chat_id'=>$id,'sender'=>'bot','message'=>'This chat has been closed. Thank you for contacting '.setting('site_name').'! 💛','seen_by_agent'=>1]);
    db_update('visitor_chats', ['status'=>'closed','closed_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
    json_out(['ok'=>true]);

case 'save_contact':
    $id = (int)post('chat_id');
    db_update('visitor_chats', ['name'=>post('name'),'phone'=>preg_replace('/[^0-9+]/','',post('phone')),'email'=>post('email')], 'id=?', [$id]);
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [$id]);
    if (!$chat['lead_id'] && ($chat['phone'] || $chat['email'])) {
        $lid = create_lead(['name'=>$chat['name'],'phone'=>$chat['phone'],'email'=>$chat['email'],'source'=>'chat','message'=>'Contact captured by agent '.$A['name'].' during live chat']);
        db_update('visitor_chats', ['lead_id'=>$lid], 'id=?', [$id]);
    }
    json_out(['ok'=>true]);

default:
    json_out(['ok'=>false,'error'=>'unknown'], 400);
}
