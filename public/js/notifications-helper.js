// Simple frontend helper for testing notifications
// Usage:
//   NotificationsHelper.simulate({token: 't=...', title: 'Hi', message: 'hello'})
//   NotificationsHelper.emitMock(payload) // dispatches window event matching broadcastAs

window.NotificationsHelper = (function () {
  async function simulate({ token, title = 'Test', message = 'Simulated', type = 'test' } = {}) {
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const res = await fetch('/api/notifications/simulate', {
      method: 'POST',
      headers,
      body: JSON.stringify({ title, message, type }),
      credentials: token ? 'omit' : 'include',
    });

    return res.json();
  }

  function emitMock(payload) {
    // payload should match the server broadcastWith() shape
    const ev = new CustomEvent('notification.created', { detail: payload });
    window.dispatchEvent(ev);
  }

  return {
    simulate,
    emitMock,
  };
})();
