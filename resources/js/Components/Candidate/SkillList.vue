<script setup>
import { computed, ref } from 'vue';
import { Languages } from '@lucide/vue';

const props = defineProps({
	skills: { type: Array, default: () => [] },
	languages: { type: Array, default: () => [] },
	fr: { type: Boolean, default: false },
});

// The first skills are the ones the candidate listed first, so they lead.
const TOP = 6;
const LIMIT = 18;
const open = ref(false);
const split = computed(() => props.skills.length > TOP + 2);
const top = computed(() => (split.value ? props.skills.slice(0, TOP) : props.skills));
const rest = computed(() => (split.value ? props.skills.slice(TOP) : []));
const shown = computed(() => (open.value ? rest.value : rest.value.slice(0, LIMIT - TOP)));
const hidden = computed(() => rest.value.length - shown.value.length);
const text = (en, french) => (props.fr ? french : en);
</script>

<template>
	<div>
		<template v-if="skills.length">
			<p v-if="split" class="text-[12px] tracking-caps uppercase text-subtle mb-2.5">{{ text('Top skills', 'Compétences clés') }}</p>
			<ul class="flex flex-wrap gap-2">
				<li v-for="skill in top" :key="skill" class="px-3 py-1.5 rounded-md bg-brand text-white text-[13.5px]">{{ skill }}</li>
			</ul>
			<template v-if="rest.length">
				<p class="text-[12px] tracking-caps uppercase text-subtle mt-5 mb-2.5">{{ text('Also works with', 'Maîtrise aussi') }}</p>
				<ul class="flex flex-wrap gap-2">
					<li v-for="skill in shown" :key="skill" class="px-3 py-1.5 rounded-md bg-blue-50 text-brand text-[13.5px]">{{ skill }}</li>
				</ul>
				<button v-if="hidden > 0 || open" type="button" class="mt-3.5 text-[13px] font-medium tracking-caps uppercase text-brand transition-colors duration-180 hover:text-navy" :aria-expanded="open" @click="open = !open">
					{{ open ? text('Show fewer', 'Afficher moins') : text(`Show ${hidden} more`, `Afficher ${hidden} de plus`) }}
				</button>
			</template>
		</template>
		<p v-if="languages.length" class="flex items-start gap-2.5 text-[14.5px] text-ink-700" :class="{ 'mt-5 pt-4 border-t border-line': skills.length }">
			<Languages class="w-[18px] h-[18px] text-brand shrink-0 mt-0.5" aria-hidden="true" />
			<span><span class="text-subtle">{{ text('Speaks', 'Langues :') }}</span> <b class="font-medium text-navy" dir="auto">{{ languages.join(' · ') }}</b></span>
		</p>
	</div>
</template>
