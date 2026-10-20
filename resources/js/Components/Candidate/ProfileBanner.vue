<script setup>
import { computed } from 'vue';

const props = defineProps({
	name: { type: String, required: true },
	headline: { type: String, default: null },
	// [{ icon, label }]; empty labels are skipped.
	items: { type: Array, default: () => [] },
	tag: { type: String, default: 'h1' },
});

const initials = computed(() =>
	(props.name || '')
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);
const shown = computed(() => props.items.filter((item) => item.label));
</script>

<template>
	<div class="relative bg-gradient-to-br from-navy-800 to-navy-900 rounded-[26px] px-[34px] py-8 max-[680px]:px-6 max-[680px]:py-6 overflow-hidden">
		<div class="absolute inset-0 bg-[radial-gradient(560px_240px_at_12%_-40%,rgba(59,130,246,.32),transparent_70%)]"></div>
		<div class="relative z-[1] flex items-center gap-5 flex-wrap">
			<div class="w-[78px] h-[78px] rounded-[20px] bg-gradient-to-br from-blue-400 to-blue-700 grid place-items-center text-white font-bold font-display text-[28px] shrink-0" aria-hidden="true">{{ initials }}</div>
			<div class="min-w-0 flex-1">
				<component :is="tag" class="font-display text-white text-[30px] max-[680px]:text-[25px] font-semibold leading-[1.1]">{{ name }}</component>
				<p v-if="headline" class="text-[#dbe6fb] text-[15px] font-medium mt-1.5">{{ headline }}</p>
				<div v-if="shown.length" class="flex items-center gap-x-3.5 gap-y-1.5 flex-wrap mt-2.5 text-[#aebfdd] text-sm [&_span]:inline-flex [&_span]:items-center [&_span]:gap-1.5 [&_span]:min-w-0 [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:opacity-80 [&_svg]:shrink-0">
					<span v-for="item in shown" :key="item.label"><component :is="item.icon" aria-hidden="true" />{{ item.label }}</span>
				</div>
			</div>
			<div v-if="$slots.default" class="ml-auto flex gap-2 flex-wrap justify-end max-[760px]:ml-0 max-[760px]:justify-start">
				<slot />
			</div>
		</div>
	</div>
</template>
