<?php
/**
 * "Import images from my old website" — runs on your own hosting.
 * 1) WordPress media library via /wp-json/wp/v2/media (gets *every* uploaded image)
 * 2) crawls same-site pages and collects <img>, srcset, lazy-load attributes, CSS backgrounds, og:image
 * 3) downloads in small batches into uploads/gallery/imported and adds them to the Gallery
 */

const IMPORT_EXT = ['jpg','jpeg','png','webp','gif'];

/** Block internal/private addresses (SSRF guard). Allowed in APP_DEBUG for local testing. */
function import_url_ok(string $url): bool {
    $p = parse_url($url);
    if (!$p || !in_array(strtolower($p['scheme'] ?? ''), ['http','https'], true) || empty($p['host'])) return false;
    if (APP_DEBUG) return true;
    $ips = filter_var($p['host'], FILTER_VALIDATE_IP) ? [$p['host']] : (gethostbynamel($p['host']) ?: []);
    if (!$ips) return false;
    foreach ($ips as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    return true;
}
/** GET with manual, re-validated redirects and a size cap. Returns [code, body, final_url, content_type]. */
function import_get(string $url, int $maxBytes = 4194304, int $timeout = 20): array {
    for ($hop = 0; $hop < 4; $hop++) {
        if (!import_url_ok($url)) return [0, '', $url, ''];
        $ch = curl_init($url);
        $body = ''; $tooBig = false;
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>false, CURLOPT_HEADER=>false, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>$timeout, CURLOPT_CONNECTTIMEOUT=>8,
            CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; ECH-Importer/1.0; +'.base_url('').')', CURLOPT_ENCODING=>'',
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION=>function ($ch, $chunk) use (&$body, &$tooBig, $maxBytes) {
                $body .= $chunk; if (strlen($body) > $maxBytes) { $tooBig = true; return 0; } return strlen($chunk);
            },
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $loc = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($tooBig) return [413, '', $url, $ctype];
        if ($code >= 300 && $code < 400 && $loc !== '') { $url = import_abs($loc, $url); continue; }
        return [$code, $body, $url, $ctype];
    }
    return [310, '', $url, ''];
}
/** Resolve a (possibly relative) URL against a base URL */
function import_abs(string $href, string $base): string {
    $href = trim(html_entity_decode($href, ENT_QUOTES));
    if ($href === '' || str_starts_with($href, 'data:') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) return '';
    if (preg_match('#^https?://#i', $href)) return $href;
    $b = parse_url($base);
    $origin = $b['scheme'].'://'.$b['host'].(isset($b['port']) ? ':'.$b['port'] : '');
    if (str_starts_with($href, '//')) return $b['scheme'].':'.$href;
    if (str_starts_with($href, '/')) return $origin.$href;
    if (str_starts_with($href, '#') || str_starts_with($href, '?')) return $origin.($b['path'] ?? '/').(str_starts_with($href, '?') ? $href : '');
    $dir = preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/');
    $path = $dir.$href;
    $parts = [];
    foreach (explode('/', $path) as $seg) { if ($seg === '..') array_pop($parts); elseif ($seg !== '.') $parts[] = $seg; }
    return $origin.implode('/', $parts);
}
function import_is_image_url(string $u): bool {
    $path = strtolower(parse_url($u, PHP_URL_PATH) ?? '');
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    return in_array($ext, IMPORT_EXT, true);
}
/** WordPress "-300x200.jpg" thumbnail → original */
function import_wp_original(string $u): string {
    return preg_replace('#-\d{2,4}x\d{2,4}(\.(?:jpe?g|png|webp|gif))(\?.*)?$#i', '$1', $u);
}
/** Extract images + same-site links from one HTML page */
function import_parse_html(string $html, string $pageUrl): array {
    $imgs = []; $links = [];
    $add = function (string $u, string $alt = '') use (&$imgs, $pageUrl) {
        $abs = import_abs($u, $pageUrl);
        if ($abs !== '' && import_is_image_url($abs)) $imgs[$abs] = ($imgs[$abs] ?? '') !== '' ? $imgs[$abs] : $alt;
    };
    // <img> & <source> (src, lazy variants, srcset → largest)
    if (preg_match_all('#<(?:img|source)\b[^>]*>#is', $html, $tags)) {
        foreach ($tags[0] as $t) {
            $alt = preg_match('#\balt\s*=\s*["\']([^"\']*)#i', $t, $m) ? trim($m[1]) : '';
            foreach (['src','data-src','data-lazy-src','data-original','data-bg','data-large_image','data-orig-file','data-full-url'] as $a)
                if (preg_match('#\b'.preg_quote($a, '#').'\s*=\s*["\']([^"\']+)#i', $t, $m)) $add($m[1], $alt);
            foreach (['srcset','data-srcset','data-lazy-srcset'] as $a) {
                if (!preg_match('#\b'.$a.'\s*=\s*["\']([^"\']+)#i', $t, $m)) continue;
                $best = ''; $bw = -1;
                foreach (explode(',', $m[1]) as $cand) {
                    $bits = preg_split('/\s+/', trim($cand));
                    $w = isset($bits[1]) ? (int)$bits[1] : 0;
                    if (!empty($bits[0]) && $w >= $bw) { $bw = $w; $best = $bits[0]; }
                }
                if ($best) $add($best, $alt);
            }
        }
    }
    // CSS backgrounds (inline styles + <style> blocks) and og/twitter images
    if (preg_match_all('#url\(\s*["\']?([^"\')]+)["\']?\s*\)#i', $html, $m)) foreach ($m[1] as $u) $add($u);
    if (preg_match_all('#<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\'][^>]*content=["\']([^"\']+)#i', $html, $m)) foreach ($m[1] as $u) $add($u);
    if (preg_match_all('#<a\b[^>]*href=["\']([^"\']+)#i', $html, $m)) {
        // links straight to image files (lightbox galleries) count as images
        foreach ($m[1] as $h) { $abs = import_abs($h, $pageUrl); if ($abs === '') continue; if (import_is_image_url($abs)) $add($abs); else $links[] = $abs; }
    }
    return [$imgs, $links];
}
/** Scan a website. Returns ['images'=>[[url, alt, fallback]], 'pages'=>n, 'wp'=>n, 'errors'=>[]] */
function import_scan(string $start, int $maxPages = 25): array {
    $start = trim($start);
    if (!preg_match('#^https?://#i', $start)) $start = 'https://'.$start;
    if (!import_url_ok($start)) return ['error'=>'That address is not allowed (must be a public http/https website).'];
    $sp = parse_url($start);
    $host = preg_replace('/^www\./', '', strtolower($sp['host']));
    $origin = $sp['scheme'].'://'.$sp['host'].(isset($sp['port']) ? ':'.$sp['port'] : '');
    $found = []; $errors = []; $wpCount = 0;

    // 1) WordPress media library
    for ($page = 1; $page <= 10; $page++) {
        [$code, $body] = import_get($origin.'/wp-json/wp/v2/media?per_page=100&media_type=image&page='.$page, 6291456, 25);
        $list = $code === 200 ? json_decode($body, true) : null;
        if (!is_array($list) || !$list) break;
        foreach ($list as $m) {
            $u = $m['source_url'] ?? ($m['guid']['rendered'] ?? '');
            if ($u && import_is_image_url($u)) { $found[$u] = ['alt'=>trim(strip_tags($m['alt_text'] ?? '') ?: strip_tags($m['title']['rendered'] ?? '')), 'fallback'=>'']; $wpCount++; }
        }
        if (count($list) < 100) break;
    }

    // 2) crawl pages on the same site
    $queue = [$start]; $seen = []; $pages = 0;
    while ($queue && $pages < $maxPages) {
        $url = array_shift($queue);
        $key = preg_replace('/#.*$/', '', $url);
        if (isset($seen[$key])) continue;
        $seen[$key] = 1;
        [$code, $body, $final, $ctype] = import_get($url, 3145728, 20);
        if ($code !== 200 || !str_contains(strtolower($ctype), 'html')) { if ($pages === 0) $errors[] = "Could not open $url (HTTP $code)"; continue; }
        $pages++;
        [$imgs, $links] = import_parse_html($body, $final);
        foreach ($imgs as $u => $alt) {
            $orig = import_wp_original($u);
            if (!isset($found[$orig])) $found[$orig] = ['alt'=>$alt, 'fallback'=>$orig !== $u ? $u : ''];
        }
        foreach ($links as $l) {
            $lp = parse_url($l);
            if (empty($lp['host']) || preg_replace('/^www\./', '', strtolower($lp['host'])) !== $host) continue;
            $path = strtolower($lp['path'] ?? '/');
            if (preg_match('#/(wp-admin|wp-login|feed|xmlrpc|cart|checkout|my-account)|\.(pdf|zip|docx?|xlsx?|mp4|mp3)$#', $path)) continue;
            $clean = $lp['scheme'].'://'.$lp['host'].(isset($lp['port']) ? ':'.$lp['port'] : '').($lp['path'] ?? '/');
            if (!isset($seen[$clean]) && count($queue) < 200) $queue[] = $clean;
        }
    }
    // drop obvious junk (icons, spinners, emoji, tracking pixels)
    $out = [];
    foreach ($found as $u => $meta) {
        if (preg_match('#(favicon|sprite|spinner|loader|loading|pixel|emoji|/icons?/|gravatar|placeholder|blank\.gif)#i', $u)) continue;
        $out[] = ['url'=>$u, 'alt'=>mb_substr((string)$meta['alt'], 0, 180), 'fallback'=>$meta['fallback'], 'have'=>(bool)q_val("SELECT id FROM gallery_images WHERE source_url=?", [$u])];
        if (count($out) >= 500) break;
    }
    return ['images'=>$out, 'pages'=>$pages, 'wp'=>$wpCount, 'errors'=>$errors];
}
/* ============================ Google Drive (shared folder → gallery) ============================ */

