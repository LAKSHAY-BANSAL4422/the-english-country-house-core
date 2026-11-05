<?php
/**
 * Shared page rendering: every public page = (banner) + intro content + drag-and-drop sections.
 * Also arms the front-end live editor for logged-in admins ($GLOBALS['LIVE_PAGE']).
 */
require_once __DIR__.'/sections.php';
require_once __DIR__.'/redesign_content.php';   // round 17: rd() is needed before the header, to know the banner photo to preload

function page_load(string $slug): ?array {
    $pg = q_row("SELECT * FROM pages WHERE slug=?", [$slug]);
    return $pg ?: null;
}

/** Build $PAGE meta for header.php from a page row + per-template defaults */
function page_meta(?array $pg, array $def = []): array {
    $site = setting('site_name');
    return [
      'title'    => ($pg['seo_title'] ?? '') !== '' ? $pg['seo_title'] : ($def['title'] ?? (($pg['title'] ?? 'Page').' | '.$site)),
      'desc'     => ($pg['seo_desc'] ?? '') !== '' ? $pg['seo_desc'] : ($def['desc'] ?? setting('seo_desc')),
      'keywords' => ($pg['seo_keywords'] ?? '') !== '' ? $pg['seo_keywords'] : ($def['keywords'] ?? setting('seo_keywords')),
      'schema'   => ($pg['schema_json'] ?? '') !== '' ? $pg['schema_json'] : ($def['schema'] ?? ''),
      'og_image' => ($pg['banner_image'] ?? '') !== '' ? $pg['banner_image'] : ($def['og_image'] ?? ''),
    ] + $def;
}

/** Is the live editor allowed for the current visitor? */
function live_editor_on(): bool {
    static $on = null;
    if ($on !== null) return $on;
    $a = current_admin();
    $on = $a && admin_can($a, 'cms') && setting('live_editor_enabled', '1') === '1';
    return $on;
}
function live_edit_mode(): bool { return live_editor_on() && (get('edit') === '1'); }

/** The banner photo of a page (also preloaded by the head, round 17) */
function banner_image_url(array $pg, string $fallbackImg = 'hero'): string {
    return ($pg['banner_image'] ?? '') !== '' ? base_url($pg['banner_image'])
         : (setting('img_'.$fallbackImg) !== '' || !function_exists('rd') ? site_img($fallbackImg) : base_url(rd('hero_poster')));   // REDESIGN: real photo instead of the stock placeholder
}

/** Page banner (header image + title). Editable in the live editor. */
function render_banner(array $pg, array $crumbs = [], string $fallbackImg = 'hero'): void {
    if (isset($pg['show_banner']) && (int)$pg['show_banner'] === 0 && !live_edit_mode()) return;
    $img = banner_image_url($pg, $fallbackImg);
    $title = ($pg['banner_title'] ?? '') !== '' ? $pg['banner_title'] : $pg['title'];
    $sub = strip_free_cancellation($pg['banner_subtitle'] ?? '');
    $hidden = isset($pg['show_banner']) && (int)$pg['show_banner'] === 0;
    // REDESIGN: cinematic video header (round 2: Gallery; round 3: Amenities, Rooms, About, Contact; room pages call
    // rd_cine_banner() from room.php). Same CMS title / subtitle / photo fields; the pages are listed in rd('cine_pages').
    if (function_exists('rd_cine_banner') && in_array($pg['slug'] ?? '', rd('cine_pages', []), true)) {
        if ($sub === '') $sub = rd('cine_subtitles', [])[$pg['slug']] ?? '';
        $attrs = 'data-banner="'.(int)$pg['id'].'"'.($hidden ? ' data-hidden="1"' : '');
        rd_cine_banner($img, $title, $sub, $crumbs, $attrs);
        return;
    } ?>
    <div class="page-banner" data-banner="<?= (int)$pg['id'] ?>" <?= $hidden ? 'data-hidden="1"' : '' ?> style="background-image:url('<?= e($img) ?>')">
      <div class="pb-inner">
        <?php /* round 14: the subline sits inside the H1 (same look) so the page heading carries the keywords */ ?>
        <h1 class="rd-h1"><?php if ($sub !== ''): ?><span class="pb-sub"><?= e($sub) ?> </span><?php endif; ?><span class="pb-title"><?= e($title) ?></span></h1>
        <div class="crumbs"><a href="<?= base_url('') ?>">Home</a>
          <?php foreach ($crumbs as $c): ?> › <?= is_array($c) ? '<a href="'.e($c[1]).'">'.e($c[0]).'</a>' : e($c) ?><?php endforeach; ?>
        </div>
      </div>
    </div>
<?php }

/** round 10: the hotel does not offer free cancellation. Page text saved in the database (the three location
 *  landing pages, the booking banner) still says "… and free cancellation up to N days before check-in";
 *  drop that phrase when the page is shown, without changing the database. */
