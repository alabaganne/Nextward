// Public URLs carry the APP_URL host; in-app links only need the path so they work on any host.
export const sitePath = (url) => url ? url.replace(/^https?:\/\/[^/]+/, '') || '/' : url;

// Signed-in pages live under /app and are the only ones with the workspace sidebar.
export const isAppPath = (url) => /^\/app(?:[/?#]|$)/.test(url || '');

// Inside /app, listing and company links open the workspace pages instead of the public ones.
export const appPath = (url) => sitePath(url)
    ?.replace(/^(?:\/fr)?\/opportunities\//, '/app/opportunities/')
    .replace(/^(?:\/fr)?\/companies\/(\d+)-[^?#]*/, '/app/companies/$1');
