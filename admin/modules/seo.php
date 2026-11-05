<?php
/** SEO / AEO / GEO: defaults, robots, llms.txt, geo coordinates + Google autosuggest keyword research + recommendations */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        foreach (['seo_title','seo_desc','seo_keywords','robots_txt','ga_code','head_scripts','schema_org'] as $k)
            setting_save($k, $_POST[$k] ?? '');
        // round 17: technical SEO settings (main web address, Search Console / Bing verification, Google Business Profile, other profiles)
        foreach (['seo_site_url','seo_gsc_verify','seo_bing_verify','seo_gbp_url','seo_same_as','seo_price_range'] as $k)
            if (isset($_POST[$k])) setting_save($k, trim((string)$_POST[$k]));
        $flash = 'SEO settings saved.';
    }
    if (post('do') === 'save_geo') {
        foreach (['geo_lat','geo_lng','geo_region','geo_placename','llms_txt'] as $k)
            setting_save($k, $_POST[$k] ?? '');
        $flash = 'AEO / GEO settings saved.';
    }
}
/* Google autosuggest (server-side proxy, cached) */
if (get('suggest')) {
    $seed = trim(get('suggest'));
    $cached = q_row("SELECT * FROM seo_keywords WHERE seed=? AND fetched_at > DATE_SUB(NOW(), INTERVAL 7 DAY)", [$seed]);
    if ($cached) json_out(['ok'=>true,'suggestions'=>json_decode($cached['suggestions'],true)]);
    $all = [];
    foreach (['', ' in mussoorie', ' price', ' booking', ' near me', ' best'] as $suffix) {
        $r = http_request('https://suggestqueries.google.com/complete/search?client=firefox&hl=en&q='.rawurlencode($seed.$suffix));
        foreach (($r['json'][1] ?? []) as $s) $all[] = $s;
    }
    $all = array_values(array_unique($all));
    db_insert('seo_keywords', ['seed'=>$seed,'suggestions'=>json_encode($all, JSON_UNESCAPED_UNICODE)]);
    json_out(['ok'=>true,'suggestions'=>$all]);
}
$tab = get('tab','seo');
$titleLen = mb_strlen(setting('seo_title')); $descLen = mb_strlen(setting('seo_desc'));
$recommended = [
 'hotel in mussoorie','resort in mussoorie','best hotel in mussoorie for couples','luxury resort mussoorie',
 'pet friendly hotel mussoorie','tree house stay mussoorie','hotel near dhanaulti road','mussoorie hotels with bathtub',
 'boutique hotel mussoorie','hotel with jacuzzi in mussoorie','honeymoon resort mussoorie','mussoorie resort with swimming pool',
 'hotels near landour','stay near jabarkhet nature reserve','weekend getaway from delhi hills','farm to table restaurant mussoorie',
];
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs">
  <a href="?m=seo&tab=seo" class="<?= $tab==='seo'?'active':'' ?>">🔍 SEO</a>
  <a href="?m=seo&tab=aeo" class="<?= $tab==='aeo'?'active':'' ?>">🤖 AEO &amp; GEO</a>
  <a href="?m=seo&tab=kw" class="<?= $tab==='kw'?'active':'' ?>">🎯 Keyword Research</a>
</div>

