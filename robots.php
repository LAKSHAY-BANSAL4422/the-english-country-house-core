<?php
/** robots.txt (rewritten from /robots.txt via .htaccess), editable in Admin → SEO.
 *  Round 17: {BASE_URL} uses Admin → SEO "Main web address" when set, and the sitemap line is always present. */
require_once __DIR__.'/config.php'; db();
require_once __DIR__.'/includes/seo.php';
header('Content-Type: text/plain; charset=utf-8');
$txt = str_replace('{BASE_URL}', seo_base(), setting('robots_txt', robots_default()));
if (!preg_match('/^\s*Sitemap:/mi', $txt)) $txt = rtrim($txt)."\n\nSitemap: ".seo_base()."/sitemap.xml";
echo rtrim($txt)."\n";
