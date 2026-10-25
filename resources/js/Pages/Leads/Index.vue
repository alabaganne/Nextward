<script setup>
import { computed, h, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { EyeOff, Mail, Plus, Upload } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import LeadCard from '@/Components/Leads/LeadCard.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'leads' }, () => page) });
const props = defineProps({
	filters: Object, day: String, leads: Object, skips: Object, hidden: Array,
	counts: Object, reasons: Object, statuses: Array, canSuggest: Boolean, gmail: Object,
});

const statusLabels = { to_apply: 'To apply', opened: 'Opened', applied: 'Applied', interviewing: 'Interviewing', offer: 'Offer', rejected: 'Rejected', skipped: 'Skipped' };
const reasonLabels = { repost: 'Repost', ineligible: 'Not open to Tunisia', seniority: 'Level', years: 'Too many years', duplicate: 'Same job', applied: 'Already applied', cooldown: 'Applied there lately', low_score: 'Low score', cap: 'Over the daily limit' };
const total = computed(() => Object.values(props.counts || {}).reduce((sum, n) => sum + Number(n), 0));
const skipped = computed(() => Object.values(props.reasons || {}).reduce((sum, n) => sum + Number(n), 0));
const dayLabel = computed(() => props.day ? new Date(`${props.day}T12:00:00`).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) : null);
const tabs = computed(() => [
	{ key: 'today', label: 'Today', count: props.filters.tab === 'today' ? props.leads?.data.length : null },
	{ key: 'all', label: 'All leads', count: total.value },
	{ key: 'skipped', label: 'Left out', count: skipped.value },
]);

const search = ref(props.filters.q || '');
const visit = (changes) => router.get(route('leads.index'), { ...props.filters, page: undefined, ...changes }, { preserveScroll: true, preserveState: true });
const select = (key, value) => visit({ [key]: value || undefined });

const adding = ref(false);
const form = useForm({ company: '', title: '', url: '', status: 'applied', applied_at: new Date().toISOString().slice(0, 10), notes: '' });
const add = () => form.post(route('leads.store'), { preserveScroll: true, onSuccess: () => { form.reset(); adding.value = false; } });

const gmailRead = computed(() => props.gmail?.synced_at ? `Last read ${new Date(props.gmail.synced_at).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}.` : 'Not read yet.');
const readGmail = () => router.post(route('leads.gmail.sync'), {}, { preserveScroll: true });
const disconnectGmail = () => { if (confirm('Disconnect Gmail? Leads already added stay.')) router.delete(route('leads.gmail.disconnect'), { preserveScroll: true }); };

const upload = useForm({ file: null });
const importFile = (event) => {
	upload.file = event.target.files[0];
	if (upload.file) upload.post(route('leads.import'), { preserveScroll: true, forceFormData: true, onFinish: () => { event.target.value = ''; } });
};
</script>

