<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, X } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import Sparkle from '@/Shared/Sparkle.vue';
import { guideImage } from '@/Components/Blog/guideImages';

const props = defineProps({ posts: { type: Array, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const formatDate = (date) => new Date(date).toLocaleDateString(fr.value ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });

const query = ref('');
const category = ref('');
const categories = computed(() => [...new Set(props.posts.map((post) => post.category).filter(Boolean))]);
const plain = (value) => String(value || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const filtered = computed(() => {
	const words = plain(query.value).split(/\s+/).filter(Boolean);
	return props.posts.filter((post) => (!category.value || post.category === category.value)
		&& words.every((word) => plain(`${post.title} ${post.excerpt} ${post.category}`).includes(word)));
});
const filtering = computed(() => !!(query.value.trim() || category.value));
const featured = computed(() => filtering.value ? null : filtered.value[0]);
const rest = computed(() => featured.value ? filtered.value.slice(1) : filtered.value);
const featuredImage = computed(() => featured.value ? guideImage(featured.value) : null);
const reset = () => { query.value = ''; category.value = ''; };
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="guides-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('The Nextward career guide', 'Le guide carrière Nextward') }}</p>
						<h1 id="guides-title" class="hero-title">{{ text('A little guidance for', 'Quelques conseils pour') }} <span class="accent">{{ text('your next move.', 'votre prochaine étape.') }}</span></h1>
						<p class="hero-lead">{{ text('Practical advice for your first application, your next role and everything in between.', 'Des conseils pratiques pour votre première candidature, votre prochain poste et tout ce qui se passe entre les deux.') }}</p>
					</div>
					<figure class="hero-stage guides-stage">
						<img src="/nextward-assets/mascots/11-learning-books-and-cap.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section v-if="posts.length" class="public-container guides-intro" :aria-label="text('Find a guide', 'Trouver un guide')">
				<div class="guide-filters">
					<form role="search" class="guide-search" :aria-label="text('Find a guide', 'Trouver un guide')" @submit.prevent>
						<label for="guide-search" class="sr-only">{{ text('Find a guide', 'Trouver un guide') }}</label>
						<span class="field flex-1"><Search aria-hidden="true" /><input id="guide-search" v-model="query" type="search" maxlength="100" :placeholder="text('Find a guide', 'Trouver un guide')" autocomplete="off" /></span>
					</form>
					<div class="guide-tabs" role="group" :aria-label="text('Filter by category', 'Filtrer par catégorie')">
						<button type="button" class="guide-tab focus-ring" :aria-pressed="!category" @click="category = ''">{{ text('All guides', 'Tous les guides') }}</button>
						<button v-for="item in categories" :key="item" type="button" class="guide-tab focus-ring" :aria-pressed="category === item" @click="category = category === item ? '' : item">{{ item }}</button>
					</div>
				</div>
			</section>

			<section class="public-container pb-20" :class="{ 'pt-14': !posts.length }" :aria-label="text('All guides', 'Tous les guides')">
				<p v-if="!posts.length" class="panel p-8 text-slate-600">{{ text('New guides are on the way.', 'De nouveaux guides arrivent bientôt.') }}</p>
				<template v-else>
					<p class="sr-only" role="status">{{ filtering ? text(`${filtered.length} ${filtered.length === 1 ? 'guide' : 'guides'} found`, `${filtered.length} ${filtered.length === 1 ? 'guide trouvé' : 'guides trouvés'}`) : '' }}</p>

					<Link v-if="featured" :href="featured.path" class="featured focus-ring" :class="{ 'is-text': !featuredImage }" :lang="featured.locale">
						<img v-if="featuredImage" :src="featuredImage.src" alt="" :width="featuredImage.width" :height="featuredImage.height" fetchpriority="high" decoding="async" class="featured-photo" />
						<div class="featured-copy">
							<p class="guide-category">{{ featured.category }}<template v-if="featured.draft"> · {{ text('Draft', 'Brouillon') }}</template></p>
							<h2 class="featured-title">{{ featured.title }}</h2>
							<p class="featured-excerpt">{{ featured.excerpt }}</p>
							<p class="featured-meta"><time :datetime="featured.published_at">{{ formatDate(featured.published_at) }}</time> · {{ featured.read_time }} {{ text('min read', 'min de lecture') }}</p>
							<span class="landing-link mt-4">{{ text('Read the guide', 'Lire le guide') }}<ArrowRight class="action-icon" aria-hidden="true" /></span>
						</div>
					</Link>

					<div v-if="rest.length">
						<div class="section-head" :class="featured ? 'mt-16' : 'mt-2'">
							<h2 class="section-title">{{ filtering ? text('Matching guides', 'Guides correspondants') : text('Explore the guides', 'Explorer les guides') }}</h2>
							<button v-if="filtering" type="button" class="landing-link focus-ring" @click="reset"><X class="action-icon" aria-hidden="true" />{{ text('Clear filters', 'Effacer les filtres') }}</button>
						</div>
						<div class="guide-grid"><GuideCard v-for="post in rest" :key="post.slug" :post="post" /></div>
					</div>

					<div v-if="!filtered.length" class="panel guides-empty">
						<h2 class="font-display text-xl font-semibold">{{ text('No guide matches this search.', 'Aucun guide ne correspond à cette recherche.') }}</h2>
						<p class="mt-2 text-slate-600">{{ text('Try another word or a different category.', 'Essayez un autre mot ou une autre catégorie.') }}</p>
						<button type="button" class="cta cta-secondary focus-ring mt-5" @click="reset">{{ text('Show all guides', 'Afficher tous les guides') }}</button>
					</div>
				</template>
			</section>

			<ClosingBanner :title="text('Turn your next read into your next move.', 'Passez de la lecture à l’action.')" :body="text('Browse internships and jobs in Tunisia and worldwide, and check where each application takes place.', 'Consultez les stages et les emplois en Tunisie et à l’international, et vérifiez où se fait chaque candidature.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>

<style scoped>
.guides-intro { padding-block: 40px; }
.guides-stage { width: min(100%, 320px); }
.guide-search { display: flex; max-width: 560px; }
.guide-tabs { display: flex; flex-wrap: wrap; gap: 4px 24px; margin-top: 24px; border-bottom: 1px solid var(--color-line); }
.guide-tab { position: relative; padding: 10px 0 12px; border-radius: 2px; color: #475569; font-size: 14.5px; font-weight: 600; transition: color 180ms; }
.guide-tab::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: var(--color-brand); transform: scaleX(0); transition: transform 200ms; }
.guide-tab:hover { color: var(--color-navy); }
.guide-tab[aria-pressed='true'] { color: var(--color-brand); }
.guide-tab[aria-pressed='true']::after { transform: scaleX(1); }
.featured { display: grid; grid-template-columns: minmax(0, 560px) minmax(0, 1fr); align-items: center; gap: 40px; padding-bottom: 8px; border-radius: 18px; }
.featured.is-text { grid-template-columns: 1fr; padding: 36px; background: #eef4ff; }
.featured-photo { width: 100%; height: auto; border-radius: 18px; background: #eef4ff; }
.featured-title { margin-top: 10px; font-size: clamp(26px, 2.8vw, 36px); font-weight: 700; line-height: 1.15; letter-spacing: -.04em; transition: color 180ms; }
.featured:hover .featured-title { color: var(--color-brand); }
.featured-excerpt { max-width: 560px; margin-top: 14px; color: #475569; font-size: 16.5px; line-height: 1.7; }
.featured-meta { margin-top: 14px; color: #67707e; font-size: 13.5px; }
.guides-empty { padding: 36px; text-align: center; }
@media (max-width: 767px) {
	.guides-intro { padding-block: 28px; }
	.featured { grid-template-columns: 1fr; gap: 20px; }
	.featured.is-text { padding: 24px; }
	.guide-tabs { flex-wrap: nowrap; overflow-x: auto; gap: 20px; }
	.guide-tab { white-space: nowrap; }
}
@media (prefers-reduced-motion: reduce) {
	.guide-tab::after { transition: none; }
}
</style>
