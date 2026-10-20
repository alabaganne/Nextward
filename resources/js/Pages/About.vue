<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight, Check, Sparkles } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const mission = computed(() => [
	{ art: '06-internship-listing-board', title: text('Every IT role in one place', 'Toutes les offres IT au même endroit'), text: text('Internships and jobs in software, data and AI, DevOps, security, networks, QA, UI/UX and more, for students and professionals around the world.', 'Des stages et des emplois en logiciel, data et IA, DevOps, sécurité, réseaux, QA, UI/UX et plus, pour étudiants et professionnels partout dans le monde.') },
	{ art: '12-wand-and-stars', title: text('In English and French', 'En français et en anglais'), text: text('Every page and listing in both languages, with the employer’s original text always kept.', 'Chaque page et chaque offre dans les deux langues, avec le texte d’origine de l’employeur conservé.') },
	{ art: '02-student-prepare-cv', title: text('Help to apply well', 'De l’aide pour bien candidater'), text: text('Clear requirements, honest unknowns, and a CV and cover letter that fit the role.', 'Des critères clairs, des inconnues assumées, et un CV et une lettre adaptés au poste.') },
]);
const tools = computed(() => [
	text('Fill your profile from your resume, then check every detail.', 'Remplissez votre profil depuis votre CV, puis vérifiez chaque détail.'),
	text('Get a CV and a cover letter written for any role, in English or French.', 'Obtenez un CV et une lettre écrits pour chaque offre, en français ou en anglais.'),
	text('Edit both, then download them or use them to apply.', 'Modifiez-les, puis téléchargez-les ou utilisez-les pour candidater.'),
]);
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="about-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('About Nextward', 'À propos de Nextward') }}</p>
						<h1 id="about-title" class="hero-title">{{ text('IT careers,', 'Les carrières IT,') }} <span class="accent">{{ text('made clearer.', 'en plus clair.') }}</span></h1>
						<p class="hero-lead">{{ text('Nextward brings IT internships and jobs from around the world into one place, says plainly what each role asks for, and helps you apply well.', 'Nextward réunit les stages et emplois IT du monde entier, dit clairement ce que chaque offre demande et vous aide à bien candidater.') }}</p>
						<div class="hero-buttons">
							<Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
							<Link :href="`${prefix}/companies`" class="cta cta-ghost focus-ring">{{ text('See companies', 'Voir les entreprises') }}</Link>
						</div>
					</div>
					<figure class="hero-stage is-flat about-stage">
						<img src="/nextward-assets/mascots/15-student-company-conversation.svg" alt="" width="1100" height="760" fetchpriority="high" />
					</figure>
				</div>
			</section>

			<section class="public-container section" aria-labelledby="mission-title">
				<div class="max-w-3xl">
					<p class="landing-label">{{ text('Our mission', 'Notre mission') }}</p>
					<h2 id="mission-title" class="section-title">{{ text('Finding work should not depend on who you know.', 'Trouver un poste ne devrait pas dépendre de qui vous connaissez.') }}</h2>
					<p class="section-description">{{ text('Good IT roles are spread across company sites and job boards, in several languages, often without the details that matter. Nextward brings them together so every candidate gets the same fair start.', 'Les bonnes offres IT sont dispersées entre sites d’entreprises et job boards, en plusieurs langues, souvent sans les détails importants. Nextward les réunit pour que chaque candidat parte avec les mêmes chances.') }}</p>
				</div>
				<ul class="highlights">
					<li v-for="item in mission" :key="item.title">
						<img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" class="highlight-art" />
						<h3>{{ item.title }}</h3>
						<p>{{ item.text }}</p>
					</li>
				</ul>
				<p class="sources-note">{{ text('Employers post on Nextward, and the rest come from approved public sources. Each listing links to the original and shows when it was last checked.', 'Les employeurs publient sur Nextward, les autres offres viennent de sources publiques autorisées. Chaque offre renvoie vers l’original et indique son dernier contrôle.') }}</p>
			</section>

			<section id="ai-tools" class="public-container section tools" aria-labelledby="tools-title">
				<img src="/nextward-assets/mascots/14-prepare-application.svg" alt="" width="768" height="768" loading="lazy" class="tools-art" />
				<div>
					<p class="landing-label inline-flex items-center gap-2"><Sparkles class="size-4" aria-hidden="true" />{{ text('Free to use', 'Gratuit') }}</p>
					<h2 id="tools-title" class="section-title">{{ text('Free to use, with AI help when you apply.', 'Gratuit, avec l’aide de l’IA pour candidater.') }}</h2>
					<p class="section-description">{{ text('Searching, saving, applying, alerts and tracking cost nothing. The AI tools are free while we launch.', 'Chercher, sauvegarder, candidater, recevoir des alertes et suivre vos candidatures ne coûte rien. Les outils d’IA sont gratuits pendant le lancement.') }}</p>
					<ul class="tool-list">
						<li v-for="tool in tools" :key="tool"><Check class="tool-icon" aria-hidden="true" />{{ tool }}</li>
					</ul>
					<p class="mt-6 text-sm text-slate-600 leading-6">{{ text('The AI only uses facts from your own profile and resume.', 'L’IA n’utilise que les faits de votre profil et de votre CV.') }} <Link :href="`${prefix}/privacy#ai`" class="font-semibold text-brand underline underline-offset-4 focus-ring">{{ text('How we handle your data', 'Comment nous traitons vos données') }}</Link></p>
				</div>
			</section>

			<section class="public-container section" aria-labelledby="builder-title">
				<div class="builder">
					<div>
						<p class="landing-label">{{ text('Built by', 'Créé par') }}</p>
						<h2 id="builder-title" class="section-title">Ala Baganne</h2>
						<p class="builder-copy">{{ text('I’m a software engineer who builds full-stack products end to end, from the first idea to a working product online. I built Nextward and run it day to day.', 'Je suis ingénieur logiciel et je crée des produits complets, de l’idée au produit en ligne. J’ai créé Nextward et je le fais vivre au quotidien.') }}</p>
						<p class="builder-copy">{{ text('I’m open to engineering roles and to building products for others. Feel free to get in touch.', 'Je suis ouvert aux postes en ingénierie et à la création de produits pour d’autres. N’hésitez pas à me contacter.') }}</p>
					</div>
					<ul class="builder-links">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring">alabaganne9@gmail.com<ArrowUpRight aria-hidden="true" /></a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" rel="noopener" class="focus-ring">alabaganne.com<ArrowUpRight aria-hidden="true" /></a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" rel="noopener" class="focus-ring">LinkedIn<ArrowUpRight aria-hidden="true" /></a></li>
					</ul>
				</div>
			</section>

			<ClosingBanner mascot="16-first-day-celebration" :title="text('Your next IT role starts here.', 'Votre prochain poste IT commence ici.')" :body="text('Browse open internships and jobs, or create a free account to save roles and follow every application.', 'Consultez les stages et emplois ouverts, ou créez un compte gratuit pour sauvegarder des offres et suivre vos candidatures.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}</Link>
				<Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>

