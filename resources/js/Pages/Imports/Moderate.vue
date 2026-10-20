<script setup>
import { computed, h, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ChevronRight, ExternalLink } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.review' }, () => page) });
const props = defineProps({ opportunity: Object, sources: Array, reports: Array });
const page = usePage();

const facts = { remote_eligibility: ['eligible', 'restricted', 'unknown'], relocation: ['yes', 'no', 'unknown'], visa_sponsorship: ['yes', 'no', 'unknown'], kind: ['internship', 'job'] };
const labels = { title: 'Title', description: 'Description', kind: 'Internship or job', location: 'Location', country_code: 'Country code', remote_eligibility: 'Remote from Tunisia', relocation: 'Relocation', visa_sponsorship: 'Visa sponsorship' };
const moderation = useForm({ action: 'suppress', reason: '', corrections: {} });
const field = ref('remote_eligibility');
const value = ref(props.opportunity.remote_eligibility);
const options = computed(() => facts[field.value] || null);
const pickField = () => { value.value = props.opportunity[field.value] ?? ''; };
const moderate = () => {
    moderation.corrections = moderation.action === 'correct' ? { [field.value]: value.value } : {};
    moderation.put(route('imports.opportunities.moderate', props.opportunity.id), { preserveScroll: true, onSuccess: () => moderation.reset('reason') });
};

