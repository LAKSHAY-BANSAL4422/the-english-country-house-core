<?php
require_once ROOT_PATH.'/install/page_defaults.php';
/** Location pages generator — local SEO landing pages (near attractions/areas) */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'generate') {
        $place = trim(post('place')); $dist = trim(post('distance'));
        $prefix = post('prefix') ?: 'Hotel Near';
        if ($place === '') $flash = 'Enter a place name.';
        else {
            $title = $prefix.' '.$place;
            $slug = slugify($title);
            if (q_row("SELECT id FROM pages WHERE slug=?", [$slug])) $flash = 'A page for this place already exists.';
            else {
                $content = '<p>Looking for a luxury stay near <strong>'.e($place).'</strong>? '.e(setting('site_name')).' is just '.e($dist ?: 'a short drive').' away — a boutique resort with five themed rooms, a pool, outdoor jacuzzi, sauna and a farm-to-table restaurant.</p>'
                  .'<p>Guests visiting '.e($place).' love our private balconies with valley views, the farm-to-table restaurant and pet-friendly tree houses. Book direct for the best rates.</p>'
                  .(post('extra') ? '<p>'.nl2br(e(post('extra'))).'</p>' : '');
                $id = db_insert('pages', ['title'=>$title,'slug'=>$slug,'page_type'=>'location','content'=>$content,
                  'seo_title'=>$title.' | '.setting('site_name'),
                  'seo_desc'=>'Book a boutique luxury stay near '.$place.' — pool, jacuzzi, themed rooms and farm-to-table dining at '.setting('site_name').', Mussoorie.',
                  'seo_keywords'=>strtolower($place).' hotel, stay near '.strtolower($place).', resort near '.strtolower($place),
                  'sections'=>json_encode(location_default_sections($title), JSON_UNESCAPED_UNICODE),'schema_json'=>'','banner_image'=>'','banner_title'=>$title,'banner_subtitle'=>'','show_banner'=>1,'show_in_menu'=>0,'menu_order'=>60,'active'=>1]);
                $flash = 'Location page created! Edit content/SEO in the CMS or open the Section Builder.';
            }
        }
    }
}
$locs = q_all("SELECT * FROM pages WHERE page_type='location' ORDER BY id DESC");
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.4fr 1fr">
  <div class="card"><h3>📍 Location Pages (rank for "hotel near X" searches)</h3>
    <table class="tbl"><tr><th>Page</th><th>Slug</th><th>Status</th><th></th></tr>
    <?php foreach ($locs as $L): ?>
    <tr><td><b><?= e($L['title']) ?></b></td><td>/<?= e($L['slug']) ?></td>
      <td><span class="badge <?= $L['active']?'green':'red' ?>"><?= $L['active']?'live':'hidden' ?></span></td>
      <td><a class="btn sm ghost" href="?m=cms&edit=<?= $L['id'] ?>">Edit</a>
        <a class="btn sm sec" href="?m=cms&builder=<?= $L['id'] ?>">🧩 Builder</a>
        <a class="btn sm ghost" target="_blank" href="<?= base_url('page.php?slug='.$L['slug']) ?>">View</a></td></tr>
    <?php endforeach; if(!$locs): ?><tr><td colspan="4" class="hint">No location pages yet — generate one →</td></tr><?php endif; ?></table>
    <p class="hint" style="margin-top:10px">Each page auto-includes your rooms grid + booking CTA, GEO meta tags, gets listed in sitemap.xml and appears under "Nearby Stays" in the footer.</p>
  </div>
  <div class="card"><h3>⚡ Generate a Location Page</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="generate">
      <label>Page style</label><select class="input" name="prefix">
        <option>Hotel Near</option><option>Resort Near</option><option>Stay Near</option><option>Hotel In</option><option>Resort In</option></select>
      <label>Place / area name</label><input class="input" name="place" required placeholder="e.g. Dhanaulti, Kempty Falls, Landour">
      <label>Distance / travel time (optional)</label><input class="input" name="distance" placeholder="e.g. 8 km / 20 min drive">
      <label>Extra paragraph (optional)</label><textarea class="input" name="extra" placeholder="Anything special about visiting this place…"></textarea>
      <button class="btn" style="margin-top:12px">Generate Page</button>
    </form>
    <p class="hint" style="margin-top:10px">💡 Ideas: Dhanaulti · Kempty Falls · Landour · Mall Road · George Everest · Jabarkhet Reserve · Surkanda Devi · Company Garden — one page per search intent.</p>
  </div>
</div>
