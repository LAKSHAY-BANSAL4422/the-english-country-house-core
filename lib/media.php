<?php
/**
 * Media optimisation — every uploaded photo / video is made web-fast automatically
 *   • images: EXIF orientation fixed, resized to max N px, re-encoded (progressive JPEG / PNG keeps transparency),
 *             metadata stripped, plus a .webp twin that .htaccess serves to browsers that accept it (25–35 % smaller)
 *   • videos: re-encoded with ffmpeg when the server has it (720p, H.264, faststart, ~2 Mbps) — otherwise the
 *             browser already compressed it before upload (uploader.js) so nothing is lost
 *   • "Optimise existing media" (Site Tools) runs the same over everything already in uploads/
 */

function media_settings(): array {
    return [
        'max_px'   => max(800, (int)setting('upload_max_px', '2000')),
        'quality'  => min(95, max(50, (int)setting('upload_quality', '82'))),
        'webp'     => setting('media_webp', '1') === '1' && function_exists('imagewebp'),
        'webp_q'   => min(95, max(50, (int)setting('media_webp_quality', '78'))),
        'video_h'  => max(360, (int)setting('media_video_height', '720')),
        'video_crf'=> min(35, max(18, (int)setting('media_video_crf', '27'))),
    ];
}

/** Is the path one of ours and an image type we can process with GD? */
function media_is_image(string $abs): bool {
    return (bool)preg_match('/\.(jpe?g|png|webp|gif)$/i', $abs) && is_file($abs);
}

/** Open any supported image into a GD resource (orientation corrected). Returns [img, type, hasAlpha] or null. */
function media_open(string $abs): ?array {
    $info = @getimagesize($abs); if (!$info) return null;
    $type = $info[2]; $img = null; $alpha = false;
    switch ($type) {
        case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($abs); break;
        case IMAGETYPE_PNG:  $img = @imagecreatefrompng($abs); $alpha = true; break;
        case IMAGETYPE_WEBP: $img = @imagecreatefromwebp($abs); $alpha = true; break;
        case IMAGETYPE_GIF:  // animated gifs are left alone
            $raw = (string)file_get_contents($abs, false, null, 0, 2*1024*1024);
            if (preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $raw) > 1) return null;
            $img = @imagecreatefromgif($abs); $alpha = true; break;
        default: return null;
    }
    if (!$img) return null;
    if ($alpha) { imagealphablending($img, false); imagesavealpha($img, true); }
    // EXIF orientation (phones)
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($abs);
        $o = (int)($exif['Orientation'] ?? 1);
        if (in_array($o, [3, 6, 8], true)) {
            $rot = imagerotate($img, $o === 3 ? 180 : ($o === 6 ? -90 : 90), 0);
            if ($rot) { imagedestroy($img); $img = $rot; }
        }
    }
    return [$img, $type, $alpha];
}

/** Does a PNG/WebP/GIF actually use transparency? (sampled) */
function media_has_transparency($img): bool {
    $w = imagesx($img); $h = imagesy($img);
    $sx = max(1, (int)($w / 40)); $sy = max(1, (int)($h / 40));
    for ($y = 0; $y < $h; $y += $sy) for ($x = 0; $x < $w; $x += $sx) if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) return true;
    return false;
}

/**
 * Optimise one image in place (+ .webp twin). Returns ['ok','before','after','webp','w','h','note'].
 * $force re-does files that already have a .webp twin.
 */
