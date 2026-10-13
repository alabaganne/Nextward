import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, usePage } from '@inertiajs/vue3';
import AppShell from './Layouts/AppShell.vue';
import Icon from './Shared/Icon.vue';
import BrandLogo from './Shared/BrandLogo.vue';
import reveal from './directives/reveal';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
	resolve: async (name) => {
		const module = await pages[`./Pages/${name}.vue`]();
		module.default.layout ??= AppShell;
		return module.default;
	},
	setup({ el, App, props, plugin }) {
		const app = createApp({ render: () => h(App, props) }).use(plugin);
		const page = usePage();

		app.config.globalProperties.route = window.route;
		Object.defineProperty(app.config.globalProperties, 'currentUser', {
			get: () => page.props.auth?.user,
		});

		app.directive('reveal', reveal);
		app.component('Link', Link);
		app.component('InertiaLink', Link);
		app.component('Icon', Icon);
		app.component('BrandLogo', BrandLogo);
		app.mount(el);
	},
	progress: { color: '#2563eb' },
});
