<script setup>
import { computed, h, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.review' }, () => page) });
const props = defineProps({ items: Object, counts: Object, sources: Array, filters: Object });
const page = usePage();

const states = computed(() => [
    { key: 'review', label: 'Waiting for review' },
    { key: 'pending', label: 'Not read yet' },
    { key: 'published', label: 'Published' },
    { key: 'rejected', label: 'Rejected' },
    { key: 'closed', label: 'Closed' },
    { key: 'all', label: 'All' },
].map((state) => ({ ...state, count: state.key === 'all' ? Object.values(props.counts).reduce((sum, n) => sum + Number(n), 0) : Number(props.counts[state.key] || 0) })));
const visit = (changes) => router.get(route('imports.review'), { ...props.filters, page: undefined, ...changes }, { preserveScroll: true, preserveState: true });

const publish = (item) => router.post(route('imports.items.publish', item.id), {}, { preserveScroll: true });
const rejection = useForm({ reason: '' });
const rejectingId = ref(null);
const reject = () => rejection.post(route('imports.items.reject', rejectingId.value), { preserveScroll: true, onSuccess: () => { rejectingId.value = null; rejection.reset(); } });
const date = (value) => value ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : 'Not yet';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
        <PageHeader title="Review queue" description="Listings found on the sources. Approve the ones that should go live, reject the rest." />

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-[#fef2f2] border border-[#fecaca] text-[#dc2626] rounded-[12px] p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <nav class="flex flex-wrap gap-2" aria-label="Filter by state">
                <button
                    v-for="state in states"
                    :key="state.key"
                    type="button"
                    class="chip"
                    :class="{ 'is-active': filters.state === state.key }"
                    :aria-pressed="filters.state === state.key"
                    @click="visit({ state: state.key })"
                >{{ state.label }} <span class="opacity-70 tabular-nums">{{ state.count }}</span></button>
            </nav>
            <label class="w-full max-w-[260px]">
                <span class="sr-only">Source</span>
                <select class="fld !py-2.5" :value="filters.source_id || ''" @change="visit({ source_id: $event.target.value || undefined })">
                    <option value="">All sources</option>
                    <option v-for="source in sources" :key="source.id" :value="source.id">{{ source.name }}</option>
                </select>
            </label>
        </div>

        <div class="bg-white border border-line rounded-[18px] overflow-hidden">
            <article v-for="item in items.data" :key="item.id" class="px-[22px] py-[18px] border-b border-line-2 last:border-b-0">
                <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="font-semibold text-[15.5px]">{{ item.normalized_data?.title || item.opportunity?.title || 'Not read yet' }}</h2>
                            <StatusPill set="import" :status="item.state" />
                        </div>
                        <p class="text-[13px] text-muted mt-1">
                            <template v-if="item.normalized_data">{{ item.normalized_data.employer }} · {{ item.normalized_data.kind }} · {{ item.normalized_data.location || 'No location' }} · </template>
                            {{ item.source_name }} · checked {{ date(item.last_verified_at) }}
                        </p>
                        <p v-if="item.error" class="text-[13px] text-[#dc2626] mt-1.5">{{ item.error }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button v-if="item.normalized_data && !['closed', 'published'].includes(item.state)" type="button" class="text-[13px] font-semibold text-white bg-blue-600 hover:bg-blue-700 px-3 py-[7px] rounded-[9px] transition-colors" @click="publish(item)">Approve</button>
                        <button v-if="item.state !== 'rejected'" type="button" class="text-[13px] font-semibold text-[#dc2626] bg-[#fef2f2] border border-[#fecaca] px-3 py-[7px] rounded-[9px]" :aria-expanded="rejectingId === item.id" @click="rejectingId = rejectingId === item.id ? null : item.id">Reject</button>
                        <Link v-if="item.opportunity" class="text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] hover:bg-blue-100 transition-colors" :href="route('imports.opportunities.show', item.opportunity.id)">Moderate</Link>
                        <a v-if="item.opportunity?.publication_status === 'published'" class="text-[13px] font-semibold text-ink-700 px-2 py-[7px] hover:text-blue-700" :href="item.opportunity.public_url">Open page</a>
                        <a class="inline-flex items-center gap-1 text-[13px] font-semibold text-ink-700 px-2 py-[7px] hover:text-blue-700" :href="item.source_url" target="_blank" rel="noopener noreferrer">Original <ExternalLink class="size-3.5" aria-hidden="true" /></a>
                    </div>
                </div>
                <form v-if="rejectingId === item.id" class="mt-3 flex flex-wrap items-end gap-3" @submit.prevent="reject">
                    <label class="grow text-[13px] font-semibold text-ink-700">Why reject it?<input v-model="rejection.reason" class="fld mt-1.5 !py-2.5" minlength="5" required></label>
                    <button class="cbtn !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c]" :disabled="rejection.processing">Reject listing</button>
                </form>
                <details v-if="item.normalized_data" class="mt-3 group">
                    <summary class="text-[13px] font-semibold text-blue-700 cursor-pointer">Facts and description</summary>
                    <div class="mt-3 rounded-[12px] bg-paper-2 p-4">
                        <p class="text-[12.5px] text-muted">Remote from Tunisia: {{ item.normalized_data.remote_eligibility }} · Relocation: {{ item.normalized_data.relocation }} · Visa: {{ item.normalized_data.visa_sponsorship }}<template v-if="item.normalized_data.field"> · Field: {{ item.normalized_data.field }}</template></p>
                        <p class="whitespace-pre-wrap text-[14px] leading-6 mt-3 max-h-[420px] overflow-auto">{{ item.normalized_data.description }}</p>
                    </div>
                </details>
            </article>
            <div v-if="!items.data.length" class="px-[22px] py-8 text-[14px] text-muted">{{ filters.state === 'review' ? 'Nothing is waiting for review.' : 'No listings match.' }}</div>
        </div>

        <Pagination :meta="items" />
    </div>
</template>
