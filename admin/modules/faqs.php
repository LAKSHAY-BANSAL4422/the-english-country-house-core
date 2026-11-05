<?php
/** FAQs CRUD */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $data = ['question'=>post('question'),'answer'=>post('answer'),'category'=>post('category') ?: 'General','sort'=>(int)post('sort'),'active'=>(int)(bool)post('active')];
        if ((int)post('id')) { db_update('faqs', $data, 'id=?', [(int)post('id')]); $flash='FAQ updated.'; }
        else { db_insert('faqs', $data); $flash='FAQ added.'; }
    }
    if (post('do') === 'delete') { db_delete('faqs','id=?',[(int)post('id')]); $flash='Deleted.'; }
}
$rows = q_all("SELECT * FROM faqs ORDER BY category, sort, id");
$edit = get('edit') ? q_row("SELECT * FROM faqs WHERE id=?", [(int)get('edit')]) : null;
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <div class="card"><h3>FAQs (also power AEO — answer engines read the FAQ schema)</h3>
    <table class="tbl"><tr><th>Question</th><th>Category</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><b><?= e($r['question']) ?></b><br><small class="hint"><?= e(mb_substr($r['answer'],0,80)) ?>…</small></td>
      <td><?= e($r['category']) ?></td>
      <td><span class="badge <?= $r['active']?'green':'red' ?>"><?= $r['active']?'live':'hidden' ?></span></td>
      <td><a class="btn sm ghost" href="?m=faqs&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table>
  </div>
  <div class="card"><h3><?= $edit ? 'Edit FAQ' : 'Add FAQ' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Question</label><input class="input" name="question" value="<?= e($edit['question'] ?? '') ?>" required>
      <label>Answer</label><textarea class="input" name="answer" style="min-height:110px" required><?= e($edit['answer'] ?? '') ?></textarea>
      <div class="frow"><div><label>Category</label><input class="input" name="category" value="<?= e($edit['category'] ?? 'General') ?>" list="faqcats">
        <datalist id="faqcats"><?php foreach (array_unique(array_column($rows,'category')) as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></datalist></div>
      <div><label>Sort</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div></div>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save</button>
      <?php if ($edit): ?><a class="btn ghost" href="?m=faqs">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
