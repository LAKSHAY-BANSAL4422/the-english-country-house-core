<?php
/** Appearance: 11-color scheme + fonts + 6 presets, logos & favicon with size controls */
$PRESETS = [
 'royal-gold' => ['Royal Gold', ['color_primary'=>'#c9974b','color_primary_dark'=>'#a87a2f','color_secondary'=>'#0f2238','color_accent'=>'#e8c88f','color_bg'=>'#ffffff','color_bg_alt'=>'#f6f3ee','color_text'=>'#333b45','color_heading'=>'#182635','color_footer_bg'=>'#0f2238','color_footer_text'=>'#cfd8e3','color_btn_text'=>'#ffffff','font_heading'=>'Cormorant Garamond','font_body'=>'Outfit','font_accent'=>'Great Vibes']],
 'emerald'    => ['Emerald Retreat', ['color_primary'=>'#1e7d5f','color_primary_dark'=>'#155c46','color_secondary'=>'#0d2320','color_accent'=>'#a3d9c5','color_bg'=>'#ffffff','color_bg_alt'=>'#eff6f3','color_text'=>'#2f3b38','color_heading'=>'#12241f','color_footer_bg'=>'#0d2320','color_footer_text'=>'#c4d8d1','color_btn_text'=>'#ffffff','font_heading'=>'Cormorant Garamond','font_body'=>'Nunito Sans','font_accent'=>'Pinyon Script']],
 'royal-blue' => ['Royal Blue', ['color_primary'=>'#2563b8','color_primary_dark'=>'#1a4a8c','color_secondary'=>'#101e33','color_accent'=>'#9cc1ef','color_bg'=>'#ffffff','color_bg_alt'=>'#eef3fa','color_text'=>'#33404f','color_heading'=>'#14243a','color_footer_bg'=>'#101e33','color_footer_text'=>'#c9d6e8','color_btn_text'=>'#ffffff','font_heading'=>'Libre Baskerville','font_body'=>'Inter','font_accent'=>'Allura']],
 'terracotta' => ['Terracotta Sunset', ['color_primary'=>'#c0603a','color_primary_dark'=>'#99492a','color_secondary'=>'#2e1c14','color_accent'=>'#eec4ab','color_bg'=>'#fffdfb','color_bg_alt'=>'#f9efe8','color_text'=>'#43362e','color_heading'=>'#2e1c14','color_footer_bg'=>'#2e1c14','color_footer_text'=>'#e3d1c6','color_btn_text'=>'#ffffff','font_heading'=>'Fraunces','font_body'=>'Poppins','font_accent'=>'Dancing Script']],
 'lavender'   => ['Lavender Luxe', ['color_primary'=>'#7b5ea8','color_primary_dark'=>'#5d4483','color_secondary'=>'#241d33','color_accent'=>'#cdbceb','color_bg'=>'#ffffff','color_bg_alt'=>'#f4f1fa','color_text'=>'#3a3545','color_heading'=>'#241d33','color_footer_bg'=>'#241d33','color_footer_text'=>'#d5cde4','color_btn_text'=>'#ffffff','font_heading'=>'DM Serif Display','font_body'=>'DM Sans','font_accent'=>'Parisienne']],
 'monochrome' => ['Modern Monochrome', ['color_primary'=>'#1c1c1e','color_primary_dark'=>'#000000','color_secondary'=>'#111113','color_accent'=>'#bdbdc2','color_bg'=>'#ffffff','color_bg_alt'=>'#f4f4f6','color_text'=>'#3c3c40','color_heading'=>'#111113','color_footer_bg'=>'#111113','color_footer_text'=>'#cfcfd4','color_btn_text'=>'#ffffff','font_heading'=>'Marcellus','font_body'=>'Sora','font_accent'=>'Sacramento']],
];
$COLORS = ['color_primary'=>'Primary / Buttons','color_primary_dark'=>'Primary hover','color_secondary'=>'Header / Dark panels','color_accent'=>'Accent highlights','color_bg'=>'Page background','color_bg_alt'=>'Alternate section bg','color_text'=>'Body text','color_heading'=>'Headings','color_footer_bg'=>'Footer background','color_footer_text'=>'Footer text','color_btn_text'=>'Button text'];
$FONTS = ['Cormorant Garamond','Playfair Display','Marcellus','Fraunces','Prata','Libre Baskerville','Lora','DM Serif Display','Montserrat','Merriweather','Cinzel','Italiana','Outfit','Jost','Inter','Plus Jakarta Sans','Sora','Poppins','Nunito Sans','Open Sans','DM Sans','Lato','Raleway'];
$ACCENT_FONTS = ['Great Vibes','Pinyon Script','Allura','Dancing Script','Parisienne','Sacramento','Italianno','Tangerine'];
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'preset' && isset($PRESETS[post('preset')])) {
        foreach ($PRESETS[post('preset')][1] as $k=>$v) setting_save($k, $v);
        setting_save('appearance_preset', post('preset'));
        $flash = 'Preset "'.$PRESETS[post('preset')][0].'" applied.';
    }
    if (post('do') === 'colors') {
        foreach (array_keys($COLORS) as $k) if (preg_match('/^#[0-9a-fA-F]{6}$/', post($k))) setting_save($k, post($k));
        setting_save('font_heading', post('font_heading'));
        setting_save('font_body', post('font_body'));
        setting_save('font_accent', post('font_accent'));
        setting_save('appearance_preset', 'custom');
        $flash = 'Colors & fonts saved.';
    }
    if (post('do') === 'logos') {
        require_once ROOT_PATH.'/lib/media.php';
        foreach (['logo_header'=>'logo','logo_footer'=>'logo','favicon'=>'logo'] as $f=>$dir) {
            $up = handle_upload($f, $dir, ['jpg','jpeg','png','webp','svg','ico']);
            if ($up) {
                // "make background transparent" — white/near-white pixels become see-through and the logo is trimmed (JPG logos too)
                if ($f !== 'favicon' && post('logo_transparent') && preg_match('/\.(jpe?g|png|webp)$/i', $up)) {
                    $t = media_logo_transparent(ROOT_PATH.'/'.$up, max(5, min(80, (int)post('logo_tolerance', 28))));
                    if ($t) $up = $t;
                }
                setting_save($f, $up);
            }
        }
        if (post('do_transparent_existing')) {   // apply to the logos already uploaded
            foreach (['logo_header','logo_footer'] as $f) {
                $cur = setting($f);
                if ($cur && preg_match('/\.(jpe?g|png|webp)$/i', $cur) && is_file(ROOT_PATH.'/'.$cur)) { $t = media_logo_transparent(ROOT_PATH.'/'.$cur, max(5, min(80, (int)post('logo_tolerance', 28)))); if ($t) setting_save($f, $t); }
            }
        }
        foreach (['logo_header_w','logo_header_h','logo_footer_w','logo_footer_h'] as $k) setting_save($k, (int)post($k));
        setting_save('pwa_enabled', post('pwa_enabled') ? '1' : '0');
        setting_save('pwa_short_name', post('pwa_short_name'));
        $flash = 'Branding saved.';
    }
}
if (get('saved')) $flash = get('saved');
$cur = fn($k)=>setting($k);
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="card"><h3>🎨 Ready-made Presets (whole website theme in one click)</h3>
  <div class="swatches">
    <?php foreach ($PRESETS as $key=>$p): ?>
    <form method="post" style="display:contents"><?= csrf_field() ?><input type="hidden" name="do" value="preset"><input type="hidden" name="preset" value="<?= $key ?>">
      <button class="preset <?= setting('appearance_preset')===$key?'sel':'' ?>" type="submit">
        <span class="dots"><span style="background:<?= $p[1]['color_primary'] ?>"></span><span style="background:<?= $p[1]['color_secondary'] ?>"></span><span style="background:<?= $p[1]['color_accent'] ?>"></span><span style="background:<?= $p[1]['color_bg_alt'] ?>"></span></span>
        <b><?= $p[0] ?></b><br><small class="hint"><?= $p[1]['font_heading'] ?> + <?= $p[1]['font_body'] ?></small>
      </button></form>
    <?php endforeach; ?>
  </div>
