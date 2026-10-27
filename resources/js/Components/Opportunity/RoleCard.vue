<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Banknote, Bookmark, Globe, MapPin } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';
import { openedAt, isSaved, toggleSaved } from '@/composables/useOpportunityActivity';
import { appPath, isAppPath, sitePath } from '@/utils/url';
const props = defineProps({ opportunity: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const user = computed(() => page.props.auth?.user);
const candidate = computed(() => user.value?.is_candidate || user.value?.userable_type === 'candidate');
const saved = computed(() => user.value ? !!props.opportunity.liked : isSaved(props.opportunity.id));
const publicUrl = computed(() => {
    const url = sitePath(props.opportunity.public_url) || `/opportunities/${props.opportunity.id}`;
    return isAppPath(page.url) ? appPath(url) : url;
});
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
    const date = props.opportunity.posted_at || props.opportunity.added_at;
    return date ? new Intl.DateTimeFormat(fr.value ? 'fr-FR' : 'en-GB', { dateStyle: 'medium', timeZone: 'Africa/Tunis' }).format(new Date(date)) : '';
});
const seniorityLabel = computed(() => ({ entry: text('Entry level', 'Débutant'), graduate: text('Graduate', 'Jeune diplômé'), mid: text('Mid level', 'Confirmé'), senior: 'Senior', lead: text('Lead', 'Responsable'), executive: text('Executive', 'Direction') }[props.opportunity.seniority] || props.opportunity.seniority));
const workTypes = computed(() => fr.value ? { remote: 'À distance', hybrid: 'Hybride', onsite: 'Sur site', unknown: 'Mode de travail non précisé' } : { remote: 'Remote', hybrid: 'Hybrid', onsite: 'Onsite', unknown: 'Work mode not specified' });
const location = computed(() => props.opportunity.location_label || props.opportunity.city?.name || '');
const paid = computed(() => !!props.opportunity.pay);
const toggleSave = () => toggleSaved(props.opportunity);
</script>
<template>
    <article class="bg-white border border-line rounded-card p-6 flex flex-col min-w-0 transition-colors duration-180 hover:border-brand/40">
        <div class="flex gap-3.5 items-center">
            <CompanyLogo class="w-12 h-12 rounded-card text-base shrink-0" :name="opportunity.company?.name || 'Company'" :src="opportunity.company?.image" />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1 min-w-0">
                    <p class="text-[15px] text-navy truncate" :title="opportunity.company?.name">{{ opportunity.company?.name }}</p>
                    <VerifiedBadge v-if="opportunity.company?.verified" compact />
                </div>
                <p v-if="location" class="mt-0.5 flex items-center gap-1 text-[13px] text-subtle-2 min-w-0" :title="location"><MapPin :size="13" class="shrink-0" aria-hidden="true" /><span class="truncate">{{ location }}</span></p>
            </div>
            <button v-if="candidate || !user" type="button" :aria-label="saved ? text('Remove from saved', 'Retirer des favoris') : text('Save role', 'Sauvegarder l’offre')" :aria-pressed="saved" class="focus-ring w-10 h-10 shrink-0 rounded-card border grid place-items-center transition-colors duration-180" :class="saved ? 'border-brand bg-brand text-white' : 'border-line text-subtle-2 hover:border-brand hover:text-brand'" @click="toggleSave"><Bookmark :size="18" :fill="saved ? 'currentColor' : 'none'" /></button>
        </div>
        <h3 class="mt-5 text-[19px] font-medium leading-[1.4] text-navy line-clamp-2 break-words" :title="opportunity.title"><Link :href="publicUrl" class="transition-colors duration-180 hover:text-brand">{{ opportunity.title }}</Link></h3>
        <div class="mt-3 flex flex-wrap gap-1.5 text-[12.5px]">
            <span class="rounded-md bg-blue-50 text-brand px-2.5 py-1">{{ opportunity.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</span>
            <span v-if="opportunity.work_type" class="rounded-md px-2.5 py-1" :class="opportunity.work_type === 'unknown' ? 'bg-slate-100 text-subtle-2' : 'bg-slate-100 text-navy'">{{ workTypes[opportunity.work_type] || opportunity.work_type }}</span>
            <span v-if="opportunity.applicant_scope === 'anywhere'" class="inline-flex items-center gap-1 rounded-md bg-green-50 text-green-700 px-2.5 py-1"><Globe :size="13" aria-hidden="true" />{{ text('Open worldwide', 'Ouvert au monde entier') }}</span>
            <span v-if="opportunity.seniority" class="rounded-md bg-slate-100 text-navy px-2.5 py-1">{{ seniorityLabel }}</span>
            <span v-if="opportunity.duration_weeks && opportunity.kind !== 'job'" class="rounded-md bg-slate-100 text-navy px-2.5 py-1">{{ opportunity.duration_weeks }} {{ text('weeks', 'semaines') }}</span>
            <span v-if="!opportunity.is_open" class="rounded-md bg-slate-100 text-slate-600 px-2.5 py-1">{{ text('Closed', 'Clôturée') }}</span>
            <span v-if="opportunity.application" class="rounded-md bg-green-50 text-green-700 px-2.5 py-1">{{ text('Applied', 'Candidature enregistrée') }}</span>
            <span v-else-if="openedAt(opportunity.id)" class="rounded-md bg-blue-50 text-brand px-2.5 py-1">{{ text('Viewed', 'Consultée') }}</span>
        </div>
        <p class="mt-4 flex items-start gap-2 text-[14.5px]" :class="paid ? 'text-navy' : 'text-subtle-2'"><Banknote :size="17" class="mt-0.5 shrink-0" :class="paid ? 'text-brand' : ''" aria-hidden="true" /><span class="min-w-0">{{ payLabel }}</span></p>
        <div class="mt-auto pt-5"><div class="flex gap-3 justify-between items-end border-t border-line pt-4"><p class="min-w-0 text-[13px] text-subtle-2"><span class="block truncate">{{ opportunity.posted_at ? text('Posted', 'Publié') : text('Added to Nextward', 'Ajouté sur Nextward') }} {{ displayDate }}</span><span class="block mt-1 truncate">{{ opportunity.origin === 'native' ? text('Employer post on Nextward', 'Offre employeur sur Nextward') : opportunity.source_name || text('Imported role', 'Offre importée') }}</span></p><Link :href="publicUrl" class="focus-ring shrink-0 inline-flex items-center gap-1 rounded-[2px] text-[13px] font-medium tracking-caps uppercase text-brand transition-colors duration-180 hover:text-navy">{{ text('View role', 'Voir l’offre') }}<span class="sr-only"> {{ opportunity.title }}</span><ArrowUpRight :size="16" aria-hidden="true" /></Link></div></div>
    </article>
</template>