/** Folder / file id from any Google Drive link */
function import_drive_id(string $url): array {
    $url = trim($url);
    if (preg_match('~drive\.google\.com/drive/(?:u/\d+/)?folders/([A-Za-z0-9_-]{10,})~', $url, $m)) return ['folder', $m[1]];
    if (preg_match('~[?&]id=([A-Za-z0-9_-]{10,})~', $url, $m)) return [str_contains($url, 'folder') ? 'folder' : 'file', $m[1]];
    if (preg_match('~drive\.google\.com/file/d/([A-Za-z0-9_-]{10,})~', $url, $m)) return ['file', $m[1]];
    if (preg_match('~^[A-Za-z0-9_-]{20,}$~', $url)) return ['folder', $url];
    return ['', ''];
}
function import_drive_download_url(string $id): string { return 'https://drive.google.com/uc?export=download&id='.$id; }

/**
 * List the images of a PUBLIC shared folder ("Anyone with the link → Viewer").
 * Uses the Drive API when an API key is saved (Settings → Google APIs), otherwise the public embedded folder view.
 * Returns ['images'=>[[url, alt, fallback, have, thumb]], 'folders'=>[[id,name]], 'errors'=>[], 'name'=>folder name]
 */
function import_drive_list(string $link): array {
    [$kind, $id] = import_drive_id($link);
    $out = ['images'=>[], 'folders'=>[], 'errors'=>[], 'name'=>''];
    if ($id === '') { $out['errors'][] = 'That does not look like a Google Drive link. Copy the folder link from Drive → Share → Copy link.'; return $out; }
    $mk = function (string $fid, string $name) {
        $u = import_drive_download_url($fid);
        return ['url'=>$u, 'alt'=>mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 180), 'fallback'=>'https://drive.google.com/thumbnail?id='.$fid.'&sz=w2000', 'thumb'=>'https://drive.google.com/thumbnail?id='.$fid.'&sz=w400',
                'have'=>(bool)q_val("SELECT id FROM gallery_images WHERE source_url=?", [$u]), 'name'=>$name];
    };
    if ($kind === 'file') { $out['images'][] = $mk($id, 'drive-image-'.$id.'.jpg'); return $out; }
    $key = trim((string)setting('google_drive_api_key'));
    if ($key !== '') {   // Drive API v3 (public folders work with just an API key)
        $page = ''; $n = 0;
        do {
            $q = rawurlencode("'$id' in parents and trashed=false");
            [$code, $body] = import_get("https://www.googleapis.com/drive/v3/files?q=$q&fields=nextPageToken,files(id,name,mimeType)&pageSize=200&key=$key".($page ? "&pageToken=$page" : ''), 2097152, 25);
            $j = json_decode($body, true);
            if ($code !== 200 || !is_array($j)) { $out['errors'][] = 'Drive API error (HTTP '.$code.'): '.($j['error']['message'] ?? 'check the API key / that the folder is shared with "Anyone with the link"'); break; }
            foreach ($j['files'] ?? [] as $f) {
                if ($f['mimeType'] === 'application/vnd.google-apps.folder') $out['folders'][] = ['id'=>$f['id'], 'name'=>$f['name']];
                elseif (str_starts_with($f['mimeType'], 'image/')) $out['images'][] = $mk($f['id'], $f['name']);
            }
            $page = $j['nextPageToken'] ?? '';
        } while ($page && ++$n < 10);
        if (!$out['errors']) return $out;
    }
    // public embedded folder view (no key needed)
    [$code, $body] = import_get('https://drive.google.com/embeddedfolderview?id='.$id.'#list', 6291456, 25);
    if ($code !== 200 || $body === '') { $out['errors'][] = 'Could not open the folder (HTTP '.$code.'). Make sure it is shared as "Anyone with the link → Viewer".'; return $out; }
    $parsed = import_drive_parse($body);
    $out['name'] = $parsed['name']; $out['folders'] = $parsed['folders'];
    foreach ($parsed['files'] as $f) {
        if (preg_match('/\.(heic|heif)$/i', $f['name'])) { $out['errors'][] = $f['name'].' is HEIC (iPhone format) — export it as JPG first.'; continue; }
        if (preg_match('/\.(pdf|docx?|xlsx?|pptx?|mp4|mov|m4v|webm|zip|txt|csv)$/i', $f['name'])) continue;   // not a picture
        $out['images'][] = $mk($f['id'], $f['name']);      // jpg/png/webp/gif or no extension (verified as an image when downloaded)
    }
    if (!$out['images'] && !$out['folders'] && !$out['errors']) $out['errors'][] = 'The folder looks empty or is not shared publicly. In Drive: right-click the folder → Share → General access → "Anyone with the link" (Viewer), then try again.';
    return $out;
}
/** Parse Drive's public "embeddedfolderview" HTML → ['name', 'files'=>[[id,name]], 'folders'=>[[id,name]]] */
function import_drive_parse(string $body): array {
    $r = ['name'=>'', 'files'=>[], 'folders'=>[]];
    if (preg_match('~<title>([^<]*)</title>~i', $body, $m)) $r['name'] = html_entity_decode(trim(str_ireplace(['- Google Drive','Google Drive'], '', $m[1])), ENT_QUOTES, 'UTF-8');
    // entries look like: <div class="flip-entry" id="entry-FILEID"> … <div class="flip-entry-title">name.jpg</div>   (folders carry a folder icon / "folder" in the thumbnail markup)
    if (preg_match_all('~<div[^>]*class="flip-entry"[^>]*id="entry-([A-Za-z0-9_-]{10,})"(.*?)<div class="flip-entry-title"[^>]*>(.*?)</div>~s', $body, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $e) {
            $name = html_entity_decode(trim(strip_tags($e[3])), ENT_QUOTES, 'UTF-8');
            $isFolder = (stripos($e[2], 'folder') !== false || stripos($e[2], 'vnd.google-apps.folder') !== false) && !preg_match('/\.[a-z0-9]{2,5}$/i', $name);
            if ($isFolder) $r['folders'][] = ['id'=>$e[1], 'name'=>$name]; else $r['files'][] = ['id'=>$e[1], 'name'=>$name];
        }
    }
    return $r;
}

