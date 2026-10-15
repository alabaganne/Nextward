<script setup>
import { computed } from 'vue';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import Sparkle from '@/Shared/Sparkle.vue';
const props = defineProps({ locale: { type: String, default: 'en' }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, posts: { type: Array, default: () => [] }, shortcuts: { type: Array, default: () => [] } });
const listings = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const fr = computed(() => props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const href = (shortcut) => `${prefix.value}${shortcut.path}${shortcut.query ? `?${shortcut.query}` : ''}`;
const labels = computed(() => ({
    'full-time': text('Full-time', 'Temps plein'),
    'part-time': text('Part-time', 'Temps partiel'),
    contract: text('Contract', 'Contrat'),
    remote: text('Remote', 'Télétravail'),
    senior: text('Senior roles', 'Postes seniors'),
}));
const chips = computed(() => props.shortcuts.filter((shortcut) => labels.value[shortcut.key]));
const paths = computed(() => {
    const details = {
        tunisia: { title: text('In Tunisia', 'En Tunisie'), body: text('Local employers, with the real workplace and work arrangement on each listing.', 'Des employeurs locaux, avec le lieu de travail et l’organisation réelle de chaque poste.'), link: text('Browse jobs in Tunisia', 'Voir les emplois en Tunisie') },
        'remote-from-tunisia': { title: text('Remote from Tunisia', 'À distance depuis la Tunisie'), body: text('Only roles that accept people based in Tunisia. Remote alone is not a worldwide invitation.', 'Uniquement les postes ouverts aux candidats basés en Tunisie. Le télétravail seul ne signifie pas un accès mondial.'), link: text('Browse remote jobs', 'Voir les emplois à distance') },
        relocation: { title: text('Abroad with relocation', 'À l’étranger avec mobilité'), body: text('Roles outside Tunisia that help you move. Visa sponsorship is shown separately.', 'Des postes hors de Tunisie qui vous aident à déménager. Le parrainage de visa est indiqué à part.'), link: text('Browse relocation jobs', 'Voir les offres avec mobilité') },
    };
    return props.shortcuts.filter((shortcut) => details[shortcut.key]).map((shortcut) => ({ ...shortcut, ...details[shortcut.key] }));
});
const promises = computed(() => [
    { title: text('Unknown stays unknown', 'L’inconnu reste inconnu'), body: text('If the source does not say whether pay, a visa or relocation is offered, we say so instead of guessing.', 'Si la source ne précise pas la rémunération, le visa ou la mobilité, nous l’indiquons au lieu de deviner.') },
    { title: text('Remote, with the fine print', 'Le télétravail, en détail'), body: text('See which countries can apply before you spend time on a role that cannot hire from Tunisia.', 'Voyez quels pays peuvent postuler avant de passer du temps sur un poste fermé à la Tunisie.') },
    { title: text('The original source, always', 'Toujours la source d’origine'), body: text('Every job shows where it comes from and when it was last checked. Apply where the employer wants.', 'Chaque offre indique sa provenance et la date du dernier contrôle. Candidatez là où l’employeur le souhaite.') },
]);
const example = computed(() => [
    { label: text('Work type', 'Mode de travail'), value: text('Remote, full-time', 'Télétravail, temps plein') },
    { label: text('Can apply from', 'Candidats acceptés depuis'), value: text('Tunisia, France, Germany', 'Tunisie, France, Allemagne') },
    { label: text('Relocation', 'Mobilité'), value: text('Not offered', 'Non proposée') },
    { label: text('Visa sponsorship', 'Parrainage de visa'), value: text('Not given by the source', 'Non précisé par la source'), unknown: true },
    { label: text('Pay', 'Rémunération'), value: text('Not given by the source', 'Non précisée par la source'), unknown: true },
    { label: text('Source', 'Source'), value: text('Company careers page, checked 2 days ago', 'Page carrières de l’entreprise, contrôlée il y a 2 jours') },
]);
const tools = computed(() => [
    { art: '07-search-internships', title: text('Save a search', 'Sauvegardez une recherche'), body: text('Keep your filters and choose to get an email when new jobs match. Every email has an unsubscribe link.', 'Gardez vos filtres et recevez un e-mail quand de nouvelles offres correspondent. Chaque e-mail contient un lien de désinscription.') },
    { art: '05-resume-document', title: text('One profile for every move', 'Un profil pour chaque étape'), body: text('Keep your skills, LinkedIn and job preferences in one place, from your first role to your next one.', 'Regroupez vos compétences, votre LinkedIn et vos préférences, de votre premier poste au suivant.') },
    { art: '09-interview-calendar', title: text('Track every application', 'Suivez chaque candidature'), body: text('Mark external jobs as applied and update their status yourself, next to the ones you sent on Nextward.', 'Indiquez vos candidatures externes et mettez leur statut à jour, à côté de celles envoyées sur Nextward.') },
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <section aria-labelledby="professionals-title" class="hero-dark">
                <div class="hero-grid public-container">
                    <div>
                        <p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('Nextward for professionals', 'Nextward pour les professionnels') }}</p>
                        <h1 id="professionals-title" class="hero-title">{{ text('Your next job,', 'Votre prochain poste,') }} <span class="lg:block">{{ text('in Tunisia or abroad.', 'en Tunisie ou à l’étranger.') }}</span></h1>
                        <p class="hero-lead">{{ text('Full-time, part-time, contract and freelance roles from employers and approved sources. Every job shows where you can work from and where to apply.', 'Des postes à temps plein, à temps partiel, en contrat et en freelance, publiés par des employeurs ou des sources autorisées. Chaque offre indique d’où vous pouvez travailler et où candidater.') }}</p>
                        <div class="hero-buttons">
                            <Link :href="`${prefix}/opportunities/jobs`" class="cta cta-primary focus-ring">{{ text('Explore jobs', 'Explorer les emplois') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                            <Link :href="`${prefix}/opportunities/remote-from-tunisia`" class="cta cta-ghost focus-ring">{{ text('Remote jobs from Tunisia', 'Télétravail depuis la Tunisie') }}</Link>
                        </div>
                        <nav v-if="chips.length" class="hero-chips" :aria-label="text('Job types', 'Types de postes')">
                            <Link v-for="shortcut in chips" :key="shortcut.key" :href="href(shortcut)" class="hero-chip focus-ring">{{ labels[shortcut.key] }}<span v-if="shortcut.count" class="chip-count">{{ shortcut.count }}</span></Link>
                        </nav>
                    </div>
                    <figure class="hero-stage pro-stage">
                        <img src="/nextward-assets/mascots/18-recruiter-mentor.webp" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                        <Sparkle style="top: 10%; right: 12%; --i: 0" />
                        <Sparkle class="is-sky" style="top: 18%; left: 8%; width: 16px; height: 16px; --i: 1" />
                        <Sparkle class="is-sky" style="bottom: 24%; left: 4%; width: 12px; height: 12px; --i: 2" />
                    </figure>
                </div>
            </section>

            <section v-if="paths.length" class="public-container paths-section" aria-labelledby="paths-title">
                <p class="landing-label">{{ text('Where to next', 'Prochaine destination') }}</p>
                <h2 id="paths-title" class="section-title">{{ text('Choose where you want to work.', 'Choisissez où vous voulez travailler.') }}</h2>
                <div class="paths">
                    <Link v-for="path in paths" :key="path.key" :href="href(path)" class="path focus-ring">
                        <span v-if="path.count" class="path-count">{{ text(`${path.count} open ${path.count === 1 ? 'job' : 'jobs'}`, `${path.count} ${path.count === 1 ? 'offre ouverte' : 'offres ouvertes'}`) }}</span>
                        <h3>{{ path.title }}</h3>
                        <p>{{ path.body }}</p>
                        <span class="path-link">{{ path.link }}<Icon name="arrow-up-right" class="action-icon" /></span>
                    </Link>
                </div>
            </section>

            <section class="anatomy-band" aria-labelledby="anatomy-title">
                <div class="public-container anatomy">
                    <div>
                        <p class="landing-label">{{ text('Know before you apply', 'Sachez avant de postuler') }}</p>
                        <h2 id="anatomy-title" class="section-title">{{ text('The details that matter, on every job.', 'Les détails qui comptent, sur chaque offre.') }}</h2>
                        <dl class="promises">
                            <div v-for="item in promises" :key="item.title"><dt><Icon name="check-circle" />{{ item.title }}</dt><dd>{{ item.body }}</dd></div>
                        </dl>
                    </div>
                    <div class="example-wrap">
                        <figure class="example" :aria-label="text('Example job listing', 'Exemple d’offre d’emploi')">
                            <div class="example-head">
                                <span class="example-tag">{{ text('Example', 'Exemple') }}</span>
                                <h3>{{ text('Senior Backend Engineer', 'Ingénieur backend senior') }}</h3>
                            </div>
                            <dl class="example-rows">
                                <div v-for="row in example" :key="row.label"><dt>{{ row.label }}</dt><dd :class="{ 'is-unknown': row.unknown }">{{ row.value }}</dd></div>
                            </dl>
                            <p class="example-foot">{{ text('Apply on the employer’s website', 'Candidater sur le site de l’employeur') }}<Icon name="arrow-up-right" class="action-icon" /></p>
                        </figure>
                        <img src="/nextward-assets/mascots/03-recruiter-clipboard.svg" alt="" width="768" height="768" loading="lazy" class="example-mascot" />
                    </div>
                </div>
            </section>

            <section class="public-container py-20" aria-labelledby="jobs-title">
                <div class="flex flex-wrap gap-4 justify-between items-end mb-7">
                    <div><p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p><h2 id="jobs-title" class="section-title">{{ text('Recent jobs', 'Les derniers emplois') }}</h2></div>
                    <Link :href="`${prefix}/opportunities/jobs`" class="landing-link text-blue-700">{{ text('Browse all jobs', 'Voir tous les emplois') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                </div>
                <div v-if="listings.length" class="grid lg:grid-cols-2 gap-4"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="rounded-2xl bg-white border border-line p-8 text-slate-600">{{ text('No open jobs right now. Browse the full catalogue, or check back when new roles are published.', 'Aucun emploi ouvert pour le moment. Consultez le catalogue complet ou revenez à la publication de nouvelles offres.') }}</p>
            </section>

            <section class="tools-band" aria-labelledby="tools-title">
                <div class="public-container py-20">
                    <p class="landing-label">{{ text('Your workspace', 'Votre espace') }}</p>
                    <h2 id="tools-title" class="section-title">{{ text('Keep your job search in one place.', 'Gardez votre recherche d’emploi au même endroit.') }}</h2>
                    <ul class="tools">
                        <li v-for="tool in tools" :key="tool.art"><img :src="`/nextward-assets/mascots/${tool.art}.svg`" alt="" width="768" height="768" loading="lazy" class="tool-art" /><h3>{{ tool.title }}</h3><p>{{ tool.body }}</p></li>
                    </ul>
                </div>
            </section>

            <section v-if="posts.length" class="public-container py-20" aria-labelledby="pro-guides-title">
                <p class="landing-label">{{ text('Career guides', 'Guides carrière en anglais') }}</p>
                <h2 id="pro-guides-title" class="section-title">{{ text('Plan your next move', 'Préparez votre prochaine étape') }}</h2>
                <div class="guide-list"><Link v-for="post in posts" :key="post.slug" :href="`/blog/${post.slug}`" class="guide-row focus-ring" lang="en"><span class="guide-category">{{ post.category }}</span><div><h3>{{ post.title }}</h3><p>{{ post.excerpt }}</p></div><span class="guide-time">{{ post.read_time }}</span><Icon name="arrow-up-right" class="guide-arrow" /></Link></div>
            </section>

            <section class="closing-band">
                <div class="public-container pro-closing">
                    <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="closing-art" />
                    <div>
                        <h2 class="section-title">{{ text('Ready for your next move?', 'Prêt pour la suite ?') }}</h2>
                        <p class="mt-4 text-lg">{{ text('Create a candidate account to save jobs, set alerts and follow every application.', 'Créez un compte candidat pour sauvegarder des offres, créer des alertes et suivre chaque candidature.') }}</p>
                        <div class="hero-buttons">
                            <Link href="/register?account=student&career_stage=professional" class="cta cta-light focus-ring">{{ text('Create a candidate account', 'Créer un compte candidat') }}</Link>
                            <Link href="/register?account=company" class="cta cta-ghost focus-ring">{{ text('Hiring? Post a job', 'Vous recrutez ? Publiez une offre') }}</Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.pro-stage { width: min(100%, 460px); justify-self: end; }
.paths-section { padding-block: 88px 80px; }
.paths { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 40px; border: 1px solid #0a17381a; border-radius: 18px; background: #fff; overflow: hidden; }
.path { display: flex; flex-direction: column; padding: 32px; transition: background 180ms; }
.path + .path { border-left: 1px solid #0a17381a; }
.path:hover { background: #f6f8fc; }
.path:hover .path-link { color: var(--color-brand-dark); }
.path-count { margin-bottom: 14px; font-size: 12px; font-weight: 600; color: var(--color-brand); font-variant-numeric: tabular-nums; }
.path h3 { margin-top: 0; font-size: 24px; font-weight: 600; letter-spacing: -.035em; }
.path p { flex: 1; margin-top: 10px; font-size: 14px; line-height: 1.75; color: #586171; }
.path-link { display: inline-flex; align-items: center; gap: 10px; margin-top: 24px; font-size: 14px; font-weight: 600; color: var(--color-brand); }
.anatomy-band { background: var(--color-navy); color: #fff; }
.anatomy-band .landing-label { color: #b9d0ff; }
.anatomy { display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 72px; padding-block: 96px; }
.promises { display: grid; gap: 26px; margin-top: 36px; }
.promises dt { display: flex; align-items: center; gap: 10px; font-weight: 600; }
.promises dt svg { width: 20px; height: 20px; color: #8ab2ff; }
.promises dd { max-width: 460px; margin-top: 6px; padding-left: 30px; font-size: 14px; line-height: 1.75; color: #c5cddc; }
.example-wrap { position: relative; padding-top: 84px; }
.example { position: relative; margin: 0; border-radius: 18px; background: #fff; color: var(--color-navy); }
.example-head { padding: 26px 28px 20px; border-bottom: 1px solid #0a17381a; }
.example-tag { display: inline-block; padding: 3px 9px; border-radius: 6px; background: #fdebe7; color: #b5442e; font-size: 11px; font-weight: 600; }
.example-head h3 { margin-top: 12px; font-size: 22px; font-weight: 600; letter-spacing: -.03em; }
.example-rows { padding: 8px 28px; }
.example-rows div { display: grid; grid-template-columns: 150px 1fr; gap: 16px; padding-block: 12px; border-bottom: 1px dashed #0a17381f; font-size: 14px; }
.example-rows div:last-child { border-bottom: 0; }
.example-rows dt { color: #67707e; }
.example-rows dd { font-weight: 600; }
.example-rows dd.is-unknown { color: #67707e; font-weight: 500; font-style: italic; }
.example-foot { display: flex; align-items: center; justify-content: space-between; margin: 0; padding: 18px 28px; border-top: 1px solid #0a17381a; border-radius: 0 0 18px 18px; background: #f6f8fc; color: var(--color-brand); font-size: 14px; font-weight: 600; }
.example-mascot { position: absolute; top: -6px; right: 12px; width: 150px; height: 150px; }
.tools-band { background: #fff; border-bottom: 1px solid #0a17380f; }
.tools { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 36px; }
.tool-art { width: 104px; height: 104px; margin-left: -10px; }
.tools h3 { margin-top: 12px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.tools p { margin-top: 8px; font-size: 14px; line-height: 1.75; color: #586171; }
.pro-closing { display: flex; align-items: center; gap: 48px; padding-block: 64px; }
.pro-closing .section-title { max-width: 640px; }
.closing-art { width: 190px; height: 190px; flex-shrink: 0; }
@media (max-width: 1023px) {
    .paths { grid-template-columns: 1fr; }
    .path + .path { border-left: 0; border-top: 1px solid #0a17381a; }
    .anatomy { grid-template-columns: 1fr; gap: 48px; }
    .tools { gap: 24px; }
}
@media (max-width: 767px) {
    .pro-stage { justify-self: center; width: min(100%, 320px); }
    .paths-section { padding-block: 64px 56px; }
    .path { padding: 26px 24px; }
    .anatomy { padding-block: 64px; }
    .example-wrap { padding-top: 72px; }
    .example-rows { padding-inline: 20px; }
    .example-rows div { grid-template-columns: 1fr; gap: 2px; }
    .example-head, .example-foot { padding-inline: 20px; }
    .example-mascot { top: 0; right: 4px; width: 120px; height: 120px; }
    .tools { grid-template-columns: 1fr; }
    .pro-closing { flex-direction: column; align-items: flex-start; gap: 16px; padding-block: 48px; }
    .closing-art { width: 120px; height: 120px; }
}
</style>
