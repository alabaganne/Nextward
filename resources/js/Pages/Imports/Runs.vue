<script setup>
import { h } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.runs' }, () => page) });
defineProps({ runs: Object, deliveryHealth: Array, uncertainDeliveries: Array });
const date = (value) => value ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader title="Runs & health" description="Recent imports and how alert emails are going out." />

        <h2 class="font-display text-[19px] font-semibold mb-3">Recent runs</h2>
        <div class="bg-white border border-line rounded-[18px] overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-bold">
                    <tr><th scope="col">Source</th><th scope="col">Status</th><th scope="col" class="text-right">Found</th><th scope="col" class="text-right">Read</th><th scope="col" class="text-right">Published</th><th scope="col">Started</th></tr>
                </thead>
                <tbody>
                    <tr v-for="run in runs.data" :key="run.id" class="border-b border-line-2 last:border-b-0 align-top text-[13.5px] [&_td]:px-[18px] [&_td]:py-3.5">
                        <td class="font-semibold">{{ run.import_source?.name }}<p v-if="run.error" class="font-normal text-[12.5px] text-[#dc2626] mt-1 max-w-[340px]">{{ run.error }}</p></td>
                        <td><StatusPill set="import" :status="run.status === 'completed' && !run.complete ? 'partial' : run.status" /></td>
                        <td class="text-right tabular-nums">{{ run.discovered }}</td>
                        <td class="text-right tabular-nums">{{ run.processed }}</td>
                        <td class="text-right tabular-nums">{{ run.published }}</td>
                        <td class="text-muted whitespace-nowrap">{{ date(run.started_at) }}</td>
                    </tr>
                    <tr v-if="!runs.data.length"><td colspan="6" class="px-[18px] py-8 text-[14px] text-muted">No runs yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="runs" />

        <h2 class="font-display text-[19px] font-semibold mt-10 mb-3">Alert emails</h2>
        <div class="flex flex-wrap gap-2.5">
            <div v-for="state in deliveryHealth" :key="state.status" class="bg-white border border-line rounded-[12px] px-4 py-3 min-w-[120px]">
                <p class="text-[12px] uppercase tracking-[.08em] text-muted-2 font-bold">{{ state.status }}</p>
                <p class="font-display text-[22px] font-semibold tabular-nums mt-0.5">{{ state.total }}</p>
            </div>
            <p v-if="!deliveryHealth.length" class="text-[14px] text-muted">No alert emails sent yet.</p>
        </div>
        <article v-for="batch in uncertainDeliveries" :key="batch.batch_id" class="mt-3 rounded-[12px] bg-[#fef3c7] text-[#92400e] p-4 text-[14px]">
            <p class="font-semibold">Batch {{ batch.batch_id }} may not have been sent</p>
            <p v-if="batch.last_error" class="mt-1">{{ batch.last_error }}</p>
            <p class="mt-2">Check the mail provider, then run <code class="font-mono text-[13px]">php artisan opportunities:review-digest {{ batch.batch_id }}</code> with the outcome. Retries for this search are paused until then.</p>
        </article>
    </div>
</template>
