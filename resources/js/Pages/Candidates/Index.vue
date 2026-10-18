<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, MapPin } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import Sparkle from '@/Shared/Sparkle.vue';

const props = defineProps({ candidates: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const user = computed(() => page.props.auth?.user);
const stages = { student: ['Student', 'Étudiant'], graduate: ['Graduate', 'Jeune diplômé'], professional: ['Professional', 'Professionnel'] };
const stage = (key) => text(...(stages[key] || stages.student));
const initials = (name) => (name || '').split(/\s+/).map((word) => word[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
const list = computed(() => props.candidates.data || []);
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="candidates-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('Candidate profiles', 'Profils de candidats') }}</p>
						<h1 id="candidates-title" class="hero-title">{{ text('Students and professionals', 'Des étudiants et des professionnels') }} <span class="accent">{{ text('ready for their next role.', 'prêts pour leur prochain poste.') }}</span></h1>
						<p class="hero-lead">{{ text('Each candidate chose to share this profile, and the Nextward team checked it before it went live. Email addresses, phone numbers and resumes stay private.', 'Chaque candidat a choisi de partager ce profil, et l’équipe Nextward l’a vérifié avant sa mise en ligne. Les adresses e-mail, les numéros de téléphone et les CV restent privés.') }}</p>
					</div>
					<figure class="hero-stage candidates-stage">
						<img src="/nextward-assets/mascots/10-portfolio-laptop.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-14" :aria-label="text('All candidate profiles', 'Tous les profils de candidats')">
				<p v-if="!list.length" class="panel p-8 text-slate-600">{{ text('No public profiles yet. Check back soon.', 'Aucun profil public pour le moment. Revenez bientôt.') }}</p>
				<div v-else class="candidate-grid">
					<Link v-for="candidate in list" :key="candidate.id" :href="candidate.url" class="candidate-card focus-ring">
						<div class="flex items-center gap-4">
							<span class="avatar" aria-hidden="true">{{ initials(candidate.name) }}</span>
							<div class="min-w-0">
								<h2 class="font-display text-lg font-semibold leading-tight truncate">{{ candidate.name }}</h2>
								<p class="text-sm text-slate-600 mt-1 line-clamp-2">{{ candidate.headline || [candidate.field, stage(candidate.career_stage)].filter(Boolean).join(' · ') }}</p>
							</div>
						</div>
						<p v-if="candidate.location" class="mt-4 inline-flex items-center gap-1.5 text-sm text-slate-600"><MapPin :size="15" aria-hidden="true" />{{ candidate.location }}</p>
						<ul v-if="candidate.skills.length" class="mt-4 flex flex-wrap gap-2" :aria-label="text('Skills', 'Compétences')">
							<li v-for="skill in candidate.skills.slice(0, 5)" :key="skill" class="skill">{{ skill }}</li>
						</ul>
						<span class="landing-link mt-auto pt-5">{{ text('View profile', 'Voir le profil') }}<ArrowRight class="action-icon" aria-hidden="true" /></span>
					</Link>
				</div>

				<nav v-if="candidates.prev_page_url || candidates.next_page_url" class="mt-10 flex justify-between gap-4" :aria-label="text('Pages', 'Pages')">
					<Link v-if="candidates.prev_page_url" :href="candidates.prev_page_url" class="cta cta-secondary focus-ring">{{ text('Previous', 'Précédent') }}</Link>
					<span v-else></span>
					<Link v-if="candidates.next_page_url" :href="candidates.next_page_url" class="cta cta-secondary focus-ring">{{ text('Next', 'Suivant') }}</Link>
				</nav>
			</section>

			<section class="public-container pb-20">
				<ClosingBanner :title="text('Want your profile here?', 'Vous voulez apparaître ici ?')" :body="text('Fill in your candidate profile, then ask to show it on Nextward from your settings. We check every profile before it goes live.', 'Complétez votre profil de candidat, puis demandez à l’afficher sur Nextward depuis vos paramètres. Nous vérifions chaque profil avant sa mise en ligne.')">
					<Link v-if="user" href="/settings" class="cta cta-light focus-ring">{{ text('Open settings', 'Ouvrir les paramètres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
					<Link v-else :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
				</ClosingBanner>
			</section>
		</div>
	</PublicLayout>
</template>

<style scoped>
.candidates-stage { width: min(100%, 320px); }
.candidate-grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
.candidate-card { display: flex; flex-direction: column; padding: 24px; border: 1px solid var(--color-line); border-radius: 18px; background: #fff; transition: border-color 180ms; }
.candidate-card:hover { border-color: rgb(37 99 235 / .45); }
.avatar { display: grid; place-items: center; flex-shrink: 0; width: 56px; height: 56px; border-radius: 16px; background: var(--color-brand); color: #fff; font-family: var(--font-display); font-size: 20px; font-weight: 700; }
.skill { padding: 4px 10px; border-radius: 999px; background: #eef4ff; color: #1d4ed8; font-size: 12.5px; font-weight: 600; }
</style>
