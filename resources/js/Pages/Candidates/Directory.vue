<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, Users } from '@lucide/vue';
import CandidateCard from '@/Components/Candidate/CandidateCard.vue';
import PublicLayout from '@/Layouts/Public.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

const props = defineProps({
	candidates: { type: Object, required: true },
	fields: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const user = computed(() => page.props.auth?.user);
const list = computed(() => props.candidates.data || []);

const search = ref(props.filters.search || '');
watch(() => props.filters.search, (value) => { search.value = value || ''; });
const visit = (changes) => {
	const query = { search: search.value.trim(), field: props.filters.field, resume: props.filters.resume ? 1 : null, ...changes };
	router.get(`${prefix.value}/candidates`, Object.fromEntries(Object.entries(query).filter(([, value]) => value)), { preserveScroll: true, preserveState: true });
};
const filtered = computed(() => !!(props.filters.search || props.filters.field || props.filters.resume));
</script>

<template>
	<PublicLayout>
		<div class="landing bg-pale">
			<LandingHero title-id="candidates-title" overlap :label="text('Candidate profiles', 'Profils de candidats')" :lead="text('Each candidate chose to share this profile, and the Nextward team checked it before it went live. Some also share their resume and contact details, so you can reach out directly.', 'Chaque candidat a choisi de partager ce profil, et l’équipe Nextward l’a vérifié avant sa mise en ligne. Certains partagent aussi leur CV et leurs coordonnées pour que vous puissiez les contacter directement.')">
				{{ text('Candidates', 'Des candidats') }} <span class="accent">{{ text('ready for their next role.', 'prêts pour leur prochain poste.') }}</span>
			</LandingHero>

			<div class="public-container hero-overlap pb-20">
				<div class="rounded-card border border-line bg-white p-6 max-[768px]:p-4">
					<form class="flex gap-3 max-[480px]:flex-col" role="search" :action="`${prefix}/candidates`" method="get" @submit.prevent="visit({})">
						<label class="field flex-1 min-w-0">
							<span class="sr-only">{{ text('Search candidates', 'Rechercher des candidats') }}</span>
							<Search aria-hidden="true" />
							<input v-model="search" name="search" type="search" maxlength="100" :placeholder="text('Name, role, skill or location', 'Nom, poste, compétence ou lieu')" />
						</label>
						<button type="submit" class="cta cta-primary focus-ring h-[52px]">{{ text('Search', 'Rechercher') }}</button>
					</form>
					<div class="mt-4 flex gap-2 py-1 -mx-4 px-4 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" role="group" :aria-label="text('Filter candidates', 'Filtrer les candidats')">
						<button type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': !filters.field }" :aria-pressed="!filters.field" @click="visit({ field: null })">{{ text('All fields', 'Tous les domaines') }}</button>
						<button v-for="field in fields" :key="field.id" type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': filters.field === field.id }" :aria-pressed="filters.field === field.id" @click="visit({ field: field.id })">{{ field.name }}</button>
						<button type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': filters.resume }" :aria-pressed="!!filters.resume" @click="visit({ resume: filters.resume ? null : 1 })">{{ text('Resume shared', 'CV partagé') }}</button>
					</div>
				</div>

				<section class="mt-10" :aria-label="text('All candidate profiles', 'Tous les profils de candidats')">
					<p class="mb-5 text-[15px] text-subtle" aria-live="polite"><strong class="font-medium text-navy">{{ candidates.total }}</strong> {{ candidates.total === 1 ? text('profile', 'profil') : text('profiles', 'profils') }}</p>
					<div v-if="!list.length" class="panel flex flex-col items-center px-5 py-16 text-center">
						<span class="icon-tile" aria-hidden="true"><Users class="size-6" /></span>
						<h2 class="mt-5 text-[22px] font-medium text-navy">{{ filtered ? text('No candidates found', 'Aucun candidat trouvé') : text('No public profiles yet', 'Aucun profil public pour le moment') }}</h2>
						<p class="mt-2 text-subtle">{{ filtered ? text('Try another name, skill or field.', 'Essayez un autre nom, une autre compétence ou un autre domaine.') : text('Check back soon.', 'Revenez bientôt.') }}</p>
						<Link v-if="filtered" :href="`${prefix}/candidates`" class="cta cta-secondary focus-ring mt-6">{{ text('Clear filters', 'Effacer les filtres') }}</Link>
					</div>
					<div v-else class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] gap-6">
						<CandidateCard v-for="candidate in list" :key="candidate.id" :candidate="candidate" />
					</div>
					<Pagination :meta="candidates" />
				</section>
			</div>

			<ClosingBanner :title="text('Want your profile here?', 'Vous voulez apparaître ici ?')" :body="text('Fill in your candidate profile, then ask to show it on Nextward from your profile page. We check every profile before it goes live.', 'Complétez votre profil de candidat, puis demandez à l’afficher sur Nextward depuis votre page de profil. Nous vérifions chaque profil avant sa mise en ligne.')">
				<Link v-if="user" :href="route('profile.show')" class="cta cta-light focus-ring">{{ text('Open your profile', 'Ouvrir votre profil') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
				<Link v-else :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>
