<?php
/**
 * Shared section / banner editing forms — used by the back-end Section Builder (admin/modules/cms.php)
 * AND the front-end live editor (admin/ajax.php → sec_form / sec_save).
 */
require_once ROOT_PATH.'/includes/sections.php';

function section_blank(string $type): array {
    $t = section_types()[$type] ?? section_types()['text'];
    $s = ['type'=>$type,'visible'=>1,'title'=>'','subtitle'=>'','content'=>'','image'=>'','bg'=>'','tx'=>'',
          'btn1_label'=>'','btn1_url'=>'','btn2_label'=>'','btn2_url'=>'','items'=>[],'video_url'=>'','video_file'=>'','opts'=>[]];
    foreach ($t['opts'] as $k => $o) $s['opts'][$k] = $o[3];
    if ($type === 'cards') $s['items'] = [['icon'=>'fa-solid fa-star','title'=>'Feature one','text'=>'Describe it in one or two lines.'],['icon'=>'fa-solid fa-heart','title'=>'Feature two','text'=>'Describe it in one or two lines.'],['icon'=>'fa-solid fa-leaf','title'=>'Feature three','text'=>'Describe it in one or two lines.']];
    if ($type === 'text') $s['content'] = '<p>Write your content here…</p>';
    if ($type === 'cta') { $s['title'] = 'Ready To Book Your Stay?'; $s['btn1_label'] = 'Book Now'; $s['btn1_url'] = 'booking.php'; }
    if ($type === 'hero') { $s['title'] = 'Your headline here'; $s['btn1_label'] = 'Book Your Stay'; $s['btn1_url'] = 'booking.php'; }
    return $s;
}

/** Read a section's fields from $_POST (indexed: s_title[$idx] … or flat: s_title) */
function section_from_post(array $s, ?int $idx = null): array {
    $v = function (string $f, $default = null) use ($idx) {
        $k = 's_'.$f;
        if ($idx === null) return $_POST[$k] ?? $default;
        return $_POST[$k][$idx] ?? $default;
    };
    $t = section_types()[$s['type'] ?? 'text'] ?? section_types()['text'];
    $fields = $t['fields'];
    $s['visible'] = (int)(bool)$v('visible', 0);
    foreach (['title','subtitle','btn1_label','btn1_url','btn2_label','btn2_url'] as $f) if ($v($f) !== null) $s[$f] = mb_substr(trim((string)$v($f)), 0, 500);
    if (in_array('content', $fields, true) || in_array('raw', $fields, true)) { if ($v('content') !== null) $s['content'] = (string)$v('content'); }
    if ($v('bg') !== null) $s['bg'] = preg_match('/^#[0-9a-f]{3,8}$/i', (string)$v('bg')) ? $v('bg') : '';
    if ($v('tx') !== null) $s['tx'] = preg_match('/^#[0-9a-f]{3,8}$/i', (string)$v('tx')) ? $v('tx') : '';
    if ($v('image') !== null) $s['image'] = preg_replace('#[^A-Za-z0-9_./:-]#', '', (string)$v('image'));
    if ($v('video_url') !== null) $s['video_url'] = mb_substr(trim((string)$v('video_url')), 0, 500);
    if ($v('video_file') !== null) $s['video_file'] = preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$v('video_file'));
    if ($v('items') !== null) { $items = json_decode((string)$v('items'), true); $s['items'] = is_array($items) ? array_values(array_map(fn($it) => [
        'icon'=>mb_substr(trim((string)($it['icon'] ?? '')), 0, 80), 'title'=>mb_substr(trim((string)($it['title'] ?? '')), 0, 190),
        'text'=>mb_substr(trim((string)($it['text'] ?? '')), 0, 1000), 'image'=>preg_replace('#[^A-Za-z0-9_./-]#', '', (string)($it['image'] ?? '')),
        'url'=>mb_substr(trim((string)($it['url'] ?? '')), 0, 300), 'link_label'=>mb_substr(trim((string)($it['link_label'] ?? '')), 0, 80)], $items)) : []; }
    $s['opts'] = is_array($s['opts'] ?? null) ? $s['opts'] : [];
    foreach ($t['opts'] as $k => $o) {
        $raw = $v('opt_'.$k);
        if ($o[1] === 'checkbox') { if ($v('opts_posted') !== null) $s['opts'][$k] = $raw ? 1 : 0; }
        elseif ($raw !== null) $s['opts'][$k] = $o[1] === 'number' ? (int)$raw : mb_substr((string)$raw, 0, 120);
    }
    return $s;
}

