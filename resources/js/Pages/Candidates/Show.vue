<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Briefcase, GraduationCap, MapPin } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';

const props = defineProps({
	candidate: { type: Object, required: true },
	status: { type: String, default: null },
	translation: { type: String, default: null },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const stages = { student: ['Student', 'Étudiant'], graduate: ['Graduate', 'Jeune diplômé'], professional: ['Professional', 'Professionnel'] };
const stage = computed(() => text(...(stages[props.candidate.career_stage] || stages.student)));
const initials = computed(() => (props.candidate.name || '').split(/\s+/).map((word) => word[0]).filter(Boolean).slice(0, 2).join('').toUpperCase());
const experience = computed(() => {
	const years = props.candidate.experience_years;
	if (years === null || years === undefined) return null;
	return fr.value ? `${years} ${years > 1 ? 'ans' : 'an'} d’expérience` : `${years} ${years === 1 ? 'year' : 'years'} of experience`;
});
const preview = computed(() => ({
	pending: text('Preview. This profile is waiting for review and is not public yet.', 'Aperçu. Ce profil attend une validation et n’est pas encore public.'),
	declined: text('Preview. This profile was not approved, so only you and the Nextward team can see it.', 'Aperçu. Ce profil n’a pas été validé : seuls vous et l’équipe Nextward pouvez le voir.'),
	null: text('Preview. This profile is private.', 'Aperçu. Ce profil est privé.'),
}[props.status] ?? null));
</script>

<template>
	<PublicLayout>
		<div class="public-container max-w-4xl! py-12">
			<nav class="text-sm text-slate-600" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link :href="`${prefix}/candidates`" class="hover:text-brand">{{ text('Candidate profiles', 'Profils de candidats') }}</Link> <span aria-hidden="true">/</span> <span class="text-navy">{{ candidate.name }}</span></nav>

			<p v-if="status !== 'approved'" class="mt-6 rounded-xl bg-[#fef3c7] px-5 py-3.5 text-sm font-semibold text-[#92400e]" role="status">{{ preview }}</p>

			<header class="mt-6 bg-white border border-line rounded-2xl p-6 sm:p-8">
				<div class="flex items-start gap-5 max-sm:flex-col">
					<span class="avatar" aria-hidden="true">{{ initials }}</span>
					<div class="min-w-0">
						<h1 class="font-display text-3xl sm:text-4xl font-semibold leading-tight">{{ candidate.name }}</h1>
						<p class="mt-2 text-lg text-slate-700">{{ candidate.headline || [candidate.field, stage].filter(Boolean).join(' · ') }}</p>
						<p class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-slate-600">
							<span class="inline-flex items-center gap-1.5"><GraduationCap :size="16" aria-hidden="true" />{{ stage }}<template v-if="candidate.field"> · {{ candidate.field }}</template></span>
							<span v-if="candidate.location" class="inline-flex items-center gap-1.5"><MapPin :size="16" aria-hidden="true" />{{ candidate.location }}</span>
							<span v-if="experience" class="inline-flex items-center gap-1.5"><Briefcase :size="16" aria-hidden="true" />{{ experience }}</span>
						</p>
						<p v-if="candidate.website || candidate.linkedin_profile_url" class="mt-4 flex flex-wrap gap-x-5 gap-y-2">
							<a v-if="candidate.website" :href="candidate.website" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1.5 font-semibold text-blue-700">{{ text('Website or portfolio', 'Site ou portfolio') }}<ArrowUpRight :size="16" aria-hidden="true" /></a>
							<a v-if="candidate.linkedin_profile_url" :href="candidate.linkedin_profile_url" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1.5 font-semibold text-blue-700">LinkedIn<ArrowUpRight :size="16" aria-hidden="true" /></a>
						</p>
					</div>
				</div>
			</header>

			<section v-if="candidate.about" class="mt-6 bg-white border border-line rounded-2xl p-6 sm:p-8" aria-labelledby="about-title">
				<h2 id="about-title" class="font-display text-xl font-semibold">{{ text('About', 'À propos') }}</h2>
				<p class="mt-4 whitespace-pre-line text-slate-700 leading-8" dir="auto">{{ candidate.about }}</p>
			</section>

			<div v-if="candidate.skills.length || candidate.languages.length" class="mt-6 grid gap-6 sm:grid-cols-2">
				<section v-if="candidate.skills.length" class="bg-white border border-line rounded-2xl p-6" aria-labelledby="skills-title">
					<h2 id="skills-title" class="font-display text-xl font-semibold">{{ text('Skills', 'Compétences') }}</h2>
					<ul class="mt-4 flex flex-wrap gap-2"><li v-for="skill in candidate.skills" :key="skill" class="skill">{{ skill }}</li></ul>
				</section>
				<section v-if="candidate.languages.length" class="bg-white border border-line rounded-2xl p-6" aria-labelledby="languages-title">
					<h2 id="languages-title" class="font-display text-xl font-semibold">{{ text('Languages', 'Langues') }}</h2>
					<ul class="mt-4 flex flex-wrap gap-2"><li v-for="language in candidate.languages" :key="language" class="skill">{{ language }}</li></ul>
				</section>
			</div>
		</div>
	</PublicLayout>
</template>

<style scoped>
.avatar { display: grid; place-items: center; flex-shrink: 0; width: 80px; height: 80px; border-radius: 22px; background: var(--color-brand); color: #fff; font-family: var(--font-display); font-size: 28px; font-weight: 700; }
.skill { padding: 5px 12px; border-radius: 999px; background: #eef4ff; color: #1d4ed8; font-size: 13.5px; font-weight: 600; }
</style>
