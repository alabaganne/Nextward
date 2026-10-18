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
    const translatedPaths = ['/', '/about', '/contact', '/privacy', '/terms', '/blog', '/opportunities', '/candidates', ...['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'].map((category) => `/opportunities/${category}`)];
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
        <header class="site-header">
            <div class="public-container flex h-[72px] items-center justify-between gap-4">
                <Link :href="prefix || '/'" class="focus-ring brand-link"><BrandLogo size="sm" class="!h-9 [&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                <nav :aria-label="fr ? 'Navigation principale' : 'Main navigation'" class="hidden xl:flex items-center gap-8">
                    <Link v-for="link in links" :key="link.href" :href="link.href" class="focus-ring nav-link" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}</Link>
                </nav>
                <div class="flex shrink-0 items-center gap-2 xl:gap-4 text-sm font-semibold">
                    <details v-if="languageOptions.length" ref="languageMenu" name="public-header-menu" class="group relative" @keydown.esc.stop.prevent="dismissMenu(languageMenu)" @focusout="($event.relatedTarget && !languageMenu.contains($event.relatedTarget)) && (languageMenu.open = false)">
                        <summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" aria-controls="public-language-options" class="focus-ring language-toggle">
                            <Flag :code="fr ? 'fr' : 'en'" />
                            <span>{{ fr ? 'FR' : 'EN' }}</span>
                            <ChevronDown class="h-3.5 w-3.5 opacity-70 transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <nav id="public-language-options" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="language-menu">
                            <a v-for="language in languageOptions" :key="language.code" :href="language.href" :lang="language.code" :hreflang="language.code" :aria-current="language.code === (fr ? 'fr' : 'en') ? 'true' : undefined" class="focus-ring language-option">
                                <Flag :code="language.code" class="language-flag" />
                                <span class="flex-1">{{ language.label }}</span>
                                <span v-if="language.code === (fr ? 'fr' : 'en')" class="language-check"><Icon name="check" class="h-3 w-3" /></span>
                            </a>
                        </nav>
                    </details>
                    <div class="hidden xl:flex items-center gap-5">
                        <template v-if="!currentUser"><Link :href="`${prefix}/login`" class="focus-ring nav-link">{{ fr ? 'Connexion' : 'Log in' }}</Link><Link :href="`${prefix}/register`" class="cta cta-light focus-ring px-4 py-2.5 text-sm">{{ fr ? 'Créer un compte' : 'Create account' }}</Link></template>
                        <Link v-else href="/dashboard" class="cta cta-light focus-ring px-4 py-2.5 text-sm">{{ fr ? 'Mon espace' : 'Dashboard' }}</Link>
                    </div>
                    <details ref="mobileMenu" name="public-header-menu" class="group xl:hidden" @keydown.esc.stop.prevent="dismissMenu(mobileMenu)">
                        <summary class="focus-ring menu-toggle"><span class="sr-only">{{ fr ? 'Menu' : 'Navigation menu' }}</span><Icon name="menu" class="h-6 w-6 group-open:hidden" /><Icon name="x" class="hidden h-6 w-6 group-open:block" /></summary>
                        <nav :aria-label="fr ? 'Navigation mobile' : 'Mobile navigation'" class="mobile-menu">
                            <div class="public-container py-4">
                                <a v-for="link in links" :key="link.href" :href="link.href" class="focus-ring mobile-link" :aria-current="isActive(link.href) ? 'page' : undefined">{{ link.label }}<Icon name="arrow-right" class="h-4 w-4 opacity-50" /></a>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <Link :href="currentUser ? '/dashboard' : `${prefix}/login`" class="cta cta-ghost focus-ring">{{ currentUser ? (fr ? 'Mon espace' : 'Dashboard') : (fr ? 'Connexion' : 'Log in') }}</Link>
                                    <Link v-if="!currentUser" :href="`${prefix}/register`" class="cta cta-light focus-ring">{{ fr ? 'Créer un compte' : 'Create account' }}</Link>
                                </div>
                            </div>
                        </nav>
                    </details>
                </div>
            </div>
        </header>
        <div v-if="page.props.toast?.message" role="status" class="public-container mt-5"><p class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-900">{{ page.props.toast.message }}</p></div>
        <main id="main" tabindex="-1" class="flex-1 focus:outline-hidden"><slot /></main>
        <footer class="site-footer">
            <div class="public-container grid gap-10 py-14 md:grid-cols-[1.2fr_1fr]">
                <div class="max-w-md">
                    <Link :href="prefix || '/'" class="focus-ring brand-link"><BrandLogo size="sm" class="[&>img]:!h-9 [&>img]:!w-9" /><span>Nextward</span></Link>
                    <p class="mt-4 text-sm leading-6 text-slate-300">{{ fr ? 'Stages et emplois pour étudiants et professionnels, en Tunisie et à l’international. Des sources claires pour préparer votre prochaine candidature.' : 'Internships and jobs for students and professionals, in Tunisia and around the world. Clear sources and practical details for your next application.' }}</p>
                </div>
                <nav :aria-label="fr ? 'Pied de page' : 'Footer'" class="grid grid-cols-2 content-start gap-x-8 gap-y-3 text-sm font-medium">
                    <Link v-for="link in links" :key="link.href" :href="link.href" class="focus-ring footer-link">{{ link.label }}</Link>
                    <Link :href="`${prefix}/candidates`" class="focus-ring footer-link">{{ fr ? 'Profils de candidats' : 'Candidate profiles' }}</Link>
                    <Link :href="`${prefix}/contact`" class="focus-ring footer-link">Contact</Link>
                    <Link :href="`${prefix}/register?account=company`" class="focus-ring footer-link">{{ fr ? 'Publier une offre' : 'Post an opportunity' }}</Link>
                </nav>
            </div>
            <div class="public-container flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-white/10 py-5 text-xs text-slate-400">
                <p>{{ fr ? 'Créé par' : 'Built by' }} <a href="https://alabaganne.com/" class="focus-ring footer-link font-medium">Ala Baganne</a></p>
                <nav :aria-label="fr ? 'Informations légales' : 'Legal'" class="flex gap-5">
                    <Link :href="`${prefix}/privacy`" class="focus-ring footer-link">{{ fr ? 'Confidentialité' : 'Privacy' }}</Link>
                    <Link :href="`${prefix}/terms`" class="focus-ring footer-link">{{ fr ? 'Conditions d’utilisation' : 'Terms' }}</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
