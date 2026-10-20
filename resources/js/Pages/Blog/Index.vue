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
const tab = (pressed) => ['focus-ring relative rounded-[2px] pt-2.5 pb-3 text-[14.5px] font-semibold transition-colors duration-180 after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-brand after:transition-transform after:duration-200 max-[768px]:whitespace-nowrap motion-reduce:after:transition-none', pressed ? 'text-brand after:scale-x-100' : 'text-gray-600 after:scale-x-0 hover:text-navy'];
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
					<figure class="hero-stage w-[min(100%,320px)]">
						<img src="/nextward-assets/mascots/11-learning-books-and-cap.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section v-if="posts.length" class="public-container py-10 max-[768px]:py-7" :aria-label="text('Find a guide', 'Trouver un guide')">
				<div>
					<form role="search" class="flex max-w-[560px]" :aria-label="text('Find a guide', 'Trouver un guide')" @submit.prevent>
						<label for="guide-search" class="sr-only">{{ text('Find a guide', 'Trouver un guide') }}</label>
						<span class="field flex-1"><Search aria-hidden="true" /><input id="guide-search" v-model="query" type="search" maxlength="100" :placeholder="text('Find a guide', 'Trouver un guide')" autocomplete="off" /></span>
					</form>
					<div class="mt-6 flex flex-wrap gap-x-6 gap-y-1 border-b border-line max-[768px]:flex-nowrap max-[768px]:gap-5 max-[768px]:overflow-x-auto" role="group" :aria-label="text('Filter by category', 'Filtrer par catégorie')">
						<button type="button" :class="tab(!category)" :aria-pressed="!category" @click="category = ''">{{ text('All guides', 'Tous les guides') }}</button>
						<button v-for="item in categories" :key="item" type="button" :class="tab(category === item)" :aria-pressed="category === item" @click="category = category === item ? '' : item">{{ item }}</button>
					</div>
				</div>
			</section>

			<section class="public-container pb-20" :class="{ 'pt-14': !posts.length }" :aria-label="text('All guides', 'Tous les guides')">
				<p v-if="!posts.length" class="panel p-8 text-slate-600">{{ text('New guides are on the way.', 'De nouveaux guides arrivent bientôt.') }}</p>
				<template v-else>
					<p class="sr-only" role="status">{{ filtering ? text(`${filtered.length} ${filtered.length === 1 ? 'guide' : 'guides'} found`, `${filtered.length} ${filtered.length === 1 ? 'guide trouvé' : 'guides trouvés'}`) : '' }}</p>

					<Link v-if="featured" :href="featured.path" class="group focus-ring grid items-center gap-10 rounded-[18px] max-[768px]:gap-5" :class="featuredImage ? 'grid-cols-[minmax(0,560px)_minmax(0,1fr)] pb-2 max-[768px]:grid-cols-1' : 'grid-cols-1 bg-blue-50 p-9 max-[768px]:p-6'" :lang="featured.locale">
						<img v-if="featuredImage" :src="featuredImage.src" alt="" :width="featuredImage.width" :height="featuredImage.height" fetchpriority="high" decoding="async" class="h-auto w-full rounded-[18px] bg-blue-50" />
						<div>
							<p class="guide-category">{{ featured.category }}<template v-if="featured.draft"> · {{ text('Draft', 'Brouillon') }}</template></p>
							<h2 class="mt-2.5 text-[length:clamp(26px,2.8vw,36px)] leading-[1.15] font-bold tracking-[-.04em] transition-colors duration-180 group-hover:text-brand">{{ featured.title }}</h2>
							<p class="mt-3.5 max-w-[560px] text-[16.5px] leading-[1.7] text-gray-600">{{ featured.excerpt }}</p>
							<p class="mt-3.5 text-[13.5px] text-subtle-2"><time :datetime="featured.published_at">{{ formatDate(featured.published_at) }}</time> · {{ featured.read_time }} {{ text('min read', 'min de lecture') }}</p>
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

					<div v-if="!filtered.length" class="panel p-9 text-center">
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
