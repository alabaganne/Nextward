<script setup>
import { ref } from 'vue';
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
</script>

<template>
	<div class="workspace-shell font-body text-ink grid grid-cols-[248px_1fr] max-[900px]:grid-cols-1 bg-paper-2" :class="fill ? 'h-dvh' : 'min-h-screen'" @keydown.esc="navigationOpen = false">
		<button v-if="navigationOpen" class="hidden max-[900px]:block fixed inset-0 bg-navy-900/45 z-40" type="button" aria-label="Close navigation" @click="navigationOpen = false"></button>
		<AppSidebar :active="active" :mobile-open="navigationOpen" @close="navigationOpen = false" />
		<div class="flex flex-col min-w-0" :class="{ 'overflow-hidden': fill }">
			<AppTopbar :navigation-open="navigationOpen" @navigation="navigationOpen = !navigationOpen" @notifications="notificationsOpen = true" />
			<slot />
		</div>
		<NotificationsPanel :open="notificationsOpen" @close="notificationsOpen = false" />
		<WorkspaceNotice />
	</div>
</template>
