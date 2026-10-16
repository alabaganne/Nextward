<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const facts = computed(() => [
	{ term: text('What', 'Quoi'), text: text('A job and internship platform: PFE and summer internships, graduate programmes, first jobs and experienced roles.', 'Une plateforme de stages et d’emplois : stages PFE et d’été, programmes jeunes diplômés, premiers emplois et postes expérimentés.') },
	{ term: text('For whom', 'Pour qui'), text: text('Students, recent graduates and professionals in Tunisia, and the employers who hire them.', 'Les étudiants, les jeunes diplômés et les professionnels en Tunisie, et les employeurs qui recrutent.') },
	{ term: text('Where', 'Où'), text: text('Roles in Tunisia, remote roles that accept candidates in Tunisia, and roles abroad with relocation support.', 'Des offres en Tunisie, des postes à distance ouverts aux candidats en Tunisie et des postes à l’étranger avec aide à la mobilité.') },
	{ term: text('Sources', 'Sources'), text: text('Employers post directly. Other listings come from approved public sources, are checked daily, and link to the original page.', 'Les employeurs publient directement. Les autres offres viennent de sources autorisées, sont vérifiées chaque jour et renvoient vers l’annonce d’origine.') },
	{ term: text('Languages', 'Langues'), text: text('French and English.', 'Français et anglais.') },
	{ term: text('Founder', 'Fondateur'), text: text('Ala Baganne, software engineer.', 'Ala Baganne, ingénieur logiciel.') },
]);
const highlights = computed(() => [
	{ art: '06-internship-listing-board', title: text('Public discovery', 'Offres accessibles à tous'), text: text('Internships and jobs are readable without an account, with clear sources and application destinations.', 'Les stages et les emplois se consultent sans compte, avec leur source et le lieu de candidature.') },
	{ art: '02-student-prepare-cv', title: text('Candidate and employer workspaces', 'Espaces candidats et employeurs'), text: text('Students and professionals save and track opportunities; employers publish internships and jobs.', 'Les candidats enregistrent et suivent leurs offres ; les employeurs publient leurs stages et emplois.') },
	{ art: '03-recruiter-clipboard', title: text('Company onboarding', 'Accueil des entreprises'), text: text('Employers introduce themselves before posting. Imported listings identify the source and do not imply an employer account.', 'Les employeurs se présentent avant de publier. Les offres importées indiquent leur source et n’impliquent pas de compte employeur.') },
]);
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="about-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('About Nextward', 'À propos de Nextward') }}</p>
						<h1 id="about-title" class="hero-title">{{ text('Your next opportunity, at every stage of your career.', 'Votre prochaine opportunité, à chaque étape de votre carrière.') }}</h1>
						<p class="hero-lead">{{ text('Nextward helps students and professionals find internships and jobs in Tunisia and worldwide. Browse employer posts and approved public sources, understand the requirements, and apply through Nextward or the original application website.', 'Nextward aide les étudiants et les professionnels à trouver des stages et des emplois en Tunisie et à l’international. Consultez les offres des employeurs et des sources autorisées, comprenez les conditions et postulez sur Nextward ou sur le site d’origine.') }}</p>
						<div class="hero-buttons">
							<Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
							<Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create account', 'Créer un compte') }}</Link>
						</div>
					</div>
					<figure class="hero-stage about-stage">
						<img src="/nextward-assets/mascots/15-student-company-conversation.svg" alt="" width="1100" height="760" fetchpriority="high" />
					</figure>
				</div>
			</section>

			<section class="public-container pt-20" aria-labelledby="facts-title">
				<p class="landing-label">{{ text('Nextward at a glance', 'Nextward en bref') }}</p>
				<h2 id="facts-title" class="section-title">{{ text('What Nextward is', 'Ce qu’est Nextward') }}</h2>
				<dl class="facts mt-10">
					<div v-for="fact in facts" :key="fact.term"><dt>{{ fact.term }}</dt><dd>{{ fact.text }}</dd></div>
				</dl>
			</section>

			<section class="public-container py-20" aria-labelledby="solves-title">
				<p class="landing-label">{{ text('What it solves', 'Ce que Nextward apporte') }}</p>
				<h2 id="solves-title" class="section-title">{{ text('One place for internships, jobs and the people who post them.', 'Un seul endroit pour les stages, les emplois et ceux qui les publient.') }}</h2>
				<ul class="highlights">
					<li v-for="item in highlights" :key="item.title"><img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" /><h3>{{ item.title }}</h3><p>{{ item.text }}</p></li>
				</ul>
			</section>

			<section class="bg-white border-y border-navy/5" :aria-label="text('Sources and privacy', 'Sources et confidentialité')">
				<div class="public-container py-20 grid md:grid-cols-2 gap-14">
					<article>
						<h2 class="text-2xl font-semibold tracking-tight">{{ text('Where opportunities come from', 'D’où viennent les offres') }}</h2>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Employers can publish opportunities directly on Nextward. Imported roles identify their original source and application destination. A source must be approved for the intended reuse before its listings can be published.', 'Les employeurs peuvent publier leurs offres directement sur Nextward. Les offres importées indiquent leur source d’origine et le site où postuler. Une source doit être autorisée avant que ses offres soient publiées.') }}</p>
						<p class="mt-4 text-slate-600 leading-7">{{ text('The last successful check describes when the source was read. It is not an independent employer endorsement. Missing salary, remote eligibility, relocation, or visa details remain unknown rather than becoming a promise.', 'La date du dernier contrôle indique quand la source a été lue. Ce n’est pas une garantie de l’employeur. Un salaire, une éligibilité au télétravail, une aide à la mobilité ou un visa non précisés restent inconnus, jamais promis.') }}</p>
						<Link :href="`${prefix}/opportunities`" class="landing-link mt-5 text-blue-700">{{ text('Explore opportunities and their sources', 'Voir les offres et leurs sources') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
					</article>
					<article>
						<h2 class="text-2xl font-semibold tracking-tight">{{ text('Your applications stay under your control', 'Vos candidatures restent sous votre contrôle') }}</h2>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Opening an external application link does not send your CV or create a submitted application. You choose what to provide on the source website and can separately record that you applied.', 'Ouvrir un lien de candidature externe n’envoie pas votre CV et ne crée pas de candidature. Vous choisissez ce que vous transmettez sur le site d’origine et vous pouvez noter séparément que vous avez postulé.') }}</p>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Application attachments uploaded through Nextward use authorized access for the candidate and the recipient of a native application. Candidate notes and external tracking are private to the candidate. Saved-search emails are optional and include an unsubscribe link.', 'Les pièces jointes envoyées via Nextward ne sont accessibles qu’au candidat et au destinataire de la candidature. Vos notes et votre suivi restent privés. Les alertes e-mail sont facultatives et contiennent un lien de désinscription.') }}</p>
						<Link :href="`${prefix}/contact`" class="landing-link mt-5 text-blue-700">{{ text('Contact us about an opportunity or your information', 'Nous contacter au sujet d’une offre ou de vos données') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
					</article>
				</div>
			</section>

			<section class="public-container py-20 grid lg:grid-cols-[1.4fr_1fr] gap-14" aria-labelledby="builder-title">
				<div>
					<p class="landing-label">{{ text('Built by', 'Créé par') }}</p>
					<h2 id="builder-title" class="section-title">Ala Baganne</h2>
					<p class="mt-6 text-slate-700 leading-8 text-lg">{{ text('I’m a software engineer and builder focused on practical SaaS products, clean user experiences, and reliable full-stack execution. I enjoy taking an idea from rough requirements to a working deployed product: frontend, backend, database, authentication, permissions, deployment, and the final details that make a demo feel real.', 'Je suis ingénieur logiciel et je crée des produits SaaS utiles, simples à utiliser et solides de bout en bout. J’aime transformer une idée en produit en ligne : interface, serveur, base de données, authentification, droits d’accès, déploiement et tous les détails qui comptent.') }}</p>
					<p class="mt-4 text-slate-700 leading-8 text-lg">{{ text('Nextward is part of that builder journey: a working application with public opportunity pages, candidate and employer accounts, company onboarding, application management, file uploads, and messaging.', 'Nextward fait partie de ce parcours : une application complète avec des pages d’offres publiques, des comptes candidats et employeurs, l’accueil des entreprises, la gestion des candidatures, l’envoi de fichiers et la messagerie.') }}</p>
					<p class="mt-4 text-slate-700 leading-8 text-lg">{{ text('I’m open to strong engineering opportunities, especially roles where I can build useful products end to end. If you have a product idea, an MVP to launch, or a business workflow you want turned into software, I’m also open to building for people and helping bring practical ideas to life.', 'Je suis ouvert aux belles opportunités en ingénierie, surtout pour créer des produits utiles de bout en bout. Si vous avez une idée de produit, un MVP à lancer ou un processus à transformer en logiciel, je peux aussi vous aider à le concrétiser.') }}</p>
					<dl class="mt-10 grid sm:grid-cols-3 gap-6 border-t border-navy/10 pt-8">
						<div><dt class="font-display text-xl font-semibold">Full-stack</dt><dd class="mt-2 text-sm text-slate-600">{{ text('Laravel, Vue, databases, auth, deployment', 'Laravel, Vue, bases de données, authentification, déploiement') }}</dd></div>
						<div><dt class="font-display text-xl font-semibold">{{ text('Product-minded', 'Orienté produit') }}</dt><dd class="mt-2 text-sm text-slate-600">{{ text('Focused on useful features and polished demos', 'Des fonctionnalités utiles et soignées') }}</dd></div>
						<div><dt class="font-display text-xl font-semibold">{{ text('Builder', 'Bâtisseur') }}</dt><dd class="mt-2 text-sm text-slate-600">{{ text('Shipping SaaS-style apps end to end', 'Des applications SaaS livrées de bout en bout') }}</dd></div>
					</dl>
				</div>
				<aside class="self-start rounded-2xl bg-navy text-white p-8">
					<h3 class="text-2xl font-semibold">{{ text('Developer contact', 'Contacter le développeur') }}</h3>
					<ul class="mt-6 divide-y divide-white/10 text-blue-100">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring block py-3 hover:text-white break-all">alabaganne9@gmail.com</a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" class="focus-ring block py-3 hover:text-white">www.alabaganne.com</a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" class="focus-ring block py-3 hover:text-white">LinkedIn / alabaganne</a></li>
					</ul>
					<p class="mt-6 text-slate-300 leading-7">{{ text('If you’re hiring for full-stack, frontend, backend, or product-focused engineering roles, or you want to build a product from an idea, I’d be glad to connect.', 'Si vous recrutez pour un poste full-stack, frontend, backend ou produit, ou si vous voulez créer un produit à partir d’une idée, échangeons.') }}</p>
				</aside>
			</section>
		</div>
	</PublicLayout>
</template>

<style scoped>
.about-stage::before { display: none; }
.highlights { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 40px; }
.highlights img { width: 112px; height: 112px; margin-left: -12px; }
.highlights h3 { margin-top: 12px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.highlights p { margin-top: 8px; font-size: 14px; line-height: 1.75; color: #586171; }
.facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; overflow: hidden; border: 1px solid #0a17381a; border-radius: 16px; background: #0a17381a; }
.facts > div { background: #fff; padding: 22px 24px; }
.facts dt { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand); }
.facts dd { margin-top: 8px; font-size: 15px; line-height: 1.7; color: #334155; }
@media (max-width: 767px) {
	.facts { grid-template-columns: 1fr; }
	.highlights { grid-template-columns: 1fr; gap: 28px; }
}
</style>
