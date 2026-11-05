<?php
/**
 * ============================================================
 *  ROUND 19 — content corrections (database part)
 *  ------------------------------------------------------------
 *  Applied once by database update v19 (install/schema.php) and on a fresh install (end of install/seed.php).
 *  Safe to run more than once.
 *   - the tree houses are "Charlotte Tree House 1" and "Charlotte Tree House 2" in every text
 *   - check-in is at 2:00 PM (was 1:00 PM)
 *   - the breakfast FAQ says breakfast depends on the package, and is shown again
 * ============================================================
 */

function round19_apply(mysqli $db): void {
    $cols = [
        'pages'          => ['title', 'banner_title', 'banner_subtitle', 'content', 'sections', 'seo_title', 'seo_desc', 'seo_keywords'],
        'faqs'           => ['question', 'answer'],
        'chatbot_qa'     => ['answer'],
        'canned_replies' => ['content'],
        'blog_posts'     => ['title', 'excerpt', 'content', 'seo_title', 'seo_desc'],
        'gallery_images' => ['title'],
    ];
    // "Tree House 1" → "Charlotte Tree House 1" (also inside the JSON of page sections); "Charlotte Tree House 1" stays as it is
    $rename = fn(string $v) => preg_replace('/(?<!Charlotte )\bTree House ([12])\b/', 'Charlotte Tree House $1', $v);
    // check-in time written in text: "Check-in is at 1:00 PM", "Check-in time is 1:00 PM", "check-in from 1 PM" …
    $checkin = fn(string $v) => preg_replace('/(check[- ]?in\b[^.<]{0,30}?)\b1(:00)?(\s?)(PM|pm|p\.m\.)/i', '${1}2${2}${3}${4}', $v);
    foreach ($cols as $table => $fields) {
        try { $have = array_column($db->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC), 'Field'); } catch (Throwable $e) { continue; }
        $fields = array_values(array_intersect($fields, $have));
        if (!$fields) continue;
        $res = $db->query("SELECT id, `".implode('`, `', $fields)."` FROM `$table`");
        while ($r = $res->fetch_assoc()) {
            $set = [];
            foreach ($fields as $f) {
                $old = (string)$r[$f];
                if ($old === '') continue;
                $new = $checkin($rename($old));
                if ($new !== $old) $set[$f] = $new;
            }
            if (!$set) continue;
            $sql = "UPDATE `$table` SET ".implode(', ', array_map(fn($f) => "`$f`=?", array_keys($set)))." WHERE id=?";
            $vals = array_values($set); $vals[] = (int)$r['id'];
            $st = $db->prepare($sql); $st->bind_param(str_repeat('s', count($set)).'i', ...$vals); $st->execute();
        }
    }

    // check-in time setting (used by the hotel data, room guide, booking emails, invoices)
    $st = $db->prepare("REPLACE INTO settings (skey,svalue) VALUES ('checkin_time','14:00')"); $st->execute();

    // breakfast FAQ: depends on the package (round 18 switched the "complimentary" answer off)
    $q = 'Is breakfast included in the tariff?';
    $a = 'It depends on the package you book. Please ask us about the packages and what they include when you enquire.';
    $r = $db->query("SELECT id FROM faqs WHERE question LIKE '%breakfast%' ORDER BY id LIMIT 1")->fetch_row();
    if ($r) { $id = (int)$r[0]; $st = $db->prepare("UPDATE faqs SET question=?, answer=?, active=1 WHERE id=?"); $st->bind_param('ssi', $q, $a, $id); $st->execute(); }
    else {
        $sort = (int)($db->query("SELECT COALESCE(MAX(sort),0)+1 FROM faqs")->fetch_row()[0] ?? 1);
        $st = $db->prepare("INSERT INTO faqs (question, answer, category, sort, active) VALUES (?, ?, 'Dining', ?, 1)"); $st->bind_param('ssi', $q, $a, $sort); $st->execute();
    }
}
