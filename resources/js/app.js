import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, usePage } from '@inertiajs/vue3';

import store from './store';
import AuthenticatedLayout from './Layouts/Authenticated.vue';
import Icon from './Shared/Icon.vue';
import BrandLogo from './Shared/BrandLogo.vue';
import Card from './Shared/Card.vue';
import Table from './Shared/Table.vue';

createInertiaApp({
	resolve: async (name) => {
		const pages = import.meta.glob('./Pages/**/*.vue');
		const module = await pages[`./Pages/${name}.vue`]();

		if (!module.default.layout) {
			module.default.layout = AuthenticatedLayout;
		}

		return module.default;
	},
	setup({ el, App, props, plugin }) {
		const app = createApp({
			render: () => h(App, props),
			mounted() {
				window.addEventListener('popstate', () => {
					this.$page.props.popstate = true;
				});
			},
		});

		// A global property (rather than a mixin computed) so it is also readable inside data().
		const page = usePage();
		Object.defineProperty(app.config.globalProperties, 'currentUser', {
			get: () => page.props.auth?.user,
		});

		app.mixin({
			methods: {
				route,
				truncate(value, numChars) {
					if (!value) return '';

					let str = value.substring(0, numChars).trim();
					if (numChars > value.length) {
						return str;
					} else {
						return str + '...';
					}
				},
				routeUrl() {
					return location.pathname.substring(1);
				},
			},
		})
			.use(plugin)
			.use(store)
			.component('Link', Link)
			.component('Icon', Icon)
			.component('BrandLogo', BrandLogo)
			.component('Card', Card)
			.component('AppTable', Table)
			.mount(el);
	},
	progress: {
		color: '#4B5563',
	},
});
