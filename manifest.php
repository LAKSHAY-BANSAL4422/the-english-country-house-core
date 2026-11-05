<?php
/** PWA manifest — makes the site installable like a mobile app */
require_once __DIR__.'/config.php'; db();
header('Content-Type: application/manifest+json; charset=utf-8');
$icon = setting('favicon') ? base_url(setting('favicon')) : base_url('assets/img/icon-512.png');
echo json_encode([
  'name' => setting('site_name'),
  'short_name' => setting('pwa_short_name', setting('site_name')),
  'description' => setting('seo_desc'),
  'start_url' => base_url('index.php'),
  'scope' => base_url('/'),
  'display' => 'standalone',
  'orientation' => 'portrait',
  'background_color' => setting('color_bg', '#ffffff'),
  'theme_color' => setting('color_secondary', '#0f2238'),
  'icons' => [
    ['src'=>base_url('assets/img/icon-192.png'),'sizes'=>'192x192','type'=>'image/png','purpose'=>'any maskable'],
    ['src'=>base_url('assets/img/icon-512.png'),'sizes'=>'512x512','type'=>'image/png','purpose'=>'any maskable'],
  ],
], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
