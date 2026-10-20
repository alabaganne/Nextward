<script setup>
import { ArrowRight, ChevronRight } from '@lucide/vue';
import { computed, h, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { sitePath } from '@/utils/url';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'opportunities' }, () => page) });
const props = defineProps({ fields: Array, cities: { type: Array, default: () => [] }, skills: { type: Array, default: () => [] }, opportunity: { type: Object, default: null } });
const MAX_SKILLS = 12;
const editing = computed(() => !!props.opportunity);
const role = computed(() => props.opportunity || {});
const formData = (role) => ({
	title: role.title || '', description: role.description || '', kind: role.kind || 'internship',
	field_id: role.field_id || null, city_id: role.city_id || null, country_code: role.country_code || '', location: role.location || '',
	closing_at: role.closing_at ? String(role.closing_at).slice(0, 10) : '', work_type: role.work_type || 'onsite',
	duration_weeks: role.duration_weeks || null, term: role.term || '', program_type: role.program_type || '',
	start_date: role.start_date ? String(role.start_date).slice(0, 10) : '', employment_types: role.employment_types || [],
	seniority: role.seniority || '', language: role.language || '',
	pay_min: role.pay_min == null ? null : Math.round(Number(role.pay_min)), pay_max: role.pay_max == null ? null : Math.round(Number(role.pay_max)), pay_currency: role.pay_currency || '', pay_unit: role.pay_unit || '',
	compensation_status: role.compensation_status || 'unspecified', remote_scope: role.remote_scope || '',
	applicant_scope: role.applicant_scope || 'unknown', relocation: role.relocation || 'unknown', visa_sponsorship: role.visa_sponsorship || 'unknown',
	eligible_countries: role.eligible_countries || [], excluded_countries: role.excluded_countries || [],
	status: role.status || 'open', publication_status: role.publication_status || 'published', attachment_files: [],
	remove_attachments: false, skills: (role.skills || []).map((skill) => skill.id),
});
const form = useForm(formData(role.value));
const eligibleCountries = ref(form.eligible_countries.join(', '));
const excludedCountries = ref(form.excluded_countries.join(', '));
watch(() => role.value.id, () => {
	form.defaults(formData(role.value));
	form.reset();
	eligibleCountries.value = form.eligible_countries.join(', ');
	excludedCountries.value = form.excluded_countries.join(', ');
});
const countries = (text) => [...new Set(text.split(',').map((part) => part.trim().toUpperCase()).filter(Boolean))];
const skillSearch = ref('');
const shownSkills = computed(() => {
	const term = skillSearch.value.trim().toLowerCase();
	return term ? props.skills.filter((skill) => form.skills.includes(skill.id) || skill.name.toLowerCase().includes(term)) : props.skills;
});
// File uploads drop empty arrays, so an empty string clears every skill.
const payload = (data) => ({ ...data, skills: data.skills.length ? data.skills : '' });
const submit = () => {
	form.eligible_countries = countries(eligibleCountries.value);
	form.excluded_countries = countries(excludedCountries.value);
	if (form.kind === 'job') { form.duration_weeks = null; form.term = ''; form.program_type = ''; }
	const options = { onSuccess: () => form.reset('remove_attachments') };
	if (editing.value) form.transform((data) => ({ ...payload(data), _method: 'put' })).post(route('opportunities.update', role.value.id), options);
	else form.transform(payload).post(route('opportunities.store'), options);
};
const employmentTypes = { FULL_TIME: 'Full-time', PART_TIME: 'Part-time', CONTRACTOR: 'Contract / freelance', TEMPORARY: 'Temporary', INTERN: 'Internship', VOLUNTEER: 'Volunteer', OTHER: 'Other' };
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<div class="flex items-center gap-2 text-sm text-muted mb-6"><Link :href="route('opportunities.index')">Opportunities</Link><ChevronRight :size="14" /><span>{{ editing ? 'Edit opportunity' : 'Post an opportunity' }}</span></div>
		<div class="mb-6"><span class="block font-display text-xs font-semibold tracking-widest uppercase text-blue-600 mb-2">Hire students and professionals</span><h1 class="font-display text-[32px] font-semibold">{{ editing ? 'Edit opportunity' : 'Post an opportunity' }}</h1><p class="text-muted mt-2">Share clear responsibilities, requirements, compensation, and where candidates can work.</p></div>
		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-7 space-y-5">
				<div v-if="Object.keys(form.errors).length" class="rounded-xl bg-red-50 text-red-700 p-4" role="alert"><p v-for="(error, key) in form.errors" :key="key" class="text-sm">{{ error }}</p></div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Opportunity type<select v-model="form.kind" class="fld mt-2"><option value="internship">Internship</option><option value="job">Job</option></select></label>
					<label class="text-sm font-semibold">Title<input v-model="form.title" class="fld mt-2" required maxlength="255" placeholder="e.g. Software engineer"></label>
					<label class="text-sm font-semibold">Field<select v-model="form.field_id" class="fld mt-2"><option :value="null">Not specified</option><option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option></select></label>
					<label class="text-sm font-semibold">Experience level<select v-model="form.seniority" class="fld mt-2"><option value="">Not specified</option><option v-for="level in ['entry', 'graduate', 'mid', 'senior', 'lead', 'executive']" :key="level" :value="level">{{ level }}</option></select></label>
				</div>
				<fieldset><legend class="text-sm font-semibold mb-2">Employment type</legend><div class="flex flex-wrap gap-x-6 gap-y-3"><label v-for="(label, value) in employmentTypes" :key="value" class="text-sm flex gap-2 items-center"><input v-model="form.employment_types" type="checkbox" :value="value"> {{ label }}</label></div></fieldset>
				<fieldset v-if="skills.length">
					<legend class="text-sm font-semibold">Skills</legend>
					<p class="text-xs text-muted mt-1 mb-3">Pick up to {{ MAX_SKILLS }} skills candidates should have. {{ form.skills.length }} of {{ MAX_SKILLS }} picked.</p>
					<input v-if="skills.length > 20" v-model="skillSearch" class="fld mb-3 max-w-sm" type="search" placeholder="Search skills" aria-label="Search skills">
					<div class="flex flex-wrap gap-2 max-h-[232px] overflow-y-auto">
						<label
							v-for="skill in shownSkills"
							:key="skill.id"
							class="inline-flex items-center text-[13px] font-semibold px-3 py-[7px] rounded-[9px] border cursor-pointer select-none transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-blue-500 has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed"
							:class="form.skills.includes(skill.id) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-line text-ink-700 hover:border-blue-300'"
						><input v-model="form.skills" type="checkbox" class="sr-only" :value="skill.id" :disabled="!form.skills.includes(skill.id) && form.skills.length >= MAX_SKILLS">{{ skill.name }}</label>
						<p v-if="!shownSkills.length" class="text-sm text-muted">No skills match.</p>
					</div>
				</fieldset>
				<h2 class="font-display text-lg font-semibold pt-3">Location and work arrangement</h2>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Work arrangement<select v-model="form.work_type" class="fld mt-2"><option value="onsite">On-site</option><option value="hybrid">Hybrid</option><option value="remote">Fully remote</option></select></label>
					<label class="text-sm font-semibold">Country code<input v-model="form.country_code" class="fld mt-2" maxlength="2" placeholder="FR, US, TN..."></label>
					<label class="text-sm font-semibold">Location<input v-model="form.location" class="fld mt-2" maxlength="255" placeholder="City, region, or office location"></label>
					<label class="text-sm font-semibold">City, optional<select v-model="form.city_id" class="fld mt-2"><option :value="null">Not specified</option><option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option></select></label>
					<label class="text-sm font-semibold">Eligible applicant countries<input v-model="eligibleCountries" class="fld mt-2" placeholder="FR, DE, US"><span class="text-xs font-normal text-muted">Country codes separated by commas. Leave empty if unspecified.</span></label>
					<label class="text-sm font-semibold">Excluded applicant countries<input v-model="excludedCountries" class="fld mt-2" placeholder="Country codes, if any"></label>
					<label v-if="form.work_type === 'remote'" class="text-sm font-semibold">Remote scope<input v-model="form.remote_scope" class="fld mt-2" maxlength="255" placeholder="e.g. Worldwide, or only specific countries"></label>
					<label class="text-sm font-semibold">Who can apply?<select v-model="form.applicant_scope" class="fld mt-2"><option value="unknown">Not specified</option><option value="anywhere">Anyone, from any country</option><option value="listed">Only people in the eligible countries</option><option value="local">Only people already allowed to work there</option></select></label>
					<label class="text-sm font-semibold">Relocation assistance<select v-model="form.relocation" class="fld mt-2"><option value="unknown">Not specified</option><option value="yes">Provided</option><option value="no">Not provided</option></select></label>
					<label class="text-sm font-semibold">Visa sponsorship<select v-model="form.visa_sponsorship" class="fld mt-2"><option value="unknown">Not specified</option><option value="yes">Provided</option><option value="no">Not provided</option></select></label>
				</div>
				<p class="text-sm text-muted">Only confirm who can apply, relocation, or sponsorship that your team can provide.</p>
				<h2 class="font-display text-lg font-semibold pt-3">Timing</h2>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Application deadline, optional<input v-model="form.closing_at" class="fld mt-2" type="date"></label>
					<label class="text-sm font-semibold">Start date, optional<input v-model="form.start_date" class="fld mt-2" type="date"></label>
					<template v-if="form.kind === 'internship'">
						<label class="text-sm font-semibold">Programme<select v-model="form.program_type" class="fld mt-2"><option value="">Not specified</option><option value="pfe">PFE</option><option value="summer">Summer internship</option><option value="apprenticeship">Apprenticeship</option><option value="graduate">Graduate programme</option><option value="other">Other internship</option></select></label>
						<label class="text-sm font-semibold">Duration in weeks<input v-model.number="form.duration_weeks" class="fld mt-2" type="number" min="1" max="104"></label>
						<label class="text-sm font-semibold">Term<input v-model="form.term" class="fld mt-2" maxlength="255" placeholder="e.g. Summer 2027"></label>
					</template>
				</div>
				<h2 class="font-display text-lg font-semibold pt-3">Compensation</h2>
				<p v-if="role.pay_amount != null && role.pay_min == null" class="text-sm text-muted">Legacy amount: {{ Math.round(Number(role.pay_amount)) }} per {{ role.pay_unit || 'unspecified period' }}. Currency was not recorded. Enter a confirmed range and currency to update it.</p>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Compensation status<select v-model="form.compensation_status" class="fld mt-2"><option value="unspecified">Not specified</option><option value="paid">Paid</option><option value="unpaid">Unpaid</option></select></label>
					<label class="text-sm font-semibold">Currency code<input v-model="form.pay_currency" class="fld mt-2" maxlength="3" placeholder="TND, EUR, USD"></label>
					<label class="text-sm font-semibold">Minimum<input v-model.number="form.pay_min" class="fld mt-2" type="number" min="0" step="1"></label>
					<label class="text-sm font-semibold">Maximum<input v-model.number="form.pay_max" class="fld mt-2" type="number" min="0" step="1"></label>
					<label class="text-sm font-semibold">Pay period<select v-model="form.pay_unit" class="fld mt-2"><option value="">Not specified</option><option v-for="unit in ['hour','day','week','month','year']" :key="unit" :value="unit">{{ unit }}</option></select></label>
				</div>
				<label class="block text-sm font-semibold">Description<textarea v-model="form.description" class="fld mt-2 !min-h-[220px]" rows="12" required placeholder="Responsibilities, requirements, benefits, and application instructions"></textarea></label>
				<label class="block text-sm font-semibold">Description language code<input v-model="form.language" class="fld mt-2 max-w-xs" maxlength="10" placeholder="en, fr, ar..."></label>
				<label class="block text-sm font-semibold">Attachments<input class="fld mt-2" type="file" multiple accept=".pdf,.doc,.docx,.txt" @change="form.attachment_files = Array.from($event.target.files || [])"><span class="text-xs font-normal text-muted">Public role documents. Up to 10 files, 10 MB each.</span></label>
				<div v-if="role.attachments?.length" class="rounded-xl border border-line-2 bg-paper-2 p-4">
					<p class="text-xs font-semibold text-muted mb-2">Current files</p>
					<ul class="text-sm space-y-1"><li v-for="file in role.attachments" :key="file.url"><a class="text-blue-700 font-semibold" :href="file.url" target="_blank" rel="noopener">{{ file.name }}</a></li></ul>
					<label class="mt-3 text-sm flex gap-2 items-center"><input v-model="form.remove_attachments" type="checkbox"> Remove current files</label>
					<p class="text-xs text-muted mt-1">New files replace the current ones.</p>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Publication<select v-model="form.publication_status" class="fld mt-2"><option value="published">Published</option><option value="draft">Draft</option></select></label>
					<label class="text-sm font-semibold">Availability<select v-model="form.status" class="fld mt-2"><option value="open">Open</option><option value="closed">Closed</option></select></label>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex justify-end gap-3"><Link class="cbtn border border-line" :href="editing && role.publication_status === 'published' ? sitePath(role.public_url) || route('internships.show', role.id) : route('dashboard')">Cancel</Link><button class="cbtn bg-blue-600 text-white" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : form.publication_status === 'draft' ? 'Save draft' : 'Publish opportunity' }} <ArrowRight :size="17" /></button></div>
		</form>
	</div>
</template>
