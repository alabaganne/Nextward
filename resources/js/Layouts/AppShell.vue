<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import NotificationsPanel from '@/Components/NotificationsPanel.vue';
import WorkspaceNotice from '@/Components/WorkspaceNotice.vue';

defineProps({
	active: { type: String, default: '' }, // 'dashboard' | 'applications' | …
	fill: { type: Boolean, default: false }, // true for full-height, internally-scrolling pages (Messages)
});

const notificationsOpen = ref(false);
const navigationOpen = ref(false);
let stopListening;
onMounted(() => { stopListening = router.on('navigate', () => { navigationOpen.value = false; }); });
onBeforeUnmount(() => stopListening?.());
</script>

<template>
	<div class="workspace-shell font-body text-ink grid grid-cols-[248px_minmax(0,1fr)] max-[900px]:grid-cols-1 bg-paper-2" :class="fill ? 'h-dvh' : 'min-h-screen'" @keydown.esc="navigationOpen = false">
		<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] rounded-lg bg-white px-4 py-2 text-sm font-semibold border border-line">Skip to content</a>
		<Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
			<button v-if="navigationOpen" class="hidden max-[900px]:block fixed inset-0 bg-navy-900/50 z-40" type="button" aria-label="Close navigation" @click="navigationOpen = false"></button>
		</Transition>
		<AppSidebar :active="active" :mobile-open="navigationOpen" @close="navigationOpen = false" />
		<div class="flex flex-col min-w-0" :class="{ 'overflow-hidden': fill }">
			<AppTopbar :navigation-open="navigationOpen" @navigation="navigationOpen = !navigationOpen" @notifications="notificationsOpen = true" />
			<main id="main" class="flex-1 flex flex-col min-h-0" tabindex="-1">
				<slot />
			</main>
		</div>
		<NotificationsPanel :open="notificationsOpen" @close="notificationsOpen = false" />
		<WorkspaceNotice />
	</div>
</template>
