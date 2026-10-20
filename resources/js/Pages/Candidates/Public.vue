<script setup>
import { computed, h } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Briefcase, FileText, GraduationCap, Mail, MapPin, Phone } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PublicLayout from '@/Layouts/Public.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';
import SkillList from '@/Components/Candidate/SkillList.vue';
import { isAppPath } from '@/utils/url';

// /app/talent/{slug} opens the profile inside the workspace; /candidates/{slug} is the public page.
defineOptions({
	layout: (_h, page) => isAppPath(usePage().url) ? h(AppShell, { active: 'talent' }, () => page) : h(PublicLayout, () => page),
});

const props = defineProps({
	candidate: { type: Object, required: true },
	status: { type: String, default: null },
	translation: { type: String, default: null },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const inApp = computed(() => isAppPath(page.url));
const text = (en, french) => fr.value ? french : en;
const experience = computed(() => {
	const years = props.candidate.experience_years;
	if (years === null || years === undefined) return null;
	return fr.value ? `${years} ${years > 1 ? 'ans' : 'an'} d’expérience` : `${years} ${years === 1 ? 'year' : 'years'} of experience`;
});
const dates = (row) => [row.start, row.current ? text('Now', 'Aujourd’hui') : row.end].filter(Boolean).join(' – ');
const history = computed(() => [
	{ key: 'experience', title: text('Experience', 'Expérience'), rows: (props.candidate.experience || []).map((row) => ({ title: row.title, sub: [row.company, row.location].filter(Boolean).join(' · '), dates: dates(row), body: row.summary })) },
	{ key: 'education', title: text('Education', 'Formation'), rows: (props.candidate.education || []).map((row) => ({ title: row.school, sub: [row.degree, row.field].filter(Boolean).join(' · '), dates: dates(row) })) },
	{ key: 'projects', title: text('Projects', 'Projets'), rows: (props.candidate.projects || []).map((row) => ({ title: row.name, body: row.summary, url: row.url })) },
].filter((list) => list.rows.length));
const button = 'inline-flex items-center justify-center gap-1.5 font-semibold text-sm px-4 py-2.5 rounded-[10px] transition-colors';
const preview = computed(() => ({
	pending: text('Preview. This profile is waiting for review and is not public yet.', 'Aperçu. Ce profil attend une validation et n’est pas encore public.'),
	declined: text('Preview. This profile was not approved, so only you and the Nextward team can see it.', 'Aperçu. Ce profil n’a pas été validé : seuls vous et l’équipe Nextward pouvez le voir.'),
	null: text('Preview. This profile is private.', 'Aperçu. Ce profil est privé.'),
}[props.status] ?? null));
</script>

<template>
	<div>
		<div class="public-container max-w-6xl! py-12">
			<nav class="text-sm text-slate-600" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link v-if="inApp" :href="route('talent.index')" class="hover:text-brand">Talent</Link><Link v-else :href="`${prefix}/candidates`" class="hover:text-brand">{{ text('Candidate profiles', 'Profils de candidats') }}</Link> <span aria-hidden="true">/</span> <span class="text-navy">{{ candidate.name }}</span></nav>

			<p v-if="status !== 'approved'" class="mt-6 rounded-xl bg-[#fef3c7] px-5 py-3.5 text-sm font-semibold text-[#92400e]" role="status">{{ preview }}</p>

			<ProfileBanner class="mt-6" :name="candidate.name" :headline="candidate.headline || candidate.field || text('Candidate', 'Candidat')" :items="[{ icon: GraduationCap, label: candidate.field }, { icon: MapPin, label: candidate.location }, { icon: Briefcase, label: experience }]">
				<a v-if="candidate.contact?.resume_url" :href="candidate.contact.resume_url" target="_blank" rel="noopener nofollow" :class="[button, 'bg-blue-600 text-white hover:bg-blue-700']"><FileText :size="15" aria-hidden="true" />{{ text('Resume', 'CV') }}</a>
				<a v-if="candidate.website" :href="candidate.website" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']">{{ text('Portfolio', 'Portfolio') }}<ArrowUpRight :size="15" aria-hidden="true" /></a>
				<a v-if="candidate.linkedin_profile_url" :href="candidate.linkedin_profile_url" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']">LinkedIn<ArrowUpRight :size="15" aria-hidden="true" /></a>
			</ProfileBanner>

			<div class="mt-6 grid gap-6 items-start lg:grid-cols-[minmax(0,1fr)_300px]">
				<div class="grid gap-6 min-w-0">
					<section v-if="candidate.about" class="bg-white border border-line rounded-2xl p-6 sm:p-8" aria-labelledby="about-title">
						<h2 id="about-title" class="font-display text-xl font-semibold">{{ text('About', 'À propos') }}</h2>
						<p class="mt-4 whitespace-pre-line text-slate-700 leading-8" dir="auto">{{ candidate.about }}</p>
					</section>

					<section v-for="list in history" :key="list.key" class="bg-white border border-line rounded-2xl p-6 sm:p-8" :aria-labelledby="`${list.key}-title`">
						<h2 :id="`${list.key}-title`" class="font-display text-xl font-semibold">{{ list.title }}</h2>
						<ul class="mt-2">
							<li v-for="(row, index) in list.rows" :key="index" class="py-4 border-b border-line last:border-b-0 last:pb-0">
								<div class="flex items-baseline justify-between gap-x-3 gap-y-0.5 flex-wrap">
									<h3 class="font-semibold text-navy-900" dir="auto">{{ row.title }}</h3>
									<span v-if="row.dates" class="text-sm text-slate-500">{{ row.dates }}</span>
								</div>
								<p v-if="row.sub" class="text-[15px] text-slate-700 mt-0.5" dir="auto">{{ row.sub }}</p>
								<p v-if="row.body" class="text-[15px] text-slate-600 mt-2 leading-7 whitespace-pre-line" dir="auto">{{ row.body }}</p>
								<a v-if="row.url" :href="row.url" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:underline mt-1.5 break-all">{{ row.url }}<ArrowUpRight :size="14" aria-hidden="true" /></a>
							</li>
						</ul>
					</section>
				</div>

				<aside class="grid gap-6 min-w-0">
					<section v-if="candidate.contact" class="bg-white border border-line rounded-2xl p-6" aria-labelledby="contact-title">
						<h2 id="contact-title" class="font-display text-lg font-semibold">{{ text('Contact', 'Contact') }}</h2>
						<ul class="mt-4 grid gap-3 text-[15px]">
							<li><a :href="`mailto:${candidate.contact.email}`" class="flex items-center gap-2.5 font-semibold text-blue-700 hover:underline min-w-0"><span class="grid size-[34px] shrink-0 place-items-center rounded-[10px] bg-blue-50"><Mail :size="16" aria-hidden="true" /></span><span class="break-all">{{ candidate.contact.email }}</span></a></li>
							<li v-if="candidate.contact.phone_number"><a :href="`tel:${candidate.contact.phone_number.replace(/\s+/g, '')}`" class="flex items-center gap-2.5 font-semibold text-blue-700 hover:underline"><span class="grid size-[34px] shrink-0 place-items-center rounded-[10px] bg-blue-50"><Phone :size="16" aria-hidden="true" /></span>{{ candidate.contact.phone_number }}</a></li>
						</ul>
					</section>

					<section v-if="candidate.skills.length || candidate.languages.length" class="bg-white border border-line rounded-2xl p-6" aria-labelledby="skills-title">
						<h2 id="skills-title" class="font-display text-lg font-semibold">{{ candidate.skills.length ? text('Skills', 'Compétences') : text('Languages', 'Langues') }}</h2>
						<SkillList class="mt-4" :skills="candidate.skills" :languages="candidate.languages" :fr="fr" />
					</section>
				</aside>
			</div>
		</div>
	</div>
</template>
