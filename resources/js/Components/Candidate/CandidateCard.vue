<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Briefcase, FileText, GraduationCap, MapPin } from '@lucide/vue';

const props = defineProps({ candidate: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const SKILLS = 6;
const initials = computed(() => (props.candidate.name || '').split(/\s+/).map((word) => word[0]).filter(Boolean).slice(0, 2).join('').toUpperCase());
const years = computed(() => {
	const count = props.candidate.experience_years;
	if (count === null || count === undefined) return null;
	return fr.value ? `${count} ${count > 1 ? 'ans' : 'an'} d’expérience` : `${count} ${count === 1 ? 'year' : 'years'} of experience`;
});
</script>

<template>
	<Link :href="candidate.url" class="group flex flex-col min-w-0 bg-white border border-line rounded-card transition-colors duration-180 hover:border-brand/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
		<div class="flex items-start gap-4 p-6 pb-5">
			<span class="grid place-items-center shrink-0 size-14 rounded-card bg-brand text-white text-xl font-medium" aria-hidden="true">{{ initials }}</span>
			<div class="min-w-0 flex-1">
				<h2 class="text-[19px] font-medium leading-tight text-navy truncate transition-colors duration-180 group-hover:text-brand">{{ candidate.name }}</h2>
				<p class="text-[15px] text-ink-700 mt-1.5 line-clamp-2">{{ candidate.headline || candidate.field || text('Candidate', 'Candidat') }}</p>
			</div>
			<span v-if="candidate.has_resume" class="inline-flex items-center gap-1 shrink-0 rounded-md bg-green-50 text-green-700 text-[12.5px] font-medium px-2.5 py-1"><FileText :size="13" aria-hidden="true" />{{ text('Resume', 'CV') }}</span>
		</div>

		<div class="px-6 flex-1">
			<ul v-if="(candidate.field && candidate.headline) || candidate.location || years" class="flex flex-wrap gap-x-4 gap-y-1.5 text-[14px] text-subtle [&_li]:inline-flex [&_li]:items-center [&_li]:gap-1.5 [&_li]:min-w-0 [&_svg]:shrink-0 [&_svg]:text-subtle-2">
				<li v-if="candidate.field && candidate.headline"><GraduationCap :size="15" aria-hidden="true" />{{ candidate.field }}</li>
				<li v-if="candidate.location"><MapPin :size="15" aria-hidden="true" />{{ candidate.location }}</li>
				<li v-if="years"><Briefcase :size="15" aria-hidden="true" />{{ years }}</li>
			</ul>
			<p v-if="candidate.summary" class="mt-4 text-[15px] leading-[1.7] text-subtle line-clamp-3" dir="auto">{{ candidate.summary }}</p>
			<ul v-if="candidate.skills.length" class="mt-4 flex flex-wrap gap-1.5" :aria-label="text('Skills', 'Compétences')">
				<li v-for="skill in candidate.skills.slice(0, SKILLS)" :key="skill" class="px-2.5 py-1 rounded-md bg-blue-50 text-brand text-[12.5px]">{{ skill }}</li>
				<li v-if="candidate.skills_count > SKILLS" class="px-2.5 py-1 rounded-md bg-slate-100 text-navy text-[12.5px]">+{{ candidate.skills_count - SKILLS }}</li>
			</ul>
		</div>

		<div class="mt-5 mx-6 py-4 border-t border-line flex items-center gap-2 text-[13px] font-medium tracking-caps uppercase text-brand">
			{{ text('View profile', 'Voir le profil') }}<ArrowRight :size="16" class="transition-transform group-hover:translate-x-[3px]" aria-hidden="true" />
		</div>
	</Link>
</template>
