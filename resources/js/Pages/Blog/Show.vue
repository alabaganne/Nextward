<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ChevronDown } from '@lucide/vue';
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
					<div class="public-container relative z-[1] max-w-[880px]! text-center" :class="image ? 'pt-14 pb-40 max-[768px]:pt-9 max-[768px]:pb-28' : 'py-14 max-[768px]:py-9'">
						<nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-[14px]">
							<Link :href="`${prefix}/blog`" class="focus-ring inline-flex items-center gap-2 tracking-caps text-mist uppercase hover:text-white"><ArrowLeft class="size-4" aria-hidden="true" />{{ text('All guides', 'Tous les guides') }}</Link>
							<Link v-if="translation" :href="translation" class="focus-ring rounded-card border border-white/30 px-3 py-1 text-white/85 hover:border-white hover:text-white" :hreflang="fr ? 'en' : 'fr'">{{ text('Lire en français', 'Read in English') }}</Link>
						</nav>
						<p class="mt-9 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-[14px] text-mist-2">
							<span v-if="post.category" class="rounded-card bg-white/10 px-3.5 py-1.5 text-[12.5px] tracking-caps text-white uppercase">{{ post.category }}</span>
							<span v-if="post.draft" class="rounded-card bg-amber-300 px-3.5 py-1.5 text-[12.5px] font-medium text-navy">{{ text('Draft', 'Brouillon') }}</span>
							<time :datetime="post.published_at">{{ formatDate(post.published_at) }}</time>
							<span>{{ post.read_time }} {{ text('min read', 'min de lecture') }}</span>
						</p>
						<h1 class="mt-5 text-[length:clamp(34px,4.4vw,56px)] leading-[1.15] font-medium text-balance">{{ post.title }}</h1>
						<p class="mx-auto mt-5 max-w-[680px] text-[18px] leading-[1.7] text-mist max-[768px]:text-[17px]">{{ post.excerpt }}</p>
					</div>
				</header>

				<div v-if="image" class="public-container hero-overlap max-w-[880px]!">
					<img :src="image.src" :alt="image.alt" :width="image.width" :height="image.height" fetchpriority="high" decoding="async" class="h-auto w-full rounded-card bg-pale" />
				</div>

				<div class="public-container max-w-5xl! grid gap-14 pt-14 pb-20 max-[768px]:pt-10 lg:grid-cols-[minmax(0,1fr)_230px]">
					<div class="min-w-0 max-w-3xl">
						<div class="nw-prose" v-html="post.html"></div>

						<section v-if="post.faq?.length" class="mt-16" aria-labelledby="faq-title">
							<h2 id="faq-title" class="text-[length:clamp(26px,2.6vw,34px)] leading-[1.25] text-navy">{{ text('Frequently asked questions', 'Questions fréquentes') }}</h2>
							<div class="faq-list mt-8">
								<details v-for="item in post.faq" :key="item.q" class="faq-item">
									<summary class="focus-ring">{{ item.q }}<span class="faq-toggle" aria-hidden="true"><ChevronDown /></span></summary>
									<p>{{ item.a }}</p>
								</details>
							</div>
						</section>

						<section v-if="post.links?.length" class="mt-16 rounded-card bg-pale p-7 max-[768px]:p-5" aria-labelledby="links-title">
							<h2 id="links-title" class="text-[20px] font-medium text-navy">{{ text('Browse matching roles', 'Voir les offres correspondantes') }}</h2>
							<ul class="mt-5 flex flex-wrap gap-3">
								<li v-for="link in post.links" :key="link.href"><Link :href="link.href" class="chip focus-ring">{{ link.label }}<ArrowRight aria-hidden="true" /></Link></li>
							</ul>
						</section>

						<div class="mt-16 flex items-center gap-4 border-t border-line pt-8">
							<span class="grid size-12 shrink-0 place-items-center rounded-card bg-brand text-[16px] font-medium text-white" aria-hidden="true">AB</span>
							<p class="text-[15px] leading-[1.6] text-subtle">
								{{ text('Written by', 'Écrit par') }} <a href="https://www.alabaganne.com/" class="font-medium text-navy hover:text-brand">Ala Baganne</a>, {{ text('founder of Nextward', 'fondateur de Nextward') }}.
								<template v-if="updated"><br />{{ text('Updated on', 'Mis à jour le') }} <time :datetime="post.updated_at">{{ formatDate(post.updated_at) }}</time>.</template>
							</p>
						</div>
					</div>

					<aside v-if="post.toc?.length > 1" class="hidden lg:block">
						<nav class="sticky top-28" :aria-label="text('On this page', 'Sur cette page')">
							<p class="px-3 text-[13px] tracking-caps text-brand uppercase">{{ text('On this page', 'Sur cette page') }}</p>
							<ul class="mt-4 space-y-1">
								<li v-for="item in post.toc" :key="item.id"><a :href="`#${item.id}`" class="focus-ring block rounded-card px-3 py-2 text-[14.5px] leading-[1.5] text-subtle transition-colors duration-150 hover:bg-pale hover:text-brand">{{ item.text }}</a></li>
							</ul>
						</nav>
					</aside>
				</div>
			</article>

			<section v-if="related.length" class="section border-t border-line" aria-labelledby="related-title">
				<div class="public-container">
					<div class="section-intro">
						<p class="landing-label">{{ text('Keep reading', 'À lire aussi') }}</p>
						<h2 id="related-title" class="section-title">{{ text('More', 'Autres') }} <span class="accent">{{ text('guides', 'guides') }}</span></h2>
					</div>
					<div class="guide-grid"><GuideCard v-for="item in related" :key="item.slug" :post="item" /></div>
					<div class="mt-14 flex justify-center"><Link :href="`${prefix}/blog`" class="cta cta-secondary focus-ring">{{ text('View all guides', 'Voir tous les guides') }}</Link></div>
				</div>
			</section>

			<ClosingBanner mascot="01-student-wave" :title="text('Put it into practice on Nextward', 'Passez à l’action sur Nextward')" :body="text('Explore IT internships and jobs around the world, with clear sources and application details.', 'Découvrez des stages et des emplois en informatique partout dans le monde, avec des sources et des conditions claires.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse roles', 'Voir les offres') }}</Link>
				<Link v-if="!page.props.auth?.user" :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create account', 'Créer un compte') }}</Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>

<style src="./Show.css"></style>
