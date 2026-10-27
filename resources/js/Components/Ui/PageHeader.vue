<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
	title: { type: String, required: true },
	description: { type: String, default: '' },
	// Browser tab title when it should differ from the heading.
	documentTitle: { type: String, default: '' },
	// Small uppercase label above the title.
	label: { type: String, default: '' },
	// "navy" shows a navy band with line art, like the public hero.
	tone: { type: String, default: 'plain' },
});
const navy = computed(() => props.tone === 'navy');

// Added after mount so it comes after the app-wide PageHead and its title wins.
const mounted = ref(false);
onMounted(() => { mounted.value = true; });
</script>

<template>
	<div v-if="navy" class="line-art relative mb-7 grid items-end gap-8 overflow-hidden rounded-card bg-navy px-10 pt-10 text-white max-[680px]:px-6 max-[680px]:pt-8" :class="$slots.art ? 'lg:grid-cols-[minmax(0,1fr)_auto]' : 'pb-10 max-[680px]:pb-8'">
		<Head v-if="mounted" :title="`${documentTitle || title} | Nextward`" />
		<div class="min-w-0" :class="$slots.art ? 'pb-10 max-[680px]:pb-8' : ''">
			<p v-if="label" class="mb-3 text-[13px] tracking-caps text-mist uppercase">{{ label }}</p>
			<h1 class="text-[length:clamp(30px,3vw,42px)] font-medium leading-[1.15] [&_.accent]:text-blue-500"><slot name="title">{{ title }}</slot></h1>
			<p v-if="description || $slots.description" class="mt-3 max-w-[560px] text-[16px] leading-[1.6] text-mist">
				<slot name="description">{{ description }}</slot>
			</p>
			<div v-if="$slots.actions" class="mt-7 flex flex-wrap items-center gap-3">
				<slot name="actions" />
			</div>
		</div>
		<div v-if="$slots.art" class="hidden self-end lg:block"><slot name="art" /></div>
	</div>
	<div v-else class="flex items-end justify-between flex-wrap gap-4 mb-7">
		<Head v-if="mounted" :title="`${documentTitle || title} | Nextward`" />
		<div class="min-w-0">
			<slot name="before" />
			<p v-if="label" class="mb-2.5 text-[13px] tracking-caps text-brand uppercase">{{ label }}</p>
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ title }}</h1>
			<p v-if="description || $slots.description" class="text-subtle text-[16px] leading-[1.6] mt-2.5 max-w-[680px]">
				<slot name="description">{{ description }}</slot>
			</p>
		</div>
		<div v-if="$slots.actions" class="flex items-center gap-2.5 flex-wrap">
			<slot name="actions" />
		</div>
	</div>
</template>
