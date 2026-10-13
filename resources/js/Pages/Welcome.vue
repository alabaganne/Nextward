<script setup>
import { computed } from 'vue';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
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
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <section aria-labelledby="hero-title" class="hero public-container">
                <div class="hero-copy">
                    <p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('For students and professionals', 'Pour les étudiants et les professionnels') }}</p>
                    <h1 id="hero-title">{{ text('Find your next', 'Trouvez votre prochaine') }} <span>{{ text('opportunity.', 'opportunité.') }}</span></h1>
                    <p class="hero-description">{{ text('Nextward brings internships and jobs in Tunisia and worldwide into one place. Find your first role or your next career move.', 'Nextward réunit des stages et des emplois en Tunisie et dans le monde. Trouvez votre première expérience ou votre prochaine étape professionnelle.') }}</p>
                    <div class="hero-actions"><Link :href="`${prefix}/opportunities`" class="cta cta-primary focus-ring">{{ text('Explore opportunities', 'Explorer les opportunités') }}<Icon name="arrow-up-right" class="action-icon" /></Link><a href="#how-it-works" class="landing-link focus-ring">{{ text('See how it works', 'Comment ça marche') }}</a></div>
                    <p class="hero-note"><Icon name="check-circle" aria-hidden="true" />{{ text('No account needed to explore or visit external application pages.', 'Aucun compte nécessaire pour consulter les offres ou candidater sur un site externe.') }}</p>
                </div>
                <figure class="hero-art"><img src="/nextward-assets/landing/career-steps.webp" alt="" width="1254" height="1254" fetchpriority="high" decoding="async" /><figcaption>{{ text('Your next move starts here.', 'Votre prochaine étape commence ici.') }}</figcaption></figure>
            </section>
            <section class="public-container pb-14" :aria-label="text('Search opportunities', 'Rechercher des opportunités')">
                <form method="get" :action="`${prefix}/opportunities`" class="grid md:grid-cols-[2fr_1fr_1fr_auto] gap-4 rounded-2xl bg-white border border-line p-5">
                    <label class="text-sm font-semibold">{{ text('Keyword', 'Mot-clé') }}<input name="search" type="search" maxlength="150" :placeholder="text('Role, company, or skill', 'Poste, entreprise ou compétence')" class="mt-2 w-full rounded-lg border-line" /></label>
                    <label class="text-sm font-semibold">{{ text('Opportunity type', 'Type d’opportunité') }}<select name="kind" class="mt-2 w-full rounded-lg border-line"><option value="">{{ text('Internships and jobs', 'Stages et emplois') }}</option><option value="internship">{{ text('Internships', 'Stages') }}</option><option value="job">{{ text('Jobs', 'Emplois') }}</option></select></label>
                    <label class="text-sm font-semibold">{{ text('Location', 'Lieu') }}<select name="country" class="mt-2 w-full rounded-lg border-line"><option value="">{{ text('Worldwide', 'Monde entier') }}</option><option value="TN">{{ text('Tunisia', 'Tunisie') }}</option></select></label>
                    <button class="cta cta-primary self-end" type="submit">{{ text('Search', 'Rechercher') }}</button>
                </form>
                <nav class="mt-5 flex flex-wrap gap-3" :aria-label="text('Locations and work options', 'Lieux et modes de travail')"><Link v-for="item in shortcuts" :key="item.path" :href="`${prefix}/opportunities/${item.path}`" class="rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-blue-700">{{ item.label }}</Link></nav>
            </section>
            <section class="public-container grid md:grid-cols-2 gap-6 pb-16">
                <article class="rounded-2xl bg-white border border-line p-8"><p class="landing-label">{{ text('For students', 'Pour les étudiants') }}</p><h2 class="section-title">{{ text('Make your first move.', 'Faites le premier pas.') }}</h2><p class="section-description">{{ text('Explore PFE and summer internships, graduate programmes, and first jobs. Find the experience that helps you grow.', 'Explorez les PFE, les stages d’été, les programmes jeunes diplômés et les premiers emplois. Trouvez une expérience pour progresser.') }}</p><Link :href="`${prefix}/for-students`" class="landing-link mt-6 text-blue-700">{{ text('Explore the student section', 'Découvrir l’espace étudiants') }}<Icon name="arrow-up-right" class="action-icon" /></Link></article>
                <article class="rounded-2xl bg-navy text-white p-8"><p class="landing-label text-blue-200">{{ text('For professionals', 'Pour les professionnels') }}</p><h2 class="section-title">{{ text('Build on your experience.', 'Valorisez votre expérience.') }}</h2><p class="section-description !text-slate-300">{{ text('Find full-time, part-time, contract, and freelance roles. Explore local hiring, remote work, and opportunities abroad.', 'Trouvez des postes à temps plein, à temps partiel, en contrat et en freelance. Explorez le marché local, le télétravail et les offres à l’étranger.') }}</p><Link :href="`${prefix}/for-professionals`" class="landing-link mt-6 text-blue-200">{{ text('Explore the professional section', 'Découvrir l’espace professionnels') }}<Icon name="arrow-up-right" class="action-icon" /></Link></article>
            </section>
            <section class="public-container pb-16" aria-labelledby="latest-title">
                <div class="flex flex-wrap gap-4 justify-between items-end mb-7"><div><p class="landing-label">{{ text('What’s next', 'La suite de votre parcours') }}</p><h2 id="latest-title" class="section-title">{{ text('Recent opportunities', 'Les dernières opportunités') }}</h2></div><Link :href="`${prefix}/opportunities`" class="landing-link text-blue-700">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}</Link></div>
                <div v-if="listings.length" class="grid lg:grid-cols-2 gap-4"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="rounded-2xl border border-line bg-white p-8 text-slate-600">{{ text('There are no active opportunities to show right now. Employer posts and approved source listings appear here when available.', 'Aucune opportunité active à afficher pour le moment. Les offres des employeurs et des sources autorisées apparaissent ici lorsqu’elles sont disponibles.') }}</p>
            </section>
            <section id="how-it-works" class="how-section public-container"><p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p><h2 class="section-title">{{ text('Find your fit. Keep moving.', 'Trouvez votre place. Avancez.') }}</h2><ol class="steps"><li><span class="step-number">01</span><div><h3>{{ text('Discover and save', 'Découvrez et sauvegardez') }}</h3><p>{{ text('Browse roles, check their requirements, and save the opportunities you want to revisit.', 'Consultez les offres et leurs critères, puis sauvegardez les opportunités qui vous intéressent.') }}</p></div></li><li><span class="step-number">02</span><div><h3>{{ text('Apply at the right place', 'Candidatez au bon endroit') }}</h3><p>{{ text('Apply on Nextward for employer-managed roles, or follow the link to the employer or original source.', 'Candidatez sur Nextward pour les offres gérées ici, ou suivez le lien vers l’employeur ou la source originale.') }}</p></div></li><li><span class="step-number">03</span><div><h3>{{ text('Track your progress', 'Suivez vos démarches') }}</h3><p>{{ text('Keep applications together. For external roles, record “I applied” and update your own progress.', 'Regroupez vos candidatures. Pour les offres externes, indiquez votre candidature et mettez vous-même votre suivi à jour.') }}</p></div></li></ol></section>
            <section class="public-container py-16 grid md:grid-cols-2 gap-10"><div><p class="landing-label">{{ text('Know what you’re applying for', 'Comprenez chaque candidature') }}</p><h2 class="section-title">{{ text('Clear sources. Useful details.', 'Des sources claires. Des détails utiles.') }}</h2></div><div class="space-y-4 text-slate-600 leading-7"><p>{{ text('Explore employer posts and opportunities from approved sources. See the original listing and where to apply before you share your information. Source dates show the last successful check; availability can change.', 'Explorez les offres des employeurs et des sources autorisées. Consultez l’annonce originale et le site de candidature avant de partager vos informations. Les dates indiquent le dernier contrôle réussi de la source ; la disponibilité peut évoluer.') }}</p><p>{{ text('Remote does not always mean worldwide. Unknown eligibility means the source has not confirmed where applicants may work. Relocation support and visa sponsorship are shown separately.', 'Le télétravail n’est pas toujours accessible partout. Une éligibilité inconnue signifie que la source ne confirme pas les pays autorisés. L’aide à la mobilité et le parrainage de visa sont indiqués séparément.') }}</p></div></section>
            <section class="company-section"><img class="company-background" src="/nextward-assets/landing/workspace.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" /><div class="company-overlay"></div><div class="public-container company-content"><div class="company-copy"><p class="landing-label">{{ text('For employers', 'Pour les employeurs') }}</p><h2 class="section-title">{{ text('Hire students and professionals.', 'Recrutez des étudiants et des professionnels.') }}</h2><p class="section-description">{{ text('Publish an internship or a job. Introduce your company, review applications, and keep candidate conversations in one place.', 'Publiez un stage ou un emploi. Présentez votre entreprise, examinez les candidatures et centralisez les échanges avec les candidats.') }}</p><Link :href="currentUser?.userable_type === 'company' ? '/internships/create' : '/register?account=company'" class="cta cta-light mt-8">{{ text('Post an opportunity', 'Publier une opportunité') }}</Link></div></div></section>
            <section v-if="posts.length" class="guides-section public-container"><p class="landing-label">{{ text('Career guides', 'Guides carrière en anglais') }}</p><h2 class="section-title">{{ text('A little guidance goes a long way.', 'Des conseils pour avancer.') }}</h2><div class="guide-list"><Link v-for="post in posts" :key="post.slug" :href="`/blog/${post.slug}`" class="guide-row focus-ring" lang="en"><span class="guide-category">{{ post.category }}</span><div><h3>{{ post.title }}</h3><p>{{ post.excerpt }}</p></div><span class="guide-time">{{ post.read_time }}</span><Icon name="arrow-up-right" class="guide-arrow" /></Link></div></section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.landing { background: #faf9f6; color: var(--color-navy); }
.landing p { white-space: normal; }
.landing h1, .landing h2, .landing h3 { font-family: var(--font-display); }
.landing-label { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 600; letter-spacing: .15em; text-transform: uppercase; }
.label-dot { height: 7px; width: 7px; border-radius: 50%; background: var(--color-brand); }
.landing-link { display: inline-flex; align-items: center; gap: 15px; padding-block: 5px; font-size: 14px; font-weight: 600; border-radius: 2px; text-decoration: none; transition: color 180ms; }
.landing-link:hover { color: var(--color-brand); }
.link-label { text-decoration: underline; text-decoration-color: #0a173830; text-underline-offset: 6px; transition: text-decoration-color 180ms; }
.landing-link:hover .link-label { text-decoration-color: currentColor; }
.action-icon { width: 18px; height: 18px; flex-shrink: 0; }
.hero { display: grid; grid-template-columns: 1.1fr 1fr; align-items: center; gap: 24px; padding-block: 76px 70px; }
.hero h1 { margin-top: 25px; font-size: clamp(54px, 5.7vw, 80px); font-weight: 600; line-height: 1.05; letter-spacing: -.065em; }
.hero h1 > span { color: var(--color-brand); }
.hero-description { max-width: 470px; margin-top: 25px; font-size: 17px; line-height: 1.8; color: #586171; }
.hero-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 24px; margin-top: 30px; }
.hero .cta { padding: 15px 23px; font-size: 14px; }
.hero-note { display: flex; align-items: center; gap: 7px; margin-top: 22px; font-size: 12px; color: #67707e; }
.hero-note svg { width: 15px; height: 15px; color: var(--color-brand); }
.hero-art { margin: 0; }
.hero-art img { width: 100%; height: auto; object-fit: contain; }
.hero-art figcaption { margin-top: 4px; text-align: center; font-size: 11px; letter-spacing: .035em; color: #67707e; }
.how-section { scroll-margin-top: 100px; padding-block: 35px 50px; border-top: 1px solid #0a17381a; }
.how-heading { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.how-heading h2 { font-size: 19px; font-weight: 500; letter-spacing: -.035em; }
.steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 40px; }
.steps li { display: flex; align-items: flex-start; gap: 18px; }
.step-number { color: var(--color-brand); font-family: var(--font-display); font-size: 15px; line-height: 1.7; }
.steps h3 { font-size: 18px; font-weight: 600; letter-spacing: -.025em; }
.steps p { margin-top: 9px; font-size: 13px; line-height: 1.8; color: #586171; }
.student-section { background: #fff; padding-block: 100px; border-top: 1px solid #0a173810; }
.student-grid { display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 90px; }
.section-title { margin-top: 19px; font-size: clamp(32px, 3.4vw, 46px); font-weight: 500; line-height: 1.16; letter-spacing: -.05em; }
.section-description { max-width: 460px; margin-top: 20px; color: #586171; font-size: 15px; line-height: 1.85; }
.feature-list { display: grid; gap: 24px; margin-block: 32px; }
.feature-list li { display: flex; gap: 14px; }
.feature-list svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: 3px; color: var(--color-brand); }
.feature-list h3 { font-family: var(--font-sans); font-size: 14px; font-weight: 600; }
.feature-list p { max-width: 400px; margin-top: 5px; font-size: 13px; line-height: 1.8; color: #586171; }
.application-preview { background: #f1f4f9; border-radius: 12px; padding: 32px; }
.preview-caption { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding-bottom: 30px; font-size: 11px; font-weight: 500; }
.preview-caption span { color: #67707e; font-size: 10px; }
.preview-heading { display: flex; align-items: center; gap: 12px; padding-bottom: 23px; }
.preview-heading svg { color: var(--color-brand); width: 22px; height: 22px; }
.preview-heading h3 { font-size: 22px; font-weight: 500; letter-spacing: -.035em; }
.application-list li { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding-block: 25px; border-top: 1px solid #0a17381a; }
.application-title { font-size: 13px; font-weight: 600; }
.application-detail { margin-top: 6px; color: #67707e; font-size: 11px; }
.application-status { flex-shrink: 0; padding: 5px 9px; border-radius: 5px; font-size: 10px; font-weight: 500; }
.status-review { background: #e2eaf8; color: #345283; }
.status-offer { background: #eee7f8; color: #684398; }
.preview-note { display: flex; align-items: center; gap: 7px; padding-top: 23px; border-top: 1px solid #0a17381a; color: #67707e; font-size: 11px; }
.preview-note svg { width: 15px; height: 15px; }
.company-section { position: relative; overflow: hidden; background: #0a1730; color: #fff; }
.company-background, .company-overlay { position: absolute; inset: 0; width: 100%; height: 100%; }
.company-background { object-fit: cover; object-position: center; }
.company-overlay { background: linear-gradient(90deg, rgb(8 20 43 / .98) 0%, rgb(8 20 43 / .93) 35%, rgb(8 20 43 / .58) 70%, rgb(8 20 43 / .38) 100%); }
.company-content { position: relative; display: flex; justify-content: space-between; align-items: end; gap: 60px; padding-block: 90px; }
.company-copy { max-width: 560px; }
.company-section .landing-label { color: #b9cefa; }
.company-section .section-title { font-size: clamp(36px, 3.8vw, 52px); }
.company-section .section-description, .company-section .feature-list p { color: #c5cddc; }
.company-section .feature-list svg { color: #a5c1ff; }
.company-aside { margin-bottom: 5px; color: #fff; font-family: var(--font-display); font-size: 24px; font-weight: 400; line-height: 1.4; letter-spacing: -.025em; text-align: right; }
.company-section .focus-ring:focus-visible, .closing-section .focus-ring:focus-visible { outline-color: #fff; }
.guides-section { padding-block: 90px; }
.section-heading { display: flex; justify-content: space-between; align-items: end; gap: 30px; }
.section-heading .section-title { max-width: 650px; }
.section-heading > .landing-link { flex-shrink: 0; }
.guide-list { margin-top: 40px; }
.guide-row { display: grid; grid-template-columns: 110px 1fr 90px 24px; align-items: center; gap: 24px; padding-block: 28px; border-top: 1px solid #0a17381a; transition: color 180ms; }
.guide-row:last-child { border-bottom: 1px solid #0a17381a; }
.guide-row:hover h3, .guide-row:hover .guide-arrow { color: var(--color-brand); }
.guide-category { font-size: 11px; font-weight: 600; color: var(--color-brand); }
.guide-row h3 { font-size: 21px; font-weight: 500; line-height: 1.4; letter-spacing: -.025em; }
.guide-row p { max-width: 650px; margin-top: 8px; font-size: 13px; line-height: 1.75; color: #67707e; }
.guide-time { color: #67707e; font-size: 11px; text-align: right; }
.guide-arrow { width: 22px; height: 22px; justify-self: end; }
.faq-section { display: grid; grid-template-columns: .85fr 1.15fr; gap: 80px; padding-block: 0 90px; }
.faq-section .section-description { margin-block: 22px 6px; }
.faq-list { border-top: 1px solid #0a17381a; }
.faq-item { border-bottom: 1px solid #0a17381a; }
.faq-item summary { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding-block: 22px; cursor: pointer; font-size: 14px; font-weight: 500; list-style: none; }
.faq-item summary::-webkit-details-marker { display: none; }
.faq-toggle { flex-shrink: 0; width: 18px; height: 18px; color: #67707e; transition: transform 180ms; }
.faq-item[open] .faq-toggle { transform: rotate(45deg); }
.faq-item > p { max-width: 550px; padding: 0 25px 24px 0; font-size: 13px; line-height: 1.85; color: #586171; }
.closing-section { background: var(--color-brand); color: #fff; }
.closing-content { display: flex; align-items: center; justify-content: space-between; gap: 40px; padding-block: 65px; }
.closing-section .landing-label { color: #d3e1ff; }
.closing-section .section-title { max-width: 660px; margin-top: 14px; }
.closing-content > div > p:last-child { margin-top: 12px; color: #d3e1ff; font-size: 18px; }
.closing-actions { display: flex; flex-shrink: 0; flex-direction: column; align-items: center; gap: 16px; }
.closing-actions .landing-link { font-size: 12px; }
.closing-actions .link-label { text-decoration-color: #ffffff60; }
.closing-actions .landing-link:hover { color: #fff; }
.closing-actions .landing-link:hover .link-label { text-decoration-color: #fff; }
@media (max-width: 1023px) {
	.hero { padding-block: 55px; }
	.hero h1 { font-size: clamp(48px, 6.4vw, 68px); }
	.hero-description { font-size: 15px; }
	.hero-actions { gap: 16px; }
	.hero-actions .landing-link { font-size: 12px; }
	.steps { gap: 24px; }
	.student-grid { gap: 45px; }
	.application-preview { padding: 24px; }
	.application-list li { flex-wrap: wrap; gap: 10px; padding-block: 20px; }
	.company-aside { max-width: 220px; font-size: 20px; }
	.faq-section { gap: 45px; }
	.guide-row { grid-template-columns: 85px 1fr 75px 20px; gap: 18px; }
}
@media (max-width: 767px) {
	.hero { grid-template-columns: 1fr; gap: 25px; padding-block: 48px 35px; }
	.hero h1 { font-size: clamp(46px, 10.5vw, 74px); }
	.hero-description { max-width: 510px; }
	.hero-art { width: min(100%, 420px); justify-self: center; }
	.hero-art figcaption { margin-top: -5px; }
	.how-section { padding-block: 28px 40px; }
	.how-heading { align-items: start; flex-direction: column; gap: 12px; }
	.how-heading h2 { font-size: 20px; }
	.steps { grid-template-columns: 1fr; gap: 28px; margin-top: 30px; }
	.steps li { gap: 20px; }
	.steps p { max-width: 450px; }
	.student-section { padding-block: 60px; }
	.student-grid { grid-template-columns: 1fr; gap: 35px; }
	.application-preview { padding: 25px; }
	.preview-caption { padding-bottom: 25px; }
	.application-list li { flex-wrap: nowrap; }
	.company-content { padding-block: 65px; }
	.company-overlay { background: rgb(8 20 43 / .88); }
	.company-background { object-position: 65% center; }
	.company-aside { display: none; }
	.guides-section { padding-block: 60px; }
	.section-heading { flex-direction: column; align-items: start; gap: 20px; }
	.guide-list { margin-top: 30px; }
	.guide-row { grid-template-columns: 1fr auto; gap: 12px 20px; padding-block: 24px; }
	.guide-category { grid-column: 1; }
	.guide-row > div { grid-column: 1; grid-row: 2; }
	.guide-row h3 { font-size: 20px; }
	.guide-time { grid-column: 2; grid-row: 1; }
	.guide-arrow { grid-column: 2; grid-row: 2; align-self: start; }
	.faq-section { grid-template-columns: 1fr; gap: 30px; padding-bottom: 60px; }
	.closing-content { flex-direction: column; align-items: start; gap: 30px; padding-block: 50px; }
	.closing-actions { align-items: start; }
}
@media (prefers-reduced-motion: reduce) {
	.faq-toggle { transition: none; }
}
</style>
