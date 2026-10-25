<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, MapPin } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';
import { appPath, isAppPath, sitePath } from '@/utils/url';
const props = defineProps({ company: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
// The workspace links to its own pages; the public site to the public ones.
const link = (url) => isAppPath(page.url) ? appPath(url) : sitePath(url);
const href = computed(() => props.company.href || link(props.company.url));
const jobs = computed(() => props.company.open_jobs_count ?? 0);
const internships = computed(() => props.company.open_internships_count ?? 0);
const details = computed(() => [
    props.company.location,
    { startup: text('Startup', 'Start-up'), established: text('Established', 'Établie') }[props.company.stage],
    props.company.size && text(`${props.company.size} people`, `${props.company.size} personnes`),
].filter(Boolean));
</script>

<template>
    <article class="bg-white border border-line rounded-[18px] p-5 flex flex-col min-w-0 transition-colors hover:border-blue-300">
        <div class="flex items-start gap-3.5">
            <CompanyLogo class="size-12 rounded-xl text-base border border-line" :name="company.name" :src="company.logo" />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5 min-w-0">
                    <h3 class="font-display text-[17px] font-semibold leading-snug truncate"><Link :href="href" class="hover:text-blue-700">{{ company.name }}</Link></h3>
                    <VerifiedBadge v-if="company.verified" compact />
                </div>
                <p v-if="details.length" class="mt-0.5 flex items-center gap-1 text-xs text-slate-500 min-w-0">
                    <MapPin v-if="company.location" :size="13" class="shrink-0" aria-hidden="true" /><span class="truncate">{{ details.join(' · ') }}</span>
                </p>
            </div>
        </div>

        <p v-if="company.about" class="mt-3.5 text-[13.5px] leading-6 text-slate-600 line-clamp-3" dir="auto">{{ company.about }}</p>
        <p v-else class="mt-3.5 text-[13.5px] leading-6 text-slate-400">{{ text('No description yet.', 'Pas encore de description.') }}</p>

        <dl class="mt-4 grid grid-cols-2 gap-2">
            <div class="rounded-xl px-3 py-2.5" :class="jobs ? 'bg-[#EEF4FF]' : 'bg-paper-2'">
                <dt class="text-xs font-semibold" :class="jobs ? 'text-blue-700' : 'text-slate-500'">{{ text('Jobs', 'Emplois') }}</dt>
                <dd class="font-display text-[22px] font-semibold leading-tight" :class="jobs ? 'text-navy-900' : 'text-slate-400'">{{ jobs }}</dd>
            </div>
            <div class="rounded-xl px-3 py-2.5" :class="internships ? 'bg-[#EEF4FF]' : 'bg-paper-2'">
                <dt class="text-xs font-semibold" :class="internships ? 'text-blue-700' : 'text-slate-500'">{{ text('Internships', 'Stages') }}</dt>
                <dd class="font-display text-[22px] font-semibold leading-tight" :class="internships ? 'text-navy-900' : 'text-slate-400'">{{ internships }}</dd>
            </div>
        </dl>

        <ul v-if="company.roles?.length" class="mt-4 space-y-2 text-sm" :aria-label="text(`Latest roles at ${company.name}`, `Dernières offres chez ${company.name}`)">
            <li v-for="role in company.roles" :key="role.id" class="flex items-baseline gap-2 min-w-0">
                <span class="shrink-0 rounded-md bg-paper-2 px-1.5 py-0.5 text-[11px] font-semibold text-ink-700">{{ role.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</span>
                <Link :href="link(role.url)" class="truncate text-ink-700 hover:text-blue-700" :title="role.title">{{ role.title }}</Link>
            </li>
        </ul>
        <div class="mt-auto pt-4">
            <Link :href="href" class="flex items-center justify-between gap-3 border-t border-line pt-4 text-sm font-semibold" :class="jobs + internships ? 'text-blue-700' : 'text-slate-500'">
                {{ jobs + internships ? text('View company and roles', 'Voir l’entreprise et ses offres') : text('No open roles right now', 'Aucune offre ouverte') }}<span class="sr-only"> {{ text('at', 'chez') }} {{ company.name }}</span>
                <ArrowRight :size="16" aria-hidden="true" />
            </Link>
        </div>
    </article>
</template>
