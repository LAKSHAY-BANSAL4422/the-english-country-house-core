<?php
/**
 * Admin-only upload endpoint (visual editor, image uploader component, live editor).
 * POST file + csrf [+ subdir] → JSON {ok, url, path, w, h} — or a clear reason why it failed.
 */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
$A = current_admin();
if (!$A) json_out(['ok'=>false,'error'=>'Not signed in'], 401);
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) json_out(['ok'=>false,'error'=>'Session expired — refresh the page.'], 403);

$subdir = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)post('subdir', 'editor'))) ?: 'editor';
if (!in_array($subdir, ['editor','gallery','rooms','pages','sections','menu','logo','blog','amenities','video','popups','social','qr'], true)) $subdir = 'editor';
$kind = post('kind') === 'video' ? 'video' : 'image';
$allowed = $kind === 'video' ? ['mp4','webm','mov','m4v'] : ['jpg','jpeg','png','webp','gif','svg'];

function ini_bytes(string $v): int {
    $v = trim($v); if ($v === '') return 0;
    $n = (float)$v; $u = strtolower(substr($v, -1));
    return (int)($u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n)));
}
$maxUp = ini_bytes((string)ini_get('upload_max_filesize')); $maxPost = ini_bytes((string)ini_get('post_max_size'));
$limitHuman = round(min($maxUp ?: PHP_INT_MAX, $maxPost ?: PHP_INT_MAX) / 1048576).' MB';

if (empty($_FILES['file'])) {
    // PHP silently drops everything when the POST is larger than post_max_size
    $len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len > 0 && $maxPost && $len > $maxPost) json_out(['ok'=>false,'error'=>"File is bigger than your hosting's upload limit ($limitHuman). Choose a smaller file or raise upload_max_filesize / post_max_size in cPanel → MultiPHP INI Editor."]);
    json_out(['ok'=>false,'error'=>'No file received.']);
}
$f = $_FILES['file'];
$errs = [UPLOAD_ERR_INI_SIZE=>"File is bigger than your hosting's upload limit ($limitHuman). Raise upload_max_filesize in cPanel → MultiPHP INI Editor, or use a smaller file.",
         UPLOAD_ERR_FORM_SIZE=>'File too large.', UPLOAD_ERR_PARTIAL=>'Upload was interrupted — please try again.', UPLOAD_ERR_NO_FILE=>'No file selected.',
         UPLOAD_ERR_NO_TMP_DIR=>'Server has no temp folder (contact your host).', UPLOAD_ERR_CANT_WRITE=>'Server could not write the file (check uploads/ folder permissions — 755).', UPLOAD_ERR_EXTENSION=>'Upload blocked by a PHP extension.'];
if ((int)$f['error'] !== UPLOAD_ERR_OK) json_out(['ok'=>false,'error'=>$errs[(int)$f['error']] ?? 'Upload failed (code '.$f['error'].').']);
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) json_out(['ok'=>false,'error'=>'“.'.$ext.'” files are not allowed here. Use '.strtoupper(implode(' / ', $allowed)).'.']);
if ($kind === 'image' && $ext !== 'svg' && !@getimagesize($f['tmp_name'])) json_out(['ok'=>false,'error'=>'That file is not a valid image.']);

$dir = UPLOAD_PATH.'/'.$subdir;
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) json_out(['ok'=>false,'error'=>'Cannot create uploads/'.$subdir.' — set the uploads folder permission to 755 in cPanel File Manager.']);
if (!is_writable($dir)) json_out(['ok'=>false,'error'=>'uploads/'.$subdir.' is not writable — set its permission to 755 in cPanel File Manager.']);

$origSize = (int)$f['size'];
$path = handle_upload('file', $subdir, $allowed);   // ← optimises automatically (resize / recompress / .webp twin / ffmpeg)
if ($path === '') json_out(['ok'=>false,'error'=>'Upload failed — the file could not be saved (max 64 MB).']);
$w = $h = 0; $abs = ROOT_PATH.'/'.$path;
if ($kind === 'image' && $ext !== 'svg') { $i = @getimagesize($abs); if ($i) [$w, $h] = $i; }
$final = (int)@filesize($abs);
$poster = '';
if ($kind === 'video' && post('poster') === '1') { require_once ROOT_PATH.'/lib/media.php'; $poster = media_video_poster($abs); }
json_out(['ok'=>true, 'url'=>base_url($path), 'path'=>$path, 'w'=>$w, 'h'=>$h, 'size'=>$final, 'orig_size'=>$origSize,
          'saved'=>max(0, $origSize - $final), 'webp'=>is_file($abs.'.webp'), 'poster'=>$poster, 'name'=>$f['name']]);
