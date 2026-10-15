<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bookmark, ArrowUpRight } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import { openedAt, isSaved, toggleSaved } from '@/composables/useOpportunityActivity';
const props = defineProps({ internship: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const user = computed(() => page.props.auth?.user);
const candidate = computed(() => user.value?.is_candidate || user.value?.userable_type === 'student');
const saved = computed(() => user.value ? !!props.internship.liked : isSaved(props.internship.id));
const publicUrl = computed(() => props.internship.public_url || `/opportunities/${props.internship.id}`);
const text = (en, french) => fr.value ? french : en;
const payLabel = computed(() => {
    const pay = props.internship.pay;
    if (!pay) return props.internship.compensation_status === 'unpaid' ? text('Unpaid', 'Non rémunéré') : text('Pay not specified', 'Rémunération non précisée');
    const minimum = pay.min ?? pay.amount;
    const min = minimum == null ? null : Math.round(Number(minimum));
    const max = pay.max == null ? null : Math.round(Number(pay.max));
    const amount = min != null && max != null && min !== max ? `${min}–${max}` : min ?? max;
    const periods = fr.value ? { hour: 'heure', day: 'jour', week: 'semaine', month: 'mois', year: 'an' } : { hour: 'hour', day: 'day', week: 'week', month: 'month', year: 'year' };
    return `${amount ?? ''} ${pay.currency || text('(currency not specified)', '(devise non précisée)')}${pay.unit ? ` / ${periods[pay.unit] || pay.unit}` : ''}`;
});
const displayDate = computed(() => {
    const date = props.internship.posted_at || props.internship.first_seen_at;
    return date ? new Intl.DateTimeFormat(fr.value ? 'fr-FR' : 'en-GB', { dateStyle: 'medium', timeZone: 'Africa/Tunis' }).format(new Date(date)) : '';
});
const seniorityLabel = computed(() => ({ entry: text('Entry level', 'Débutant'), graduate: text('Graduate', 'Jeune diplômé'), mid: text('Mid level', 'Confirmé'), senior: 'Senior', lead: text('Lead', 'Responsable'), executive: text('Executive', 'Direction') }[props.internship.seniority] || props.internship.seniority));
const workTypes = computed(() => fr.value ? { remote: 'À distance', hybrid: 'Hybride', onsite: 'Sur site', unknown: 'Non précisé' } : { remote: 'Remote', hybrid: 'Hybrid', onsite: 'Onsite', unknown: 'Not specified' });
const toggleSave = () => toggleSaved(props.internship);
</script>
<template>
    <article class="bg-white border border-line rounded-[18px] p-5 sm:p-6 flex flex-col transition-colors hover:border-blue-300">
        <div class="flex gap-4 items-start">
            <CompanyLogo class="w-12 h-12 rounded-xl text-lg shrink-0" :name="internship.company?.name || 'Company'" />
            <div class="min-w-0 flex-1"><p class="text-xs uppercase tracking-wider text-blue-700 font-bold mb-2">{{ internship.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</p><h3 class="font-display text-xl font-semibold leading-snug"><Link :href="publicUrl" class="hover:text-blue-700">{{ internship.title }}</Link></h3><p class="text-sm text-slate-600 mt-2">{{ internship.company?.name }}<span v-if="internship.location_label || internship.city?.name"> · {{ internship.location_label || internship.city?.name }}</span></p></div>
            <button v-if="candidate || !user" type="button" :aria-label="saved ? text('Remove from saved', 'Retirer des favoris') : text('Save opportunity', 'Sauvegarder l’offre')" :aria-pressed="saved" class="focus-ring w-10 h-10 shrink-0 rounded-xl border grid place-items-center" :class="saved ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-line text-slate-500'" @click="toggleSave"><Bookmark :size="18" :fill="saved ? 'currentColor' : 'none'" /></button>
        </div>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold"><span v-if="internship.work_type" class="rounded-lg bg-paper-2 px-3 py-1.5">{{ workTypes[internship.work_type] || internship.work_type }}</span><span v-if="internship.duration_weeks && internship.kind !== 'job'" class="rounded-lg bg-paper-2 px-3 py-1.5">{{ internship.duration_weeks }} {{ text('weeks', 'semaines') }}</span><span v-if="internship.seniority" class="rounded-lg bg-paper-2 px-3 py-1.5">{{ seniorityLabel }}</span><span v-if="!internship.is_open" class="rounded-lg bg-slate-100 px-3 py-1.5">{{ text('Closed', 'Clôturée') }}</span><span v-if="internship.application" class="rounded-lg bg-green-50 text-green-700 px-3 py-1.5">{{ text('Applied', 'Candidature enregistrée') }}</span><span v-else-if="openedAt(internship.id)" class="rounded-lg bg-blue-50 text-blue-700 px-3 py-1.5">{{ text('Viewed', 'Consultée') }}</span></div>
        <p class="mt-4 text-sm font-semibold text-ink-700">{{ payLabel }}</p>
        <p v-if="internship.work_type === 'remote'" class="mt-2 text-xs text-slate-500">{{ internship.remote_eligibility === 'eligible' ? text('Explicitly open to work from Tunisia', 'Travail depuis la Tunisie explicitement autorisé') : internship.remote_eligibility === 'restricted' ? text('Location restrictions apply', 'Restrictions géographiques applicables') : text('Remote eligibility not confirmed', 'Éligibilité au télétravail non confirmée') }}</p>
        <div class="mt-auto pt-5"><div class="flex flex-wrap gap-3 justify-between items-center border-t border-line pt-4"><p class="text-xs text-slate-500"><span v-if="internship.posted_at">{{ text('Posted', 'Publié') }} {{ displayDate }}</span><span v-else>{{ text('Added to Nextward', 'Ajouté sur Nextward') }} {{ displayDate }}</span><span class="block mt-1">{{ internship.origin === 'native' ? text('Employer post on Nextward', 'Offre employeur sur Nextward') : internship.source_name || text('Imported opportunity', 'Offre importée') }}</span></p><Link :href="publicUrl" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700">{{ text('View opportunity', 'Voir l’offre') }}<ArrowUpRight :size="16" /></Link></div></div>
    </article>
</template>
