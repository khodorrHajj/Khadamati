import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const metaValue = (name) => document.querySelector(`meta[name="${name}"]`)?.content || undefined;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || metaValue('reverb-app-key');
const reverbHost = import.meta.env.VITE_REVERB_HOST || metaValue('reverb-host') || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT || metaValue('reverb-port') || 80;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || metaValue('reverb-scheme') || 'https';

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        },
    });

    if (import.meta.env.DEV) {
        console.info('Laravel Echo initialized for Reverb.', {
            host: reverbHost,
            port: reverbPort,
            scheme: reverbScheme,
        });
    }
} else if (import.meta.env.DEV) {
    console.warn('Laravel Echo was not initialized because VITE_REVERB_APP_KEY / REVERB_APP_KEY is missing.');
}
