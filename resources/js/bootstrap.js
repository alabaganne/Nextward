import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Axios automatically sends the CSRF token as a header based on the value
 * of the "XSRF" token cookie.
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel.
 */
window.Pusher = Pusher;

if (import.meta.env.VITE_PUSHER_APP_KEY) {
    const scheme = import.meta.env.VITE_PUSHER_SCHEME || 'https';

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
        wsPort: import.meta.env.VITE_PUSHER_PORT || 80,
        wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
