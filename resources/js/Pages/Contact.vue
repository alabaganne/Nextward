<template>
	<public-layout>
		<div class="landing">
			<section aria-labelledby="contact-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>Contact</p>
						<h1 id="contact-title" class="hero-title">Let’s talk about your next opportunity.</h1>
						<p class="hero-lead">Whether you’re a student, professional, employer, source partner, or recruiter, you can reach the developer directly using the links below.</p>
					</div>
					<figure class="hero-stage contact-stage">
						<img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 14%; right: 10%; --i: 0" />
						<Sparkle class="is-sky" style="top: 30%; left: 12%; width: 14px; height: 14px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-20 grid lg:grid-cols-[1.4fr_1fr] gap-14">
				<div>
					<h2 class="section-title !mt-0">Get in touch</h2>
					<p class="mt-5 text-slate-600 leading-7">To request a listing correction or removal, include its URL and explain which information should change. Source owners can also contact us about permission, attribution, or approved feeds. Please do not send passwords or CV attachments with a general support message.</p>
					<p class="mt-3 text-slate-600 leading-7">For employer posts, source partnerships, product feedback, or problems with a listing, use the contact links below. Please include the opportunity URL when reporting an issue.</p>
					<ul class="contact-links">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring"><icon name="mail" /><span><small>Email</small>alabaganne9@gmail.com</span></a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" class="focus-ring"><icon name="link" /><span><small>Portfolio</small>www.alabaganne.com</span></a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="focus-ring"><icon name="user-circle" /><span><small>LinkedIn</small>linkedin.com/in/alabaganne</span></a></li>
						<li><Link :href="route('opportunities.index')" class="focus-ring"><icon name="search" /><span><small>Explore the product</small>Browse opportunities</span></Link></li>
					</ul>
				</div>
				<aside class="self-start rounded-2xl bg-white border border-line p-8">
					<h3 class="text-2xl font-semibold">About the developer</h3>
					<p class="mt-4 text-slate-600 leading-7">Ala Baganne is a software engineer and SaaS-focused builder who likes shipping practical full-stack products with clean interfaces, solid auth, useful role permissions, and real deployment polish. He is open to good engineering opportunities and to helping people turn product ideas into working software.</p>
					<dl class="mt-8 divide-y divide-navy/10 text-sm">
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">Focus</dt><dd class="font-semibold">Full-stack web</dd></div>
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">Product type</dt><dd class="font-semibold">SaaS apps</dd></div>
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">Available for</dt><dd class="font-semibold">Roles & product builds</dd></div>
					</dl>
				</aside>
			</section>
		</div>
	</public-layout>
</template>

<script>
import PublicLayout from '@/Layouts/Public.vue'
import Sparkle from '@/Shared/Sparkle.vue'

export default {
	components: { PublicLayout, Sparkle }
}
</script>

<style scoped>
.contact-stage { width: min(100%, 340px); justify-self: center; }
.contact-links { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 32px; }
.contact-links a { display: flex; align-items: center; gap: 14px; height: 100%; padding: 18px 20px; border: 1px solid var(--color-line); border-radius: 14px; background: #fff; font-weight: 600; transition: border-color 180ms, color 180ms; overflow-wrap: anywhere; }
.contact-links a:hover { border-color: #8ab2ff; color: var(--color-brand-dark); }
.contact-links svg { width: 22px; height: 22px; flex-shrink: 0; color: var(--color-brand); }
.contact-links small { display: block; margin-bottom: 2px; color: #67707e; font-size: 12px; font-weight: 500; }
@media (max-width: 639px) {
	.contact-links { grid-template-columns: 1fr; }
}
</style>
