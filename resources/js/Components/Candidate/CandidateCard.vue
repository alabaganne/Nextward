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
	<Link :href="candidate.url" class="group flex flex-col min-w-0 bg-white border border-line rounded-[20px] transition-colors hover:border-blue-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
		<div class="flex items-start gap-4 p-6 pb-5">
			<span class="grid place-items-center shrink-0 w-14 h-14 rounded-2xl bg-blue-600 text-white font-display text-xl font-bold" aria-hidden="true">{{ initials }}</span>
			<div class="min-w-0 flex-1">
				<h2 class="font-display text-[19px] font-semibold leading-tight text-navy-900 truncate group-hover:text-blue-700 transition-colors">{{ candidate.name }}</h2>
				<p class="text-[15px] font-medium text-slate-700 mt-1 line-clamp-2">{{ candidate.headline || candidate.field || text('Candidate', 'Candidat') }}</p>
			</div>
			<span v-if="candidate.has_resume" class="inline-flex items-center gap-1 shrink-0 rounded-full bg-[#dcfce7] text-[#047857] text-xs font-semibold px-2.5 py-1"><FileText :size="13" aria-hidden="true" />{{ text('Resume', 'CV') }}</span>
		</div>

		<div class="px-6 flex-1">
			<ul v-if="(candidate.field && candidate.headline) || candidate.location || years" class="flex flex-wrap gap-x-4 gap-y-1.5 text-sm text-slate-600 [&_li]:inline-flex [&_li]:items-center [&_li]:gap-1.5 [&_li]:min-w-0 [&_svg]:shrink-0 [&_svg]:text-slate-500">
				<li v-if="candidate.field && candidate.headline"><GraduationCap :size="15" aria-hidden="true" />{{ candidate.field }}</li>
				<li v-if="candidate.location"><MapPin :size="15" aria-hidden="true" />{{ candidate.location }}</li>
				<li v-if="years"><Briefcase :size="15" aria-hidden="true" />{{ years }}</li>
			</ul>
			<p v-if="candidate.summary" class="mt-4 text-[14.5px] leading-6 text-slate-600 line-clamp-3" dir="auto">{{ candidate.summary }}</p>
			<ul v-if="candidate.skills.length" class="mt-4 flex flex-wrap gap-1.5" :aria-label="text('Skills', 'Compétences')">
				<li v-for="skill in candidate.skills.slice(0, SKILLS)" :key="skill" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-[13px] font-semibold">{{ skill }}</li>
				<li v-if="candidate.skills_count > SKILLS" class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[13px] font-semibold">+{{ candidate.skills_count - SKILLS }}</li>
			</ul>
		</div>

		<div class="mt-5 mx-6 py-4 border-t border-line flex items-center gap-2 text-[15px] font-semibold text-blue-700">
			{{ text('View profile', 'Voir le profil') }}<ArrowRight :size="16" class="transition-transform group-hover:translate-x-[3px]" aria-hidden="true" />
		</div>
	</Link>
</template>
