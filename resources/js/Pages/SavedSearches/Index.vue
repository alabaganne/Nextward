<script setup>
import { h, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'saved-searches' }, () => page) });
const props = defineProps({ searches: Array, initialFilters: Object, initialBrowseUrl: String, deliveryEnabled: Boolean, timezones: Array });
const form = useForm({ name: '', filters: props.initialFilters || {}, frequency: 'off', timezone: 'Africa/Tunis', consent: false });
const editing = ref(null);
// Show the form when the user came here from the opportunities page.
const hasFilters = Object.keys(props.initialFilters || {}).length > 0 || usePage().url.includes('?');
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
const removingId = ref(null);
const remove = (search) => router.delete(route('saved-searches.destroy', search.id), { preserveScroll: true, onFinish: () => { removingId.value = null; } });
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
        <PageHeader title="Saved searches" description="Keep the filters you use most. Email alerts are optional and you can pause them at any time.">
            <template #actions><Link :href="route('app.opportunities.index')" class="cbtn cbtn-secondary h-11 !py-0">Browse opportunities</Link></template>
        </PageHeader>
        <p v-if="!deliveryEnabled" class="mb-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">Email alerts are not switched on yet. Your alert choices will apply once they are.</p>
        <div class="grid gap-6 items-start" :class="hasFilters || editing ? 'lg:grid-cols-[1fr_360px]' : ''">
            <section aria-label="Your saved searches" class="space-y-4">
                <div v-if="!searches.length" class="border border-line rounded-[16px] bg-white p-7">
                    <h2 class="font-display text-lg font-semibold">No saved searches yet</h2>
                    <p class="text-muted text-[15px] mt-1.5 max-w-[520px]">Open Opportunities, set your filters, then choose "Save this search and set alerts". It will show up here.</p>
                    <Link :href="route('app.opportunities.index')" class="cbtn cbtn-primary h-11 !py-0 mt-5">Set up a search</Link>
                </div>
                <article v-for="search in searches" :key="search.id" class="border border-line rounded-[16px] bg-white p-5">
                    <h2 class="font-display text-lg font-semibold"><a :href="search.browse_url" class="hover:text-blue-700">{{ search.name }}</a></h2>
                    <p class="text-sm text-muted mt-1.5 break-words">{{ formatFilter(search.filters) }}</p>
                    <p class="text-sm mt-3">{{ search.frequency === 'off' ? 'Email alerts paused' : `${search.frequency === 'daily' ? 'Daily' : 'Weekly'} alerts, after 09:00 (${search.timezone})` }}</p>
                    <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-line-2 text-sm font-semibold"><a :href="search.browse_url" class="text-blue-700 hover:underline">View matches</a><button type="button" class="text-blue-700 hover:underline" @click="edit(search)">Edit</button><button v-if="search.frequency !== 'off'" type="button" class="text-ink-700 hover:underline" @click="pause(search)">Pause emails</button><button type="button" class="text-red-600 hover:underline ml-auto" :aria-expanded="removingId === search.id" @click="removingId = search.id">Remove</button></div>
                    <div v-if="removingId === search.id" class="flex flex-wrap items-center gap-3 mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm">
                        <p class="text-red-900 mr-auto" role="alert">Remove this saved search? Its email alerts stop too.</p>
                        <button type="button" class="font-semibold text-ink-700 hover:underline" @click="removingId = null">Cancel</button>
                        <button type="button" class="cbtn !px-4 !py-2 !text-sm bg-[#dc2626] text-white hover:bg-[#b91c1c]" @click="remove(search)">Yes, remove</button>
                    </div>
                </article>
            </section>
            <form v-if="hasFilters || editing" class="bg-white border border-line rounded-[16px] p-6 space-y-4 lg:sticky lg:top-20" @submit.prevent="save">
                <h2 class="font-display text-lg font-semibold">{{ editing ? 'Edit saved search' : 'Save this search' }}</h2>
                <p class="text-xs text-muted break-words">{{ formatFilter(form.filters) }}</p>
                <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600" role="alert">{{ error }}</p>
                <label class="block text-sm font-semibold">Search name<input v-model="form.name" class="fld mt-2" required maxlength="120" placeholder="Remote design jobs"></label>
                <label class="block text-sm font-semibold">Email frequency<select v-model="form.frequency" class="fld mt-2"><option value="off">No email alerts</option><option value="daily">Daily</option><option value="weekly">Weekly</option></select></label>
                <label v-if="form.frequency !== 'off'" class="block text-sm font-semibold">Your timezone<select v-model="form.timezone" class="fld mt-2"><option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option></select></label>
                <label v-if="form.frequency !== 'off'" class="flex items-start gap-2 text-sm"><input v-model="form.consent" type="checkbox" required class="ck mt-0.5"> Email me matching opportunities at this frequency. I can unsubscribe from any alert.</label>
                <button class="cbtn cbtn-primary w-full" :disabled="form.processing">{{ editing ? 'Save changes' : 'Save search' }}</button>
                <button v-if="editing" type="button" class="text-sm text-muted hover:text-ink" @click="editing = null; form.reset()">Cancel editing</button>
                <p class="text-xs text-muted">You get each opportunity once across all your saved searches.</p>
            </form>
        </div>
    </div>
</template>