function media_optimize_image(string $abs, bool $force = false): array {
    $r = ['ok'=>false,'before'=>@filesize($abs) ?: 0,'after'=>0,'webp'=>0,'w'=>0,'h'=>0,'note'=>''];
    if (!media_is_image($abs)) { $r['note'] = 'not an image'; return $r; }
    $S = media_settings();
    $webpPath = $abs.'.webp';
    if (!$force && is_file($webpPath) && filemtime($webpPath) >= filemtime($abs)) { $r['ok'] = true; $r['after'] = $r['before']; $r['webp'] = filesize($webpPath); $r['note'] = 'already optimised'; return $r; }
    $o = media_open($abs);
    if (!$o) { $r['note'] = 'animated / unreadable — skipped'; return $r; }
    [$img, $type, $alpha] = $o;
    $w = imagesx($img); $h = imagesy($img);
    $max = $S['max_px']; $wasResized = false;
    if ($w > $max || $h > $max) {
        $wasResized = true;
        $scale = min($max / $w, $max / $h);
        $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        if ($alpha) { imagealphablending($dst, false); imagesavealpha($dst, true); imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127)); }
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img); $img = $dst; $w = $nw; $h = $nh;
    }
    $transparent = $alpha && media_has_transparency($img);
    $tmp = $abs.'.tmp';
    $written = false;
    if ($type === IMAGETYPE_JPEG) {
        imageinterlace($img, true);
        $written = imagejpeg($img, $tmp, $S['quality']);
    } elseif ($type === IMAGETYPE_PNG) {
        if ($transparent) { $written = imagepng($img, $tmp, 9); }
        else { // opaque PNG (screenshots, exports) → PNG stays PNG but max-compressed; the .webp twin does the real saving
            $written = imagepng($img, $tmp, 9);
        }
    } elseif ($type === IMAGETYPE_WEBP) {
        $written = imagewebp($img, $tmp, $S['webp_q']);
    } elseif ($type === IMAGETYPE_GIF) {
        $written = imagegif($img, $tmp);
    }
    // replace the original only when the new file is smaller or the picture had to be shrunk — never make a file bigger
    clearstatcache(true, $tmp);
    if ($written && is_file($tmp) && filesize($tmp) > 0 && ($wasResized || filesize($tmp) < $r['before'])) @rename($tmp, $abs);
    else @unlink($tmp);
    clearstatcache(true, $abs);
    $r['after'] = filesize($abs); $r['w'] = $w; $r['h'] = $h;
    // .webp twin (lossy, keeps transparency) — served automatically by .htaccess when the browser accepts image/webp
    if ($S['webp'] && $type !== IMAGETYPE_WEBP && $type !== IMAGETYPE_GIF) {
        if ($transparent) { imagealphablending($img, false); imagesavealpha($img, true); }
        if (@imagewebp($img, $webpPath, $S['webp_q'])) {
            clearstatcache(true, $webpPath);
            // pointless twin (bigger than the original) → remove it
            if (filesize($webpPath) >= $r['after']) { @unlink($webpPath); $r['webp'] = 0; } else $r['webp'] = filesize($webpPath);
        }
    }
    imagedestroy($img);
    $r['ok'] = true;
    return $r;
}

/** Remove a (near-)white background from a logo → transparent PNG. Writes next to the original and returns the new relative path. */
function media_logo_transparent(string $abs, int $tolerance = 28): string {
    $o = media_open($abs); if (!$o) return '';
    [$img] = $o;
    $w = imagesx($img); $h = imagesy($img);
    $out = imagecreatetruecolor($w, $h); imagealphablending($out, false); imagesavealpha($out, true);
    $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
    for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($img, $x, $y);
        $a = ($c >> 24) & 0x7F; $rr = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF;
        $d = max(255 - $rr, 255 - $g, 255 - $b);                       // distance from pure white
        if ($a >= 120) { imagesetpixel($out, $x, $y, $clear); continue; }
        if ($d <= $tolerance) imagesetpixel($out, $x, $y, $clear);
        elseif ($d <= $tolerance * 2) imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $rr, $g, $b, (int)round(127 * (1 - ($d - $tolerance) / $tolerance)))); // soft edge
        else imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $rr, $g, $b, $a));
    }
    // trim empty margins
    $minX = $w; $minY = $h; $maxX = -1; $maxY = -1;
    for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) if (((imagecolorat($out, $x, $y) >> 24) & 0x7F) < 120) { $minX = min($minX, $x); $maxX = max($maxX, $x); $minY = min($minY, $y); $maxY = max($maxY, $y); }
    if ($maxX < 0) { imagedestroy($out); imagedestroy($img); return ''; }
    $pad = 6; $cx = max(0, $minX - $pad); $cy = max(0, $minY - $pad); $cw = min($w, $maxX + $pad + 1) - $cx; $ch = min($h, $maxY + $pad + 1) - $cy;
    $crop = imagecreatetruecolor($cw, $ch); imagealphablending($crop, false); imagesavealpha($crop, true); imagefill($crop, 0, 0, $clear);
    imagecopy($crop, $out, 0, 0, $cx, $cy, $cw, $ch);
    $new = preg_replace('/\.[a-z0-9]+$/i', '', $abs).'-transparent.png';
    $ok = imagepng($crop, $new, 9);
    imagedestroy($crop); imagedestroy($out); imagedestroy($img);
    if (!$ok) return '';
    @imagewebp(media_open($new)[0], $new.'.webp', 85);
    return str_replace(ROOT_PATH.'/', '', $new);
}

/* ============================ video ============================ */

function media_ffmpeg(): string {
    static $bin = null;
    if ($bin !== null) return $bin;
    $bin = '';
    if (!function_exists('exec') || !function_exists('shell_exec')) return $bin;
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true) || in_array('shell_exec', $disabled, true)) return $bin;
    $cands = array_filter([setting('ffmpeg_path', ''), '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg', trim((string)@shell_exec('command -v ffmpeg 2>/dev/null'))]);
    foreach ($cands as $c) if ($c && @is_executable($c)) { $bin = $c; break; }
    return $bin;
}

