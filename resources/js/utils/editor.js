import { onBeforeUnmount, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Ctrl+S (Cmd+S on a Mac) saves, and leaving with unsaved changes asks first.
// Call allowLeave() right before a visit the page has already confirmed.
export function useEditorGuards({ dirty, save }) {
	let allowed = false;
	const onKey = (event) => {
		if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
			event.preventDefault();
			save();
		}
	};
	const onUnload = (event) => {
		if (!dirty.value) return;
		event.preventDefault();
		event.returnValue = '';
	};
	let stop = null;
	onMounted(() => {
		window.addEventListener('keydown', onKey);
		window.addEventListener('beforeunload', onUnload);
		stop = router.on('before', (event) => {
			const visit = event.detail.visit;
			if (allowed || !dirty.value || visit.method !== 'get' || visit.preserveState) return;
			if (!window.confirm('You have unsaved changes. Leave without saving them?')) event.preventDefault();
		});
	});
	onBeforeUnmount(() => {
		window.removeEventListener('keydown', onKey);
		window.removeEventListener('beforeunload', onUnload);
		stop?.();
	});
	return { allowLeave: () => { allowed = true; } };
}
