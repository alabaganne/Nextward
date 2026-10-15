<template>
	<public-layout>
		<div class="landing">
			<section aria-labelledby="guides-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>Nextward career guides</p>
						<h1 id="guides-title" class="hero-title">Career guides for students and professionals</h1>
						<p class="hero-lead">Practical guides on finding internships and jobs, writing stronger applications, hiring candidates, and keeping your next career move organized.</p>
					</div>
					<figure class="hero-stage guides-stage">
						<img src="/nextward-assets/mascots/11-learning-books-and-cap.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 16%; left: 14%; --i: 0" />
						<Sparkle class="is-sky" style="top: 10%; right: 12%; width: 16px; height: 16px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-16" aria-label="All guides">
				<div class="guide-list !mt-0">
					<Link v-for="post in posts" :key="post.slug" :href="route('blog.show', post.slug)" class="guide-row focus-ring">
						<span class="guide-category">{{ post.category }}</span>
						<div><h2 class="guide-title">{{ post.title }}</h2><p>{{ post.excerpt }}</p></div>
						<span class="guide-time">{{ formatDate(post.published_at) }}<br />{{ post.read_time }}</span>
						<icon name="arrow-up-right" class="guide-arrow" />
					</Link>
				</div>
			</section>

			<section class="closing-band">
				<div class="public-container flex flex-col md:flex-row md:items-center md:justify-between gap-6 py-14">
					<div>
						<h2 class="section-title">Ready to explore your next opportunity?</h2>
						<p class="mt-3 leading-7">Browse internships and jobs in Tunisia and worldwide, and check where each application takes place.</p>
					</div>
					<Link :href="route('opportunities.index')" class="cta cta-light focus-ring">Browse opportunities</Link>
				</div>
			</section>
		</div>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'
import Sparkle from '@/Shared/Sparkle.vue'

export default {
	components: { PublicLayout, Sparkle },
	props: {
		posts: { type: Array, required: true },
	},
	methods: {
		formatDate(date) {
			return new Date(date).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' })
		}
	}
}
</script>

<style scoped>
.guides-stage { width: min(100%, 320px); justify-self: center; }
.guide-title { font-size: 21px; font-weight: 500; line-height: 1.4; letter-spacing: -.025em; }
.guide-row:hover .guide-title { color: var(--color-brand); }
@media (max-width: 767px) {
	.guide-title { font-size: 19px; }
}
</style>
