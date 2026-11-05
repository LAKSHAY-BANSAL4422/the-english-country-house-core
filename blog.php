<?php
/** Blog — fully editable page; category pages keep their own SEO title/description */
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/auth.php'; db(); sess_start();
require_once __DIR__.'/includes/page_render.php';
$cat = get('category') ? q_row("SELECT * FROM blog_categories WHERE slug=? AND active=1", [get('category')]) : null;
if (get('category') !== '' && !$cat) { require __DIR__.'/404.php'; exit; }   // round 17: unknown category → real 404
require_once __DIR__.'/includes/seo.php';
$extra = [];
/* round 17 (technical SEO): the blog and its posts as data (Blog + BlogPosting list) */
$blogPosts = q_all("SELECT title, slug, excerpt, image, published_at, created_at FROM blog_posts WHERE status='published'".($cat ? " AND category_id=".(int)$cat['id'] : '')." ORDER BY published_at DESC LIMIT 30");
$extra['seo'] = ['nodes'=>[['@type'=>'Blog', '@id'=>seo_abs(u('blog.php')).'#blog', 'name'=>setting('site_name').' Blog', 'url'=>seo_abs(u('blog.php')),
  'publisher'=>['@id'=>seo_id('hotel')], 'inLanguage'=>'en-IN',
  'blogPost'=>array_map(fn($p) => array_filter(['@type'=>'BlogPosting', '@id'=>seo_abs(u('post.php?slug='.$p['slug'])).'#article', 'headline'=>$p['title'],
     'url'=>seo_abs(u('post.php?slug='.$p['slug'])), 'datePublished'=>date('c', strtotime($p['published_at'] ?: $p['created_at'])),
     'image'=>($bi = rd_blog_image_path($p)) !== '' ? seo_abs($bi) : null]), $blogPosts)]],
  'webpage'=>['mainEntity'=>['@id'=>seo_abs(u('blog.php')).'#blog']]];
if ($cat) { $extra['title_override'] = ($cat['seo_title'] ?: $cat['name'].' — Blog').' | '.setting('site_name'); $extra['desc_override'] = $cat['seo_desc'] ?: $cat['description'];
            $extra['banner_title'] = $cat['name']; $extra['banner_subtitle'] = (string)$cat['description']; }   // round 14: each category page has its own H1
render_system_page('blog',
  ['title'=>'Blog | '.setting('site_name'), 'desc'=>'Travel guides, resort stories and Mussoorie tips from '.setting('site_name').'.', 'h1'=>'Our Blog'],
  $cat ? [['Blog', u('blog.php')], $cat['name']] : ['Blog'],
  [['type'=>'blog_list','visible'=>1]], 'hero', $extra);
