<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import Flag from '@/Shared/Flag.vue';
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const links = computed(() => [
    { label: fr.value ? 'Offres' : 'Roles', href: `${prefix.value}/opportunities` },
    { label: fr.value ? 'Entreprises' : 'Companies', href: `${prefix.value}/companies` },
    { label: fr.value ? 'Talents' : 'Talent', href: `${prefix.value}/candidates` },
    { label: 'Blog', href: `${prefix.value}/blog` },
    { label: fr.value ? 'À propos' : 'About', href: `${prefix.value}/about` },
]);
// Visitors get one sign-up button; the sign-up page links to log in.
const account = computed(() => page.props.auth?.user ? (fr.value ? 'Mon espace' : 'Dashboard') : (fr.value ? 'Commencer' : 'Get started'));
const languageMenu = ref(null);
const mobileMenu = ref(null);
const year = new Date().getFullYear();
const currentPath = computed(() => page.url.split(/[?#]/)[0]);
const isActive = (href) => currentPath.value === href || currentPath.value.startsWith(`${href}/`);
const languageOptions = computed(() => {
    const translation = page.props.translation;
    if (translation) {
        const [en, french] = fr.value ? [translation, currentPath.value] : [currentPath.value, translation];
        return [{ code: 'en', label: 'English', href: en }, { code: 'fr', label: 'Français', href: french }];
    }
    const path = currentPath.value.replace(/^\/fr(?=\/|$)/, '') || '/';
    const translatedPaths = ['/', '/about', '/contact', '/privacy', '/terms', '/blog', '/opportunities', '/companies', '/candidates', ...['internships', 'jobs'].map((category) => `/opportunities/${category}`)];
    if (!translatedPaths.includes(path)) return [];
    const suffix = page.url.slice(currentPath.value.length);
    return [
        { code: 'en', label: 'English', href: path + suffix },
        { code: 'fr', label: 'Français', href: (path === '/' ? '/fr' : `/fr${path}`) + suffix },
    ];
});
const closeMenus = (event) => {
    [languageMenu.value, mobileMenu.value].forEach((menu) => { if (menu && !menu.contains(event.target)) menu.open = false; });
};
const dismissMenu = (menu) => {
    if (!menu?.open) return;
    menu.open = false;
    menu.querySelector('summary')?.focus();
};
const brandLink = 'focus-ring inline-flex items-center gap-2.5 rounded-lg text-[21px] font-semibold ';
const navLink = 'focus-ring relative py-2 text-[15px] tracking-[.06em] text-white/80 decoration-2 underline-offset-[10px] transition-colors duration-180 hover:text-white focus-visible:outline-white aria-[current=page]:text-white aria-[current=page]:underline';
const footerLink = 'focus-ring w-fit rounded-[2px] tracking-[.06em] text-navy transition-colors duration-180 hover:text-brand';
const helpLinks = computed(() => [
    { label: fr.value ? 'Nous contacter' : 'Contact us', href: `${prefix.value}/contact` },
    { label: fr.value ? 'Signaler un bug' : 'Report a bug', href: `${prefix.value}/contact?topic=bug` },
    { label: fr.value ? 'Recrutement et partenariats' : 'Hiring and partnerships', href: `${prefix.value}/contact?topic=business` },
]);
// Navy at the top, where it meets the navy hero, and cobalt once the page scrolls.
const scrolled = ref(false);
const onScroll = () => { scrolled.value = window.scrollY > 8; };
onMounted(() => {
    document.addEventListener('click', closeMenus);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenus);
    window.removeEventListener('scroll', onScroll);
});
</script>
<template>
    <div class="min-h-screen bg-white text-navy flex flex-col">
        <a href="#main" class="focus-ring sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ fr ? 'Aller au contenu' : 'Skip to content' }}</a>
        <header class="sticky top-0 z-40 text-white transition-colors duration-300 motion-reduce:transition-none" :class="scrolled ? 'bg-brand' : 'bg-navy'">
            <div class="public-container flex h-[84px] items-center justify-between gap-4 max-[768px]:h-[72px]">
                <Link :href="prefix || '/'" :class="brandLink" class="focus-visible:outline-white"><BrandLogo size="sm" class="!h-9 [&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                <nav :aria-label="fr ? 'Navigation principale' : 'Main navigation'" class="hidden xl:flex items-center gap-9">
                    <Link v-for="link in links" :key="link.href" :href="link.href" :class="navLink" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}</Link>
                </nav>
                <div class="flex shrink-0 items-center gap-2 xl:gap-4 text-sm font-semibold">
                    <details v-if="languageOptions.length" ref="languageMenu" name="public-header-menu" class="group relative" @keydown.esc.stop.prevent="dismissMenu(languageMenu)" @focusout="($event.relatedTarget && !languageMenu.contains($event.relatedTarget)) && (languageMenu.open = false)">
                        <summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" aria-controls="public-language-options" class="focus-ring flex h-10 cursor-pointer list-none items-center gap-2 rounded-card border border-white/30 pr-[11px] pl-1.5 text-[13px] tracking-[.06em] text-white transition-[border-color,background-color] duration-180 group-open:border-white group-open:bg-white/10 hover:border-white hover:bg-white/10 focus-visible:outline-white [&::-webkit-details-marker]:hidden">
                            <Flag :code="fr ? 'fr' : 'en'" class="size-6 outline-white/25" />
                            <span>{{ fr ? 'FR' : 'EN' }}</span>
                            <ChevronDown class="h-3.5 w-3.5 opacity-70 transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <nav id="public-language-options" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="absolute top-[calc(100%+12px)] right-0 w-[196px] origin-top-right animate-menu-pop rounded-card border border-line bg-white p-1.5 text-navy before:absolute before:-top-[7px] before:right-[22px] before:size-3 before:rotate-45 before:border-t before:border-l before:border-line before:bg-white motion-reduce:animate-none">
                            <a v-for="language in languageOptions" :key="language.code" :href="language.href" :lang="language.code" :hreflang="language.code" :aria-current="language.code === (fr ? 'fr' : 'en') ? 'true' : undefined" :class="['group/option focus-ring relative flex min-h-[46px] items-center gap-3 rounded-card px-2.5 py-2 text-[14px] font-semibold transition-colors duration-150', language.code === (fr ? 'fr' : 'en') ? 'bg-blue-50 text-brand-dark' : 'text-gray-700 hover:bg-pale hover:text-navy']">
                                <Flag :code="language.code" class="size-[26px] outline-navy/12 transition-transform duration-200 ease-[cubic-bezier(.2,.7,.2,1)] group-hover/option:scale-108 group-hover/option:-rotate-8 motion-reduce:transition-none" />
                                <span class="flex-1">{{ language.label }}</span>
                                <span v-if="language.code === (fr ? 'fr' : 'en')" class="grid size-5 place-items-center rounded-full bg-brand text-white"><Icon name="check" class="h-3 w-3" /></span>
                            </a>
                        </nav>
                    </details>
                    <div class="hidden xl:flex items-center gap-5">
                        <Link :href="currentUser ? route('dashboard') : `${prefix}/register`" class="cta cta-light focus-ring px-6 py-2.5 text-[14px] focus-visible:outline-white">{{ account }}</Link>
                    </div>
                    <details ref="mobileMenu" name="public-header-menu" class="group xl:hidden" @keydown.esc.stop.prevent="dismissMenu(mobileMenu)">
                        <summary class="focus-ring flex size-10 cursor-pointer list-none items-center justify-center rounded-[9px] hover:bg-white/8 focus-visible:outline-white [&::-webkit-details-marker]:hidden"><span class="sr-only">{{ fr ? 'Menu' : 'Navigation menu' }}</span><Icon name="menu" class="h-6 w-6 group-open:hidden" /><Icon name="x" class="hidden h-6 w-6 group-open:block" /></summary>
                        <nav :aria-label="fr ? 'Navigation mobile' : 'Mobile navigation'" class="absolute inset-x-0 top-full max-h-[calc(100vh-72px)] overflow-y-auto border-t border-white/10 bg-navy-700">
                            <div class="public-container py-4">
                                <a v-for="link in links" :key="link.href" :href="link.href" :class="['focus-ring flex items-center justify-between px-3 py-[15px] text-[17px] tracking-[.04em] focus-visible:outline-white', isActive(link.href) ? 'rounded-card border-b border-transparent bg-white/10 text-white' : 'border-b border-white/10 text-white/85']" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}<Icon name="arrow-right" class="h-4 w-4 opacity-50" /></a>
                                <Link :href="currentUser ? route('dashboard') : `${prefix}/register`" class="cta cta-light focus-ring mt-4 w-full focus-visible:outline-white">{{ account }}</Link>
                            </div>
                        </nav>
                    </details>
                </div>
            </div>
        </header>
        <div v-if="page.props.toast?.message" role="status" class="public-container mt-5"><p class="rounded-card bg-blue-50 px-5 py-3 text-sm text-navy">{{ page.props.toast.message }}</p></div>
        <main id="main" tabindex="-1" class="flex-1 focus:outline-hidden"><slot /></main>
        <footer class="bg-pale text-navy">
            <div class="public-container grid gap-10 pt-20 md:grid-cols-2 max-[768px]:pt-14">
                <div>
                    <p class="text-[17px] text-subtle">{{ fr ? 'Une question ?' : 'Have a question?' }}</p>
                    <h2 class="mt-2 text-[length:clamp(30px,3.4vw,46px)] leading-[1.2]">{{ fr ? 'Nous sommes là pour vous aider' : 'We would love to help' }}</h2>
                    <ul class="mt-7 grid gap-3 text-[17px]">
                        <li v-for="link in helpLinks" :key="link.href"><Link :href="link.href" class="focus-ring rounded-[2px] text-brand transition-colors duration-180 hover:text-navy">{{ link.label }}</Link></li>
                    </ul>
                </div>
                <div class="md:pt-9">
                    <p class="max-w-[460px] text-[17px] leading-[1.75] text-subtle">{{ fr ? 'Stages et emplois en informatique pour étudiants et professionnels, partout dans le monde. Des sources claires pour préparer votre prochaine candidature.' : 'IT internships and jobs for students and professionals, around the world. Clear sources and practical details for your next application.' }}</p>
                    <Link :href="`${prefix}/register?account=company`" class="cta cta-secondary focus-ring mt-7">{{ fr ? 'Publier une offre' : 'Post a role' }}</Link>
                </div>
            </div>
            <div class="public-container mt-16 flex flex-wrap items-center justify-between gap-x-10 gap-y-6 pb-10 max-[768px]:mt-12">
                <Link :href="prefix || '/'" :class="brandLink"><BrandLogo size="sm" class="[&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                <nav :aria-label="fr ? 'Pied de page' : 'Footer'" class="flex flex-wrap gap-x-9 gap-y-3 text-[15px]">
                    <Link v-for="link in links" :key="link.href" :href="link.href" :class="footerLink">{{ link.label }}</Link>
                    <Link :href="`${prefix}/contact`" :class="footerLink">Contact</Link>
                </nav>
            </div>
            <div class="public-container flex flex-col items-center gap-2 border-t border-navy/15 py-8 text-center text-[15px] text-subtle">
                <p>© {{ year }} Nextward. {{ fr ? 'Créé par' : 'Built by' }} <a href="https://alabaganne.com/" class="focus-ring rounded-[2px] text-navy transition-colors duration-180 hover:text-brand">Ala Baganne</a></p>
                <nav :aria-label="fr ? 'Informations légales' : 'Legal'" class="flex gap-6">
                    <Link :href="`${prefix}/privacy`" class="focus-ring rounded-[2px] transition-colors duration-180 hover:text-brand">{{ fr ? 'Confidentialité' : 'Privacy' }}</Link>
                    <Link :href="`${prefix}/terms`" class="focus-ring rounded-[2px] transition-colors duration-180 hover:text-brand">{{ fr ? 'Conditions d’utilisation' : 'Terms' }}</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
