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
	<section class="bg-white border border-line rounded-card p-6" aria-labelledby="company-hiring-title">
		<h2 id="company-hiring-title" class="text-[20px] font-medium text-navy">{{ text('Hiring on Nextward', 'Recrute sur Nextward') }}</h2>
		<dl class="mt-4 grid grid-cols-3 gap-2">
			<div v-for="stat in stats" :key="stat.label" class="rounded-card bg-pale px-2 py-3.5 text-center">
				<dd class="text-[26px] font-medium leading-none" :class="stat.value ? 'text-navy' : 'text-slate-400'">{{ stat.value }}</dd>
				<dt class="mt-2 text-[12px]" :class="stat.value ? 'text-brand' : 'text-subtle-2'">{{ stat.label }}</dt>
			</div>
		</dl>
		<template v-if="hiring.open">
			<ul v-if="modes.length" class="mt-4 flex flex-wrap gap-2 text-[13px]" :aria-label="text('Work modes', 'Modes de travail')">
				<li v-for="mode in modes" :key="mode.key" class="rounded-md bg-slate-100 px-2.5 py-1 text-navy">{{ mode.label }} <span class="text-subtle-2">{{ mode.count }}</span></li>
			</ul>
			<div v-for="group in groups" :key="group.key" class="mt-5">
				<h3 class="text-[12px] tracking-caps uppercase text-subtle">{{ group.title }}</h3>
				<ul class="mt-2 flex flex-wrap gap-2 text-[13px]">
					<li v-for="item in group.items" :key="item.name" class="rounded-md bg-slate-100 px-2.5 py-1 text-navy">{{ item.name }} <span class="text-subtle-2">{{ item.count }}</span></li>
				</ul>
			</div>
			<div v-if="skills.length" class="mt-5">
				<h3 class="text-[12px] tracking-caps uppercase text-subtle">{{ text('Skills they ask for', 'Compétences demandées') }}</h3>
				<ul class="mt-2 flex flex-wrap gap-2">
					<li v-for="(skill, index) in skills" :key="skill.name" class="px-3 py-1.5 rounded-md text-[13px]" :class="index < 4 ? 'bg-brand text-white' : 'bg-blue-50 text-brand'">{{ skill.name }}</li>
				</ul>
			</div>
		</template>
		<p v-else class="mt-4 text-[14px] text-slate-400">{{ text('No open roles right now.', 'Aucune offre ouverte pour le moment.') }}</p>
	</section>
</template>
