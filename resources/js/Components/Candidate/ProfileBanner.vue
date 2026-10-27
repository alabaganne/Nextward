<script setup>
import { computed } from 'vue';

const props = defineProps({
	name: { type: String, required: true },
	headline: { type: String, default: null },
	// [{ icon, label }]; empty labels are skipped.
	items: { type: Array, default: () => [] },
	tag: { type: String, default: 'h1' },
	// Sits straight on a navy page header instead of drawing its own card.
	flat: Boolean,
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
	<div class="relative" :class="!flat && 'line-art overflow-hidden rounded-card bg-navy px-[34px] py-8 max-[680px]:px-6 max-[680px]:py-6'">
		<div class="relative z-[1] flex items-center gap-5 flex-wrap">
			<slot name="avatar">
				<div class="grid size-[78px] shrink-0 place-items-center rounded-card bg-brand text-[28px] font-medium text-white" aria-hidden="true">{{ initials }}</div>
			</slot>
			<div class="min-w-0 flex-1">
				<div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
					<component :is="tag" class="font-medium leading-[1.15] text-white break-words" :class="flat ? 'text-[length:clamp(30px,3.6vw,46px)]' : 'text-[30px] max-[680px]:text-[25px]'">{{ name }}</component>
					<slot name="badge" />
				</div>
				<p v-if="headline" class="mt-2 text-[16px] text-mist">{{ headline }}</p>
				<div v-if="shown.length" class="flex items-center gap-x-4 gap-y-1.5 flex-wrap mt-3 text-mist-2 text-[14px] [&_span]:inline-flex [&_span]:items-center [&_span]:gap-1.5 [&_span]:min-w-0 [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:shrink-0">
					<span v-for="item in shown" :key="item.label"><component :is="item.icon" aria-hidden="true" />{{ item.label }}</span>
				</div>
			</div>
			<div v-if="$slots.default" class="ml-auto flex gap-2 flex-wrap justify-end max-[760px]:ml-0 max-[760px]:w-full max-[760px]:justify-start">
				<slot />
			</div>
		</div>
	</div>
</template>