/** Download one image into the gallery. Returns [ok, info, row|null] */
function import_fetch_one(string $url, string $fallback, string $alt, string $category, int $minW = 250): array {
    if (q_val("SELECT id FROM gallery_images WHERE source_url=?", [$url])) return [false, 'already imported', null];
    [$code, $body] = import_get($url, 15728640, 30);
    if ($code !== 200 && $fallback !== '') { $url2 = $fallback; [$code, $body] = import_get($url2, 15728640, 30); }
    if ($code !== 200 || $body === '') return [false, 'download failed (HTTP '.$code.')', null];
    $info = @getimagesizefromstring($body);
    if (!$info) return [false, 'not an image', null];
    [$w, $h] = $info;
    if ($w < $minW || $h < 120) return [false, "too small ({$w}×{$h})", null];
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$info['mime']] ?? null;
    if (!$ext) return [false, 'unsupported type '.$info['mime'], null];
    $hash = md5($body);
    $dir = UPLOAD_PATH.'/gallery/imported';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir.'/'.$hash.'.'.$ext;
    $rel = UPLOAD_URL.'/gallery/imported/'.$hash.'.'.$ext;
    if (q_val("SELECT id FROM gallery_images WHERE image=?", [$rel])) return [false, 'duplicate picture', null];
    if (file_put_contents($file, $body) === false) return [false, 'cannot write to uploads/ (check folder permissions)', null];
    require_once ROOT_PATH.'/lib/media.php';
    try { $opt = media_optimize_image($file); if (!empty($opt['w'])) { $w = $opt['w']; $h = $opt['h']; } } catch (Throwable $e) {}
    $title = $alt !== '' ? $alt : ucwords(trim(preg_replace('/[-_]+|\d{2,4}x\d{2,4}/', ' ', pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME))));
    $id = db_insert('gallery_images', ['image'=>$rel, 'title'=>mb_substr($title, 0, 180), 'category'=>$category ?: 'Old website', 'sort'=>0, 'active'=>1, 'source_url'=>mb_substr($url, 0, 500)]);
    return [true, "{$w}×{$h}", ['id'=>$id, 'image'=>$rel, 'url'=>base_url($rel), 'title'=>$title, 'w'=>$w, 'h'=>$h]];
}
/** Targets an image can be placed into */
function import_targets(): array {
    $t = ['Website photos' => []];
    foreach (site_img_slots() as $k => [$label]) $t['Website photos']['site:'.$k] = $label;
    $t['Website photos']['home_hero'] = 'Home page hero (top slider image)';
    $t['Website photos']['menu_hero'] = 'Our Menu page hero';
    foreach (q_all("SELECT id,name FROM rooms ORDER BY sort,id") as $r) {
        $t['Rooms']['room_cover:'.$r['id']] = $r['name'].' — main photo';
        $t['Rooms']['room_add:'.$r['id']] = $r['name'].' — add to photos';
    }
    foreach (q_all("SELECT id,name FROM amenities ORDER BY sort,id") as $a) { $t['Amenities']['amenity:'.$a['id']] = $a['name'].' — main photo'; $t['Amenities']['amenity_add:'.$a['id']] = $a['name'].' — add to photos'; }
    if (q_val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='attractions'", [], 0))
        foreach (q_all("SELECT id,name FROM attractions ORDER BY sort,id") as $a) { $t['Explore Mussoorie']['attraction:'.$a['id']] = $a['name'].' — main photo'; $t['Explore Mussoorie']['attraction_add:'.$a['id']] = $a['name'].' — add to photos'; }
    foreach (q_all("SELECT i.id, i.name, c.name cat FROM menu_items i JOIN menu_categories c ON c.id=i.category_id ORDER BY c.sort, i.sort") as $i) $t['Menu dishes — '.$i['cat']]['dish:'.$i['id']] = $i['name'];
    foreach (q_all("SELECT id,name FROM menu_categories ORDER BY sort") as $c) $t['Menu section banners']['menucat:'.$c['id']] = $c['name'];
    return $t;
}
/** Put a gallery image somewhere on the site. Returns [ok, message] */
function import_assign(int $galleryId, string $target): array {
    $g = q_row("SELECT * FROM gallery_images WHERE id=?", [$galleryId]);
    if (!$g) return [false, 'Image not found'];
    $img = $g['image'];
    [$kind, $id] = array_pad(explode(':', $target, 2), 2, '');
    switch ($kind) {
        case 'site':
            if (!isset(site_img_slots()[$id])) return [false, 'Unknown slot'];
            setting_save('img_'.$id, $img); return [true, 'Now used as: '.site_img_slots()[$id][0]];
        case 'home_hero':
            $pg = q_row("SELECT id, sections FROM pages WHERE slug='home'");
            $secs = json_decode($pg['sections'] ?? '[]', true) ?: [];
            foreach ($secs as &$s) if (($s['type'] ?? '') === 'hero') { $s['image'] = $img; break; }
            unset($s);
            if ($pg) db_update('pages', ['sections'=>json_encode($secs, JSON_UNESCAPED_UNICODE)], 'id=?', [(int)$pg['id']]);
            setting_save('img_hero', $img);
            return [true, 'Home page hero updated'];
        case 'menu_hero':
            setting_save('menu_hero_image', $img); return [true, 'Our Menu hero updated'];
        case 'room_cover': case 'room_add':
            $r = q_row("SELECT id,name,images FROM rooms WHERE id=?", [(int)$id]);
            if (!$r) return [false, 'Room not found'];
            $imgs = array_values(array_filter(json_decode($r['images'] ?: '[]', true) ?: [], fn($x) => $x !== $img));
            $kind === 'room_cover' ? array_unshift($imgs, $img) : $imgs[] = $img;
            db_update('rooms', ['images'=>json_encode($imgs)] + ($kind === 'room_cover' ? ['cover_image'=>$img] : []), 'id=?', [(int)$r['id']]);
            return [true, $kind === 'room_cover' ? $r['name'].' main photo set' : 'Added to '.$r['name'].' photos'];
        case 'amenity': case 'amenity_add':
            $a = q_row("SELECT id,name,images FROM amenities WHERE id=?", [(int)$id]);
            if (!$a) return [false, 'Amenity not found'];
            $imgs = array_values(array_filter(json_decode($a['images'] ?: '[]', true) ?: [], fn($x) => $x !== $img));
            $kind === 'amenity' ? array_unshift($imgs, $img) : $imgs[] = $img;
            db_update('amenities', ['images'=>json_encode($imgs), 'image'=>$imgs[0]], 'id=?', [(int)$a['id']]);
            return [true, $kind === 'amenity' ? $a['name'].' main photo set' : 'Added to '.$a['name'].' photos'];
        case 'attraction': case 'attraction_add':
            $a = q_row("SELECT id,name,images FROM attractions WHERE id=?", [(int)$id]);
            if (!$a) return [false, 'Place not found'];
            $imgs = array_values(array_filter(json_decode($a['images'] ?: '[]', true) ?: [], fn($x) => $x !== $img));
            $kind === 'attraction' ? array_unshift($imgs, $img) : $imgs[] = $img;
            db_update('attractions', ['images'=>json_encode($imgs), 'image'=>$imgs[0]], 'id=?', [(int)$a['id']]);
            return [true, $kind === 'attraction' ? $a['name'].' main photo set' : 'Added to '.$a['name'].' photos'];
        case 'dish':
            db_update('menu_items', ['image'=>$img], 'id=?', [(int)$id]); return [true, 'Dish photo set'];
        case 'menucat':
            db_update('menu_categories', ['image'=>$img], 'id=?', [(int)$id]); return [true, 'Menu section banner set'];
    }
    return [false, 'Unknown target'];
}
