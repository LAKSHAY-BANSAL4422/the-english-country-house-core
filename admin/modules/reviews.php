<?php
/** Reviews: Google Places fetch + manual reviews */
$flash = ''; $flashOk = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'save':
            $data = ['source'=>'manual','author'=>post('author'),'rating'=>min(5,max(1,(float)post('rating'))),
                     'content'=>post('content'),'review_date'=>post('review_date') ?: date('Y-m-d'),
                     'sort'=>(int)post('sort'),'active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('reviews', $data, 'id=?', [(int)post('id')]); $flash='Review updated.'; }
            else { db_insert('reviews', $data); $flash='Review added.'; }
            break;
        case 'delete': db_delete('reviews','id=?',[(int)post('id')]); $flash='Deleted.'; break;
        case 'toggle': q("UPDATE reviews SET active=1-active WHERE id=?",[(int)post('id')]); $flash='Toggled.'; break;
        case 'fetch_google':
            $key = setting('google_places_key'); $pid = setting('google_place_id');
            if (!$key || !$pid) { $flash='Set Google Places API key and Place ID in Site Settings → Google first.'; $flashOk=false; break; }
            $r = http_request('https://maps.googleapis.com/maps/api/place/details/json?place_id='.urlencode($pid).'&fields=reviews,rating,user_ratings_total&reviews_sort=newest&key='.urlencode($key));
            $reviews = $r['json']['result']['reviews'] ?? [];
            if (!$reviews) { $flash='Google returned no reviews: '.($r['json']['status'] ?? 'HTTP '.$r['code']).' '.($r['json']['error_message'] ?? ''); $flashOk=false; break; }
            $n=0;
            foreach ($reviews as $g) {
                $ext = 'g_'.md5(($g['author_name'] ?? '').($g['time'] ?? ''));
                if (q_row("SELECT id FROM reviews WHERE external_id=?", [$ext])) continue;
                db_insert('reviews', ['source'=>'google','author'=>$g['author_name'] ?? 'Google User','avatar'=>$g['profile_photo_url'] ?? '',
                  'rating'=>(float)($g['rating'] ?? 5),'content'=>$g['text'] ?? '','review_date'=>date('Y-m-d', $g['time'] ?? time()),
                  'external_id'=>$ext,'active'=>1]);
                $n++;
            }
            $flash = "$n new Google review(s) imported.";
            break;
    }
}
$rows = q_all("SELECT * FROM reviews ORDER BY sort, id DESC");
$edit = get('edit') ? q_row("SELECT * FROM reviews WHERE id=?", [(int)get('edit')]) : null;
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Reviews (shown on home page)</h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="fetch_google">
        <button class="btn sec sm"><i class="fa-brands fa-google"></i> Fetch Google Reviews</button></form>
    </div>
    <table class="tbl"><tr><th>Author</th><th>Rating</th><th>Review</th><th>Source</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><b><?= e($r['author']) ?></b><br><small class="hint"><?= $r['review_date'] ?></small></td>
      <td><?= $r['rating'] ?>⭐</td><td style="max-width:280px"><?= e(mb_substr($r['content'],0,100)) ?>…</td>
      <td><span class="badge <?= $r['source']==='google'?'blue':'gray' ?>"><?= e($r['source']) ?></span></td>
      <td><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn sm <?= $r['active']?'green':'ghost' ?>"><?= $r['active']?'visible':'hidden' ?></button></form>
        <?php if ($r['source']==='manual'): ?><a class="btn sm ghost" href="?m=reviews&edit=<?= $r['id'] ?>">Edit</a><?php endif; ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete review?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit Review' : 'Add Manual Review' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Guest Name</label><input class="input" name="author" value="<?= e($edit['author'] ?? '') ?>" required>
      <div class="frow"><div><label>Rating (1–5)</label><input class="input" type="number" step="0.5" min="1" max="5" name="rating" value="<?= $edit['rating'] ?? 5 ?>"></div>
      <div><label>Date</label><input class="input" type="date" name="review_date" value="<?= $edit['review_date'] ?? date('Y-m-d') ?>"></div></div>
      <label>Review Text</label><textarea class="input" name="content" style="min-height:110px" required><?= e($edit['content'] ?? '') ?></textarea>
      <div class="frow"><div><label>Sort</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div></div>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Show on website</label><br>
      <button class="btn" style="margin-top:12px">Save</button></form>
    <p class="hint" style="margin-top:12px">To auto-fetch Google Business reviews, add your <b>Places API key</b> and <b>Place ID</b> under Site Settings → Google, then click "Fetch Google Reviews". A daily cron also refreshes them automatically.</p>
  </div>
</div>
