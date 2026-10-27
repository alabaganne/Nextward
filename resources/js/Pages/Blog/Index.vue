<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, X } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
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
			<LandingHero title-id="guides-title" :overlap="!!posts.length" :label="text('The Nextward career guide', 'Le guide carrière Nextward')" :lead="text('Practical advice for your first application, your next role and everything in between.', 'Des conseils pratiques pour votre première candidature, votre prochain poste et tout ce qui se passe entre les deux.')">
				{{ text('A little guidance for', 'Quelques conseils pour') }} <span class="accent">{{ text('your next move.', 'votre prochaine étape.') }}</span>
			</LandingHero>

			<div v-if="posts.length" class="public-container hero-overlap">
				<div class="rounded-card border border-line bg-white p-6 max-[768px]:p-4">
					<form role="search" :aria-label="text('Find a guide', 'Trouver un guide')" @submit.prevent>
						<label for="guide-search" class="sr-only">{{ text('Find a guide', 'Trouver un guide') }}</label>
						<span class="field"><Search aria-hidden="true" /><input id="guide-search" v-model="query" type="search" maxlength="100" :placeholder="text('Find a guide', 'Trouver un guide')" autocomplete="off" /></span>
					</form>
					<div class="mt-4 -mx-4 flex gap-2 overflow-x-auto px-4 py-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" role="group" :aria-label="text('Filter by category', 'Filtrer par catégorie')">
						<button type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': !category }" :aria-pressed="!category" @click="category = ''">{{ text('All guides', 'Tous les guides') }}</button>
						<button v-for="item in categories" :key="item" type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': category === item }" :aria-pressed="category === item" @click="category = category === item ? '' : item">{{ item }}</button>
					</div>
				</div>
			</div>

			<section class="public-container pt-14 pb-20 max-[768px]:pt-10" :aria-label="text('All guides', 'Tous les guides')">
				<p v-if="!posts.length" class="panel p-8 text-subtle">{{ text('New guides are on the way.', 'De nouveaux guides arrivent bientôt.') }}</p>
				<template v-else>
					<p class="sr-only" role="status">{{ filtering ? text(`${filtered.length} ${filtered.length === 1 ? 'guide' : 'guides'} found`, `${filtered.length} ${filtered.length === 1 ? 'guide trouvé' : 'guides trouvés'}`) : '' }}</p>

					<Link v-if="featured" :href="featured.path" class="group focus-ring grid items-center gap-12 rounded-card max-[1024px]:gap-8 max-[768px]:gap-6" :class="featuredImage ? 'grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] max-[768px]:grid-cols-1' : 'grid-cols-1 bg-pale p-9 max-[768px]:p-6'" :lang="featured.locale">
						<div v-if="featuredImage" class="relative overflow-hidden rounded-card bg-blue-50">
							<img :src="featuredImage.src" alt="" :width="featuredImage.width" :height="featuredImage.height" fetchpriority="high" decoding="async" class="h-auto w-full" />
							<span v-if="featured.category" class="absolute top-5 left-5 rounded-card bg-white px-4 py-2 text-[12.5px] leading-5 tracking-caps text-navy uppercase">{{ featured.category }}<template v-if="featured.draft"> · {{ text('Draft', 'Brouillon') }}</template></span>
						</div>
						<div>
							<p class="landing-label">{{ text('Latest guide', 'Dernier guide') }}</p>
							<p v-if="!featuredImage && featured.category" class="guide-category mt-2">{{ featured.category }}<template v-if="featured.draft"> · {{ text('Draft', 'Brouillon') }}</template></p>
							<h2 class="mt-3 text-[length:clamp(28px,3vw,40px)] leading-[1.2] text-navy text-balance transition-colors duration-180 group-hover:text-brand">{{ featured.title }}</h2>
							<p class="mt-4 max-w-[560px] text-[17px] leading-[1.7] text-subtle">{{ featured.excerpt }}</p>
							<p class="mt-4 text-[14px] text-subtle-2"><time :datetime="featured.published_at">{{ formatDate(featured.published_at) }}</time> · {{ featured.read_time }} {{ text('min read', 'min de lecture') }}</p>
							<span class="landing-link mt-5">{{ text('Read the guide', 'Lire le guide') }}<ArrowRight class="action-icon" aria-hidden="true" /></span>
						</div>
					</Link>

					<div v-if="rest.length">
						<div class="section-head" :class="featured && 'mt-20 max-[768px]:mt-14'">
							<h2 class="section-title">{{ filtering ? text('Matching guides', 'Guides correspondants') : text('Explore the guides', 'Explorer les guides') }}</h2>
							<button v-if="filtering" type="button" class="landing-link focus-ring" @click="reset"><X class="action-icon" aria-hidden="true" />{{ text('Clear filters', 'Effacer les filtres') }}</button>
						</div>
						<div class="guide-grid"><GuideCard v-for="post in rest" :key="post.slug" :post="post" /></div>
					</div>

					<div v-if="!filtered.length" class="panel flex flex-col items-center px-5 py-16 text-center">
						<span class="icon-tile" aria-hidden="true"><Search class="size-6" /></span>
						<h2 class="mt-5 text-[22px] font-medium text-navy">{{ text('No guide matches this search.', 'Aucun guide ne correspond à cette recherche.') }}</h2>
						<p class="mt-2 text-subtle">{{ text('Try another word or a different category.', 'Essayez un autre mot ou une autre catégorie.') }}</p>
						<button type="button" class="cta cta-secondary focus-ring mt-6" @click="reset">{{ text('Show all guides', 'Afficher tous les guides') }}</button>
					</div>
				</template>
			</section>

			<ClosingBanner :title="text('Turn your next read into your next move.', 'Passez de la lecture à l’action.')" :body="text('Browse IT internships and jobs around the world, and check where each application takes place.', 'Consultez les stages et les emplois en informatique partout dans le monde, et vérifiez où se fait chaque candidature.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse roles', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>
