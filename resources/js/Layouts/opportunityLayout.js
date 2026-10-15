import { h } from 'vue';
import AppShell from './AppShell.vue';
import PublicLayout from './Public.vue';

// Signed-in users stay in their workspace; visitors get the public site.
export default (_h, page) =>
	page.props?.auth?.user && page.props?.locale !== 'fr'
		? h(AppShell, { active: 'internships' }, () => page)
		: h(PublicLayout, () => page);
