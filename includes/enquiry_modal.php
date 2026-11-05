<?php
/* REDESIGN (round 4): "Make an Enquiry" form, included once by footer.php on every public page.
 * Opened by any [data-enquire] link (data-room preselects a room) and by any remaining link to booking.php.
 * Everything happens in the browser (assets/js/redesign.js, section 11): the form is validated, a WhatsApp message is
 * built from the filled-in fields and the guest is sent to https://wa.me/<number>?text=… to press Send themselves.
 * Round 18: before the redirect a copy of the enquiry is saved (api/form_submit.php, form "wa_enquiry": Admin → Leads, and an
 * email to the booking notification address) and GTM gets one form_submit_success event (form_name stay_enquiry).
 * Number: rd_enquiry_number() — the enquiry_wa_number override in redesign_content.php, else Admin → Settings → WhatsApp number.
 * Rooms list: read-only SELECT (the same query the old booking modal used). */
$rdEnqNum = rd_enquiry_number();
$rdEnqRooms = public_rooms();
?>
<div class="rd-enq" id="rdEnquiry" data-wa="<?= e($rdEnqNum) ?>" data-phone-own hidden><?php /* data-phone-own: this form shows its own phone errors (same rule, via ECHPhone) */ ?>
  <div class="rd-enq__scrim" data-enq-close></div>
  <div class="rd-enq__panel" role="dialog" aria-modal="true" aria-labelledby="rdEnqTitle">
    <button type="button" class="rd-enq__x" data-enq-close aria-label="Close enquiry form"><?= rd_icon('close') ?></button>

    <div class="rd-enq__step" data-enq-step="form">
      <p class="rd-enq__eyebrow">Enquiry via WhatsApp</p>
      <h2 class="rd-enq__title" id="rdEnqTitle">Make an Enquiry</h2>
      <p class="rd-enq__intro">Share a few details and we'll reply on WhatsApp with availability, details and pricing. No account needed.</p>
      <?php if ($rdEnqNum === ''): ?>
      <p class="rd-enq__alert" role="alert">Enquiries can't be sent right now because our WhatsApp number hasn't been set up. Please call or email us instead.</p>
      <?php endif; ?>
      <form class="rd-enq__form" id="rdEnqForm" novalidate>
        <div class="rd-enq__field rd-enq__field--wide">
          <label for="rdEnqName">Full name <span aria-hidden="true">*</span></label>
          <input type="text" id="rdEnqName" autocomplete="name" maxlength="80" required aria-required="true" aria-describedby="rdEnqName-err">
          <p class="rd-enq__err" id="rdEnqName-err" hidden></p>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqPhone">WhatsApp / mobile <span aria-hidden="true">*</span></label>
          <input type="tel" id="rdEnqPhone" autocomplete="tel" inputmode="tel" maxlength="24" placeholder="+91 98765 43210" required aria-required="true" aria-describedby="rdEnqPhone-err">
          <p class="rd-enq__err" id="rdEnqPhone-err" hidden></p>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqEmail">Email <small>(optional)</small></label>
          <input type="email" id="rdEnqEmail" autocomplete="email" maxlength="120" aria-describedby="rdEnqEmail-err">
          <p class="rd-enq__err" id="rdEnqEmail-err" hidden></p>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqIn">Check-in <small>(optional)</small></label>
          <input type="date" id="rdEnqIn" aria-describedby="rdEnqIn-err">
          <p class="rd-enq__err" id="rdEnqIn-err" hidden></p>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqOut">Check-out <small>(optional)</small></label>
          <input type="date" id="rdEnqOut" aria-describedby="rdEnqOut-err">
          <p class="rd-enq__err" id="rdEnqOut-err" hidden></p>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqGuests">Guests <small>(optional)</small></label>
          <select id="rdEnqGuests">
            <option value="">Select</option>
            <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            <option value="More than 10">More than 10</option>
          </select>
        </div>
        <div class="rd-enq__field">
          <label for="rdEnqRoom">Room preference <small>(optional)</small></label>
          <select id="rdEnqRoom">
            <option value="">No preference</option>
            <?php foreach ($rdEnqRooms as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="rd-enq__field rd-enq__field--wide">
          <label for="rdEnqMsg">Message / special requirements <small>(optional)</small></label>
          <textarea id="rdEnqMsg" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="rd-enq__actions rd-enq__field--wide">
          <button type="submit" class="rd-btn rd-btn--dark rd-btn--block" <?= $rdEnqNum === '' ? 'disabled' : '' ?>>Continue to WhatsApp</button>
          <p class="rd-enq__small">* Required. Nothing is sent until you press Send in WhatsApp.</p>
        </div>
      </form>
    </div>

    <div class="rd-enq__step rd-enq__step--go" data-enq-step="go" hidden>
      <span class="rd-enq__wa" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
      <p class="rd-enq__title" tabindex="-1" data-enq-gotitle>You'll be redirected to WhatsApp to send your enquiry.</p>
      <p class="rd-enq__intro">Your message is ready. Just press Send in WhatsApp. If WhatsApp doesn't open, use the button below.</p>
      <a class="rd-btn rd-btn--dark rd-btn--block" href="#" data-enq-link target="_blank" rel="noopener">Open WhatsApp</a>
      <button type="button" class="rd-enq__back" data-enq-back>Edit my enquiry</button>
    </div>
  </div>
</div>