<?php if ($tab === 'aeo'): ?>
<div class="grid2">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save_geo">
    <h3>📍 GEO — Local Search Signals</h3>
    <p class="hint">Adds geo meta tags (geo.position, ICBM, geo.region) and GeoCoordinates schema to every page — helps "near me" searches and map packs.</p>
    <div class="frow">
      <div><label>Latitude</label><input class="input" name="geo_lat" value="<?= e(setting('geo_lat')) ?>" placeholder="30.4265"></div>
      <div><label>Longitude</label><input class="input" name="geo_lng" value="<?= e(setting('geo_lng')) ?>" placeholder="78.1465"></div>
      <div><label>Region code</label><input class="input" name="geo_region" value="<?= e(setting('geo_region')) ?>" placeholder="IN-UT"></div>
      <div><label>Place name</label><input class="input" name="geo_placename" value="<?= e(setting('geo_placename')) ?>" placeholder="Mussoorie, Uttarakhand"></div>
    </div>
    <h3 style="margin-top:18px">🤖 AEO — Answer Engine Optimization (llms.txt)</h3>
    <p class="hint">Served at <a target="_blank" href="<?= base_url('llms.txt') ?>">/llms.txt</a> — a summary AI assistants (ChatGPT, Gemini, Perplexity…) read to describe and recommend your hotel. {BASE_URL} auto-replaces. <b>{AUTO}</b> is replaced with a summary built from the live site (rooms, amenities, FAQs, pages, contact); write your own text before or after it.</p>
    <textarea class="input" name="llms_txt" style="font-family:monospace;min-height:180px"><?= e(setting('llms_txt')) ?></textarea>
    <button class="btn" style="margin-top:12px">Save AEO / GEO</button>
  </form>
  <div class="card"><h3>✅ AEO / GEO Health</h3>
    <table class="tbl">
      <tr><td>llms.txt for AI assistants</td><td><span class="badge green">live</span> <a target="_blank" href="<?= base_url('llms.txt') ?>">view</a></td></tr>
      <tr><td>FAQPage schema (answer engines quote these)</td><td><span class="badge green">auto</span> — manage in FAQs module</td></tr>
      <tr><td>Hotel + GeoCoordinates + amenities schema</td><td><span class="badge green">auto on every page</span></td></tr>
      <tr><td>geo.position / ICBM / geo.region meta</td><td><span class="badge <?= setting('geo_lat')?'green':'orange' ?>"><?= setting('geo_lat')?'live':'set coordinates' ?></span></td></tr>
      <tr><td>Location landing pages ("hotel near X")</td><td><a href="?m=locations">manage →</a></td></tr>
      <tr><td>Google Business reviews on site</td><td><a href="?m=reviews">fetch via Places API →</a></td></tr>
    </table>
    <div class="tip" style="margin-top:12px"><b>Get recommended by Google &amp; AI:</b> ① Verify the site in <a href="https://search.google.com/search-console" target="_blank">Search Console</a> and submit <code>sitemap.xml</code> ② Keep your <a href="https://business.google.com" target="_blank">Google Business Profile</a> updated with the same name/address/phone as this site ③ Answer every FAQ people actually ask (they feed the FAQ schema) ④ Collect fresh Google reviews weekly ⑤ Publish one blog/location page per week from the keyword list.</div>
  </div>
</div>

<?php elseif ($tab === 'kw'): ?>
<div class="grid2">
  <div class="card"><h3>🔎 Live Google Autosuggest Research</h3>
    <p class="hint">Type a seed keyword — we fetch live Google search suggestions (with Mussoorie/price/booking variations) so you target what people actually type.</p>
    <div style="display:flex;gap:8px;margin-top:8px">
      <input class="input" id="kwSeed" placeholder="e.g. resort in mussoorie">
      <button class="btn" onclick="fetchKw()">Suggest</button>
    </div>
    <div id="kwOut" style="margin-top:12px"></div>
    <label style="margin-top:14px">Selected keywords (saved into SEO defaults)</label>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save">
      <input type="hidden" name="seo_title" value="<?= e(setting('seo_title')) ?>">
      <input type="hidden" name="seo_desc" value="<?= e(setting('seo_desc')) ?>">
      <input type="hidden" name="robots_txt" value="<?= e(setting('robots_txt')) ?>">
      <input type="hidden" name="ga_code" value="<?= e(setting('ga_code')) ?>">
      <input type="hidden" name="head_scripts" value="<?= e(setting('head_scripts')) ?>">
      <input type="hidden" name="schema_org" value="<?= e(setting('schema_org')) ?>">
      <textarea class="input" name="seo_keywords" id="kwField" style="min-height:80px"><?= e(setting('seo_keywords')) ?></textarea>
      <button class="btn" style="margin-top:10px">Save Keywords</button>
    </form>
  </div>
  <div class="card"><h3>⭐ Recommended keywords for your property</h3>
    <p class="hint">Hand-picked, high-intent searches for a boutique Mussoorie resort — click to add:</p>
    <div style="margin-top:8px">
      <?php foreach ($recommended as $kw): ?>
        <span class="badge blue" style="margin:3px;cursor:pointer" onclick="addKw('<?= e($kw) ?>')">+ <?= e($kw) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="tip" style="margin-top:14px"><b>Where to use them:</b> page SEO titles &amp; descriptions (CMS/Rooms/Blog editors), blog post topics, and location pages. One primary keyword per page beats ten stuffed ones.</div>
  </div>
