<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import Sparkle from '@/Shared/Sparkle.vue';

defineProps({ posts: { type: Array, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const formatDate = (date) => new Date(date).toLocaleDateString(fr.value ? 'fr-FR' : 'en', { month: 'short', day: 'numeric', year: 'numeric' });
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="guides-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('Nextward career guides', 'Guides carrière Nextward') }}</p>
						<h1 id="guides-title" class="hero-title">{{ text('Career guides for students and professionals', 'Guides carrière pour étudiants et professionnels') }}</h1>
						<p class="hero-lead">{{ text('Practical guides on finding internships and jobs, writing stronger applications, hiring candidates, and keeping your next career move organized.', 'Des guides pratiques pour trouver un stage ou un emploi en Tunisie, préparer votre candidature et réussir votre entretien.') }}</p>
					</div>
					<figure class="hero-stage guides-stage">
						<img src="/nextward-assets/mascots/11-learning-books-and-cap.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-16" :aria-label="text('All guides', 'Tous les guides')">
				<div v-if="posts.length" class="guide-list !mt-0">
					<Link v-for="post in posts" :key="post.slug" :href="post.path" class="guide-row focus-ring">
						<span class="guide-category">{{ post.category }}<template v-if="post.draft"> · {{ text('Draft', 'Brouillon') }}</template></span>
						<div><h2 class="guide-title">{{ post.title }}</h2><p>{{ post.excerpt }}</p></div>
						<span class="guide-time">{{ formatDate(post.published_at) }}<br />{{ post.read_time }} {{ text('min read', 'min') }}</span>
						<Icon name="arrow-up-right" class="guide-arrow" />
					</Link>
				</div>
				<p v-else class="text-slate-600">{{ text('New guides are on the way.', 'De nouveaux guides arrivent bientôt.') }}</p>
			</section>

			<section class="closing-band">
				<div class="public-container flex flex-col md:flex-row md:items-center md:justify-between gap-6 py-14">
					<div>
						<h2 class="section-title">{{ text('Ready to explore your next opportunity?', 'Prêt à trouver votre prochaine opportunité ?') }}</h2>
						<p class="mt-3 leading-7">{{ text('Browse internships and jobs in Tunisia and worldwide, and check where each application takes place.', 'Consultez les stages et les emplois en Tunisie et à l’international, et vérifiez où se fait chaque candidature.') }}</p>
					</div>
					<Link :href="fr ? '/fr/opportunities' : '/opportunities'" class="cta cta-light focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}</Link>
				</div>
			</section>
		</div>
	</PublicLayout>
</template>

<style scoped>
.guides-stage { width: min(100%, 320px); justify-self: center; }
.guide-title { font-size: 21px; font-weight: 500; line-height: 1.4; letter-spacing: -.025em; }
.guide-row:hover .guide-title { color: var(--color-brand); }
@media (max-width: 767px) {
	.guide-title { font-size: 19px; }
}
</style>
