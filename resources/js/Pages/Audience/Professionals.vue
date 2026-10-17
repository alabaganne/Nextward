<script setup>
import { computed } from 'vue';
import { ArrowRight, ArrowUpRight, CircleQuestionMark, FileText, Globe, Laptop, MapPin, Search, SquareArrowOutUpRight } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import GuideCard from '@/Components/Blog/GuideCard.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';
const props = defineProps({ locale: { type: String, default: 'en' }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, posts: { type: Array, default: () => [] }, shortcuts: { type: Array, default: () => [] } });
const listings = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const fr = computed(() => props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const href = (shortcut) => `${prefix.value}${shortcut.path}${shortcut.query ? `?${shortcut.query}` : ''}`;
// Places first, then job types. Plain "remote" is left out next to "remote from Tunisia".
const labels = computed(() => ({
    tunisia: { label: text('In Tunisia', 'En Tunisie'), icon: MapPin },
    'remote-from-tunisia': { label: text('Remote from Tunisia', 'À distance depuis la Tunisie'), icon: Laptop },
    relocation: { label: text('Abroad with relocation', 'À l’étranger avec mobilité'), icon: Globe },
    'full-time': { label: text('Full-time', 'Temps plein') },
    'part-time': { label: text('Part-time', 'Temps partiel') },
    contract: { label: text('Contract', 'Contrat') },
    senior: { label: text('Senior roles', 'Postes seniors') },
}));
const chips = computed(() => Object.keys(labels.value).map((key) => props.shortcuts.find((shortcut) => shortcut.key === key)).filter(Boolean));
const promises = computed(() => [
    { icon: FileText, title: text('Eligibility before effort', 'L’éligibilité d’abord'), body: text('See which countries can apply before you spend time on a role that cannot hire from Tunisia.', 'Voyez quels pays peuvent postuler avant de passer du temps sur un poste fermé à la Tunisie.') },
    { icon: CircleQuestionMark, title: text('Unknown stays unknown', 'L’inconnu reste inconnu'), body: text('If the source does not say whether pay, a visa or relocation is offered, we say so instead of guessing.', 'Si la source ne précise pas la rémunération, le visa ou la mobilité, nous l’indiquons au lieu de deviner.') },
    { icon: SquareArrowOutUpRight, title: text('The original source, always', 'Toujours la source d’origine'), body: text('Every job shows where it comes from and when it was last checked. Apply where the employer wants.', 'Chaque offre indique sa provenance et la date du dernier contrôle. Candidatez là où l’employeur le souhaite.') },
]);
const example = computed(() => [
    { label: text('Work type', 'Mode de travail'), value: text('Remote, full-time', 'Télétravail, temps plein') },
    { label: text('Can apply from', 'Candidats acceptés depuis'), value: text('Tunisia, France, Germany', 'Tunisie, France, Allemagne') },
    { label: text('Relocation', 'Mobilité'), value: text('Not offered', 'Non proposée') },
    { label: text('Visa sponsorship', 'Parrainage de visa'), value: text('Not given by the source', 'Non précisé par la source'), unknown: true },
    { label: text('Pay', 'Rémunération'), value: text('Not given by the source', 'Non précisée par la source'), unknown: true },
    { label: text('Source', 'Source'), value: text('Company careers page, checked 2 days ago', 'Page carrières de l’entreprise, contrôlée il y a 2 jours') },
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <LandingHero title-id="professionals-title" :mascots="['18-recruiter-mentor']" :label="text('Nextward for professionals', 'Nextward pour les professionnels')" :lead="text('Full-time, part-time, contract and freelance roles from employers and approved sources. Every job shows where you can work from and where to apply.', 'Des postes à temps plein, à temps partiel, en contrat et en freelance, publiés par des employeurs ou des sources autorisées. Chaque offre indique d’où vous pouvez travailler et où candidater.')">
                {{ text('Your', 'Votre') }} <span class="accent">{{ text('next job,', 'prochain poste,') }}</span> <span class="lg:block">{{ text('in Tunisia or abroad.', 'en Tunisie ou à l’étranger.') }}</span>
                <template #actions>
                    <Link :href="`${prefix}/opportunities/jobs`" class="cta cta-primary focus-ring">{{ text('Explore jobs', 'Explorer les emplois') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    <Link :href="`${prefix}/opportunities/remote-from-tunisia`" class="cta cta-ghost focus-ring">{{ text('Remote jobs from Tunisia', 'Télétravail depuis la Tunisie') }}</Link>
                </template>
            </LandingHero>
            <section class="public-container search-overlap" :aria-label="text('Search jobs', 'Rechercher des emplois')">
                <form method="get" :action="`${prefix}/opportunities/jobs`" class="search-panel" role="search">
                    <div class="search-grid is-two">
                        <label><span class="field-label">{{ text('Job title or skill', 'Poste ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. backend, sales, finance', 'ex. backend, commercial, finance')" /></span></label>
                        <button type="submit" class="cta cta-primary focus-ring">{{ text('Find a job', 'Trouver un emploi') }}</button>
                    </div>
                </form>
                <div v-if="chips.length" class="chip-row">
                    <p class="chip-row-label">{{ text('Popular searches:', 'Recherches populaires :') }}</p>
                    <nav class="flex flex-wrap gap-2.5" :aria-label="text('Popular searches', 'Recherches populaires')"><Link v-for="shortcut in chips" :key="shortcut.key" :href="href(shortcut)" class="chip focus-ring"><component :is="labels[shortcut.key].icon" v-if="labels[shortcut.key].icon" aria-hidden="true" />{{ labels[shortcut.key].label }}<span v-if="shortcut.count" class="chip-count">{{ shortcut.count }}</span></Link></nav>
                </div>
            </section>

            <section class="public-container section" aria-labelledby="jobs-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p><h2 id="jobs-title" class="section-title">{{ text('Recent jobs', 'Les derniers emplois') }}</h2></div>
                    <Link :href="`${prefix}/opportunities/jobs`" class="landing-link focus-ring">{{ text('Browse all jobs', 'Voir tous les emplois') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div v-if="listings.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="panel p-8 text-slate-600">{{ text('No open jobs right now. Browse the full catalogue, or check back when new roles are published.', 'Aucun emploi ouvert pour le moment. Consultez le catalogue complet ou revenez à la publication de nouvelles offres.') }}</p>
            </section>

            <section class="band-pale section" aria-labelledby="anatomy-title">
                <div class="public-container anatomy">
                    <div>
                        <p class="landing-label">{{ text('Know before you apply', 'Sachez avant de postuler') }}</p>
                        <h2 id="anatomy-title" class="section-title">{{ text('The details that matter, on every job.', 'Les détails qui comptent, sur chaque offre.') }}</h2>
                        <dl class="promises">
                            <div v-for="item in promises" :key="item.title"><component :is="item.icon" class="promise-icon" aria-hidden="true" /><div><dt>{{ item.title }}</dt><dd>{{ item.body }}</dd></div></div>
                        </dl>
                    </div>
                    <div class="example-wrap">
                        <img src="/nextward-assets/mascots/03-recruiter-clipboard.svg" alt="" width="768" height="768" loading="lazy" class="example-mascot" />
                        <figure class="panel example" :aria-label="text('Example job listing', 'Exemple d’offre d’emploi')">
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

            <section v-if="posts.length" class="public-container section" aria-labelledby="pro-guides-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p><h2 id="pro-guides-title" class="section-title">{{ text('Plan your next move.', 'Préparez votre prochaine étape.') }}</h2></div>
                    <Link :href="`${prefix}/blog`" class="landing-link focus-ring">{{ text('View all guides', 'Voir tous les guides') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div class="guide-grid is-three"><GuideCard v-for="post in posts" :key="post.slug" :post="post" /></div>
            </section>

            <section class="public-container pb-20" :class="{ 'pt-20': !posts.length }">
                <ClosingBanner mascot="04-mentor-pointing" :title="text('Ready for your next move?', 'Prêt pour la suite ?')" :body="text('Create a candidate account to save jobs, set alerts and follow every application.', 'Créez un compte candidat pour sauvegarder des offres, créer des alertes et suivre chaque candidature.')">
                    <Link :href="`${prefix}/register?account=student&career_stage=professional`" class="cta cta-light focus-ring">{{ text('Create a candidate account', 'Créer un compte candidat') }}</Link>
                    <Link :href="`${prefix}/register?account=company`" class="cta cta-ghost focus-ring">{{ text('Hiring? Post a job', 'Vous recrutez ? Publiez une offre') }}</Link>
                </ClosingBanner>
            </section>
        </div>
    </PublicLayout>
</template>
<style scoped>
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
@media (max-width: 1023px) {
    .anatomy { grid-template-columns: 1fr; gap: 44px; }
}
@media (max-width: 767px) {
    .example-mascot { width: 110px; height: 110px; }
    .example-wrap { padding-top: 76px; }
    .example-head, .example-foot { padding-inline: 20px; }
    .example-rows { padding-inline: 20px; }
}
</style>
