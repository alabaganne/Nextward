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
const count = computed(() => props.company.open_roles_count || 0);
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

        <p v-if="company.about" class="mt-4 text-sm leading-6 text-slate-600 line-clamp-3" dir="auto">{{ company.about }}</p>
        <p v-else class="mt-4 text-sm leading-6 text-slate-400">{{ text('No description yet.', 'Pas encore de description.') }}</p>

        <div class="mt-auto pt-4">
            <Link :href="href" class="flex items-center justify-between gap-3 border-t border-line pt-4 text-sm font-semibold" :class="count ? 'text-blue-700' : 'text-slate-500'">
                <span>{{ count ? text(`${count} open ${count === 1 ? 'role' : 'roles'}`, `${count} ${count === 1 ? 'offre ouverte' : 'offres ouvertes'}`) : text('No open roles right now', 'Aucune offre ouverte') }}<span class="sr-only"> {{ text('at', 'chez') }} {{ company.name }}</span></span>
                <ArrowRight :size="16" aria-hidden="true" />
            </Link>
        </div>
    </article>
</template>
