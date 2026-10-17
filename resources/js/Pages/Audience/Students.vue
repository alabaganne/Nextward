<script setup>
import { computed } from 'vue';
import { ArrowRight, GraduationCap, Search } from '@lucide/vue';
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
    { q: text('What does a company see when I apply?', 'Que voit l’entreprise quand je candidate ?'), a: text('On Nextward, the company sees your profile (career stage, field and skills), a short cover letter you write for the role, and your CV and any files you attach. Roles on other websites use their own forms, and opening their link does not send your CV.', 'Sur Nextward, l’entreprise voit votre profil (niveau, domaine et compétences), une courte lettre écrite pour l’offre, ainsi que votre CV et les fichiers joints. Les offres d’autres sites utilisent leur propre formulaire, et ouvrir leur lien n’envoie pas votre CV.') },
    { q: text('Can I apply for jobs too?', 'Puis-je aussi postuler à des emplois ?'), a: text('Yes. One account covers internships and jobs, and your student profile never hides opportunities from you.', 'Oui. Un seul compte couvre les stages et les emplois, et votre profil étudiant ne vous cache jamais d’opportunités.') },
    { q: text('Can I do a remote internship from Tunisia?', 'Puis-je faire un stage à distance depuis la Tunisie ?'), a: text('Only when the listing accepts applicants based in Tunisia. Remote does not always mean worldwide, so we show eligibility as unknown when the source does not say.', 'Seulement si l’offre accepte les candidats basés en Tunisie. Le télétravail n’est pas toujours ouvert à tous les pays : nous indiquons une éligibilité inconnue quand la source ne la précise pas.') },
    { q: text('Where do the listings come from?', 'D’où viennent les offres ?'), a: text('Some are posted by employers on Nextward. Others come from approved public sources, and each one links to the original listing and shows when it was last checked.', 'Certaines sont publiées par les employeurs sur Nextward. Les autres viennent de sources publiques autorisées : chacune renvoie vers l’annonce originale et indique la date du dernier contrôle.') },
]);
</script>
<template>
    <PublicLayout>
        <div class="landing">
            <LandingHero title-id="students-title" :mascots="['17-student-presenter']" :label="text('Nextward for students', 'Nextward pour les étudiants')" :lead="text('PFE, summer internships, graduate programmes and first jobs, in Tunisia and abroad.', 'PFE, stages d’été, programmes jeunes diplômés et premiers emplois, en Tunisie et à l’étranger.')" :note="text('Look around freely, and create an account when you’re ready to apply.', 'Explorez librement et créez un compte quand vous êtes prêt à candidater.')">
                {{ text('Your', 'Votre') }} <span class="accent">{{ text('first opportunity', 'première opportunité') }}</span> {{ text('starts here.', 'commence ici.') }}
                <template #actions>
                    <Link :href="`${prefix}/opportunities/internships`" class="cta cta-primary focus-ring">{{ text('Find an internship', 'Trouver un stage') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                    <Link :href="`${prefix}/opportunities/jobs?seniority=entry`" class="cta cta-ghost focus-ring">{{ text('See first jobs', 'Voir les premiers emplois') }}</Link>
                </template>
            </LandingHero>
            <section class="public-container search-overlap" :aria-label="text('Search internships', 'Rechercher des stages')">
                <form method="get" :action="`${prefix}/opportunities/internships`" class="search-panel" role="search">
                    <div class="search-grid is-three">
                        <label><span class="field-label">{{ text('Field or skill', 'Domaine ou compétence') }}</span><span class="field"><Search aria-hidden="true" /><input name="search" type="search" maxlength="150" :placeholder="text('e.g. marketing, software, finance', 'ex. marketing, logiciel, finance')" /></span></label>
                        <label><span class="field-label">{{ text('Programme', 'Programme') }}</span><span class="field"><GraduationCap aria-hidden="true" /><select name="program_type"><option value="">{{ text('Any programme', 'Tous les programmes') }}</option><option value="pfe">PFE</option><option value="summer">{{ text('Summer', 'Été') }}</option><option value="apprenticeship">{{ text('Apprenticeship', 'Alternance') }}</option></select></span></label>
                        <button class="cta cta-primary focus-ring" type="submit">{{ text('Search internships', 'Rechercher') }}</button>
                    </div>
                </form>
                <div v-if="shortcuts.length" class="chip-row">
                    <p class="chip-row-label">{{ text('Popular searches:', 'Recherches populaires :') }}</p>
                    <nav class="flex flex-wrap gap-2.5" :aria-label="text('Popular searches', 'Recherches populaires')"><Link v-for="shortcut in shortcuts" :key="shortcut.key" :href="href(shortcut)" class="chip focus-ring">{{ labels[shortcut.key] }}<span v-if="shortcut.count" class="chip-count">{{ shortcut.count }}</span></Link></nav>
                </div>
            </section>

            <section class="public-container section" aria-labelledby="student-roles-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Open now', 'Ouvertes maintenant') }}</p><h2 id="student-roles-title" class="section-title">{{ text('Internships and first jobs', 'Stages et premiers emplois') }}</h2></div>
                    <Link :href="`${prefix}/opportunities/internships`" class="landing-link focus-ring">{{ text('Browse all internships', 'Voir tous les stages') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div v-if="listings.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"><RoleCard v-for="opportunity in listings" :key="opportunity.id" :internship="opportunity" /></div>
                <p v-else class="panel p-8 text-slate-600">{{ text('No open internships right now. Browse the full catalogue, or check back when new roles are published.', 'Aucun stage ouvert pour le moment. Consultez le catalogue complet ou revenez à la publication de nouvelles offres.') }}</p>
            </section>

            <section class="band-pale section" aria-labelledby="journey-title">
                <div class="public-container">
                    <p class="landing-label">{{ text('How it works', 'Comment ça marche') }}</p>
                    <h2 id="journey-title" class="section-title">{{ text('From first search to first day.', 'De la première recherche au premier jour.') }}</h2>
                    <ol class="journey">
                        <li v-for="(step, index) in journey" :key="step.title">
                            <img :src="`/nextward-assets/mascots/${step.art}.svg`" alt="" width="1100" height="760" loading="lazy" class="journey-art" />
                            <span class="journey-number" aria-hidden="true">{{ index + 1 }}</span>
                            <h3>{{ step.title }}</h3>
                            <p>{{ step.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section v-if="posts.length" class="public-container section" aria-labelledby="student-guides-title">
                <div class="section-head">
                    <div><p class="landing-label">{{ text('Career guides', 'Guides carrière') }}</p><h2 id="student-guides-title" class="section-title">{{ text('Prepare your next application', 'Préparez votre prochaine candidature') }}</h2></div>
                    <Link :href="`${prefix}/blog`" class="landing-link focus-ring">{{ text('View all guides', 'Voir tous les guides') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </div>
                <div class="guide-grid is-three"><GuideCard v-for="post in posts" :key="post.slug" :post="post" /></div>
            </section>

            <section class="public-container faq-layout" :class="{ 'no-top': posts.length }" aria-labelledby="student-faq-title">
                <div>
                    <img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="faq-art" />
                    <h2 id="student-faq-title" class="section-title">{{ text('Questions students ask us', 'Les questions des étudiants') }}</h2>
                    <p class="section-description">{{ text('Still unsure about something? Write to us from the contact page.', 'Une autre question ? Écrivez-nous depuis la page contact.') }} <Link :href="`${prefix}/contact`" class="font-semibold text-brand underline underline-offset-4 focus-ring">{{ text('Contact us', 'Nous contacter') }}</Link></p>
                </div>
                <div class="faq-list">
                    <details v-for="item in faqs" :key="item.q" class="faq-item"><summary class="focus-ring">{{ item.q }}<Icon name="plus" class="faq-toggle" /></summary><p>{{ item.a }}</p></details>
                </div>
            </section>

            <section class="public-container pb-20">
                <ClosingBanner mascot="01-student-wave" :title="text('Your first day starts with one search.', 'Votre premier jour commence par une recherche.')" :body="text('Create a student account to save roles, apply and follow every application.', 'Créez votre compte étudiant pour sauvegarder des offres, candidater et suivre chaque candidature.')">
                    <Link :href="`${prefix}/register?account=student&career_stage=student`" class="cta cta-light focus-ring">{{ text('Create a student account', 'Créer un compte étudiant') }}</Link>
                    <Link :href="`${prefix}/opportunities/internships`" class="cta cta-ghost focus-ring">{{ text('Browse internships', 'Voir les stages') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
                </ClosingBanner>
            </section>
        </div>
    </PublicLayout>
</template>
<style scoped>
.journey { position: relative; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 28px; margin-top: 40px; --art-h: 160px; }
.journey::before { content: ''; position: absolute; top: calc(var(--art-h) + 15px); left: 6%; right: 6%; border-top: 2px dashed #b9d0ff; }
.journey li { position: relative; }
.journey-art { width: 100%; height: var(--art-h); object-fit: contain; }
.journey-number { position: relative; display: grid; place-items: center; width: 30px; height: 30px; border-radius: 50%; background: var(--color-navy); color: #fff; font-family: var(--font-display); font-size: 14px; font-weight: 600; outline: 6px solid #f6f8fc; }
.journey h3 { margin-top: 18px; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
.journey p { margin-top: 8px; color: #586171; font-size: 14.5px; line-height: 1.75; }
.faq-layout { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); align-items: start; gap: 64px; padding-block: 88px; }
.faq-layout.no-top { padding-top: 0; }
.faq-art { width: 128px; height: 128px; margin: 0 0 8px -14px; }
@media (max-width: 1023px) {
    .journey { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 48px; }
    .journey::before { display: none; }
    .faq-layout { grid-template-columns: 1fr; gap: 32px; padding-block: 72px; }
}
@media (max-width: 767px) {
    .journey { grid-template-columns: 1fr; --art-h: 140px; }
    .journey-art { width: auto; max-width: 100%; }
    .faq-layout { padding-block: 56px; }
}
</style>
