<?php
/** Guest account → Food Menu: order to the room (auto-filled name / phone / room) + my food orders with live status */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../lib/food.php';
db(); sess_start();
$u = require_user();

$room = user_current_room($u);
$MENU_CTX = ['source'=>'account', 'room'=>$room, 'name'=>$u['name'], 'phone'=>$u['phone'] ?? '', 'email'=>$u['email']];
$orders = q_all("SELECT * FROM food_orders WHERE user_id=? OR (email<>'' AND email=?) ORDER BY id DESC LIMIT 15", [(int)$u['id'], $u['email']]);
$theme = preg_match('/^#[0-9a-f]{6}$/i', setting('menu_theme_color')) ? setting('menu_theme_color') : '#163a2c';
$PAGE = ['title'=>'Food Menu — My Account | '.setting('site_name'),
  'head'=>'<link rel="stylesheet" href="'.base_url('assets/css/menu.css').'?v='.@filemtime(ROOT_PATH.'/assets/css/menu.css').'.'.e(setting('cache_bust','1')).'"><style>:root{--fm-green:'.e($theme).'}</style><meta name="robots" content="noindex">'];
include __DIR__.'/../includes/header.php';
?>
<div style="height:90px;background:var(--c-secondary)"></div>
<div class="fm-wrap" style="padding-top:26px">
  <div class="fm">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
      <div>
        <a href="<?= base_url('account/index.php') ?>" style="font-size:13.5px"><i class="fa-solid fa-arrow-left"></i> My Account</a>
        <h2 style="font-size:clamp(30px,4vw,42px);color:var(--fm-green)">🍽 Food Menu</h2>
        <p class="fm-muted">Hello <?= e(explode(' ', $u['name'])[0]) ?> — <?= $room !== '' ? 'ordering to <b>'.e($room).'</b>. ' : '' ?>pick your dishes and press <b>Order now</b>.</p>
      </div>
    </div>

    <?php if ($orders): ?>
    <h3 style="margin:6px 0 10px;color:var(--fm-green)">My food orders</h3>
    <div class="fo-list" style="margin-bottom:26px">
      <?php foreach ($orders as $o): $st = FOOD_STATUSES[$o['status']]; ?>
      <div class="fo-card">
        <div><b><?= e($o['order_code']) ?></b> · <?= (int)$o['item_count'] ?> item(s) · <b><?= money($o['total']) ?></b>
          <div class="fm-muted"><?= date('d M, h:i A', strtotime($o['created_at'])) ?> · <?= e($o['room_no']) ?></div></div>
        <div style="display:flex;gap:8px;align-items:center">
          <span class="fo-st" style="background:<?= $st[1] ?>"><i class="fa-solid <?= $st[2] ?>"></i> <?= $st[0] ?></span>
          <?php if (!in_array($o['status'], ['delivered','cancelled'])): ?>
          <a class="fm-btn fm-btn-call" style="padding:7px 14px" href="<?= e(food_status_url($o)) ?>">Track</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php include __DIR__.'/../includes/menu_view.php'; ?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
