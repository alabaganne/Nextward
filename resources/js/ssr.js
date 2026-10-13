import { createSSRApp, Fragment, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { route as ziggyRoute } from '../../vendor/tightenco/ziggy/dist/index.js';
import EmptyLayout from './Layouts/Empty.vue';
import Icon from './Shared/Icon.vue';
import BrandLogo from './Shared/BrandLogo.vue';
import PageHead from './Shared/PageHead.vue';

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
createServer((page) => createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) => {
        const component = pages[`./Pages/${name}.vue`].default;
        component.layout ??= EmptyLayout;
        return component;
    },
    setup({ App, props, plugin }) {
        const app = createSSRApp({ render: () => h(Fragment, [h(App, props), h(PageHead)]) }).use(plugin);
        const ziggy = { ...page.props.ziggy, location: new URL(page.props.ziggy.location) };
        app.config.globalProperties.route = (name, params, absolute) => ziggyRoute(name, params, absolute, ziggy);
        Object.defineProperty(app.config.globalProperties, 'currentUser', { get: () => page.props.auth?.user });
        app.directive('reveal', { getSSRProps: () => ({}) });
        app.component('Link', Link);
        app.component('InertiaLink', Link);
        app.component('Icon', Icon);
        app.component('BrandLogo', BrandLogo);
        return app;
    },
}), { host: '127.0.0.1' });
