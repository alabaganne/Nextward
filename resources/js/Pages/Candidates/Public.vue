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
const button = 'cta focus-ring gap-2 px-5 py-2.5 text-[13.5px]';
const card = 'rounded-card border border-line bg-white p-6 sm:p-8';
const preview = computed(() => ({
	pending: text('Preview. This profile is waiting for review and is not public yet.', 'Aperçu. Ce profil attend une validation et n’est pas encore public.'),
	declined: text('Preview. This profile was not approved, so only you and the Nextward team can see it.', 'Aperçu. Ce profil n’a pas été validé : seuls vous et l’équipe Nextward pouvez le voir.'),
	null: text('Preview. This profile is private.', 'Aperçu. Ce profil est privé.'),
}[props.status] ?? null));
</script>

<template>
	<div>
		<section :class="inApp ? 'public-container pt-12' : 'hero-dark'">
			<div :class="!inApp && 'public-container relative z-[1] pt-10 pb-14 max-[768px]:pt-8 max-[768px]:pb-10'">
				<nav class="flex flex-wrap gap-2 text-[14px]" :class="inApp ? 'text-subtle' : 'text-mist'" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link v-if="inApp" :href="route('talent.index')" class="focus-ring rounded-sm hover:text-brand">Talent</Link><Link v-else :href="`${prefix}/candidates`" class="focus-ring rounded-sm hover:text-white">{{ text('Candidate profiles', 'Profils de candidats') }}</Link><span aria-hidden="true">/</span><span aria-current="page" :class="inApp ? 'text-navy' : 'text-white'">{{ candidate.name }}</span></nav>

				<p v-if="status !== 'approved'" class="mt-6 rounded-card bg-amber-50 px-5 py-3.5 text-[14px] font-medium text-amber-900" role="status">{{ preview }}</p>

				<ProfileBanner :flat="!inApp" :class="inApp ? 'mt-6' : 'mt-8'" :name="candidate.name" :headline="candidate.headline || candidate.field || text('Candidate', 'Candidat')" :items="[{ icon: GraduationCap, label: candidate.field }, { icon: MapPin, label: candidate.location }, { icon: Briefcase, label: experience }]">
					<a v-if="candidate.contact?.resume_url" :href="candidate.contact.resume_url" target="_blank" rel="noopener nofollow" :class="[button, 'cta-light']"><FileText :size="15" aria-hidden="true" />{{ text('Resume', 'CV') }}</a>
					<a v-if="candidate.website" :href="candidate.website" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'cta-ghost']">{{ text('Portfolio', 'Portfolio') }}<ArrowUpRight :size="15" aria-hidden="true" /></a>
					<a v-if="candidate.linkedin_profile_url" :href="candidate.linkedin_profile_url" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'cta-ghost']">LinkedIn<ArrowUpRight :size="15" aria-hidden="true" /></a>
				</ProfileBanner>
			</div>
		</section>

		<div :class="inApp ? 'pb-12' : 'bg-pale pt-12 pb-20 max-[768px]:pt-8'">
			<div class="public-container grid gap-6 items-start lg:grid-cols-[minmax(0,1fr)_320px]" :class="inApp && 'pt-6'">
				<div class="grid gap-6 min-w-0">
					<section v-if="candidate.about" :class="card" aria-labelledby="about-title">
						<h2 id="about-title" class="text-[22px] leading-snug font-medium text-navy">{{ text('About', 'À propos') }}</h2>
						<p class="mt-4 whitespace-pre-line text-[16px] text-ink-700 leading-8" dir="auto">{{ candidate.about }}</p>
					</section>

					<section v-for="list in history" :key="list.key" :class="card" :aria-labelledby="`${list.key}-title`">
						<h2 :id="`${list.key}-title`" class="text-[22px] leading-snug font-medium text-navy">{{ list.title }}</h2>
						<ul class="mt-2">
							<li v-for="(row, index) in list.rows" :key="index" class="py-5 border-b border-line last:border-b-0 last:pb-0">
								<div class="flex items-baseline justify-between gap-x-3 gap-y-0.5 flex-wrap">
									<h3 class="text-[17px] font-medium text-navy" dir="auto">{{ row.title }}</h3>
									<span v-if="row.dates" class="text-[14px] text-subtle-2">{{ row.dates }}</span>
								</div>
								<p v-if="row.sub" class="text-[15px] text-ink-700 mt-1" dir="auto">{{ row.sub }}</p>
								<p v-if="row.body" class="text-[15px] text-subtle mt-2 leading-7 whitespace-pre-line" dir="auto">{{ row.body }}</p>
								<a v-if="row.url" :href="row.url" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 text-[14px] font-medium text-brand transition-colors duration-180 hover:text-navy mt-2 break-all">{{ row.url }}<ArrowUpRight :size="14" aria-hidden="true" /></a>
							</li>
						</ul>
					</section>
				</div>

				<aside class="grid gap-6 min-w-0">
					<section v-if="candidate.contact" class="rounded-card border border-line bg-white p-6" aria-labelledby="contact-title">
						<h2 id="contact-title" class="text-[20px] font-medium text-navy">{{ text('Contact', 'Contact') }}</h2>
						<ul class="mt-4 grid gap-3 text-[15px]">
							<li><a :href="`mailto:${candidate.contact.email}`" class="flex items-center gap-3 font-medium text-brand transition-colors duration-180 hover:text-navy min-w-0"><span class="grid size-[38px] shrink-0 place-items-center rounded-card bg-blue-50 text-brand"><Mail :size="16" aria-hidden="true" /></span><span class="break-all">{{ candidate.contact.email }}</span></a></li>
							<li v-if="candidate.contact.phone_number"><a :href="`tel:${candidate.contact.phone_number.replace(/\s+/g, '')}`" class="flex items-center gap-3 font-medium text-brand transition-colors duration-180 hover:text-navy"><span class="grid size-[38px] shrink-0 place-items-center rounded-card bg-blue-50 text-brand"><Phone :size="16" aria-hidden="true" /></span>{{ candidate.contact.phone_number }}</a></li>
						</ul>
					</section>

					<section v-if="candidate.skills.length || candidate.languages.length" class="rounded-card border border-line bg-white p-6" aria-labelledby="skills-title">
						<h2 id="skills-title" class="text-[20px] font-medium text-navy">{{ candidate.skills.length ? text('Skills', 'Compétences') : text('Languages', 'Langues') }}</h2>
						<SkillList class="mt-4" :skills="candidate.skills" :languages="candidate.languages" :fr="fr" />
					</section>
				</aside>
			</div>
		</div>
	</div>
</template>
