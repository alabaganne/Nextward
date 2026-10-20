<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Banknote, Bookmark, Globe, MapPin } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import { openedAt, isSaved, toggleSaved } from '@/composables/useOpportunityActivity';
import { sitePath } from '@/utils/url';
const props = defineProps({ opportunity: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const user = computed(() => page.props.auth?.user);
const candidate = computed(() => user.value?.is_candidate || user.value?.userable_type === 'candidate');
const saved = computed(() => user.value ? !!props.opportunity.liked : isSaved(props.opportunity.id));
const publicUrl = computed(() => sitePath(props.opportunity.public_url) || `/opportunities/${props.opportunity.id}`);
const text = (en, french) => fr.value ? french : en;
const payLabel = computed(() => {
    const pay = props.opportunity.pay;
    if (!pay) return props.opportunity.compensation_status === 'unpaid' ? text('Unpaid', 'Non rémunéré') : text('Pay not specified', 'Rémunération non précisée');
    const minimum = pay.min ?? pay.amount;
    const min = minimum == null ? null : Math.round(Number(minimum));
    const max = pay.max == null ? null : Math.round(Number(pay.max));
    const amount = min != null && max != null && min !== max ? `${min}–${max}` : min ?? max;
    const periods = fr.value ? { hour: 'heure', day: 'jour', week: 'semaine', month: 'mois', year: 'an' } : { hour: 'hour', day: 'day', week: 'week', month: 'month', year: 'year' };
    return `${amount ?? ''} ${pay.currency || text('(currency not specified)', '(devise non précisée)')}${pay.unit ? ` / ${periods[pay.unit] || pay.unit}` : ''}`;
});
const displayDate = computed(() => {
    const date = props.opportunity.posted_at || props.opportunity.first_seen_at;
    return date ? new Intl.DateTimeFormat(fr.value ? 'fr-FR' : 'en-GB', { dateStyle: 'medium', timeZone: 'Africa/Tunis' }).format(new Date(date)) : '';
});
const seniorityLabel = computed(() => ({ entry: text('Entry level', 'Débutant'), graduate: text('Graduate', 'Jeune diplômé'), mid: text('Mid level', 'Confirmé'), senior: 'Senior', lead: text('Lead', 'Responsable'), executive: text('Executive', 'Direction') }[props.opportunity.seniority] || props.opportunity.seniority));
const workTypes = computed(() => fr.value ? { remote: 'À distance', hybrid: 'Hybride', onsite: 'Sur site', unknown: 'Mode de travail non précisé' } : { remote: 'Remote', hybrid: 'Hybrid', onsite: 'Onsite', unknown: 'Work mode not specified' });
const location = computed(() => props.opportunity.location_label || props.opportunity.city?.name || '');
const paid = computed(() => !!props.opportunity.pay);
const toggleSave = () => toggleSaved(props.opportunity);
</script>
<template>
    <article class="bg-white border border-line rounded-[18px] p-5 sm:p-6 flex flex-col min-w-0 transition-colors hover:border-blue-300">
        <div class="flex gap-3 items-center">
            <CompanyLogo class="w-11 h-11 rounded-xl text-base shrink-0" :name="opportunity.company?.name || 'Company'" :src="opportunity.company?.image" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink-700 truncate" :title="opportunity.company?.name">{{ opportunity.company?.name }}</p>
                <p v-if="location" class="mt-0.5 flex items-center gap-1 text-xs text-slate-500 min-w-0" :title="location"><MapPin :size="13" class="shrink-0" aria-hidden="true" /><span class="truncate">{{ location }}</span></p>
            </div>
            <button v-if="candidate || !user" type="button" :aria-label="saved ? text('Remove from saved', 'Retirer des favoris') : text('Save opportunity', 'Sauvegarder l’offre')" :aria-pressed="saved" class="focus-ring w-10 h-10 shrink-0 rounded-xl border grid place-items-center" :class="saved ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-line text-slate-500'" @click="toggleSave"><Bookmark :size="18" :fill="saved ? 'currentColor' : 'none'" /></button>
        </div>
        <h3 class="mt-4 font-display text-lg font-semibold leading-snug line-clamp-2 break-words" :title="opportunity.title"><Link :href="publicUrl" class="hover:text-blue-700">{{ opportunity.title }}</Link></h3>
        <div class="mt-3 flex flex-wrap gap-1.5 text-xs font-semibold">
            <span class="rounded-md bg-blue-50 text-blue-700 px-2.5 py-1">{{ opportunity.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</span>
            <span v-if="opportunity.work_type" class="rounded-md px-2.5 py-1" :class="opportunity.work_type === 'unknown' ? 'bg-paper-2 text-slate-500 font-medium' : 'bg-paper-2 text-ink-700'">{{ workTypes[opportunity.work_type] || opportunity.work_type }}</span>
            <span v-if="opportunity.applicant_scope === 'anywhere'" class="inline-flex items-center gap-1 rounded-md bg-green-50 text-green-700 px-2.5 py-1"><Globe :size="13" aria-hidden="true" />{{ text('Open worldwide', 'Ouvert au monde entier') }}</span>
            <span v-if="opportunity.seniority" class="rounded-md bg-paper-2 text-ink-700 px-2.5 py-1">{{ seniorityLabel }}</span>
            <span v-if="opportunity.duration_weeks && opportunity.kind !== 'job'" class="rounded-md bg-paper-2 text-ink-700 px-2.5 py-1">{{ opportunity.duration_weeks }} {{ text('weeks', 'semaines') }}</span>
            <span v-if="!opportunity.is_open" class="rounded-md bg-slate-100 text-slate-600 px-2.5 py-1">{{ text('Closed', 'Clôturée') }}</span>
            <span v-if="opportunity.application" class="rounded-md bg-green-50 text-green-700 px-2.5 py-1">{{ text('Applied', 'Candidature enregistrée') }}</span>
            <span v-else-if="openedAt(opportunity.id)" class="rounded-md bg-blue-50 text-blue-700 px-2.5 py-1">{{ text('Viewed', 'Consultée') }}</span>
        </div>
        <ul class="mt-4 space-y-1.5 text-sm">
            <li class="flex items-start gap-2" :class="paid ? 'font-semibold text-ink-700' : 'text-slate-500'"><Banknote :size="16" class="mt-0.5 shrink-0" :class="paid ? 'text-blue-700' : ''" aria-hidden="true" /><span class="min-w-0">{{ payLabel }}</span></li>
        </ul>
        <div class="mt-auto pt-5"><div class="flex gap-3 justify-between items-end border-t border-line pt-4"><p class="min-w-0 text-xs text-slate-500"><span class="block truncate">{{ opportunity.posted_at ? text('Posted', 'Publié') : text('Added to Nextward', 'Ajouté sur Nextward') }} {{ displayDate }}</span><span class="block mt-1 truncate">{{ opportunity.origin === 'native' ? text('Employer post on Nextward', 'Offre employeur sur Nextward') : opportunity.source_name || text('Imported opportunity', 'Offre importée') }}</span></p><Link :href="publicUrl" class="shrink-0 inline-flex items-center gap-1 text-sm font-semibold text-blue-700">{{ text('View', 'Voir') }}<span class="sr-only"> {{ opportunity.title }}</span><ArrowUpRight :size="16" aria-hidden="true" /></Link></div></div>
    </article>
</template>
