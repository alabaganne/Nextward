<script setup>
import { computed, h, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Pause, Play, Power, RefreshCw, Search } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.sources' }, () => page) });
const props = defineProps({ sources: Array });

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
                <Link class="cbtn cbtn-primary" :href="route('imports.sources.create')">Add a source</Link>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-3 mb-5">
            <label class="relative w-full max-w-[320px]">
                <span class="sr-only">Search sources</span>
                <Search class="size-4 text-muted-2 absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true" />
                <input v-model="search" class="fld !py-2.5 !pl-10" type="search" placeholder="Search by name or link">
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

        <div class="bg-white border border-line rounded-[18px] overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-bold">
                    <tr><th scope="col">Source</th><th scope="col">Permission</th><th scope="col">Status</th><th scope="col">Listings</th><th scope="col">Last full read</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="source in shown" :key="source.id" class="border-b border-line-2 last:border-b-0 align-top [&_td]:px-[18px] [&_td]:py-4">
                        <td class="max-w-[360px]">
                            <Link class="font-semibold text-[15px] hover:text-blue-700" :href="route('imports.sources.edit', source.id)">{{ source.name }}</Link>
                            <p class="text-[12.5px] text-muted mt-0.5"><span class="capitalize">{{ source.provider === 'jsonld' ? 'Job pages' : source.provider }}</span> · <span class="break-all">{{ source.url }}</span></p>
                            <p v-if="source.last_error" class="text-[12.5px] text-[#dc2626] mt-1.5">{{ source.last_error }}</p>
                        </td>
                        <td><StatusPill set="import" :status="source.permission_status" /></td>
                        <td>
                            <StatusPill set="import" :status="source.enabled ? 'enabled' : 'paused'" />
                            <p class="text-[12.5px] text-muted mt-1.5">{{ source.publication_mode === 'automatic' ? 'Publishes on its own' : 'Every listing reviewed' }}</p>
                        </td>
                        <td class="text-[13.5px] tabular-nums whitespace-nowrap">
                            {{ source.items_count }} found
                            <Link v-if="source.pending_count" class="block text-blue-700 font-semibold mt-0.5" :href="route('imports.review', { source_id: source.id, state: 'all' })">{{ source.pending_count }} waiting</Link>
                        </td>
                        <td class="text-[13.5px] text-muted whitespace-nowrap">{{ date(source.last_complete_at) }}<span class="block text-[12.5px]">every {{ source.cadence_hours }} h</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1.5">
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="`Run import for ${source.name}`" title="Run import" @click="queue(source)"><Play class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="`Recheck ${source.name}`" title="Recheck every listing" @click="queue(source, true)"><RefreshCw class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!source.enabled && source.permission_status !== 'approved'" :aria-label="`${source.enabled ? 'Pause' : 'Enable'} ${source.name}`" :title="source.enabled ? 'Pause' : 'Enable'" @click="toggle(source)">
                                    <component :is="source.enabled ? Pause : Power" class="size-4" aria-hidden="true" />
                                </button>
                                <Link class="text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] hover:bg-blue-100 transition-colors" :href="route('imports.sources.edit', source.id)">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!shown.length"><td colspan="6" class="px-[18px] py-8 text-[14px] text-muted">{{ sources.length ? 'No sources match.' : 'No sources yet. Add a job board or employer page to start.' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
