<template>
	<public-layout>
		<section class="bg-white border-b border-gray-200">
			<div class="max-w-7xl mx-auto px-6 py-16 lg:py-20 grid lg:grid-cols-2 gap-12 items-center">
				<div>
					<div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold border border-blue-100">About Internly</div>
					<h1 class="mt-6 text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">
						A cleaner way for students and companies to meet.
					</h1>
					<p class="mt-6 text-lg text-gray-600 leading-8">
						Internly is an internship discovery platform built to make early-career hiring simpler: students can browse public opportunities and apply online, while companies can publish internships and manage applications from one focused workspace.
					</p>
					<div class="mt-8 flex flex-wrap gap-3">
						<Link :href="route('internships.index')" class="px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Browse internships</Link>
						<Link :href="route('register')" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-900 font-semibold hover:bg-gray-200 transition">Create account</Link>
					</div>
				</div>

				<div class="bg-gray-900 text-white rounded-3xl p-8 shadow-xl">
					<div class="text-sm font-semibold text-blue-300 uppercase tracking-wide">What it solves</div>
					<div class="mt-6 space-y-5">
						<div v-for="item in highlights" :key="item.title" class="flex gap-4">
							<div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0"><icon name="check" class="h-5 w-5" /></div>
							<div>
								<h3 class="font-bold">{{ item.title }}</h3>
								<p class="mt-1 text-gray-300 leading-6">{{ item.text }}</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="max-w-7xl mx-auto px-6 py-16">
			<div class="grid lg:grid-cols-3 gap-8">
				<div class="lg:col-span-2 bg-white border border-gray-200 rounded-3xl p-8 shadow-xs">
					<div class="text-sm font-semibold text-blue-600 uppercase tracking-wide">Built by</div>
					<h2 class="mt-3 text-3xl font-extrabold text-gray-900">Ala Baganne</h2>
					<p class="mt-5 text-gray-700 leading-8 text-lg">I’m a software engineer and builder focused on practical SaaS products, clean user experiences, and reliable full-stack execution. I enjoy taking an idea from rough requirements to a working deployed product: frontend, backend, database, authentication, permissions, deployment, and the final details that make a demo feel real.</p>
					<p class="mt-4 text-gray-700 leading-8 text-lg">Internly is part of that builder journey: a working application with public internship pages, separate student and company accounts, company onboarding, application management, file uploads, and messaging.</p>
					<p class="mt-4 text-gray-700 leading-8 text-lg">I’m open to strong engineering opportunities, especially roles where I can build useful products end to end. If you have a product idea, an MVP to launch, or a business workflow you want turned into software, I’m also open to building for people and helping bring practical ideas to life.</p>

					<div class="mt-8 grid sm:grid-cols-3 gap-4">
						<div class="rounded-2xl bg-gray-50 p-5 border border-gray-100">
							<div class="text-2xl font-extrabold text-gray-900">Full-stack</div>
							<div class="mt-2 text-sm text-gray-600">Laravel, Vue, databases, auth, deployment</div>
						</div>
						<div class="rounded-2xl bg-gray-50 p-5 border border-gray-100">
							<div class="text-2xl font-extrabold text-gray-900">Product-minded</div>
							<div class="mt-2 text-sm text-gray-600">Focused on useful features and polished demos</div>
						</div>
						<div class="rounded-2xl bg-gray-50 p-5 border border-gray-100">
							<div class="text-2xl font-extrabold text-gray-900">Builder</div>
							<div class="mt-2 text-sm text-gray-600">Shipping SaaS-style apps end to end</div>
						</div>
					</div>
				</div>

				<aside class="bg-blue-600 text-white rounded-3xl p-8 shadow-xs">
					<h3 class="text-2xl font-extrabold">Developer contact</h3>
					<div class="mt-6 space-y-4 text-blue-50">
						<a href="mailto:alabaganne9@gmail.com" class="block hover:text-white break-all">alabaganne9@gmail.com</a>
						<a href="https://www.alabaganne.com" target="_blank" class="block hover:text-white">www.alabaganne.com</a>
						<a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="block hover:text-white">LinkedIn / alabaganne</a>
					</div>
					<p class="mt-8 text-blue-100 leading-7">If you’re hiring for full-stack, frontend, backend, or product-focused engineering roles — or you want to build a product from an idea — I’d be glad to connect.</p>
				</aside>
			</div>
		</section>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout },
	data() {
		return {
			highlights: [
				{ title: 'Public discovery', text: 'Internship listings are accessible without logging in, making opportunities easier to find and share.' },
				{ title: 'Separate student and company workspaces', text: 'Students apply and track applications; companies publish internships and review applicants.' },
				{ title: 'Company onboarding', text: 'Companies add a website, city, and description before posting, so students can see who is hiring.' },
			]
		}
	}
}
</script>
