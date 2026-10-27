<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ExternalLink, MapPin, Search, Trash2, UserRound } from '@lucide/vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';

const props = defineProps({
	lead: { type: Object, required: true },
	statuses: { type: Array, required: true },
	canSuggest: Boolean,
});

const statusLabels = { to_apply: 'To apply', opened: 'Opened', applied: 'Applied', interviewing: 'Interviewing', offer: 'Offer', rejected: 'Rejected', skipped: 'Skipped' };
const eligibility = {
	remote: ['Remote from Tunisia', 'bg-green-50 text-green-800'],
	visa: ['Visa or relocation', 'bg-blue-50 text-blue-800'],
	unclear: ['Check eligibility', 'bg-amber-50 text-amber-900'],
	excluded: ['Not open to Tunisia', 'bg-red-50 text-red-800'],
};

const save = (data) => router.put(route('leads.update', props.lead.id), data, { preserveScroll: true, preserveState: true });
const notes = ref(props.lead.notes || '');
// The open link marks the lead opened on the server; the list catches up a moment later.
const refreshSoon = () => setTimeout(() => router.reload({ only: ['leads', 'counts'] }), 1500);
const appliedAt = ref(props.lead.applied_at || '');

const adding = ref(false);
const contact = useForm({ name: '', title: '', profile_url: '', source_url: '', note: '', mutual: '', source: 'manual' });
const addContact = () => contact.post(route('leads.contacts.store', props.lead.id), { preserveScroll: true, onSuccess: () => { contact.reset(); adding.value = false; } });
const toggleContacted = (person) => router.put(route('leads.contacts.update', person.id), { contacted: !person.contacted_at }, { preserveScroll: true, preserveState: true });
const removeContact = (person) => confirm(`Remove ${person.name}?`) && router.delete(route('leads.contacts.destroy', person.id), { preserveScroll: true, preserveState: true });

const suggesting = ref(false);
const suggestion = ref(null);
const suggestMessage = ref('');
const suggest = async () => {
	suggesting.value = true;
	suggestMessage.value = '';
	suggestion.value = null;
	try {
		const { data } = await axios.post(route('leads.contacts.suggest', props.lead.id));
		suggestion.value = data.contact || null;
		suggestMessage.value = data.message || '';
	} catch (error) {
		suggestMessage.value = error.response?.data?.message || 'The search did not work this time. Try again later.';
	} finally {
		suggesting.value = false;
	}
};
const keepSuggestion = () => {
	Object.assign(contact, { ...suggestion.value, mutual: '', source: 'ai' });
	addContact();
	suggestion.value = null;
};
</script>