function strip_free_cancellation(string $html): string {
    return preg_replace('/(?:\s+and|\s*[,·])?\s*free[\s-]+cancellation(?:\s+up\s+to\s+(?:\{REFUND_DAYS\}|\d+)\s+days?\s+before\s+check-in)?/iu', '', $html) ?? $html;
}

/** True when editor content is really empty (the visual editor saves "<p><br></p>" for an empty box) */
function html_is_empty(?string $html): bool {
    if ($html === null) return true;
    $t = preg_replace('/<(br|p|div|span)[^>]*>|<\/(br|p|div|span)>|&nbsp;|\xC2\xA0/u', '', $html);
    return trim(strip_tags((string)$t)) === '' && !preg_match('/<(img|iframe|video|table|ul|ol|h[1-6]|a )/i', (string)$html);
}

/** Intro content (classic editor) with placeholders, then the sections.
 *  A hero section is always rendered first (flush under the transparent header); the intro follows it. */
function render_page_body(array $pg, array $fallbackSections = []): void {
    $GLOBALS['LIVE_PAGE'] = $pg;
    $content = html_is_empty($pg['content'] ?? '') ? '' : (string)$pg['content'];
    $sections = json_decode($pg['sections'] ?? '[]', true) ?: [];
    if (!$sections && $fallbackSections) $sections = $fallbackSections;
    $edit = live_edit_mode();
    $heroFirst = isset($sections[0]['type']) && $sections[0]['type'] === 'hero' && (!empty($sections[0]['visible']) || $edit);
    if ($heroFirst) { render_sections([0 => $sections[0]], $edit); unset($sections[0]); }
    if ($content !== '') {
        $content = str_replace(['{REFUND_DAYS}','{REFUND_PERCENT}','{SITE_NAME}','{PHONE}','{EMAIL}'],
            [setting('refund_days','7'), setting('refund_percent','100'), setting('site_name'), setting('contact_phone'), setting('contact_email')], $content);
        $content = strip_free_cancellation($content);
        // round 14: {URL:file.php} (written by install/seo_content.php) → that page's address, so links work in any install folder
        $content = preg_replace_callback('/\{URL:([a-z0-9._?=&-]+)\}/i', fn($m) => u($m[1]), $content) ?? $content;
        echo '<section class="page-intro" data-intro="'.(int)$pg['id'].'"><div class="container reveal rich" style="max-width:900px;color:#4a535f">'.$content.'</div></section>';
    } elseif ($edit) {
        echo '<section class="page-intro page-intro-empty" data-intro="'.(int)$pg['id'].'"><div class="container" style="max-width:900px"></div></section>';
    }
    render_sections($sections, $edit);
}

/** Everything a simple system page needs: load → meta → header → banner → body → footer */
function render_system_page(string $slug, array $metaDefaults, array $crumbs, array $fallbackSections, string $fallbackImg = 'hero', array $extra = []): void {
    $pg = page_load($slug);
    if (!$pg) {   // pre-migration safety net: behave like before
        $pg = ['id'=>0,'slug'=>$slug,'title'=>$metaDefaults['h1'] ?? ucfirst($slug),'banner_title'=>$metaDefaults['h1'] ?? '','banner_subtitle'=>'','banner_image'=>'','content'=>'','sections'=>'[]','show_banner'=>1,'active'=>1];
    }
    if (!(int)$pg['active'] && !current_admin()) { http_response_code(404); redirect(base_url('')); }
    $PAGE = page_meta($pg, $metaDefaults);
    // round 17 (technical SEO): breadcrumb data, page type, last change, and the banner photo preloaded for a faster first view
    $PAGE += ['seo_type'=>$slug, 'crumbs'=>$crumbs, 'modified'=>$pg['updated_at'] ?? '', 'slug'=>$slug];
    if (!isset($pg['show_banner']) || (int)$pg['show_banner'] !== 0) $PAGE['lcp'] = banner_image_url($pg, $fallbackImg);
    if (!empty($extra['seo'])) $PAGE = array_merge($PAGE, $extra['seo']);
    if (!empty($extra['title_override'])) $PAGE['title'] = $extra['title_override'];
    if (!empty($extra['desc_override'])) $PAGE['desc'] = $extra['desc_override'];
    include ROOT_PATH.'/includes/header.php';
    render_banner(array_merge($pg, array_intersect_key($extra, ['banner_title'=>1, 'banner_subtitle'=>1])), $crumbs, $fallbackImg);   // round 14: blog categories set their own H1
    render_page_body($pg, $fallbackSections);
    include ROOT_PATH.'/includes/inquiry_modal.php';
    include ROOT_PATH.'/includes/footer.php';
}
