<script>
import opportunityLayout from '@/Layouts/opportunityLayout';
export default { layout: opportunityLayout };
</script>
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, CalendarDays, MapPin, Users } from '@lucide/vue';
import CompanyFacts from '@/Components/Company/CompanyFacts.vue';
import CompanyHiring from '@/Components/Company/CompanyHiring.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';
import RoleCard from '@/Components/Opportunity/RoleCard.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';
const props = defineProps({ company: { type: Object, required: true }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, translation: { type: String, default: null } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const roles = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const open = computed(() => props.company.hiring?.open ?? roles.value.length);
const fields = computed(() => (props.company.hiring?.fields || []).slice(0, 2).map((field) => field.name));
const headline = computed(() => fields.value.length
	? text(`Hiring in ${fields.value.join(' and ')}`, `Recrute en ${fields.value.join(' et ')}`)
	: { startup: text('Startup', 'Start-up'), established: text('Established company', 'Entreprise établie') }[props.company.stage] || null);
const button = 'inline-flex items-center justify-center gap-1.5 font-semibold text-sm px-4 py-2.5 rounded-[10px] transition-colors';
</script>

<template>
	<div class="public-container max-w-6xl! py-12">
		<nav class="text-sm text-slate-600" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link :href="`${prefix}/companies`" class="hover:text-brand">{{ text('Companies', 'Entreprises') }}</Link> <span aria-hidden="true">/</span> <span class="text-navy">{{ company.name }}</span></nav>

		<ProfileBanner class="mt-6" :name="company.name" :headline="headline" :items="[
			{ icon: MapPin, label: company.location },
			{ icon: Users, label: company.size && text(`${company.size} people`, `${company.size} personnes`) },
			{ icon: CalendarDays, label: company.founded_year && text(`Founded in ${company.founded_year}`, `Fondée en ${company.founded_year}`) },
		]">
			<template #avatar><CompanyLogo class="w-[78px] h-[78px] rounded-[20px] text-[28px]" :name="company.name" :src="company.logo" /></template>
			<template #badge><VerifiedBadge v-if="company.verified" dark class="text-xs!" /></template>
			<a v-if="open" href="#roles-title" :class="[button, 'bg-blue-600 text-white hover:bg-blue-700']">{{ text(`See ${open} open ${open === 1 ? 'role' : 'roles'}`, `Voir ${open} ${open === 1 ? 'offre' : 'offres'}`) }}</a>
			<a v-if="company.website" :href="company.website" target="_blank" rel="noopener noreferrer" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']">{{ text('Website', 'Site web') }}<ArrowUpRight :size="15" aria-hidden="true" /></a>
			<a v-if="company.linkedin_url" :href="company.linkedin_url" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']">LinkedIn<ArrowUpRight :size="15" aria-hidden="true" /></a>
		</ProfileBanner>

		<div class="mt-6 grid gap-6 items-start lg:grid-cols-[minmax(0,1fr)_320px]">
			<div class="grid gap-6 min-w-0">
				<section class="bg-white border border-line rounded-2xl p-6 sm:p-8" aria-labelledby="about-title">
					<h2 id="about-title" class="font-display text-xl font-semibold">{{ text(`About ${company.name}`, `À propos de ${company.name}`) }}</h2>
					<p v-if="company.about" class="mt-4 whitespace-pre-line text-slate-700 leading-8" dir="auto">{{ company.about }}</p>
					<p v-else class="mt-4 text-slate-400">{{ text('No description yet.', 'Pas encore de description.') }}</p>
					<p v-if="!company.managed" class="mt-6 pt-5 border-t border-line text-xs text-slate-500 leading-6">{{ text('This employer does not have a managed account on Nextward. Roles come from approved public sources and link to the original listing.', 'Cet employeur n’a pas de compte géré sur Nextward. Les offres viennent de sources publiques approuvées et renvoient vers l’annonce d’origine.') }}</p>
				</section>

				<section class="bg-white border border-line rounded-2xl p-6 sm:p-8" aria-labelledby="roles-title">
					<div class="flex flex-wrap items-center justify-between gap-3">
						<h2 id="roles-title" class="font-display text-xl font-semibold scroll-mt-24">{{ text('Open internships and jobs', 'Stages et emplois ouverts') }}</h2>
						<span class="rounded-full bg-paper-2 px-3 py-1 text-[13px] font-semibold text-slate-600">{{ open }} {{ open === 1 ? text('role', 'offre') : text('roles', 'offres') }}</span>
					</div>
					<div v-if="roles.length" class="mt-5 grid xl:grid-cols-2 gap-4"><RoleCard v-for="opportunity in roles" :key="opportunity.id" :opportunity="opportunity" /></div>
					<div v-else class="mt-4">
						<p class="text-slate-500">{{ text(`There are no open roles at ${company.name} right now.`, `Aucune offre ouverte chez ${company.name} pour le moment.`) }}</p>
						<Link :href="`${prefix}/opportunities`" class="inline-block mt-3 text-blue-700 font-semibold hover:underline">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}</Link>
					</div>
				</section>
			</div>

			<aside class="grid gap-6 min-w-0">
				<CompanyHiring v-if="company.hiring" :hiring="company.hiring" />
				<CompanyFacts :company="company" />
			</aside>
		</div>
	</div>
</template>
