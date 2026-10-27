<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2, Search } from '@lucide/vue';
import PublicLayout from '@/Layouts/Public.vue';
import CompanyCard from '@/Components/Company/CompanyCard.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import ClosingBanner from '@/Components/Landing/ClosingBanner.vue';

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
const isCompany = computed(() => page.props.auth?.user?.userable_type === 'company');
</script>

<template>
    <div class="landing bg-pale">
        <LandingHero title-id="companies-title" overlap :label="text('Companies', 'Entreprises')" :lead="text('Find an employer, read what it does and see every internship and job it has open on Nextward.', 'Trouvez un employeur, découvrez ce qu’il fait et voyez tous les stages et emplois qu’il propose sur Nextward.')">
            {{ text('Companies hiring', 'Les entreprises qui recrutent') }} <span class="accent">{{ text('in IT', 'en informatique') }}</span>
        </LandingHero>

        <div class="public-container hero-overlap pb-20">
            <div class="rounded-card border border-line bg-white p-6 max-[768px]:p-4">
                <form class="flex gap-3 max-[480px]:flex-col" role="search" :action="`${prefix}/companies`" method="get" @submit.prevent="visit({})">
                    <label class="field flex-1 min-w-0">
                        <span class="sr-only">{{ text('Search companies', 'Rechercher une entreprise') }}</span>
                        <Search aria-hidden="true" />
                        <input v-model="search" name="search" type="search" maxlength="100" :placeholder="text('Company name or city', 'Nom de l’entreprise ou ville')" />
                    </label>
                    <button type="submit" class="cta cta-primary focus-ring h-[52px]">{{ text('Search', 'Rechercher') }}</button>
                </form>
                <div class="mt-4 flex gap-2 py-1 -mx-4 px-4 overflow-x-auto [scrollbar-width:none] sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" role="group" :aria-label="text('Filter companies', 'Filtrer les entreprises')">
                    <button type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': filters.show !== 'all' && !filters.field }" :aria-pressed="filters.show !== 'all' && !filters.field" @click="visit({ show: 'hiring', field: undefined })">{{ text('Hiring now', 'Recrutent en ce moment') }}</button>
                    <button v-for="field in fields" :key="field.id" type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': filters.field === field.id }" :aria-pressed="filters.field === field.id" @click="visit({ field: field.id, show: undefined })">{{ field.name }}</button>
                    <button type="button" class="chip focus-ring shrink-0 whitespace-nowrap" :class="{ 'is-active': filters.show === 'all' }" :aria-pressed="filters.show === 'all'" @click="visit({ show: 'all', field: undefined })">{{ text('All companies', 'Toutes les entreprises') }}</button>
                </div>
            </div>

            <section class="mt-10" :aria-label="text('Companies', 'Entreprises')">
                <p class="mb-5 text-[15px] text-subtle" aria-live="polite"><strong class="font-medium text-navy">{{ total }}</strong> {{ total === 1 ? text('company', 'entreprise') : text('companies', 'entreprises') }}</p>
                <div v-if="companies.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <CompanyCard v-for="company in companies.data" :key="company.id" :company="company" />
                </div>
                <div v-else class="panel flex flex-col items-center px-5 py-16 text-center">
                    <span class="icon-tile" aria-hidden="true"><Building2 class="size-6" /></span>
                    <h2 class="mt-5 text-[22px] font-medium text-navy">{{ text('No companies found', 'Aucune entreprise trouvée') }}</h2>
                    <p class="mt-2 text-subtle">{{ filtered ? text('Try another name or city, or clear the filters.', 'Essayez un autre nom ou une autre ville, ou effacez les filtres.') : text('Companies show up here when they have open roles.', 'Les entreprises apparaissent ici quand elles ont des offres ouvertes.') }}</p>
                    <Link v-if="filtered" :href="`${prefix}/companies`" class="cta cta-secondary focus-ring mt-6">{{ text('Clear filters', 'Effacer les filtres') }}</Link>
                </div>
                <Pagination :meta="companies" />
            </section>
        </div>

        <ClosingBanner :title="text('Hiring in IT?', 'Vous recrutez en informatique ?')" :body="text('Post internships and jobs, introduce your company and review applications in one place.', 'Publiez des stages et des emplois, présentez votre entreprise et examinez les candidatures au même endroit.')">
            <Link :href="isCompany ? route('opportunities.create') : `${prefix}/register?account=company`" class="cta cta-light focus-ring">{{ isCompany ? text('Post a role', 'Publier une offre') : text('Create a company account', 'Créer un compte entreprise') }}<ArrowRight class="action-icon" aria-hidden="true" /></Link>
        </ClosingBanner>
    </div>
</template>
