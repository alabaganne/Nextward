<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Briefcase, FileText, GraduationCap, Mail, MapPin, Phone } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';

const props = defineProps({
	candidate: { type: Object, required: true },
	status: { type: String, default: null },
	translation: { type: String, default: null },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
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

			<ProfileBanner class="mt-6" :name="candidate.name" :headline="candidate.headline || candidate.field || text('Candidate', 'Candidat')" :items="[{ icon: GraduationCap, label: candidate.field }, { icon: MapPin, label: candidate.location }, { icon: Briefcase, label: experience }]">
				<a v-if="candidate.contact?.resume_url" :href="candidate.contact.resume_url" target="_blank" rel="noopener nofollow" class="inline-flex items-center justify-center gap-1.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors"><FileText :size="16" aria-hidden="true" />{{ text('Resume', 'CV') }}</a>
				<a v-if="candidate.website" :href="candidate.website" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center justify-center gap-1.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-navy-900 hover:bg-[#eef2f7] transition-colors">{{ text('Portfolio', 'Portfolio') }}<ArrowUpRight :size="16" aria-hidden="true" /></a>
				<a v-if="candidate.linkedin_profile_url" :href="candidate.linkedin_profile_url" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center justify-center gap-1.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-navy-900 hover:bg-[#eef2f7] transition-colors">LinkedIn<ArrowUpRight :size="16" aria-hidden="true" /></a>
			</ProfileBanner>

			<section v-if="candidate.contact" class="mt-6 bg-white border border-line rounded-2xl p-6 sm:p-8" aria-labelledby="contact-title">
				<h2 id="contact-title" class="font-display text-xl font-semibold">{{ text('Contact', 'Contact') }}</h2>
				<ul class="mt-4 flex flex-wrap gap-x-8 gap-y-3 text-slate-700">
					<li><a :href="`mailto:${candidate.contact.email}`" class="inline-flex items-center gap-2 font-semibold text-blue-700 hover:underline"><Mail :size="17" aria-hidden="true" />{{ candidate.contact.email }}</a></li>
					<li v-if="candidate.contact.phone_number"><a :href="`tel:${candidate.contact.phone_number.replace(/\s+/g, '')}`" class="inline-flex items-center gap-2 font-semibold text-blue-700 hover:underline"><Phone :size="17" aria-hidden="true" />{{ candidate.contact.phone_number }}</a></li>
				</ul>
			</section>

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
.skill { padding: 5px 12px; border-radius: 999px; background: #eef4ff; color: #1d4ed8; font-size: 13.5px; font-weight: 600; }
</style>
