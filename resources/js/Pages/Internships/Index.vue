<script>
import opportunityLayout from '@/Layouts/opportunityLayout';
export default { layout: opportunityLayout };
</script>
<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Search, SlidersHorizontal, X } from '@lucide/vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
const props = defineProps({ internships: Object, fields: Array, cities: Array, companies: Array, countries: Array, filters: { type: Object, default: () => ({}) }, category: String, categoryLabel: String });
const page = usePage();
const guest = computed(() => !page.props.auth?.user);
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const categories = computed(() => [{ key: '', label: text('All opportunities', 'Toutes les opportunités') }, { key: 'internships', label: text('Internships', 'Stages') }, { key: 'jobs', label: text('Jobs', 'Emplois') }, { key: 'tunisia', label: text('In Tunisia', 'En Tunisie') }, { key: 'remote-from-tunisia', label: text('Remote from Tunisia', 'À distance depuis la Tunisie') }, { key: 'relocation', label: text('Relocation', 'Mobilité internationale') }]);
const heading = computed(() => props.category ? categories.value.find((item) => item.key === props.category)?.label : text('Browse opportunities', 'Explorer les opportunités'));
const action = computed(() => `${prefix.value}/opportunities${props.category ? `/${props.category}` : ''}`);
const savedSearchUrl = computed(() => '/saved-searches?' + new URLSearchParams(Object.entries(props.filters).flatMap(([key, value]) => Array.isArray(value) ? value.map((item) => [`${key}[]`, item]) : [[key, value]])).toString() + (props.category ? `&category=${props.category}` : ''));

const listKeys = ['fields', 'cities', 'companies'];
const formKeys = ['search', 'kind', 'country', 'work_type', 'seniority', 'posted_within', 'employment_type', 'fields', 'cities', 'companies', 'compensation_status', 'program_type', 'include_unknown_remote'];
const first = (value) => String((Array.isArray(value) ? value[0] : value) ?? '');
const isOn = (value) => [true, 1, '1', 'true'].includes(value);
function toForm(filters) {
    const values = Object.fromEntries(formKeys.map((key) => [key, first(filters[key])]));
    values.fields ||= first(filters.field);
    values.include_unknown_remote = isOn(filters.include_unknown_remote);
    return values;
}
const form = reactive(toForm(props.filters));
watch(() => props.filters, (filters) => Object.assign(form, toForm(filters)));
// Filters without a control here (older links and saved searches) keep applying and show as chips.
const extra = computed(() => Object.fromEntries(Object.entries(props.filters).filter(([key]) => ![...formKeys, 'field', 'page'].includes(key))));
const moreOpen = ref(false);