/** The editing fields for one section. $n = name builder (e.g. fn($f)=>"s_$f[3]") */
function section_fields_html(array $s, callable $n, string $uid): void {
    $t = section_types()[$s['type'] ?? 'text'] ?? section_types()['text'];
    $F = array_flip($t['fields']);
    $has = fn($f) => isset($F[$f]); ?>
    <input type="hidden" name="<?= e($n('opts_posted')) ?>" value="1">
    <?php if ($has('title') || $has('subtitle')): ?>
    <div class="frow">
      <?php if ($has('title')): ?><div><label>Heading</label><input class="input" name="<?= e($n('title')) ?>" value="<?= e($s['title'] ?? '') ?>" placeholder="Section heading"></div><?php endif; ?>
      <?php if ($has('subtitle')): ?><div><label>Tagline <small class="hint">(small script text above the heading)</small></label><input class="input" name="<?= e($n('subtitle')) ?>" value="<?= e($s['subtitle'] ?? '') ?>"></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($has('content')): ?>
      <label>Content</label>
      <textarea class="input wysiwyg" name="<?= e($n('content')) ?>" data-minh="140px"><?= e($s['content'] ?? '') ?></textarea>
    <?php elseif ($has('raw')): ?>
      <label><?= ($s['type'] ?? '') === 'gallery' ? 'Gallery category to show <small class="hint">(leave empty = all gallery photos; or comma-separated image paths)</small>' : 'HTML / embed code' ?></label>
      <textarea class="input" name="<?= e($n('content')) ?>" style="min-height:90px;font-family:monospace" <?= ($s['type'] ?? '') === 'gallery' ? 'list="galcats"' : '' ?>><?= e($s['content'] ?? '') ?></textarea>
    <?php endif; ?>
    <?php if ($has('image')): ?>
      <label><?= ($s['type'] ?? '') === 'hero' || ($s['type'] ?? '') === 'cta' ? 'Background photo' : (($s['type'] ?? '') === 'video' ? 'Poster image (optional)' : 'Image') ?></label>
      <div data-uploader data-name="<?= e($n('image')) ?>" data-subdir="sections" data-value="<?= e($s['image'] ?? '') ?>"></div>
    <?php endif; ?>
    <?php if ($has('video')): ?>
      <?php if (($s['type'] ?? '') === 'hero'): ?><label style="margin-top:10px">🎬 Background video <small class="hint">(optional — plays muted in an endless loop behind the text; the photo above stays as the poster / fallback. YouTube: the video must be <b>Public or Unlisted</b> with <b>embedding allowed</b> (Studio → video → Details → Show more → "Allow embedding"); private videos and Shorts do not play)</small></label><?php endif; ?>
      <div class="frow">
        <div><label>YouTube / Vimeo link</label><input class="input" name="<?= e($n('video_url')) ?>" value="<?= e($s['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=…"></div>
        <div><label>…or upload a video file <small class="hint">(MP4, keep it under 40 MB)</small></label><div data-uploader data-name="<?= e($n('video_file')) ?>" data-kind="video" data-subdir="video" data-value="<?= e($s['video_file'] ?? '') ?>" data-poster-target="<?= e($n('image')) ?>"></div></div>
      </div>
    <?php endif; ?>
    <?php if ($has('items')): ?>
      <label>Cards <small class="hint">(add / remove / drag to reorder)</small></label>
      <div data-items-editor data-name="<?= e($n('items')) ?>" data-value="<?= e(json_encode($s['items'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"></div>
    <?php endif; ?>
    <?php if ($has('buttons')): ?>
    <div class="frow4">
      <div><label>Button 1 text</label><input class="input" name="<?= e($n('btn1_label')) ?>" value="<?= e($s['btn1_label'] ?? '') ?>"></div>
      <div><label>Button 1 link</label><input class="input" name="<?= e($n('btn1_url')) ?>" value="<?= e($s['btn1_url'] ?? '') ?>" placeholder="booking.php" list="linklist"></div>
      <div><label>Button 2 text</label><input class="input" name="<?= e($n('btn2_label')) ?>" value="<?= e($s['btn2_label'] ?? '') ?>"></div>
      <div><label>Button 2 link</label><input class="input" name="<?= e($n('btn2_url')) ?>" value="<?= e($s['btn2_url'] ?? '') ?>" list="linklist"></div>
    </div>
    <?php endif; ?>
    <?php if ($t['opts'] || $has('colors')): ?>
    <div class="frow4 sec-opts">
      <?php foreach ($t['opts'] as $k => $o): $val = $s['opts'][$k] ?? $o[3]; ?>
        <div>
          <?php if ($o[1] === 'checkbox'): ?>
            <label class="inline" style="margin-top:24px"><input type="checkbox" name="<?= e($n('opt_'.$k)) ?>" value="1" <?= (int)$val === 1 ? 'checked' : '' ?>> <?= e($o[0]) ?></label>
          <?php elseif ($o[1] === 'select'): ?>
            <label><?= e($o[0]) ?></label><select class="input" name="<?= e($n('opt_'.$k)) ?>"><?php foreach ($o[2] as $ck => $cv): ?><option value="<?= e($ck) ?>" <?= (string)$val === (string)$ck ? 'selected' : '' ?>><?= e($cv) ?></option><?php endforeach; ?></select>
          <?php elseif ($o[1] === 'form'): ?>
            <label><?= e($o[0]) ?></label><select class="input" name="<?= e($n('opt_'.$k)) ?>"><option value="0">First active form</option><?php foreach (q_all("SELECT id,name FROM forms ORDER BY id") as $fm): ?><option value="<?= $fm['id'] ?>" <?= (int)$val === (int)$fm['id'] ? 'selected' : '' ?>><?= e($fm['name']) ?></option><?php endforeach; ?></select>
          <?php else: ?>
            <label><?= e($o[0]) ?></label><input class="input" type="<?= $o[1] === 'number' ? 'number' : 'text' ?>" name="<?= e($n('opt_'.$k)) ?>" value="<?= e((string)$val) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($has('colors')): ?>
        <div><label>Background colour</label><div class="colorpick"><input type="color" value="<?= e(($s['bg'] ?? '') ?: '#ffffff') ?>" oninput="this.nextElementSibling.value=this.value"><input class="input" name="<?= e($n('bg')) ?>" value="<?= e($s['bg'] ?? '') ?>" placeholder="default"><button type="button" class="btn sm ghost" onclick="this.previousElementSibling.value=''" title="Reset">×</button></div></div>
        <div><label>Text colour</label><div class="colorpick"><input type="color" value="<?= e(($s['tx'] ?? '') ?: '#333b45') ?>" oninput="this.nextElementSibling.value=this.value"><input class="input" name="<?= e($n('tx')) ?>" value="<?= e($s['tx'] ?? '') ?>" placeholder="default"><button type="button" class="btn sm ghost" onclick="this.previousElementSibling.value=''" title="Reset">×</button></div></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
<?php }

