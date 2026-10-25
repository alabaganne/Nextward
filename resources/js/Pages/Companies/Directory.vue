<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building2, Search } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import CompanyCard from '@/Components/Company/CompanyCard.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({ layout: PublicLayout });
const props = defineProps({ companies: Object, fields: { type: Array, default: () => [] }, filters: { type: Object, default: () => ({}) } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;

const search = ref(props.filters.search || '');
watch(() => props.filters.search, (value) => { search.value = value || ''; });
const visit = (changes) => {
    const query = { search: search.value.trim(), field: props.filters.field, show: props.filters.show, ...changes };
    router.get(`${prefix.value}/companies`, Object.fromEntries(Object.entries(query).filter(([key, value]) => value && !(key === 'show' && value === 'hiring'))), { preserveScroll: true, preserveState: true });
};
const total = computed(() => props.companies.total);
const filtered = computed(() => !!(props.filters.search || props.filters.field || props.filters.show === 'all'));
</script>

<template>
    <section class="bg-white border-b border-line">
        <div class="public-container pt-10 pb-8">
            <p class="text-sm text-blue-700 font-semibold uppercase tracking-widest">{{ text('Companies', 'Entreprises') }}</p>
            <h1 class="font-display text-4xl font-semibold tracking-tight mt-3">{{ text('Companies hiring in IT', 'Les entreprises qui recrutent en informatique') }}</h1>
            <p class="text-slate-600 leading-7 mt-4 max-w-3xl">{{ text('Find an employer, read what it does and see every internship and job it has open on Nextward.', 'Trouvez un employeur, découvrez ce qu’il fait et voyez tous les stages et emplois qu’il propose sur Nextward.') }}</p>
            <form class="flex gap-3 mt-7 max-w-3xl" role="search" :action="`${prefix}/companies`" method="get" @submit.prevent="visit({})">
                <label class="relative flex-1 min-w-0">
                    <span class="sr-only">{{ text('Search companies', 'Rechercher une entreprise') }}</span>
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none" aria-hidden="true" />
                    <input v-model="search" name="search" type="search" maxlength="100" :placeholder="text('Company name or city', 'Nom de l’entreprise ou ville')" class="w-full h-12 rounded-xl border-line bg-white pl-11" />
                </label>
                <button type="submit" class="cta cta-primary">{{ text('Search', 'Rechercher') }}</button>
            </form>
        </div>
    </section>

    <div class="public-container py-8">
        <div class="flex gap-2 py-1 -mx-5 px-5 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" role="group" :aria-label="text('Filter companies', 'Filtrer les entreprises')">
            <button type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="filters.show !== 'all' && !filters.field ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-line hover:border-blue-300'" :aria-pressed="filters.show !== 'all' && !filters.field" @click="visit({ show: 'hiring', field: undefined })">{{ text('Hiring now', 'Recrutent en ce moment') }}</button>
            <button v-for="field in fields" :key="field.id" type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="filters.field === field.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-line hover:border-blue-300'" :aria-pressed="filters.field === field.id" @click="visit({ field: field.id, show: undefined })">{{ field.name }}</button>
            <button type="button" class="shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold border" :class="filters.show === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-line hover:border-blue-300'" :aria-pressed="filters.show === 'all'" @click="visit({ show: 'all', field: undefined })">{{ text('All companies', 'Toutes les entreprises') }}</button>
        </div>

        <p class="mt-6 text-sm text-slate-600" aria-live="polite">{{ total }} {{ total === 1 ? text('company', 'entreprise') : text('companies', 'entreprises') }}</p>

        <div v-if="companies.data.length" class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <CompanyCard v-for="company in companies.data" :key="company.id" :company="company" />
        </div>
        <div v-else class="mt-4 flex flex-col items-center text-center py-16 px-5 bg-white border border-line rounded-[18px]">
            <Building2 class="size-8 text-slate-400 mb-3" aria-hidden="true" />
            <h2 class="font-display text-lg font-semibold">{{ text('No companies found', 'Aucune entreprise trouvée') }}</h2>
            <p class="text-slate-600 text-sm mt-1.5">{{ filtered ? text('Try another name or city, or clear the filters.', 'Essayez un autre nom ou une autre ville, ou effacez les filtres.') : text('Companies show up here when they have open roles.', 'Les entreprises apparaissent ici quand elles ont des offres ouvertes.') }}</p>
            <Link v-if="filtered" :href="`${prefix}/companies`" class="mt-4 text-sm font-semibold text-blue-700 hover:underline">{{ text('Clear filters', 'Effacer les filtres') }}</Link>
        </div>

        <Pagination :meta="companies" />

        <p class="mt-10 text-sm text-slate-600">{{ text('Hiring?', 'Vous recrutez ?') }} <Link :href="`${prefix}/register?account=company`" class="font-semibold text-blue-700 hover:underline">{{ text('Create a company account and post a role', 'Créez un compte entreprise et publiez une offre') }}</Link></p>
    </div>
</template>
