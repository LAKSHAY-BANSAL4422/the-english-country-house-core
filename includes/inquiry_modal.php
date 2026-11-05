<?php /* Booking-enquiry popup listing all accommodation */
$__inqRooms = public_rooms();   // round 5: no room prices shown
?>
<div class="modal" id="inquiryModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('inquiryModal')">&times;</button>
    <p class="inq-room" role="heading" aria-level="2" style="margin-bottom:6px;font-family:var(--f-heading);font-weight:600;line-height:1.25;font-size:1.17em;color:var(--c-heading)">Booking Enquiry</p>
    <p style="font-size:14px;color:#78818d;margin-bottom:18px">Tell us about your stay — our team will reply within minutes on phone / WhatsApp.</p>
    <form onsubmit="return submitBuiltForm(this)" data-form="inquiry">
      <div class="form-grid">
        <div><label class="req">Your Name</label><input type="text" name="name" required></div>
        <div><label class="req">Mobile Number</label><input type="tel" name="phone" required inputmode="tel" autocomplete="tel"></div>
        <div><label>Email</label><input type="email" name="email"></div>
        <div><label class="req">Accommodation</label>
          <select name="room_id" required>
            <?php foreach ($__inqRooms as $ir): ?>
              <option value="<?= $ir['id'] ?>"><?= e($ir['name']) ?><?= $ir['pets_allowed'] ? ' · Pet friendly' : '' ?></option>
            <?php endforeach; ?>
            <option value="0">Not sure — suggest me the best room</option>
          </select></div>
        <div><label class="req">Check-in</label><input type="date" name="checkin" data-min-today required></div>
        <div><label class="req">Check-out</label><input type="date" name="checkout" data-min-today required></div>
        <div><label>Adults</label><select name="adults"><?php for($i=1;$i<=6;$i++): ?><option <?= $i==2?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
        <div><label>Children</label><select name="children"><?php for($i=0;$i<=4;$i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
        <div class="full"><label>Message</label><textarea name="message" placeholder="Any special request, occasion, pets…"></textarea></div>
      </div>
      <button class="btn btn-primary mt-2" type="submit">Send Enquiry</button>
      <div class="form-msg"></div>
    </form>
  </div>
</div>
