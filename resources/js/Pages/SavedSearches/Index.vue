<script setup>
import { h, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'saved-searches' }, () => page) });
const props = defineProps({ searches: Array, initialFilters: Object, initialBrowseUrl: String, deliveryEnabled: Boolean, timezones: Array });
const form = useForm({ name: '', filters: props.initialFilters || {}, frequency: 'off', timezone: 'Africa/Tunis', consent: false });
const editing = ref(null);
const formatFilter = (filters) => Object.entries(filters || {}).map(([key, value]) => {
    const label = key === 'min_pay' && value != null && value !== '' ? Math.round(Number(value)) : value;
    return `${key.replaceAll('_', ' ')}: ${Array.isArray(label) ? label.join(', ') : label}`;
}).join(' · ') || 'All active opportunities';
const save = () => {
    const options = { preserveScroll: true, onSuccess: () => { editing.value = null; form.reset(); } };
    editing.value ? form.put(route('saved-searches.update', editing.value), options) : form.post(route('saved-searches.store'), options);
};
const edit = (search) => {
    editing.value = search.id;
    form.name = search.name;
    form.filters = search.filters;
    form.frequency = search.frequency;
    form.timezone = search.timezone;
    form.consent = search.frequency !== 'off';
};
const pause = (search) => router.put(route('saved-searches.update', search.id), { ...search, frequency: 'off', consent: undefined }, { preserveScroll: true });
const remove = (search) => router.delete(route('saved-searches.destroy', search.id), { preserveScroll: true });
</script>

<template>
    <div class="max-w-[1120px] mx-auto px-5 md:px-10 py-9">
        <p class="uppercase text-blue-600 text-xs font-semibold tracking-widest mb-2">Your opportunity finder</p>
        <h1 class="font-display text-3xl font-semibold">Saved searches and alerts</h1>
        <p class="text-muted mt-2 max-w-2xl">Keep the filters that matter to you. Email alerts are optional, and you can pause them at any time.</p>
        <p v-if="!deliveryEnabled" class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">Saved searches are available. Email delivery is not enabled yet. Your alert choices will apply when delivery becomes available.</p>
        <div class="grid lg:grid-cols-[1fr_360px] gap-6 mt-7 items-start">
            <section aria-label="Your saved searches" class="space-y-4">
                <div v-if="!searches.length" class="border border-line rounded-2xl bg-white p-7"><h2 class="font-semibold">Your first saved search</h2><p class="text-muted mt-2">Browse opportunities, choose your filters, and select Save search.</p><Link :href="route('opportunities.index')" class="inline-block text-blue-600 font-semibold mt-4">Explore opportunities</Link></div>
                <article v-for="search in searches" :key="search.id" class="border border-line rounded-2xl bg-white p-6">
                    <h2 class="font-display text-xl font-semibold"><a :href="search.browse_url" class="hover:text-blue-600">{{ search.name }}</a></h2>
                    <p class="text-sm text-muted mt-2 break-words">{{ formatFilter(search.filters) }}</p>
                    <p class="text-sm mt-3">{{ search.frequency === 'off' ? 'Email alerts paused' : `${search.frequency === 'daily' ? 'Daily' : 'Weekly'} alerts, after 09:00 (${search.timezone})` }}</p>
                    <div class="flex flex-wrap gap-4 mt-4 text-sm font-semibold"><a :href="search.browse_url" class="text-blue-600">View matches</a><button type="button" class="text-blue-600" @click="edit(search)">Edit</button><button v-if="search.frequency !== 'off'" type="button" @click="pause(search)">Pause emails</button><button type="button" class="text-red-600" @click="remove(search)">Remove</button></div>
                </article>
            </section>
            <form class="bg-white border border-line rounded-2xl p-6 space-y-4" @submit.prevent="save">
                <h2 class="font-display text-xl font-semibold">{{ editing ? 'Edit saved search' : 'Save this search' }}</h2>
                <p class="text-xs text-muted break-words">{{ formatFilter(form.filters) }}</p>
                <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600" role="alert">{{ error }}</p>
                <label class="block text-sm font-semibold">Search name<input v-model="form.name" class="fld mt-2" required maxlength="120" placeholder="Remote design jobs"></label>
                <label class="block text-sm font-semibold">Email frequency<select v-model="form.frequency" class="fld mt-2"><option value="off">No email alerts</option><option value="daily">Daily</option><option value="weekly">Weekly</option></select></label>
                <label v-if="form.frequency !== 'off'" class="block text-sm font-semibold">Your timezone<select v-model="form.timezone" class="fld mt-2"><option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option></select></label>
                <label v-if="form.frequency !== 'off'" class="flex items-start gap-2 text-sm"><input v-model="form.consent" type="checkbox" required class="mt-1"> Email me matching opportunities at this frequency. I can unsubscribe from any alert.</label>
                <button class="cbtn bg-blue-600 text-white w-full" :disabled="form.processing">{{ editing ? 'Save changes' : 'Save search' }}</button>
                <button v-if="editing" type="button" class="text-sm text-muted" @click="editing = null; form.reset()">Cancel editing</button>
                <p class="text-xs text-muted">Each opportunity is included at most once across your saved searches. Availability is checked again before sending.</p>
            </form>
        </div>
    </div>
</template>
