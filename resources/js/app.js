import './bootstrap';
import { createApp, createSSRApp, Fragment, h } from 'vue';
import { createInertiaApp, Link, usePage } from '@inertiajs/vue3';
import { route as ziggyRoute } from '../../vendor/tightenco/ziggy/dist/index.js';
import AppShell from './Layouts/AppShell.vue';
import EmptyLayout from './Layouts/Empty.vue';
import Icon from './Shared/Icon.vue';
import BrandLogo from './Shared/BrandLogo.vue';
import PageHead from './Shared/PageHead.vue';
import { startActivitySync } from './composables/useOpportunityActivity';
import { trackPreviousPage } from './composables/usePreviousPage';

const pages = import.meta.glob('./Pages/**/*.vue');
const publicPages = ['Welcome', 'About', 'Contact', 'Privacy', 'Terms', 'Blog/Index', 'Blog/Show', 'Candidates/Directory'];
createInertiaApp({
    resolve: async (name) => {
        const module = await pages[`./Pages/${name}.vue`]();
        module.default.layout ??= publicPages.includes(name) ? EmptyLayout : AppShell;
        return module.default;
    },
    setup({ el, App, props, plugin }) {
        const app = (el.hasChildNodes() ? createSSRApp : createApp)({ render: () => h(Fragment, [h(App, props), h(PageHead)]) }).use(plugin);
        const page = usePage();
        app.config.globalProperties.route = (name, params, absolute) => ziggyRoute(name, params, absolute, { ...page.props.ziggy, location: new URL(page.props.ziggy.location) });
        Object.defineProperty(app.config.globalProperties, 'currentUser', { get: () => page.props.auth?.user });
        app.component('Link', Link);
        app.component('InertiaLink', Link);
        app.component('Icon', Icon);
        app.component('BrandLogo', BrandLogo);
        app.mount(el);
        startActivitySync(page);
        trackPreviousPage(page.url);
    },
    progress: { color: '#2563eb' },
});
