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
	text('Get a CV and a cover letter written for any role, in the language of the listing, in English or in French.', 'Obtenez un CV et une lettre écrits pour chaque offre, dans la langue de l’offre, en français ou en anglais.'),
	text('Translate your resume into English or French.', 'Traduisez votre CV en français ou en anglais.'),
	text('Edit everything, then download it or use it to apply.', 'Modifiez tout, puis téléchargez-le ou utilisez-le pour candidater.'),
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
					<figure class="hero-stage w-[min(100%,520px)] before:hidden">
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
				<ul class="mt-11 grid grid-cols-3 gap-5 max-[768px]:grid-cols-1">
					<li v-for="item in mission" :key="item.title" class="rounded-[18px] bg-pale p-7 max-[768px]:p-[22px]">
						<img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" class="size-22" />
						<h3 class="mt-4 text-[19px] font-semibold tracking-[-.025em]">{{ item.title }}</h3>
						<p class="mt-2 text-[15px] leading-[1.7] text-subtle">{{ item.text }}</p>
					</li>
				</ul>
				<p class="mt-7 max-w-[720px] text-[15px] leading-[1.7] text-subtle">{{ text('Employers post on Nextward, and the rest come from approved public sources. Each listing links to the original and shows when it was last checked.', 'Les employeurs publient sur Nextward, les autres offres viennent de sources publiques autorisées. Chaque offre renvoie vers l’original et indique son dernier contrôle.') }}</p>
			</section>

			<section id="ai-tools" class="public-container section grid scroll-mt-[100px] grid-cols-[260px_minmax(0,1fr)] items-center gap-14 pt-10 max-[1024px]:grid-cols-1 max-[1024px]:gap-6" aria-labelledby="tools-title">
				<img src="/nextward-assets/mascots/14-prepare-application.svg" alt="" width="768" height="768" loading="lazy" class="size-65 max-[1024px]:size-40" />
				<div>
					<p class="landing-label inline-flex items-center gap-2"><Sparkles class="size-4" aria-hidden="true" />{{ text('Free to use', 'Gratuit') }}</p>
					<h2 id="tools-title" class="section-title">{{ text('Free to use, with AI help when you apply.', 'Gratuit, avec l’aide de l’IA pour candidater.') }}</h2>
					<p class="section-description">{{ text('Searching, saving, applying, alerts and tracking cost nothing, and so do resumes made from your profile. The AI tools use credits: you get 20 free credits, then 5 more every month.', 'Chercher, sauvegarder, candidater, recevoir des alertes et suivre vos candidatures ne coûte rien, tout comme les CV créés depuis votre profil. Les outils d’IA utilisent des crédits : vous en recevez 20 offerts, puis 5 de plus chaque mois.') }}</p>
					<ul class="mt-6 grid gap-3">
						<li v-for="tool in tools" :key="tool" class="flex items-start gap-3 text-[16px] leading-[1.6] text-gray-700"><Check class="mt-0.5 size-5 shrink-0 text-brand" aria-hidden="true" />{{ tool }}</li>
					</ul>
					<p class="mt-6 text-sm text-slate-600 leading-6">{{ text('The AI only uses facts from your own profile and resume.', 'L’IA n’utilise que les faits de votre profil et de votre CV.') }} <Link :href="`${prefix}/privacy#ai`" class="font-semibold text-brand underline underline-offset-4 focus-ring">{{ text('How we handle your data', 'Comment nous traitons vos données') }}</Link></p>
				</div>
			</section>

			<section class="public-container section pt-10" aria-labelledby="builder-title">
				<div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] items-center gap-12 rounded-[22px] bg-[#0d2353] p-12 text-white max-[1024px]:grid-cols-1 max-[1024px]:px-7 max-[1024px]:py-9 max-[768px]:rounded-[18px] max-[768px]:px-[22px] max-[768px]:py-7">
					<div>
						<p class="landing-label text-blue-200">{{ text('Built by', 'Créé par') }}</p>
						<h2 id="builder-title" class="section-title text-white">Ala Baganne</h2>
						<p class="mt-4 max-w-[560px] text-[17px] leading-[1.75] text-mist">{{ text('I’m a software engineer who builds full-stack products end to end, from the first idea to a working product online. I built Nextward and run it day to day.', 'Je suis ingénieur logiciel et je crée des produits complets, de l’idée au produit en ligne. J’ai créé Nextward et je le fais vivre au quotidien.') }}</p>
						<p class="mt-4 max-w-[560px] text-[17px] leading-[1.75] text-mist">{{ text('I’m open to engineering roles and to building products for others. Feel free to get in touch.', 'Je suis ouvert aux postes en ingénierie et à la création de produits pour d’autres. N’hésitez pas à me contacter.') }}</p>
					</div>
					<ul class="grid gap-2.5">
						<li><a href="mailto:alabaganne9@gmail.com" class="focus-ring flex items-center justify-between gap-3 rounded-[14px] bg-white/8 px-5 py-4 font-semibold break-all text-white transition-colors duration-180 hover:bg-white/14 focus-visible:outline-white">alabaganne9@gmail.com<ArrowUpRight class="size-[18px] shrink-0 text-blue-200" aria-hidden="true" /></a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" rel="noopener" class="focus-ring flex items-center justify-between gap-3 rounded-[14px] bg-white/8 px-5 py-4 font-semibold break-all text-white transition-colors duration-180 hover:bg-white/14 focus-visible:outline-white">alabaganne.com<ArrowUpRight class="size-[18px] shrink-0 text-blue-200" aria-hidden="true" /></a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" rel="noopener" class="focus-ring flex items-center justify-between gap-3 rounded-[14px] bg-white/8 px-5 py-4 font-semibold break-all text-white transition-colors duration-180 hover:bg-white/14 focus-visible:outline-white">LinkedIn<ArrowUpRight class="size-[18px] shrink-0 text-blue-200" aria-hidden="true" /></a></li>
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
