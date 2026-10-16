<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';

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
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<article :lang="post.locale">
				<header class="hero-dark">
					<div class="public-container max-w-3xl! py-16 lg:py-20">
						<nav class="flex flex-wrap items-center justify-between gap-4 text-sm font-semibold">
							<Link :href="`${prefix}/blog`" class="focus-ring inline-flex items-center gap-2 text-blue-200 hover:text-white"><Icon name="arrow-left" class="h-4 w-4" /> {{ text('All guides', 'Tous les guides') }}</Link>
							<Link v-if="translation" :href="translation" class="focus-ring rounded-full border border-white/20 px-3 py-1 text-blue-100 hover:text-white" :hreflang="fr ? 'en' : 'fr'">{{ text('Lire en français', 'Read in English') }}</Link>
						</nav>
						<p class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-300">
							<span v-if="post.category" class="rounded-full border border-white/20 px-3 py-1 font-semibold text-blue-100">{{ post.category }}</span>
							<span v-if="post.draft" class="rounded-full bg-amber-300 px-3 py-1 font-semibold text-navy">{{ text('Draft', 'Brouillon') }}</span>
							<time :datetime="post.published_at">{{ formatDate(post.published_at) }}</time>
							<span>{{ post.read_time }} {{ text('min read', 'min de lecture') }}</span>
						</p>
						<h1 class="hero-title !text-4xl md:!text-5xl">{{ post.title }}</h1>
						<p class="hero-lead !max-w-none">{{ post.excerpt }}</p>
					</div>
				</header>

				<div class="public-container max-w-5xl! py-14 grid gap-12 lg:grid-cols-[minmax(0,1fr)_220px]">
					<div class="min-w-0 max-w-3xl">
						<img v-if="post.cover" :src="post.cover" :alt="post.cover_alt" width="1200" height="630" class="mb-10 w-full rounded-2xl border border-navy/5 bg-white" fetchpriority="high" />
						<div class="nw-prose" v-html="post.html"></div>

						<section v-if="post.faq?.length" class="mt-14" aria-labelledby="faq-title">
							<h2 id="faq-title" class="text-2xl font-semibold tracking-tight">{{ text('Frequently asked questions', 'Questions fréquentes') }}</h2>
							<div class="mt-6 divide-y divide-navy/10 border-y border-navy/10">
								<details v-for="item in post.faq" :key="item.q" class="faq-item group py-5">
									<summary class="focus-ring flex cursor-pointer list-none items-start justify-between gap-6 text-lg font-semibold">{{ item.q }}<Icon name="plus" class="mt-1 h-5 w-5 shrink-0 text-brand transition group-open:rotate-45" /></summary>
									<p class="mt-3 text-slate-700 leading-8">{{ item.a }}</p>
								</details>
							</div>
						</section>

						<section v-if="post.links?.length" class="mt-14 rounded-2xl bg-white border border-navy/5 p-6" aria-labelledby="links-title">
							<h2 id="links-title" class="text-lg font-semibold">{{ text('Browse matching opportunities', 'Voir les offres correspondantes') }}</h2>
							<ul class="mt-4 flex flex-wrap gap-3">
								<li v-for="link in post.links" :key="link.href"><Link :href="link.href" class="focus-ring inline-flex items-center gap-2 rounded-full border border-navy/10 px-4 py-2 text-sm font-semibold hover:border-brand/40 hover:text-brand">{{ link.label }}<Icon name="arrow-up-right" class="h-4 w-4" /></Link></li>
							</ul>
						</section>

						<p class="mt-14 border-t border-navy/10 pt-6 text-sm text-slate-600">
							{{ text('Written by', 'Écrit par') }} <a href="https://www.alabaganne.com/" class="font-semibold text-navy hover:text-brand">Ala Baganne</a>, {{ text('founder of Nextward', 'fondateur de Nextward') }}.
							<template v-if="updated"> {{ text('Updated on', 'Mis à jour le') }} <time :datetime="post.updated_at">{{ formatDate(post.updated_at) }}</time>.</template>
						</p>
					</div>

					<aside v-if="post.toc?.length > 1" class="hidden lg:block">
						<nav class="sticky top-28 text-sm" :aria-label="text('On this page', 'Sur cette page')">
							<p class="font-semibold text-navy">{{ text('On this page', 'Sur cette page') }}</p>
							<ul class="mt-4 space-y-3 border-l border-navy/10">
								<li v-for="item in post.toc" :key="item.id"><a :href="`#${item.id}`" class="focus-ring -ml-px block border-l border-transparent pl-4 text-slate-600 hover:border-brand hover:text-brand">{{ item.text }}</a></li>
							</ul>
						</nav>
					</aside>
				</div>
			</article>

			<section v-if="related.length" class="public-container max-w-3xl! pb-16" aria-labelledby="related-title">
				<h2 id="related-title" class="text-2xl font-semibold tracking-tight">{{ text('More guides', 'Autres guides') }}</h2>
				<div class="guide-list !mt-6">
					<Link v-for="item in related" :key="item.slug" :href="item.path" class="related-row guide-row focus-ring">
						<div><h3>{{ item.title }}</h3><p>{{ item.excerpt }}</p></div>
						<Icon name="arrow-up-right" class="guide-arrow" />
					</Link>
				</div>
			</section>

			<section class="closing-band">
				<div class="public-container flex flex-col md:flex-row md:items-center md:justify-between gap-8 py-14">
					<div class="flex items-center gap-6">
						<img src="/nextward-assets/mascots/01-student-wave.svg" alt="" width="768" height="768" loading="lazy" class="h-28 w-28 shrink-0" />
						<div>
							<h2 class="section-title !text-3xl">{{ text('Put it into practice on Nextward', 'Passez à l’action sur Nextward') }}</h2>
							<p class="mt-3 leading-7">{{ text('Explore internships and jobs in Tunisia and worldwide, with clear sources and application details.', 'Découvrez des stages et des emplois en Tunisie et à l’international, avec des sources et des conditions claires.') }}</p>
						</div>
					</div>
					<div class="flex flex-col sm:flex-row gap-3 shrink-0">
						<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}</Link>
						<Link v-if="!page.props.auth?.user" :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create account', 'Créer un compte') }}</Link>
					</div>
				</div>
			</section>
		</div>
	</PublicLayout>
</template>

<style scoped>
.related-row { grid-template-columns: 1fr 24px; }
.faq-item summary::-webkit-details-marker { display: none; }
@media (max-width: 767px) {
	.related-row > div { grid-row: 1; }
	.related-row .guide-arrow { grid-row: 1; }
}
</style>
