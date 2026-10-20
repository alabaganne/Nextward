<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, MapPin } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import { appPath, isAppPath, sitePath } from '@/utils/url';
const props = defineProps({ company: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const count = computed(() => props.company.open_roles_count);
// The workspace links to its own pages; the public site to the public ones.
const link = (url) => isAppPath(page.url) ? appPath(url) : sitePath(url);
const href = computed(() => props.company.href || link(props.company.url));
const openRoles = computed(() => count.value
    ? text(`${count.value} open ${count.value === 1 ? 'role' : 'roles'}`, `${count.value} ${count.value === 1 ? 'offre ouverte' : 'offres ouvertes'}`)
    : text('No open roles right now', 'Aucune offre ouverte'));
</script>

<template>
    <article class="bg-white border border-line rounded-[18px] p-5 flex flex-col min-w-0 transition-colors hover:border-blue-300">
        <div class="flex items-center gap-3.5">
            <CompanyLogo class="size-12 rounded-xl text-base border border-line" :name="company.name" :src="company.logo" />
            <div class="min-w-0">
                <h3 class="font-display text-[17px] font-semibold leading-snug truncate"><Link :href="href" class="hover:text-blue-700">{{ company.name }}</Link></h3>
                <p v-if="company.location" class="mt-0.5 flex items-center gap-1 text-xs text-slate-500 min-w-0"><MapPin :size="13" class="shrink-0" aria-hidden="true" /><span class="truncate">{{ company.location }}</span></p>
            </div>
        </div>
        <ul v-if="company.roles?.length" class="mt-4 space-y-2 text-sm" :aria-label="text(`Latest roles at ${company.name}`, `Dernières offres chez ${company.name}`)">
            <li v-for="role in company.roles" :key="role.id" class="flex items-baseline gap-2 min-w-0">
                <span class="shrink-0 rounded-md bg-paper-2 px-1.5 py-0.5 text-[11px] font-semibold text-ink-700">{{ role.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</span>
                <Link :href="link(role.url)" class="truncate text-ink-700 hover:text-blue-700" :title="role.title">{{ role.title }}</Link>
            </li>
        </ul>
        <div class="mt-auto pt-4">
            <Link :href="href" class="flex items-center justify-between gap-3 border-t border-line pt-4 text-sm font-semibold" :class="count ? 'text-blue-700' : 'text-slate-500'">
                {{ openRoles }}<span class="sr-only"> {{ text('at', 'chez') }} {{ company.name }}</span>
                <ArrowRight :size="16" aria-hidden="true" />
            </Link>
        </div>
    </article>
</template>
