<?php
/** Gallery manager: multi-upload, captions, categories, sort, hide/show */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'upload') {
        $n = 0;
        // support multi-file input gallery_files[]
        if (!empty($_FILES['gallery_files']['name'][0])) {
            foreach ($_FILES['gallery_files']['name'] as $i => $nm) {
                if ($_FILES['gallery_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $_FILES['__one'] = [
                    'name'=>$_FILES['gallery_files']['name'][$i], 'type'=>$_FILES['gallery_files']['type'][$i],
                    'tmp_name'=>$_FILES['gallery_files']['tmp_name'][$i], 'error'=>UPLOAD_ERR_OK, 'size'=>$_FILES['gallery_files']['size'][$i],
                ];
                $p = handle_upload('__one', 'gallery', ['jpg','jpeg','png','webp','gif']);
                if ($p) { db_insert('gallery_images', ['image'=>$p,'title'=>post('title'),'category'=>post('category') ?: 'General','sort'=>(int)post('sort'),'active'=>1]); $n++; }
            }
        }
        $flash = "$n image(s) added to the gallery.";
    }
    if (post('do') === 'add_paths') {
        $n = 0;
        foreach ((array)($_POST['images'] ?? []) as $pth) {
            $pth = preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$pth);
            if ($pth === '' || !is_file(ROOT_PATH.'/'.$pth)) continue;
            db_insert('gallery_images', ['image'=>$pth,'title'=>post('title'),'category'=>post('category') ?: 'General','sort'=>(int)post('sort'),'active'=>1]); $n++;
        }
        $flash = "$n image(s) added to the gallery.";
    }
    if (post('do') === 'update') {
        db_update('gallery_images', ['title'=>post('title'),'category'=>post('category') ?: 'General','sort'=>(int)post('sort')], 'id=?', [(int)post('id')]);
        $flash = 'Image updated.';
    }
    if (post('do') === 'toggle') { q("UPDATE gallery_images SET active=1-active WHERE id=?", [(int)post('id')]); $flash='Visibility toggled.'; }
    if (post('do') === 'delete') { db_delete('gallery_images','id=?',[(int)post('id')]); $flash='Image removed.'; }
}
$cat = get('cat');
$imgs = $cat ? q_all("SELECT * FROM gallery_images WHERE category=? ORDER BY sort, id DESC", [$cat])
             : q_all("SELECT * FROM gallery_images ORDER BY sort, id DESC");
$cats = q_all("SELECT category, COUNT(*) c FROM gallery_images GROUP BY category ORDER BY category");
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<?php require_once ROOT_PATH.'/lib/importer.php'; $TARGETS = import_targets(); ?>
<div class="card imp-card" style="margin-bottom:16px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <h3 style="margin:0">⬇ Import photos</h3>
    <div class="tabs" style="margin:0;border:0">
      <a href="#" class="imp-src active" data-src="drive">📁 Google Drive folder</a>
      <a href="#" class="imp-src" data-src="web">🌐 Old website</a>
    </div>
  </div>
  <div id="impSrcDrive">
    <p class="hint" style="margin:8px 0 10px">Photos shared with you on Google Drive? Open the folder in Drive → <b>Share</b> → General access → <b>"Anyone with the link" (Viewer)</b> → Copy link → paste it here. Sub-folders are listed too. (Optional: a Drive API key under Settings → Google APIs makes very large folders faster.)</p>
    <div class="frow4" style="align-items:end">
      <div style="grid-column:span 3"><label>Google Drive folder link</label><input class="input" id="impDrive" placeholder="https://drive.google.com/drive/folders/1AbC…"></div>
      <div><label>Save into category</label><input class="input" id="impCatD" value="Drive photos" list="galcats"></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
      <button class="btn" type="button" id="impDriveScan">📁 List photos in folder</button>
      <span id="impFolders" class="hint"></span>
    </div>
  </div>
  <div id="impSrcWeb" hidden>
    <small class="hint">Runs on your hosting · WordPress media library + every page · duplicates skipped</small>
    <div class="frow4" style="margin-top:10px;align-items:end">
      <div style="grid-column:span 2"><label>Website address</label><input class="input" id="impUrl" value="https://theenglishcountryhouse.com/"></div>
      <div><label>Pages to scan</label><input class="input" id="impPages" type="number" min="1" max="60" value="25"></div>
      <div><label>Save into category</label><input class="input" id="impCat" value="Old website" list="galcats"></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
      <button class="btn" type="button" id="impScan">🔎 Scan website</button>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
    <label class="inline hint">Skip images narrower than <input class="input" id="impMinW" type="number" value="300" min="50" style="width:80px;padding:4px 6px;display:inline-block"> px</label>
    <span class="hint" id="impStatus"></span>
  </div>
  <div id="impFound" hidden>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:12px 0 8px">
      <b id="impCount"></b>
      <button class="btn sm ghost" type="button" id="impAll">Select all</button>
      <button class="btn sm ghost" type="button" id="impNone">Select none</button>
      <button class="btn sm green" type="button" id="impGo">⬇ Import selected into Gallery</button>
    </div>
    <div class="imp-prog" hidden><div></div></div>
    <div class="imp-grid" id="impGrid"></div>
  </div>
