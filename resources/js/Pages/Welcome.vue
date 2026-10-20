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
const aiPoints = computed(() => [
    text('Upload your resume once. We fill in your profile, and you check every detail.', 'Ajoutez votre CV une fois. Nous remplissons votre profil, et vous vérifiez chaque détail.'),
    text('On any role, get a CV and a cover letter written for it, in English or French.', 'Sur chaque offre, obtenez un CV et une lettre écrits pour elle, en français ou en anglais.'),
    text('Only facts from your profile. Edit everything, then download or apply.', 'Uniquement des faits tirés de votre profil. Modifiez tout, puis téléchargez ou candidatez.'),
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
            <section class="public-container search-overlap" :aria-label="text('Search opportunities', 'Rechercher des opportunités')">
                <form method="get" :action="`${prefix}/opportunities`" class="search-panel" role="search">
                    <div class="search-grid is-three">
                        <label><span class="field-label">{{ text('Role, company or skill', 'Poste, entreprise ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. React, data, DevOps', 'ex. React, data, DevOps')" /></span></label>
                        <label><span class="field-label">{{ text('Opportunity type', 'Type d’opportunité') }}</span><span class="field"><Briefcase aria-hidden="true" /><select name="kind"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></span></label>
                        <button class="cta cta-primary focus-ring" type="submit">{{ text('Search opportunities', 'Rechercher') }}</button>
                    </div>
                </form>
                <div v-if="shortcuts.length" class="chip-row">
                    <p class="chip-row-label">{{ text('Popular searches:', 'Recherches populaires :') }}</p>
                    <nav class="flex flex-wrap gap-2.5" :aria-label="text('Popular searches', 'Recherches populaires')"><Link v-for="shortcut in shortcuts" :key="shortcut.key" :href="href(shortcut)" class="chip focus-ring">{{ labels[shortcut.key] }}<span class="chip-count">{{ shortcut.count }}</span></Link></nav>
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
                    <form method="get" :action="`${prefix}/companies`" class="company-search" role="search">
                        <label class="field flex-1 min-w-0"><span class="sr-only">{{ text('Search companies', 'Rechercher une entreprise') }}</span><Building2 aria-hidden="true" /><input name="search" type="search" maxlength="100" :placeholder="text('Search a company by name or city', 'Cherchez une entreprise par nom ou ville')" /></label>
                        <button class="cta cta-primary focus-ring" type="submit">{{ text('Find companies', 'Trouver') }}</button>
                    </form>
                    <ul class="company-grid">
                        <li v-for="company in companies" :key="company.id">
                            <Link :href="sitePath(company.url)" class="company-tile focus-ring">
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

            <section id="how-it-works" class="public-container how-section" aria-labelledby="how-title">
                <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                <h2 id="how-title" class="section-title">{{ text('Find your fit. Keep moving.', 'Trouvez votre place. Avancez.') }}</h2>
                <ol class="steps">
                    <li v-for="(step, index) in steps" :key="step.title">
                        <img :src="`/nextward-assets/mascots/${step.art}.svg`" alt="" width="768" height="768" loading="lazy" class="section-art" />
                        <span class="step-number mt-3" aria-hidden="true">0{{ index + 1 }}</span>
                        <h3>{{ step.title }}</h3>
                        <p>{{ step.body }}</p>
                    </li>
                </ol>
            </section>

            <section class="band-pale section" aria-labelledby="ai-title">
                <div class="public-container ai-band">
                    <img src="/nextward-assets/mascots/02-student-prepare-cv.svg" alt="" width="768" height="768" loading="lazy" class="ai-art" />
                    <div>
                        <p class="landing-label inline-flex items-center gap-2"><Sparkles class="size-4" aria-hidden="true" />{{ text('Free while we launch', 'Gratuit pendant le lancement') }}</p>
                        <h2 id="ai-title" class="section-title">{{ text('A CV and cover letter for every role.', 'Un CV et une lettre pour chaque offre.') }}</h2>
                        <ul class="ai-points">
                            <li v-for="point in aiPoints" :key="point">{{ point }}</li>
                        </ul>
                        <div class="flex flex-wrap gap-3 mt-8">
                            <Link :href="`${prefix}/register`" class="cta cta-primary focus-ring">{{ text('Create a free account', 'Créer un compte gratuit') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                            <Link :href="`${prefix}/about#ai-tools`" class="landing-link focus-ring self-center">{{ text('How the AI tools work', 'Comment marchent les outils d’IA') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                        </div>
                    </div>
                </div>
            </section>


            <section class="section" aria-labelledby="anatomy-title">
                <div class="public-container anatomy">
                    <div>
                        <p class="landing-label">{{ text('Know before you apply', 'Sachez avant de postuler') }}</p>
                        <h2 id="anatomy-title" class="section-title">{{ text('The details that matter, on every listing.', 'Les détails qui comptent, sur chaque offre.') }}</h2>
                        <dl class="promises">
                            <div v-for="item in promises" :key="item.title"><component :is="item.icon" class="promise-icon" aria-hidden="true" /><div><dt>{{ item.title }}</dt><dd>{{ item.body }}</dd></div></div>
                        </dl>
                    </div>
                    <div class="example-wrap">
                        <img src="/nextward-assets/mascots/03-recruiter-clipboard.svg" alt="" width="768" height="768" loading="lazy" class="example-mascot" />
                        <figure class="panel example" :aria-label="text('Example listing', 'Exemple d’offre')">
                            <div class="example-head">
                                <p class="example-tag">{{ text('Example listing', 'Exemple d’offre') }}</p>
                                <h3>{{ text('Senior Backend Engineer', 'Ingénieur backend senior') }}</h3>
                            </div>
                            <dl class="detail-rows example-rows">
                                <div v-for="row in example" :key="row.label"><dt>{{ row.label }}</dt><dd :class="{ 'is-unknown': row.unknown }">{{ row.value }}</dd></div>
                            </dl>
                            <p class="example-foot">{{ text('Apply on the employer’s website', 'Candidater sur le site de l’employeur') }}<ArrowUpRight class="action-icon" aria-hidden="true" /></p>
                        </figure>
                    </div>
                </div>
            </section>

            <section class="public-container section pt-0!" aria-labelledby="employers-title">
                <div class="employer-card">
                    <img class="employer-art" src="/nextward-assets/mascots/21-cozy-career-workspace-night.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
                    <div class="employer-copy">
                        <p class="landing-label">{{ text('For employers', 'Pour les employeurs') }}</p>
                        <h2 id="employers-title" class="section-title">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2>
                        <p class="section-description">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p>
                        <Link :href="currentUser?.userable_type === 'company' ? '/opportunities/create' : `${prefix}/register?account=company`" class="cta cta-primary focus-ring mt-8">{{ text('Post an opportunity', 'Publier une opportunité') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
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

            <section v-if="faqs.length" class="public-container faq-layout" aria-labelledby="faq-title">
                <div>
                    <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="faq-art" />
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
<style scoped>
.how-section { scroll-margin-top: 100px; padding-block: 88px; }
.company-search { display: flex; gap: 12px; max-width: 640px; margin-bottom: 28px; }
.company-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.company-tile { display: flex; align-items: center; gap: 14px; height: 100%; padding: 18px; border: 1px solid var(--color-line); border-radius: 18px; background: #fff; transition: border-color 180ms; }
.company-tile:hover { border-color: #8ab2ff; }
.ai-band { display: grid; grid-template-columns: 240px minmax(0, 1fr); align-items: center; gap: 56px; }
.ai-art { width: 240px; height: 240px; }
.ai-points { display: grid; gap: 14px; margin-top: 24px; max-width: 620px; }
.ai-points li { position: relative; padding-left: 26px; color: #334155; font-size: 16px; line-height: 1.7; }
.ai-points li::before { content: ''; position: absolute; left: 4px; top: 10px; width: 8px; height: 8px; border-radius: 50%; background: var(--color-brand); }
.anatomy { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: center; gap: 72px; }
.promises { display: grid; gap: 26px; margin-top: 36px; }
.promises > div { display: flex; gap: 16px; }
.promise-icon { flex-shrink: 0; width: 26px; height: 26px; margin-top: 1px; color: var(--color-brand); }
.promises dt { font-size: 17px; font-weight: 600; }
.promises dd { max-width: 460px; margin-top: 4px; color: #586171; font-size: 15px; line-height: 1.7; }
.example-wrap { position: relative; padding-top: 96px; }
.example-mascot { position: absolute; top: 0; right: 16px; width: 140px; height: 140px; }
.example { position: relative; margin: 0; overflow: hidden; }
.example-head { padding: 24px 26px 18px; border-bottom: 1px solid var(--color-line); }
.example-tag { color: var(--color-brand); font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
.example-head h3 { margin-top: 8px; font-size: 22px; font-weight: 600; letter-spacing: -.03em; }
.example-rows { padding-inline: 26px; }
.example-foot { display: flex; align-items: center; justify-content: space-between; margin: 0; padding: 16px 26px; border-top: 1px solid var(--color-line); background: #f6f8fc; color: var(--color-brand); font-size: 14px; font-weight: 600; }
.employer-card { position: relative; display: flex; align-items: center; aspect-ratio: 1672 / 941; overflow: hidden; border-radius: 22px; background: #0d2353; color: #fff; }
.employer-card .landing-label { color: #b9d0ff; }
.employer-card .section-description { color: #c5cddc; }
.employer-card .focus-ring:focus-visible { outline-color: #fff; }
.employer-art { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: right bottom; }
.employer-copy { position: relative; width: 48%; max-width: 580px; padding: 48px 48px 48px 80px; }
.faq-layout { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); align-items: start; gap: 64px; padding-block: 88px; }
.faq-art { width: 128px; height: 128px; margin: 0 0 8px -14px; }
@media (max-width: 1023px) {
    .company-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ai-band { grid-template-columns: 1fr; gap: 24px; }
    .ai-art { width: 160px; height: 160px; }
    .anatomy { grid-template-columns: 1fr; gap: 44px; }
    .employer-card { flex-direction: column; align-items: stretch; aspect-ratio: auto; }
    .employer-copy { order: -1; width: auto; max-width: none; padding: 40px 32px 8px; }
    .employer-art { position: static; height: auto; aspect-ratio: 16 / 9; object-position: 85% 100%; }
    .faq-layout { grid-template-columns: 1fr; gap: 32px; padding-block: 72px; }
}
@media (max-width: 767px) {
    .company-search { flex-direction: column; }
    .company-grid { grid-template-columns: 1fr; }
    .how-section { padding-block: 56px; }
    .example-mascot { width: 110px; height: 110px; }
    .example-wrap { padding-top: 76px; }
    .example-head, .example-foot { padding-inline: 20px; }
    .example-rows { padding-inline: 20px; }
    .employer-card { border-radius: 18px; }
    .employer-copy { padding: 32px 24px 0; }
    .employer-art { aspect-ratio: 4 / 3; }
    .faq-layout { padding-block: 56px; }
}
</style>
