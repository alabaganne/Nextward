<script setup>
import { computed, h, onMounted, ref, watch } from 'vue';
import { Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { Download, Pause, Play, Power, RefreshCw, Search } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import { appPath } from '@/utils/url';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.sources' }, () => page) });
const props = defineProps({ sources: Array, fetch: Object });

const fetchForm = useForm({ count: 10 });
const startFetch = () => fetchForm.post(route('imports.fetch'), { preserveScroll: true });
// While a fetch runs, the page asks for its progress every 5 seconds.
const poll = usePoll(5000, { only: ['fetch', 'sources'] }, { autoStart: false });
onMounted(() => watch(() => props.fetch?.running, (running) => running ? poll.start() : poll.stop(), { immediate: true }));
const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;

const search = ref('');
const filter = ref('all');
const filters = computed(() => [
    { key: 'all', label: 'All', test: () => true },
    { key: 'enabled', label: 'Enabled', test: (source) => source.enabled },
    { key: 'paused', label: 'Paused', test: (source) => !source.enabled },
    { key: 'permission', label: 'Needs permission', test: (source) => source.permission_status !== 'approved' },
    { key: 'errors', label: 'With errors', test: (source) => !!source.last_error },
].map((item) => ({ ...item, count: props.sources.filter(item.test).length })));
const shown = computed(() => {
    const words = search.value.trim().toLowerCase();
    const test = filters.value.find((item) => item.key === filter.value).test;
    return props.sources.filter((source) => test(source) && (!words || `${source.name} ${source.key} ${source.url}`.toLowerCase().includes(words)));
});

const runnable = (source) => source.enabled && source.permission_status === 'approved';
const toggle = (source) => router.put(route('imports.sources.update', source.id), { ...source, enabled: !source.enabled }, { preserveScroll: true });
const queue = (source, recheck = false) => router.post(route('imports.sources.run', source.id), { recheck }, { preserveScroll: true });
const date = (value) => value ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : 'Never';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
        <PageHeader title="Sources" description="The job boards and employer pages Nextward reads every day.">
            <template #actions>
                <form class="flex items-center gap-2" @submit.prevent="startFetch">
                    <label class="sr-only" for="fetch-count">How many new listings to add</label>
                    <select id="fetch-count" v-model="fetchForm.count" class="fld !w-auto !py-[11px] bg-white" :disabled="fetch?.running">
                        <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }} listings</option>
                    </select>
                    <button class="cta cta-secondary disabled:opacity-50 disabled:pointer-events-none" type="submit" :disabled="fetchForm.processing || fetch?.running">
                        <Download class="size-4" aria-hidden="true" />Fetch new listings
                    </button>
                </form>
                <Link class="cta cta-primary" :href="route('imports.sources.create')">Add a source</Link>
            </template>
        </PageHeader>

        <p v-if="fetchForm.errors.count" class="text-[13.5px] text-red-600 -mt-4 mb-5">{{ fetchForm.errors.count }}</p>
        <section v-if="fetch" class="rounded-card bg-white px-6 py-5 mb-6 text-[14px]" aria-live="polite">
            <p class="font-medium flex items-center gap-2">
                <RefreshCw v-if="fetch.running" class="size-4 text-blue-600 animate-spin" aria-hidden="true" />
                <template v-if="fetch.running">Fetching new listings: {{ fetch.added.length }} of {{ fetch.count }} added</template>
                <template v-else-if="fetch.added.length">{{ plural(fetch.added.length, 'new listing') }} added</template>
                <template v-else>No new listings found</template>
            </p>
            <p class="text-muted mt-1">
                {{ fetch.read }} of {{ plural(fetch.sources, 'source') }} read<template v-if="fetch.failed">, {{ fetch.failed }} could not be read (see <Link class="text-blue-700 font-medium" :href="route('imports.runs')">Runs &amp; health</Link>)</template>.
                Started by {{ fetch.by }} on {{ date(fetch.started_at) }}<template v-if="fetch.finished_at">, finished {{ date(fetch.finished_at) }}</template>.
                <template v-if="fetch.stopped"> It stopped before the end; start it again to go on.</template>
            </p>
            <p v-if="!fetch.running" class="text-muted mt-1">Only IT listings posted in the last {{ fetch.days }} days that a profile wants are added, never one already on Nextward. Listings waiting for review or a company check show in the <Link class="text-blue-700 font-medium" :href="route('imports.review', { state: 'all' })">review queue</Link>.</p>
            <ul v-if="fetch.added.length" class="mt-3 grid gap-1.5">
                <li v-for="role in fetch.added" :key="role.id">
                    <Link class="font-medium hover:text-blue-700" :href="appPath(role.public_url)">{{ role.title }}</Link>
                    <span class="text-muted"> · {{ role.company }} · {{ role.kind === 'job' ? 'Job' : 'Internship' }}</span>
                </li>
            </ul>
        </section>

        <div class="flex flex-wrap items-center gap-3 mb-5">
            <label class="relative w-full max-w-[320px]">
                <span class="sr-only">Search sources</span>
                <Search class="size-4 text-muted-2 absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true" />
                <input v-model="search" class="fld !py-2.5 !pl-10 bg-white" type="search" placeholder="Search by name or link">
            </label>
            <nav class="flex flex-wrap gap-2" aria-label="Filter sources">
                <button
                    v-for="item in filters"
                    :key="item.key"
                    type="button"
                    class="chip"
                    :class="{ 'is-active': filter === item.key }"
                    :aria-pressed="filter === item.key"
                    @click="filter = item.key"
                >{{ item.label }} <span class="opacity-70 tabular-nums">{{ item.count }}</span></button>
            </nav>
        </div>

        <div class="bg-white border border-transparent rounded-card overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-medium">
                    <tr><th scope="col">Source</th><th scope="col">Permission</th><th scope="col">Status</th><th scope="col">Listings</th><th scope="col">Last full read</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="source in shown" :key="source.id" class="border-b border-line-2 last:border-b-0 align-top [&_td]:px-[18px] [&_td]:py-4">
                        <td class="max-w-[360px]">
                            <Link class="font-medium text-[15px] hover:text-blue-700" :href="route('imports.sources.edit', source.id)">{{ source.name }}</Link>
                            <p class="text-[12.5px] text-muted mt-0.5"><span class="capitalize">{{ source.provider === 'jsonld' ? 'Job pages' : source.provider }}</span> · <span class="break-all">{{ source.url }}</span></p>
                            <p v-if="source.last_error" class="text-[12.5px] text-red-600 mt-1.5">{{ source.last_error }}</p>
                        </td>
                        <td><StatusPill set="import" :status="source.permission_status" /></td>
                        <td>
                            <StatusPill set="import" :status="source.enabled ? 'enabled' : 'paused'" />
                            <p class="text-[12.5px] text-muted mt-1.5">{{ source.publication_mode === 'automatic' ? 'Publishes on its own' : 'Every listing reviewed' }}</p>
                        </td>
                        <td class="text-[13.5px] tabular-nums whitespace-nowrap">
                            {{ source.items_count }} found
                            <Link v-if="source.pending_count" class="block text-blue-700 font-medium mt-0.5" :href="route('imports.review', { source_id: source.id, state: 'all' })">{{ source.pending_count }} waiting</Link>
                        </td>
                        <td class="text-[13.5px] text-muted whitespace-nowrap">{{ date(source.last_complete_at) }}<span class="block text-[12.5px]">every {{ source.cadence_hours }} h</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1.5">
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="`Run import for ${source.name}`" title="Run import" @click="queue(source)"><Play class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="`Recheck ${source.name}`" title="Recheck every listing" @click="queue(source, true)"><RefreshCw class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!source.enabled && source.permission_status !== 'approved'" :aria-label="`${source.enabled ? 'Pause' : 'Enable'} ${source.name}`" :title="source.enabled ? 'Pause' : 'Enable'" @click="toggle(source)">
                                    <component :is="source.enabled ? Pause : Power" class="size-4" aria-hidden="true" />
                                </button>
                                <Link class="cta cta-secondary cta-sm" :href="route('imports.sources.edit', source.id)">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!shown.length"><td colspan="6" class="px-[18px] py-8 text-[14px] text-muted">{{ sources.length ? 'No sources match.' : 'No sources yet. Add a job board or employer page to start.' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
