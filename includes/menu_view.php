<?php
/**
 * Food-menu component (public "Our Menu" page, QR scans, guest account).
 * Expects lib/food.php loaded and optional $MENU_CTX = [source, room, name, phone, email, embedded].
 */
$MENU_CTX = ($MENU_CTX ?? []) + ['source'=>'website','room'=>'','name'=>'','phone'=>'','email'=>'','embedded'=>false];
$fmCats = menu_load(true);
[$fmOpen, $fmClosedMsg] = menu_kitchen_open();
$fmRooms = public_rooms();
$fmCall = setting('menu_order_phone') ?: setting('contact_phone');
$fmWait = menu_wait_minutes();
$fmTax = (float)setting('menu_tax_percent', '0');
$fmMenuUrl = u('menu.php').($MENU_CTX['room'] !== '' ? '?room='.rawurlencode($MENU_CTX['room']).'&src=qr' : '?src=qr');
$fmQrUrl = u('menu.php').'?src=qr';
$tagClass = fn($t) => str_contains(strtolower($t), 'best') ? 'best' : (str_contains(strtolower($t), 'chef') ? 'chef' : 'new');
?>
<div class="fm" id="foodMenu"
     data-api="<?= e(base_url('api/food_order.php')) ?>"
     data-source="<?= e($MENU_CTX['source']) ?>"
     data-open="<?= $fmOpen ? 1 : 0 ?>"
     data-tax="<?= e((string)$fmTax) ?>" data-tax-label="<?= e(setting('menu_tax_label','GST')) ?>"
     data-currency="<?= e(setting('currency_symbol','₹')) ?>"
     data-min="<?= (float)setting('menu_min_order','0') ?>"
     data-wait="<?= $fmWait ?>"
     data-room-required="<?= setting('menu_room_required','1') === '1' ? 1 : 0 ?>">

  <?php if (!$fmOpen): ?>
  <div class="fm-banner"><i class="fa-solid fa-circle-info"></i> <?= e($fmClosedMsg) ?>
    <a class="fm-btn fm-btn-call" href="tel:<?= e($fmCall) ?>"><i class="fa-solid fa-phone"></i> Call <?= e($fmCall) ?></a></div>
  <?php endif; ?>

  <!-- toolbar -->
  <div class="fm-toolbar">
    <div class="fm-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="fmSearch" placeholder="Search dishes — paneer, coffee, momos…" autocomplete="off"></div>
    <label class="fm-vegonly"><input type="checkbox" id="fmVegOnly"><span class="sw"></span><span><i class="diet diet-veg"></i> Veg only</span></label>
    <a class="fm-btn fm-btn-call" href="tel:<?= e($fmCall) ?>"><i class="fa-solid fa-phone-volume"></i> <span>Order by call</span></a>
    <button type="button" class="fm-btn fm-btn-qr" data-qr-open><i class="fa-solid fa-qrcode"></i> <span>Menu QR</span></button>
  </div>

  <!-- category chips -->
  <nav class="fm-cats" id="fmCats">
    <?php foreach ($fmCats as $c): ?>
      <a href="#mc-<?= e($c['slug']) ?>" data-cat="<?= e($c['slug']) ?>"><i class="fa-solid <?= e($c['icon'] ?: 'fa-utensils') ?>"></i> <?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="fm-layout">
    <div class="fm-book">
      <img class="fm-corner tl" src="<?= base_url('assets/img/menu/corner-tl.png') ?>" alt="" loading="lazy">
      <img class="fm-corner br" src="<?= base_url('assets/img/menu/corner-br.png') ?>" alt="" loading="lazy">
      <div class="fm-book-head">
        <div class="fm-script"><?= e(setting('menu_tagline')) ?></div>
        <div class="fm-legend"><span><i class="diet diet-veg"></i> Veg</span><span><i class="diet diet-nonveg"></i> Non-veg</span><span><i class="diet diet-egg"></i> Contains egg</span></div>
      </div>

      <?php foreach ($fmCats as $c): $catOpen = menu_cat_open($c); $tl = menu_time_label($c['time_from'], $c['time_to']); ?>
      <section class="fm-sec" id="mc-<?= e($c['slug']) ?>" data-cat="<?= e($c['slug']) ?>">
        <header class="fm-sec-head" <?= $c['image'] ? 'style="--sec-img:url(\''.e(base_url($c['image'])).'\')"' : '' ?>>
          <span class="fm-sec-ico"><i class="fa-solid <?= e($c['icon'] ?: 'fa-utensils') ?>"></i></span>
          <h2><?= e($c['name']) ?></h2>
          <?php if ($c['subtitle']): ?><p><?= e($c['subtitle']) ?></p><?php endif; ?>
          <div class="fm-sec-badges">
            <?php if ($tl): ?><span class="fm-badge time"><i class="fa-regular fa-clock"></i> <?= e($tl) ?></span><?php endif; ?>
            <?php if ($c['advance_order']): ?><span class="fm-badge adv"><i class="fa-solid fa-hourglass-half"></i> Advance order</span><?php endif; ?>
          </div>
        </header>
        <div class="fm-items">
          <?php foreach ($c['items'] as $it): $vars = menu_variants($it); $canAdd = $fmOpen && $catOpen && $it['available']; ?>
          <article class="fm-item <?= $it['available'] ? '' : 'soldout' ?> <?= $it['image'] ? 'has-img' : '' ?>"
                   data-id="<?= (int)$it['id'] ?>" data-name="<?= e($it['name']) ?>" data-price="<?= (float)$it['price'] ?>"
                   data-diet="<?= e($it['diet']) ?>" data-search="<?= e(mb_strtolower($it['name'].' '.$it['description'].' '.$it['variants'].' '.$c['name'])) ?>">
            <?php if ($it['image']): ?><img class="fm-thumb" src="<?= base_url($it['image']) ?>" alt="<?= e($it['name']) ?>" loading="lazy"><?php endif; ?>
            <div class="fm-i-body">
              <h4><i class="diet diet-<?= e($it['diet']) ?>" title="<?= e(FOOD_DIETS[$it['diet']] ?? '') ?>"></i><?= e($it['name']) ?>
                <?php foreach (array_filter(array_map('trim', explode(',', (string)$it['tags']))) as $tg): ?><span class="fm-tag <?= $tagClass($tg) ?>"><?= e($tg) ?></span><?php endforeach; ?>
              </h4>
              <?php if ($it['description']): ?><p><?= e($it['description']) ?></p><?php endif; ?>
              <?php if ($vars): ?>
                <div class="fm-vars" role="radiogroup" aria-label="Choose option">
                  <?php foreach ($vars as $vi => $v): ?><button type="button" class="fm-var <?= $vi === 0 ? 'on' : '' ?>" data-v="<?= e($v) ?>"><?= e($v) ?></button><?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="fm-i-side">
              <div class="fm-price"><?= money($it['price']) ?></div>
              <?php if (!$it['available']): ?><span class="fm-sold">Sold out</span>
              <?php elseif (!$catOpen): ?><span class="fm-sold">Served <?= e($tl) ?></span>
              <?php elseif ($canAdd): ?><button type="button" class="fm-add" data-add>ADD <i class="fa-solid fa-plus"></i></button>
              <?php endif; ?>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>
      <p class="fm-note"><?= e(setting('menu_price_note')) ?></p>
      <p class="fm-empty" id="fmEmpty" hidden>No dishes match your search.</p>
    </div>

    <!-- desktop order panel -->
    <aside class="fm-panel" id="fmPanel">
      <h3><i class="fa-solid fa-bell-concierge"></i> Your order</h3>
      <div class="fm-panel-list" data-cart-list><p class="fm-muted">Tap <b>ADD</b> on any dish to start your order.</p></div>
      <div class="fm-panel-foot" data-cart-foot hidden>
        <div class="fm-total-row"><span>Total</span><b data-cart-total>₹0</b></div>
        <button type="button" class="fm-btn fm-btn-primary fm-wide" data-checkout><i class="fa-solid fa-paper-plane"></i> Review &amp; order</button>
      </div>
      <div class="fm-wait"><i class="fa-regular fa-clock"></i><span>Approx. <b><?= $fmWait ?> min</b> — <?= e(setting('menu_wait_note')) ?></span></div>
      <a class="fm-btn fm-btn-call fm-wide" href="tel:<?= e($fmCall) ?>"><i class="fa-solid fa-phone"></i> Order now by call — <?= e($fmCall) ?></a>
      <div class="fm-panel-qr">
        <canvas data-qr="<?= e($fmQrUrl) ?>" data-qr-size="170"></canvas>
        <small>Scan to open this menu on your phone</small>
      </div>
    </aside>
  </div>

  <!-- mobile cart bar -->
  <div class="fm-cartbar" id="fmCartBar" hidden>
    <div><b data-cart-count>0 items</b><span data-cart-total>₹0</span></div>
    <button type="button" data-checkout>View order <i class="fa-solid fa-chevron-up"></i></button>
  </div>

  <!-- checkout sheet -->
  <div class="fm-sheet" id="fmSheet" aria-hidden="true">
    <div class="fm-sheet-box" role="dialog" aria-label="Your order">
      <button type="button" class="fm-x" data-close>&times;</button>
      <div data-step="cart">
        <h3><i class="fa-solid fa-bell-concierge"></i> Your order</h3>
        <div class="fm-lines" data-cart-list></div>
        <div class="fm-totals" data-cart-totals></div>
        <form id="fmForm" autocomplete="on" novalidate>
          <div class="fm-grid2">
            <label>Room no. / Table <b class="req">*</b>
              <input name="room" list="fmRoomList" value="<?= e($MENU_CTX['room']) ?>" placeholder="e.g. Victorian Suite I" <?= setting('menu_room_required','1') === '1' ? 'required' : '' ?>></label>
            <label>Your name <b class="req">*</b><input name="name" value="<?= e($MENU_CTX['name']) ?>" required autocomplete="name"></label>
            <label>Mobile number <b class="req">*</b><input name="phone" type="tel" value="<?= e($MENU_CTX['phone']) ?>" required inputmode="tel" autocomplete="tel"></label>
            <label>Email <small>(optional)</small><input name="email" type="email" value="<?= e($MENU_CTX['email']) ?>" autocomplete="email"></label>
          </div>
          <label>Cooking notes <small>(less spicy, no onion, allergies…)</small><textarea name="notes" rows="2" maxlength="480"></textarea></label>
          <datalist id="fmRoomList">
            <?php foreach ($fmRooms as $r): ?><option value="<?= e($r['name']) ?>"><?php endforeach; ?>
            <option value="Restaurant (dine-in)"><option value="Deck / Lawn"><option value="Pool side">
          </datalist>
          <div class="fm-wait big"><i class="fa-regular fa-clock"></i> <span>Estimated waiting time <b><?= $fmWait ?> minutes</b><br><small><?= e(setting('menu_wait_note')) ?></small></span></div>
          <div class="fm-err" data-err hidden></div>
          <div class="fm-actions">
            <button type="submit" class="fm-btn fm-btn-primary fm-wide" data-place <?= $fmOpen ? '' : 'disabled' ?>>
              <i class="fa-brands fa-whatsapp"></i> Order now — send to kitchen</button>
            <a class="fm-btn fm-btn-call fm-wide" href="tel:<?= e($fmCall) ?>"><i class="fa-solid fa-phone"></i> Call to order instead</a>
          </div>
          <p class="fm-fine">Your room, name, mobile and dishes go straight to our kitchen team on WhatsApp &amp; our dashboard.</p>
        </form>
      </div>
      <div data-step="done" hidden>
        <div class="fm-done-ico"><i class="fa-solid fa-check"></i></div>
        <h3>Order placed!</h3>
        <p class="fm-code">Order <b data-o-code></b> · <span data-o-total></span></p>
        <div class="fm-eta"><span>Estimated ready by</span><b data-o-ready></b><small data-o-countdown></small></div>
        <ol class="fm-track" data-track>
          <li data-s="new"><i class="fa-solid fa-receipt"></i><span>Received</span></li>
          <li data-s="accepted"><i class="fa-solid fa-thumbs-up"></i><span>Accepted</span></li>
          <li data-s="preparing"><i class="fa-solid fa-fire-burner"></i><span>Preparing</span></li>
          <li data-s="ready"><i class="fa-solid fa-person-walking"></i><span>On the way</span></li>
          <li data-s="delivered"><i class="fa-solid fa-circle-check"></i><span>Served</span></li>
        </ol>
        <p class="fm-cancelled" data-o-cancel hidden><i class="fa-solid fa-circle-xmark"></i> This order was cancelled — please call us.</p>
        <a class="fm-btn fm-btn-wa fm-wide" data-o-wa target="_blank" rel="noopener" hidden><i class="fa-brands fa-whatsapp"></i> Send order on WhatsApp</a>
        <a class="fm-btn fm-btn-call fm-wide" href="tel:<?= e($fmCall) ?>"><i class="fa-solid fa-phone"></i> Call the kitchen</a>
        <button type="button" class="fm-btn fm-btn-ghost fm-wide" data-more>Order something more</button>
      </div>
    </div>
  </div>

  <!-- QR modal -->
  <div class="fm-sheet fm-qr-modal" id="fmQrModal" aria-hidden="true">
    <div class="fm-sheet-box fm-qr-box">
      <button type="button" class="fm-x" data-close>&times;</button>
      <h3>Scan for our menu</h3>
      <canvas data-qr="<?= e($fmQrUrl) ?>" data-qr-size="300" data-qr-label="SCAN · CHOOSE · ORDER"></canvas>
      <p class="fm-muted">Point your phone camera here — the menu opens instantly and you can order straight to your room.</p>
      <a class="fm-btn fm-btn-ghost" data-qr-download href="#"><i class="fa-solid fa-download"></i> Download QR</a>
    </div>
  </div>
</div>
<script>window.ECH_QR = {logo: '<?= e(base_url(setting('menu_qr_logo') ?: 'assets/img/brand/logo.png')) ?>', color: '<?= e(setting('menu_qr_color','#163a2c')) ?>'};</script>
<script src="<?= base_url('assets/js/vendor/qrcode.js') ?>"></script>
<script src="<?= base_url('assets/js/menu-qr.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/menu-qr.js') ?>.<?= e(setting('cache_bust','1')) ?>"></script>
<script src="<?= base_url('assets/js/menu.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/menu.js') ?>.<?= e(setting('cache_bust','1')) ?>"></script>
