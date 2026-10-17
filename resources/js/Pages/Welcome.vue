<script setup>
import { computed } from 'vue';
import { ArrowRight, Briefcase, Globe, MapPin, Plane, Search, ShieldCheck } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
const props = defineProps({ locale: { type: String, default: 'en' }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, posts: { type: Array, default: () => [] } });
const listings = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const fr = computed(() => props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const shortcuts = computed(() => [
    { path: 'tunisia', icon: MapPin, label: text('In Tunisia', 'En Tunisie') },
    { path: 'remote-from-tunisia', icon: Globe, label: text('Remote from Tunisia', 'À distance depuis la Tunisie') },
    { path: 'relocation', icon: Plane, label: text('Abroad with relocation', 'À l’étranger avec aide à la mobilité') },
]);
const audiences = computed(() => [
    { key: 'students', art: '11-learning-books-and-cap', label: text('For students', 'Pour les étudiants'), title: text('Make your first move.', 'Faites le premier pas.'), body: text('Explore PFE and summer internships, graduate programmes, and first jobs. Find the experience that helps you grow.', 'Explorez les PFE, les stages d’été, les programmes jeunes diplômés et les premiers emplois. Trouvez une expérience pour progresser.'), link: text('Explore the student section', 'Découvrir l’espace étudiants'), href: `${prefix.value}/for-students` },
    { key: 'professionals', art: '10-portfolio-laptop', label: text('For professionals', 'Pour les professionnels'), title: text('Build on your experience.', 'Valorisez votre expérience.'), body: text('Find full-time, part-time, contract, and freelance roles. Explore local hiring, remote work, and opportunities abroad.', 'Trouvez des postes à temps plein, à temps partiel, en contrat et en freelance. Explorez le marché local, le télétravail et les offres à l’étranger.'), link: text('Explore the professional section', 'Découvrir l’espace professionnels'), href: `${prefix.value}/for-professionals` },
]);
const steps = computed(() => [
    { art: '07-search-internships', title: text('Discover and save', 'Découvrez et sauvegardez'), body: text('Browse roles, check their requirements, and save the opportunities you want to revisit.', 'Consultez les offres et leurs critères, puis sauvegardez les opportunités qui vous intéressent.') },
    { art: '08-application-envelope', title: text('Apply at the right place', 'Candidatez au bon endroit'), body: text('Apply on Nextward for employer-managed roles, or follow the link to the employer or original source.', 'Candidatez sur Nextward pour les offres gérées ici, ou suivez le lien vers l’employeur ou la source originale.') },
    { art: '09-interview-calendar', title: text('Track your progress', 'Suivez vos démarches'), body: text('Keep applications together. For external roles, record “I applied” and update your own progress.', 'Regroupez vos candidatures. Pour les offres externes, indiquez votre candidature et mettez vous-même votre suivi à jour.') },
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <LandingHero title-id="hero-title" :mascots="['17-student-presenter', '18-recruiter-mentor']" :label="text('For students and professionals', 'Pour les étudiants et les professionnels')" :lead="text('Internships and jobs for students and professionals, in Tunisia and worldwide. Find your first role or your next career move.', 'Des stages et des emplois pour les étudiants et les professionnels, en Tunisie et dans le monde. Trouvez votre première expérience ou votre prochaine étape professionnelle.')" :note="text('No account needed to explore or visit external application pages.', 'Aucun compte nécessaire pour consulter les offres ou candidater sur un site externe.')">
                {{ text('Find your next', 'Trouvez votre prochaine') }} <span class="accent">{{ text('opportunity.', 'opportunité.') }}</span>
            </LandingHero>
            <section class="public-container search-overlap" :aria-label="text('Search opportunities', 'Rechercher des opportunités')">
                <form method="get" :action="`${prefix}/opportunities`" class="search-panel" role="search">
                    <div class="search-grid is-three">
                        <label><span class="field-label">{{ text('Role, company or skill', 'Poste, entreprise ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. marketing, data, engineering', 'ex. marketing, data, ingénierie')" /></span></label>
                        <label><span class="field-label">{{ text('Opportunity type', 'Type d’opportunité') }}</span><span class="field"><Briefcase aria-hidden="true" /><select name="kind"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></span></label>
                        <button class="cta cta-primary focus-ring" type="submit">{{ text('Search opportunities', 'Rechercher') }}</button>
                    </div>
                </form>
                <div class="chip-row">
                    <p class="chip-row-label">{{ text('Where to work:', 'Où travailler :') }}</p>
                    <nav class="flex flex-wrap gap-2.5" :aria-label="text('Locations and work options', 'Lieux et modes de travail')"><Link v-for="item in shortcuts" :key="item.path" :href="`${prefix}/opportunities/${item.path}`" class="chip chip-ice focus-ring"><component :is="item.icon" aria-hidden="true" />{{ item.label }}<ArrowRight aria-hidden="true" /></Link></nav>
                </div>
            </section>

            <section class="public-container section" aria-labelledby="latest-title">
                <div class="section-head"><div><p class="landing-label">{{ text('What’s next', 'La suite de votre parcours') }}</p><h2 id="latest-title" class="section-title">{{ text('Recent opportunities', 'Les dernières opportunités') }}</h2></div><Link :href="`${prefix}/opportunities`" class="landing-link focus-ring">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link></div>
                <div v-if="listings.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-if="listings.length" class="mt-6 flex items-start gap-2.5 text-[14.5px] leading-6 text-slate-600"><ShieldCheck class="mt-0.5 size-[18px] shrink-0 text-brand" aria-hidden="true" />{{ text('Every listing shows its source, where to apply and whether you can work from Tunisia. Details the source doesn’t give are marked as unknown.', 'Chaque offre indique sa source, où candidater et si vous pouvez travailler depuis la Tunisie. Les détails que la source ne donne pas sont indiqués comme inconnus.') }}</p>
                <p v-else class="panel p-8 text-slate-600">{{ text('There are no active opportunities to show right now. Employer posts and approved source listings appear here when available.', 'Aucune opportunité active à afficher pour le moment. Les offres des employeurs et des sources autorisées apparaissent ici lorsqu’elles sont disponibles.') }}</p>
            </section>

            <section class="band-pale section-tight" :aria-label="text('Students and professionals', 'Étudiants et professionnels')">
                <div class="public-container audience-grid">
                    <article v-for="item in audiences" :key="item.key" class="audience-card" :class="{ 'is-navy': item.key === 'professionals' }">
                        <img :src="`/nextward-assets/mascots/${item.art}.svg`" alt="" width="768" height="768" loading="lazy" class="audience-art" />
                        <p class="landing-label">{{ item.label }}</p>
                        <h2 class="audience-title">{{ item.title }}</h2>
                        <p class="audience-body">{{ item.body }}</p>
                        <Link :href="item.href" class="landing-link focus-ring mt-6">{{ item.link }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    </article>
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

            <section class="public-container" aria-labelledby="employers-title">
                <div class="employer-card">
                    <img class="employer-art" src="/nextward-assets/mascots/21-cozy-career-workspace-night.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
                    <div class="employer-copy">
                        <p class="landing-label">{{ text('For employers', 'Pour les employeurs') }}</p>
                        <h2 id="employers-title" class="section-title">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2>
                        <p class="section-description">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p>
                        <Link :href="currentUser?.userable_type === 'company' ? '/internships/create' : `${prefix}/register?account=company`" class="cta cta-primary focus-ring mt-8">{{ text('Post an opportunity', 'Publier une opportunité') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    </div>
                </div>
            </section>

            <section v-if="posts.length" class="public-container section" aria-labelledby="guides-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p><h2 id="guides-title" class="section-title">{{ text('A little guidance goes a long way.', 'Des conseils pour avancer.') }}</h2></div>
                    <Link :href="`${prefix}/blog`" class="landing-link focus-ring">{{ text('Explore career guides', 'Voir les guides carrière') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div class="guide-grid is-three"><GuideCard v-for="post in posts" :key="post.slug" :post="post" /></div>
            </section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.audience-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
.audience-card { position: relative; overflow: hidden; padding: 32px; border: 1px solid var(--color-line); border-radius: 18px; background: #fff; }
.audience-card.is-navy { border-color: var(--color-navy); background: var(--color-navy); color: #fff; }
.audience-card.is-navy .landing-label { color: #b9d0ff; }
.audience-card.is-navy .audience-body { color: #c5cddc; }
.audience-card.is-navy .landing-link { color: #b9d0ff; }
.audience-card.is-navy .landing-link:hover { color: #fff; }
.audience-card.is-navy .focus-ring:focus-visible { outline-color: #fff; }
.audience-art { float: right; width: 128px; height: 128px; margin: -16px -12px 8px 16px; }
.audience-title { margin-top: 12px; font-size: clamp(26px, 2.6vw, 34px); font-weight: 700; line-height: 1.12; letter-spacing: -.045em; }
.audience-body { max-width: 440px; margin-top: 12px; color: #586171; font-size: 15px; line-height: 1.75; }
.how-section { scroll-margin-top: 100px; padding-block: 88px 72px; }
.employer-card { position: relative; display: flex; align-items: center; aspect-ratio: 1672 / 941; overflow: hidden; border-radius: 22px; background: #0d2353; color: #fff; }
.employer-card .landing-label { color: #b9d0ff; }
.employer-card .section-description { color: #c5cddc; }
.employer-card .focus-ring:focus-visible { outline-color: #fff; }
.employer-art { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: right bottom; }
.employer-copy { position: relative; width: 48%; max-width: 580px; padding: 48px 48px 48px 80px; }
@media (max-width: 1023px) {
    .audience-grid { grid-template-columns: 1fr; }
    .employer-card { flex-direction: column; align-items: stretch; aspect-ratio: auto; }
    .employer-copy { order: -1; width: auto; max-width: none; padding: 40px 32px 8px; }
    .employer-art { position: static; height: auto; aspect-ratio: 16 / 9; object-position: 85% 100%; }
}
@media (max-width: 767px) {
    .audience-card { padding: 26px 22px; }
    .audience-art { width: 92px; height: 92px; }
    .how-section { padding-block: 56px; }
    .employer-card { border-radius: 18px; }
    .employer-copy { padding: 32px 24px 0; }
    .employer-art { aspect-ratio: 4 / 3; }
}
</style>