</div>
<script>
function fetchKw(){
  const seed = document.getElementById('kwSeed').value.trim(); if (!seed) return;
  const out = document.getElementById('kwOut'); out.innerHTML = '<p class="hint">Fetching Google suggestions…</p>';
  fetch('?m=seo&suggest='+encodeURIComponent(seed)).then(r=>r.json()).then(d=>{
    if (!d.ok || !d.suggestions.length) { out.innerHTML = '<p class="hint">No suggestions found.</p>'; return; }
    out.innerHTML = d.suggestions.map(s=>`<span class="badge blue" style="margin:3px;cursor:pointer" onclick="addKw('${s.replace(/'/g,"\\'")}')">+ ${s}</span>`).join('');
  }).catch(()=>out.innerHTML='<p class="hint">Fetch failed.</p>');
}
function addKw(k){
  const f = document.getElementById('kwField');
  if (f.value.includes(k)) return;
  f.value = f.value ? f.value.replace(/,\s*$/,'') + ', ' + k : k;
  toast('Added: '+k);
}
document.getElementById('kwSeed').addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();fetchKw();} });
</script>

<?php else: ?>
<div class="grid2">
  <div>
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="do" value="save">
      <h3>Site-wide SEO Defaults</h3>
      <label>Default Meta Title <span class="hint" id="tLen">(<?= $titleLen ?>/60 chars)</span></label>
      <input class="input" name="seo_title" id="seoTitle" value="<?= e(setting('seo_title')) ?>">
      <div class="hint" id="tHint"></div>
      <label>Default Meta Description <span class="hint" id="dLen">(<?= $descLen ?>/160 chars)</span></label>
      <textarea class="input" name="seo_desc" id="seoDesc"><?= e(setting('seo_desc')) ?></textarea>
      <div class="hint" id="dHint"></div>
      <label>Default Keywords</label><input class="input" name="seo_keywords" value="<?= e(setting('seo_keywords')) ?>">
      <?php /* round 17: technical SEO */ ?>
      <label>Main web address <span class="hint">(e.g. https://theenglishcountryhouse.com — used for every canonical link, the sitemap and the schema; leave empty to use the address the site is opened on)</span></label>
      <input class="input" name="seo_site_url" value="<?= e(setting('seo_site_url')) ?>" placeholder="https://theenglishcountryhouse.com">
      <div class="frow">
        <div><label>Google Search Console code <span class="hint">(only the code from the HTML-tag method)</span></label><input class="input" name="seo_gsc_verify" value="<?= e(setting('seo_gsc_verify')) ?>"></div>
        <div><label>Bing Webmaster code</label><input class="input" name="seo_bing_verify" value="<?= e(setting('seo_bing_verify')) ?>"></div>
      </div>
      <label>Google Business Profile link <span class="hint">(the "Share" link of your Google Maps listing; joins the hotel data as its map and profile)</span></label>
      <input class="input" name="seo_gbp_url" value="<?= e(setting('seo_gbp_url')) ?>" placeholder="https://maps.app.goo.gl/…">
      <label>Other official profiles <span class="hint">(TripAdvisor, Booking.com, MakeMyTrip … one link per line)</span></label>
      <textarea class="input" name="seo_same_as" style="min-height:56px"><?= e(setting('seo_same_as')) ?></textarea>
      <label>Price range shown to Google <span class="hint">(e.g. ₹₹₹ or ₹8,000 – ₹15,000)</span></label>
      <input class="input" name="seo_price_range" value="<?= e(setting('seo_price_range', '₹₹₹')) ?>">
      <label>robots.txt content ({BASE_URL} auto-replaced)</label>
      <textarea class="input" name="robots_txt" style="font-family:monospace;min-height:110px"><?= e(setting('robots_txt')) ?></textarea>
      <label>Google Analytics / Tag code (full &lt;script&gt; tags)</label>
      <p class="hint" style="margin-top:-4px">Google Tag Manager (<?= e(GTM_ID) ?>) is already on every page. GA4, Google Ads and Meta are set up inside GTM by the marketing team, so leave this empty: a second copy here would count every visit twice.</p>
      <textarea class="input" name="ga_code" style="font-family:monospace;min-height:70px"><?= e(setting('ga_code')) ?></textarea>
      <label>Extra &lt;head&gt; scripts (Search Console verification etc.)</label>
      <textarea class="input" name="head_scripts" style="font-family:monospace;min-height:56px"><?= e(setting('head_scripts')) ?></textarea>
      <label>Additional site-wide Schema.org JSON-LD (optional)</label>
      <textarea class="input" name="schema_org" style="font-family:monospace;min-height:70px"><?= e(setting('schema_org')) ?></textarea>
      <button class="btn" style="margin-top:14px">Save SEO Settings</button>
    </form>
  </div>
  <div class="card"><h3>✅ SEO Health</h3>
    <table class="tbl">
      <tr><td>XML Sitemap</td><td><a target="_blank" href="<?= base_url('sitemap.xml') ?>">sitemap.xml</a> <span class="badge green">auto (incl. location pages)</span></td></tr>
      <tr><td>robots.txt</td><td><a target="_blank" href="<?= base_url('robots.txt') ?>">view</a> <span class="badge green">auto</span></td></tr>
      <tr><td>llms.txt (AI answer engines)</td><td><a target="_blank" href="<?= base_url('llms.txt') ?>">view</a> <span class="badge green">auto</span> — edit in AEO tab</td></tr>
      <tr><td>Schema.org</td><td>One linked graph per page: Hotel/Resort + Geo + amenities, HotelRoom, FAQPage, BreadcrumbList, BlogPosting, Restaurant, TouristAttraction <span class="badge green">auto</span></td></tr>
      <tr><td>Canonical address / 301s / 404 page</td><td><span class="badge <?= setting('seo_site_url') ? 'green' : 'orange' ?>"><?= setting('seo_site_url') ? 'main web address set' : 'set the main web address' ?></span></td></tr>
      <tr><td>Search Console verification</td><td><span class="badge <?= setting('seo_gsc_verify') ? 'green' : 'orange' ?>"><?= setting('seo_gsc_verify') ? 'tag live' : 'add the code' ?></span></td></tr>
      <tr><td>Open Graph / Twitter cards / Canonical</td><td>All pages <span class="badge green">auto</span></td></tr>
      <tr><td>Per-page SEO</td><td>Editable on every Room, Page, Blog post, Category &amp; Location page</td></tr>
      <tr><td>PWA / mobile-app view</td><td><span class="badge <?= setting('pwa_enabled')==='1'?'green':'gray' ?>"><?= setting('pwa_enabled')==='1'?'enabled':'off' ?></span> manifest + service worker + bottom nav</td></tr>
    </table>
    <p class="hint" style="margin-top:10px">Submit the sitemap in <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a> to get indexed faster.</p>
  </div>
</div>
<script>
function len(el, out, ideal, max){
  const n = el.value.length;
  out.textContent = `(${n}/${max} chars)`;
  return n < ideal[0] ? '⚠ Too short — aim for '+ideal[0]+'–'+max+' characters.' : (n > max ? '⚠ Too long — Google will truncate it.' : '✅ Good length.');
}
const st=document.getElementById('seoTitle'), sd=document.getElementById('seoDesc');
function upd(){ document.getElementById('tHint').textContent = len(st, document.getElementById('tLen'), [30], 60);
                document.getElementById('dHint').textContent = len(sd, document.getElementById('dLen'), [70], 160); }
st.addEventListener('input',upd); sd.addEventListener('input',upd); upd();
</script>
<?php endif; ?>