</div>
<div class="grid2">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="colors">
    <h3>Custom Colors (11) &amp; Fonts</h3>
    <div class="frow">
      <?php foreach ($COLORS as $k=>$lbl): ?>
      <div><label><?= $lbl ?></label>
        <div style="display:flex;gap:8px"><input type="color" name="<?= $k ?>" value="<?= e($cur($k)) ?>" style="width:46px;height:38px;border:1px solid var(--bd);border-radius:6px;padding:2px" oninput="this.nextElementSibling.value=this.value">
        <input class="input" value="<?= e($cur($k)) ?>" oninput="this.previousElementSibling.value=this.value" style="font-family:monospace"></div></div>
      <?php endforeach; ?>
      <div><label>Heading font</label><select class="input" name="font_heading"><?php foreach ($FONTS as $f): ?><option <?= $cur('font_heading')===$f?'selected':'' ?>><?= $f ?></option><?php endforeach; ?></select></div>
      <div><label>Body font</label><select class="input" name="font_body"><?php foreach ($FONTS as $f): ?><option <?= $cur('font_body')===$f?'selected':'' ?>><?= $f ?></option><?php endforeach; ?></select></div>
      <div><label>Accent script font <small class="hint">(fancy section taglines)</small></label><select class="input" name="font_accent"><?php foreach ($ACCENT_FONTS as $f): ?><option <?= $cur('font_accent')===$f?'selected':'' ?>><?= $f ?></option><?php endforeach; ?></select></div>
    </div>
    <button class="btn" style="margin-top:14px">Save Colors &amp; Fonts</button>
  </form>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="logos">
    <h3>Logos, Favicon &amp; App View</h3>
    <div class="alert" style="margin-bottom:12px;font-size:13px">✨ <b>Logo on a white box?</b> Tick <i>Make background transparent</i> below and upload it (JPG or PNG) — white/near-white pixels are removed and the logo is trimmed to its edges, so it sits cleanly on the dark header. Best results with a PNG that already has transparency (ask your designer for "PNG with transparent background" or an SVG).</div>
    <label class="inline"><input type="checkbox" name="logo_transparent" value="1" checked> Make background transparent on upload &nbsp; <small class="hint">tolerance</small> <input type="number" name="logo_tolerance" value="28" min="5" max="80" style="width:70px;display:inline-block" class="input"></label>
    <label class="inline"><input type="checkbox" name="do_transparent_existing" value="1"> Apply to the logos already uploaded (header &amp; footer) when I click Save</label>
    <label>Header Logo</label><input class="input" type="file" name="logo_header" accept="image/*">
    <?php if ($cur('logo_header')): ?><img src="<?= base_url($cur('logo_header')) ?>" style="max-height:56px;margin-top:6px;background:#26364d;padding:6px;border-radius:6px"><?php endif; ?>
    <div class="frow"><div><label>Header logo width px (0=auto)</label><input class="input" type="number" name="logo_header_w" value="<?= (int)$cur('logo_header_w') ?>"></div>
    <div><label>Header logo height px (0=auto)</label><input class="input" type="number" name="logo_header_h" value="<?= (int)$cur('logo_header_h') ?>"></div></div>
    <p class="hint">Set only the height to keep the logo's proportions (width follows automatically). After scrolling the header shrinks the logo to ~78 % of this height; phones show ~62 %.</p>
    <label>Footer Logo</label><input class="input" type="file" name="logo_footer" accept="image/*">
    <?php if ($cur('logo_footer')): ?><img src="<?= base_url($cur('logo_footer')) ?>" style="max-height:56px;margin-top:6px;background:#26364d;padding:6px;border-radius:6px"><?php endif; ?>
    <div class="frow"><div><label>Footer logo width px</label><input class="input" type="number" name="logo_footer_w" value="<?= (int)$cur('logo_footer_w') ?>"></div>
    <div><label>Footer logo height px</label><input class="input" type="number" name="logo_footer_h" value="<?= (int)$cur('logo_footer_h') ?>"></div></div>
    <label>Favicon (.ico / .png / .svg)</label><input class="input" type="file" name="favicon" accept=".ico,.png,.svg">
    <?php if ($cur('favicon')): ?><img src="<?= base_url($cur('favicon')) ?>" style="width:28px;margin-top:6px"><?php endif; ?>
    <h3 style="margin-top:16px">📱 Mobile App View (PWA)</h3>
    <label class="inline"><input type="checkbox" name="pwa_enabled" value="1" <?= $cur('pwa_enabled')==='1'?'checked':'' ?>> Enable app-like mobile view (installable, bottom nav bar, offline cache)</label>
    <label>App short name</label><input class="input" name="pwa_short_name" value="<?= e($cur('pwa_short_name')) ?>">
    <button class="btn" style="margin-top:14px">Save Branding</button>
  </form>
</div>
