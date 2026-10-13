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
const languageOptions = computed(() => {
    const currentPath = page.url.split(/[?#]/)[0];
    const path = currentPath.replace(/^\/fr(?=\/|$)/, '') || '/';
    const translatedPaths = ['/', '/for-students', '/for-professionals', '/opportunities', ...['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'].map((category) => `/opportunities/${category}`)];
    if (!translatedPaths.includes(path)) return [];
    const suffix = page.url.slice(currentPath.length);
    return [
        { code: 'en', label: 'English', href: path + suffix },
        { code: 'fr', label: 'Français', href: (path === '/' ? '/fr' : `/fr${path}`) + suffix },
    ];
});
const closeLanguageMenu = (event) => {
    if (languageMenu.value && !languageMenu.value.contains(event.target)) languageMenu.value.open = false;
};
const dismissLanguageMenu = () => {
    if (!languageMenu.value?.open) return;
    languageMenu.value.open = false;
    languageMenu.value.querySelector('summary')?.focus();
};
onMounted(() => document.addEventListener('click', closeLanguageMenu));
onBeforeUnmount(() => document.removeEventListener('click', closeLanguageMenu));
</script>
<template>
    <div class="min-h-screen bg-[#faf9f6] text-navy flex flex-col">
        <a href="#main" class="focus-ring sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ fr ? 'Aller au contenu' : 'Skip to content' }}</a>
        <header class="sticky top-0 z-40 border-b border-navy/10 bg-[#faf9f6]/95 backdrop-blur-md">
            <div class="public-container min-h-20 flex items-center justify-between gap-4">
                <Link :href="prefix || '/'" class="focus-ring flex items-center rounded-lg"><BrandLogo /></Link>
                <nav :aria-label="fr ? 'Navigation principale' : 'Main navigation'" class="hidden xl:flex items-center gap-1 text-sm font-semibold text-slate-600">
                    <Link v-for="link in links" :key="link.href" :href="link.href" class="focus-ring rounded-lg px-3 py-2 transition hover:bg-pale hover:text-navy" :aria-current="page.url.split('?')[0] === link.href ? 'page' : undefined">{{ link.label }}</Link>
                </nav>
                <div class="flex shrink-0 items-center gap-2 xl:gap-3 text-sm font-semibold">
                    <details v-if="languageOptions.length" ref="languageMenu" name="public-header-menu" class="group relative" @keydown.esc.stop.prevent="dismissLanguageMenu" @focusout="($event.relatedTarget && !languageMenu.contains($event.relatedTarget)) && (languageMenu.open = false)">
                        <summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" aria-controls="public-language-options" class="focus-ring flex h-10 cursor-pointer list-none items-center gap-2 whitespace-nowrap rounded-lg border border-line bg-white px-3 text-slate-700 transition hover:border-blue-300 hover:text-blue-700 [&::-webkit-details-marker]:hidden">
                            <Globe2 class="hidden h-4 w-4 sm:block" aria-hidden="true" />
                            <span class="hidden sm:inline">{{ fr ? 'Français' : 'English' }}</span><span class="sm:hidden" aria-hidden="true">{{ fr ? 'FR' : 'EN' }}</span>
                            <ChevronDown class="h-3.5 w-3.5 transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <nav id="public-language-options" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="absolute right-0 top-full mt-2 w-44 rounded-xl border border-line bg-white p-1.5 shadow-lg">
                            <a v-for="language in languageOptions" :key="language.code" :href="language.href" :lang="language.code" :hreflang="language.code" :aria-current="language.code === (fr ? 'fr' : 'en') ? 'true' : undefined" class="focus-ring flex min-h-11 items-center justify-between rounded-lg px-3 py-2 transition hover:bg-pale" :class="language.code === (fr ? 'fr' : 'en') ? 'bg-pale text-blue-700' : 'text-slate-700'">
                                {{ language.label }}<Icon v-if="language.code === (fr ? 'fr' : 'en')" name="check" class="h-4 w-4" />
                            </a>
                        </nav>
                    </details>
                    <div class="hidden xl:flex items-center gap-3">
                        <template v-if="!currentUser"><Link href="/login" class="focus-ring rounded-lg">{{ fr ? 'Connexion' : 'Log in' }}</Link><Link href="/register" class="cta cta-primary focus-ring px-4 py-2 text-sm">{{ fr ? 'Créer un compte' : 'Create account' }}</Link></template>
                        <Link v-else href="/dashboard" class="cta cta-primary focus-ring px-4 py-2 text-sm">{{ fr ? 'Mon espace' : 'Dashboard' }}</Link>
                    </div>
                    <details name="public-header-menu" class="xl:hidden relative">
                        <summary class="focus-ring h-10 w-10 rounded-lg list-none cursor-pointer flex items-center justify-center"><span class="sr-only">{{ fr ? 'Menu' : 'Navigation menu' }}</span><Icon name="menu" class="h-6 w-6" /></summary>
                        <nav :aria-label="fr ? 'Navigation mobile' : 'Mobile navigation'" class="absolute right-0 top-14 w-72 max-w-[85vw] rounded-xl border border-line shadow-lg bg-white p-4 flex flex-col gap-2 font-semibold">
                            <a v-for="link in links" :key="link.href" :href="link.href" class="focus-ring rounded-lg p-3">{{ link.label }}</a>
                            <Link :href="currentUser ? '/dashboard' : '/login'" class="p-3">{{ currentUser ? (fr ? 'Mon espace' : 'Dashboard') : (fr ? 'Connexion' : 'Log in') }}</Link>
                            <Link v-if="!currentUser" href="/register" class="cta cta-primary">{{ fr ? 'Créer un compte' : 'Create account' }}</Link>
                        </nav>
                    </details>
                </div>
            </div>
        </header>
        <div v-if="page.props.toast?.message" role="status" class="public-container mt-5"><p class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-900">{{ page.props.toast.message }}</p></div>
        <main id="main" tabindex="-1" class="flex-1 focus:outline-hidden"><slot /></main>
        <footer class="border-t border-navy/10">
            <div class="public-container py-12 grid gap-8 md:grid-cols-2">
                <div class="max-w-md"><Link :href="prefix || '/'"><BrandLogo size="sm" /></Link><p class="mt-4 text-sm leading-6 text-slate-600">{{ fr ? 'Stages et emplois pour étudiants et professionnels, en Tunisie et à l’international. Des sources claires pour préparer votre prochaine candidature.' : 'Internships and jobs for students and professionals, in Tunisia and around the world. Clear sources and practical details for your next application.' }}</p></div>
                <nav :aria-label="fr ? 'Pied de page' : 'Footer'" class="grid grid-cols-2 gap-4 text-sm font-medium text-slate-600"><Link v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</Link><Link href="/about">{{ fr ? 'À propos (anglais)' : 'About' }}</Link><Link href="/contact">Contact</Link><Link href="/register?account=company">{{ fr ? 'Publier une offre' : 'Post an opportunity' }}</Link></nav>
            </div>
            <div class="public-container border-t border-navy/5 py-5 text-xs text-slate-500">{{ fr ? 'Créé par' : 'Built by' }} <a href="https://alabaganne.com/" class="font-medium text-slate-700">Ala Baganne</a></div>
        </footer>
    </div>
</template>
