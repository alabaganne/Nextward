<template>
	<public-layout>
		<div class="landing">
			<article>
				<header class="hero-dark">
					<div class="public-container max-w-3xl! py-16 lg:py-20">
						<Link :href="route('blog.index')" class="focus-ring inline-flex items-center gap-2 text-sm font-semibold text-blue-200 hover:text-white"><icon name="arrow-left" class="h-4 w-4" /> All guides</Link>
						<p class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-300">
							<span class="rounded-full border border-white/20 px-3 py-1 font-semibold text-blue-100">{{ post.category }}</span>
							<span>{{ formatDate(post.published_at) }}</span>
							<span>{{ post.read_time }}</span>
						</p>
						<h1 class="hero-title !text-4xl md:!text-5xl">{{ post.title }}</h1>
						<p class="hero-lead !max-w-none">{{ post.excerpt }}</p>
					</div>
				</header>

				<div class="public-container max-w-3xl! py-14">
					<section v-for="section in post.sections" :key="section.heading" class="mb-12 last:mb-0">
						<h2 class="text-2xl font-semibold tracking-tight">{{ section.heading }}</h2>
						<p class="mt-4 text-slate-700 leading-8 text-lg">{{ section.body }}</p>
					</section>
				</div>
			</article>

			<section v-if="related.length" class="public-container max-w-3xl! pb-16" aria-labelledby="related-title">
				<h2 id="related-title" class="text-2xl font-semibold tracking-tight">More guides</h2>
				<div class="guide-list !mt-6">
					<Link v-for="item in related" :key="item.slug" :href="route('blog.show', item.slug)" class="related-row guide-row focus-ring">
						<div><h3>{{ item.title }}</h3><p>{{ item.excerpt }}</p></div>
						<icon name="arrow-up-right" class="guide-arrow" />
					</Link>
				</div>
			</section>

			<section class="closing-band">
				<div class="public-container flex flex-col md:flex-row md:items-center md:justify-between gap-8 py-14">
					<div class="flex items-center gap-6">
						<img src="/nextward-assets/mascots/01-student-wave.svg" alt="" width="768" height="768" loading="lazy" class="h-28 w-28 shrink-0" />
						<div>
							<h2 class="section-title !text-3xl">Put it into practice on Nextward</h2>
							<p class="mt-3 leading-7">Students and professionals can explore internships and jobs. Employers can publish opportunities and review native applications in one workspace.</p>
						</div>
					</div>
					<div class="flex flex-col sm:flex-row gap-3 shrink-0">
						<Link :href="route('opportunities.index')" class="cta cta-light focus-ring">Browse opportunities</Link>
						<Link :href="route('register')" class="cta cta-ghost focus-ring">Create account</Link>
					</div>
				</div>
			</section>
		</div>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout },
	props: {
		post: { type: Object, required: true },
		related: { type: Array, default: () => [] },
	},
	methods: {
		formatDate(date) {
			return new Date(date).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' })
		}
	}
}
</script>

<style scoped>
.related-row { grid-template-columns: 1fr 24px; }
@media (max-width: 767px) {
	.related-row > div { grid-row: 1; }
	.related-row .guide-arrow { grid-row: 1; }
}
</style>
