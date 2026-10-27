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
const button = 'cta focus-ring gap-2 px-5 py-2.5 text-[13.5px]';
</script>

<template>
	<section class="hero-dark">
		<div class="public-container relative z-[1] pt-10 pb-14 max-[768px]:pt-8 max-[768px]:pb-10">
			<nav class="mb-8 flex flex-wrap gap-2 text-[14px] text-mist" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link :href="`${prefix}/companies`" class="focus-ring rounded-sm hover:text-white">{{ text('Companies', 'Entreprises') }}</Link><span aria-hidden="true">/</span><span aria-current="page" class="text-white">{{ company.name }}</span></nav>
			<ProfileBanner flat :name="company.name" :headline="headline" :items="[
				{ icon: MapPin, label: company.location },
				{ icon: Users, label: company.size && text(`${company.size} people`, `${company.size} personnes`) },
				{ icon: CalendarDays, label: company.founded_year && text(`Founded in ${company.founded_year}`, `Fondée en ${company.founded_year}`) },
			]">
				<template #avatar><CompanyLogo class="size-[78px] rounded-card text-[28px]" :name="company.name" :src="company.logo" /></template>
				<template #badge><VerifiedBadge v-if="company.verified" dark class="text-xs!" /></template>
				<a v-if="open" href="#roles-title" :class="[button, 'cta-light']">{{ text(`See ${open} open ${open === 1 ? 'role' : 'roles'}`, `Voir ${open} ${open === 1 ? 'offre' : 'offres'}`) }}</a>
				<a v-if="company.website" :href="company.website" target="_blank" rel="noopener noreferrer" :class="[button, 'cta-ghost']">{{ text('Website', 'Site web') }}<ArrowUpRight :size="15" aria-hidden="true" /></a>
				<a v-if="company.linkedin_url" :href="company.linkedin_url" target="_blank" rel="noopener noreferrer nofollow" :class="[button, 'cta-ghost']">LinkedIn<ArrowUpRight :size="15" aria-hidden="true" /></a>
			</ProfileBanner>
		</div>
	</section>

	<div class="bg-pale pt-12 pb-20 max-[768px]:pt-8">
		<div class="public-container grid gap-6 items-start lg:grid-cols-[minmax(0,1fr)_340px]">
			<div class="grid gap-6 min-w-0">
				<section class="rounded-card border border-line bg-white p-6 sm:p-8" aria-labelledby="about-title">
					<h2 id="about-title" class="text-[22px] leading-snug font-medium text-navy">{{ text(`About ${company.name}`, `À propos de ${company.name}`) }}</h2>
					<p v-if="company.about" class="mt-4 whitespace-pre-line text-[16px] text-ink-700 leading-8" dir="auto">{{ company.about }}</p>
					<p v-else class="mt-4 text-slate-400">{{ text('No description yet.', 'Pas encore de description.') }}</p>
					<p v-if="!company.managed" class="mt-6 pt-5 border-t border-line text-[13px] text-subtle-2 leading-6">{{ text('This employer does not have a managed account on Nextward. Roles come from approved public sources and link to the original listing.', 'Cet employeur n’a pas de compte géré sur Nextward. Les offres viennent de sources publiques approuvées et renvoient vers l’annonce d’origine.') }}</p>
				</section>

				<section class="rounded-card border border-line bg-white p-6 sm:p-8" aria-labelledby="roles-title">
					<div class="flex flex-wrap items-center justify-between gap-3">
						<h2 id="roles-title" class="text-[22px] leading-snug font-medium text-navy scroll-mt-28">{{ text('Open internships and jobs', 'Stages et emplois ouverts') }}</h2>
						<span class="rounded-md bg-blue-50 px-3 py-1 text-[13px] text-brand">{{ open }} {{ open === 1 ? text('role', 'offre') : text('roles', 'offres') }}</span>
					</div>
					<div v-if="roles.length" class="mt-6 grid xl:grid-cols-2 gap-5"><RoleCard v-for="opportunity in roles" :key="opportunity.id" :opportunity="opportunity" /></div>
					<div v-else class="mt-4">
						<p class="text-subtle">{{ text(`There are no open roles at ${company.name} right now.`, `Aucune offre ouverte chez ${company.name} pour le moment.`) }}</p>
						<Link :href="`${prefix}/opportunities`" class="cta cta-secondary focus-ring mt-5">{{ text('Browse all roles', 'Voir toutes les offres') }}</Link>
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
