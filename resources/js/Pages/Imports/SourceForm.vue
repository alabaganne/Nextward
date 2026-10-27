<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.sources' }, () => page) });
const props = defineProps({ source: { type: Object, default: null } });

const editing = computed(() => !!props.source);
const settings = { employer_name: '', job_path_prefix: '', authoritative_employer: false, scope: 'all' };
const defaults = { key: '', name: '', provider: 'jsonld', url: '', allowed_domains: [], permission_status: 'pending', permission_notes: '', publication_mode: 'review', enabled: false, company_id: null, cadence_hours: 24, request_delay_ms: 1000, max_requests: 100 };
const form = useForm({
    ...Object.fromEntries(Object.keys(defaults).map((key) => [key, props.source?.[key] ?? defaults[key]])),
    settings: { ...settings, ...(props.source?.settings || {}) },
});
const domainText = ref((props.source?.allowed_domains || []).join(', '));

const submit = () => {
    form.allowed_domains = [...new Set(domainText.value.split(/[\s,]+/).map((part) => part.trim().toLowerCase()).filter(Boolean))];
    editing.value ? form.put(route('imports.sources.update', props.source.id)) : form.post(route('imports.sources.store'));
};
</script>

<template>
    <div class="max-w-[960px] mx-auto px-5 md:px-10 py-9">
        <div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
            <Link class="hover:text-blue-700" :href="route('imports.sources.index')">Sources</Link>
            <ChevronRight aria-hidden="true" />
            <span class="text-ink-700 font-medium">{{ editing ? source.name : 'New source' }}</span>
        </div>
        <h1 class="mb-6 text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? 'Edit source' : 'Add a source' }}</h1>
        <div v-if="Object.keys(form.errors).length" class="bg-red-50 border border-red-200 text-red-700 rounded-card p-4 mb-6" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
        <form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
            <div class="p-6 space-y-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <label class="text-sm font-medium">Name<input v-model="form.name" class="fld mt-2" required></label><label class="text-sm font-medium">Stable source key<input v-model="form.key" class="fld mt-2" required placeholder="company-greenhouse"></label><label class="text-sm font-medium">Provider<select v-model="form.provider" class="fld mt-2"><option value="jsonld">Public pages with JSON-LD</option><option value="greenhouse">Greenhouse</option><option value="lever">Lever</option><option value="ashby">Ashby</option><option value="workable">Workable</option><option value="recruitee">Recruitee</option><option value="himalayas">Himalayas (job leads)</option><option value="remoteok">Remote OK (job leads)</option><option value="remotive">Remotive (job leads)</option><option value="wwr">We Work Remotely RSS (job leads)</option><option value="hn">Hacker News Who is hiring (job leads)</option><option value="pages">Job pages linked from board posts (AI check)</option></select></label>
                    <label class="text-sm font-medium md:col-span-2">Feed, board API, or sitemap URL<input v-model="form.url" class="fld mt-2" type="url" required></label><label class="text-sm font-medium">Allowed domains<input v-model="domainText" class="fld mt-2" :required="form.provider !== 'pages'" :placeholder="form.provider === 'pages' ? 'Any site: the links in the posts' : 'boards-api.greenhouse.io, example.com'"></label>
                    <label class="text-sm font-medium">Reuse permission<select v-model="form.permission_status" class="fld mt-2"><option value="pending">Pending review</option><option value="approved">Approved for the intended reuse</option><option value="revoked">Revoked</option></select></label><label class="text-sm font-medium">Publication<select v-model="form.publication_mode" class="fld mt-2"><option value="review">Review every new role</option><option value="automatic">Publish validated roles automatically</option><option value="leads">Job leads only, never published</option></select></label><label class="text-sm font-medium">Verified company ID, optional<input v-model.number="form.company_id" class="fld mt-2" type="number" min="1"></label>
                    <label class="text-sm font-medium md:col-span-3">Permission evidence and review notes<textarea v-model="form.permission_notes" class="fld mt-2" rows="3" placeholder="Record the agreement, source URL, scope of reuse, and review date. Robots access alone does not grant republication rights."></textarea></label>
                    <label class="text-sm font-medium">Cadence, hours<input v-model.number="form.cadence_hours" class="fld mt-2" type="number" min="1" max="168" required></label><label class="text-sm font-medium">Delay between requests, milliseconds<input v-model.number="form.request_delay_ms" class="fld mt-2" type="number" min="500" max="60000" required></label><label class="text-sm font-medium">Maximum requests per run<input v-model.number="form.max_requests" class="fld mt-2" type="number" min="1" max="1000" required></label>
                    <label class="text-sm font-medium">Employer name supplied by this board<input v-model="form.settings.employer_name" class="fld mt-2"></label><label class="text-sm font-medium">Job URL path prefix, optional<input v-model="form.settings.job_path_prefix" class="fld mt-2" placeholder="/careers/jobs/"></label><label class="text-sm font-medium">Listings to keep<select v-model="form.settings.scope" class="fld mt-2"><option value="all">Everything this source lists</option><option value="reachable">Internships, plus jobs open to people in Tunisia</option></select></label>
                </div>
                <label class="flex gap-2 items-start text-sm"><input v-model="form.settings.authoritative_employer" type="checkbox" class="mt-1"> This is the employer's authoritative board. Confirmed closure takes precedence over aggregator copies.</label>
                <label class="flex gap-2 items-center text-sm"><input v-model="form.enabled" type="checkbox"> Enable scheduled imports once reuse permission is approved</label>
            </div>
            <div class="px-6 py-4 border-t border-line-2 flex items-center justify-end gap-2.5">
                <Link class="cta cta-secondary" :href="route('imports.sources.index')">Cancel</Link>
                <button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Add source' }}</button>
            </div>
        </form>
    </div>
</template>
