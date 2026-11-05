<?php
/** Shared newsletter builder used by cron (mirrors admin/modules/newsletter.php preview) */
function cron_newsletter_build(string $toEmail = ''): string {
    $rooms = q_all("SELECT * FROM rooms WHERE active=1 ORDER BY sort LIMIT 3");
    $post = q_row("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 1");
    $offer = q_row("SELECT * FROM pricing_rules WHERE active=1 AND rule_type='special' AND (date_to IS NULL OR date_to >= CURDATE()) ORDER BY id DESC LIMIT 1");
    $inner = '<p>Namaste! 🙏 The hills are calling — here\'s what\'s waiting for you this week at <b>'.e(setting('site_name')).'</b>.</p>';
    if ($offer) $inner .= '<div style="background:#fdf7ec;border:1px solid #f2e1c2;border-radius:10px;padding:14px 18px;margin:14px 0"><b>🎁 '.e($offer['name']).'</b> — book now and save! Limited-period offer applied automatically at checkout.</div>';
    $inner .= '<table width="100%" cellpadding="0" cellspacing="0"><tr>';
    foreach ($rooms as $r) {
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
