<?php /* Live chat visitor widget (markup only — logic in assets/js/chat.js) */ ?>
<button class="chat-fab" id="chatFab" aria-label="Chat with us">
  <i class="fa-regular fa-comments"></i>
  <span class="cf-badge" id="chatBadge" style="display:none">1</span>
</button>
<div class="chat-panel" id="chatPanel">
  <div class="chat-head">
    <div class="ch-av"><i class="fa-solid fa-headset"></i></div>
    <div class="ch-info">
      <b id="chatTitle"><?= e(setting('site_name')) ?></b>
      <span id="chatSub">We reply within minutes</span>
    </div>
    <button class="ch-close" id="chatClose" aria-label="Close">&times;</button>
  </div>
  <div class="chat-body" id="chatBody"></div>
  <div class="chat-foot" id="chatFoot">
    <input type="text" id="chatInput" placeholder="Type your message…" maxlength="1000" autocomplete="off">
    <button id="chatSend" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</div>
<script src="<?= base_url('assets/js/chat.js') ?>?v=<?= @filemtime(ROOT_PATH.'/assets/js/chat.js') ?>" defer></script>