/**
 * Re-encode a video to a lean MP4 (H.264 + AAC, max height N, faststart). Synchronous; returns the (possibly new) absolute path.
 * If ffmpeg is missing or the result is not smaller, the original is kept.
 */
function media_optimize_video(string $abs): array {
    $r = ['ok'=>false,'path'=>$abs,'before'=>@filesize($abs) ?: 0,'after'=>0,'note'=>''];
    $ff = media_ffmpeg();
    if (!$ff) { $r['note'] = 'ffmpeg not available on this server (browser-side compression was used)'; return $r; }
    if ($r['before'] < 1500000) { $r['ok'] = true; $r['after'] = $r['before']; $r['note'] = 'small enough'; return $r; }
    $S = media_settings();
    @set_time_limit(0); @ignore_user_abort(true);
    $out = preg_replace('/\.[a-z0-9]+$/i', '', $abs).'-web.mp4';
    $cmd = escapeshellarg($ff).' -y -nostdin -loglevel error -i '.escapeshellarg($abs)
         .' -vf '.escapeshellarg("scale='min(1280,iw)':'min(".$S['video_h'].",ih)':force_original_aspect_ratio=decrease,scale=trunc(iw/2)*2:trunc(ih/2)*2")
         .' -c:v libx264 -preset veryfast -crf '.(int)$S['video_crf'].' -pix_fmt yuv420p -movflags +faststart -c:a aac -b:a 96k -ac 2 '.escapeshellarg($out).' 2>&1';
    $outp = []; $code = 0; @exec($cmd, $outp, $code);
    clearstatcache();
    if ($code !== 0 || !is_file($out) || filesize($out) < 10000) { @unlink($out); $r['note'] = 'ffmpeg failed: '.trim(implode(' ', array_slice($outp, -2))); return $r; }
    if (filesize($out) >= $r['before'] * 0.92) { @unlink($out); $r['ok'] = true; $r['after'] = $r['before']; $r['note'] = 'already compact'; return $r; }
    @unlink($abs);
    $r['ok'] = true; $r['path'] = $out; $r['after'] = filesize($out); $r['note'] = 'compressed with ffmpeg';
    return $r;
}

/** Poster frame (JPEG) for a video, if ffmpeg exists. Returns relative path or ''. */
function media_video_poster(string $abs): string {
    $ff = media_ffmpeg(); if (!$ff) return '';
    $out = preg_replace('/\.[a-z0-9]+$/i', '', $abs).'-poster.jpg';
    @exec(escapeshellarg($ff).' -y -nostdin -loglevel error -ss 1 -i '.escapeshellarg($abs).' -frames:v 1 -q:v 4 '.escapeshellarg($out).' 2>&1', $o, $c);
    if ($c !== 0 || !is_file($out)) { @unlink($out); return ''; }
    media_optimize_image($out);
    return str_replace(ROOT_PATH.'/', '', $out);
}

/* ============================ bulk: optimise everything already uploaded ============================ */

/** All optimisable images under uploads/ (skips QR codes & invoices). */
function media_scan_images(): array {
    $list = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOAD_PATH, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (!preg_match('/\.(jpe?g|png)$/i', $p)) continue;
        if (preg_match('#/uploads/(qr|invoices)/#', $p)) continue;
        if (str_ends_with(strtolower($p), '-poster.jpg') && is_file($p.'.webp')) continue;
        $list[] = $p;
    }
    sort($list);
    return $list;
}

/** Optimise a batch starting at $offset; returns progress for the admin UI. */
function media_optimize_batch(int $offset, int $count = 6, bool $force = false): array {
    $all = media_scan_images(); $total = count($all);
    $done = []; $saved = 0; $t0 = microtime(true);
    for ($i = $offset; $i < min($total, $offset + $count); $i++) {
        $res = media_optimize_image($all[$i], $force);
        $saved += max(0, $res['before'] - $res['after']);
        $done[] = ['file'=>str_replace(UPLOAD_PATH.'/', '', $all[$i]), 'before'=>$res['before'], 'after'=>$res['after'], 'webp'=>$res['webp'], 'note'=>$res['note']];
        if (microtime(true) - $t0 > 20) { $i++; break; }   // stay well inside the request time limit
    }
    $next = $i ?? $offset;
    return ['total'=>$total, 'next'=>min($next, $total), 'finished'=>$next >= $total, 'saved'=>$saved, 'items'=>$done];
}

function human_bytes(int $b): string { return $b >= 1048576 ? round($b / 1048576, 1).' MB' : ($b >= 1024 ? round($b / 1024).' KB' : $b.' B'); }
