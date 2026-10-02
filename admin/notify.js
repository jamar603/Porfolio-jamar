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

  /* ---------- toasts (Sonner-style stack) ---------- */
  // newest in front, older ones peek behind; hovering expands the stack and pauses every countdown
  const VISIBLE = 3;
  const MAX = 5;
  const GAP = 12;
  const PEEK = 14;
  const SWIPE_THRESHOLD = 45;
  const stack = document.createElement('ol');
  stack.className = 'notify-stack';
  stack.setAttribute('aria-live', 'polite');
  stack.setAttribute('aria-label', 'Notifications');
  document.body.append(stack);
  const toasts = []; // newest first

  const layout = () => {
    const expanded = stack.classList.contains('is-expanded');
    const front = toasts[0]?.offsetHeightNatural || 0;
    let offset = 0;
    toasts.forEach((item, index) => {
      const el = item.el;
      const hiddenBehind = index >= VISIBLE;
      el.style.zIndex = String(MAX - index);
      el.dataset.front = String(index === 0);
      if (expanded) {
        el.style.height = `${item.offsetHeightNatural}px`;
        el.style.transform = `translateY(${-offset}px)`;
        offset += item.offsetHeightNatural + GAP;
      } else {
        // older toasts take the front toast's height so only their top edge peeks out
        el.style.height = `${index === 0 ? item.offsetHeightNatural : front}px`;
        el.style.transform = `translateY(${-index * PEEK}px) scale(${1 - index * 0.05})`;
      }
      el.style.opacity = hiddenBehind ? '0' : '1';
      el.style.pointerEvents = hiddenBehind ? 'none' : '';
    });
    stack.style.height = `${expanded ? Math.max(offset - GAP, 0) : front}px`;
  };

  const dismiss = (item, direction = 'down') => {
    const index = toasts.indexOf(item);
    if (index === -1) return;
    toasts.splice(index, 1);
    item.el.dataset.leaving = direction;
    item.el.style.opacity = '0';
    if (direction === 'down') item.el.style.transform += ' translateY(16px)';
    setTimeout(() => item.el.remove(), 220);
    if (!toasts.length) stack.classList.remove('is-expanded');
    layout();
  };

  const initials = (name) => name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || '?';

  const make = (tag, className, text) => {
    const el = document.createElement(tag);
    el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  };

  const toast = ({ meta, title, text, href, actionLabel, avatar, duration = 8000 }) => {
    const el = make('li', 'notify-toast');
    el.style.setProperty('--duration', `${duration}ms`);

    const body = make('div', 'notify-body');
    const badge = make('span', avatar ? 'notify-avatar' : 'notify-icon', avatar ? initials(avatar) : '');
    badge.setAttribute('aria-hidden', 'true');
    const textBox = make('div', 'notify-text');
    if (meta) textBox.append(make('p', 'notify-meta', meta));
    textBox.append(make('p', 'notify-title', title));
    if (text) textBox.append(make('p', 'notify-desc', text));
    const close = make('button', 'notify-close', '×');
    close.type = 'button';
    close.setAttribute('aria-label', 'Fermer la notification');
    body.append(badge, textBox, close);
    el.append(body);

    if (href) {
      const action = make('a', 'notify-action', actionLabel || 'Ouvrir');
      action.href = href;
      el.append(action);
    }
    const progress = make('span', 'notify-progress');
    el.append(progress);

    const item = { el, offsetHeightNatural: 0 };
    stack.prepend(el);
    item.offsetHeightNatural = el.offsetHeight;
    toasts.unshift(item);
    toasts.slice(MAX).forEach((old) => dismiss(old));

    // start below the screen edge, then let layout() transition it into place
    el.style.transform = 'translateY(100%)';
    el.style.opacity = '0';
    requestAnimationFrame(() => requestAnimationFrame(() => { el.dataset.mounted = 'true'; layout(); }));

    close.addEventListener('click', () => dismiss(item));
    // the countdown is a CSS animation, so hovering the stack or hiding the tab pauses it for free
    progress.addEventListener('animationend', () => dismiss(item));

    /* swipe right to dismiss: distance or a quick flick */
    let start = null;
    el.addEventListener('pointerdown', (event) => {
      if (start || event.button !== 0 || event.target.closest('a, button')) return;
      start = { x: event.clientX, time: performance.now() };
      el.setPointerCapture(event.pointerId);
      el.dataset.swiping = 'true';
    });
    el.addEventListener('pointermove', (event) => {
      if (!start) return;
      const dx = event.clientX - start.x;
      // dragging the wrong way meets growing friction instead of a wall
      el.style.translate = `${dx > 0 ? dx : dx * 0.15}px 0`;
    });
    const endSwipe = (event) => {
      if (!start) return;
      const dx = event.clientX - start.x;
      const velocity = Math.abs(dx) / (performance.now() - start.time);
      start = null;
      delete el.dataset.swiping;
      if (dx > SWIPE_THRESHOLD || (dx > 0 && velocity > 0.11)) {
        el.style.translate = '110% 0';
        dismiss(item, 'right');
      } else {
        el.style.translate = '0 0';
      }
    };
    el.addEventListener('pointerup', endSwipe);
    el.addEventListener('pointercancel', endSwipe);
  };

  stack.addEventListener('mouseenter', () => { stack.classList.add('is-expanded'); layout(); });
  stack.addEventListener('mouseleave', () => { stack.classList.remove('is-expanded'); layout(); });
  document.addEventListener('visibilitychange', () => stack.classList.toggle('is-paused', document.hidden));

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
    if (Notification.permission === 'granted') toast({ title: 'Notifications activées', text: 'Vous serez prévenu à chaque nouveau message.', duration: 4000 });
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
        toast({ meta: 'Nouveau message · à l’instant', title: message.name, text: message.subject, avatar: message.name, href: messageUrl(message.id), actionLabel: 'Lire le message →' });
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