</div>

<div class="modalx" id="assignModal"><div class="mbox" style="max-width:520px"><button class="mclose" onclick="this.closest('.modalx').classList.remove('show')">&times;</button>
  <h3>📌 Use this image on the website</h3>
  <img id="asImg" src="" alt="" style="width:100%;max-height:220px;object-fit:cover;border-radius:10px;margin:8px 0">
  <label>Place it as…</label>
  <select class="input" id="asTarget">
    <?php foreach ($TARGETS as $grp => $opts): ?><optgroup label="<?= e($grp) ?>"><?php foreach ($opts as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
  </select>
  <div style="display:flex;gap:8px;margin-top:12px"><button class="btn" type="button" id="asGo">Apply</button><span class="hint" id="asMsg" style="align-self:center"></span></div>
  <p class="hint" style="margin-top:10px">Tip: use one image as many times as you like (e.g. a room main photo <i>and</i> a home hero).</p>
</div></div>

<div class="grid2" style="grid-template-columns:1.6fr 1fr">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
      <h3>🖼 Gallery (<?= count($imgs) ?>)</h3>
      <div class="filters" style="margin:0">
        <a class="btn sm <?= !$cat?'sec':'ghost' ?>" href="?m=gallery">All</a>
        <?php foreach ($cats as $c): ?><a class="btn sm <?= $cat===$c['category']?'sec':'ghost' ?>" href="?m=gallery&cat=<?= urlencode($c['category']) ?>"><?= e($c['category']) ?> (<?= $c['c'] ?>)</a><?php endforeach; ?>
      </div>
    </div>
    <div class="gal-grid">
      <?php foreach ($imgs as $g): ?>
      <div class="gal-item <?= $g['active']?'':'off' ?>">
        <img src="<?= base_url($g['image']) ?>" alt="">
        <div class="gi-body">
          <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="update"><input type="hidden" name="id" value="<?= $g['id'] ?>">
            <input class="input" name="title" value="<?= e($g['title']) ?>" placeholder="Caption" style="margin-bottom:4px;padding:5px 8px">
            <div style="display:flex;gap:4px">
              <input class="input" name="category" value="<?= e($g['category']) ?>" style="padding:5px 8px" list="galcats">
              <input class="input" type="number" name="sort" value="<?= $g['sort'] ?>" style="width:52px;padding:5px 6px" title="Sort">
            </div>
            <div style="display:flex;gap:4px;margin-top:6px">
              <button class="btn sm" style="flex:1">Save</button>
          </form>
              <form method="post" style="display:contents"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button class="btn sm <?= $g['active']?'green':'ghost' ?>" title="show/hide"><?= $g['active']?'👁':'🚫' ?></button></form>
              <button type="button" class="btn sm ghost" data-assign="<?= $g['id'] ?>" data-img="<?= e(base_url($g['image'])) ?>" title="Use on website">📌</button>
              <form method="post" style="display:contents" onsubmit="return confirm('Remove image?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button class="btn sm danger">✕</button></form>
            </div>
        </div>
      </div>
      <?php endforeach; if(!$imgs): ?><p class="hint">No images yet — upload some →</p><?php endif; ?>
    </div>
    <datalist id="galcats"><?php foreach ($cats as $c): ?><option><?= e($c['category']) ?></option><?php endforeach; ?><option>Rooms</option><option>Dining</option><option>Amenities</option><option>Views</option><option>Events</option></datalist>
  </div>
  <div class="card"><h3>⬆ Upload Images (multiple at once)</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="add_paths">
      <label>Choose images (JPG/PNG/WebP — select many; resized &amp; compressed automatically)</label>
      <div data-uploader data-name="images[]" data-multiple="1" data-max="40" data-subdir="gallery" data-value="[]"></div>
      <div class="frow">
        <div><label>Caption (applied to all)</label><input class="input" name="title" placeholder="optional"></div>
        <div><label>Category</label><input class="input" name="category" value="General" list="galcats"></div>
        <div><label>Sort</label><input class="input" type="number" name="sort" value="0"></div>
      </div>
      <button class="btn" style="margin-top:12px">Add To Gallery</button>
    </form>
    <div class="tip" style="margin-top:14px"><b>Where the gallery shows:</b><br>
      • Public page: <a target="_blank" href="<?= base_url('gallery.php') ?>"><?= base_url('gallery.php') ?></a> (add it to the menu in Header &amp; Footer)<br>
      • Any page via the Section Builder → add a <b>Image Gallery</b> section (leave content empty = pulls these images; type a category name to show only that category)<br>
      • Hidden images (🚫) stay stored but never display.</div>
  </div>
</div>

<script>
(function(){
  const $ = id => document.getElementById(id);
  function post(data){
    const fd = new FormData(); fd.append('csrf', ADMIN.csrf);
    Object.entries(data).forEach(([k,v]) => Array.isArray(v) ? v.forEach(x => fd.append(k+'[]', x)) : fd.append(k, v));
    return fetch(ADMIN.base + '/admin/ajax.php', {method:'POST', body:fd}).then(r => r.json());
  }
  let found = [], src = 'drive';
  document.querySelectorAll('.imp-src').forEach(a => a.addEventListener('click', e => { e.preventDefault(); src = a.dataset.src; document.querySelectorAll('.imp-src').forEach(x => x.classList.toggle('active', x === a)); $('impSrcDrive').hidden = src !== 'drive'; $('impSrcWeb').hidden = src !== 'web'; }));
  function showFound(list, note) {
    found = list;
    $('impCount').textContent = found.length + ' image(s) found';
    $('impGrid').innerHTML = found.map((im, i) =>
      '<label class="imp-it' + (im.have ? ' have' : '') + '"><input type="checkbox" data-i="' + i + '"' + (im.have ? '' : ' checked') + '>' +
      '<img loading="lazy" src="' + (im.thumb || im.url).replace(/"/g,'&quot;') + '" onerror="this.closest(\'label\').classList.add(\'bad\')">' +
      '<span>' + (im.have ? '✔ already in gallery' : (im.alt || im.name || im.url.split('/').pop())).replace(/</g,'&lt;') + '</span></label>').join('');
    $('impFound').hidden = false; if (note) $('impStatus').textContent = note;
  }
  $('impDriveScan').onclick = () => {
    const b = $('impDriveScan'); b.disabled = true; $('impStatus').textContent = '⏳ Reading the Drive folder…'; $('impFolders').innerHTML = '';
    post({action:'import_drive', url:$('impDrive').value}).then(d => {
      b.disabled = false;
      if (!d.ok) { $('impStatus').textContent = '❌ ' + (d.error || 'Failed'); return; }
      if (d.folders && d.folders.length) $('impFolders').innerHTML = 'Sub-folders: ' + d.folders.map(f => '<a href="#" data-folder="' + f.id + '">📁 ' + f.name.replace(/</g,'&lt;') + '</a>').join(' · ');
      showFound(d.images, (d.images.length ? '✅ ' + d.images.length + ' photo(s) in ' + (d.name ? '"' + d.name + '"' : 'the folder') : '⚠ No photos found') + (d.errors.length ? ' · ' + d.errors.join('; ') : ''));
    }).catch(() => { b.disabled = false; $('impStatus').textContent = '❌ Request failed — try again.'; });
  };
  $('impFolders').addEventListener('click', e => { const a = e.target.closest('[data-folder]'); if (!a) return; e.preventDefault(); $('impDrive').value = 'https://drive.google.com/drive/folders/' + a.dataset.folder; $('impDriveScan').click(); });
  $('impScan').onclick = () => {
    const b = $('impScan'); b.disabled = true; $('impStatus').textContent = '⏳ Scanning — this can take a minute for big sites…';
    post({action:'import_scan', url:$('impUrl').value, pages:$('impPages').value}).then(d => {
      b.disabled = false;
      if (!d.ok) { $('impStatus').textContent = '❌ ' + (d.error || 'Scan failed'); return; }
      found = d.images;
      $('impStatus').textContent = '✅ Scanned ' + d.pages + ' page(s)' + (d.wp ? ' + WordPress media library (' + d.wp + ')' : '') + (d.errors.length ? ' · ' + d.errors.join('; ') : '');
      $('impCount').textContent = found.length + ' image(s) found';
      $('impGrid').innerHTML = found.map((im, i) =>
        '<label class="imp-it' + (im.have ? ' have' : '') + '"><input type="checkbox" data-i="' + i + '"' + (im.have ? '' : ' checked') + '>' +
        '<img loading="lazy" src="' + im.url.replace(/"/g,'&quot;') + '" onerror="this.closest(\'label\').classList.add(\'bad\')">' +
        '<span>' + (im.have ? '✔ already in gallery' : (im.alt || im.url.split('/').pop())).replace(/</g,'&lt;') + '</span></label>').join('');
      $('impFound').hidden = false;
    }).catch(() => { b.disabled = false; $('impStatus').textContent = '❌ Request failed (server timeout?) — try fewer pages.'; });
  };
  $('impAll').onclick = () => document.querySelectorAll('#impGrid input').forEach(c => c.checked = true);
  $('impNone').onclick = () => document.querySelectorAll('#impGrid input').forEach(c => c.checked = false);
  $('impGo').onclick = async () => {
    const pick = [...document.querySelectorAll('#impGrid input:checked')].map(c => found[+c.dataset.i]);
    if (!pick.length) return alert('Select at least one image');
    const bar = document.querySelector('.imp-prog'); bar.hidden = false;
    let done = 0, ok = 0, skipped = 0;
    $('impGo').disabled = true;
    for (let i = 0; i < pick.length; i += 6) {
      const batch = pick.slice(i, i + 6);
      try {
        const d = await post({action:'import_fetch', category:(src === 'drive' ? $('impCatD') : $('impCat')).value, min_w:$('impMinW').value,
          urls:batch.map(x => x.url), fallbacks:batch.map(x => x.fallback || ''), alts:batch.map(x => x.alt || '')});
        (d.results || []).forEach(r => { r.ok ? ok++ : skipped++;
          const idx = found.findIndex(x => x.url === r.url); const el = idx >= 0 ? document.querySelector('#impGrid label:nth-child(' + (idx + 1) + ')') : null;
          if (el) { el.classList.add(r.ok ? 'done' : 'skip'); el.querySelector('span').textContent = r.ok ? '✔ imported ' + r.info : '— ' + r.info;
            if (r.ok && r.row) { el.querySelector('input').checked = false; el.insertAdjacentHTML('beforeend', '<button type="button" class="btn sm" data-assign="' + r.row.id + '" data-img="' + r.row.url + '">📌 Use as…</button>'); } }
        });
      } catch (e) { skipped += batch.length; }
      done += batch.length;
      bar.firstElementChild.style.width = Math.round(done / pick.length * 100) + '%';
      $('impStatus').textContent = '⬇ ' + done + '/' + pick.length + ' processed · ' + ok + ' imported · ' + skipped + ' skipped';
    }
    $('impGo').disabled = false;
    toast('🖼 ' + ok + ' image(s) imported into the Gallery — click 📌 on any to place it on the website', 'fa-images');
  };
  // assign
  let asId = 0;
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-assign]'); if (!b) return;
    e.preventDefault(); asId = b.dataset.assign; $('asImg').src = b.dataset.img; $('asMsg').textContent = '';
    $('assignModal').classList.add('show');
  });
  $('asGo').onclick = () => post({action:'import_assign', id:asId, target:$('asTarget').value}).then(d => {
    $('asMsg').textContent = (d.ok ? '✅ ' : '❌ ') + (d.message || d.error || '');
    if (d.ok) toast('📌 ' + d.message, 'fa-image');
  });
})();
</script>
