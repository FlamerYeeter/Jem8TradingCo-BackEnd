// Notifications client: subscribe to private user channel and re-dispatch events

async function getUserId() {
  if (window.CURRENT_USER_ID) return window.CURRENT_USER_ID;
  try {
    const res = await fetch('/api/me', { credentials: 'include' });
    if (!res.ok) return null;
    const data = await res.json();
    // common shapes: { id: 1 } or { user_id: 1 }
    return data?.id ?? data?.user_id ?? null;
  } catch (e) {
    return null;
  }
}

function subscribe(userId) {
  if (!window.Echo || !userId) return;
  const channel = window.Echo.private(`user.${userId}`);
  const handler = (payload) => {
    const ev = new CustomEvent('notification.created', { detail: payload });
    window.dispatchEvent(ev);
  };

  // Listen common event name variants to be robust
  try {
    channel.listen('notification.created', handler);
    channel.listen('.notification.created', handler);
    channel.listen('NotificationCreated', handler);
  } catch (e) {
    // ignore if channel subscription fails (Echo not ready or unauthenticated)
  }
}

(async function init() {
  const userId = await getUserId();
  if (userId) {
    subscribe(userId);
    return;
  }

  // If we can't resolve user now, wait for potential auth-ready hook
  window.addEventListener('auth:ready', async () => {
    const id = await getUserId();
    if (id) subscribe(id);
  });
})();
