<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, BellOff, Trash2, X } from '@lucide/vue';

const props = defineProps({
	open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const page = usePage();
const notifications = computed(() => page.props.auth?.user?.notifications?.data ?? []);
const unreadCount = computed(() => page.props.auth?.user?.notifications?.unread_count ?? 0);

const onKeydown = (event) => {
	if (event.key === 'Escape') emit('close');
};

watch(
	() => props.open,
	(open) => {
		document.body.style.overflow = open ? 'hidden' : '';
		if (open) window.addEventListener('keydown', onKeydown);
		else window.removeEventListener('keydown', onKeydown);
	}
);

onBeforeUnmount(() => {
	window.removeEventListener('keydown', onKeydown);
	document.body.style.overflow = '';
});

// Marks the notification read server-side, which then redirects to its action page.
const openNotification = (notification) => {
	emit('close');
	router.post(route('notifications.store', notification.id));
};

const clearAll = () => {
	if (!confirm('Delete all your notifications? This cannot be undone.')) return;
	router.delete(route('notifications.clear'), { preserveScroll: true });
};
</script>

<template>
	<Teleport to="body">
		<Transition
			enter-active-class="transition-opacity duration-200"
			enter-from-class="opacity-0"
			leave-active-class="transition-opacity duration-200"
			leave-to-class="opacity-0"
		>
			<div v-if="open" class="fixed inset-0 z-40 bg-navy-900/45" @click="emit('close')"></div>
		</Transition>

		<Transition
			enter-active-class="transition-transform duration-300 ease-out"
			enter-from-class="translate-x-full"
			leave-active-class="transition-transform duration-200 ease-in"
			leave-to-class="translate-x-full"
		>
			<aside
				v-if="open"
				class="fixed top-0 right-0 z-50 h-full w-[400px] max-w-[92vw] bg-white border-l border-line flex flex-col"
				role="dialog"
				aria-label="Notifications"
			>
				<header class="flex items-center gap-3 px-6 py-5 border-b border-line-2">
					<h2 class="font-display text-[19px] font-semibold tracking-[-.01em]">Notifications</h2>
					<span v-if="unreadCount" class="text-xs font-bold text-blue-700 bg-blue-50 px-[9px] py-[3px] rounded-full">{{ unreadCount }} new</span>
					<button
						class="ml-auto w-9 h-9 rounded-[10px] border border-line grid place-items-center text-muted hover:border-blue-300 hover:text-blue-700 transition-colors"
						type="button"
						aria-label="Close notifications"
						@click="emit('close')"
					><X :size="17" /></button>
				</header>

				<div v-if="notifications.length" class="flex-1 overflow-y-auto">
					<button
						v-for="notification in notifications"
						:key="notification.id"
						class="w-full text-left flex gap-3.5 px-6 py-[18px] border-b border-line-2 transition-colors cursor-pointer"
						:class="notification.read_at ? 'hover:bg-paper-2' : 'bg-blue-50/40 hover:bg-blue-50/70'"
						type="button"
						@click="openNotification(notification)"
					>
						<span
							class="w-10 h-10 rounded-[11px] grid place-items-center shrink-0"
							:class="notification.read_at ? 'bg-paper-2 text-muted-2' : 'bg-blue-50 text-blue-600'"
						><Bell :size="18" /></span>
						<span class="flex-1 min-w-0">
							<b class="block text-[14px] font-semibold text-ink leading-snug">{{ notification.data.title }}</b>
							<span class="block text-[13.5px] text-muted leading-[1.55] mt-0.5">{{ notification.data.body_text }}</span>
							<span class="block text-xs text-muted-2 mt-1.5">{{ notification.created_at }}</span>
						</span>
						<span v-if="!notification.read_at" class="w-2 h-2 rounded-full bg-blue-600 mt-2 shrink-0" aria-label="Unread"></span>
					</button>
				</div>

				<div v-else class="flex-1 grid place-items-center px-8">
					<div class="text-center">
						<div class="w-14 h-14 rounded-full bg-paper-2 border border-line-2 grid place-items-center text-muted-2 mx-auto mb-4"><BellOff :size="22" /></div>
						<b class="block font-display text-[16px] font-semibold">You're all caught up</b>
						<p class="text-[13.5px] text-muted mt-1">Application updates will land here.</p>
					</div>
				</div>

				<footer v-if="notifications.length" class="px-6 py-4 border-t border-line-2 bg-white">
					<button
						class="w-full inline-flex items-center justify-center gap-2 font-semibold text-[14px] h-[42px] rounded-[12px] bg-[#dc2626] text-white hover:bg-[#b91c1c] transition-colors"
						type="button"
						@click="clearAll"
					><Trash2 :size="16" /> Clear all notifications</button>
				</footer>
			</aside>
		</Transition>
	</Teleport>
</template>
