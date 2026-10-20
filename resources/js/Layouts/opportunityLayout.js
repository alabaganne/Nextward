import { h } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppShell from './AppShell.vue';
import PublicLayout from './Public.vue';
import { isAppPath } from '@/utils/url';

// /app pages get the workspace; the same page anywhere else is the public site, signed in or not.
export default (_h, page) =>
	isAppPath(usePage().url)
		? h(AppShell, { active: 'opportunities' }, () => page)
		: h(PublicLayout, () => page);
