// New-message notifications for the logged-in admin: in-page toast, system notification, "(n)" in the tab title.
// Needs an element with data-notify-endpoint and data-latest-id (admin page body, portfolio admin button).
(() => {
  const root = document.querySelector('[data-notify-endpoint]');
  if (!root) return;

  const POLL_EVERY = 20000;
  const endpoint = root.dataset.notifyEndpoint;
  const adminUrl = root.dataset.adminUrl || endpoint.replace(/api\.php.*$/, '');
  const baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
  const canNotify = 'Notification' in window && window.isSecureContext;
  let latestId = Number(root.dataset.latestId) || 0;
  let timer = 0;
  // the query string forces a real page load even when already on the admin page
  const messageUrl = (id) => `${adminUrl}?message=${id}#message-${id}`;

  /* ---------- toasts ---------- */
  const stack = document.createElement('div');
  stack.className = 'notify-stack';
  stack.setAttribute('role', 'status');
  stack.setAttribute('aria-live', 'polite');
  document.body.append(stack);

  const toast = ({ title, text, href }) => {
    const item = document.createElement(href ? 'a' : 'div');
    item.className = 'notify-toast';
    if (href) item.href = href;
    const strong = document.createElement('strong');
    strong.textContent = title;
    const span = document.createElement('span');
    span.textContent = text;
    item.append(strong, span);
    stack.prepend(item);
    // enter on the next frame so the transition runs; leave after 8s
    requestAnimationFrame(() => item.classList.add('is-in'));
    setTimeout(() => {
      item.classList.remove('is-in');
      item.addEventListener('transitionend', () => item.remove(), { once: true });
    }, 8000);
  };

  /* ---------- counters ---------- */
  const render = (unread) => {
    document.title = unread > 0 ? `(${unread}) ${baseTitle}` : baseTitle;
    document.querySelectorAll('[data-unread-count]').forEach((el) => {
      el.textContent = el.dataset.unreadCount === 'label' ? `${unread} non lu${unread > 1 ? 's' : ''}` : unread;
      el.hidden = el.dataset.unreadCount === 'label' && unread === 0;
    });
  };

  /* ---------- system notifications ---------- */
  const permissionButton = document.querySelector('[data-notify-permission]');
  const syncPermissionButton = () => {
    if (!permissionButton) return;
    permissionButton.hidden = !canNotify || Notification.permission !== 'default';
  };
  permissionButton?.addEventListener('click', async () => {
    await Notification.requestPermission();
    syncPermissionButton();
    if (Notification.permission === 'granted') toast({ title: 'Notifications activées', text: 'Vous serez prévenu à chaque nouveau message.' });
  });
  syncPermissionButton();

  const systemNotify = (message) => {
    if (!canNotify || Notification.permission !== 'granted') return;
    const notification = new Notification(`Nouveau message de ${message.name}`, {
      body: message.subject,
      icon: root.dataset.notifyIcon || undefined,
      tag: `message-${message.id}`,
    });
    notification.onclick = () => {
      window.focus();
      location.href = messageUrl(message.id);
      notification.close();
    };
  };

  /* ---------- polling ---------- */
  const check = async () => {
    try {
      const response = await fetch(`${endpoint}?since=${latestId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (response.status === 401) return stop(); // session ended
      if (!response.ok) return;
      const data = await response.json();
      [...data.messages].reverse().forEach((message) => {
        toast({ title: `Nouveau message de ${message.name}`, text: message.subject, href: messageUrl(message.id) });
        systemNotify(message);
      });
      if (data.messages.length) root.dispatchEvent(new CustomEvent('notify:new', { detail: data }));
      latestId = Math.max(latestId, data.latestId);
      render(data.unread);
    } catch {
      // offline or server restarting: try again on the next tick
    }
  };

  const stop = () => { clearInterval(timer); timer = 0; };
  const start = () => { if (!timer) timer = setInterval(check, POLL_EVERY); };

  // no polling in a hidden tab; catch up as soon as it is visible again
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stop();
    else { check(); start(); }
  });
  if (!document.hidden) start();
})();
