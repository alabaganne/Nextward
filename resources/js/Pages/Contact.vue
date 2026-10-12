<template>
	<public-layout>
		<section class="bg-white border-b border-gray-200">
			<div class="max-w-7xl mx-auto px-6 py-16 lg:py-20">
				<div class="max-w-3xl">
					<div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold border border-blue-100">Contact</div>
					<h1 class="mt-6 text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">Let’s talk about Internly or engineering opportunities.</h1>
					<p class="mt-6 text-lg text-gray-600 leading-8">
						Whether you’re a student, company, recruiter, or engineering team, you can reach the developer directly using the links below.
					</p>
				</div>
			</div>
		</section>

		<section class="max-w-7xl mx-auto px-6 py-16">
			<div class="grid lg:grid-cols-3 gap-8">
				<div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200 p-8 shadow-xs">
					<h2 class="text-2xl font-extrabold text-gray-900">Get in touch</h2>
					<p class="mt-3 text-gray-600 leading-7">For job opportunities, freelance work, product feedback, MVP builds, or questions about this app, email is the fastest way to reach me.</p>

					<div class="mt-8 grid sm:grid-cols-2 gap-4">
						<a href="mailto:alabaganne9@gmail.com" class="group rounded-2xl border border-gray-200 p-5 hover:border-blue-300 hover:bg-blue-50 transition">
							<div class="text-sm font-semibold text-gray-500">Email</div>
							<div class="mt-2 font-bold text-gray-900 group-hover:text-blue-700 break-all">alabaganne9@gmail.com</div>
						</a>
						<a href="https://www.alabaganne.com" target="_blank" class="group rounded-2xl border border-gray-200 p-5 hover:border-blue-300 hover:bg-blue-50 transition">
							<div class="text-sm font-semibold text-gray-500">Portfolio</div>
							<div class="mt-2 font-bold text-gray-900 group-hover:text-blue-700">www.alabaganne.com</div>
						</a>
						<a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="group rounded-2xl border border-gray-200 p-5 hover:border-blue-300 hover:bg-blue-50 transition">
							<div class="text-sm font-semibold text-gray-500">LinkedIn</div>
							<div class="mt-2 font-bold text-gray-900 group-hover:text-blue-700">linkedin.com/in/alabaganne</div>
						</a>
						<Link :href="route('internships.index')" class="group rounded-2xl border border-gray-200 p-5 hover:border-blue-300 hover:bg-blue-50 transition">
							<div class="text-sm font-semibold text-gray-500">Explore the product</div>
							<div class="mt-2 font-bold text-gray-900 group-hover:text-blue-700">Browse internships</div>
						</Link>
					</div>
				</div>

				<aside class="rounded-3xl bg-gray-900 text-white p-8 shadow-xs">
					<h3 class="text-2xl font-extrabold">About the developer</h3>
					<p class="mt-4 text-gray-300 leading-7">Ala Baganne is a software engineer and SaaS-focused builder who likes shipping practical full-stack products with clean interfaces, solid auth, useful role permissions, and real deployment polish. He is open to good engineering opportunities and to helping people turn product ideas into working software.</p>
					<div class="mt-8 space-y-3 text-sm text-gray-300">
						<div class="flex items-center justify-between border-b border-white/10 pb-3">
							<span>Focus</span>
							<span class="font-semibold text-white">Full-stack web</span>
						</div>
						<div class="flex items-center justify-between border-b border-white/10 pb-3">
							<span>Product type</span>
							<span class="font-semibold text-white">SaaS apps</span>
						</div>
						<div class="flex items-center justify-between">
							<span>Available for</span>
							<span class="font-semibold text-white">Roles & product builds</span>
						</div>
					</div>
				</aside>
			</div>
		</section>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout }
}
</script>
