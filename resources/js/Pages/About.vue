<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, Check, Sparkles } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const facts = computed(() => [
	{ term: text('What', 'Quoi'), text: text('A job and internship platform: PFE and summer internships, graduate programmes, first jobs and experienced roles.', 'Une plateforme de stages et d’emplois : stages PFE et d’été, programmes jeunes diplômés, premiers emplois et postes expérimentés.') },
	{ term: text('For whom', 'Pour qui'), text: text('Everyone looking for work, from students to experienced people, and the employers who hire them.', 'Toutes les personnes qui cherchent un poste, des étudiants aux profils expérimentés, et les employeurs qui recrutent.') },
	{ term: text('Where', 'Où'), text: text('Roles in Tunisia, remote roles that accept candidates in Tunisia, and roles abroad with relocation support.', 'Des offres en Tunisie, des postes à distance ouverts aux candidats en Tunisie et des postes à l’étranger avec aide à la mobilité.') },
	{ term: text('Sources', 'Sources'), text: text('Employers post directly. Other listings come from approved public sources, are checked daily, and link to the original page.', 'Les employeurs publient directement. Les autres offres viennent de sources autorisées, sont vérifiées chaque jour et renvoient vers l’annonce d’origine.') },
	{ term: text('Languages', 'Langues'), text: text('Every page and every listing in French and English, with the original text kept.', 'Chaque page et chaque offre en français et en anglais, avec le texte d’origine conservé.') },
	{ term: text('Founder', 'Fondateur'), text: text('Ala Baganne, software engineer.', 'Ala Baganne, ingénieur logiciel.') },
]);
const mission = computed(() => [
	{ art: '06-internship-listing-board', title: text('Every opportunity in one place', 'Toutes les offres au même endroit'), text: text('Internships and jobs from employers and approved sources, readable by anyone without an account.', 'Des stages et des emplois d’employeurs et de sources autorisées, lisibles par tous sans compte.') },
	{ art: '12-wand-and-stars', title: text('No language barrier', 'Pas de barrière de langue'), text: text('Each listing in French and English, so you never skip a role because of the language it was posted in.', 'Chaque offre en français et en anglais, pour ne jamais passer à côté d’un poste à cause de sa langue.') },
	{ art: '02-student-prepare-cv', title: text('Help to apply well', 'De l’aide pour bien candidater'), text: text('Clear requirements, honest unknowns, and a CV and cover letter tailored to each role.', 'Des critères clairs, des inconnues assumées, et un CV et une lettre adaptés à chaque offre.') },
]);
const free = computed(() => [
	text('Search and read every internship and job', 'Rechercher et lire tous les stages et emplois'),
	text('Read each listing in French or English', 'Lire chaque offre en français ou en anglais'),
	text('Save roles and searches, with email alerts', 'Sauvegarder des offres et des recherches, avec alertes e-mail'),
	text('Apply on Nextward and message companies', 'Candidater sur Nextward et écrire aux entreprises'),
	text('Track applications made on other websites', 'Suivre les candidatures faites sur d’autres sites'),
	text('Post internships and jobs as an employer', 'Publier des stages et des emplois en tant qu’employeur'),
]);
const tools = computed(() => [
	{ title: text('Fill your profile from your resume', 'Remplir votre profil depuis votre CV'), text: text('Upload your resume and we suggest your headline, experience, education, skills and links. You tick what to keep and save it yourself.', 'Ajoutez votre CV et nous proposons votre titre, vos expériences, formations, compétences et liens. Vous cochez ce que vous gardez et enregistrez vous-même.') },
	{ title: text('A CV and cover letter for each role', 'Un CV et une lettre pour chaque offre'), text: text('On any listing, get a CV that puts forward what fits the role, and a cover letter written for it, in English or French. Edit both, then download them or use them to apply.', 'Sur chaque offre, obtenez un CV qui met en avant ce qui correspond au poste, et une lettre écrite pour lui, en français ou en anglais. Modifiez-les, puis téléchargez-les ou utilisez-les pour candidater.') },
]);
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="about-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('About Nextward', 'À propos de Nextward') }}</p>
						<h1 id="about-title" class="hero-title">{{ text('Every opportunity,', 'Chaque opportunité,') }} <span class="accent">{{ text('open to everyone.', 'ouverte à tous.') }}</span></h1>
						<p class="hero-lead">{{ text('Nextward helps people find internships and jobs in Tunisia and worldwide, read them in French or English, and apply with a CV and cover letter that fit the role.', 'Nextward aide chacun à trouver des stages et des emplois en Tunisie et dans le monde, à les lire en français ou en anglais, et à postuler avec un CV et une lettre adaptés au poste.') }}</p>
						<div class="hero-buttons">
							<Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
							<Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
						</div>
					</div>
					<figure class="hero-stage is-flat about-stage">
						<img src="/nextward-assets/mascots/15-student-company-conversation.svg" alt="" width="1100" height="760" fetchpriority="high" />
					</figure>
				</div>
			</section>

			<section class="public-container py-20" aria-labelledby="mission-title">
				<p class="landing-label">{{ text('Our mission', 'Notre mission') }}</p>
				<h2 id="mission-title" class="section-title">{{ text('Finding work should not depend on who you know.', 'Trouver un poste ne devrait pas dépendre de qui vous connaissez.') }}</h2>
				<p class="section-description max-w-3xl">{{ text('Good roles are spread across company sites and job boards, in several languages, with key details left out. Nextward brings them together, says plainly what each one asks for, and helps every candidate apply well, whether it is their first internship or their next senior role.', 'Les bonnes offres sont dispersées entre sites d’entreprises et job boards, en plusieurs langues, souvent sans les détails importants. Nextward les réunit, dit clairement ce que chacune demande et aide chaque candidat à bien postuler, pour un premier stage comme pour un poste senior.') }}</p>
				<ul class="highlights">
					<li v-for="item in mission" :key="item.title"><img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" class="section-art" /><h3>{{ item.title }}</h3><p>{{ item.text }}</p></li>
				</ul>
			</section>

			<section class="band-pale" aria-labelledby="free-title">
				<div class="public-container py-20 grid lg:grid-cols-2 gap-14">
					<div>
						<p class="landing-label">{{ text('Free for everyone', 'Gratuit pour tous') }}</p>
						<h2 id="free-title" class="section-title">{{ text('Finding and applying is free, and stays free.', 'Chercher et candidater est gratuit, et le restera.') }}</h2>
						<ul class="free-list">
							<li v-for="item in free" :key="item"><Check class="free-icon" aria-hidden="true" />{{ item }}</li>
						</ul>
					</div>
					<div id="ai-tools" class="scroll-mt-28">
						<p class="landing-label inline-flex items-center gap-2"><Sparkles class="size-4" aria-hidden="true" />{{ text('AI tools', 'Outils d’IA') }}</p>
						<h2 class="section-title">{{ text('Free while we launch.', 'Gratuits pendant le lancement.') }}</h2>
						<div class="mt-8 grid gap-4">
							<article v-for="tool in tools" :key="tool.title" class="panel p-6"><h3 class="font-display text-lg font-semibold">{{ tool.title }}</h3><p class="mt-2 text-slate-600 leading-7">{{ tool.text }}</p></article>
						</div>
						<div class="mt-4 rounded-2xl bg-[#eef4ff] p-6">
							<h3 class="font-display text-lg font-semibold">{{ text('What will change later', 'Ce qui changera plus tard') }}</h3>
							<p class="mt-2 text-slate-700 leading-7">{{ text('The tailored CV and cover letter will become a paid feature, sold as credit packs. One credit covers one role, with a few rewrites included, and every account gets one free credit. We will show prices before anything is charged. Filling your profile from your resume and everything above stay free.', 'Le CV et la lettre sur mesure deviendront payants, sous forme de packs de crédits. Un crédit couvre une offre, avec quelques réécritures incluses, et chaque compte reçoit un crédit gratuit. Les prix seront affichés avant tout paiement. Remplir votre profil depuis votre CV et tout ce qui est à gauche restent gratuits.') }}</p>
							<p class="mt-3 text-sm text-slate-600 leading-6">{{ text('The AI only uses facts from your profile and resume, and you check everything before you use it.', 'L’IA n’utilise que les faits de votre profil et de votre CV, et vous vérifiez tout avant de l’utiliser.') }} <Link :href="`${prefix}/privacy#ai`" class="font-semibold text-brand underline underline-offset-4 focus-ring">{{ text('How we handle your data', 'Comment nous traitons vos données') }}</Link></p>
						</div>
					</div>
				</div>
			</section>

			<section class="public-container py-20" aria-labelledby="facts-title">
				<p class="landing-label">{{ text('Nextward at a glance', 'Nextward en bref') }}</p>
				<h2 id="facts-title" class="section-title">{{ text('What Nextward is', 'Ce qu’est Nextward') }}</h2>
				<dl class="facts mt-10">
					<div v-for="fact in facts" :key="fact.term"><dt>{{ fact.term }}</dt><dd>{{ fact.text }}</dd></div>
				</dl>
			</section>

			<section class="band-pale" :aria-label="text('Sources and privacy', 'Sources et confidentialité')">
				<div class="public-container py-20 grid md:grid-cols-2 gap-14">
					<article>
						<h2 class="font-display text-2xl font-semibold tracking-tight">{{ text('Where opportunities come from', 'D’où viennent les offres') }}</h2>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Employers can publish opportunities directly on Nextward. Imported roles identify their original source and application destination. A source must be approved for the intended reuse before its listings can be published.', 'Les employeurs peuvent publier leurs offres directement sur Nextward. Les offres importées indiquent leur source d’origine et le site où postuler. Une source doit être autorisée avant que ses offres soient publiées.') }}</p>
						<p class="mt-4 text-slate-600 leading-7">{{ text('The last successful check describes when the source was read. It is not an independent employer endorsement. Missing salary, remote eligibility, relocation, or visa details remain unknown rather than becoming a promise.', 'La date du dernier contrôle indique quand la source a été lue. Ce n’est pas une garantie de l’employeur. Un salaire, une éligibilité au télétravail, une aide à la mobilité ou un visa non précisés restent inconnus, jamais promis.') }}</p>
						<Link :href="`${prefix}/opportunities`" class="landing-link focus-ring mt-5">{{ text('Explore opportunities and their sources', 'Voir les offres et leurs sources') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
					</article>
					<article>
						<h2 class="font-display text-2xl font-semibold tracking-tight">{{ text('Your applications stay under your control', 'Vos candidatures restent sous votre contrôle') }}</h2>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Opening an external application link does not send your CV or create a submitted application. You choose what to provide on the source website and can separately record that you applied.', 'Ouvrir un lien de candidature externe n’envoie pas votre CV et ne crée pas de candidature. Vous choisissez ce que vous transmettez sur le site d’origine et vous pouvez noter séparément que vous avez postulé.') }}</p>
						<p class="mt-4 text-slate-600 leading-7">{{ text('Application attachments uploaded through Nextward use authorized access for the candidate and the recipient of a native application. Candidate notes and external tracking are private to the candidate. Saved-search emails are optional and include an unsubscribe link.', 'Les pièces jointes envoyées via Nextward ne sont accessibles qu’au candidat et au destinataire de la candidature. Vos notes et votre suivi restent privés. Les alertes e-mail sont facultatives et contiennent un lien de désinscription.') }}</p>
						<Link :href="`${prefix}/contact`" class="landing-link focus-ring mt-5">{{ text('Contact us about an opportunity or your information', 'Nous contacter au sujet d’une offre ou de vos données') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
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
					<dl class="mt-10 grid sm:grid-cols-3 gap-6 border-t border-line pt-8">
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
.highlights { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 40px; }
.about-stage { width: min(100%, 520px); }
.highlights h3 { margin-top: 12px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.highlights p { margin-top: 8px; font-size: 14px; line-height: 1.75; color: #586171; }
.free-list { display: grid; gap: 14px; margin-top: 28px; }
.free-list li { display: flex; gap: 12px; align-items: flex-start; color: #334155; font-size: 16px; line-height: 1.6; }
.free-icon { flex-shrink: 0; width: 20px; height: 20px; margin-top: 2px; color: var(--color-brand); }
.facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; overflow: hidden; border: 1px solid var(--color-line); border-radius: 16px; background: var(--color-line); }
.facts > div { background: #fff; padding: 22px 24px; }
.facts dt { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand); }
.facts dd { margin-top: 8px; font-size: 15px; line-height: 1.7; color: #334155; }
@media (max-width: 767px) {
	.facts { grid-template-columns: 1fr; }
	.highlights { grid-template-columns: 1fr; gap: 28px; }
}
</style>
