<script setup>
import { h, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.claims' }, () => page) });
defineProps({ claims: Array });
const page = usePage();

const notes = ref({});
const review = (claim, status) => router.put(route('imports.claims.review', claim.id), { status, review_notes: notes.value[claim.id] || '' }, { preserveScroll: true });
const date = (value) => value ? new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader title="Employer claims" description="People asking to manage a company page. Approve only after checking they really work there." />

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-[#fef2f2] border border-[#fecaca] text-[#dc2626] rounded-[12px] p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="bg-white border border-line rounded-[18px] overflow-hidden">
            <article v-for="claim in claims" :key="claim.id" class="px-[22px] py-[18px] border-b border-line-2 last:border-b-0">
                <h2 class="font-semibold text-[15.5px]">{{ claim.company_name || `Company ${claim.company_id}` }}</h2>
                <p class="text-[13px] text-muted mt-0.5">{{ claim.claimant_name }} · {{ claim.claimant_email }} · {{ date(claim.created_at) }}</p>
                <p class="text-[14px] leading-6 whitespace-pre-wrap mt-3 rounded-[12px] bg-paper-2 p-4">{{ claim.evidence }}</p>
                <label class="block text-[13px] font-semibold text-ink-700 mt-3">How you checked it<textarea v-model="notes[claim.id]" class="fld mt-1.5 !min-h-[64px]" rows="2" placeholder="Independent proof that this person can speak for the employer (at least 20 characters)"></textarea></label>
                <div class="flex gap-2 mt-3">
                    <button type="button" class="text-[13px] font-semibold text-white bg-blue-600 hover:bg-blue-700 px-3 py-[7px] rounded-[9px] transition-colors" @click="review(claim, 'approved')">Approve claim</button>
                    <button type="button" class="text-[13px] font-semibold text-[#dc2626] bg-[#fef2f2] border border-[#fecaca] px-3 py-[7px] rounded-[9px]" @click="review(claim, 'rejected')">Reject claim</button>
                </div>
            </article>
            <div v-if="!claims.length" class="px-[22px] py-8 text-[14px] text-muted">No claims waiting.</div>
        </div>
    </div>
</template>