<template>
	<article class="bg-white border border-transparent rounded-card p-5 min-w-0">
		<div class="flex flex-wrap items-start gap-4">
			<CompanyLogo class="size-11 rounded-card text-base shrink-0" :name="lead.company_name" :src="lead.company?.logo" />
			<div class="min-w-0 flex-1">
				<div class="flex flex-wrap items-center gap-1.5 text-sm font-medium text-ink-700">
					<Link v-if="lead.company?.url" :href="lead.company.url" class="hover:text-blue-700">{{ lead.company_name }}</Link>
					<span v-else>{{ lead.company_name }}</span>
					<VerifiedBadge v-if="lead.company?.verified" compact />
					<span v-if="lead.company?.stage" class="rounded-md bg-paper-2 px-1.5 py-0.5 text-[11px] font-medium text-ink-700">{{ lead.company.stage === 'startup' ? 'Startup' : 'Established' }}</span>
				</div>
				<h3 class="mt-0.5 text-[20px] leading-[1.35] text-navy">{{ lead.title }}</h3>
				<p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-muted">
					<span v-if="lead.posted">{{ lead.posted }}</span>
					<span v-if="lead.extra?.location" class="inline-flex items-center gap-1"><MapPin class="size-3.5" aria-hidden="true" />{{ lead.extra.location }}</span>
					<span v-if="lead.extra?.pay">{{ lead.extra.pay }}</span>
					<span v-if="lead.external_ref">Tracker #{{ lead.external_ref }}</span>
				</p>
			</div>
			<a v-if="lead.url" :href="lead.open_url" target="_blank" rel="noopener noreferrer" class="cta cta-primary !text-[14px] !px-4 !py-2.5 shrink-0 max-sm:w-full max-sm:justify-center" @click="refreshSoon">
				Open posting <ExternalLink class="size-4" aria-hidden="true" />
			</a>
		</div>

		<div class="mt-4 flex flex-wrap items-center gap-2 text-[12.5px]">
			<span v-if="lead.eligibility" class="rounded-lg px-2.5 py-1 font-medium" :class="eligibility[lead.eligibility][1]">{{ eligibility[lead.eligibility][0] }}</span>
			<span v-if="lead.score" class="rounded-lg bg-paper-2 px-2.5 py-1 font-medium text-ink-700">Score {{ lead.score }}</span>
			<span v-for="reason in lead.reasons.filter((reason) => reason !== eligibility[lead.eligibility]?.[0])" :key="reason" class="rounded-lg bg-paper-2 px-2.5 py-1 text-ink-700">{{ reason }}</span>
		</div>
		<p v-if="lead.eligibility_quote" class="mt-2 text-[13px] text-muted leading-relaxed" dir="auto">“{{ lead.eligibility_quote }}”</p>

		<div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,170px)_minmax(0,170px)_1fr]">
			<label class="text-[12.5px] font-medium text-ink-700">Status
				<select class="fld mt-1 !py-2" :value="lead.status" @change="save({ status: $event.target.value })">
					<option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] }}</option>
				</select>
			</label>
			<label class="text-[12.5px] font-medium text-ink-700">Applied on
				<input v-model="appliedAt" type="date" class="fld mt-1 !py-2" @change="save({ applied_at: appliedAt || null })">
			</label>
			<label class="text-[12.5px] font-medium text-ink-700">Notes
				<textarea v-model="notes" rows="1" class="fld mt-1 !py-2 !min-h-[42px]" placeholder="Anything to remember" @blur="notes !== (lead.notes || '') && save({ notes })" />
			</label>
		</div>

		<details v-if="lead.urls?.length" class="mt-3">
			<summary class="text-[13px] font-medium text-blue-700 cursor-pointer">Other links ({{ lead.urls.length }})</summary>
			<ul class="mt-2 space-y-1 text-[13px]">
				<li v-for="url in lead.urls" :key="url" class="truncate"><a :href="url" target="_blank" rel="noopener noreferrer" class="text-ink-700 hover:text-blue-700">{{ url }}</a></li>
			</ul>
		</details>

		<section class="mt-4 rounded-card bg-paper-2 p-4" :aria-label="`People at ${lead.company_name}`">
			<div class="flex flex-wrap items-center justify-between gap-2">
				<h4 class="text-[13px] font-medium text-ink-700">People to reach out to</h4>
				<div class="flex gap-2">
					<button v-if="canSuggest" type="button" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-blue-700 disabled:opacity-60" :disabled="suggesting" @click="suggest">
						<Search class="size-3.5" aria-hidden="true" />{{ suggesting ? 'Searching…' : 'Suggest a contact' }}
					</button>
					<button type="button" class="text-[13px] font-medium text-blue-700" :aria-expanded="adding" @click="adding = !adding">{{ adding ? 'Cancel' : 'Add a contact' }}</button>
				</div>
			</div>
			<ul v-if="lead.contacts.length" class="mt-3 space-y-2.5">
				<li v-for="person in lead.contacts" :key="person.id" class="flex flex-wrap items-start gap-3 rounded-card bg-white p-3">
					<UserRound class="size-4 mt-0.5 text-muted shrink-0" aria-hidden="true" />
					<div class="min-w-0 flex-1 text-[13.5px]">
						<p class="font-medium">{{ person.name }}<span v-if="person.title" class="font-normal text-muted"> · {{ person.title }}</span></p>
						<p v-if="person.note || person.mutual" class="text-muted text-[13px] mt-0.5">{{ person.note }}<template v-if="person.mutual"> · Mutual: {{ person.mutual }}</template></p>
						<p class="mt-1 flex flex-wrap gap-3 text-[13px]">
							<a v-if="person.profile_url" :href="person.profile_url" target="_blank" rel="noopener noreferrer nofollow" class="font-medium text-blue-700">Profile</a>
							<a v-if="person.source_url" :href="person.source_url" target="_blank" rel="noopener noreferrer nofollow" class="text-ink-700 hover:text-blue-700">Where we found them</a>
							<span v-if="person.source === 'ai'" class="text-muted">Suggested by AI</span>
						</p>
					</div>
					<label class="inline-flex items-center gap-2 text-[14px] font-medium text-navy">
						<input type="checkbox" class="ck" :checked="!!person.contacted_at" @change="toggleContacted(person)">
						{{ person.contacted_at ? `Contacted ${person.contacted_at}` : 'Contacted' }}
					</label>
					<button type="button" class="icon-btn !size-8" :aria-label="`Remove ${person.name}`" @click="removeContact(person)"><Trash2 class="size-4" aria-hidden="true" /></button>
				</li>
			</ul>
			<p v-else-if="!adding && !suggestion" class="mt-2 text-[13px] text-muted">No one yet. Someone on the team can make your application stand out.</p>

			<div v-if="suggestion" class="mt-3 rounded-card bg-white p-3 text-[13.5px]">
				<p class="font-medium">{{ suggestion.name }}<span v-if="suggestion.title" class="font-normal text-muted"> · {{ suggestion.title }}</span></p>
				<p v-if="suggestion.note" class="text-muted mt-0.5">{{ suggestion.note }}</p>
				<p class="mt-1 flex flex-wrap gap-3 text-[13px]">
					<a v-if="suggestion.profile_url" :href="suggestion.profile_url" target="_blank" rel="noopener noreferrer nofollow" class="font-medium text-blue-700">Profile</a>
					<a v-if="suggestion.source_url" :href="suggestion.source_url" target="_blank" rel="noopener noreferrer nofollow" class="text-ink-700 hover:text-blue-700">Source</a>
				</p>
				<p class="mt-2 text-[12.5px] text-muted">Check the page before you keep this person.</p>
				<div class="mt-2 flex gap-2">
					<button type="button" class="cta cta-primary !text-[13px] !px-3 !py-2" @click="keepSuggestion">Keep contact</button>
					<button type="button" class="cta cta-secondary !text-[13px] !px-3 !py-2" @click="suggestion = null">Dismiss</button>
				</div>
			</div>
			<p v-if="suggestMessage" class="mt-2 text-[13px] text-muted" role="status">{{ suggestMessage }}</p>

			<form v-if="adding" class="mt-3 grid gap-2.5 sm:grid-cols-2" @submit.prevent="addContact">
				<label class="text-[12.5px] font-medium text-ink-700">Name<input v-model="contact.name" class="fld mt-1 !py-2" required maxlength="191"></label>
				<label class="text-[12.5px] font-medium text-ink-700">Job title<input v-model="contact.title" class="fld mt-1 !py-2" maxlength="191"></label>
				<label class="text-[12.5px] font-medium text-ink-700">Profile link<input v-model="contact.profile_url" type="url" class="fld mt-1 !py-2" placeholder="https://"></label>
				<label class="text-[12.5px] font-medium text-ink-700">Mutual connection<input v-model="contact.mutual" class="fld mt-1 !py-2" maxlength="191"></label>
				<label class="text-[12.5px] font-medium text-ink-700 sm:col-span-2">Note<input v-model="contact.note" class="fld mt-1 !py-2" maxlength="2000"></label>
				<p v-if="Object.keys(contact.errors).length" class="sm:col-span-2 text-[13px] text-red-600">{{ Object.values(contact.errors)[0] }}</p>
				<div class="sm:col-span-2"><button class="cta cta-primary !text-[13.5px] !px-4 !py-2.5" :disabled="contact.processing">Save contact</button></div>
			</form>
		</section>
	</article>
</template>
