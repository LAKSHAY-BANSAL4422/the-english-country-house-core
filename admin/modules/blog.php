<?php
/** Blog: posts + categories with SEO */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('do')) {
        case 'save_post':
            $data = ['title'=>post('title'),'slug'=>slugify(post('slug') ?: post('title')),
              'category_id'=>(int)post('category_id') ?: null,'excerpt'=>post('excerpt'),'content'=>$_POST['content'] ?? '',
              'author'=>post('author') ?: $ADMIN['name'],'tags'=>post('tags'),'status'=>post('status')==='published'?'published':'draft',
              'seo_title'=>post('seo_title'),'seo_desc'=>post('seo_desc'),'seo_keywords'=>post('seo_keywords')];
            if ($data['status']==='published' && !post('published_at')) $data['published_at'] = date('Y-m-d H:i:s');
            elseif (post('published_at')) $data['published_at'] = post('published_at');
            $data['image'] = preg_replace('#[^A-Za-z0-9_./-]#', '', post('image'));
            if ((int)post('id')) { db_update('blog_posts', $data, 'id=?', [(int)post('id')]); $flash='Post saved.'; }
            else { db_insert('blog_posts', $data); $flash='Post created.'; }
            break;
        case 'delete_post': db_delete('blog_posts','id=?',[(int)post('id')]); $flash='Post deleted.'; break;
        case 'save_cat':
            $data = ['name'=>post('name'),'slug'=>slugify(post('slug') ?: post('name')),'description'=>post('description'),
                     'seo_title'=>post('seo_title'),'seo_desc'=>post('seo_desc'),'active'=>1];
            if ((int)post('id')) { db_update('blog_categories', $data, 'id=?', [(int)post('id')]); $flash='Category saved.'; }
            else { db_insert('blog_categories', $data); $flash='Category added.'; }
            break;
        case 'delete_cat': db_delete('blog_categories','id=?',[(int)post('id')]); $flash='Category deleted.'; break;
    }
}
$tab = get('tab','posts');
$cats = q_all("SELECT c.*,(SELECT COUNT(*) FROM blog_posts p WHERE p.category_id=c.id) cnt FROM blog_categories c ORDER BY c.name");
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs"><a href="?m=blog&tab=posts" class="<?= $tab==='posts'?'active':'' ?>">Posts</a><a href="?m=blog&tab=cats" class="<?= $tab==='cats'?'active':'' ?>">Categories</a></div>

<?php if ($tab === 'cats'):
$editC = get('edit') ? q_row("SELECT * FROM blog_categories WHERE id=?", [(int)get('edit')]) : null; ?>
<div class="grid2">
  <div class="card"><h3>Categories</h3>
    <table class="tbl"><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr>
    <?php foreach ($cats as $c): ?>
    <tr><td><b><?= e($c['name']) ?></b></td><td><?= e($c['slug']) ?></td><td><?= $c['cnt'] ?></td>
      <td><a class="btn sm ghost" href="?m=blog&tab=cats&edit=<?= $c['id'] ?>">Edit</a>
      <form method="post" style="display:inline" onsubmit="return confirm('Delete category?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete_cat"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
    <?php endforeach; ?></table></div>
  <div class="card"><h3><?= $editC ? 'Edit Category' : 'Add Category' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save_cat"><input type="hidden" name="id" value="<?= $editC['id'] ?? '' ?>">
      <label>Name</label><input class="input" name="name" value="<?= e($editC['name'] ?? '') ?>" required>
      <label>Slug</label><input class="input" name="slug" value="<?= e($editC['slug'] ?? '') ?>">
      <label>Description</label><textarea class="input" name="description"><?= e($editC['description'] ?? '') ?></textarea>
      <div class="frow"><div><label>SEO Title</label><input class="input" name="seo_title" value="<?= e($editC['seo_title'] ?? '') ?>"></div>
      <div><label>SEO Description</label><input class="input" name="seo_desc" value="<?= e($editC['seo_desc'] ?? '') ?>"></div></div>
      <button class="btn" style="margin-top:12px">Save</button></form></div>
</div>

<?php else:
$edit = get('edit') ? q_row("SELECT * FROM blog_posts WHERE id=?", [(int)get('edit')]) : null;
if ($edit || get('new')): ?>
<p><a href="?m=blog">← All posts</a></p>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
  <input type="hidden" name="do" value="save_post"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <div class="frow3">
    <div><label>Title</label><input class="input" name="title" value="<?= e($edit['title'] ?? '') ?>" required></div>
    <div><label>Slug</label><input class="input" name="slug" value="<?= e($edit['slug'] ?? '') ?>"></div>
    <div><label>Category</label><select class="input" name="category_id"><option value="">—</option>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['category_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><label>Author</label><input class="input" name="author" value="<?= e($edit['author'] ?? $ADMIN['name']) ?>"></div>
    <div><label>Status</label><select class="input" name="status"><option value="draft" <?= ($edit['status'] ?? '')==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= ($edit['status'] ?? '')==='published'?'selected':'' ?>>Published</option></select></div>
    <div><label>Publish date</label><input class="input" type="datetime-local" name="published_at" value="<?= $edit && $edit['published_at'] ? date('Y-m-d\TH:i', strtotime($edit['published_at'])) : '' ?>"></div>
  </div>
  <label>Featured Image</label>
  <div data-uploader data-name="image" data-subdir="blog" data-value="<?= e($edit['image'] ?? '') ?>"></div>
  <label>Excerpt</label><textarea class="input" name="excerpt" style="min-height:56px"><?= e($edit['excerpt'] ?? '') ?></textarea>
  <label>Content (visual editor)</label><textarea class="input wysiwyg" name="content" data-minh="320px"><?= e($edit['content'] ?? '') ?></textarea>
  <label>Tags (comma separated)</label><input class="input" name="tags" value="<?= e($edit['tags'] ?? '') ?>">
  <h3 style="margin-top:16px">SEO</h3>
  <div class="frow"><div><label>SEO Title</label><input class="input" name="seo_title" value="<?= e($edit['seo_title'] ?? '') ?>"></div>
  <div><label>SEO Keywords</label><input class="input" name="seo_keywords" value="<?= e($edit['seo_keywords'] ?? '') ?>"></div></div>
  <label>SEO Description</label><textarea class="input" name="seo_desc" style="min-height:56px"><?= e($edit['seo_desc'] ?? '') ?></textarea>
  <button class="btn" style="margin-top:14px">Save Post</button>
</form>
<?php else:
$posts = q_all("SELECT p.*, c.name cat FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id ORDER BY p.id DESC"); ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;margin-bottom:12px"><h3>Posts</h3><a class="btn" href="?m=blog&new=1">+ New Post</a></div>
  <table class="tbl"><tr><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Date</th><th></th></tr>
  <?php foreach ($posts as $p): ?>
  <tr><td><b><?= e($p['title']) ?></b></td><td><?= e($p['cat'] ?: '—') ?></td>
    <td><span class="badge <?= $p['status']==='published'?'green':'orange' ?>"><?= e($p['status']) ?></span></td>
    <td><?= $p['views'] ?></td><td><?= $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '—' ?></td>
    <td><a class="btn sm ghost" href="?m=blog&edit=<?= $p['id'] ?>">Edit</a>
      <a class="btn sm ghost" target="_blank" href="<?= base_url('post.php?slug='.$p['slug']) ?>">View</a>
      <form method="post" style="display:inline" onsubmit="return confirm('Delete post?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete_post"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm danger">✕</button></form></td></tr>
  <?php endforeach; ?></table>
</div>
<?php endif; endif; ?>
