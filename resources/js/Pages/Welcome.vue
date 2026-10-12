<template>
	<public-layout>
		<div class="bg-white text-navy [&_p]:whitespace-normal">
			<!-- Hero -->
			<section aria-labelledby="hero-title" class="relative overflow-hidden bg-pale">
				<div aria-hidden="true" class="pointer-events-none absolute inset-0">
					<div class="absolute -top-48 right-[-12%] h-[34rem] w-[34rem] rounded-full bg-blue-200/45 blur-3xl"></div>
					<div class="absolute -bottom-56 left-[-14%] h-[30rem] w-[30rem] rounded-full bg-sky-100 blur-3xl"></div>
					<div class="absolute inset-0 bg-[radial-gradient(rgb(10_23_56/0.07)_1px,transparent_1px)] bg-size-[22px_22px] mask-[linear-gradient(to_bottom,black,transparent_70%)]"></div>
				</div>

				<div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-16 pt-12 sm:px-6 md:pt-16 lg:grid-cols-[1fr_1.05fr] lg:gap-12 lg:pb-24 lg:pt-20">
					<div class="max-w-xl">
						<p class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-sm font-semibold text-brand shadow-xs ring-1 ring-blue-100">
							<span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>
							For students and the companies hiring them
						</p>
						<h1 id="hero-title" class="mt-6 font-display text-[2.6rem] font-bold leading-[1.04] tracking-tight sm:text-5xl lg:text-[3.6rem]">
							Find the internship that <span class="text-brand">starts your career.</span>
						</h1>
						<p class="mt-6 text-lg leading-8 text-slate-600">Browse open internships without signing up. When one fits, apply with your cover letter and documents, then keep the conversation with the company in one place.</p>
						<div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
							<Link :href="route('internships.index')" class="cta cta-primary focus-ring text-base">
								Browse internships
								<svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h10M9 4l4 4-4 4" /></svg>
							</Link>
							<Link v-if="companyAction" :href="companyAction" class="cta cta-secondary focus-ring text-base">Hiring? Post an internship</Link>
						</div>
						<p class="mt-5 flex items-center gap-2 text-sm text-slate-500">
							<icon name="check-circle" solid class="h-4 w-4 text-brand" aria-hidden="true" />
							No account needed to browse listings.
						</p>
					</div>

					<div class="relative mx-auto w-full max-w-2xl lg:max-w-none">
						<div class="rounded-[2rem] bg-white p-2 shadow-[0_40px_90px_-45px_rgb(10_23_56/0.5)] ring-1 ring-navy/5">
							<loop-video name="conversation" class="rounded-[1.6rem]" />
						</div>

						<!-- Small labels that explain the three steps; they never show activity or numbers. -->
						<div class="pointer-events-none absolute inset-0 hidden sm:block" aria-hidden="true">
							<div v-for="(chip, index) in heroChips" :key="chip.title" class="absolute flex items-center gap-3 rounded-2xl bg-white/95 py-2.5 pl-2.5 pr-4 shadow-lg ring-1 ring-navy/5" :class="chip.position">
								<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-brand"><icon :name="chip.icon" class="h-5 w-5" /></span>
								<span>
									<span class="block text-sm font-semibold text-navy">{{ chip.title }}</span>
									<span class="block text-xs text-slate-500">{{ chip.text }}</span>
								</span>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- How it works -->
			<section id="how-it-works" aria-labelledby="how-title" class="bg-white">
				<div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
					<div v-reveal class="max-w-2xl">
						<p class="eyebrow">How it works</p>
						<h2 id="how-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Explore, apply, connect.</h2>
						<p class="mt-4 text-lg leading-8 text-slate-600">Every internship on Internly follows the same three steps, from the first search to the first conversation.</p>
					</div>

					<ol v-reveal class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
						<li aria-hidden="true" class="flow-line-x absolute left-[16%] right-[16%] top-7 hidden h-0.5 rounded-full bg-linear-to-r from-brand via-brand-soft to-blue-100 md:block"></li>
						<li aria-hidden="true" class="flow-line-y absolute bottom-10 left-[27px] top-4 w-0.5 rounded-full bg-linear-to-b from-brand via-brand-soft to-blue-100 md:hidden"></li>
						<li v-for="(step, index) in steps" :key="step.title" class="relative pl-20 md:pl-0 md:text-center">
							<span class="absolute left-0 top-0 z-10 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-brand shadow-md ring-1 ring-blue-100 md:relative md:mx-auto">
								<icon :name="step.icon" class="h-6 w-6" aria-hidden="true" />
							</span>
							<p class="text-sm font-semibold text-brand md:mt-6">Step {{ index + 1 }}</p>
							<h3 class="mt-1 font-display text-2xl font-semibold">{{ step.title }}</h3>
							<p class="mt-3 leading-7 text-slate-600 md:mx-auto md:max-w-xs">{{ step.text }}</p>
						</li>
					</ol>
				</div>
			</section>

			<!-- Students -->
			<section aria-labelledby="students-title" class="border-y border-navy/5 bg-pale">
				<div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:py-28">
					<div v-reveal>
						<p class="eyebrow">For students</p>
						<h2 id="students-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Spend your time on internships that fit.</h2>
						<p class="mt-4 text-lg leading-8 text-slate-600">Read the full description, closing date, and company details before you create an account. Once you apply, every application stays in one list.</p>
						<ul class="mt-9 space-y-6">
							<li v-for="item in studentFeatures" :key="item.title" class="flex gap-4">
								<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand shadow-xs ring-1 ring-blue-100"><icon :name="item.icon" class="h-5 w-5" aria-hidden="true" /></span>
								<div>
									<h3 class="font-semibold">{{ item.title }}</h3>
									<p class="mt-1 leading-7 text-slate-600">{{ item.text }}</p>
								</div>
							</li>
						</ul>
						<div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
							<Link :href="route('internships.index')" class="cta cta-primary focus-ring">Browse internships</Link>
							<Link v-if="!currentUser" :href="route('register', { account: 'student' })" class="cta cta-secondary focus-ring">Create a student account</Link>
						</div>
					</div>
					<figure v-reveal="120" class="relative">
						<div aria-hidden="true" class="absolute inset-x-[10%] bottom-[8%] top-[18%] rounded-[2.5rem] bg-white shadow-[0_30px_70px_-40px_rgb(37_99_235/0.45)] ring-1 ring-navy/5"></div>
						<img src="/internly-assets/stills/student-workspace.png" alt="Illustration of a student at a desk looking through internships on a laptop" width="1536" height="1024" loading="lazy" decoding="async" class="relative h-auto w-full" />
					</figure>
				</div>
			</section>

			<!-- Companies -->
			<section aria-labelledby="companies-title" class="bg-white">
				<div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:py-28">
					<div v-reveal class="lg:order-2">
						<p class="eyebrow">For companies</p>
						<h2 id="companies-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Publish internships and review applicants in one place.</h2>
						<p class="mt-4 text-lg leading-8 text-slate-600">Set up your company profile once, post roles with a clear closing date, and work through applications without chasing email threads.</p>
						<ul class="mt-9 space-y-6">
							<li v-for="item in companyFeatures" :key="item.title" class="flex gap-4">
								<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-brand ring-1 ring-blue-100"><icon :name="item.icon" class="h-5 w-5" aria-hidden="true" /></span>
								<div>
									<h3 class="font-semibold">{{ item.title }}</h3>
									<p class="mt-1 leading-7 text-slate-600">{{ item.text }}</p>
								</div>
							</li>
						</ul>
						<div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
							<Link v-if="companyAction" :href="companyAction" class="cta cta-primary focus-ring">{{ isCompany ? 'Post an internship' : 'Create a company account' }}</Link>
							<Link :href="route('blog.show', 'how-companies-can-hire-interns-faster')" class="cta cta-secondary focus-ring">Read the hiring guide</Link>
						</div>
					</div>
					<figure v-reveal="120" class="relative lg:order-1">
						<div aria-hidden="true" class="absolute inset-x-[10%] bottom-[8%] top-[18%] rounded-[2.5rem] bg-pale ring-1 ring-navy/5"></div>
						<img src="/internly-assets/stills/company-workspace.png" alt="Illustration of a recruiter at a desk reviewing an application" width="1536" height="1024" loading="lazy" decoding="async" class="relative h-auto w-full" />
					</figure>
				</div>
			</section>

			<!-- Guides -->
			<section aria-labelledby="guides-title" class="border-t border-navy/5 bg-pale">
				<div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-24">
					<div v-reveal class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
						<div class="max-w-2xl">
							<p class="eyebrow">Guides</p>
							<h2 id="guides-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Advice for both sides of an internship.</h2>
							<p class="mt-4 text-lg leading-8 text-slate-600">Short, practical reads on finding internships, writing stronger applications, and hiring interns.</p>
						</div>
						<Link :href="route('blog.index')" class="focus-ring group inline-flex items-center gap-2 self-start rounded-lg font-semibold text-brand hover:text-brand-dark md:self-auto">
							All guides
							<svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h10M9 4l4 4-4 4" /></svg>
						</Link>
					</div>

					<div class="mt-12 grid gap-6 md:grid-cols-3">
						<div v-for="(post, index) in posts" :key="post.slug" v-reveal="index * 80">
							<Link :href="route('blog.show', post.slug)" class="lift focus-ring group flex h-full flex-col rounded-3xl bg-white p-7 shadow-xs ring-1 ring-navy/5 transition duration-200 hover:shadow-lg hover:ring-brand/30">
								<span class="flex items-center justify-between gap-4 text-sm">
									<span class="rounded-full bg-blue-50 px-3 py-1 font-semibold text-brand">{{ post.category }}</span>
									<span class="text-slate-500">{{ post.read_time }}</span>
								</span>
								<span class="mt-5 font-display text-xl font-semibold leading-snug text-navy group-hover:text-brand">{{ post.title }}</span>
								<span class="mt-3 flex-1 leading-7 text-slate-600">{{ post.excerpt }}</span>
								<span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand">
									Read guide
									<svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h10M9 4l4 4-4 4" /></svg>
								</span>
							</Link>
						</div>
					</div>
				</div>
			</section>

			<!-- FAQ -->
			<section aria-labelledby="faq-title" class="bg-white">
				<div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16 lg:py-28">
					<div v-reveal>
						<p class="eyebrow">FAQ</p>
						<h2 id="faq-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Questions, answered.</h2>
						<p class="mt-4 text-lg leading-8 text-slate-600">How Internly works today, for students and companies. Can't find your question?</p>
						<Link :href="route('contact')" class="focus-ring mt-4 inline-flex rounded-lg font-semibold text-brand hover:text-brand-dark">Get in touch</Link>
					</div>
					<div v-reveal="100" class="divide-y divide-navy/5 rounded-3xl bg-pale ring-1 ring-navy/5">
						<details v-for="item in faqs" :key="item.question" class="group">
							<summary class="focus-ring flex cursor-pointer list-none items-center justify-between gap-6 rounded-3xl px-6 py-5 font-semibold text-navy [&::-webkit-details-marker]:hidden">
								{{ item.question }}
								<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-brand ring-1 ring-navy/10 transition-transform duration-200 group-open:rotate-45" aria-hidden="true">
									<svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M8 3v10M3 8h10" /></svg>
								</span>
							</summary>
							<p class="px-6 pb-6 leading-7 text-slate-600">{{ item.answer }}</p>
						</details>
					</div>
				</div>
			</section>

			<!-- Closing call to action -->
			<section aria-labelledby="cta-title" class="bg-white pb-20 lg:pb-28">
				<div class="mx-auto max-w-7xl px-4 sm:px-6">
					<div v-reveal class="grid items-center overflow-hidden rounded-[2rem] bg-pale ring-1 ring-navy/5 lg:grid-cols-2">
						<div class="p-8 sm:p-12 lg:p-14">
							<p class="eyebrow">Ready when you are</p>
							<h2 id="cta-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Your next internship starts with a search.</h2>
							<p class="mt-4 text-lg leading-8 text-slate-600">See what's open right now. Hiring instead? Create a company account and publish your first role.</p>
							<div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
								<Link :href="route('internships.index')" class="cta cta-primary focus-ring">Browse internships</Link>
								<Link v-if="companyAction" :href="companyAction" class="cta cta-secondary focus-ring">Post an internship</Link>
							</div>
						</div>
						<div class="px-4 pb-6 sm:px-8 lg:py-8 lg:pl-0">
							<loop-video name="success" lazy class="rounded-3xl" />
						</div>
					</div>
				</div>
			</section>
		</div>
	</public-layout>
