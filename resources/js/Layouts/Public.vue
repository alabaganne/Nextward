<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import Flag from '@/Shared/Flag.vue';
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const links = computed(() => [
    { label: fr.value ? 'Opportunités' : 'Opportunities', href: `${prefix.value}/opportunities` },
    { label: fr.value ? 'Entreprises' : 'Companies', href: `${prefix.value}/companies` },
    { label: fr.value ? 'Talents' : 'Talent', href: `${prefix.value}/candidates` },
    { label: fr.value ? 'Guides carrière' : 'Career guides', href: `${prefix.value}/blog` },
    { label: fr.value ? 'À propos' : 'About', href: `${prefix.value}/about` },
]);
const languageMenu = ref(null);
const mobileMenu = ref(null);
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
const brandLink = 'focus-ring inline-flex items-center gap-2.5 rounded-lg font-display text-[20px] font-semibold tracking-[-.035em] focus-visible:outline-white';
const navLink = 'focus-ring relative py-2 text-[14px] font-semibold text-white/72 transition-colors duration-180 after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:rounded-[2px] after:bg-coral after:transition-transform after:duration-220 after:ease-[cubic-bezier(.2,.7,.2,1)] hover:text-white hover:after:scale-x-100 focus-visible:outline-white aria-[current=page]:text-white aria-[current=page]:after:scale-x-100 aria-[current=page]:after:bg-blue-300 motion-reduce:after:transition-none';
const footerLink = 'focus-ring w-fit rounded-[2px] text-white/72 underline decoration-transparent underline-offset-[5px] transition-[color,text-decoration-color] duration-180 hover:text-white hover:decoration-coral focus-visible:outline-white';
onMounted(() => {
    document.addEventListener('click', closeMenus);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenus);
});
</script>
<template>
    <div class="min-h-screen bg-pale text-navy flex flex-col">
        <a href="#main" class="focus-ring sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ fr ? 'Aller au contenu' : 'Skip to content' }}</a>
        <header class="sticky top-0 z-40 border-b border-white/8 bg-navy text-white">
            <div class="public-container flex h-[72px] items-center justify-between gap-4">
                <Link :href="prefix || '/'" :class="brandLink"><BrandLogo size="sm" class="!h-9 [&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                <nav :aria-label="fr ? 'Navigation principale' : 'Main navigation'" class="hidden xl:flex items-center gap-8">
                    <Link v-for="link in links" :key="link.href" :href="link.href" :class="navLink" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}</Link>
                </nav>
                <div class="flex shrink-0 items-center gap-2 xl:gap-4 text-sm font-semibold">
                    <details v-if="languageOptions.length" ref="languageMenu" name="public-header-menu" class="group relative" @keydown.esc.stop.prevent="dismissMenu(languageMenu)" @focusout="($event.relatedTarget && !languageMenu.contains($event.relatedTarget)) && (languageMenu.open = false)">
                        <summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" aria-controls="public-language-options" class="focus-ring flex h-[38px] cursor-pointer list-none items-center gap-2 rounded-full border border-white/16 bg-white/4 pr-[11px] pl-1.5 text-[13px] tracking-[.02em] text-white transition-[border-color,background-color] duration-180 group-open:border-white/40 group-open:bg-white/10 hover:border-white/40 hover:bg-white/10 focus-visible:outline-white [&::-webkit-details-marker]:hidden">
                            <Flag :code="fr ? 'fr' : 'en'" class="size-6 outline-white/25" />
                            <span>{{ fr ? 'FR' : 'EN' }}</span>
                            <ChevronDown class="h-3.5 w-3.5 opacity-70 transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <nav id="public-language-options" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="absolute top-[calc(100%+12px)] right-0 w-[196px] origin-top-right animate-menu-pop rounded-2xl border border-line bg-white p-1.5 text-navy before:absolute before:-top-[7px] before:right-[22px] before:size-3 before:rotate-45 before:border-t before:border-l before:border-line before:bg-white motion-reduce:animate-none">
                            <a v-for="language in languageOptions" :key="language.code" :href="language.href" :lang="language.code" :hreflang="language.code" :aria-current="language.code === (fr ? 'fr' : 'en') ? 'true' : undefined" :class="['group/option focus-ring relative flex min-h-[46px] items-center gap-3 rounded-[11px] px-2.5 py-2 text-[14px] font-semibold transition-colors duration-150', language.code === (fr ? 'fr' : 'en') ? 'bg-blue-50 text-brand-dark' : 'text-gray-700 hover:bg-pale hover:text-navy']">
                                <Flag :code="language.code" class="size-[26px] outline-navy/12 transition-transform duration-200 ease-[cubic-bezier(.2,.7,.2,1)] group-hover/option:scale-108 group-hover/option:-rotate-8 motion-reduce:transition-none" />
                                <span class="flex-1">{{ language.label }}</span>
                                <span v-if="language.code === (fr ? 'fr' : 'en')" class="grid size-5 place-items-center rounded-full bg-brand text-white"><Icon name="check" class="h-3 w-3" /></span>
                            </a>
                        </nav>
                    </details>
                    <div class="hidden xl:flex items-center gap-5">
                        <template v-if="!currentUser"><Link :href="`${prefix}/login`" :class="navLink">{{ fr ? 'Connexion' : 'Log in' }}</Link><Link :href="`${prefix}/register`" class="cta cta-light focus-ring px-4 py-2.5 text-sm focus-visible:outline-white">{{ fr ? 'Créer un compte' : 'Create account' }}</Link></template>
                        <Link v-else :href="route('dashboard')" class="cta cta-light focus-ring px-4 py-2.5 text-sm focus-visible:outline-white">{{ fr ? 'Mon espace' : 'Dashboard' }}</Link>
                    </div>
                    <details ref="mobileMenu" name="public-header-menu" class="group xl:hidden" @keydown.esc.stop.prevent="dismissMenu(mobileMenu)">
                        <summary class="focus-ring flex size-10 cursor-pointer list-none items-center justify-center rounded-[9px] hover:bg-white/8 focus-visible:outline-white [&::-webkit-details-marker]:hidden"><span class="sr-only">{{ fr ? 'Menu' : 'Navigation menu' }}</span><Icon name="menu" class="h-6 w-6 group-open:hidden" /><Icon name="x" class="hidden h-6 w-6 group-open:block" /></summary>
                        <nav :aria-label="fr ? 'Navigation mobile' : 'Mobile navigation'" class="absolute inset-x-0 top-full max-h-[calc(100vh-72px)] overflow-y-auto border-t border-white/8 bg-navy-700">
                            <div class="public-container py-4">
                                <a v-for="link in links" :key="link.href" :href="link.href" :class="['focus-ring flex items-center justify-between px-3 py-[15px] text-[17px] font-semibold focus-visible:outline-white', isActive(link.href) ? 'rounded-[10px] border-b border-transparent bg-white/10 text-white' : 'border-b border-white/8 text-white/85']" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}<Icon name="arrow-right" class="h-4 w-4 opacity-50" /></a>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <Link :href="currentUser ? route('dashboard') : `${prefix}/login`" class="cta cta-ghost focus-ring focus-visible:outline-white">{{ currentUser ? (fr ? 'Mon espace' : 'Dashboard') : (fr ? 'Connexion' : 'Log in') }}</Link>
                                    <Link v-if="!currentUser" :href="`${prefix}/register`" class="cta cta-light focus-ring focus-visible:outline-white">{{ fr ? 'Créer un compte' : 'Create account' }}</Link>
                                </div>
                            </div>
                        </nav>
                    </details>
                </div>
            </div>
        </header>
        <div v-if="page.props.toast?.message" role="status" class="public-container mt-5"><p class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-900">{{ page.props.toast.message }}</p></div>
        <main id="main" tabindex="-1" class="flex-1 focus:outline-hidden"><slot /></main>
        <footer class="bg-navy text-white">
            <div class="public-container grid gap-10 py-14 md:grid-cols-[1.2fr_1fr]">
                <div class="max-w-md">
                    <Link :href="prefix || '/'" :class="brandLink"><BrandLogo size="sm" class="[&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                    <p class="mt-4 text-sm leading-6 text-slate-300">{{ fr ? 'Stages et emplois pour étudiants et professionnels, partout dans le monde. Des sources claires pour préparer votre prochaine candidature.' : 'Internships and jobs for students and professionals, around the world. Clear sources and practical details for your next application.' }}</p>
                </div>
                <nav :aria-label="fr ? 'Pied de page' : 'Footer'" class="grid grid-cols-2 content-start gap-x-8 gap-y-3 text-sm font-medium">
                    <Link v-for="link in links" :key="link.href" :href="link.href" :class="footerLink">{{ link.label }}</Link>
                    <Link :href="`${prefix}/contact`" :class="footerLink">Contact</Link>
                    <Link :href="`${prefix}/register?account=company`" :class="footerLink">{{ fr ? 'Publier une offre' : 'Post an opportunity' }}</Link>
                </nav>
            </div>
            <div class="public-container flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-white/10 py-5 text-xs text-slate-400">
                <p>{{ fr ? 'Créé par' : 'Built by' }} <a href="https://alabaganne.com/" class="font-medium" :class="footerLink">Ala Baganne</a></p>
                <nav :aria-label="fr ? 'Informations légales' : 'Legal'" class="flex gap-5">
                    <Link :href="`${prefix}/privacy`" :class="footerLink">{{ fr ? 'Confidentialité' : 'Privacy' }}</Link>
                    <Link :href="`${prefix}/terms`" :class="footerLink">{{ fr ? 'Conditions d’utilisation' : 'Terms' }}</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
