<script setup>
import { computed } from 'vue';
import { ArrowRight, ArrowUpRight, Briefcase, Building2, CircleQuestionMark, FileText, MapPin, Search, ShieldCheck, Sparkles, SquareArrowOutUpRight } from '@lucide/vue';
import { sitePath } from '@/utils/url';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Opportunity/RoleCard.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
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
    { art: '07-search-internships', title: text('Discover and save', 'Découvrez et sauvegardez'), body: text('Browse internships and jobs, check their requirements, and save the ones you want to come back to.', 'Consultez les stages et les emplois, vérifiez leurs critères et sauvegardez ceux qui vous intéressent.') },
    { art: '08-application-envelope', title: text('Apply at the right place', 'Candidatez au bon endroit'), body: text('Apply on Nextward for roles posted here, or follow the link to the employer. Tailor your CV and cover letter to the role first.', 'Candidatez sur Nextward pour les offres publiées ici, ou suivez le lien vers l’employeur. Adaptez d’abord votre CV et votre lettre à l’offre.') },
    { art: '09-interview-calendar', title: text('Track your progress', 'Suivez vos démarches'), body: text('Keep every application in one place. For roles on other websites, mark “I applied” and update your progress.', 'Regroupez toutes vos candidatures. Pour les offres d’autres sites, indiquez « J’ai postulé » et mettez votre suivi à jour.') },
]);
const promises = computed(() => [
    { icon: FileText, title: text('Eligibility before effort', 'L’éligibilité d’abord'), body: text('See who can apply before you spend time on a role that cannot hire from where you live.', 'Voyez qui peut postuler avant de passer du temps sur un poste fermé à votre pays.') },
    { icon: CircleQuestionMark, title: text('Unknown stays unknown', 'L’inconnu reste inconnu'), body: text('If the source does not say whether pay, a visa or relocation is offered, we say so instead of guessing.', 'Si la source ne précise pas la rémunération, le visa ou la mobilité, nous l’indiquons au lieu de deviner.') },
    { icon: SquareArrowOutUpRight, title: text('The original, always', 'Toujours l’original'), body: text('Every listing shows its source, when it was last checked, and its original text when you read a translation.', 'Chaque offre indique sa source, la date du dernier contrôle, et son texte d’origine quand vous lisez une traduction.') },
]);
const example = computed(() => [
    { label: text('Work type', 'Mode de travail'), value: text('Remote, full-time', 'Télétravail, temps plein') },
    { label: text('Who can apply', 'Qui peut postuler'), value: text('Anyone, from any country', 'Tout le monde, depuis n’importe quel pays') },
    { label: text('Relocation', 'Mobilité'), value: text('Not offered', 'Non proposée') },
    { label: text('Visa sponsorship', 'Parrainage de visa'), value: text('Not given by the source', 'Non précisé par la source'), unknown: true },
    { label: text('Pay', 'Rémunération'), value: text('Not given by the source', 'Non précisée par la source'), unknown: true },
    { label: text('Source', 'Source'), value: text('Company careers page, checked 2 days ago', 'Page carrières de l’entreprise, contrôlée il y a 2 jours') },
]);
const fieldLabel = 'mb-2 block text-[13px] font-semibold text-navy';
const aiPoints = computed(() => [
    text('Upload your resume, or make one from your profile with our themes. We can fill in your profile from it, and you check every detail.', 'Ajoutez votre CV, ou créez-en un depuis votre profil avec nos thèmes. Nous pouvons remplir votre profil à partir de celui-ci, et vous vérifiez chaque détail.'),
    text('On any role, get a CV and a cover letter written for it, in the language of the listing, in English or in French.', 'Sur chaque offre, obtenez un CV et une lettre écrits pour elle, dans la langue de l’offre, en français ou en anglais.'),
    text('Only facts from your profile. Edit everything, then download or apply.', 'Uniquement des faits tirés de votre profil. Modifiez tout, puis téléchargez ou candidatez.'),
    text('You get 20 free credits, then 5 more every month. One credit covers one AI task.', 'Vous recevez 20 crédits offerts, puis 5 de plus chaque mois. Un crédit couvre une tâche d’IA.'),
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <LandingHero title-id="hero-title" :mascots="['17-student-presenter', '18-recruiter-mentor']" :label="text('Internships and jobs', 'Stages et emplois')" :lead="text('Internships and jobs around the world, from your first role to your next career move. Free to browse, with a CV and cover letter tailored to each role.', 'Des stages et des emplois partout dans le monde, de votre première expérience à votre prochaine étape. Gratuit, avec un CV et une lettre adaptés à chaque offre.')" :note="text('No account needed to explore or visit external application pages.', 'Aucun compte nécessaire pour consulter les offres ou candidater sur un site externe.')">
                {{ text('Find your next', 'Trouvez votre prochaine') }} <span class="accent">{{ text('opportunity.', 'opportunité.') }}</span>
                <template #actions>
                    <Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    <Link :href="`${prefix}/register`" class="cta cta-ghost focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
                </template>
            </LandingHero>
            <section class="public-container relative z-[2] -mt-[72px] max-[768px]:-mt-[60px]" :aria-label="text('Search opportunities', 'Rechercher des opportunités')">
                <form method="get" :action="`${prefix}/opportunities`" class="rounded-[18px] border border-line bg-white p-5 max-[768px]:p-4" role="search">
                    <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] items-end gap-4 max-[1024px]:grid-cols-2 max-[768px]:grid-cols-1">
                        <label class="max-[1024px]:col-span-full"><span :class="fieldLabel">{{ text('Role, company or skill', 'Poste, entreprise ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. React, data, DevOps', 'ex. React, data, DevOps')" /></span></label>
                        <label><span :class="fieldLabel">{{ text('Opportunity type', 'Type d’opportunité') }}</span><span class="field"><Briefcase aria-hidden="true" /><select name="kind"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></span></label>
                        <button class="cta cta-primary focus-ring h-12" type="submit">{{ text('Search opportunities', 'Rechercher') }}</button>
                    </div>
                </form>
                <div v-if="shortcuts.length" class="mt-[18px] flex flex-wrap items-center gap-2.5">
                    <p class="mr-1 text-[14px] text-subtle">{{ text('Popular searches:', 'Recherches populaires :') }}</p>
                    <nav class="flex flex-wrap gap-2.5" :aria-label="text('Popular searches', 'Recherches populaires')"><Link v-for="shortcut in shortcuts" :key="shortcut.key" :href="href(shortcut)" class="chip focus-ring">{{ labels[shortcut.key] }}<span class="font-medium text-brand tabular-nums">{{ shortcut.count }}</span></Link></nav>
                </div>
            </section>

            <section class="public-container section" aria-labelledby="latest-title">
                <div class="section-head"><div><p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p><h2 id="latest-title" class="section-title">{{ text('Recent opportunities', 'Les dernières opportunités') }}</h2></div><Link :href="`${prefix}/opportunities`" class="landing-link focus-ring">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link></div>
                <div v-if="listings.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :opportunity="opportunity" /></div>
                <p v-if="listings.length" class="mt-6 flex items-start gap-2.5 text-[14.5px] leading-6 text-slate-600"><ShieldCheck class="mt-0.5 size-[18px] shrink-0 text-brand" aria-hidden="true" />{{ text('Every listing shows its source, where to apply and who can apply. Details the source doesn’t give are marked as unknown.', 'Chaque offre indique sa source, où candidater et qui peut postuler. Les détails que la source ne donne pas sont indiqués comme inconnus.') }}</p>
                <p v-else class="panel p-8 text-slate-600">{{ text('There are no active opportunities to show right now. Employer posts and approved source listings appear here when available.', 'Aucune opportunité active à afficher pour le moment. Les offres des employeurs et des sources autorisées apparaissent ici lorsqu’elles sont disponibles.') }}</p>
            </section>


            <section v-if="companies.length" class="band-pale section" aria-labelledby="companies-title">
                <div class="public-container">
                    <div class="section-head">
                        <div><p class="landing-label">{{ text('Companies', 'Entreprises') }}</p><h2 id="companies-title" class="section-title">{{ text('Companies hiring now', 'Ils recrutent en ce moment') }}</h2></div>
                        <Link :href="`${prefix}/companies`" class="landing-link focus-ring">{{ hiringCount > companies.length ? text(`See all ${hiringCount} companies`, `Voir les ${hiringCount} entreprises`) : text('See all companies', 'Voir toutes les entreprises') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    </div>
                    <form method="get" :action="`${prefix}/companies`" class="mb-7 flex max-w-[640px] gap-3 max-[768px]:flex-col" role="search">
                        <label class="field flex-1 min-w-0"><span class="sr-only">{{ text('Search companies', 'Rechercher une entreprise') }}</span><Building2 aria-hidden="true" /><input name="search" type="search" maxlength="100" :placeholder="text('Search a company by name or city', 'Cherchez une entreprise par nom ou ville')" /></label>
                        <button class="cta cta-primary focus-ring" type="submit">{{ text('Find companies', 'Trouver') }}</button>
                    </form>
                    <ul class="grid grid-cols-4 gap-4 max-[1024px]:grid-cols-2 max-[768px]:grid-cols-1">
                        <li v-for="company in companies" :key="company.id">
                            <Link :href="sitePath(company.url)" class="focus-ring flex h-full items-center gap-3.5 rounded-[18px] border border-line bg-white p-[18px] transition-[border-color] duration-180 hover:border-blue-300">
                                <CompanyLogo class="size-12 rounded-xl text-base border border-line" :name="company.name" :src="company.logo" />
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-navy">{{ company.name }}</span>
                                    <span v-if="company.location" class="mt-0.5 flex items-center gap-1 text-xs text-slate-500"><MapPin :size="13" class="shrink-0" aria-hidden="true" /><span class="truncate">{{ company.location }}</span></span>
                                    <span class="mt-1.5 block text-sm font-semibold text-brand">{{ roles(company.open_roles_count) }}</span>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <section id="how-it-works" class="public-container scroll-mt-[100px] py-22 max-[768px]:py-14" aria-labelledby="how-title">
                <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                <h2 id="how-title" class="section-title">{{ text('Find your fit. Keep moving.', 'Trouvez votre place. Avancez.') }}</h2>
                <ol class="mt-10 grid grid-cols-3 gap-8 max-[768px]:grid-cols-1 max-[768px]:gap-7">
                    <li v-for="(step, index) in steps" :key="step.title">
                        <img :src="`/nextward-assets/mascots/${step.art}.svg`" alt="" width="768" height="768" loading="lazy" class="-ml-2.5 block size-28 max-[768px]:size-[92px]" />
                        <span class="mt-3 grid size-11 place-items-center rounded-full bg-blue-50 font-display text-[15px] font-semibold text-brand tabular-nums" aria-hidden="true">0{{ index + 1 }}</span>
                        <h3 class="mt-[18px] text-[19px] font-semibold tracking-[-.025em]">{{ step.title }}</h3>
                        <p class="mt-2 text-[14.5px] leading-[1.75] text-subtle">{{ step.body }}</p>
                    </li>
                </ol>
            </section>

            <section class="band-pale section" aria-labelledby="ai-title">
                <div class="public-container grid grid-cols-[240px_minmax(0,1fr)] items-center gap-14 max-[1024px]:grid-cols-1 max-[1024px]:gap-6">
                    <img src="/nextward-assets/mascots/02-student-prepare-cv.svg" alt="" width="768" height="768" loading="lazy" class="size-60 max-[1024px]:size-40" />
                    <div>
                        <p class="landing-label inline-flex items-center gap-2"><Sparkles class="size-4" aria-hidden="true" />{{ text('20 free credits to start', '20 crédits offerts pour commencer') }}</p>
                        <h2 id="ai-title" class="section-title">{{ text('A CV and cover letter for every role.', 'Un CV et une lettre pour chaque offre.') }}</h2>
                        <ul class="mt-6 grid max-w-[620px] gap-3.5">
                            <li v-for="point in aiPoints" :key="point" class="relative pl-[26px] text-[16px] leading-[1.7] text-gray-700 before:absolute before:top-2.5 before:left-1 before:size-2 before:rounded-full before:bg-brand">{{ point }}</li>
                        </ul>
                        <div class="flex flex-wrap gap-3 mt-8">
                            <Link :href="`${prefix}/register`" class="cta cta-primary focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                            <Link :href="`${prefix}/about#ai-tools`" class="landing-link focus-ring self-center">{{ text('How the AI tools work', 'Comment marchent les outils d’IA') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                        </div>
                    </div>
                </div>
            </section>


            <section class="section" aria-labelledby="anatomy-title">
                <div class="public-container grid grid-cols-2 items-center gap-18 max-[1024px]:grid-cols-1 max-[1024px]:gap-11">
                    <div>
                        <p class="landing-label">{{ text('Know before you apply', 'Sachez avant de postuler') }}</p>
                        <h2 id="anatomy-title" class="section-title">{{ text('The details that matter, on every listing.', 'Les détails qui comptent, sur chaque offre.') }}</h2>
                        <dl class="mt-9 grid gap-[26px]">
                            <div v-for="item in promises" :key="item.title" class="flex gap-4"><component :is="item.icon" class="mt-px size-[26px] shrink-0 text-brand" aria-hidden="true" /><div><dt class="text-[17px] font-semibold">{{ item.title }}</dt><dd class="mt-1 max-w-[460px] text-[15px] leading-[1.7] text-subtle">{{ item.body }}</dd></div></div>
                        </dl>
                    </div>
                    <div class="relative pt-24 max-[768px]:pt-[76px]">
                        <img src="/nextward-assets/mascots/03-recruiter-clipboard.svg" alt="" width="768" height="768" loading="lazy" class="absolute top-0 right-4 size-[140px] max-[768px]:size-[110px]" />
                        <figure class="panel relative m-0 overflow-hidden" :aria-label="text('Example listing', 'Exemple d’offre')">
                            <div class="border-b border-line px-[26px] pt-6 pb-[18px] max-[768px]:px-5">
                                <p class="text-[11.5px] font-semibold tracking-[.12em] text-brand uppercase">{{ text('Example listing', 'Exemple d’offre') }}</p>
                                <h3 class="mt-2 text-[22px] font-semibold tracking-[-.03em]">{{ text('Senior Backend Engineer', 'Ingénieur backend senior') }}</h3>
                            </div>
                            <dl class="grid px-[26px] max-[768px]:px-5">
                                <div v-for="row in example" :key="row.label" class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] gap-4 border-b border-line py-[13px] text-[14px] last:border-b-0 max-[768px]:grid-cols-1 max-[768px]:gap-0.5"><dt class="text-subtle">{{ row.label }}</dt><dd :class="row.unknown ? 'font-medium text-subtle italic' : 'font-semibold text-navy'">{{ row.value }}</dd></div>
                            </dl>
                            <p class="m-0 flex items-center justify-between border-t border-line bg-pale px-[26px] py-4 text-[14px] font-semibold text-brand max-[768px]:px-5">{{ text('Apply on the employer’s website', 'Candidater sur le site de l’employeur') }}<ArrowUpRight class="action-icon" aria-hidden="true" /></p>
                        </figure>
                    </div>
                </div>
            </section>

            <section class="public-container section pt-0!" aria-labelledby="employers-title">
                <div class="relative flex aspect-[1672/941] items-center overflow-hidden rounded-[22px] bg-[#0d2353] text-white max-[1024px]:aspect-auto max-[1024px]:flex-col max-[1024px]:items-stretch max-[768px]:rounded-[18px]">
                    <img class="absolute inset-0 size-full object-cover object-right-bottom max-[1024px]:static max-[1024px]:aspect-video max-[1024px]:h-auto max-[1024px]:object-[85%_100%] max-[768px]:aspect-[4/3]" src="/nextward-assets/mascots/21-cozy-career-workspace-night.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
                    <div class="relative w-[48%] max-w-[580px] py-12 pr-12 pl-20 max-[1024px]:-order-1 max-[1024px]:w-auto max-[1024px]:max-w-none max-[1024px]:px-8 max-[1024px]:pt-10 max-[1024px]:pb-2 max-[768px]:px-6 max-[768px]:pt-8 max-[768px]:pb-0">
                        <p class="landing-label text-blue-200">{{ text('For employers', 'Pour les employeurs') }}</p>
                        <h2 id="employers-title" class="section-title">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2>
                        <p class="section-description text-mist">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p>
                        <Link :href="currentUser?.userable_type === 'company' ? route('opportunities.create') : `${prefix}/register?account=company`" class="cta cta-primary focus-ring mt-8 focus-visible:outline-white">{{ text('Post an opportunity', 'Publier une opportunité') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    </div>
                </div>
            </section>

            <section v-if="posts.length" class="public-container pb-4" aria-labelledby="guides-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p><h2 id="guides-title" class="section-title">{{ text('A little guidance goes a long way.', 'Des conseils pour avancer.') }}</h2></div>
                    <Link :href="`${prefix}/blog`" class="landing-link focus-ring">{{ text('Explore career guides', 'Voir les guides carrière') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div class="guide-grid is-three"><GuideCard v-for="post in posts" :key="post.slug" :post="post" /></div>
            </section>

            <section v-if="faqs.length" class="public-container grid grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)] items-start gap-16 py-22 max-[1024px]:grid-cols-1 max-[1024px]:gap-8 max-[1024px]:py-18 max-[768px]:py-14" aria-labelledby="faq-title">
                <div>
                    <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="mb-2 -ml-3.5 size-32" />
                    <h2 id="faq-title" class="section-title">{{ text('Questions people ask us', 'Les questions qu’on nous pose') }}</h2>
                    <p class="section-description">{{ text('Still unsure about something? Write to us from the contact page.', 'Une autre question ? Écrivez-nous depuis la page contact.') }} <Link :href="`${prefix}/contact`" class="font-semibold text-brand underline underline-offset-4 focus-ring">{{ text('Contact us', 'Nous contacter') }}</Link></p>
                </div>
                <div class="faq-list">
                    <details v-for="item in faqs" :key="item.q" class="faq-item"><summary class="focus-ring">{{ item.q }}<Icon name="plus" class="faq-toggle" /></summary><p>{{ item.a }}</p></details>
                </div>
            </section>

            <ClosingBanner mascot="01-student-wave" :title="text('Your next step starts with one search.', 'Votre prochaine étape commence par une recherche.')" :body="text('Create a free account to save roles, tailor your CV and follow every application.', 'Créez un compte gratuit pour sauvegarder des offres, adapter votre CV et suivre chaque candidature.')">
                <Link :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}</Link>
                <Link :href="`${prefix}/opportunities`" class="cta cta-ghost focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
            </ClosingBanner>
        </div>
    </PublicLayout>
</template>
