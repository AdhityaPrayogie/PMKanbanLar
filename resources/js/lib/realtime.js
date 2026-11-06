// One shared Reverb (WebSocket) connection per tab, listening on the private "user.{id}" channel.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo = null;
let refs = 0;
const channelName = () => `user.${window.__USER__?.id}`;

// Returns an unsubscribe fn. In Alpine call it from destroy().
export function subscribeRealtime(handlers) {
  if (!echo) {
    echo = new Echo({
      broadcaster: 'reverb',
      Pusher,
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST || location.hostname,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/api/broadcasting/auth',
    });
  }
  refs++;
  const channel = echo.private(channelName());

  const wrapped = Object.entries(handlers).map(([event, fn]) => {
    const listener = (data) => {
      try {
        fn(data);
      } catch (err) {
        console.error(`Realtime "${event}" handler error:`, err);
      }
    };
    channel.listen(`.${event}`, listener); // leading dot = custom event name (Broadcast::as)
    return [event, listener];
  });

  return () => {
    wrapped.forEach(([event, listener]) => channel.stopListening(`.${event}`, listener));
    if (--refs === 0) {
      echo?.disconnect();
      echo = null;
    }
  };
}
