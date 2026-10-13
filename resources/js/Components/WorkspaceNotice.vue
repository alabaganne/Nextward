<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, X } from '@lucide/vue';

const page = usePage();
const dismissed = ref(false);
watch(() => page.props.toast, () => { dismissed.value = false; });
</script>

<template>
	<div v-if="page.props.toast?.message && !dismissed" class="fixed bottom-5 right-5 z-30 flex items-start gap-3 max-w-[calc(100vw-40px)] rounded-xl border border-blue-200 bg-white p-4 text-sm text-ink" role="status">
		<CheckCircle2 :size="20" class="shrink-0 text-blue-600" aria-hidden="true" />
		<p>{{ page.props.toast.message }}</p>
		<button class="shrink-0 text-muted hover:text-ink" type="button" aria-label="Dismiss notification" @click="dismissed = true"><X :size="18" aria-hidden="true" /></button>
	</div>
</template>
