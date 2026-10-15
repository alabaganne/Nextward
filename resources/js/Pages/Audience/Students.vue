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
const labels = computed(() => ({
    pfe: text('PFE internships', 'Stages PFE'),
    summer: text('Summer internships', 'Stages d’été'),
    graduate: text('Graduate programmes', 'Programmes jeunes diplômés'),
    'first-jobs': text('First jobs', 'Premiers emplois'),
    remote: text('Remote internships', 'Stages à distance'),
    tunisia: text('Internships in Tunisia', 'Stages en Tunisie'),
}));
const href = (shortcut) => `${prefix.value}${shortcut.path}${shortcut.query ? `?${shortcut.query}` : ''}`;
const journey = computed(() => [
    { art: '13-discover-internship', title: text('Find a role that fits', 'Trouvez le bon stage'), body: text('Filter internships by field, city, duration and work type. Save the ones you like and compare them later.', 'Filtrez les stages par domaine, ville, durée et mode de travail. Sauvegardez ceux qui vous plaisent pour les comparer.') },
    { art: '14-prepare-application', title: text('Get your application ready', 'Préparez votre candidature'), body: text('Add your skills and LinkedIn to your profile. When you apply, write a cover letter and attach your CV.', 'Ajoutez vos compétences et votre LinkedIn à votre profil. Au moment de candidater, rédigez une lettre de motivation et joignez votre CV.') },
    { art: '15-student-company-conversation', title: text('Talk with the company', 'Échangez avec l’entreprise'), body: text('For roles posted on Nextward, apply here and message the company directly. For the rest, we send you to the original listing.', 'Pour les offres publiées sur Nextward, candidatez ici et écrivez directement à l’entreprise. Pour les autres, nous vous dirigeons vers l’annonce originale.') },
    { art: '16-first-day-celebration', title: text('Start your first day', 'Commencez votre premier jour'), body: text('Follow each application from sent to offer, so you always know where you stand.', 'Suivez chaque candidature de l’envoi jusqu’à l’offre, pour savoir où vous en êtes.') },
]);
const faqs = computed(() => [
    { q: text('Do I need an account to look at internships?', 'Faut-il un compte pour consulter les stages ?'), a: text('No. You can search and read every listing without an account. You need one to save roles, apply on Nextward and track your applications.', 'Non. Vous pouvez rechercher et lire toutes les offres sans compte. Il vous en faut un pour sauvegarder des offres, candidater sur Nextward et suivre vos candidatures.') },
    { q: text('What is a PFE internship?', 'Qu’est-ce qu’un stage PFE ?'), a: text('A PFE (projet de fin d’études) is the final-year internship that many engineering and business programmes in Tunisia require. Use the PFE filter to see only these roles.', 'Le PFE (projet de fin d’études) est le stage de dernière année exigé par de nombreuses écoles d’ingénieurs et de commerce en Tunisie. Utilisez le filtre PFE pour n’afficher que ces offres.') },
    { q: text('Can I apply for jobs too?', 'Puis-je aussi postuler à des emplois ?'), a: text('Yes. One account covers internships and jobs, and your student profile never hides opportunities from you.', 'Oui. Un seul compte couvre les stages et les emplois, et votre profil étudiant ne vous cache jamais d’opportunités.') },
    { q: text('Can I do a remote internship from Tunisia?', 'Puis-je faire un stage à distance depuis la Tunisie ?'), a: text('Only when the listing accepts applicants based in Tunisia. Remote does not always mean worldwide, so we show eligibility as unknown when the source does not say.', 'Seulement si l’offre accepte les candidats basés en Tunisie. Le télétravail n’est pas toujours ouvert à tous les pays : nous indiquons une éligibilité inconnue quand la source ne la précise pas.') },
    { q: text('Where do the listings come from?', 'D’où viennent les offres ?'), a: text('Some are posted by employers on Nextward. Others come from approved public sources, and each one links to the original listing and shows when it was last checked.', 'Certaines sont publiées par les employeurs sur Nextward. Les autres viennent de sources publiques autorisées : chacune renvoie vers l’annonce originale et indique la date du dernier contrôle.') },
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <section aria-labelledby="students-title" class="hero-dark">
                <div class="hero-grid public-container">
                    <div>
                        <p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ text('Nextward for students', 'Nextward pour les étudiants') }}</p>
                        <h1 id="students-title" class="hero-title">{{ text('Land your first internship.', 'Décrochez votre premier stage.') }}</h1>
                        <p class="hero-lead">{{ text('PFE, summer internships, graduate programmes and first jobs, in Tunisia and abroad. Look around freely, and create an account when you’re ready to apply.', 'PFE, stages d’été, programmes jeunes diplômés et premiers emplois, en Tunisie et à l’étranger. Explorez librement et créez un compte quand vous êtes prêt à candidater.') }}</p>
                        <div class="hero-buttons">
                            <Link :href="`${prefix}/opportunities/internships`" class="cta cta-primary focus-ring">{{ text('Find an internship', 'Trouver un stage') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                            <Link :href="`${prefix}/opportunities/jobs?seniority=entry`" class="cta cta-ghost focus-ring">{{ text('See first jobs', 'Voir les premiers emplois') }}</Link>
                        </div>
                        <nav v-if="shortcuts.length" class="hero-chips" :aria-label="text('Popular searches', 'Recherches populaires')">
                            <Link v-for="shortcut in shortcuts" :key="shortcut.key" :href="href(shortcut)" class="hero-chip focus-ring">{{ labels[shortcut.key] }}<span v-if="shortcut.count" class="chip-count">{{ shortcut.count }}</span></Link>
                        </nav>
                    </div>
                    <figure class="hero-stage student-stage">
                        <img src="/nextward-assets/mascots/17-student-presenter.webp" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                        <Sparkle style="top: 12%; left: 10%; --i: 0" />
                        <Sparkle class="is-sky" style="top: 6%; right: 14%; width: 16px; height: 16px; --i: 1" />
                        <Sparkle style="bottom: 26%; right: 4%; width: 14px; height: 14px; --i: 2" />
                    </figure>
                </div>
            </section>

            <section class="public-container journey-section" aria-labelledby="journey-title">
                <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                <h2 id="journey-title" class="section-title">{{ text('From first search to first day.', 'De la première recherche au premier jour.') }}</h2>
                <ol class="journey">
                    <li v-for="(step, index) in journey" :key="step.art">
                        <img :src="`/nextward-assets/mascots/${step.art}.svg`" alt="" width="1100" height="760" loading="lazy" class="journey-art" />
                        <span class="journey-number" aria-hidden="true">{{ index + 1 }}</span>
                        <h3>{{ step.title }}</h3>
                        <p>{{ step.body }}</p>
                    </li>
                </ol>
            </section>

            <section class="public-container pb-20" aria-labelledby="student-roles-title">
                <div class="flex flex-wrap gap-4 justify-between items-end mb-7">
                    <div><p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p><h2 id="student-roles-title" class="section-title">{{ text('Internships and first jobs', 'Stages et premiers emplois') }}</h2></div>
                    <Link :href="`${prefix}/opportunities/internships`" class="landing-link text-blue-700">{{ text('Browse all internships', 'Voir tous les stages') }}<Icon name="arrow-up-right" class="action-icon" /></Link>
                </div>
                <div v-if="listings.length" class="grid lg:grid-cols-2 gap-4"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="rounded-2xl bg-white border border-line p-8 text-slate-600">{{ text('No open internships right now. Browse the full catalogue, or check back when new roles are published.', 'Aucun stage ouvert pour le moment. Consultez le catalogue complet ou revenez à la publication de nouvelles offres.') }}</p>
            </section>

            <section class="faq-band" aria-labelledby="student-faq-title">
                <div class="public-container faq-layout">
                    <div>
                        <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="faq-art" />
                        <h2 id="student-faq-title" class="section-title">{{ text('Questions students ask us', 'Les questions des étudiants') }}</h2>
                        <p class="section-description">{{ text('Still unsure about something? Write to us from the contact page.', 'Une autre question ? Écrivez-nous depuis la page contact.') }} <Link href="/contact" class="font-semibold text-blue-700 underline underline-offset-4">{{ text('Contact us', 'Nous contacter') }}</Link></p>
                    </div>
                    <div class="faq-list">
                        <details v-for="item in faqs" :key="item.q" class="faq-item"><summary class="focus-ring">{{ item.q }}<Icon name="plus" class="faq-toggle" /></summary><p>{{ item.a }}</p></details>
                    </div>
                </div>
            </section>

            <section v-if="posts.length" class="public-container py-20" aria-labelledby="student-guides-title">
                <p class="landing-label">{{ text('Career guides', 'Guides carrière en anglais') }}</p>
                <h2 id="student-guides-title" class="section-title">{{ text('Prepare your next application', 'Préparez votre prochaine candidature') }}</h2>
                <div class="guide-list"><Link v-for="post in posts" :key="post.slug" :href="`/blog/${post.slug}`" class="guide-row focus-ring" lang="en"><span class="guide-category">{{ post.category }}</span><div><h3>{{ post.title }}</h3><p>{{ post.excerpt }}</p></div><span class="guide-time">{{ post.read_time }}</span><Icon name="arrow-up-right" class="guide-arrow" /></Link></div>
            </section>

            <section class="closing-band">
                <div class="public-container closing-layout">
                    <div>
                        <h2 class="section-title">{{ text('Your first day starts with one search.', 'Votre premier jour commence par une recherche.') }}</h2>
                        <p class="mt-4 text-lg">{{ text('Create a student account to save roles, apply and follow every application.', 'Créez votre compte étudiant pour sauvegarder des offres, candidater et suivre chaque candidature.') }}</p>
                        <div class="hero-buttons">
                            <Link href="/register?account=student&career_stage=student" class="cta cta-light focus-ring">{{ text('Create a student account', 'Créer un compte étudiant') }}</Link>
                            <Link :href="`${prefix}/opportunities/internships`" class="cta cta-ghost focus-ring">{{ text('Browse internships', 'Voir les stages') }}</Link>
                        </div>
                    </div>
                    <img src="/nextward-assets/mascots/01-student-wave.svg" alt="" width="768" height="768" loading="lazy" class="closing-art" />
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.student-stage { width: min(100%, 470px); justify-self: end; }
.journey-section { padding-block: 88px 72px; }
.journey { position: relative; display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px; margin-top: 44px; counter-reset: step; }
.journey::before { content: ''; position: absolute; top: calc(var(--art-h) + 14px); left: 6%; right: 6%; border-top: 2px dashed #b9d0ff; }
.journey { --art-h: 168px; }
.journey li { position: relative; }
.journey-art { width: 100%; height: var(--art-h); object-fit: contain; }
.journey-number { position: relative; display: grid; place-items: center; width: 30px; height: 30px; margin-top: 0; border-radius: 50%; background: var(--color-navy); color: #fff; font-family: var(--font-display); font-size: 14px; font-weight: 600; box-shadow: 0 0 0 6px #faf9f6; }
.journey h3 { margin-top: 18px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.journey p { margin-top: 8px; font-size: 14px; line-height: 1.75; color: #586171; }
.faq-band { background: #fff; border-block: 1px solid #0a17380f; }
.faq-layout { display: grid; grid-template-columns: .8fr 1.2fr; gap: 72px; padding-block: 88px; }
.faq-art { width: 132px; height: 132px; margin-left: -14px; }
.faq-layout .section-title { margin-top: 8px; }
.closing-layout { display: flex; align-items: center; justify-content: space-between; gap: 40px; padding-block: 64px; }
.closing-layout .section-title { max-width: 640px; }
.closing-art { width: 200px; height: 200px; flex-shrink: 0; }
@media (max-width: 1023px) {
    .journey { grid-template-columns: repeat(2, 1fr); row-gap: 48px; }
    .journey::before { display: none; }
    .faq-layout { gap: 40px; }
}
@media (max-width: 767px) {
    .student-stage { justify-self: center; width: min(100%, 320px); }
    .journey-section { padding-block: 64px 48px; }
    .journey { grid-template-columns: 1fr; --art-h: 150px; }
    .journey-art { width: auto; max-width: 100%; }
    .faq-layout { grid-template-columns: 1fr; padding-block: 64px; }
    .closing-layout { flex-direction: column-reverse; align-items: flex-start; padding-block: 48px; }
    .closing-art { width: 120px; height: 120px; }
}
</style>
