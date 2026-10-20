<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import { guideImage } from '@/Components/Blog/guideImages';

const props = defineProps({
	post: { type: Object, required: true },
	translation: { type: String, default: null },
	related: { type: Array, default: () => [] },
});
const page = usePage();
const fr = computed(() => props.post.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const prefix = computed(() => fr.value ? '/fr' : '');
const formatDate = (date) => new Date(date).toLocaleDateString(fr.value ? 'fr-FR' : 'en', { month: 'long', day: 'numeric', year: 'numeric' });
const updated = computed(() => props.post.updated_at && props.post.updated_at !== props.post.published_at);
const image = computed(() => guideImage(props.post));
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<article :lang="post.locale">
				<header class="hero-dark">
					<div class="public-container grid max-w-5xl gap-10 pt-14 pb-16 max-[768px]:pt-9 max-[768px]:pb-11" :class="{ 'grid-cols-[minmax(0,1fr)_minmax(0,400px)] items-end max-[1024px]:grid-cols-1 max-[1024px]:gap-7': image }">
						<div class="min-w-0">
							<nav class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm font-semibold">
								<Link :href="`${prefix}/blog`" class="focus-ring inline-flex items-center gap-2 text-blue-200 hover:text-white"><ArrowLeft class="h-4 w-4" aria-hidden="true" />{{ text('All guides', 'Tous les guides') }}</Link>
								<Link v-if="translation" :href="translation" class="focus-ring rounded-full border border-white/20 px-3 py-1 text-blue-100 hover:border-white/50 hover:text-white" :hreflang="fr ? 'en' : 'fr'">{{ text('Lire en français', 'Read in English') }}</Link>
							</nav>
							<p class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-300">
								<span v-if="post.category" class="rounded-full border border-white/20 px-3 py-1 font-semibold text-blue-100">{{ post.category }}</span>
								<span v-if="post.draft" class="rounded-full bg-amber-300 px-3 py-1 font-semibold text-navy">{{ text('Draft', 'Brouillon') }}</span>
								<time :datetime="post.published_at">{{ formatDate(post.published_at) }}</time>
								<span>{{ post.read_time }} {{ text('min read', 'min de lecture') }}</span>
							</p>
							<h1 class="mt-4 text-[length:clamp(34px,4.2vw,52px)] leading-[1.08] font-bold tracking-[-.045em] text-balance">{{ post.title }}</h1>
							<p class="mt-[18px] max-w-[640px] text-[18px] leading-[1.7] text-mist max-[768px]:text-[17px]">{{ post.excerpt }}</p>
						</div>
						<img v-if="image" :src="image.src" :alt="image.alt" :width="image.width" :height="image.height" fetchpriority="high" decoding="async" class="h-auto w-full rounded-[18px] bg-navy-600 max-[1024px]:max-w-[560px]" />
					</div>
				</header>

				<div class="public-container max-w-5xl! pt-12 pb-16 grid gap-12 lg:grid-cols-[minmax(0,1fr)_220px]">
					<div class="min-w-0 max-w-3xl">
						<div class="nw-prose" v-html="post.html"></div>

						<section v-if="post.faq?.length" class="mt-14" aria-labelledby="faq-title">
							<h2 id="faq-title" class="font-display text-2xl font-semibold tracking-tight">{{ text('Frequently asked questions', 'Questions fréquentes') }}</h2>
							<div class="faq-list mt-6">
								<details v-for="item in post.faq" :key="item.q" class="faq-item">
									<summary class="focus-ring">{{ item.q }}<Icon name="plus" class="faq-toggle" /></summary>
									<p>{{ item.a }}</p>
								</details>
							</div>
						</section>

						<section v-if="post.links?.length" class="mt-14 rounded-2xl bg-blue-50 p-6" aria-labelledby="links-title">
							<h2 id="links-title" class="text-lg font-semibold">{{ text('Browse matching opportunities', 'Voir les offres correspondantes') }}</h2>
							<ul class="mt-4 flex flex-wrap gap-3">
								<li v-for="link in post.links" :key="link.href"><Link :href="link.href" class="chip focus-ring">{{ link.label }}<ArrowRight aria-hidden="true" /></Link></li>
							</ul>
						</section>

						<p class="mt-14 border-t border-line pt-6 text-sm text-slate-600">
							{{ text('Written by', 'Écrit par') }} <a href="https://www.alabaganne.com/" class="font-semibold text-navy hover:text-brand">Ala Baganne</a>, {{ text('founder of Nextward', 'fondateur de Nextward') }}.
							<template v-if="updated"> {{ text('Updated on', 'Mis à jour le') }} <time :datetime="post.updated_at">{{ formatDate(post.updated_at) }}</time>.</template>
						</p>
					</div>

					<aside v-if="post.toc?.length > 1" class="hidden lg:block">
						<nav class="sticky top-28 text-sm" :aria-label="text('On this page', 'Sur cette page')">
							<p class="font-semibold text-navy">{{ text('On this page', 'Sur cette page') }}</p>
							<ul class="mt-4 space-y-1">
								<li v-for="item in post.toc" :key="item.id"><a :href="`#${item.id}`" class="focus-ring block rounded-lg px-2.5 py-1.5 leading-[1.5] text-subtle transition-colors duration-150 hover:bg-pale hover:text-brand">{{ item.text }}</a></li>
							</ul>
						</nav>
					</aside>
				</div>
			</article>

			<section v-if="related.length" class="band-pale py-16 max-[768px]:py-12" aria-labelledby="related-title">
				<div class="public-container">
					<div class="section-head">
						<h2 id="related-title" class="section-title">{{ text('More guides', 'Autres guides') }}</h2>
						<Link :href="`${prefix}/blog`" class="landing-link focus-ring">{{ text('View all guides', 'Voir tous les guides') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
					</div>
					<div class="guide-grid is-three"><GuideCard v-for="item in related" :key="item.slug" :post="item" /></div>
				</div>
			</section>

			<ClosingBanner mascot="01-student-wave" :title="text('Put it into practice on Nextward', 'Passez à l’action sur Nextward')" :body="text('Explore internships and jobs in Tunisia and worldwide, with clear sources and application details.', 'Découvrez des stages et des emplois en Tunisie et à l’international, avec des sources et des conditions claires.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}</Link>
				<Link v-if="!page.props.auth?.user" :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create account', 'Créer un compte') }}</Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>

<style src="./Show.css"></style>
