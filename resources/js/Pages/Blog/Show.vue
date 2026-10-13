<template>
	<public-layout>
		<article>
			<header class="bg-white border-b border-gray-200">
				<div class="max-w-4xl mx-auto px-6 py-14 lg:py-18">
					<Link :href="route('blog.index')" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800"><icon name="arrow-left" class="h-4 w-4" /> Back to blog</Link>
					<div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
						<span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-100">{{ post.category }}</span>
						<span class="text-gray-500">{{ formatDate(post.published_at) }}</span>
						<span class="text-gray-400">•</span>
						<span class="text-gray-500">{{ post.read_time }}</span>
					</div>
					<h1 class="mt-6 text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">{{ post.title }}</h1>
					<p class="mt-6 text-xl text-gray-600 leading-8">{{ post.excerpt }}</p>
				</div>
			</header>

			<section class="max-w-4xl mx-auto px-6 py-12">
				<div class="bg-white border border-gray-200 rounded-3xl p-7 md:p-10 shadow-xs">
					<section v-for="section in post.sections" :key="section.heading" class="mb-10 last:mb-0">
						<h2 class="text-2xl font-extrabold text-gray-900">{{ section.heading }}</h2>
						<p class="mt-4 text-gray-700 leading-8 text-lg">{{ section.body }}</p>
					</section>
				</div>
			</section>
		</article>

		<section class="max-w-4xl mx-auto px-6 pb-16">
			<div class="rounded-3xl bg-blue-600 text-white p-8 md:p-10">
				<h2 class="text-2xl md:text-3xl font-extrabold">Put it into practice on Nextward</h2>
				<p class="mt-3 text-blue-50 leading-7">Students and professionals can explore internships and jobs. Employers can publish opportunities and review native applications in one workspace.</p>
				<div class="mt-6 flex flex-col sm:flex-row gap-3">
					<Link :href="route('opportunities.index')" class="px-5 py-3 rounded-xl bg-white text-blue-700 font-semibold text-center hover:bg-blue-50 transition">Browse opportunities</Link>
					<Link :href="route('register')" class="px-5 py-3 rounded-xl bg-blue-700 text-white font-semibold text-center hover:bg-blue-800 transition">Create account</Link>
				</div>
			</div>
		</section>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout },
	props: {
		post: { type: Object, required: true },
	},
	methods: {
		formatDate(date) {
			return new Date(date).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' })
		}
	}
}
</script>
