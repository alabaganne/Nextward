<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
	student: { type: Object, required: true },
	compact: { type: Boolean, default: false },
});

const pills = {
	pending: { label: 'Waiting for review', text: '#b45309', bg: '#fef3c7' },
	approved: { label: 'Public', text: '#047857', bg: '#dcfce7' },
	declined: { label: 'Declined', text: '#5b6677', bg: '#eef2f9' },
};
const pill = computed(() => pills[props.student.public_status] || null);
const busy = ref(false);

const review = (decision) =>
	router.post(route('students.public.review', props.student.id), { decision }, {
		preserveScroll: true,
		onStart: () => (busy.value = true),
		onFinish: () => (busy.value = false),
	});
</script>

<template>
	<div v-if="pill" class="flex flex-wrap items-center gap-2.5">
		<span class="text-xs font-semibold px-[11px] py-[5px] rounded-full whitespace-nowrap" :style="{ color: pill.text, background: pill.bg }">{{ pill.label }}</span>
		<a class="text-[13px] font-semibold text-blue-700 hover:underline" :href="student.public_url" target="_blank" rel="noopener">{{ student.public_status === 'approved' ? 'Open page' : 'Preview' }}</a>
		<div class="flex gap-2" :class="compact ? 'w-full' : 'ml-auto'">
			<button v-if="student.public_status !== 'approved'" type="button" class="cbtn cbtn-primary !px-3.5 !py-2 !text-[13.5px]" :class="{ 'flex-1': compact }" :disabled="busy" @click="review('approve')">Approve</button>
			<button v-if="student.public_status !== 'declined'" type="button" class="cbtn cbtn-secondary !px-3.5 !py-2 !text-[13.5px]" :class="{ 'flex-1': compact }" :disabled="busy" @click="review('decline')">{{ student.public_status === 'approved' ? 'Remove from site' : 'Decline' }}</button>
		</div>
	</div>
</template>
