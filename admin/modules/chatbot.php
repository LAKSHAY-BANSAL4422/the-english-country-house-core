<?php
/** Chatbot auto-responses (Q&A) + agent canned replies */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'save_qa':
            $data = ['keywords'=>post('keywords'),'answer'=>post('answer'),'sort'=>(int)post('sort'),'active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('chatbot_qa', $data, 'id=?', [(int)post('id')]); $flash='Auto-response saved.'; }
            else { db_insert('chatbot_qa', $data); $flash='Auto-response added.'; }
            break;
        case 'del_qa': db_delete('chatbot_qa','id=?',[(int)post('id')]); $flash='Deleted.'; break;
        case 'save_cr':
            $data = ['title'=>post('title'),'content'=>post('content'),'keywords'=>post('keywords'),'active'=>(int)(bool)post('active')];
            if ((int)post('id')) { db_update('canned_replies', $data, 'id=?', [(int)post('id')]); $flash='Canned reply saved.'; }
            else { db_insert('canned_replies', $data); $flash='Canned reply added.'; }
            break;
        case 'del_cr': db_delete('canned_replies','id=?',[(int)post('id')]); $flash='Deleted.'; break;
    }
}
$tab = get('tab','qa');
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs">
  <a href="?m=chatbot&tab=qa" class="<?= $tab==='qa'?'active':'' ?>">🤖 Bot Auto-Responses</a>
  <a href="?m=chatbot&tab=cr" class="<?= $tab==='cr'?'active':'' ?>">⚡ Agent Canned Replies</a>
</div>

<?php if ($tab === 'qa'):
$rows = q_all("SELECT * FROM chatbot_qa ORDER BY sort,id");
$edit = get('edit') ? q_row("SELECT * FROM chatbot_qa WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2">
  <div class="card"><h3>Auto-responses (bot answers when visitor message contains a keyword)</h3>
    <table class="tbl"><tr><th>Keywords</th><th>Answer</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td style="max-width:180px"><?= e($r['keywords']) ?><?= $r['active']?'':' <span class="badge red">off</span>' ?></td>
      <td style="max-width:320px"><?= e(mb_substr($r['answer'],0,110)) ?>…</td>
      <td><a class="btn sm ghost" href="?m=chatbot&tab=qa&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_qa"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table></div>
  <div class="card"><h3><?= $edit ? 'Edit' : 'Add' ?> Auto-response</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_qa"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Trigger keywords (comma separated)</label><input class="input" name="keywords" value="<?= e($edit['keywords'] ?? '') ?>" required placeholder="price,cost,tariff">
      <label>Bot answer</label><textarea class="input" name="answer" style="min-height:110px" required><?= e($edit['answer'] ?? '') ?></textarea>
      <div class="frow"><div><label>Sort (lower = higher priority)</label><input class="input" type="number" name="sort" value="<?= $edit['sort'] ?? 0 ?>"></div></div>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save</button></form></div>
</div>
<?php else:
$rows = q_all("SELECT * FROM canned_replies ORDER BY id");
$edit = get('edit') ? q_row("SELECT * FROM canned_replies WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2">
  <div class="card"><h3>Canned replies (suggested to agents; {name}, {agent}, {phone} auto-fill)</h3>
    <table class="tbl"><tr><th>Title</th><th>Reply</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><b><?= e($r['title']) ?></b><br><small class="hint"><?= e($r['keywords']) ?></small></td>
      <td style="max-width:320px"><?= e(mb_substr($r['content'],0,110)) ?>…</td>
      <td><a class="btn sm ghost" href="?m=chatbot&tab=cr&edit=<?= $r['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="do" value="del_cr"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table></div>
  <div class="card"><h3><?= $edit ? 'Edit' : 'Add' ?> Canned Reply</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_cr"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Title</label><input class="input" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
      <label>Suggest when visitor mentions (keywords)</label><input class="input" name="keywords" value="<?= e($edit['keywords'] ?? '') ?>" placeholder="price,rate">
      <label>Reply text</label><textarea class="input" name="content" style="min-height:110px" required><?= e($edit['content'] ?? '') ?></textarea>
      <label class="inline"><input type="checkbox" name="active" value="1" <?= !$edit || $edit['active']?'checked':'' ?>> Active</label><br>
      <button class="btn" style="margin-top:12px">Save</button></form></div>
</div>
<?php endif; ?>