<template>
	<div>
		<PageHeader title="Job leads" description="New software roles you can take from Tunisia, picked every morning at 07:00. A job never shows twice, and roles at companies you applied to stay hidden for 60 days.">
			<template #actions>
				<label class="cbtn cbtn-secondary !text-[14px] !px-4 !py-2.5" :class="{ 'opacity-60': upload.processing }">
					<Upload class="size-4" aria-hidden="true" />{{ upload.processing ? 'Importing…' : 'Import' }}
					<input type="file" accept=".json,.csv,.txt" class="sr-only" :disabled="upload.processing" @change="importFile">
				</label>
				<button type="button" class="cbtn cbtn-primary !text-[14px] !px-4 !py-2.5" :aria-expanded="adding" @click="adding = !adding"><Plus class="size-4" aria-hidden="true" />Add a job</button>
			</template>
		</PageHeader>
		<p v-if="upload.errors.file" class="-mt-4 mb-5 text-[13.5px] text-[#dc2626]" role="alert">{{ upload.errors.file }}</p>
		<p class="-mt-4 text-[13px] text-muted">Import takes jobs.json from your old tracker, a Tsenta or LinkedIn export (Job Applications.csv), or any CSV or JSON with company and role columns.</p>
		<div class="mt-2 mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px]">
			<Mail class="size-4 text-muted" aria-hidden="true" />
			<template v-if="gmail.connected">
				<span class="text-ink-700">Gmail connected, read only ({{ gmail.email }}). {{ gmailRead }}</span>
				<button type="button" class="font-semibold text-blue-700 hover:underline" @click="readGmail">Read now</button>
				<button type="button" class="font-semibold text-ink-700 hover:underline" @click="disconnectGmail">Disconnect</button>
			</template>
			<template v-else-if="gmail.ready">
				<span class="text-muted">Connect Gmail to add applications from confirmation emails. Nextward only reads them.</span>
				<a :href="route('leads.gmail.connect')" class="font-semibold text-blue-700 hover:underline">Connect Gmail</a>
			</template>
			<span v-else class="text-muted">Gmail can add applications from confirmation emails once GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET are set.</span>
		</div>

		<form v-if="adding" class="mb-6 bg-white border border-line rounded-[18px] p-5 grid gap-3 sm:grid-cols-2" @submit.prevent="add">
			<label class="text-[13px] font-semibold text-ink-700">Company<input v-model="form.company" class="fld mt-1.5" required maxlength="191"></label>
			<label class="text-[13px] font-semibold text-ink-700">Role<input v-model="form.title" class="fld mt-1.5" required maxlength="255"></label>
			<label class="text-[13px] font-semibold text-ink-700 sm:col-span-2">Link to the posting<input v-model="form.url" type="url" class="fld mt-1.5" placeholder="https://"></label>
			<label class="text-[13px] font-semibold text-ink-700">Status
				<select v-model="form.status" class="fld mt-1.5"><option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] }}</option></select>
			</label>
			<label class="text-[13px] font-semibold text-ink-700">Applied on<input v-model="form.applied_at" type="date" class="fld mt-1.5"></label>
			<label class="text-[13px] font-semibold text-ink-700 sm:col-span-2">Notes<textarea v-model="form.notes" class="fld mt-1.5" rows="2" /></label>
			<p v-if="Object.keys(form.errors).length" class="sm:col-span-2 text-[13px] text-[#dc2626]">{{ Object.values(form.errors)[0] }}</p>
			<div class="sm:col-span-2 flex gap-2"><button class="cbtn cbtn-primary" :disabled="form.processing">Save</button><button type="button" class="cbtn cbtn-secondary" @click="adding = false">Cancel</button></div>
		</form>

		<nav class="flex flex-wrap gap-2 mb-4" aria-label="Job lead lists">
			<button v-for="tab in tabs" :key="tab.key" type="button" class="rounded-[10px] px-3.5 py-2 text-[14px] font-semibold transition-colors"
				:class="filters.tab === tab.key ? 'bg-navy-900 text-white' : 'bg-white border border-line text-ink-700 hover:border-blue-300'"
				:aria-current="filters.tab === tab.key ? 'page' : undefined" @click="visit({ tab: tab.key, status: undefined, reason: undefined })">
				{{ tab.label }}<span v-if="tab.count !== null && tab.count !== undefined" class="ml-1.5 opacity-70">{{ tab.count }}</span>
			</button>
		</nav>

		<form class="flex flex-wrap gap-2.5 mb-5" role="search" @submit.prevent="visit({ q: search || undefined })">
			<input v-model="search" type="search" class="fld !w-auto grow min-w-[200px] !py-2.5" placeholder="Search company, role or notes" aria-label="Search job leads">
			<template v-if="filters.tab !== 'skipped'">
				<select class="fld !w-auto !py-2.5" :value="filters.eligibility || ''" aria-label="Eligibility" @change="select('eligibility', $event.target.value)">
					<option value="">Any eligibility</option><option value="remote">Remote from Tunisia</option><option value="visa">Visa or relocation</option><option value="unclear">Check eligibility</option><option value="excluded">Not open to Tunisia</option>
				</select>
				<select class="fld !w-auto !py-2.5" :value="filters.stage || ''" aria-label="Company stage" @change="select('stage', $event.target.value)">
					<option value="">Startups and established</option><option value="startup">Startups</option><option value="established">Established companies</option>
				</select>
				<select class="fld !w-auto !py-2.5" :value="filters.status || ''" aria-label="Status" @change="select('status', $event.target.value)">
					<option value="">Any status</option><option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] }} ({{ counts[status] || 0 }})</option>
				</select>
			</template>
			<select v-else class="fld !w-auto !py-2.5" :value="filters.reason || ''" aria-label="Reason" @change="select('reason', $event.target.value)">
				<option value="">Any reason</option><option v-for="(label, key) in reasonLabels" :key="key" :value="key">{{ label }} ({{ reasons[key] || 0 }})</option>
			</select>
			<button class="cbtn cbtn-secondary !py-2.5 !px-4 !text-[14px]">Search</button>
		</form>

		<template v-if="filters.tab !== 'skipped'">
			<p v-if="filters.tab === 'today' && dayLabel" class="mb-3 text-[13.5px] text-muted">Picked on {{ dayLabel }}</p>
			<div class="grid gap-4">
				<LeadCard v-for="lead in leads.data" :key="lead.id" :lead="lead" :statuses="statuses" :can-suggest="canSuggest" />
			</div>
			<div v-if="!leads.data.length" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center text-[14.5px] text-muted">
				{{ filters.tab === 'today' ? 'No leads yet. The daily run picks new roles every morning at 07:00 Tunis time.' : 'No leads match.' }}
			</div>
			<Pagination v-if="filters.tab === 'all'" :meta="leads" />

			<section v-if="filters.tab === 'today' && hidden.length" class="mt-8" aria-labelledby="hidden-title">
				<h2 id="hidden-title" class="flex items-center gap-2 font-display text-[18px] font-semibold"><EyeOff class="size-4.5" aria-hidden="true" />Hidden today</h2>
				<p class="text-[13.5px] text-muted mt-1 mb-3">New roles at companies you already applied to.</p>
				<ul class="bg-white border border-line rounded-[18px] divide-y divide-line-2">
					<li v-for="skip in hidden" :key="skip.id" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-[14px]">
						<span class="rounded-md bg-amber-50 px-2 py-0.5 text-[12px] font-semibold text-amber-900">Already applied</span>
						<span class="font-semibold">{{ skip.company_name }}</span>
						<a v-if="skip.url" :href="skip.url" target="_blank" rel="noopener noreferrer" class="text-ink-700 hover:text-blue-700">{{ skip.title }}</a>
						<span v-else class="text-ink-700">{{ skip.title }}</span>
						<span class="text-[13px] text-muted basis-full">{{ skip.detail }}</span>
					</li>
				</ul>
			</section>
		</template>

		<template v-else>
			<ul class="bg-white border border-line rounded-[18px] divide-y divide-line-2">
				<li v-for="skip in skips.data" :key="skip.id" class="px-5 py-3.5 text-[14px]">
					<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
						<span class="rounded-md bg-paper-2 px-2 py-0.5 text-[12px] font-semibold text-ink-700">{{ reasonLabels[skip.reason] || skip.reason }}</span>
						<span class="font-semibold">{{ skip.company_name }}</span>
						<a v-if="skip.url" :href="skip.url" target="_blank" rel="noopener noreferrer" class="text-ink-700 hover:text-blue-700">{{ skip.title }}</a>
						<span v-else class="text-ink-700">{{ skip.title }}</span>
						<span class="ml-auto text-[12.5px] text-muted">{{ skip.run_date }}</span>
					</div>
					<p v-if="skip.detail" class="mt-1 text-[13px] text-muted" dir="auto">{{ skip.detail }}</p>
				</li>
				<li v-if="!skips.data.length" class="px-5 py-8 text-[14px] text-muted">Nothing was left out yet.</li>
			</ul>
			<Pagination :meta="skips" />
		</template>
	</div>
</template>