<style scoped>
.site-header { position: sticky; top: 0; z-index: 40; border-bottom: 1px solid rgb(255 255 255 / .08); background: var(--color-navy); color: #fff; }
.site-header .focus-ring:focus-visible, .site-footer .focus-ring:focus-visible { outline-color: #fff; }
.site-footer { background: var(--color-navy); color: #fff; }
.footer-link { width: fit-content; border-radius: 2px; color: rgb(255 255 255 / .72); text-decoration: underline; text-decoration-color: transparent; text-underline-offset: 5px; transition: color 180ms, text-decoration-color 180ms; }
.footer-link:hover { color: #fff; text-decoration-color: #f78c78; }
.brand-link { display: inline-flex; align-items: center; gap: 10px; border-radius: 8px; font-family: var(--font-display); font-size: 20px; font-weight: 600; letter-spacing: -.035em; }
.nav-link { position: relative; padding-block: 8px; color: rgb(255 255 255 / .72); font-size: 14px; font-weight: 600; transition: color 180ms; }
.nav-link::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; border-radius: 2px; background: #f78c78; transform: scaleX(0); transform-origin: left; transition: transform 220ms cubic-bezier(.2, .7, .2, 1); }
.nav-link:hover, .nav-link[aria-current='page'] { color: #fff; }
.nav-link:hover::after { transform: scaleX(1); }
.nav-link[aria-current='page']::after { transform: scaleX(1); background: #8ab2ff; }
.language-toggle { display: flex; align-items: center; gap: 8px; height: 38px; padding: 0 11px 0 6px; border: 1px solid rgb(255 255 255 / .16); border-radius: 999px; background: rgb(255 255 255 / .04); color: #fff; font-size: 13px; letter-spacing: .02em; cursor: pointer; list-style: none; transition: border-color 180ms, background 180ms; }
.language-toggle::-webkit-details-marker, .menu-toggle::-webkit-details-marker { display: none; }
.language-toggle:hover, details[open] > .language-toggle { border-color: rgb(255 255 255 / .4); background: rgb(255 255 255 / .1); }
.language-toggle .flag { width: 24px; height: 24px; outline-color: rgb(255 255 255 / .25); }
.language-menu { position: absolute; right: 0; top: calc(100% + 12px); width: 196px; padding: 6px; border: 1px solid var(--color-line); border-radius: 16px; background: #fff; color: var(--color-navy); transform-origin: top right; animation: menu-pop 160ms cubic-bezier(.2, .7, .2, 1); }
.language-menu::before { content: ''; position: absolute; top: -7px; right: 22px; width: 12px; height: 12px; border-top: 1px solid var(--color-line); border-left: 1px solid var(--color-line); background: #fff; transform: rotate(45deg); }
.language-option { position: relative; display: flex; align-items: center; gap: 12px; min-height: 46px; padding: 8px 10px; border-radius: 11px; color: #334155; font-size: 14px; font-weight: 600; transition: background 150ms, color 150ms; }
.language-option:hover { background: #f6f8fc; color: var(--color-navy); }
.language-option[aria-current='true'] { background: #eef4ff; color: var(--color-brand-dark); }
.language-option .flag { width: 26px; height: 26px; transition: transform 200ms cubic-bezier(.2, .7, .2, 1); }
.language-option:hover .flag { transform: rotate(-8deg) scale(1.08); }
.site-header .language-option:focus-visible { outline-color: var(--color-brand); }
.language-check { display: grid; place-items: center; width: 20px; height: 20px; border-radius: 50%; background: var(--color-brand); color: #fff; }
.language-check svg { width: 12px; height: 12px; }
@keyframes menu-pop { from { opacity: 0; transform: translateY(-4px) scale(.96); } }
.menu-toggle { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 9px; cursor: pointer; list-style: none; }
.menu-toggle:hover { background: rgb(255 255 255 / .08); }
.mobile-menu { position: absolute; left: 0; right: 0; top: 100%; max-height: calc(100vh - 72px); overflow-y: auto; border-top: 1px solid rgb(255 255 255 / .08); background: #0f2150; }
.mobile-link { display: flex; align-items: center; justify-content: space-between; padding: 15px 12px; border-bottom: 1px solid rgb(255 255 255 / .08); font-size: 17px; font-weight: 600; color: rgb(255 255 255 / .85); }
.mobile-link[aria-current='page'] { border-radius: 10px; border-bottom-color: transparent; background: rgb(255 255 255 / .1); color: #fff; }
@media (prefers-reduced-motion: reduce) {
    .nav-link::after { transition: none; }
    .language-menu { animation: none; }
    .language-option .flag { transition: none; }
}
</style>
