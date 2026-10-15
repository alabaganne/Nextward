<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, Globe2 } from '@lucide/vue';
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const links = computed(() => [
    { label: fr.value ? 'Opportunités' : 'Opportunities', href: `${prefix.value}/opportunities` },
    { label: fr.value ? 'Étudiants' : 'For students', href: `${prefix.value}/for-students` },
    { label: fr.value ? 'Professionnels' : 'For professionals', href: `${prefix.value}/for-professionals` },
    { label: fr.value ? 'Guides carrière (anglais)' : 'Career guides', href: '/blog' },
]);
const languageMenu = ref(null);
const mobileMenu = ref(null);
const scrolled = ref(false);
const currentPath = computed(() => page.url.split(/[?#]/)[0]);
const isActive = (href) => currentPath.value === href || currentPath.value.startsWith(`${href}/`);
const languageOptions = computed(() => {
    const path = currentPath.value.replace(/^\/fr(?=\/|$)/, '') || '/';
    const translatedPaths = ['/', '/for-students', '/for-professionals', '/opportunities', ...['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'].map((category) => `/opportunities/${category}`)];
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
    <div class="min-h-screen bg-[#faf9f6] text-navy flex flex-col">
        <a href="#main" class="focus-ring sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ fr ? 'Aller au contenu' : 'Skip to content' }}</a>
        <header class="site-header" :class="{ 'is-scrolled': scrolled }">
            <div class="public-container flex h-[72px] items-center justify-between gap-4">
                <Link :href="prefix || '/'" class="focus-ring brand-link"><BrandLogo size="sm" class="!h-9 [&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                <nav :aria-label="fr ? 'Navigation principale' : 'Main navigation'" class="hidden xl:flex items-center gap-8">
                    <Link v-for="link in links" :key="link.href" :href="link.href" class="focus-ring nav-link" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}</Link>
                </nav>
                <div class="flex shrink-0 items-center gap-2 xl:gap-5 text-sm font-semibold">
                    <details v-if="languageOptions.length" ref="languageMenu" name="public-header-menu" class="group relative" @keydown.esc.stop.prevent="dismissMenu(languageMenu)" @focusout="($event.relatedTarget && !languageMenu.contains($event.relatedTarget)) && (languageMenu.open = false)">
                        <summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" aria-controls="public-language-options" class="focus-ring language-toggle">
                            <Globe2 class="h-4 w-4" aria-hidden="true" />
                            <span>{{ fr ? 'FR' : 'EN' }}</span>
                            <ChevronDown class="h-3.5 w-3.5 transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <nav id="public-language-options" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="absolute right-0 top-full mt-3 w-44 rounded-xl border border-line bg-white p-1.5 text-navy">
                            <a v-for="language in languageOptions" :key="language.code" :href="language.href" :lang="language.code" :hreflang="language.code" :aria-current="language.code === (fr ? 'fr' : 'en') ? 'true' : undefined" class="focus-ring flex min-h-11 items-center justify-between rounded-lg px-3 py-2 transition hover:bg-pale" :class="language.code === (fr ? 'fr' : 'en') ? 'bg-pale text-blue-700' : 'text-slate-700'">
                                {{ language.label }}<Icon v-if="language.code === (fr ? 'fr' : 'en')" name="check" class="h-4 w-4" />
                            </a>
                        </nav>
                    </details>
                    <div class="hidden xl:flex items-center gap-5">
                        <template v-if="!currentUser"><Link href="/login" class="focus-ring nav-link">{{ fr ? 'Connexion' : 'Log in' }}</Link><Link href="/register" class="cta cta-light focus-ring px-4 py-2.5 text-sm">{{ fr ? 'Créer un compte' : 'Create account' }}</Link></template>
                        <Link v-else href="/dashboard" class="cta cta-light focus-ring px-4 py-2.5 text-sm">{{ fr ? 'Mon espace' : 'Dashboard' }}</Link>
                    </div>
                    <details ref="mobileMenu" name="public-header-menu" class="group xl:hidden" @keydown.esc.stop.prevent="dismissMenu(mobileMenu)">
                        <summary class="focus-ring menu-toggle"><span class="sr-only">{{ fr ? 'Menu' : 'Navigation menu' }}</span><Icon name="menu" class="h-6 w-6 group-open:hidden" /><Icon name="x" class="hidden h-6 w-6 group-open:block" /></summary>
                        <nav :aria-label="fr ? 'Navigation mobile' : 'Mobile navigation'" class="mobile-menu">
                            <div class="public-container py-4">
                                <a v-for="link in links" :key="link.href" :href="link.href" class="focus-ring mobile-link" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}<Icon name="arrow-up-right" class="h-4 w-4 opacity-50" /></a>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <Link :href="currentUser ? '/dashboard' : '/login'" class="cta cta-ghost focus-ring">{{ currentUser ? (fr ? 'Mon espace' : 'Dashboard') : (fr ? 'Connexion' : 'Log in') }}</Link>
                                    <Link v-if="!currentUser" href="/register" class="cta cta-light focus-ring">{{ fr ? 'Créer un compte' : 'Create account' }}</Link>
                                </div>
                            </div>
                        </nav>
                    </details>
                </div>
            </div>
        </header>
        <div v-if="page.props.toast?.message" role="status" class="public-container mt-5"><p class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-900">{{ page.props.toast.message }}</p></div>
        <main id="main" tabindex="-1" class="flex-1 focus:outline-hidden"><slot /></main>
        <footer class="border-t border-navy/10">
            <div class="public-container py-12 grid gap-8 md:grid-cols-2">
                <div class="max-w-md"><Link :href="prefix || '/'" class="focus-ring inline-flex items-center gap-2.5 rounded-lg font-display text-lg font-semibold tracking-tight text-navy"><BrandLogo size="sm" class="[&>img]:!h-8 [&>img]:!w-8" />Nextward</Link><p class="mt-4 text-sm leading-6 text-slate-600">{{ fr ? 'Stages et emplois pour étudiants et professionnels, en Tunisie et à l’international. Des sources claires pour préparer votre prochaine candidature.' : 'Internships and jobs for students and professionals, in Tunisia and around the world. Clear sources and practical details for your next application.' }}</p></div>
                <nav :aria-label="fr ? 'Pied de page' : 'Footer'" class="grid grid-cols-2 gap-4 text-sm font-medium text-slate-600"><Link v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</Link><Link href="/about">{{ fr ? 'À propos (anglais)' : 'About' }}</Link><Link href="/contact">Contact</Link><Link href="/register?account=company">{{ fr ? 'Publier une offre' : 'Post an opportunity' }}</Link></nav>
            </div>
            <div class="public-container border-t border-navy/5 py-5 text-xs text-slate-500">{{ fr ? 'Créé par' : 'Built by' }} <a href="https://alabaganne.com/" class="font-medium text-slate-700">Ala Baganne</a></div>
        </footer>
    </div>
</template>
<style scoped>
.site-header { position: sticky; top: 0; z-index: 40; border-bottom: 1px solid transparent; background: var(--color-navy); color: #fff; transition: border-color 200ms; }
.site-header.is-scrolled { border-bottom-color: rgb(255 255 255 / .1); }
.site-header .focus-ring:focus-visible { outline-color: #fff; }
.brand-link { display: inline-flex; align-items: center; gap: 10px; border-radius: 8px; font-family: var(--font-display); font-size: 20px; font-weight: 600; letter-spacing: -.035em; }
.nav-link { position: relative; padding-block: 8px; color: rgb(255 255 255 / .72); font-size: 14px; font-weight: 600; transition: color 180ms; }
.nav-link::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; border-radius: 2px; background: #f78c78; transform: scaleX(0); transform-origin: left; transition: transform 220ms cubic-bezier(.2, .7, .2, 1); }
.nav-link:hover, .nav-link[aria-current='page'] { color: #fff; }
.nav-link:hover::after { transform: scaleX(1); }
.nav-link[aria-current='page']::after { transform: scaleX(1); background: #8ab2ff; }
.language-toggle { display: flex; align-items: center; gap: 7px; height: 38px; padding-inline: 11px; border: 1px solid rgb(255 255 255 / .16); border-radius: 9px; color: rgb(255 255 255 / .8); cursor: pointer; list-style: none; transition: border-color 180ms, color 180ms; }
.language-toggle::-webkit-details-marker, .menu-toggle::-webkit-details-marker { display: none; }
.language-toggle:hover, details[open] > .language-toggle { border-color: rgb(255 255 255 / .45); color: #fff; }
.menu-toggle { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 9px; cursor: pointer; list-style: none; }
.menu-toggle:hover { background: rgb(255 255 255 / .08); }
.mobile-menu { position: absolute; left: 0; right: 0; top: 100%; max-height: calc(100vh - 72px); overflow-y: auto; border-top: 1px solid rgb(255 255 255 / .08); background: #0f2150; }
.mobile-link { display: flex; align-items: center; justify-content: space-between; padding: 16px 4px; border-bottom: 1px solid rgb(255 255 255 / .08); font-size: 17px; font-weight: 600; color: rgb(255 255 255 / .85); }
.mobile-link[aria-current='page'] { color: #fff; border-left: 3px solid #8ab2ff; padding-left: 14px; }
@media (prefers-reduced-motion: reduce) {
    .nav-link::after { transition: none; }
}
</style>
