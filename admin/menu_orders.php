<?php
/** Renders the live food-order cards (used by Admin → Restaurant Menu → Orders and its AJAX auto-refresh) */
require_once ROOT_PATH.'/lib/food.php';

function food_orders_query(string $view, string $date = ''): array {
    $w = []; $p = [];
    switch ($view) {
        case 'delivered': $w[] = "o.status='delivered'"; break;
        case 'cancelled': $w[] = "o.status='cancelled'"; break;
        case 'all': break;
        default: $w[] = "o.status IN ('new','accepted','preparing','ready')";
    }
    if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $w[] = "DATE(o.created_at)=?"; $p[] = $date; }
    $sql = "SELECT o.*, a.name handler FROM food_orders o LEFT JOIN admins a ON a.id=o.handled_by"
         .($w ? ' WHERE '.implode(' AND ', $w) : '')
         ." ORDER BY FIELD(o.status,'new','accepted','preparing','ready','delivered','cancelled'), o.id DESC LIMIT 120";
    return q_all($sql, $p);
}

function render_food_orders(string $view, string $date = ''): void {
    $orders = food_orders_query($view, $date);
    if (!$orders) {
        echo '<div class="fo-empty"><i class="fa-solid fa-bell-concierge"></i><p>'.($view === 'active' ? 'No open food orders right now. New orders appear here instantly with a sound alert.' : 'No orders for this filter.').'</p></div>';
        return;
    }
    $staff = menu_wa_numbers();
    $next = ['new'=>['accepted','✅ Accept'], 'accepted'=>['preparing','🔥 Preparing'], 'preparing'=>['ready','🚶 Send to room'], 'ready'=>['delivered','✔ Delivered']];
    $wait = menu_wait_minutes();
    foreach ($orders as $o):
        $items = food_order_items((int)$o['id']);
        $st = FOOD_STATUSES[$o['status']];
        $age = max(0, time() - strtotime($o['created_at']));
        $ageTxt = $age < 60 ? 'just now' : ($age < 3600 ? floor($age/60).' min ago' : date('d M, h:i A', strtotime($o['created_at'])));
        $due = strtotime($o['accepted_at'] ?: $o['created_at']) + (int)$o['eta_minutes'] * 60;
        $late = in_array($o['status'], ['new','accepted','preparing']) && time() > $due;
        $msg = food_order_message($o, $items);
        ?>
        <div class="fo-card st-<?= $o['status'] ?> <?= $late ? 'late' : '' ?>" data-id="<?= (int)$o['id'] ?>">
          <div class="fo-top">
            <div>
              <span class="fo-badge" style="background:<?= $st[1] ?>"><i class="fa-solid <?= $st[2] ?>"></i> <?= $st[0] ?></span>
              <b class="fo-code"><?= e($o['order_code']) ?></b>
              <small class="hint"><?= $ageTxt ?> · <?= e($o['source']) ?></small>
            </div>
            <div class="fo-room" title="Room / table"><i class="fa-solid fa-door-closed"></i> <?= e($o['room_no'] ?: '—') ?></div>
          </div>
          <div class="fo-guest">
            <b><?= e($o['guest_name']) ?></b>
            <a href="tel:<?= e($o['phone']) ?>"><i class="fa-solid fa-phone"></i> <?= e($o['phone']) ?></a>
            <a class="wa" target="_blank" rel="noopener" href="<?= e(wa_link($o['phone'], 'Hello '.$o['guest_name'].', this is '.setting('site_name').' kitchen about your order '.$o['order_code'].'.')) ?>"><i class="fa-brands fa-whatsapp"></i> Guest</a>
          </div>
          <ul class="fo-items">
            <?php foreach ($items as $it): ?>
              <li><i class="diet diet-<?= e($it['diet']) ?>"></i><span class="q"><?= (int)$it['qty'] ?>×</span> <?= e($it['name']) ?><?= $it['variant'] !== '' ? ' <em>('.e($it['variant']).')</em>' : '' ?><span class="amt"><?= money($it['line_total']) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($o['notes'] !== ''): ?><div class="fo-note"><i class="fa-solid fa-note-sticky"></i> <?= e($o['notes']) ?></div><?php endif; ?>
          <div class="fo-sum">
            <span><?= (int)$o['item_count'] ?> item(s)<?= (float)$o['tax'] > 0 ? ' · incl. '.e(setting('menu_tax_label','GST')).' '.money($o['tax']) : '' ?></span>
            <b><?= money($o['total']) ?></b>
          </div>
          <div class="fo-meta">
            <span class="<?= $late ? 'late-txt' : '' ?>"><i class="fa-regular fa-clock"></i> ETA <?= (int)$o['eta_minutes'] ?> min → <?= date('h:i A', $due) ?><?= $late ? ' · LATE' : '' ?></span>
            <?php if ($o['wa_status'] !== ''): ?><span title="<?= e($o['wa_status']) ?>"><i class="fa-brands fa-whatsapp"></i> <?= e(mb_strimwidth($o['wa_status'], 0, 40, '…')) ?></span><?php endif; ?>
            <?php if ($o['handler']): ?><span><i class="fa-solid fa-user-check"></i> <?= e($o['handler']) ?></span><?php endif; ?>
          </div>
          <div class="fo-actions">
            <?php if (isset($next[$o['status']])): ?>
              <?php if ($o['status'] === 'new'): ?>
                <select class="input fo-eta" title="Time to serve">
                  <?php foreach ([10,15,20,25,30,40,45,60,75,90] as $m): ?><option value="<?= $m ?>" <?= $m === (int)$o['eta_minutes'] || ($m === $wait && !in_array((int)$o['eta_minutes'], [10,15,20,25,30,40,45,60,75,90])) ? 'selected' : '' ?>><?= $m ?> min</option><?php endforeach; ?>
                </select>
              <?php endif; ?>
              <button class="btn sm <?= $o['status']==='new' ? 'green' : '' ?>" data-fo-status="<?= $next[$o['status']][0] ?>"><?= $next[$o['status']][1] ?></button>
            <?php endif; ?>
            <a class="btn sm ghost" target="_blank" href="<?= base_url('admin/food_print.php?id='.(int)$o['id']) ?>"><i class="fa-solid fa-print"></i> KOT</a>
            <?php if ($staff): ?>
            <div class="fo-fwd">
              <button class="btn sm ghost" type="button" onclick="this.nextElementSibling.classList.toggle('show')"><i class="fa-brands fa-whatsapp"></i> Forward</button>
              <div class="fo-fwd-menu">
                <?php foreach ($staff as $n): ?><a target="_blank" rel="noopener" href="<?= e(wa_link($n['number'], $msg)) ?>"><?= e(($n['name'] ?: 'Staff').' · '.$n['number']) ?></a><?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if (!in_array($o['status'], ['delivered','cancelled'])): ?>
              <button class="btn sm danger" data-fo-status="cancelled" data-confirm="Cancel order <?= e($o['order_code']) ?>?">✕</button>
            <?php endif; ?>
          </div>
        </div>
    <?php endforeach;
}