const merge = useForm({ survivor_id: '', reason: '' });
const submitMerge = () => merge.post(route('imports.opportunities.merge', props.opportunity.id), { preserveScroll: true });
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader :title="opportunity.title" :document-title="`Moderate ${opportunity.title}`">
            <template #before>
                <div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-3 [&_svg]:size-3.5">
                    <Link class="hover:text-blue-700" :href="route('imports.review')">Review queue</Link>
                    <ChevronRight aria-hidden="true" />
                    <span class="text-ink-700 font-semibold">Listing {{ opportunity.id }}</span>
                </div>
            </template>
            <template #description>{{ opportunity.company }} · {{ opportunity.kind }} · {{ opportunity.location || 'No location' }}</template>
            <template #actions>
                <a v-if="opportunity.publication_status === 'published'" class="cbtn cbtn-secondary border border-line" :href="opportunity.public_url">Open page <ExternalLink class="size-4" aria-hidden="true" /></a>
            </template>
        </PageHeader>

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-[#fef2f2] border border-[#fecaca] text-[#dc2626] rounded-[12px] p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="flex flex-wrap gap-2 mb-6">
            <StatusPill set="import" :status="opportunity.publication_status" />
            <StatusPill set="import" :status="opportunity.status === 'closed' ? 'closed' : 'open'" />
            <span class="inline-flex items-center text-xs font-semibold px-[11px] py-[5px] rounded-full bg-paper-3 text-ink-700">{{ opportunity.origin === 'imported' ? 'Imported' : 'Posted by the company' }}</span>
        </div>
        <p v-if="opportunity.merged_into_id" class="rounded-[12px] bg-blue-50 text-blue-700 p-4 text-[14px] mb-6">This listing was merged into <Link class="font-semibold underline" :href="route('imports.opportunities.show', opportunity.merged_into_id)">listing {{ opportunity.merged_into_id }}</Link>.</p>

        <div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">
            <div class="space-y-5">
                <form class="bg-white border border-line rounded-[18px] p-6 space-y-4" @submit.prevent="moderate">
                    <h2 class="font-display text-[19px] font-semibold">Hide, close or correct</h2>
                    <label class="block text-[13px] font-semibold text-ink-700">Action
                        <select v-model="moderation.action" class="fld mt-1.5">
                            <option value="suppress">Hide from the site</option>
                            <option value="close">Mark as closed</option>
                            <option value="restore">Show a hidden listing again</option>
                            <option value="correct">Correct a fact</option>
                        </select>
                    </label>
                    <div v-if="moderation.action === 'correct'" class="grid sm:grid-cols-2 gap-4">
                        <label class="block text-[13px] font-semibold text-ink-700">Fact
                            <select v-model="field" class="fld mt-1.5" @change="pickField"><option v-for="(label, key) in labels" :key="key" :value="key">{{ label }}</option></select>
                        </label>
                        <label class="block text-[13px] font-semibold text-ink-700" :class="{ 'sm:col-span-2': !options }">Correct value
                            <select v-if="options" v-model="value" class="fld mt-1.5"><option v-for="option in options" :key="option">{{ option }}</option></select>
                            <textarea v-else v-model="value" class="fld mt-1.5" :rows="field === 'description' ? 10 : 2" required></textarea>
                        </label>
                    </div>
                    <label class="block text-[13px] font-semibold text-ink-700">Reason and evidence<textarea v-model="moderation.reason" class="fld mt-1.5" required minlength="5" placeholder="What you checked and why"></textarea></label>
                    <button class="cbtn cbtn-primary" :disabled="moderation.processing">Save</button>
                </form>

                <form v-if="!opportunity.merged_into_id" class="bg-white border border-line rounded-[18px] p-6 space-y-4" @submit.prevent="submitMerge">
                    <div>
                        <h2 class="font-display text-[19px] font-semibold">Merge into another listing</h2>
                        <p class="text-[14px] text-muted mt-1">For the same role posted twice by the same company. This listing is hidden and its link, saves and applications move to the one you keep. Keep the listing the company posted itself.</p>
                    </div>
                    <label class="block text-[13px] font-semibold text-ink-700">Listing to keep (ID)<input v-model.number="merge.survivor_id" class="fld mt-1.5 max-w-[200px]" type="number" min="1" required></label>
                    <label class="block text-[13px] font-semibold text-ink-700">Why they are the same role<textarea v-model="merge.reason" class="fld mt-1.5" required minlength="10"></textarea></label>
                    <button class="cbtn cbtn-secondary border border-line" :disabled="merge.processing">Merge listings</button>
                </form>
            </div>

            <aside class="space-y-5">
                <section class="bg-white border border-line rounded-[18px] p-5">
                    <h2 class="font-display text-[16px] font-semibold mb-3">Facts</h2>
                    <dl class="text-[13.5px] space-y-2">
                        <div v-for="key in ['remote_eligibility', 'relocation', 'visa_sponsorship', 'country_code']" :key="key" class="flex justify-between gap-3"><dt class="text-muted">{{ labels[key] }}</dt><dd class="font-semibold">{{ opportunity[key] || '—' }}</dd></div>
                    </dl>
                </section>
                <section class="bg-white border border-line rounded-[18px] p-5">
                    <h2 class="font-display text-[16px] font-semibold mb-3">Sources</h2>
                    <ul class="space-y-3 text-[13.5px]">
                        <li v-for="item in sources" :key="item.id">
                            <div class="flex items-center justify-between gap-2"><span class="font-semibold truncate">{{ item.source_name }}</span><StatusPill set="import" :status="item.state" /></div>
                            <a class="text-blue-700 break-all text-[12.5px]" :href="item.source_url" target="_blank" rel="noopener noreferrer">{{ item.source_url }}</a>
                        </li>
                        <li v-if="!sources.length" class="text-muted">Posted on Nextward, no outside source.</li>
                    </ul>
                </section>
                <section v-if="reports.length" class="bg-white border border-line rounded-[18px] p-5">
                    <h2 class="font-display text-[16px] font-semibold mb-3">Open reports</h2>
                    <ul class="space-y-3 text-[13.5px]">
                        <li v-for="report in reports" :key="report.id"><span class="font-semibold capitalize">{{ String(report.reason).replaceAll('_', ' ') }}</span><p class="text-muted mt-0.5 whitespace-pre-wrap">{{ report.details }}</p></li>
                    </ul>
                    <Link class="inline-block mt-3 text-[13px] font-semibold text-blue-700" :href="route('imports.reports')">Resolve on Reports</Link>
                </section>
            </aside>
        </div>
    </div>
</template>
