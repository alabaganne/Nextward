<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight, Check, Sparkles } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import ArtPanel from '@/Components/Landing/ArtPanel.vue';

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
const checkMark = 'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-blue-50 text-brand';
const contact = 'focus-ring flex items-center justify-between gap-3 rounded-card bg-white/10 px-5 py-4 font-medium break-all text-white transition-colors duration-180 hover:bg-white/15 focus-visible:outline-white';
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<LandingHero title-id="about-title" :label="text('About Nextward', 'À propos de Nextward')" :lead="text('Nextward brings IT internships and jobs from around the world into one place, says plainly what each role asks for, and helps you apply well.', 'Nextward réunit les stages et emplois IT du monde entier, dit clairement ce que chaque offre demande et vous aide à bien candidater.')">
				{{ text('IT careers,', 'Les carrières IT,') }} <span class="accent">{{ text('made clearer.', 'en plus clair.') }}</span>
				<template #actions>
					<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse roles', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
					<Link :href="`${prefix}/companies`" class="cta cta-ghost focus-ring">{{ text('See companies', 'Voir les entreprises') }}</Link>
				</template>
			</LandingHero>

			<section class="section" aria-labelledby="mission-title">
				<div class="public-container grid grid-cols-2 items-center gap-20 max-[1024px]:grid-cols-1 max-[1024px]:gap-12">
					<div>
						<p class="landing-label">{{ text('Our mission', 'Notre mission') }}</p>
						<h2 id="mission-title" class="section-title">{{ text('Finding work should not depend on', 'Trouver un poste ne devrait pas dépendre de') }} <span class="accent">{{ text('who you know.', 'qui vous connaissez.') }}</span></h2>
						<p class="section-description">{{ text('Good IT roles are spread across company sites and job boards, in several languages, often without the details that matter. Nextward brings them together so every candidate gets the same fair start.', 'Les bonnes offres IT sont dispersées entre sites d’entreprises et job boards, en plusieurs langues, souvent sans les détails importants. Nextward les réunit pour que chaque candidat parte avec les mêmes chances.') }}</p>
						<p class="mt-4 max-w-[560px] text-[16px] leading-[1.7] text-subtle">{{ text('Employers post on Nextward, and the rest come from approved public sources. Each listing links to the original and shows when it was last checked.', 'Les employeurs publient sur Nextward, les autres offres viennent de sources publiques autorisées. Chaque offre renvoie vers l’original et indique son dernier contrôle.') }}</p>
					</div>
					<ArtPanel src="/nextward-assets/mascots/15-student-company-conversation.svg" :width="1100" :height="760" />
				</div>
			</section>

			<section class="band-pale section" aria-labelledby="offer-title">
				<div class="public-container">
					<div class="section-intro">
						<p class="landing-label">{{ text('What Nextward offers', 'Ce que propose Nextward') }}</p>
						<h2 id="offer-title" class="section-title">{{ text('One place for', 'Un seul endroit pour') }} <span class="accent">{{ text('IT careers', 'les carrières IT') }}</span></h2>
					</div>
					<ul class="grid grid-cols-3 gap-6 max-[1024px]:grid-cols-1">
						<li v-for="item in mission" :key="item.title" class="panel p-8 max-[768px]:p-6">
							<img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" class="size-24" />
							<h3 class="mt-5 text-[22px] leading-[1.4] text-navy">{{ item.title }}</h3>
							<p class="mt-3 text-[15.5px] leading-[1.75] text-subtle">{{ item.text }}</p>
						</li>
					</ul>
				</div>
			</section>

			<section id="ai-tools" class="section scroll-mt-[100px]" aria-labelledby="tools-title">
				<div class="public-container grid grid-cols-2 items-center gap-20 max-[1024px]:grid-cols-1 max-[1024px]:gap-12">
					<ArtPanel src="/nextward-assets/mascots/14-prepare-application.svg" tone="navy" class="max-[1024px]:order-last" />
					<div>
						<p class="landing-label"><Sparkles class="size-4" aria-hidden="true" />{{ text('Free to use', 'Gratuit') }}</p>
						<h2 id="tools-title" class="section-title">{{ text('Free to use, with AI help', 'Gratuit, avec l’aide de l’IA') }} <span class="accent">{{ text('when you apply.', 'pour candidater.') }}</span></h2>
						<p class="section-description">{{ text('Searching, saving, applying, alerts and tracking cost nothing, and so do resumes made from your profile. The AI tools use credits: you get 20 free credits, then 5 more every month.', 'Chercher, sauvegarder, candidater, recevoir des alertes et suivre vos candidatures ne coûte rien, tout comme les CV créés depuis votre profil. Les outils d’IA utilisent des crédits : vous en recevez 20 offerts, puis 5 de plus chaque mois.') }}</p>
						<ul class="mt-8 grid max-w-[560px] gap-4">
							<li v-for="tool in tools" :key="tool" class="flex gap-3.5 text-[16px] leading-[1.7] text-subtle"><span :class="checkMark" aria-hidden="true"><Check class="size-3.5" /></span>{{ tool }}</li>
						</ul>
						<p class="mt-8 text-[14.5px] leading-6 text-subtle">{{ text('The AI only uses facts from your own profile and resume.', 'L’IA n’utilise que les faits de votre profil et de votre CV.') }} <Link :href="`${prefix}/privacy#ai`" class="focus-ring font-medium text-brand underline underline-offset-4 hover:text-navy">{{ text('How we handle your data', 'Comment nous traitons vos données') }}</Link></p>
					</div>
				</div>
			</section>

			<section class="public-container section pt-0!" aria-labelledby="builder-title">
				<div class="line-art grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] items-center gap-12 rounded-card bg-navy p-14 text-white max-[1024px]:grid-cols-1 max-[1024px]:px-8 max-[1024px]:py-10 max-[768px]:px-6 max-[768px]:py-8">
					<div>
						<p class="landing-label text-white/85">{{ text('Built by', 'Créé par') }}</p>
						<h2 id="builder-title" class="section-title text-white">Ala Baganne</h2>
						<p class="mt-4 max-w-[560px] text-[17px] leading-[1.75] text-mist">{{ text('I’m a software engineer who builds full-stack products end to end, from the first idea to a working product online. I built Nextward and run it day to day.', 'Je suis ingénieur logiciel et je crée des produits complets, de l’idée au produit en ligne. J’ai créé Nextward et je le fais vivre au quotidien.') }}</p>
						<p class="mt-4 max-w-[560px] text-[17px] leading-[1.75] text-mist">{{ text('I’m open to engineering roles and to building products for others. Feel free to get in touch.', 'Je suis ouvert aux postes en ingénierie et à la création de produits pour d’autres. N’hésitez pas à me contacter.') }}</p>
					</div>
					<ul class="grid gap-3">
						<li><a href="mailto:alabaganne9@gmail.com" :class="contact">alabaganne9@gmail.com<ArrowUpRight class="size-[18px] shrink-0 text-blue-300" aria-hidden="true" /></a></li>
						<li><a href="https://www.alabaganne.com" target="_blank" rel="noopener" :class="contact">alabaganne.com<ArrowUpRight class="size-[18px] shrink-0 text-blue-300" aria-hidden="true" /></a></li>
						<li><a href="https://www.linkedin.com/in/alabaganne/" target="_blank" rel="noopener" :class="contact">LinkedIn<ArrowUpRight class="size-[18px] shrink-0 text-blue-300" aria-hidden="true" /></a></li>
					</ul>
				</div>
			</section>

			<ClosingBanner mascot="16-first-day-celebration" :title="text('Your next IT role starts here.', 'Votre prochain poste IT commence ici.')" :body="text('Browse open internships and jobs, or create a free account to save roles and follow every application.', 'Consultez les stages et emplois ouverts, ou créez un compte gratuit pour sauvegarder des offres et suivre vos candidatures.')">
				<Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse roles', 'Voir les offres') }}</Link>
				<Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
			</ClosingBanner>
		</div>
	</PublicLayout>
</template>