/** datalists shared by the forms (links + gallery categories) — print once */
function section_datalists(): void {
    static $done = false; if ($done) return; $done = true;
    $links = [['index.php','Home'],['rooms.php','Rooms'],['booking.php','Book Your Stay'],['amenities.php','Amenities'],['gallery.php','Gallery'],['menu.php','Our Menu'],['blog.php','Blog'],['faqs.php','FAQ'],['contact.php','Contact'],['about.php','About']];
    foreach (q_all("SELECT title, slug FROM pages WHERE page_type IN ('custom','policy','location') AND active=1") as $p) $links[] = ['page.php?slug='.$p['slug'], $p['title']];
    foreach (q_all("SELECT name, slug FROM rooms WHERE active=1") as $r) $links[] = ['room.php?slug='.$r['slug'], $r['name']];
    echo '<datalist id="linklist">'; foreach ($links as [$u, $l]) echo '<option value="'.e($u).'">'.e($l).'</option>'; echo '</datalist>';
    echo '<datalist id="galcats">'; foreach (q_all("SELECT DISTINCT category FROM gallery_images ORDER BY category") as $c) echo '<option value="'.e($c['category']).'">'; echo '</datalist>';
}

/* ---------------- page banner / header ---------------- */
function banner_fields_html(array $pg): void { ?>
    <div class="frow">
      <div><label>Banner heading (H1)</label><input class="input" name="banner_title" value="<?= e(($pg['banner_title'] ?? '') !== '' ? $pg['banner_title'] : $pg['title']) ?>"></div>
      <div><label>Banner tagline <small class="hint">(small line above the heading)</small></label><input class="input" name="banner_subtitle" value="<?= e($pg['banner_subtitle'] ?? '') ?>"></div>
    </div>
    <label>Banner / header image <small class="hint">(wide photo, 1920×800 looks best — resized automatically)</small></label>
    <div data-uploader data-name="banner_image" data-subdir="pages" data-value="<?= e($pg['banner_image'] ?? '') ?>"></div>
    <label class="inline" style="margin-top:8px"><input type="checkbox" name="show_banner" value="1" <?= !isset($pg['show_banner']) || (int)$pg['show_banner'] === 1 ? 'checked' : '' ?>> Show the banner on this page</label>
<?php }
function banner_from_post(): array {
    return ['banner_title'=>mb_substr(trim(post('banner_title')), 0, 190), 'banner_subtitle'=>mb_substr(trim(post('banner_subtitle')), 0, 255),
            'banner_image'=>preg_replace('#[^A-Za-z0-9_./-]#', '', post('banner_image')), 'show_banner'=>post('show_banner') ? 1 : 0];
}