</template>

<script>
import EmptyLayout from '@/Layouts/Empty.vue';
import PublicLayout from '@/Layouts/Public.vue';
import LoopVideo from '@/Shared/LoopVideo.vue';
import reveal from '@/Shared/reveal.js';

export default {
	layout: EmptyLayout,
	components: { PublicLayout, LoopVideo },
	directives: { reveal },
	props: {
		posts: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		// Every line below describes something the app does today. Keep it that way when editing.
		return {
			heroChips: [
				{ icon: 'search', title: 'Explore', text: 'By field, city or company', position: '-left-4 top-[12%] lg:-left-8' },
				{ icon: 'document-text', title: 'Apply', text: 'Cover letter and files', position: '-right-3 top-[44%] lg:-right-6' },
				{ icon: 'mail', title: 'Connect', text: 'Message the company', position: 'left-[8%] -bottom-5' },
			],
			steps: [
				{ icon: 'search', title: 'Explore', text: 'Browse open internships by field, city, or company, or search by title. No account needed.' },
				{ icon: 'document-text', title: 'Apply', text: 'Create a student account and send a cover letter, an optional note, and up to 10 files.' },
				{ icon: 'mail', title: 'Connect', text: 'The company accepts or declines your application and you are notified. You can also message the company from its profile.' },
			],
			studentFeatures: [
				{ icon: 'search', title: 'Filter by what matters', text: 'Narrow the list by field, city, and company, or search internships by title.' },
				{ icon: 'heart', title: 'Save internships for later', text: 'Like a listing to keep it on your shortlist until you are ready to apply.' },
				{ icon: 'clock', title: 'Know where each application stands', text: 'See whether it is under review, accepted, or declined. You can edit it while it is under review.' },
				{ icon: 'mail', title: 'Talk to companies directly', text: 'Send a message from a company profile and keep the conversation on Internly.' },
			],
			companyFeatures: [
				{ icon: 'office-building', title: 'A profile students can check', text: 'Add your logo, website, city, and a short description. It is required before you post.' },
				{ icon: 'briefcase', title: 'Post a role in one form', text: 'Write the description, choose a field, set a closing date, and attach files if you need to.' },
				{ icon: 'document-text', title: 'Every application in one place', text: 'See who applied to each internship, read their cover letters, and open their attachments.' },
				{ icon: 'check-circle', title: 'Reply and follow up', text: 'Accept or decline an application and the student is notified. Each application includes the student’s contact details.' },
			],
			faqs: [
				{ question: 'Do I need an account to look at internships?', answer: 'No. Internship listings and their details are public. You need a student account to apply, save internships, and follow your applications.' },
				{ question: 'What do I need to apply?', answer: 'A cover letter of at least 200 characters. You can add a note for the company and attach up to 10 files, such as your CV or portfolio, each up to 10 MB.' },
				{ question: 'How do I know what happened to my application?', answer: 'Each application shows its status: under review, accepted, or declined. You get a notification when the company replies, and you can edit your application while it is still under review.' },
				{ question: 'How do companies post an internship?', answer: 'Create a company account with your website, logo, city, and a short description. Once your profile is complete, you can publish internships with a description, field, closing date, and optional attachments.' },
				{ question: 'Does Internly verify companies?', answer: 'Not independently. Each listing shows the company’s contact details and website, so you can check who is hiring before you apply.' },
				{ question: 'What happens after an internship’s closing date?', answer: 'It leaves the internship list and stops accepting applications. Companies can still review the applications they already received.' },
			],
		};
	},
	computed: {
		isCompany() {
			return this.currentUser?.userable_type === 'company';
		},
		// Where "Post an internship" leads: company sign-up for visitors, the posting form for companies, hidden for everyone else.
		companyAction() {
			if (!this.currentUser) return route('register', { account: 'company' });
			return this.isCompany ? route('internships.create') : null;
		},
	},
};
</script>
