<script setup>
import { computed } from 'vue';
import { statusPill } from '@/utils/status';

const props = defineProps({
	status: { type: String, default: null },
});

const pill = computed(() => statusPill(props.status));
</script>

<template>
	<span
		class="inline-flex items-center gap-1.5 text-xs font-semibold px-[11px] py-[5px] rounded-full whitespace-nowrap"
		:style="{ color: pill.text, background: pill.bg }"
	>
		<span class="w-1.5 h-1.5 rounded-full" :style="{ background: pill.dot }"></span>
		{{ pill.label }}
	</span>
</template>
