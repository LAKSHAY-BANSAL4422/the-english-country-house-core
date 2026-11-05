/* ECH live-chat visitor widget */
(function () {
  const API = window.ECH.base + '/api/chat.php';
  const fab = document.getElementById('chatFab'), panel = document.getElementById('chatPanel');
  const body = document.getElementById('chatBody'), input = document.getElementById('chatInput');
  const sendBtn = document.getElementById('chatSend'), badge = document.getElementById('chatBadge');
  const sub = document.getElementById('chatSub');
  if (!fab) return;

  let lastId = 0, pollTimer = null, started = false, closedByAgent = false;

  const post = (action, data = {}) => {
    const fd = new FormData();
    fd.append('action', action); fd.append('csrf', window.ECH.csrf);
    fd.append('page', location.pathname);
    Object.keys(data).forEach(k => fd.append(k, data[k]));
    return fetch(API, { method: 'POST', body: fd }).then(r => r.json());
  };

  const scrollDown = () => { body.scrollTop = body.scrollHeight; };
  const el = (html) => { const d = document.createElement('div'); d.innerHTML = html; return d.firstElementChild; };
  const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

  function addMsg(m) {
    if (m.id && m.id <= lastId) return;
    if (m.id) lastId = Math.max(lastId, m.id);
    const cls = m.sender === 'visitor' ? 'visitor' : (m.sender === 'system' ? 'system' : (m.sender === 'agent' ? 'agent' : 'bot'));
    const who = m.sender === 'agent' && m.agent_name ? `<b style="font-size:11px;color:var(--c-primary)">${esc(m.agent_name)}</b><br>` : '';
    body.appendChild(el(`<div class="cmsg ${cls}">${who}${esc(m.message)}<span class="cm-time">${m.time || ''}</span></div>`));
    scrollDown();
    if (!panel.classList.contains('open') && m.sender !== 'visitor') {
      badge.style.display = 'flex';
      badge.textContent = (parseInt(badge.textContent || '0', 10) + 1) || 1;
      const bb = document.getElementById('bnChatBadge');
      if (bb) { bb.style.display = 'flex'; bb.textContent = badge.textContent; }
    }
  }

  function addOptions(opts) {
    body.querySelectorAll('.chat-opts').forEach(o => o.remove());
    const wrap = el('<div class="chat-opts"></div>');
    opts.forEach(o => {
      const b = document.createElement('button');
      b.textContent = o.label;
      b.onclick = () => { wrap.remove(); handleAction(o); };
      wrap.appendChild(b);
    });
    body.appendChild(wrap); scrollDown();
  }

  function handleAction(o) {
    if (o.action === 'link') { window.open(o.url, '_blank'); post('choose', { choice: 'whatsapp' }).then(applyState); return; }
    if (o.action === 'choose') { post('choose', { choice: o.value }).then(applyState); return; }
  }

  function showTyping(ms) {
    const t = el('<div class="typing"><span></span><span></span><span></span></div>');
    body.appendChild(t); scrollDown();
    return new Promise(res => setTimeout(() => { t.remove(); res(); }, ms || 700));
  }

  function askContact() {
    if (document.getElementById('chatContactForm')) return;
    const f = el(`<form id="chatContactForm" class="cmsg bot" style="max-width:92%">
      <b>Before I connect you</b> — please share:
      <input class="input" style="margin:8px 0 6px" name="name" placeholder="Your name" required>
      <input class="input" style="margin-bottom:8px" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="Mobile number" required>
      <button class="btn btn-primary btn-sm" type="submit">Start Chat</button></form>`);
    f.onsubmit = (e) => {
      e.preventDefault();
      post('info', { name: f.name.value, phone: f.phone.value }).then(d => { f.remove(); applyState(d); });
    };
    body.appendChild(f); scrollDown();
  }

  function showRating() {
    if (document.getElementById('chatRate')) return;
    const r = el(`<div class="cmsg bot" id="chatRate">How was your chat experience?<div class="chat-rate">
      ${[1,2,3,4,5].map(i => `<i class="fa-solid fa-star" data-v="${i}"></i>`).join('')}</div></div>`);
    r.querySelectorAll('i').forEach(star => star.onclick = () => {
      const v = +star.dataset.v;
      r.querySelectorAll('i').forEach(s => s.classList.toggle('on', +s.dataset.v <= v));
      post('rate', { rating: v });
      setTimeout(() => { r.querySelector('.chat-rate').outerHTML = '<p style="color:#2e8b57;font-size:13px;margin-top:6px">Thank you for your feedback! 💛</p>'; }, 300);
    });
    body.appendChild(r); scrollDown();
  }

  function applyState(d) {
    if (!d || !d.ok) return;
    (d.messages || []).forEach(addMsg);
    if (d.options && d.options.length) addOptions(d.options);
    if (d.need_contact) askContact();
    if (d.agent_name) { document.getElementById('chatTitle').textContent = d.agent_name; sub.textContent = d.mode === 'agent' ? '🟢 Online — replying to you' : 'Connecting…'; }
    else if (d.mode === 'waiting') sub.textContent = '⏳ Finding an agent for you…';
    else sub.textContent = d.agents_online > 0 ? `🟢 ${d.agents_online} agent(s) online` : 'We reply within minutes';
    if (d.status === 'closed' && !closedByAgent) { closedByAgent = true; showRating(); }
  }

  function poll() {
    if (!started) return;
    post('poll', { last_id: lastId }).then(applyState).catch(() => {});
  }

  function start() {
    if (started) { return; }
    started = true;
    post('start').then(d => { applyState(d); pollTimer = setInterval(poll, 3000); });
  }

  const bnChat = document.getElementById('bnChat'), bnBadge = document.getElementById('bnChatBadge');
  const togglePanel = (e) => {
    if (e) e.preventDefault();
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) { badge.style.display = 'none'; badge.textContent = '0'; if (bnBadge) { bnBadge.style.display = 'none'; bnBadge.textContent = '0'; } start(); input.focus(); }
  };
  fab.addEventListener('click', togglePanel);
  if (bnChat) bnChat.addEventListener('click', togglePanel);   // mobile bottom-menu "Chat"
  if (location.hash === '#chat') togglePanel();
  document.getElementById('chatClose').addEventListener('click', () => panel.classList.remove('open'));

  async function sendMsg() {
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    addMsg({ sender: 'visitor', message: text, time: new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) });
    const d = await post('send', { message: text });
    if (d.bot_delay) await showTyping(d.bot_delay);
    applyState(d);
  }
  sendBtn.addEventListener('click', sendMsg);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); sendMsg(); } });
})();
