<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, Users } from '@lucide/vue';
import CandidateCard from '@/Components/Candidate/CandidateCard.vue';
import PublicLayout from '@/Layouts/Public.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import Sparkle from '@/Shared/Sparkle.vue';

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
const chip = (on) => on ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-line hover:border-blue-300';
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="candidates-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('Candidate profiles', 'Profils de candidats') }}</p>
						<h1 id="candidates-title" class="hero-title">{{ text('Candidates', 'Des candidats') }} <span class="accent">{{ text('ready for their next role.', 'prêts pour leur prochain poste.') }}</span></h1>
						<p class="hero-lead">{{ text('Each candidate chose to share this profile, and the Nextward team checked it before it went live. Some also share their resume and contact details, so you can reach out directly.', 'Chaque candidat a choisi de partager ce profil, et l’équipe Nextward l’a vérifié avant sa mise en ligne. Certains partagent aussi leur CV et leurs coordonnées pour que vous puissiez les contacter directement.') }}</p>
					</div>
					<figure class="hero-stage candidates-stage">
						<img src="/nextward-assets/mascots/10-portfolio-laptop.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-14" :aria-label="text('All candidate profiles', 'Tous les profils de candidats')">
				<form class="flex gap-3 max-w-3xl" role="search" :action="`${prefix}/candidates`" method="get" @submit.prevent="visit({})">
					<label class="relative flex-1 min-w-0">
						<span class="sr-only">{{ text('Search candidates', 'Rechercher des candidats') }}</span>
						<Search class="absolute left-4 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none" aria-hidden="true" />
						<input v-model="search" name="search" type="search" maxlength="100" :placeholder="text('Name, role, skill or location', 'Nom, poste, compétence ou lieu')" class="w-full h-12 rounded-xl border-line bg-white pl-11" />
					</label>
					<button type="submit" class="cta cta-primary">{{ text('Search', 'Rechercher') }}</button>
				</form>
				<div class="mt-5 flex gap-2 py-1 -mx-5 px-5 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" role="group" :aria-label="text('Filter candidates', 'Filtrer les candidats')">
					<button type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="chip(!filters.field)" :aria-pressed="!filters.field" @click="visit({ field: null })">{{ text('All fields', 'Tous les domaines') }}</button>
					<button v-for="field in fields" :key="field.id" type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="chip(filters.field === field.id)" :aria-pressed="filters.field === field.id" @click="visit({ field: field.id })">{{ field.name }}</button>
					<button type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="chip(filters.resume)" :aria-pressed="!!filters.resume" @click="visit({ resume: filters.resume ? null : 1 })">{{ text('Resume shared', 'CV partagé') }}</button>
				</div>
				<p class="mt-6 mb-4 text-sm text-slate-600" aria-live="polite">{{ candidates.total }} {{ candidates.total === 1 ? text('profile', 'profil') : text('profiles', 'profils') }}</p>

				<div v-if="!list.length" class="flex flex-col items-center text-center py-16 px-5 bg-white border border-line rounded-[18px]">
					<Users class="size-8 text-slate-400 mb-3" aria-hidden="true" />
					<h2 class="font-display text-lg font-semibold">{{ filtered ? text('No candidates found', 'Aucun candidat trouvé') : text('No public profiles yet', 'Aucun profil public pour le moment') }}</h2>
					<p class="text-slate-600 text-sm mt-1.5">{{ filtered ? text('Try another name, skill or field.', 'Essayez un autre nom, une autre compétence ou un autre domaine.') : text('Check back soon.', 'Revenez bientôt.') }}</p>
					<Link v-if="filtered" :href="`${prefix}/candidates`" class="mt-4 text-sm font-semibold text-blue-700 hover:underline">{{ text('Clear filters', 'Effacer les filtres') }}</Link>
				</div>
				<div v-else class="candidate-grid">
					<CandidateCard v-for="candidate in list" :key="candidate.id" :candidate="candidate" />
				</div>

				<nav v-if="candidates.prev_page_url || candidates.next_page_url" class="mt-10 flex justify-between gap-4" :aria-label="text('Pages', 'Pages')">
					<Link v-if="candidates.prev_page_url" :href="candidates.prev_page_url" class="cta cta-secondary focus-ring">{{ text('Previous', 'Précédent') }}</Link>
					<span v-else></span>
					<Link v-if="candidates.next_page_url" :href="candidates.next_page_url" class="cta cta-secondary focus-ring">{{ text('Next', 'Suivant') }}</Link>
				</nav>
			</section>

			<ClosingBanner :title="text('Want your profile here?', 'Vous voulez apparaître ici ?')" :body="text('Fill in your candidate profile, then ask to show it on Nextward from your profile page. We check every profile before it goes live.', 'Complétez votre profil de candidat, puis demandez à l’afficher sur Nextward depuis votre page de profil. Nous vérifions chaque profil avant sa mise en ligne.')">
				<Link v-if="user" href="/profile" class="cta cta-light focus-ring">{{ text('Open your profile', 'Ouvrir votre profil') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
				<Link v-else :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>

<style scoped>
.candidates-stage { width: min(100%, 320px); }
.candidate-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr)); }
</style>
