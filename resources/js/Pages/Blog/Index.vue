<template>
	<public-layout>
		<section class="bg-white border-b border-gray-200">
			<div class="max-w-7xl mx-auto px-6 py-16 lg:py-20">
				<div class="max-w-3xl">
					<div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold border border-blue-100">Nextward Career Guides</div>
					<h1 class="mt-6 text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">Career guides for students and professionals</h1>
					<p class="mt-6 text-lg text-gray-600 leading-8">Practical guides on finding internships and jobs, writing stronger applications, hiring candidates, and keeping your next career move organized.</p>
				</div>
			</div>
		</section>

		<section class="max-w-7xl mx-auto px-6 py-14">
			<div class="grid md:grid-cols-2 gap-6">
				<article v-for="post in posts" :key="post.slug" class="bg-white border border-gray-200 rounded-3xl p-7 shadow-xs hover:shadow-md transition">
					<div class="flex items-center justify-between gap-4 text-sm">
						<span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">{{ post.category }}</span>
						<span class="text-gray-500">{{ post.read_time }}</span>
					</div>
					<h2 class="mt-5 text-2xl font-extrabold text-gray-900 leading-tight">
						<Link :href="route('blog.show', post.slug)" class="hover:text-blue-700">{{ post.title }}</Link>
					</h2>
					<p class="mt-4 text-gray-600 leading-7">{{ post.excerpt }}</p>
					<div class="mt-6 flex items-center justify-between">
						<span class="text-sm text-gray-500">Published {{ formatDate(post.published_at) }}</span>
						<Link :href="route('blog.show', post.slug)" class="inline-flex items-center gap-2 font-semibold text-blue-700 hover:text-blue-800">Read guide <icon name="arrow-right" class="h-4 w-4" /></Link>
					</div>
				</article>
			</div>
		</section>

		<section class="max-w-7xl mx-auto px-6 pb-16">
			<div class="rounded-3xl bg-gray-900 text-white p-8 md:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
				<div>
					<h2 class="text-2xl md:text-3xl font-extrabold">Ready to explore your next opportunity?</h2>
					<p class="mt-3 text-gray-300 leading-7">Browse internships and jobs in Tunisia and worldwide, and check where each application takes place.</p>
				</div>
				<Link :href="route('opportunities.index')" class="px-6 py-3 rounded-xl bg-white text-gray-900 font-semibold text-center hover:bg-gray-100 transition">Browse opportunities</Link>
			</div>
		</section>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout },
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
