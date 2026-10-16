<script>
import opportunityLayout from '@/Layouts/opportunityLayout';
export default { layout: opportunityLayout };
</script>
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, MapPin } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import RoleCard from '@/Components/Internship/RoleCard.vue';
const props = defineProps({ company: { type: Object, required: true }, opportunities: { type: [Object, Array], default: () => ({ data: [] }) }, translation: { type: String, default: null } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const roles = computed(() => Array.isArray(props.opportunities) ? props.opportunities : props.opportunities?.data || []);
const logo = computed(() => props.company.logo && /^https?:\/\//.test(props.company.logo) ? props.company.logo : null);
</script>

<template>
    <div class="public-container max-w-5xl! py-12">
        <nav class="text-sm text-slate-600" :aria-label="text('Breadcrumb', 'Fil d’Ariane')"><Link :href="`${prefix}/opportunities`" class="hover:text-brand">{{ text('Opportunities', 'Opportunités') }}</Link> <span aria-hidden="true">/</span> <span class="text-navy">{{ company.name }}</span></nav>
        <header class="mt-6 bg-white border border-line rounded-2xl p-6 sm:p-8">
            <div class="flex items-start gap-5">
                <img v-if="logo" :src="logo" :alt="company.name" width="64" height="64" class="h-16 w-16 shrink-0 rounded-2xl border border-line object-contain bg-white" />
                <CompanyLogo v-else class="h-16 w-16 shrink-0 rounded-2xl text-2xl" :name="company.name" />
                <div class="min-w-0">
                    <h1 class="font-display text-3xl sm:text-4xl font-semibold leading-tight">{{ company.name }}</h1>
                    <p class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-slate-600">
                        <span v-if="company.location" class="inline-flex items-center gap-1.5"><MapPin :size="16" />{{ company.location }}</span>
                        <a v-if="company.website" :href="company.website" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-blue-700">{{ text('Employer website', 'Site de l’employeur') }}<ArrowUpRight :size="16" /></a>
                    </p>
                </div>
            </div>
            <p v-if="company.about" class="mt-6 whitespace-pre-line text-slate-700 leading-8" dir="auto">{{ company.about }}</p>
            <p v-if="!company.managed" class="mt-6 text-xs text-slate-500 leading-6">{{ text('This employer does not have a managed account on Nextward. Roles come from approved public sources and link to the original listing.', 'Cet employeur n’a pas de compte géré sur Nextward. Les offres viennent de sources publiques approuvées et renvoient vers l’annonce d’origine.') }}</p>
        </header>

        <section class="mt-12" aria-labelledby="roles-title">
            <h2 id="roles-title" class="font-display text-2xl font-semibold">{{ text('Open internships and jobs at', 'Stages et emplois ouverts chez') }} {{ company.name }}</h2>
            <div v-if="roles.length" class="mt-6 grid lg:grid-cols-2 gap-4"><RoleCard v-for="opportunity in roles" :key="opportunity.id" :internship="opportunity" /></div>
            <div v-else class="mt-6 bg-white rounded-2xl border border-line p-8">
                <p class="text-slate-600">{{ text(`There are no open roles at ${company.name} right now.`, `Aucune offre ouverte chez ${company.name} pour le moment.`) }}</p>
                <Link :href="`${prefix}/opportunities`" class="inline-block mt-4 text-blue-700 font-semibold">{{ text('Browse all opportunities', 'Voir toutes les opportunités') }}</Link>
            </div>
        </section>
    </div>
</template>