const option = (value, en, french = en) => ({ value, label: text(en, french) });
const counted = (items, key) => (items || []).filter((item) => item.internships_count || String(item.id) === form[key]).map((item) => ({ value: String(item.id), label: item.name, count: item.internships_count }));
const options = computed(() => ({
    kind: [option('internship', 'Internships', 'Stages'), option('job', 'Jobs', 'Emplois')],
    country: (props.countries || []).map((country) => ({ value: country.code, label: country.name })),
    work_type: [option('onsite', 'Onsite', 'Sur site'), option('hybrid', 'Hybrid', 'Hybride'), option('remote', 'Remote', 'À distance')],
    seniority: [option('entry', 'Entry level', 'Débutant'), option('graduate', 'Graduate', 'Jeune diplômé'), option('mid', 'Mid level', 'Confirmé'), option('senior', 'Senior'), option('lead', 'Lead', 'Responsable'), option('executive', 'Executive', 'Direction')],
    posted_within: [option('1', 'Last 24 hours', 'Dernières 24 heures'), option('7', 'Last 7 days', '7 derniers jours'), option('30', 'Last 30 days', '30 derniers jours')],
    employment_type: [option('FULL_TIME', 'Full time', 'Temps plein'), option('PART_TIME', 'Part time', 'Temps partiel'), option('CONTRACTOR', 'Contract / freelance', 'Contrat / freelance'), option('TEMPORARY', 'Temporary', 'Temporaire')],
    fields: counted(props.fields, 'fields'),
    cities: counted(props.cities, 'cities'),
    companies: counted(props.companies, 'companies'),
    compensation_status: [option('paid', 'Paid', 'Rémunéré'), option('unpaid', 'Unpaid', 'Non rémunéré'), option('unspecified', 'Pay not specified', 'Rémunération non précisée')],
    program_type: [option('pfe', 'PFE'), option('summer', 'Summer internship', 'Stage d’été'), option('apprenticeship', 'Apprenticeship', 'Alternance')],
}));
const quickFilters = computed(() => [
    { key: 'kind', label: text('Type', 'Type'), any: text('All types', 'Tous les types'), show: !['internships', 'jobs'].includes(props.category) },
    { key: 'country', label: text('Country', 'Pays'), any: text('Worldwide', 'Monde entier'), show: props.category !== 'tunisia' },
    { key: 'work_type', label: text('Work mode', 'Mode de travail'), any: text('Any work mode', 'Tous les modes'), show: props.category !== 'remote-from-tunisia' },
    { key: 'seniority', label: text('Experience', 'Expérience'), any: text('Any experience', 'Tous les niveaux') },
    { key: 'posted_within', label: text('Posted', 'Publication'), any: text('Any date', 'Toutes les dates') },
].filter((item) => item.show !== false && options.value[item.key].length));
const moreFilters = computed(() => [
    { key: 'employment_type', label: text('Contract', 'Contrat'), any: text('Any contract', 'Tous les contrats') },
    { key: 'fields', label: text('Field', 'Domaine'), any: text('Any field', 'Tous les domaines') },
    { key: 'cities', label: text('City in Tunisia', 'Ville en Tunisie'), any: text('Any city', 'Toutes les villes') },
    { key: 'companies', label: text('Employer', 'Employeur'), any: text('Any employer', 'Tous les employeurs') },
    { key: 'compensation_status', label: text('Pay', 'Rémunération'), any: text('Any pay', 'Toutes') },
    { key: 'program_type', label: text('Internship programme', 'Programme de stage'), any: text('Any programme', 'Tous les programmes'), show: props.category !== 'jobs' && form.kind !== 'job' },
].filter((item) => item.show !== false && options.value[item.key].length));
const moreCount = computed(() => moreFilters.value.filter((item) => form[item.key]).length);

