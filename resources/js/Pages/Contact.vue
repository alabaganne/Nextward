<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import Sparkle from '@/Shared/Sparkle.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="contact-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>Contact</p>
						<h1 id="contact-title" class="hero-title">{{ text('Let’s talk about your next opportunity.', 'Parlons de votre prochaine opportunité.') }}</h1>
						<p class="hero-lead">{{ text('Whether you’re a student, professional, employer, source partner, or recruiter, you can reach the developer directly using the links below.', 'Étudiant, professionnel, employeur, partenaire ou recruteur : vous pouvez joindre directement le développeur avec les liens ci-dessous.') }}</p>
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
					<h2 class="section-title !mt-0">{{ text('Get in touch', 'Nous écrire') }}</h2>
					<p class="mt-5 text-slate-600 leading-7">{{ text('To request a listing correction or removal, include its URL and explain which information should change. Source owners can also contact us about permission, attribution, or approved feeds. Please do not send passwords or CV attachments with a general support message.', 'Pour corriger ou retirer une offre, indiquez son lien et ce qui doit changer. Les propriétaires de sources peuvent aussi nous écrire au sujet des autorisations, de l’attribution ou des flux. N’envoyez jamais de mot de passe ni de CV dans un message général.') }}</p>
					<p class="mt-3 text-slate-600 leading-7">{{ text('For employer posts, source partnerships, product feedback, or problems with a listing, use the contact links below. Please include the opportunity URL when reporting an issue.', 'Pour publier une offre, proposer un partenariat, donner votre avis ou signaler un problème, utilisez les liens ci-dessous. Indiquez le lien de l’offre concernée.') }}</p>
					<ul class="contact-links">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring"><Icon name="mail" /><span><small>Email</small>alabaganne9@gmail.com</span></a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" class="focus-ring"><Icon name="link" /><span><small>Portfolio</small>www.alabaganne.com</span></a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="focus-ring"><Icon name="user-circle" /><span><small>LinkedIn</small>linkedin.com/in/alabaganne</span></a></li>
						<li><Link :href="`${prefix}/opportunities`" class="focus-ring"><Icon name="search" /><span><small>{{ text('Explore the product', 'Découvrir le produit') }}</small>{{ text('Browse opportunities', 'Voir les offres') }}</span></Link></li>
					</ul>
				</div>
				<aside class="self-start rounded-2xl bg-white border border-line p-8">
					<h3 class="text-2xl font-semibold">{{ text('About the developer', 'À propos du développeur') }}</h3>
					<p class="mt-4 text-slate-600 leading-7">{{ text('Ala Baganne is a software engineer and SaaS-focused builder who likes shipping practical full-stack products with clean interfaces, solid auth, useful role permissions, and real deployment polish. He is open to good engineering opportunities and to helping people turn product ideas into working software.', 'Ala Baganne est ingénieur logiciel. Il crée des produits SaaS complets, simples à utiliser et prêts pour la production. Il est ouvert aux opportunités en ingénierie et aide à transformer des idées en logiciels.') }}</p>
					<dl class="mt-8 divide-y divide-navy/10 text-sm">
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">{{ text('Focus', 'Spécialité') }}</dt><dd class="font-semibold">{{ text('Full-stack web', 'Web full-stack') }}</dd></div>
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">{{ text('Product type', 'Type de produit') }}</dt><dd class="font-semibold">{{ text('SaaS apps', 'Applications SaaS') }}</dd></div>
						<div class="flex items-center justify-between py-3"><dt class="text-slate-500">{{ text('Available for', 'Disponible pour') }}</dt><dd class="font-semibold">{{ text('Roles & product builds', 'Postes et projets') }}</dd></div>
					</dl>
				</aside>
			</section>
		</div>
	</PublicLayout>
</template>

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
