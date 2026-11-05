<?php
/** Live chat agent console */
$agents = q_all("SELECT id,name FROM admins WHERE active=1 AND id<>? ORDER BY name", [(int)$ADMIN['id']]);
?>
<div class="chat-console">
  <div class="cc-list" id="ccList"><p class="hint" style="padding:16px">Loading chats…</p></div>
  <div class="cc-chat">
    <div class="cc-head" id="ccHead"><span class="hint">← Select a conversation</span></div>
    <div class="cc-msgs" id="ccMsgs"></div>
    <div class="cc-input" id="ccInput" style="display:none">
      <textarea id="ccText" placeholder="Type a reply… (Enter to send, Shift+Enter for new line)"></textarea>
      <button class="btn" id="ccSend"><i class="fa-solid fa-paper-plane"></i></button>
      <button class="btn ghost" id="ccClose" title="Close chat"><i class="fa-solid fa-check-double"></i></button>
      <select class="input" id="ccTransfer" style="width:130px" title="Transfer to…">
        <option value="">Transfer…</option>
        <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="cc-side" id="ccSide"><p class="hint">Visitor info, handling tips and suggested replies appear here.</p></div>
</div>

<script>
(function(){
  const api = (action, data={}) => {
    const fd = new FormData(); fd.append('action', action); fd.append('csrf', ADMIN.csrf);
    Object.keys(data).forEach(k=>fd.append(k, data[k]));
    return fetch(ADMIN.base+'/api/agent_chat.php', {method:'POST', body:fd}).then(r=>r.json());
  };
  const esc = s => String(s||'').replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  let cur = 0, lastId = 0, chatInfo = null;
  const list = document.getElementById('ccList'), msgs = document.getElementById('ccMsgs'),
        head = document.getElementById('ccHead'), side = document.getElementById('ccSide'),
        inputRow = document.getElementById('ccInput'), text = document.getElementById('ccText');

  function renderList(chats){
    if (!chats.length) { list.innerHTML = '<p class="hint" style="padding:16px">No open chats right now. You\'ll hear a sound when a visitor arrives.</p>'; return; }
    list.innerHTML = chats.map(c=>`
      <div class="cc-item ${c.id===cur?'sel':''}" data-id="${c.id}">
        <b>${esc(c.name)}</b> ${c.agent?`<span class="badge gray" style="font-size:10px">${esc(c.agent)}</span>`:'<span class="badge orange" style="font-size:10px">unassigned</span>'}
        <div class="ci-last">${esc(c.last)}</div>
        ${c.unread>0?`<span class="wait-badge">${c.unread} new</span>`:''}
      </div>`).join('');
    list.querySelectorAll('.cc-item').forEach(el=>el.onclick = ()=>openChat(+el.dataset.id));
  }
  function refreshList(){ api('list').then(d=>{ if(d.ok) renderList(d.chats); }); }

  function renderMsgs(arr, append){
    if (!append) msgs.innerHTML = '';
    arr.forEach(m=>{
      if (m.id <= lastId) return; lastId = Math.max(lastId, m.id);
      const div = document.createElement('div');
      div.className = 'am ' + m.sender;
      div.innerHTML = (m.sender==='agent'&&m.agent_name?`<b style="font-size:10.5px;opacity:.8">${esc(m.agent_name)}</b><br>`:'') + esc(m.message) + `<span class="t">${m.time}</span>`;
      msgs.appendChild(div);
    });
    msgs.scrollTop = msgs.scrollHeight;
  }
  function renderSide(d){
    const c = d.chat;
    side.innerHTML = `
      <h4>👤 Visitor</h4>
      <p><b>${esc(c.name)||'—'}</b><br>📱 ${esc(c.phone)||'—'}<br>✉ ${esc(c.email)||'—'}<br>
      📄 ${esc(c.page)||'—'}<br>🌐 ${esc(c.ip)}<br>🕐 ${c.started}
      ${c.lead_id?`<br>🎯 Lead #${c.lead_id} <span class="badge blue">${esc(c.lead_stage)}</span> <a href="?m=leads&view=${c.lead_id}">open</a>`:''}
      ${c.rating?`<br>⭐ Rated ${c.rating}/5`:''}</p>
      <h4>💡 How to handle</h4>` +
      d.tips.map(t=>`<div class="tip">${esc(t)}</div>`).join('') +
      `<h4>⚡ Suggested replies (click to insert)</h4>` +
      d.suggestions.map(s=>`<div class="sugg" data-t="${esc(s.text)}"><b>${esc(s.title)}:</b> ${esc(s.text.substring(0,90))}…</div>`).join('');
    side.querySelectorAll('.sugg').forEach(el=>el.onclick = ()=>{ text.value = el.dataset.t; text.focus(); });
  }
  function openChat(id){
    if (cur !== id) { lastId = 0; msgs.innerHTML=''; }
    cur = id;
    api('open', {chat_id:id, last_id:lastId}).then(d=>{
      if (!d.ok) { toast(d.error||'Cannot open chat'); return; }
      chatInfo = d.chat;
      head.innerHTML = `<b>${esc(d.chat.name)||'Visitor'}</b>
        <span class="badge ${d.chat.status==='active'?'green':'gray'}">${d.chat.status}</span>
        ${d.chat.agent?`<span class="hint">agent: ${esc(d.chat.agent)}</span>`:''}
        ${d.chat.phone?`<a class="btn sm green" target="_blank" href="https://wa.me/${d.chat.phone.replace(/^\+/,'').replace(/^(?!91)/,'91')}"><i class="fa-brands fa-whatsapp"></i></a>`:''}`;
      inputRow.style.display = 'flex';
      renderMsgs(d.messages, lastId>0);
      renderSide(d);
      refreshList();
    });
  }
  function send(){
    const t = text.value.trim(); if (!t || !cur) return;
    text.value = '';
    api('reply', {chat_id:cur, message:t}).then(()=>openChat(cur));
  }
  document.getElementById('ccSend').onclick = send;
  text.addEventListener('keydown', e=>{ if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); } });
  document.getElementById('ccClose').onclick = ()=>{ if (cur && confirm('Close this chat?')) api('close',{chat_id:cur}).then(()=>{ cur=0; lastId=0; msgs.innerHTML=''; head.innerHTML='<span class="hint">← Select a conversation</span>'; inputRow.style.display='none'; refreshList(); }); };
  document.getElementById('ccTransfer').onchange = function(){ if (cur && this.value) api('transfer',{chat_id:cur,to_agent:this.value}).then(()=>{ toast('Transferred'); this.value=''; refreshList(); }); };

  setInterval(()=>{ refreshList(); if (cur) openChat(cur); }, 4000);
  refreshList();
})();
</script>
