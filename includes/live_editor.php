<?php
/**
 * Front-end live editor — included by footer.php for logged-in admins with page permission.
 * Shows an "Edit this page" button; in edit mode (?edit=1) every section / the banner / the intro gets
 * its own toolbar (edit, hide, move, duplicate, delete, add) and a slide-in editing drawer.
 */
if (empty($GLOBALS['LIVE_PAGE']) || !function_exists('live_editor_on') || !live_editor_on()) return;
$lp = $GLOBALS['LIVE_PAGE'];
if (empty($lp['id'])) return;
$editMode = live_edit_mode();
$types = [];
foreach (section_types() as $k => $t) $types[] = ['type'=>$k,'label'=>$t['label'],'icon'=>$t['icon'],'desc'=>$t['desc']];
$cur = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$qs = $_GET; unset($qs['edit']);
$plainUrl = $cur.($qs ? '?'.http_build_query($qs) : '');
$editUrl  = $cur.'?'.http_build_query($qs + ['edit'=>1]);
$v = @filemtime(ROOT_PATH.'/assets/js/live-editor.js').'.'.e(setting('cache_bust','1'));
?>
<link rel="stylesheet" href="<?= base_url('assets/css/editor.css') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/css/editor.css') ?>.<?= e(setting('cache_bust','1')) ?>">
<script>
window.ADMIN = Object.assign(window.ADMIN || {}, {base:'<?= base_url() ?>', csrf:'<?= csrf_token() ?>', upMaxPx:<?= (int)setting('upload_max_px','2000') ?>, upQuality:<?= (int)setting('upload_quality','86') ?>, videoCompressMB:<?= (int)setting('media_video_compress_mb','6') ?>, videoMaxH:<?= (int)setting('media_video_height','720') ?>});
window.ECH_LIVE = {pageId:<?= (int)$lp['id'] ?>, pageTitle:<?= json_encode($lp['title'] ?? '') ?>, editMode:<?= $editMode ? 'true' : 'false' ?>,
  editUrl:<?= json_encode($editUrl) ?>, plainUrl:<?= json_encode($plainUrl) ?>, adminUrl:'<?= base_url('admin/index.php?m=cms&builder='.(int)$lp['id']) ?>',
  types:<?= json_encode($types, JSON_UNESCAPED_UNICODE) ?>};
</script>
<?php if ($editMode): ?>
<script src="<?= base_url('assets/js/editor.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/editor.js') ?>"></script>
<script src="<?= base_url('assets/js/uploader.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/uploader.js') ?>"></script>
<script src="<?= base_url('assets/js/builder.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/builder.js') ?>"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/live-editor.js') ?>?v=<?= $v ?>"></script>
