<?php
/** Weekly newsletter: settings, preview, subscriber list, send-now */
$flash = ''; $flashOk = true;

function newsletter_build(string $toEmail = ''): string {
    $rooms = q_all("SELECT * FROM rooms WHERE active=1 ORDER BY sort LIMIT 3");
    $post = q_row("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 1");
    $offer = q_row("SELECT * FROM pricing_rules WHERE active=1 AND rule_type='special' AND (date_to IS NULL OR date_to >= CURDATE()) ORDER BY id DESC LIMIT 1");
    $inner = '<p>Namaste! 🙏 The hills are calling — here\'s what\'s waiting for you this week at <b>'.e(setting('site_name')).'</b>.</p>';
    if ($offer) $inner .= '<div style="background:#fdf7ec;border:1px solid #f2e1c2;border-radius:10px;padding:14px 18px;margin:14px 0"><b>🎁 '.e($offer['name']).'</b> — book now and save! Limited-period offer applied automatically at checkout.</div>';
    $inner .= '<table width="100%" cellpadding="0" cellspacing="0"><tr>';
    foreach ($rooms as $i=>$r) {
        $im = json_decode($r['images'] ?: '[]', true) ?: [];
        $img = $im ? base_url($im[0]) : base_url('assets/img/placeholder.jpg');
        $inner .= '<td width="33%" style="padding:6px;vertical-align:top"><a href="'.base_url('room.php?slug='.$r['slug']).'" style="text-decoration:none;color:#333">
          <img src="'.$img.'" style="width:100%;border-radius:8px;height:90px;object-fit:cover" alt="">
          <div style="font-size:13px;font-weight:600;margin-top:6px">'.e($r['name']).'</div>
          <div style="font-size:12px;color:'.setting('color_primary').'">from '.money($r['base_price']).'/night</div></a></td>';
    }
    $inner .= '</tr></table>';
    if ($post) $inner .= '<p style="margin-top:14px">📖 <b>From our journal:</b> <a href="'.base_url('post.php?slug='.$post['slug']).'" style="color:'.setting('color_primary').'">'.e($post['title']).'</a></p>';
    $html = email_fancy('This Weekend In The Hills 🏔', setting('site_tagline'), $inner, [
        ['🏡 View & Visit Website', base_url(), setting('color_secondary')],
        ['📅 Book Your Stay', base_url('booking.php')],
    ]);
    if ($toEmail) {
        $unsub = base_url('unsubscribe.php?email='.urlencode($toEmail).'&t='.unsub_token($toEmail));
        $html .= '<p style="text-align:center;font-size:11px;color:#98a0ab;margin-top:10px"><a href="'.$unsub.'" style="color:#98a0ab">Unsubscribe from weekly emails</a></p>';
    }
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        setting_save('newsletter_enabled', post('newsletter_enabled') ? '1' : '0');
        setting_save('newsletter_day', (string)(int)post('newsletter_day'));
        setting_save('newsletter_subject', post('newsletter_subject'));
        $flash = 'Newsletter settings saved.';
    }
    if (post('do') === 'send_test') {
        $ok = send_email(post('test_to') ?: $ADMIN['email'], setting('newsletter_subject'), newsletter_build(post('test_to') ?: $ADMIN['email']), 'newsletter', false);
        $flash = $ok ? 'Test newsletter sent!' : 'Sending failed — check SMTP settings.'; $flashOk = $ok;
    }
    if (post('do') === 'send_now') {
        $subs = q_all("SELECT email FROM users WHERE newsletter=1 AND email_verified=1 AND active=1");
        $n = 0;
        foreach ($subs as $s) { if (send_email($s['email'], setting('newsletter_subject'), newsletter_build($s['email']), 'newsletter', false)) $n++; }
        setting_save('newsletter_last_sent', date('Y-m-d'));
        $flash = "Newsletter sent to $n subscriber(s).";
    }
}
$subCount = (int)q_val("SELECT COUNT(*) FROM users WHERE newsletter=1 AND email_verified=1 AND active=1", [], 0);
$days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',0=>'Sunday'];
?>
<?php if ($flash): ?><div class="alert <?= $flashOk?'ok':'err' ?>"><?= e($flash) ?></div><?php endif; ?>
<div class="grid2">
  <div class="card"><h3>📬 Weekly Newsletter Settings</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save">
      <label class="inline"><input type="checkbox" name="newsletter_enabled" value="1" <?= setting('newsletter_enabled')==='1'?'checked':'' ?>> Send automatically every week (via cron)</label>
      <div class="frow">
        <div><label>Send on</label><select class="input" name="newsletter_day">
          <?php foreach ($days as $k=>$v): ?><option value="<?= $k ?>" <?= (int)setting('newsletter_day',5)===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div><label>Subscribers</label><input class="input" value="<?= $subCount ?> verified guests" disabled></div>
      </div>
      <label>Email subject</label><input class="input" name="newsletter_subject" value="<?= e(setting('newsletter_subject')) ?>">
      <button class="btn" style="margin-top:12px">Save Settings</button>
    </form>
    <hr style="margin:18px 0;border:none;border-top:1px solid var(--bd)">
    <form method="post" style="display:flex;gap:10px;align-items:end"><?= csrf_field() ?><input type="hidden" name="do" value="send_test">
      <div style="flex:1"><label>Send test to</label><input class="input" name="test_to" value="<?= e($ADMIN['email']) ?>"></div>
      <button class="btn ghost">Send Test</button>
    </form>
    <form method="post" style="margin-top:12px" onsubmit="return confirm('Send the newsletter to all <?= $subCount ?> subscribers NOW?')"><?= csrf_field() ?><input type="hidden" name="do" value="send_now">
      <button class="btn sec" style="width:100%">📨 Send To All Subscribers Now</button>
    </form>
    <p class="hint" style="margin-top:10px">Content is built automatically: current special offer + top rooms with prices + latest blog post + facilities row + "View &amp; Visit" and "Book Your Stay" buttons that link back to your website. Registered guests can unsubscribe with one click (link in the footer of every issue).</p>
  </div>
  <div class="card"><h3>👁 Live Preview</h3>
    <div style="border:1px solid var(--bd);border-radius:10px;overflow:auto;max-height:70vh;zoom:.85">
      <?= newsletter_build('preview@example.com') ?>
    </div>
  </div>
</div>
