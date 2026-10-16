<script setup>
import { computed } from 'vue';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import Sparkle from '@/Shared/Sparkle.vue';
const props = defineProps({ locale: { type: String, default: 'en' }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, posts: { type: Array, default: () => [] } });
const listings = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const fr = computed(() => props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const shortcuts = computed(() => [
    { path: 'tunisia', label: text('In Tunisia', 'En Tunisie') },
    { path: 'remote-from-tunisia', label: text('Remote from Tunisia', 'À distance depuis la Tunisie') },
    { path: 'relocation', label: text('Abroad with relocation', 'À l’étranger avec aide à la mobilité') },
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
            <section aria-labelledby="hero-title" class="hero-dark">
                <div class="hero-grid home-hero public-container">
                    <div>
                        <p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('For students and professionals', 'Pour les étudiants et les professionnels') }}</p>
                        <h1 id="hero-title" class="hero-title">{{ text('Find your next', 'Trouvez votre prochaine') }} <span class="text-blue-300">{{ text('opportunity.', 'opportunité.') }}</span></h1>
                        <p class="hero-lead">{{ text('Nextward brings internships and jobs in Tunisia and worldwide into one place. Find your first role or your next career move.', 'Nextward réunit des stages et des emplois en Tunisie et dans le monde. Trouvez votre première expérience ou votre prochaine étape professionnelle.') }}</p>
                        <div class="hero-buttons"><Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Explore opportunities', 'Explorer les opportunités') }}<Icon name="arrow-up-right" class="action-icon" /></Link><a href="#how-it-works" class="landing-link focus-ring text-white hover:!text-blue-200">{{ text('See how it works', 'Comment ça marche') }}</a></div>
                        <p class="hero-note"><Icon name="check-circle" aria-hidden="true" />{{ text('No account needed to explore or visit external application pages.', 'Aucun compte nécessaire pour consulter les offres ou candidater sur un site externe.') }}</p>
                    </div>
                    <figure class="hero-stage hero-duo">
                        <img src="/nextward-assets/mascots/17-student-presenter.webp" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                        <img src="/nextward-assets/mascots/18-recruiter-mentor.webp" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                        <Sparkle style="top: 8%; left: 14%; --i: 0" />
                        <Sparkle class="is-sky" style="top: 2%; right: 18%; width: 16px; height: 16px; --i: 1" />
                        <Sparkle style="bottom: 30%; right: 2%; width: 14px; height: 14px; --i: 2" />
                    </figure>
                </div>
            </section>
            <section class="public-container relative z-10 -mt-16 pb-14" :aria-label="text('Search opportunities', 'Rechercher des opportunités')">
                <form method="get" :action="`${prefix}/opportunities`" class="grid md:grid-cols-[2fr_1fr_1fr_auto] gap-4 rounded-2xl bg-white border border-line p-5">
                    <label class="text-sm font-semibold">{{ text('Keyword', 'Mot-clé') }}<input name="search" type="search" maxlength="150" :placeholder="text('Role, company, or skill', 'Poste, entreprise ou compétence')" class="mt-2 w-full rounded-lg border-line" /></label>
                    <label class="text-sm font-semibold">{{ text('Opportunity type', 'Type d’opportunité') }}<select name="kind" class="mt-2 w-full rounded-lg border-line"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></label>
                    <label class="text-sm font-semibold">{{ text('Location', 'Lieu') }}<select name="country" class="mt-2 w-full rounded-lg border-line"><option value="">{{ text('Worldwide', 'Monde entier') }}</option><option value="TN">{{ text('Tunisia', 'Tunisie') }}</option></select></label>
                    <button class="cta cta-primary self-end" type="submit">{{ text('Search', 'Rechercher') }}</button>
                </form>
                <nav class="mt-5 flex flex-wrap gap-3" :aria-label="text('Locations and work options', 'Lieux et modes de travail')"><Link v-for="item in shortcuts" :key="item.path" :href="`${prefix}/opportunities/${item.path}`" class="rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-blue-700">{{ item.label }}</Link></nav>
            </section>
            <section class="public-container grid md:grid-cols-2 gap-6 pb-16">
                <article class="audience-card rounded-2xl bg-white border border-line p-8">
                    <img src="/nextward-assets/mascots/11-learning-books-and-cap.svg" alt="" width="768" height="768" loading="lazy" class="audience-art" />
                    <p class="landing-label">{{ text('For students', 'Pour les étudiants') }}</p>
                    <h2 class="section-title">{{ text('Make your first move.', 'Faites le premier pas.') }}</h2>
                    <p class="section-description">{{ text('Explore PFE and summer internships, graduate programmes, and first jobs. Find the experience that helps you grow.', 'Explorez les PFE, les stages d’été, les programmes jeunes diplômés et les premiers emplois. Trouvez une expérience pour progresser.') }}</p>
                    <Link :href="`${prefix}/for-students`" class="landing-link mt-6 text-blue-700">{{ text('Explore the student section', 'Découvrir l’espace étudiants') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                </article>
                <article class="audience-card rounded-2xl bg-navy text-white p-8">
                    <img src="/nextward-assets/mascots/10-portfolio-laptop.svg" alt="" width="768" height="768" loading="lazy" class="audience-art" />
                    <p class="landing-label text-blue-200">{{ text('For professionals', 'Pour les professionnels') }}</p>
                    <h2 class="section-title">{{ text('Build on your experience.', 'Valorisez votre expérience.') }}</h2>
                    <p class="section-description !text-slate-300">{{ text('Find full-time, part-time, contract, and freelance roles. Explore local hiring, remote work, and opportunities abroad.', 'Trouvez des postes à temps plein, à temps partiel, en contrat et en freelance. Explorez le marché local, le télétravail et les offres à l’étranger.') }}</p>
                    <Link :href="`${prefix}/for-professionals`" class="landing-link mt-6 text-blue-200 hover:!text-white">{{ text('Explore the professional section', 'Découvrir l’espace professionnels') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                </article>
            </section>
            <section class="public-container pb-16" aria-labelledby="latest-title">
                <div class="flex flex-wrap gap-4 justify-between items-end mb-7"><div><p class="landing-label">{{ text('What’s next', 'La suite de votre parcours') }}</p><h2 id="latest-title" class="section-title">{{ text('Recent opportunities', 'Les dernières opportunités') }}</h2></div><Link :href="`${prefix}/opportunities`" class="landing-link text-blue-700">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}</Link></div>
                <div v-if="listings.length" class="grid lg:grid-cols-2 gap-4"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="rounded-2xl border border-line bg-white p-8 text-slate-600">{{ text('There are no active opportunities to show right now. Employer posts and approved source listings appear here when available.', 'Aucune opportunité active à afficher pour le moment. Les offres des employeurs et des sources autorisées apparaissent ici lorsqu’elles sont disponibles.') }}</p>
            </section>
            <section id="how-it-works" class="how-section public-container">
                <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                <h2 class="section-title">{{ text('Find your fit. Keep moving.', 'Trouvez votre place. Avancez.') }}</h2>
                <ol class="steps">
                    <li v-for="(step, index) in steps" :key="step.art">
                        <img :src="`/nextward-assets/mascots/${step.art}.svg`" alt="" width="768" height="768" loading="lazy" class="step-art" />
                        <div class="flex gap-4"><span class="step-number">0{{ index + 1 }}</span><div><h3>{{ step.title }}</h3><p>{{ step.body }}</p></div></div>
                    </li>
                </ol>
            </section>
            <section class="public-container py-16 grid md:grid-cols-2 gap-10"><div><p class="landing-label">{{ text('Know what you’re applying for', 'Comprenez chaque candidature') }}</p><h2 class="section-title">{{ text('Clear sources. Useful details.', 'Des sources claires. Des détails utiles.') }}</h2></div><div class="space-y-4 text-slate-600 leading-7"><p>{{ text('Explore employer posts and opportunities from approved sources. See the original listing and where to apply before you share your information. Source dates show the last successful check; availability can change.', 'Explorez les offres des employeurs et des sources autorisées. Consultez l’annonce originale et le site de candidature avant de partager vos informations. Les dates indiquent le dernier contrôle réussi de la source ; la disponibilité peut évoluer.') }}</p><p>{{ text('Remote does not always mean worldwide. Unknown eligibility means the source has not confirmed where applicants may work. Relocation support and visa sponsorship are shown separately.', 'Le télétravail n’est pas toujours accessible partout. Une éligibilité inconnue signifie que la source ne confirme pas les pays autorisés. L’aide à la mobilité et le parrainage de visa sont indiqués séparément.') }}</p></div></section>
            <section class="employer-section">
                <img class="employer-art" src="/nextward-assets/mascots/19-cozy-career-workspace.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
                <div class="public-container employer-content">
                    <div class="employer-copy">
                        <p class="landing-label text-blue-700">{{ text('For employers', 'Pour les employeurs') }}</p>
                        <h2 class="section-title">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2>
                        <p class="section-description">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p>
                        <Link :href="currentUser?.userable_type === 'company' ? '/internships/create' : `${prefix}/register?account=company`" class="cta cta-primary focus-ring mt-8">{{ text('Post an opportunity', 'Publier une opportunité') }}</Link>
                    </div>
                </div>
            </section>
            <section v-if="posts.length" class="guides-section public-container"><p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p><h2 class="section-title">{{ text('A little guidance goes a long way.', 'Des conseils pour avancer.') }}</h2><div class="guide-list"><Link v-for="post in posts" :key="post.slug" :href="post.path" class="guide-row focus-ring" :lang="post.locale"><span class="guide-category">{{ post.category }}</span><div><h3>{{ post.title }}</h3><p>{{ post.excerpt }}</p></div><span class="guide-time">{{ post.read_time }} {{ text('min read', 'min de lecture') }}</span><Icon name="arrow-up-right" class="guide-arrow" /></Link></div></section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.home-hero { padding-bottom: 136px; }
.hero-note { display: flex; align-items: center; gap: 7px; margin-top: 24px; font-size: 12px; color: #a9b4c8; }
.hero-note svg { width: 15px; height: 15px; color: #8ab2ff; }
.hero-duo { display: flex; align-items: flex-end; }
.hero-duo::before { inset: 20% 2% 4%; }
.hero-duo img { width: 64%; }
.hero-duo img + img { margin-left: -24%; }
.audience-card { position: relative; overflow: hidden; }
.audience-art { float: right; width: 128px; height: 128px; margin: -18px -14px 8px 16px; }
.how-section { scroll-margin-top: 100px; padding-block: 40px 56px; border-top: 1px solid #0a17381a; }
.steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 36px; }
.step-art { width: 112px; height: 112px; margin: 0 0 12px -12px; }
.step-number { color: var(--color-brand); font-family: var(--font-display); font-size: 15px; line-height: 1.7; }
.steps h3 { font-size: 18px; font-weight: 600; letter-spacing: -.025em; }
.steps p { margin-top: 9px; font-size: 13px; line-height: 1.8; color: #586171; }
.employer-section { position: relative; overflow: hidden; background: #f5f8fe; border-block: 1px solid #0a17380f; }
.employer-art { position: absolute; right: 0; bottom: 0; height: 100%; width: auto; max-width: none; -webkit-mask-image: linear-gradient(to right, transparent, #000 32%); mask-image: linear-gradient(to right, transparent, #000 32%); }
.employer-content { position: relative; display: flex; align-items: center; min-height: 520px; padding-block: 80px; }
.employer-copy { max-width: 470px; }
.guides-section { padding-block: 90px; }
@media (max-width: 1023px) {
    .hero-duo img { width: 62%; }
    .steps { gap: 24px; }
    .employer-art { opacity: .55; }
}
@media (max-width: 767px) {
    .home-hero { padding-bottom: 110px; }
    .audience-art { width: 96px; height: 96px; }
    .steps { grid-template-columns: 1fr; gap: 28px; margin-top: 30px; }
    .step-art { width: 88px; height: 88px; }
    .employer-section { display: flex; flex-direction: column-reverse; }
    .employer-art { position: static; width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover; object-position: 85% 100%; opacity: 1; -webkit-mask-image: none; mask-image: none; }
    .employer-content { min-height: 0; padding-block: 56px 8px; }
    .guides-section { padding-block: 60px; }
}
</style>
