<?php
/** Admin AJAX endpoints (JSON) — currently: lead pipeline drag-and-drop stage change */
require_once __DIR__.'/../config.php';
db();
require_once __DIR__.'/../includes/auth.php';

$ADMIN = current_admin();
if (!$ADMIN) json_out(['ok'=>false,'error'=>'Not logged in'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok'=>false,'error'=>'POST only'], 405);
if (!hash_equals($_SESSION['csrf'] ?? '', post('csrf'))) json_out(['ok'=>false,'error'=>'Invalid session token — refresh the page'], 403);

$action = post('action');

if ($action === 'lead_stage') {
    if (!admin_can($ADMIN, 'leads')) json_out(['ok'=>false,'error'=>'No permission'], 403);
    $STAGES = ['new'=>'New','contacted'=>'Contacted','follow_up'=>'Follow Up','quoted'=>'Quoted','converted'=>'Converted','lost'=>'Lost'];
    $leadId = (int)post('lead_id');
    $stage  = post('stage');
    if (!$leadId || !isset($STAGES[$stage])) json_out(['ok'=>false,'error'=>'Bad request'], 400);
    $l = q_row("SELECT * FROM leads WHERE id=?", [$leadId]);
    if (!$l) json_out(['ok'=>false,'error'=>'Lead not found'], 404);
    if ($l['stage'] === $stage) json_out(['ok'=>true,'unchanged'=>true]);
    db_update('leads', ['stage'=>$stage], 'id=?', [$leadId]);
    db_insert('lead_activities', ['lead_id'=>$leadId,'admin_id'=>(int)$ADMIN['id'],'type'=>'stage',
        'content'=>'Stage: '.($STAGES[$l['stage']] ?? $l['stage']).' → '.$STAGES[$stage].' (drag & drop)']);
    log_activity((int)$ADMIN['id'], 'leads', 'stage', 'Lead #'.$leadId.' → '.$STAGES[$stage].' (pipeline drag)');
    json_out(['ok'=>true,'lead_id'=>$leadId,'stage'=>$stage,'stage_label'=>$STAGES[$stage]]);
}

/* ===================== Restaurant: live orders ===================== */
if (in_array($action, ['food_list','food_status','menu_sort','menu_toggle','menu_price'], true)) {
    if (!admin_can($ADMIN, 'menu')) json_out(['ok'=>false,'error'=>'No permission for Restaurant Menu'], 403);
    require_once ROOT_PATH.'/lib/food.php';
}

if ($action === 'food_list') {
    require_once ROOT_PATH.'/admin/menu_orders.php';
    $view = in_array(post('view'), ['active','delivered','cancelled','all'], true) ? post('view') : 'active';
    ob_start(); render_food_orders($view, (string)post('date')); $html = ob_get_clean();
    $open = (int)q_val("SELECT COUNT(*) FROM food_orders WHERE status IN ('new','accepted','preparing','ready')", [], 0);
    // signature changes whenever an order is added/updated or minutes tick over (ages, late flags)
    $sig = md5(q_val("SELECT CONCAT(COUNT(*),'-',COALESCE(MAX(updated_at),''),'-',COALESCE(MAX(id),0)) FROM food_orders", [], '').date('YmdHi'));
    json_out(['ok'=>true,'html'=>$html,'open'=>$open,'sig'=>$sig]);
}

if ($action === 'food_status') {
    $o = q_row("SELECT * FROM food_orders WHERE id=?", [(int)post('id')]);
    $st = post('status');
    if (!$o || !isset(FOOD_STATUSES[$st])) json_out(['ok'=>false,'error'=>'Bad request'], 400);
    $upd = ['status'=>$st, 'handled_by'=>(int)$ADMIN['id']];
    if ($st === 'accepted') { $upd['accepted_at'] = date('Y-m-d H:i:s'); if ((int)post('eta') > 0) $upd['eta_minutes'] = max(5, min(240, (int)post('eta'))); }
    if ($st === 'delivered') $upd['delivered_at'] = date('Y-m-d H:i:s');
    db_update('food_orders', $upd, 'id=?', [(int)$o['id']]);
    log_activity((int)$ADMIN['id'], 'menu', 'food_'.$st, $o['order_code'].' · room '.$o['room_no'].' · '.money($o['total']));
    json_out(['ok'=>true,'code'=>$o['order_code'],'status'=>$st,'label'=>FOOD_STATUSES[$st][0]]);
}

if ($action === 'menu_sort') {
    $table = post('type') === 'cat' ? 'menu_categories' : 'menu_items';
    $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    foreach ($ids as $i => $id) q("UPDATE $table SET sort=? WHERE id=?", [($i + 1) * 10, $id]);
    log_activity((int)$ADMIN['id'], 'menu', 'reorder', $table.' ('.count($ids).')');
    json_out(['ok'=>true]);
}

if ($action === 'menu_toggle') {
    $table = post('type') === 'cat' ? 'menu_categories' : 'menu_items';
    $field = post('field') === 'available' && $table === 'menu_items' ? 'available' : 'active';
    $id = (int)post('id');
    q("UPDATE $table SET $field=1-$field WHERE id=?", [$id]);
    $val = (int)q_val("SELECT $field FROM $table WHERE id=?", [$id], 0);
    log_activity((int)$ADMIN['id'], 'menu', 'toggle_'.$field, $table.' #'.$id.' → '.$val);
    json_out(['ok'=>true,'value'=>$val]);
}

if ($action === 'menu_price') {
    $id = (int)post('id'); $price = round(max(0, (float)post('price')), 2);
    $old = q_val("SELECT price FROM menu_items WHERE id=?", [$id]);
    if ($old === null) json_out(['ok'=>false,'error'=>'Dish not found'], 404);
    db_update('menu_items', ['price'=>$price], 'id=?', [$id]);
    log_activity((int)$ADMIN['id'], 'menu', 'price', q_val("SELECT name FROM menu_items WHERE id=?", [$id]).': '.money($old).' → '.money($price));
    json_out(['ok'=>true,'price'=>money($price)]);
}

/* ===================== Gallery: import images from the old website ===================== */
if (in_array($action, ['import_scan','import_fetch','import_assign','import_drive'], true)) {
    if (!admin_can($ADMIN, 'gallery')) json_out(['ok'=>false,'error'=>'No permission for Gallery'], 403);
    require_once ROOT_PATH.'/lib/importer.php';
    @set_time_limit(120);
}
if ($action === 'import_drive') {
    $r = import_drive_list((string)post('url'));
    log_activity((int)$ADMIN['id'], 'gallery', 'import_drive', post('url').' → '.count($r['images']).' images');
    json_out(['ok'=>true] + $r);
}
if ($action === 'import_scan') {
    $r = import_scan((string)post('url'), max(1, min(60, (int)post('pages', 25))));
    if (isset($r['error'])) json_out(['ok'=>false,'error'=>$r['error']]);
    log_activity((int)$ADMIN['id'], 'gallery', 'import_scan', post('url').' → '.count($r['images']).' images, '.$r['pages'].' pages');
    json_out(['ok'=>true] + $r);
}
if ($action === 'import_fetch') {
    $urls = array_slice((array)($_POST['urls'] ?? []), 0, 8);
    $res = [];
    foreach ($urls as $i => $u) {
        [$ok, $info, $row] = import_fetch_one((string)$u, (string)($_POST['fallbacks'][$i] ?? ''), (string)($_POST['alts'][$i] ?? ''),
                                             mb_substr((string)post('category', 'Old website'), 0, 100), max(50, (int)post('min_w', 250)));
        $res[] = ['url'=>$u, 'ok'=>$ok, 'info'=>$info, 'row'=>$row];
    }
    json_out(['ok'=>true,'results'=>$res]);
}
if ($action === 'import_assign') {
    [$ok, $msg] = import_assign((int)post('id'), (string)post('target'));
    if ($ok) log_activity((int)$ADMIN['id'], 'gallery', 'assign_image', 'gallery #'.(int)post('id').' → '.post('target'));
    json_out(['ok'=>$ok, 'message'=>$msg]);
}

/* ===================== Gallery picker (uploader component) ===================== */
if ($action === 'gallery_list') {
    $rows = q_all("SELECT id, image, title, category FROM gallery_images WHERE active=1 ORDER BY sort, id DESC LIMIT 400");
    json_out(['ok'=>true,'images'=>$rows]);
}

/* ===================== Live editor (edit sections / banner / intro on the page itself) ===================== */
/* ---- bulk media optimisation (Site Tools) ---- */
if ($action === 'media_optimize') {
    if (!admin_can($ADMIN, 'settings')) json_out(['ok'=>false,'error'=>'No permission'], 403);
    require_once ROOT_PATH.'/lib/media.php';
    @set_time_limit(120);
    $res = media_optimize_batch(max(0, (int)post('offset', 0)), 6, post('force') === '1');
    json_out(['ok'=>true] + $res);
}

if (in_array($action, ['le_form','le_save','le_op'], true)) {
    if (!admin_can($ADMIN, 'cms')) json_out(['ok'=>false,'error'=>'No permission to edit pages'], 403);
    require_once ROOT_PATH.'/admin/section_form.php';
    $pg = q_row("SELECT * FROM pages WHERE id=?", [(int)post('page')]);
    if (!$pg) json_out(['ok'=>false,'error'=>'Page not found'], 404);
    $secs = json_decode($pg['sections'] ?: '[]', true) ?: [];
    $kind = post('kind', 'section'); $idx = (int)post('idx');
}

if ($action === 'le_form') {
    ob_start();
    if ($kind === 'banner') {
        banner_fields_html($pg);
        $title = 'Page header / banner';
    } elseif ($kind === 'intro') {
        echo '<label>Intro content <small class="hint">(shown under the banner, above the sections)</small></label><textarea class="input wysiwyg" name="content" data-minh="260px">'.e($pg['content'] ?? '').'</textarea>';
        $title = 'Intro content';
    } elseif ($kind === 'new') {
        $type = post('type');
        if (!isset(section_types()[$type])) json_out(['ok'=>false,'error'=>'Unknown section type']);
        $s = section_blank($type);
        echo '<input type="hidden" name="s_visible" value="1">';
        section_fields_html($s, fn($f) => 's_'.$f, 'le');
        $title = 'New: '.section_types()[$type]['label'];
    } else {
        if (!isset($secs[$idx])) json_out(['ok'=>false,'error'=>'Section not found']);
        $s = $secs[$idx];
        echo '<label class="inline" style="margin-bottom:8px"><input type="checkbox" name="s_visible" value="1" '.(!empty($s['visible']) ? 'checked' : '').'> Visible on the website</label>';
        section_fields_html($s, fn($f) => 's_'.$f, 'le');
        $title = section_types()[$s['type']]['label'] ?? $s['type'];
    }
    section_datalists();
    json_out(['ok'=>true,'html'=>ob_get_clean(),'title'=>$title]);
}

if ($action === 'le_save') {
    if ($kind === 'banner') {
        db_update('pages', banner_from_post(), 'id=?', [(int)$pg['id']]);
        log_activity((int)$ADMIN['id'], 'cms', 'live_banner', $pg['title']);
        json_out(['ok'=>true]);
    }
    if ($kind === 'intro') {
        require_once ROOT_PATH.'/includes/page_render.php';
        db_update('pages', ['content'=>html_is_empty($_POST['content'] ?? '') ? '' : $_POST['content']], 'id=?', [(int)$pg['id']]);
        log_activity((int)$ADMIN['id'], 'cms', 'live_intro', $pg['title']);
        json_out(['ok'=>true]);
    }
    if ($kind === 'new') {
        $type = post('type');
        if (!isset(section_types()[$type])) json_out(['ok'=>false,'error'=>'Unknown section type']);
        $s = section_from_post(section_blank($type), null);
        $s['visible'] = 1;
        $at = max(0, min(count($secs), (int)post('at', count($secs))));
        array_splice($secs, $at, 0, [$s]);
        db_update('pages', ['sections'=>json_encode(array_values($secs), JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$pg['id']]);
        log_activity((int)$ADMIN['id'], 'cms', 'live_add', $pg['title'].' · '.$type);
        json_out(['ok'=>true,'idx'=>$at]);
    }
    if (!isset($secs[$idx])) json_out(['ok'=>false,'error'=>'Section not found']);
    $secs[$idx] = section_from_post($secs[$idx], null);
    db_update('pages', ['sections'=>json_encode(array_values($secs), JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$pg['id']]);
    log_activity((int)$ADMIN['id'], 'cms', 'live_section', $pg['title'].' · #'.($idx+1).' '.($secs[$idx]['type'] ?? ''));
    json_out(['ok'=>true,'idx'=>$idx]);
}

if ($action === 'le_op') {
    $op = post('op');
    if ($op === 'add') {   // quick add with defaults (no form)
        $type = post('type');
        if (!isset(section_types()[$type])) json_out(['ok'=>false,'error'=>'Unknown section type']);
        $at = max(0, min(count($secs), (int)post('at', count($secs))));
        array_splice($secs, $at, 0, [section_blank($type)]);
        $idx = $at;
    } else {
        if (!isset($secs[$idx])) json_out(['ok'=>false,'error'=>'Section not found']);
        if ($op === 'toggle') $secs[$idx]['visible'] = empty($secs[$idx]['visible']) ? 1 : 0;
        if ($op === 'up' && $idx > 0) { [$secs[$idx-1], $secs[$idx]] = [$secs[$idx], $secs[$idx-1]]; $idx--; }
        if ($op === 'down' && $idx < count($secs)-1) { [$secs[$idx+1], $secs[$idx]] = [$secs[$idx], $secs[$idx+1]]; $idx++; }
        if ($op === 'dup') { array_splice($secs, $idx+1, 0, [$secs[$idx]]); $idx++; }
        if ($op === 'del') { array_splice($secs, $idx, 1); $idx = max(0, $idx-1); }
    }
    db_update('pages', ['sections'=>json_encode(array_values($secs), JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$pg['id']]);
    log_activity((int)$ADMIN['id'], 'cms', 'live_'.$op, $pg['title'].' · #'.($idx+1));
    json_out(['ok'=>true,'idx'=>$idx,'count'=>count($secs)]);
}

json_out(['ok'=>false,'error'=>'Unknown action'], 400);
