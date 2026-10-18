// Public URLs carry the APP_URL host; in-app links only need the path so they work on any host.
export const sitePath = (url) => url ? url.replace(/^https?:\/\/[^/]+/, '') || '/' : url;
