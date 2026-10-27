<script setup>
import { computed } from 'vue';
import { ArrowRight, Bell, Briefcase, Check, ChevronDown, CircleQuestionMark, FileText, Search, Sparkles, SquareArrowOutUpRight } from '@lucide/vue';
import { sitePath } from '@/utils/url';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Opportunity/RoleCard.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
import ArtPanel from '@/Components/Landing/ArtPanel.vue';
const props = defineProps({
    locale: { type: String, default: 'en' },
    opportunities: { type: [Object, Array], default: () => ({ data: [] }) },
    posts: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
    hiringCount: { type: Number, default: 0 },
    faqs: { type: Array, default: () => [] },
});
const listings = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const fr = computed(() => props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const labels = computed(() => ({
    internships: text('Internships', 'Stages'),
    pfe: text('PFE internships', 'Stages PFE'),
    graduate: text('Graduate programmes', 'Programmes jeunes diplômés'),
    jobs: text('Jobs', 'Emplois'),
    remote: text('Remote', 'Télétravail'),
    worldwide: text('Open worldwide', 'Ouvert au monde entier'),
}));
const roles = (count) => text(`${count} open ${count === 1 ? 'role' : 'roles'}`, `${count} ${count === 1 ? 'offre ouverte' : 'offres ouvertes'}`);
const href = (shortcut) => `${prefix.value}${shortcut.path}${shortcut.query ? `?${shortcut.query}` : ''}`;
const steps = computed(() => [
    { title: text('Discover and save', 'Découvrez et sauvegardez'), body: text('Browse internships and jobs, check their requirements, and save the ones you want to come back to.', 'Consultez les stages et les emplois, vérifiez leurs critères et sauvegardez ceux qui vous intéressent.') },
    { title: text('Apply at the right place', 'Candidatez au bon endroit'), body: text('Apply on Nextward for roles posted here, or follow the link to the employer. Tailor your CV and cover letter to the role first.', 'Candidatez sur Nextward pour les offres publiées ici, ou suivez le lien vers l’employeur. Adaptez d’abord votre CV et votre lettre à l’offre.') },
    { title: text('Track your progress', 'Suivez vos démarches'), body: text('Keep every application in one place. For roles on other websites, mark “I applied” and update your progress.', 'Regroupez toutes vos candidatures. Pour les offres d’autres sites, indiquez « J’ai postulé » et mettez votre suivi à jour.') },
]);
const features = computed(() => [
    { icon: FileText, title: text('Eligibility before effort', 'L’éligibilité d’abord'), body: text('See who can apply before you spend time on a role that cannot hire from where you live.', 'Voyez qui peut postuler avant de passer du temps sur un poste fermé à votre pays.') },
    { icon: CircleQuestionMark, title: text('Unknown stays unknown', 'L’inconnu reste inconnu'), body: text('If the source does not say whether pay, a visa or relocation is offered, we say so instead of guessing.', 'Si la source ne précise pas la rémunération, le visa ou la mobilité, nous l’indiquons au lieu de deviner.') },
    { icon: SquareArrowOutUpRight, title: text('The original, always', 'Toujours l’original'), body: text('Every listing shows its source, when it was last checked, and its original text when you read a translation.', 'Chaque offre indique sa source, la date du dernier contrôle, et son texte d’origine quand vous lisez une traduction.') },
    { icon: Bell, title: text('Alerts for new roles', 'Alertes pour les nouvelles offres'), body: text('Save a search and get an email when new roles match it. Stop the alerts whenever you like.', 'Sauvegardez une recherche et recevez un e-mail quand de nouvelles offres correspondent. Arrêtez les alertes quand vous voulez.') },
]);
const aiPoints = computed(() => [
    text('Upload your resume, or make one from your profile with our themes. We can fill in your profile from it, and you check every detail.', 'Ajoutez votre CV, ou créez-en un depuis votre profil avec nos thèmes. Nous pouvons remplir votre profil à partir de celui-ci, et vous vérifiez chaque détail.'),
    text('On any role, get a CV and a cover letter written for it, in the language of the listing, in English or in French.', 'Sur chaque offre, obtenez un CV et une lettre écrits pour elle, dans la langue de l’offre, en français ou en anglais.'),
    text('Only facts from your profile. Edit everything, then download or apply.', 'Uniquement des faits tirés de votre profil. Modifiez tout, puis téléchargez ou candidatez.'),
    text('You get 20 free credits, then 5 more every month. One credit covers one AI task.', 'Vous recevez 20 crédits offerts, puis 5 de plus chaque mois. Un crédit couvre une tâche d’IA.'),
]);
const checkMark = 'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-blue-50 text-brand';
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <LandingHero title-id="hero-title" overlap :mascots="['17-student-presenter', '18-recruiter-mentor']" :label="text('Internships and jobs', 'Stages et emplois')" :lead="text('Internships and jobs around the world, from your first role to your next career move. Free to browse, with a CV and cover letter tailored to each role.', 'Des stages et des emplois partout dans le monde, de votre première expérience à votre prochaine étape. Gratuit, avec un CV et une lettre adaptés à chaque offre.')" :note="text('No account needed to explore or visit external application pages.', 'Aucun compte nécessaire pour consulter les offres ou candidater sur un site externe.')">
                {{ text('Find your next', 'Trouvez votre prochaine') }} <span class="accent">{{ text('opportunity.', 'opportunité.') }}</span>
                <template #actions>
                    <Link :href="`${prefix}/opportunities`" class="cta cta-light focus-ring">{{ text('Browse roles', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    <Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
                </template>
            </LandingHero>
            <div class="public-container hero-overlap">
                <form method="get" :action="`${prefix}/opportunities`" class="mx-auto max-w-[940px] rounded-card border border-line bg-white p-6 max-[768px]:p-4" role="search" :aria-label="text('Search roles', 'Rechercher des offres')">
                    <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] items-end gap-4 max-[1024px]:grid-cols-2 max-[768px]:grid-cols-1">
                        <label class="max-[1024px]:col-span-full"><span class="mb-2 block text-[14px] text-navy">{{ text('Role, company or skill', 'Poste, entreprise ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. React, data, DevOps', 'ex. React, data, DevOps')" /></span></label>
                        <label><span class="mb-2 block text-[14px] text-navy">{{ text('Role type', 'Type d’offre') }}</span><span class="field"><Briefcase aria-hidden="true" /><select name="kind"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></span></label>
                        <button class="cta cta-primary focus-ring h-[52px]" type="submit">{{ text('Search', 'Rechercher') }}</button>
                    </div>
                </form>
                <div v-if="shortcuts.length" class="mt-6 flex flex-wrap items-center justify-center gap-2.5">
                    <p class="mr-1 text-[14px] text-subtle">{{ text('Popular searches:', 'Recherches populaires :') }}</p>
                    <nav class="flex flex-wrap justify-center gap-2.5" :aria-label="text('Popular searches', 'Recherches populaires')"><Link v-for="shortcut in shortcuts" :key="shortcut.key" :href="href(shortcut)" class="chip focus-ring">{{ labels[shortcut.key] }}<span class="text-brand tabular-nums">{{ shortcut.count }}</span></Link></nav>
                </div>
            </div>

            <section v-if="companies.length" class="public-container pt-20 pb-4 max-[768px]:pt-14" aria-labelledby="companies-title">
                <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-b border-line pb-5">
                    <h2 id="companies-title" class="text-[15px] tracking-caps text-navy uppercase">{{ text('Companies hiring now', 'Ils recrutent en ce moment') }}</h2>
                    <Link :href="`${prefix}/companies`" class="landing-link focus-ring">{{ hiringCount > companies.length ? text(`See all ${hiringCount} companies`, `Voir les ${hiringCount} entreprises`) : text('See all companies', 'Voir toutes les entreprises') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <ul class="mt-7 grid grid-cols-4 gap-x-8 gap-y-6 max-[1024px]:grid-cols-2 max-[480px]:grid-cols-1">
                    <li v-for="company in companies" :key="company.id">
                        <Link :href="sitePath(company.url)" class="group focus-ring flex items-center gap-3.5 rounded-card">
                            <CompanyLogo class="size-12 rounded-card border border-line text-base" :name="company.name" :src="company.logo" />
                            <span class="min-w-0">
                                <span class="block truncate text-[17px] text-navy transition-colors duration-180 group-hover:text-brand">{{ company.name }}</span>
                                <span class="mt-0.5 block text-[13.5px] text-subtle-2">{{ roles(company.open_roles_count) }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="band-pale section mt-20 max-[768px]:mt-14" aria-labelledby="latest-title">
                <div class="public-container">
                    <div class="section-intro">
                        <p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p>
                        <h2 id="latest-title" class="section-title">{{ text('Recent', 'Les dernières') }} <span class="accent">{{ text('roles', 'offres') }}</span></h2>
                        <p v-if="listings.length" class="section-description">{{ text('Every listing shows its source, where to apply and who can apply. Details the source doesn’t give are marked as unknown.', 'Chaque offre indique sa source, où candidater et qui peut postuler. Les détails que la source ne donne pas sont indiqués comme inconnus.') }}</p>
                    </div>
                    <div v-if="listings.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :opportunity="opportunity" /></div>
                    <p v-else class="panel mx-auto max-w-[740px] p-8 text-center text-subtle">{{ text('There are no active roles to show right now. Employer posts and approved source listings appear here when available.', 'Aucune offre active à afficher pour le moment. Les offres des employeurs et des sources autorisées apparaissent ici lorsqu’elles sont disponibles.') }}</p>
                    <div class="mt-12 flex justify-center"><Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Browse all roles', 'Voir toutes les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link></div>
                </div>
            </section>

            <section id="how-it-works" class="section scroll-mt-[100px]" aria-labelledby="how-title">
                <div class="public-container">
                    <div class="section-intro">
                        <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                        <h2 id="how-title" class="section-title">{{ text('Find your fit.', 'Trouvez votre place.') }} <span class="accent">{{ text('Keep moving.', 'Avancez.') }}</span></h2>
                    </div>
                    <ol class="relative grid grid-cols-3 gap-10 max-[768px]:grid-cols-1 max-[768px]:gap-12">
                        <span class="absolute top-8 right-[calc(16.67%+40px)] left-[calc(16.67%+40px)] border-t-2 border-dashed border-blue-200 max-[768px]:hidden" aria-hidden="true"></span>
                        <li v-for="(step, index) in steps" :key="step.title" class="relative text-center">
                            <span class="mx-auto grid size-16 place-items-center rounded-full bg-brand text-[24px] font-medium text-white tabular-nums" aria-hidden="true">{{ index + 1 }}</span>
                            <h3 class="mt-6 text-[22px] text-navy">{{ step.title }}</h3>
                            <p class="mx-auto mt-3 max-w-[330px] text-[15.5px] leading-[1.75] text-subtle">{{ step.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="band-navy section" aria-labelledby="features-title">
                <div class="public-container">
                    <div class="section-intro">
                        <p class="landing-label">{{ text('Know before you apply', 'Sachez avant de postuler') }}</p>
                        <h2 id="features-title" class="section-title">{{ text('The details that matter,', 'Les détails qui comptent,') }} <span class="accent">{{ text('on every listing.', 'sur chaque offre.') }}</span></h2>
                    </div>
                    <ul class="grid grid-cols-4 gap-x-8 gap-y-12 max-[1024px]:grid-cols-2 max-[640px]:grid-cols-1">
                        <li v-for="item in features" :key="item.title" class="text-center">
                            <span class="icon-tile mx-auto"><component :is="item.icon" class="size-[22px]" aria-hidden="true" /></span>
                            <h3 class="mt-6 text-[20px] text-white">{{ item.title }}</h3>
                            <p class="mx-auto mt-3 max-w-[300px] text-[15.5px] leading-[1.75] text-mist">{{ item.body }}</p>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="section" aria-labelledby="ai-title">
                <div class="public-container grid grid-cols-2 items-center gap-20 max-[1024px]:grid-cols-1 max-[1024px]:gap-12">
                    <div>
                        <p class="landing-label"><Sparkles class="size-4" aria-hidden="true" />{{ text('20 free credits to start', '20 crédits offerts pour commencer') }}</p>
                        <h2 id="ai-title" class="section-title">{{ text('A CV and cover letter', 'Un CV et une lettre') }} <span class="accent">{{ text('for every role.', 'pour chaque offre.') }}</span></h2>
                        <ul class="mt-8 grid max-w-[560px] gap-4">
                            <li v-for="point in aiPoints" :key="point" class="flex gap-3.5 text-[16px] leading-[1.7] text-subtle"><span :class="checkMark" aria-hidden="true"><Check class="size-3.5" /></span>{{ point }}</li>
                        </ul>
                        <div class="mt-10 flex flex-wrap gap-4">
                            <Link :href="`${prefix}/register`" class="cta cta-primary focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
                            <Link :href="`${prefix}/about#ai-tools`" class="cta cta-secondary focus-ring">{{ text('How the AI tools work', 'Comment marchent les outils d’IA') }}</Link>
                        </div>
                    </div>
                    <ArtPanel src="/nextward-assets/mascots/02-student-prepare-cv.svg" />
                </div>
            </section>

            <section class="public-container section pt-0!" aria-labelledby="employers-title">
                <div class="relative flex aspect-[1672/941] items-center overflow-hidden rounded-card bg-navy text-white max-[1024px]:aspect-auto max-[1024px]:flex-col max-[1024px]:items-stretch">
                    <img class="absolute inset-0 size-full object-cover object-right-bottom max-[1024px]:static max-[1024px]:aspect-video max-[1024px]:h-auto max-[1024px]:object-[85%_100%] max-[768px]:aspect-[4/3]" src="/nextward-assets/mascots/21-cozy-career-workspace-night.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
                    <div class="relative w-[48%] max-w-[580px] py-12 pr-12 pl-20 max-[1024px]:-order-1 max-[1024px]:w-auto max-[1024px]:max-w-none max-[1024px]:px-8 max-[1024px]:pt-10 max-[1024px]:pb-2 max-[768px]:px-6 max-[768px]:pt-8 max-[768px]:pb-0">
                        <p class="landing-label text-blue-200">{{ text('For employers', 'Pour les employeurs') }}</p>
                        <h2 id="employers-title" class="section-title text-white">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2>
                        <p class="section-description text-mist">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p>
                        <Link :href="currentUser?.userable_type === 'company' ? route('opportunities.create') : `${prefix}/register?account=company`" class="cta cta-primary focus-ring mt-8 hover:border-white hover:text-white focus-visible:outline-white">{{ text('Post a role', 'Publier une offre') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    </div>
                </div>
            </section>

            <section v-if="faqs.length" class="section" aria-labelledby="faq-title">
                <div class="public-container grid grid-cols-[minmax(0,.75fr)_minmax(0,1.25fr)] items-start gap-20 max-[1024px]:grid-cols-1 max-[1024px]:gap-10">
                    <div>
                        <p class="landing-label">FAQ</p>
                        <h2 id="faq-title" class="section-title">{{ text('Questions people', 'Les questions') }} <span class="accent">{{ text('ask us', 'qu’on nous pose') }}</span></h2>
                        <p class="section-description">{{ text('Still unsure about something? Write to us from the contact page.', 'Une autre question ? Écrivez-nous depuis la page contact.') }} <Link :href="`${prefix}/contact`" class="focus-ring text-brand underline underline-offset-4">{{ text('Contact us', 'Nous contacter') }}</Link></p>
                        <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="mt-6 -ml-4 size-44 max-[1024px]:hidden" />
                    </div>
                    <div class="faq-list">
                        <details v-for="item in faqs" :key="item.q" class="faq-item"><summary class="focus-ring">{{ item.q }}<span class="faq-toggle" aria-hidden="true"><ChevronDown /></span></summary><p>{{ item.a }}</p></details>
                    </div>
                </div>
            </section>

            <section v-if="posts.length" class="section pt-0!" aria-labelledby="guides-title">
                <div class="public-container">
                    <div class="section-intro">
                        <p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p>
                        <h2 id="guides-title" class="section-title">{{ text('Latest', 'Les derniers') }} <span class="accent">{{ text('career guides', 'guides carrière') }}</span></h2>
                    </div>
                    <div class="guide-grid"><GuideCard v-for="post in posts" :key="post.slug" :post="post" /></div>
                    <div class="mt-12 flex justify-center"><Link :href="`${prefix}/blog`" class="cta cta-secondary focus-ring">{{ text('Explore career guides', 'Voir les guides carrière') }}</Link></div>
                </div>
            </section>

            <ClosingBanner mascot="01-student-wave" :title="text('Your next step starts with one search.', 'Votre prochaine étape commence par une recherche.')" :body="text('Create a free account to save roles, tailor your CV and follow every application.', 'Créez un compte gratuit pour sauvegarder des offres, adapter votre CV et suivre chaque candidature.')">
                <Link :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
                <Link :href="`${prefix}/opportunities`" class="cta cta-ghost focus-ring">{{ text('Browse roles', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
            </ClosingBanner>
        </div>
    </PublicLayout>
</template>
