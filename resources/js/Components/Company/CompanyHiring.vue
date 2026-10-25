<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// What the company hires for right now: counts, work modes, fields, places and the skills its roles ask for.
const props = defineProps({ hiring: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const stats = computed(() => [
	{ label: text('Open roles', 'Offres'), value: props.hiring.open },
	{ label: text('Jobs', 'Emplois'), value: props.hiring.jobs },
	{ label: text('Internships', 'Stages'), value: props.hiring.internships },
]);
const modes = computed(() => [
	{ key: 'remote', label: text('Remote', 'À distance') },
	{ key: 'hybrid', label: text('Hybrid', 'Hybride') },
	{ key: 'onsite', label: text('On-site', 'Sur site') },
].map((mode) => ({ ...mode, count: props.hiring.work_types?.[mode.key] || 0 })).filter((mode) => mode.count));
const groups = computed(() => [
	{ key: 'fields', title: text('Fields', 'Domaines'), items: props.hiring.fields || [] },
	{ key: 'countries', title: text('Countries', 'Pays'), items: props.hiring.countries || [] },
].filter((group) => group.items.length));
const skills = computed(() => props.hiring.skills || []);
</script>

<template>
	<section class="bg-white border border-line rounded-2xl p-6" aria-labelledby="company-hiring-title">
		<h2 id="company-hiring-title" class="font-display text-lg font-semibold">{{ text('Hiring on Nextward', 'Recrute sur Nextward') }}</h2>
		<dl class="mt-4 grid grid-cols-3 gap-2">
			<div v-for="stat in stats" :key="stat.label" class="rounded-xl px-2 py-3 text-center" :class="stat.value ? 'bg-[#EEF4FF]' : 'bg-paper-2'">
				<dd class="font-display text-2xl font-semibold leading-none" :class="stat.value ? 'text-navy-900' : 'text-slate-400'">{{ stat.value }}</dd>
				<dt class="mt-1.5 text-xs font-semibold" :class="stat.value ? 'text-blue-700' : 'text-slate-500'">{{ stat.label }}</dt>
			</div>
		</dl>
		<template v-if="hiring.open">
			<ul v-if="modes.length" class="mt-4 flex flex-wrap gap-2 text-[13px]" :aria-label="text('Work modes', 'Modes de travail')">
				<li v-for="mode in modes" :key="mode.key" class="rounded-lg bg-paper-2 px-2.5 py-1 font-semibold text-ink-700">{{ mode.label }} <span class="text-slate-500">{{ mode.count }}</span></li>
			</ul>
			<div v-for="group in groups" :key="group.key" class="mt-5">
				<h3 class="text-xs font-semibold uppercase tracking-[.08em] text-slate-500">{{ group.title }}</h3>
				<ul class="mt-2 flex flex-wrap gap-2 text-[13px]">
					<li v-for="item in group.items" :key="item.name" class="rounded-lg bg-paper-2 px-2.5 py-1 font-semibold text-ink-700">{{ item.name }} <span class="text-slate-500">{{ item.count }}</span></li>
				</ul>
			</div>
			<div v-if="skills.length" class="mt-5">
				<h3 class="text-xs font-semibold uppercase tracking-[.08em] text-slate-500">{{ text('Skills they ask for', 'Compétences demandées') }}</h3>
				<ul class="mt-2 flex flex-wrap gap-2">
					<li v-for="(skill, index) in skills" :key="skill.name" class="px-3 py-1.5 rounded-lg text-[13px] font-semibold" :class="index < 4 ? 'bg-blue-600 text-white' : 'bg-[#EEF4FF] text-blue-700'">{{ skill.name }}</li>
				</ul>
			</div>
		</template>
		<p v-else class="mt-4 text-sm text-slate-400">{{ text('No open roles right now.', 'Aucune offre ouverte pour le moment.') }}</p>
	</section>
</template>
