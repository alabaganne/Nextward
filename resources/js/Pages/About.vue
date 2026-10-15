<template>
	<public-layout>
		<div class="landing">
			<section aria-labelledby="about-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>About Nextward</p>
						<h1 id="about-title" class="hero-title">Your next opportunity, at every stage of your career.</h1>
						<p class="hero-lead">Nextward helps students and professionals find internships and jobs in Tunisia and worldwide. Browse employer posts and approved public sources, understand the requirements, and apply through Nextward or the original application website.</p>
						<div class="hero-buttons">
							<Link :href="route('opportunities.index')" class="cta cta-primary focus-ring">Browse opportunities<icon name="arrow-up-right" class="action-icon" /></Link>
							<Link :href="route('register')" class="cta cta-ghost focus-ring">Create account</Link>
						</div>
					</div>
					<figure class="hero-stage about-stage">
						<img src="/nextward-assets/mascots/15-student-company-conversation.svg" alt="" width="1100" height="760" fetchpriority="high" />
					</figure>
				</div>
			</section>

			<section class="public-container py-20" aria-labelledby="solves-title">
				<p class="landing-label">What it solves</p>
				<h2 id="solves-title" class="section-title">One place for internships, jobs and the people who post them.</h2>
				<ul class="highlights">
					<li v-for="item in highlights" :key="item.title"><img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" /><h3>{{ item.title }}</h3><p>{{ item.text }}</p></li>
				</ul>
			</section>

			<section class="bg-white border-y border-navy/5" aria-label="Sources and privacy">
				<div class="public-container py-20 grid md:grid-cols-2 gap-14">
					<article>
						<h2 class="text-2xl font-semibold tracking-tight">Where opportunities come from</h2>
						<p class="mt-4 text-slate-600 leading-7">Employers can publish opportunities directly on Nextward. Imported roles identify their original source and application destination. A source must be approved for the intended reuse before its listings can be published.</p>
						<p class="mt-4 text-slate-600 leading-7">The last successful check describes when the source was read. It is not an independent employer endorsement. Missing salary, remote eligibility, relocation, or visa details remain unknown rather than becoming a promise.</p>
						<Link href="/opportunities" class="landing-link mt-5 text-blue-700">Explore opportunities and their sources<icon name="arrow-up-right" class="action-icon" /></Link>
					</article>
					<article>
						<h2 class="text-2xl font-semibold tracking-tight">Your applications stay under your control</h2>
						<p class="mt-4 text-slate-600 leading-7">Opening an external application link does not send your CV or create a submitted application. You choose what to provide on the source website and can separately record that you applied.</p>
						<p class="mt-4 text-slate-600 leading-7">Application attachments uploaded through Nextward use authorized access for the candidate and the recipient of a native application. Candidate notes and external tracking are private to the candidate. Saved-search emails are optional and include an unsubscribe link.</p>
						<Link href="/contact" class="landing-link mt-5 text-blue-700">Contact us about an opportunity or your information<icon name="arrow-up-right" class="action-icon" /></Link>
					</article>
				</div>
			</section>

			<section class="public-container py-20 grid lg:grid-cols-[1.4fr_1fr] gap-14" aria-labelledby="builder-title">
				<div>
					<p class="landing-label">Built by</p>
					<h2 id="builder-title" class="section-title">Ala Baganne</h2>
					<p class="mt-6 text-slate-700 leading-8 text-lg">I’m a software engineer and builder focused on practical SaaS products, clean user experiences, and reliable full-stack execution. I enjoy taking an idea from rough requirements to a working deployed product: frontend, backend, database, authentication, permissions, deployment, and the final details that make a demo feel real.</p>
					<p class="mt-4 text-slate-700 leading-8 text-lg">Nextward is part of that builder journey: a working application with public opportunity pages, candidate and employer accounts, company onboarding, application management, file uploads, and messaging.</p>
					<p class="mt-4 text-slate-700 leading-8 text-lg">I’m open to strong engineering opportunities, especially roles where I can build useful products end to end. If you have a product idea, an MVP to launch, or a business workflow you want turned into software, I’m also open to building for people and helping bring practical ideas to life.</p>
					<dl class="mt-10 grid sm:grid-cols-3 gap-6 border-t border-navy/10 pt-8">
						<div><dt class="font-display text-xl font-semibold">Full-stack</dt><dd class="mt-2 text-sm text-slate-600">Laravel, Vue, databases, auth, deployment</dd></div>
						<div><dt class="font-display text-xl font-semibold">Product-minded</dt><dd class="mt-2 text-sm text-slate-600">Focused on useful features and polished demos</dd></div>
						<div><dt class="font-display text-xl font-semibold">Builder</dt><dd class="mt-2 text-sm text-slate-600">Shipping SaaS-style apps end to end</dd></div>
					</dl>
				</div>
				<aside class="self-start rounded-2xl bg-navy text-white p-8">
					<h3 class="text-2xl font-semibold">Developer contact</h3>
					<ul class="mt-6 divide-y divide-white/10 text-blue-100">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring block py-3 hover:text-white break-all">alabaganne9@gmail.com</a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" class="focus-ring block py-3 hover:text-white">www.alabaganne.com</a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="focus-ring block py-3 hover:text-white">LinkedIn / alabaganne</a></li>
					</ul>
					<p class="mt-6 text-slate-300 leading-7">If you’re hiring for full-stack, frontend, backend, or product-focused engineering roles, or you want to build a product from an idea, I’d be glad to connect.</p>
				</aside>
			</section>
		</div>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'

export default {
	components: { PublicLayout },
	data() {
		return {
			highlights: [
				{ art: '06-internship-listing-board', title: 'Public discovery', text: 'Internships and jobs are readable without an account, with clear sources and application destinations.' },
				{ art: '02-student-prepare-cv', title: 'Candidate and employer workspaces', text: 'Students and professionals save and track opportunities; employers publish internships and jobs.' },
				{ art: '03-recruiter-clipboard', title: 'Company onboarding', text: 'Employers introduce themselves before posting. Imported listings identify the source and do not imply an employer account.' },
			]
		}
	}
}
</script>

<style scoped>
.about-stage::before { display: none; }
.highlights { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 40px; }
.highlights img { width: 112px; height: 112px; margin-left: -12px; }
.highlights h3 { margin-top: 12px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.highlights p { margin-top: 8px; font-size: 14px; line-height: 1.75; color: #586171; }
@media (max-width: 767px) {
	.highlights { grid-template-columns: 1fr; gap: 28px; }
}
</style>
