<?php
/** Visitor live-chat API (bot + routing + polling) */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
sess_start(); db();
header('Content-Type: application/json; charset=utf-8');

if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) json_out(['ok'=>false,'error'=>'bad token'], 403);
if (setting('chat_enabled') !== '1') json_out(['ok'=>false,'error'=>'chat disabled']);

$action = post('action');

/* ---------- helpers ---------- */
function chat_session(): array {
    global $action;
    $key = $_COOKIE['ech_chat'] ?? '';
    $chat = $key ? q_row("SELECT * FROM visitor_chats WHERE chat_key=? ORDER BY id DESC LIMIT 1", [$key]) : null;
    if ($chat && !in_array($chat['status'], ['open','active']) && in_array($action, ['send','choose','info'])) $chat = null;
    if (!$chat) {
        $key = bin2hex(random_bytes(16));
        setcookie('ech_chat', $key, time()+86400*3, '/');
        $id = db_insert('visitor_chats', ['chat_key'=>$key,'visitor_key'=>$_COOKIE['ech_vk'] ?? '','page'=>substr(post('page','/'),0,250),'ip'=>client_ip(),'mode'=>'bot','bot_step'=>'greet','status'=>'open']);
        $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [$id]);
        chat_msg($id, 'bot', setting('chat_greeting','Hello! How can I help you today?'));
    }
    return $chat;
}
function chat_msg(int $chatId, string $sender, string $text, ?int $senderId = null): int {
    return db_insert('visitor_chat_messages', ['chat_id'=>$chatId,'sender'=>$sender,'sender_id'=>$senderId,'message'=>$text,
        'seen_by_visitor'=>0, 'seen_by_agent'=>$sender==='visitor'?0:1]);
}
function agents_online(): array {
    $idle = (int)setting('chat_idle_seconds', 90);
    return q_all("SELECT id,name,chat_max FROM admins WHERE active=1 AND chat_available=1 AND last_seen > DATE_SUB(NOW(), INTERVAL ? SECOND)", [$idle]);
}
function pick_agent(int $exclude = 0): ?array {
    $best = null; $bestLoad = PHP_INT_MAX; $bestIdle = 0;
    foreach (agents_online() as $a) {
        if ((int)$a['id'] === $exclude) continue;
        $load = (int)q_val("SELECT COUNT(*) FROM visitor_chats WHERE agent_id=? AND status='active'", [(int)$a['id']], 0);
        if ($load >= max(1,(int)$a['chat_max'])) continue;
        $idleSecs = (int)q_val("SELECT COALESCE(TIMESTAMPDIFF(SECOND, MAX(last_agent_msg_at), NOW()), 999999) FROM visitor_chats WHERE agent_id=?", [(int)$a['id']], 999999);
        if ($load < $bestLoad || ($load === $bestLoad && $idleSecs > $bestIdle)) { $best = $a; $bestLoad = $load; $bestIdle = $idleSecs; }
    }
    return $best;
}
function assign_agent(array &$chat, int $exclude = 0): bool {
    $a = pick_agent($exclude);
    if (!$a) return false;
    db_update('visitor_chats', ['agent_id'=>(int)$a['id'],'mode'=>'agent','status'=>'active','assigned_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$chat['id']]);
    chat_msg((int)$chat['id'], 'system', ($exclude ? 'Transferring you to '.$a['name'].'…' : 'Connecting you to '.$a['name'].'… please hold on.'));
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    return true;
}
function chat_lead(array $chat): void {
    if ($chat['lead_id'] || (!$chat['phone'] && !$chat['email'])) return;
    $lid = create_lead(['name'=>$chat['name'],'phone'=>$chat['phone'],'email'=>$chat['email'],'source'=>'chat',
        'message'=>'Started live chat on page '.$chat['page'], 'meta'=>['chat_id'=>$chat['id']]]);
    db_update('visitor_chats', ['lead_id'=>$lid], 'id=?', [(int)$chat['id']]);
}
/** Round 5: room prices are not shown publicly. A saved answer that quotes a nightly rate (e.g. "₹8,500/night") is
 *  replaced by an invitation to enquire; the answer stored in Admin → Chatbot is unchanged. */
function rd_no_room_price(string $answer): string {
    if (!preg_match('~(₹|rs\.?|inr|\?)\s*\d[\d,]*(\.\d+)?\s*(/|per)\s*night~iu', $answer)) return $answer;
    return 'Rates depend on your dates, room and number of guests. Tap "Enquire Now" (or share your dates here) and our team will send you availability and pricing on WhatsApp.';
}
function bot_answer(string $text): ?string {
    $t = mb_strtolower($text);
    foreach (q_all("SELECT * FROM chatbot_qa WHERE active=1 ORDER BY sort") as $qa) {
        foreach (array_map('trim', explode(',', mb_strtolower($qa['keywords']))) as $kw)
            if ($kw !== '' && str_contains($t, $kw)) return rd_no_room_price($qa['answer']);
    }
    return null;
}
function detect_phone_lead(array $chat, string $text): void {
    if (preg_match('/(?:\+?91[\s-]?)?([6-9]\d{9})\b/', preg_replace('/[\s-]/','',$text), $m)) {
        $phone = $m[1];
        if (!$chat['phone']) db_update('visitor_chats', ['phone'=>$phone], 'id=?', [(int)$chat['id']]);
        if (!$chat['lead_id']) {
            $lid = create_lead(['name'=>$chat['name'] ?: 'Chat Visitor','phone'=>$phone,'source'=>'chat','message'=>'Phone shared in chat: '.mb_substr($text,0,300)]);
            db_update('visitor_chats', ['lead_id'=>$lid], 'id=?', [(int)$chat['id']]);
        }
    }
}
function chat_options(array $chat): array {
    $n = count(agents_online());
    $wa = wa_link('', 'Hi! I was chatting on your website and would like assistance.');
    if ($chat['bot_step'] === 'choice') {
        $opts = [];
        if ($n > 0) $opts[] = ['label'=>"💬 Chat with our live Agent ($n available now)", 'action'=>'choose', 'value'=>'agent'];
        $opts[] = ['label'=>'🟢 Continue on WhatsApp', 'action'=>'link', 'url'=>$wa];
        if ($n === 0) $opts[] = ['label'=>'📞 Request a callback', 'action'=>'choose', 'value'=>'callback'];
        return $opts;
    }
    if ($chat['bot_step'] === 'no_agents') {
        return [['label'=>'🟢 Chat on WhatsApp','action'=>'link','url'=>$wa], ['label'=>'📞 Request a callback','action'=>'choose','value'=>'callback']];
    }
    return [];
}
function state_payload(array $chat, int $sinceId = 0, array $extra = []): array {
    $msgs = q_all("SELECT m.*, a.name agent_name FROM visitor_chat_messages m LEFT JOIN admins a ON a.id=m.sender_id AND m.sender='agent' WHERE m.chat_id=? AND m.id>? ORDER BY m.id", [(int)$chat['id'], $sinceId]);
    q("UPDATE visitor_chat_messages SET seen_by_visitor=1 WHERE chat_id=? AND sender IN ('agent','bot','system')", [(int)$chat['id']]);
    $agent = $chat['agent_id'] ? q_row("SELECT name FROM admins WHERE id=?", [(int)$chat['agent_id']]) : null;
    return array_merge([
        'ok'=>true, 'mode'=>$chat['mode'], 'status'=>$chat['status'], 'agents_online'=>count(agents_online()),
        'agent_name'=>$agent['name'] ?? null,
        'need_contact'=>$chat['bot_step'] === 'contact' && !$chat['phone'],
        'options'=>in_array($chat['status'],['open']) ? chat_options($chat) : [],
        'messages'=>array_map(fn($m)=>['id'=>(int)$m['id'],'sender'=>$m['sender'],'message'=>$m['message'],'agent_name'=>$m['agent_name'] ?? null,'time'=>date('h:i A', strtotime($m['created_at']))], $msgs),
    ], $extra);
}

/* ---------- watchdog: agent unresponsive → reassign / whatsapp ---------- */
function chat_watchdog(array &$chat): void {
    if ($chat['mode'] !== 'agent' || $chat['status'] !== 'active') return;
    $wait = (int)setting('chat_wait_seconds', 45);
    if ($chat['last_agent_msg_at'] === null && $chat['assigned_at'] && (time() - strtotime($chat['assigned_at'])) > $wait) {
        $prev = (int)$chat['agent_id'];
        $attempts = (int)q_val("SELECT COUNT(*) FROM visitor_chat_messages WHERE chat_id=? AND sender='system' AND (message LIKE 'Connecting you to%' OR message LIKE 'Transferring you to%')", [(int)$chat['id']], 0);
        $exhausted = $attempts >= max(2, count(agents_online()));
        if ($exhausted || !assign_agent($chat, $prev)) {
            db_update('visitor_chats', ['mode'=>'bot','bot_step'=>'no_agents','status'=>'open','agent_id'=>null], 'id=?', [(int)$chat['id']]);
            chat_msg((int)$chat['id'], 'bot', "I'm sorry — all our team members are busy right now. 🙏 You can continue instantly on WhatsApp, or request a callback and we'll ring you shortly.");
            $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
        }
    }
}

/* ================= actions ================= */
$chat = chat_session();

switch ($action) {

case 'start':
    chat_watchdog($chat);
    json_out(state_payload($chat));

case 'poll':
    chat_watchdog($chat);
    json_out(state_payload($chat, (int)post('last_id', 0)));

case 'send':
    $text = mb_substr(trim(post('message')), 0, 1000);
    if ($text === '') json_out(['ok'=>false]);
    chat_msg((int)$chat['id'], 'visitor', $text);
    db_update('visitor_chats', ['last_visitor_msg_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$chat['id']]);
    detect_phone_lead($chat, $text);
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    $delay = 0;

    if ($chat['mode'] === 'bot' && $chat['status'] === 'open') {
        $delay = 800;
        $ans = bot_answer($text);
        if ($chat['bot_step'] === 'greet') {
            chat_msg((int)$chat['id'], 'bot', $ans ?: "Great to hear from you! 😊");
            $n = count(agents_online());
            if ($n > 0) {
                chat_msg((int)$chat['id'], 'bot', "Would you like to talk with our live agent, or continue on WhatsApp?");
                db_update('visitor_chats', ['bot_step'=>'choice'], 'id=?', [(int)$chat['id']]);
            } else {
                chat_msg((int)$chat['id'], 'bot', setting('chat_offline_msg'));
                db_update('visitor_chats', ['bot_step'=>'no_agents'], 'id=?', [(int)$chat['id']]);
            }
        } elseif (in_array($chat['bot_step'], ['choice','no_agents'])) {
            if ($ans) chat_msg((int)$chat['id'], 'bot', $ans);
            else chat_msg((int)$chat['id'], 'bot', "Please choose an option below so I can route you to the right place 👇");
        }
        $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    }
    chat_watchdog($chat);
    json_out(state_payload($chat, (int)q_val("SELECT MAX(id) FROM visitor_chat_messages WHERE chat_id=? AND sender='visitor'", [(int)$chat['id']], 0), ['bot_delay'=>$delay]));

case 'choose':
    $choice = post('choice');
    if ($choice === 'agent') {
        db_update('visitor_chats', ['bot_step'=>'contact'], 'id=?', [(int)$chat['id']]);
        $chat['bot_step'] = 'contact';
        if ($chat['phone']) {
            if (!assign_agent($chat)) {
                db_update('visitor_chats', ['bot_step'=>'no_agents'], 'id=?', [(int)$chat['id']]);
                chat_msg((int)$chat['id'], 'bot', setting('chat_offline_msg'));
            }
        }
    } elseif ($choice === 'whatsapp') {
        db_update('visitor_chats', ['mode'=>'whatsapp','status'=>'whatsapp','closed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$chat['id']]);
        chat_msg((int)$chat['id'], 'system', 'Visitor moved to WhatsApp.');
        chat_lead($chat);
    } elseif ($choice === 'callback') {
        db_update('visitor_chats', ['mode'=>'callback','bot_step'=>'contact'], 'id=?', [(int)$chat['id']]);
        chat_msg((int)$chat['id'], 'bot', 'Sure! Please share your name and mobile number and our team will call you back shortly.');
    }
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    json_out(state_payload($chat, (int)post('last_id', 0)));

case 'info':
    $name = mb_substr(trim(post('name')), 0, 150);
    $pc = phone_check(post('phone'));   // round 5: site-wide phone rule
    if ($name === '' || !$pc['ok']) json_out(['ok'=>false,'error'=>$name === '' ? 'Name and valid phone required' : $pc['error']]);
    $phone = $pc['e164'];
    db_update('visitor_chats', ['name'=>$name,'phone'=>$phone], 'id=?', [(int)$chat['id']]);
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    chat_lead($chat);
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    if ($chat['mode'] === 'callback') {
        chat_msg((int)$chat['id'], 'bot', "Thank you, $name! 📞 Our team will call you back on $phone shortly. Have a wonderful day!");
        db_update('visitor_chats', ['status'=>'closed','closed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$chat['id']]);
    } else {
        if (!assign_agent($chat)) {
            db_update('visitor_chats', ['bot_step'=>'no_agents'], 'id=?', [(int)$chat['id']]);
            chat_msg((int)$chat['id'], 'bot', setting('chat_offline_msg'));
        }
    }
    $chat = q_row("SELECT * FROM visitor_chats WHERE id=?", [(int)$chat['id']]);
    json_out(state_payload($chat, (int)post('last_id', 0)));

case 'rate':
    $r = max(1, min(5, (int)post('rating')));
    db_update('visitor_chats', ['rating'=>$r], 'id=?', [(int)$chat['id']]);
    json_out(['ok'=>true]);

default:
    json_out(['ok'=>false,'error'=>'unknown action'], 400);
}
