import { router } from '@inertiajs/vue3';

const isContact = (url) => /\/contact\/?$/.test(url.pathname);
let lastPage = null;

export function trackPreviousPage(initialUrl) {
    const remember = (url) => {
        const parsed = new URL(url, window.location.origin);
        if (!isContact(parsed)) lastPage = parsed;
    };
    remember(initialUrl);
    router.on('navigate', (event) => remember(event.detail.page.url));
}

// The last page seen in this tab before the contact page, or a same-site referrer.
export function previousPageUrl() {
    const referrer = document.referrer ? new URL(document.referrer) : null;
    const url = lastPage ?? (referrer && referrer.origin === window.location.origin && !isContact(referrer) ? referrer : null);

    return url?.href ?? '';
}