<style scoped>
.about-stage { width: min(100%, 520px); }
.section + .section { padding-top: 40px; }
.highlights { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-top: 44px; }
.highlights li { padding: 28px; border-radius: 18px; background: #f6f8fc; }
.highlight-art { width: 88px; height: 88px; }
.sources-note { max-width: 720px; margin-top: 28px; color: #586171; font-size: 15px; line-height: 1.7; }
.highlights h3 { margin-top: 16px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.highlights p { margin-top: 8px; color: #586171; font-size: 15px; line-height: 1.7; }
.tools { display: grid; grid-template-columns: 260px minmax(0, 1fr); align-items: center; gap: 56px; scroll-margin-top: 100px; }
.tools-art { width: 260px; height: 260px; }
.tool-list { display: grid; gap: 12px; margin-top: 24px; }
.tool-list li { display: flex; align-items: flex-start; gap: 12px; color: #334155; font-size: 16px; line-height: 1.6; }
.tool-icon { flex-shrink: 0; width: 20px; height: 20px; margin-top: 2px; color: var(--color-brand); }
.builder { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); align-items: center; gap: 48px; padding: 48px; border-radius: 22px; background: #0d2353; color: #fff; }
.builder .landing-label { color: #b9d0ff; }
.builder .section-title { color: #fff; }
.builder-copy { margin-top: 16px; max-width: 560px; color: #c5cddc; font-size: 17px; line-height: 1.75; }
.builder-links { display: grid; gap: 10px; }
.builder-links a { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-radius: 14px; background: rgb(255 255 255 / .08); color: #fff; font-weight: 600; word-break: break-all; transition: background 180ms; }
.builder-links a:hover { background: rgb(255 255 255 / .14); }
.builder-links svg { flex-shrink: 0; width: 18px; height: 18px; color: #b9d0ff; }
.builder .focus-ring:focus-visible { outline-color: #fff; }
@media (max-width: 1023px) {
	.tools { grid-template-columns: 1fr; gap: 24px; }
	.tools-art { width: 160px; height: 160px; }
	.builder { grid-template-columns: 1fr; padding: 36px 28px; }
}
@media (max-width: 767px) {
	.highlights { grid-template-columns: 1fr; }
	.highlights li { padding: 22px; }
	.builder { padding: 28px 22px; border-radius: 18px; }
}
</style>