const units = computed(() => ({ hour: text('hour', 'heure'), day: text('day', 'jour'), week: text('week', 'semaine'), month: text('month', 'mois'), year: text('year', 'an') }));
const languages = { en: 'English', fr: 'Français', ar: 'العربية' };
const date = (value) => new Date(value).toLocaleDateString(fr.value ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
const optionLabel = (key, value) => options.value[key].find((item) => item.value === value)?.label
    ?? (key === 'posted_within' ? text(`Last ${value} days`, `${value} derniers jours`) : value);
const chips = computed(() => {
    const applied = toForm(props.filters);
    const f = props.filters;
    // Filters already visible as controls don't need a chip.
    const shown = [...quickFilters.value, ...(moreOpen.value ? moreFilters.value : [])].map((item) => item.key);
    const list = Object.keys(options.value).filter((key) => applied[key] && !shown.includes(key)).map((key) => ({ keys: key === 'fields' ? ['fields', 'field'] : [key], label: optionLabel(key, applied[key]) }));
    if (f.min_pay != null) list.push({ keys: ['min_pay', 'currency', 'pay_unit'], label: `${text('Pay from', 'À partir de')} ${f.min_pay} ${f.currency || ''}${f.pay_unit ? ` / ${units.value[f.pay_unit] || f.pay_unit}` : ''}` });
    if (f.language) list.push({ keys: ['language'], label: `${text('Listing in', 'Offre en')} ${languages[f.language] || f.language}` });
    if (f.duration_max) list.push({ keys: ['duration_max'], label: text(`Up to ${f.duration_max} weeks`, `${f.duration_max} semaines max.`) });
    if (f.start_after) list.push({ keys: ['start_after'], label: `${text('Starts from', 'Début dès le')} ${date(f.start_after)}` });
    if (['students', 'professionals'].includes(f.audience)) list.push({ keys: ['audience'], label: f.audience === 'students' ? text('For students', 'Pour les étudiants') : text('For professionals', 'Pour les professionnels') });
    if (isOn(f.remote_from_tunisia)) list.push({ keys: ['remote_from_tunisia'], label: text('Remote from Tunisia', 'À distance depuis la Tunisie') });
    if (isOn(f.relocation)) list.push({ keys: ['relocation'], label: text('With relocation', 'Avec mobilité') });
    return list;
});
const hasFilters = computed(() => Object.keys(props.filters).some((key) => key !== 'page'));
const total = computed(() => props.internships.meta?.total || 0);

function apply(changes = {}) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries({ ...extra.value, ...form, ...changes })) {
        const clean = value === true ? '1' : value === false ? '' : String(value ?? '').trim();
        if (clean) query.append(listKeys.includes(key) ? `${key}[]` : key, clean);
    }
    router.get(`${action.value}${query.size ? `?${query}` : ''}`, {}, { preserveScroll: true, preserveState: true });
}
const remove = (chip) => apply(Object.fromEntries(chip.keys.map((key) => [key, ''])));
</script>
<template>
    <section :class="guest ? 'bg-white border-b border-line' : ''"><div class="public-container pt-10 pb-7"><p v-if="guest" class="text-sm text-blue-700 font-semibold uppercase tracking-widest">{{ text('Students and professionals', 'Étudiants et professionnels') }}</p><h1 class="font-display text-4xl font-semibold tracking-tight mt-3">{{ heading }}</h1><p v-if="guest" class="text-slate-600 leading-7 mt-4 max-w-3xl">{{ page.props.seo?.description }}</p><nav class="flex gap-2 mt-6 py-1 -mx-5 px-5 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" :aria-label="text('Opportunity categories', 'Catégories d’opportunités')"><Link v-for="item in categories" :key="item.key" :href="`${prefix}/opportunities${item.key ? `/${item.key}` : ''}`" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="(category || '') === item.key ? 'bg-blue-600 text-white border-blue-600' : 'border-line hover:border-blue-300'">{{ item.label }}</Link></nav></div></section>
    <div class="public-container py-8">
        <form :action="action" method="get" role="search" @submit.prevent="apply()">
            <div class="flex gap-3">
                <label class="relative flex-1 min-w-0"><span class="sr-only">{{ text('Search title, employer, or description', 'Rechercher par titre, entreprise ou description') }}</span><Search class="absolute left-4 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none" aria-hidden="true" /><input v-model="form.search" name="search" type="search" maxlength="150" :placeholder="text('Role, company, or keyword', 'Poste, entreprise ou mot-clé')" class="w-full h-12 rounded-xl border-line bg-white pl-11" /></label>
                <button type="submit" class="cta cta-primary">{{ text('Search', 'Rechercher') }}</button>
            </div>
            <div class="relative flex items-center gap-2 mt-2 py-1 -mx-5 px-5 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible">
                <label v-for="item in quickFilters" :key="item.key" class="shrink-0"><span class="sr-only">{{ item.label }}</span><select v-model="form[item.key]" :name="listKeys.includes(item.key) ? `${item.key}[]` : item.key" class="h-10 max-w-full rounded-full pl-4 pr-9 text-sm font-semibold" :class="form[item.key] ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-line bg-white text-navy hover:border-blue-300'" @change="apply()"><option value="">{{ item.any }}</option><option v-for="choice in options[item.key]" :key="choice.value" :value="choice.value">{{ choice.label }}</option></select></label>
                <label v-if="category === 'remote-from-tunisia'" class="shrink-0 whitespace-nowrap h-10 inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 text-sm font-semibold"><input v-model="form.include_unknown_remote" name="include_unknown_remote" type="checkbox" value="1" class="rounded" @change="apply()" />{{ text('Include unconfirmed eligibility', 'Inclure l’éligibilité non confirmée') }}</label>
                <button v-if="moreFilters.length" type="button" class="shrink-0 whitespace-nowrap h-10 inline-flex items-center gap-2 rounded-full border px-4 text-sm font-semibold" :class="moreOpen || moreCount ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-line bg-white text-navy hover:border-blue-300'" :aria-expanded="moreOpen" aria-controls="more-filters" @click="moreOpen = !moreOpen"><SlidersHorizontal class="size-4" aria-hidden="true" />{{ text('More filters', 'Plus de filtres') }}<span v-if="moreCount" class="grid place-items-center min-w-5 h-5 px-1 rounded-full bg-blue-600 text-white text-xs">{{ moreCount }}</span></button>
                <Link v-if="hasFilters" :href="action" preserve-scroll class="shrink-0 whitespace-nowrap px-2 text-sm font-semibold text-blue-700 hover:underline">{{ text('Clear all', 'Tout effacer') }}</Link>
            </div>
            <div v-show="moreOpen" id="more-filters" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 rounded-2xl border border-line bg-white p-5">
                <label v-for="item in moreFilters" :key="item.key" class="block text-sm font-semibold">{{ item.label }}<select v-model="form[item.key]" :name="listKeys.includes(item.key) ? `${item.key}[]` : item.key" class="w-full rounded-lg border-line mt-2 font-normal" @change="apply()"><option value="">{{ item.any }}</option><option v-for="choice in options[item.key]" :key="choice.value" :value="choice.value">{{ choice.label }}<template v-if="choice.count != null"> ({{ choice.count }})</template></option></select></label>
            </div>
        </form>
        <div v-if="chips.length" class="flex flex-wrap items-center gap-2 mt-4">
            <button v-for="chip in chips" :key="chip.keys[0]" type="button" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 pl-3 pr-2 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200" :aria-label="text(`Remove filter: ${chip.label}`, `Retirer le filtre : ${chip.label}`)" @click="remove(chip)">{{ chip.label }}<X class="size-3.5" aria-hidden="true" /></button>
        </div>
        <section id="results" class="@container mt-6 scroll-mt-24" :aria-label="text('Search results', 'Résultats de recherche')">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-slate-600"><p><strong class="text-navy">{{ total }}</strong> {{ total === 1 ? text('opportunity', 'opportunité') : text('opportunities', 'opportunités') }}</p><Link v-if="guest || page.props.auth.user.is_candidate" :href="guest ? `${prefix}/login` : savedSearchUrl" class="inline-flex items-center gap-1.5 font-semibold text-blue-700 hover:underline"><Bell class="size-4" aria-hidden="true" />{{ text('Save search and get alerts', 'Sauvegarder et recevoir des alertes') }}</Link></div>
            <div v-if="internships.data?.length" class="grid grid-cols-1 @3xl:grid-cols-2 gap-5"><RoleCard v-for="opportunity in internships.data" :key="opportunity.id" :internship="opportunity" /></div>
            <div v-else class="bg-white rounded-2xl border border-line p-8"><h2 class="font-display text-xl font-semibold">{{ text('No opportunities match these filters.', 'Aucune opportunité ne correspond à ces filtres.') }}</h2><p class="text-slate-600 mt-3">{{ text('Try removing a filter or using a broader keyword.', 'Essayez de retirer un filtre ou d’utiliser un mot-clé plus large.') }}</p><Link :href="chips.length || filters.search ? action : `${prefix}/opportunities`" class="inline-block mt-5 text-blue-700 font-semibold">{{ chips.length || filters.search ? text('Clear filters', 'Effacer les filtres') : text('Browse all opportunities', 'Voir toutes les opportunités') }}</Link></div>
            <Pagination :meta="internships.meta" />
        </section>
    </div>
</template>
