<?php
require_once __DIR__.'/config.php'; db();
require_once __DIR__.'/includes/redesign_content.php';   // round 16: the post photo (rd_blog_image) is needed before the header
$post = q_row("SELECT p.*, c.name cat_name, c.slug cat_slug FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published'", [get('slug')]);
if (!$post) { require __DIR__.'/404.php'; exit; }   // round 17: a real 404 page, not a redirect to the blog
q("UPDATE blog_posts SET views=views+1 WHERE id=?", [(int)$post['id']]);
require_once __DIR__.'/includes/seo.php';
/* round 17 (technical SEO): full BlogPosting data (author, publisher logo, dates, section, tags, word count), article share tags, breadcrumb */
$postUrl = seo_abs(u('post.php?slug='.$post['slug']));
$postImg = rd_blog_image_path($post);
$postPub = date('c', strtotime($post['published_at'] ?: $post['created_at']));
$postMod = date('c', max(strtotime($post['published_at'] ?: $post['created_at']), strtotime($post['created_at']), (int)@filemtime(ROOT_PATH.'/'.$postImg)));
$postTags = array_values(array_filter(array_map('trim', explode(',', (string)$post['tags']))));
$article = ['@type'=>'BlogPosting', '@id'=>$postUrl.'#article', 'headline'=>mb_substr($post['title'], 0, 110), 'description'=>seo_text((string)($post['seo_desc'] ?: $post['excerpt'])),
  'url'=>$postUrl, 'mainEntityOfPage'=>['@id'=>$postUrl.'#webpage'], 'datePublished'=>$postPub, 'dateModified'=>$postMod,
  'author'=>['@type'=>'Organization', 'name'=>$post['author'] ?: setting('site_name'), 'url'=>seo_home()],
  'publisher'=>['@id'=>seo_id('hotel')], 'isPartOf'=>['@type'=>'Blog', '@id'=>seo_abs(u('blog.php')).'#blog', 'name'=>setting('site_name').' Blog', 'url'=>seo_abs(u('blog.php'))], 'inLanguage'=>'en-IN',
  'wordCount'=>str_word_count(seo_text((string)$post['content'])), 'about'=>['@id'=>seo_id('hotel')]];
if ($postImg !== '') $article['image'] = seo_image_obj($postImg, $post['title']);
if ($post['cat_name']) $article['articleSection'] = $post['cat_name'];
if ($postTags) $article['keywords'] = implode(', ', $postTags);
$PAGE = ['title'=>$post['seo_title'] ?: $post['title'].' | '.setting('site_name'),'desc'=>$post['seo_desc'] ?: $post['excerpt'],
  'keywords'=>$post['seo_keywords'],'og_image'=>$postImg, 'og_type'=>'article', 'og_image_alt'=>$post['title'],
  'article'=>['published_time'=>$postPub, 'modified_time'=>$postMod, 'section'=>$post['cat_name'] ?: '', 'tag'=>$postTags],
  'seo_type'=>'post', 'lcp'=>$postImg, 'modified'=>$postMod, 'nodes'=>[$article], 'main_entity'=>$postUrl.'#article',
  'crumbs'=>array_values(array_filter([['Blog', u('blog.php')], $post['cat_name'] ? [$post['cat_name'], u('blog.php?category='.$post['cat_slug'])] : null, $post['title']]))];
include __DIR__.'/includes/header.php';
$url = urlencode(u('post.php?slug='.$post['slug']));
$ttl = urlencode($post['title']);
$related = q_all("SELECT * FROM blog_posts WHERE status='published' AND id<>? ".($post['category_id']?"AND category_id=".(int)$post['category_id']:'')." ORDER BY published_at DESC LIMIT 3", [(int)$post['id']]);
$ph = base_url('assets/img/placeholder.jpg');
?>
<div class="page-banner" style="background-image:url('<?= e(rd_blog_image($post, site_img('hero'))) ?>')">
  <div class="pb-inner"><h1 style="font-size:clamp(24px,4vw,42px)"><?= e($post['title']) ?></h1>
    <div class="crumbs"><a href="<?= base_url('') ?>">Home</a> › <a href="<?= u('blog.php') ?>">Blog</a><?= $post['cat_name'] ? ' ›  <a href="'.u('blog.php?category='.$post['cat_slug']).'">'.e($post['cat_name']).'</a>' : '' ?></div></div>
</div>
<section>
  <div class="container" style="max-width:820px">
    <p class="bc-meta reveal"><i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($post['published_at'])) ?> ·
       <i class="fa-regular fa-user"></i> <?= e($post['author'] ?: setting('site_name')) ?> ·
       <i class="fa-regular fa-eye"></i> <?= $post['views']+1 ?> views</p>
    <article class="reveal mt-2" style="font-size:15.5px;color:#4a535f"><?= $post['content'] ?></article>
    <?php if ($post['tags']): ?>
    <div class="amen-pills mt-3"><?php foreach (explode(',', $post['tags']) as $t): ?><span>#<?= e(trim($t)) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
    <h4 class="mt-3">Share this post</h4>
    <div class="share-row">
      <a target="_blank" rel="noopener" aria-label="X" href="https://twitter.com/intent/tweet?url=<?= $url ?>&text=<?= $ttl ?>"><i class="fa-brands fa-x-twitter"></i></a>
      <a target="_blank" rel="noopener" aria-label="LinkedIn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $url ?>"><i class="fa-brands fa-linkedin-in"></i></a>
      <a target="_blank" rel="noopener" aria-label="WhatsApp" href="https://wa.me/?text=<?= $ttl ?>%20<?= $url ?>"><i class="fa-brands fa-whatsapp"></i></a>
      <a target="_blank" rel="noopener" aria-label="Pinterest" href="https://pinterest.com/pin/create/button/?url=<?= $url ?>"><i class="fa-brands fa-pinterest-p"></i></a>
      <a href="mailto:?subject=<?= $ttl ?>&body=<?= $url ?>" aria-label="Email"><i class="fa-regular fa-envelope"></i></a>
    </div>
  </div>
</section>
<?php if ($related): ?>
<section style="background:var(--c-bg-alt);padding:50px 0">
  <div class="container">
    <h3 class="section-title" style="font-size:26px">Related Posts</h3>
    <div class="blog-grid stagger">
      <?php foreach ($related as $p): ?>
      <a class="blog-card" href="<?= u('post.php?slug='.$p['slug']) ?>">
        <div class="bc-img"><img src="<?= e(rd_blog_image($p, $ph)) ?>" alt="<?= e($p['title']) ?>" loading="lazy" decoding="async" width="800" height="533"></div>
        <div class="bc-body"><h3 style="font-size:17px"><?= e($p['title']) ?></h3></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__.'/includes/footer.php'; ?>
