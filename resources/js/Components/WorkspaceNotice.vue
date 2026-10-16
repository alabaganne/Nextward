<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from '@lucide/vue';

const page = usePage();
const dismissed = ref(false);
watch(() => page.props.toast, () => { dismissed.value = false; });

const styles = {
	success: { icon: CircleCheck, border: 'border-blue-200', color: 'text-blue-600' },
	error: { icon: CircleAlert, border: 'border-red-200', color: 'text-red-600' },
	warning: { icon: TriangleAlert, border: 'border-amber-200', color: 'text-amber-600' },
	info: { icon: Info, border: 'border-line', color: 'text-blue-600' },
};
const kinds = { error: 'error', danger: 'error', warning: 'warning', info: 'info', notification: 'info', message: 'info' };
const style = computed(() => styles[kinds[page.props.toast?.type] || 'success']);
const isError = computed(() => style.value === styles.error);
</script>

<template>
	<div v-if="page.props.toast?.message && !dismissed" class="fixed bottom-5 right-5 z-30 flex items-start gap-3 max-w-[calc(100vw-40px)] rounded-xl border bg-white p-4 text-sm text-ink" :class="style.border" :role="isError ? 'alert' : 'status'">
		<component :is="style.icon" :size="20" class="shrink-0" :class="style.color" aria-hidden="true" />
		<p>{{ page.props.toast.message }}</p>
		<button class="shrink-0 text-muted hover:text-ink" type="button" aria-label="Dismiss notification" @click="dismissed = true"><X :size="18" aria-hidden="true" /></button>
	</div>
</template>
