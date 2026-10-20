<script setup>
import { FileText, ShieldCheck, SlidersHorizontal, UserRound } from '@lucide/vue';
import { computed, h, nextTick, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import HistoryList from '@/Components/Candidate/HistoryList.vue';
import axios from 'axios';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'settings' }, () => page),
});

const props = defineProps({
	settings: { type: Object, required: true },
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
});

const page = usePage();
const isCandidate = computed(() => props.settings.userable_type === 'candidate');

const tabs = computed(() => [
	{ key: 'profile', label: 'Profile', icon: UserRound },
	...(isCandidate.value ? [
		{ key: 'resume', label: 'Resume & experience', icon: FileText },
		{ key: 'preferences', label: 'Job preferences', icon: SlidersHorizontal },
	] : []),
	{ key: 'security', label: 'Security', icon: ShieldCheck },
]);
// ?tab= opens a tab directly, so other pages can link to it.
const askedTab = new URLSearchParams(page.url.split('?')[1] || '').get('tab');
const tab = ref(tabs.value.some((item) => item.key === askedTab) ? askedTab : 'profile');
watch(tab, (key) => {
	const url = new URL(window.location.href);
	if (key === 'profile') url.searchParams.delete('tab');
	else url.searchParams.set('tab', key);
	window.history.replaceState(window.history.state, '', url);
});

const accountForm = useForm({
	name: props.settings.name,
	phone_number: props.settings.phone_number,
	linkedin_profile_url: props.settings.linkedin_profile_url,
	city_id: props.settings.city_id,
	field_id: props.settings.field_id,
	about: props.settings.about,
	website: props.settings.website,
	headline: props.settings.headline || '',
	experience_years: props.settings.experience_years,
	country_code: props.settings.country_code || '',
	location: props.settings.location || '',
	languages: props.settings.languages || [],
	experience: props.settings.experience || [],
	education: props.settings.education || [],
	projects: props.settings.projects || [],
	preferences: {
		kinds: props.settings.preferences?.kinds || [],
		countries: props.settings.preferences?.countries || [],
		work_types: props.settings.preferences?.work_types || [],
		fields: props.settings.preferences?.fields || [],
		skills: props.settings.preferences?.skills || [],
		seniority: props.settings.preferences?.seniority || [],
		include_unknown_eligibility: props.settings.preferences?.include_unknown_eligibility || false,
	},
});

const targetCountries = ref(accountForm.preferences.countries.join(', '));
const skillsText = ref(accountForm.preferences.skills.join(', '));
const languagesText = ref(accountForm.languages.join(', '));
const splitValues = (value) => [...new Set(value.split(',').map((part) => part.trim()).filter(Boolean))];
const saveAccount = () => {
	accountForm.country_code = accountForm.country_code.toUpperCase();
	accountForm.preferences.countries = splitValues(targetCountries.value.toUpperCase());
	accountForm.preferences.skills = splitValues(skillsText.value);
	accountForm.languages = splitValues(languagesText.value);
	accountForm.put(route('profile.update'), { preserveScroll: true, onError: showFirstError });
};
// One form spans three tabs, so an error can sit on a tab that is not open.
const tabForError = (key) =>
	/^(experience|education|projects)\b/.test(key) ? 'resume'
		: key.startsWith('preferences') && !key.startsWith('preferences.skills') ? 'preferences'
			: 'profile';
const errorTabs = computed(() => [...new Set(Object.keys(accountForm.errors).map(tabForError))]);
// Errors can sit far above the Save button, so bring the first one into view once Inertia has restored the scroll.
const showFirstError = () => {
	if (errorTabs.value.length && !errorTabs.value.includes(tab.value)) tab.value = errorTabs.value[0];
	nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => {
		const error = document.querySelector('#settings-details [data-error]');
		error?.scrollIntoView({ behavior: 'smooth', block: 'center' });
		error?.parentElement.closest('label, div')?.querySelector('input, select, textarea')?.focus({ preventScroll: true });
	})));
};
// List fields come back as "languages.0" or "preferences.skills.2", so match on the prefix too.
const accountError = (key) =>
	Object.entries(accountForm.errors).find(([name]) => name === key || name.startsWith(`${key}.`))?.[1];
const hasAccountErrors = computed(() => Object.keys(accountForm.errors).length > 0);

const resumeForm = useForm({ resume: null });
const uploadResume = (event) => {
	resumeForm.resume = event.target.files[0] || null;
	event.target.value = '';
	if (resumeForm.resume) resumeForm.post(route('profile.resume.store'), { preserveScroll: true, forceFormData: true });
};
const removeResume = () => router.delete(route('profile.resume.destroy'), { preserveScroll: true });

const historyFields = {
	experience: [
		{ key: 'title', label: 'Job title' }, { key: 'company', label: 'Company' },
		{ key: 'location', label: 'Location' }, { key: 'current', label: 'I work here now', type: 'checkbox' },
		{ key: 'start', label: 'Start', placeholder: '2022-09' }, { key: 'end', label: 'End', placeholder: '2024-06' },
		{ key: 'summary', label: 'What you did', type: 'textarea', wide: true },
	],
	education: [
		{ key: 'school', label: 'School' }, { key: 'degree', label: 'Degree' },
		{ key: 'field', label: 'Field of study', wide: true },
		{ key: 'start', label: 'Start', placeholder: '2019' }, { key: 'end', label: 'End', placeholder: '2022' },
	],
	projects: [
		{ key: 'name', label: 'Project name' }, { key: 'url', label: 'Link', type: 'url', placeholder: 'https://…' },
		{ key: 'summary', label: 'What it is', type: 'textarea', wide: true },
	],
};

// Resume reading: suggestions are shown for review and only fill the form; Save changes stores them.
const reading = ref(false);
const readError = ref('');
const review = ref(null);
const appliedNote = ref(false);
const readResume = async () => {
	reading.value = true;
	readError.value = '';
	appliedNote.value = false;
	try {
		const { data } = await axios.post(route('profile.resume.read'));
		const s = data.suggestions;
		const join = (list) => (list || []).join(', ');
		const titles = (list, key) => (list || []).map((row) => row[key]).filter(Boolean).join('; ');
		const rows = [
			{ key: 'headline', label: 'Headline', now: accountForm.headline, next: s.headline },
			{ key: 'about', label: 'About', now: accountForm.about, next: s.about },
			{ key: 'experience_years', label: 'Years of experience', now: accountForm.experience_years ?? '', next: s.experience_years || '' },
			{ key: 'location', label: 'City or location', now: accountForm.location, next: s.location },
			{ key: 'country_code', label: 'Country code', now: accountForm.country_code, next: s.country_code },
			{ key: 'phone_number', label: 'Phone', now: accountForm.phone_number, next: s.phone_number },
			{ key: 'website', label: 'Website or portfolio', now: accountForm.website, next: s.website },
			{ key: 'linkedin_profile_url', label: 'LinkedIn URL', now: accountForm.linkedin_profile_url, next: s.linkedin_profile_url },
			{ key: 'languages', label: 'Languages', now: languagesText.value, next: join(s.languages), value: s.languages },
			{ key: 'skills', label: 'Skills', now: skillsText.value, next: join(s.skills), value: s.skills },
			{ key: 'experience', label: 'Experience', now: titles(accountForm.experience, 'title'), next: titles(s.experience, 'title'), value: s.experience },
			{ key: 'education', label: 'Education', now: titles(accountForm.education, 'school'), next: titles(s.education, 'school'), value: s.education },
			{ key: 'projects', label: 'Projects', now: titles(accountForm.projects, 'name'), next: titles(s.projects, 'name'), value: s.projects },
		]
			.map((row) => ({ ...row, now: String(row.now ?? ''), next: String(row.next ?? ''), value: row.value ?? s[row.key] }))
			.filter((row) => row.next && row.next !== row.now)
			.map((row) => ({ ...row, use: !row.now }));
		review.value = rows;
	} catch (error) {
		readError.value = error.response?.data?.message || 'We could not read your resume right now. Please try again in a minute.';
	} finally {
		reading.value = false;
	}
};
const applyReview = () => {
	for (const row of review.value.filter((item) => item.use)) {
		if (row.key === 'languages') languagesText.value = row.value.join(', ');
		else if (row.key === 'skills') skillsText.value = [...new Set([...splitValues(skillsText.value), ...row.value])].join(', ');
		else if (['experience', 'education', 'projects'].includes(row.key)) accountForm[row.key] = row.value.map((item) => ({ ...item }));
		else accountForm[row.key] = row.value;
	}
	review.value = null;
	appliedNote.value = true;
	nextTick(() => document.getElementById('settings-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
};

const emailForm = useForm({ email: '', password: '' });
const changeEmail = () =>
	emailForm.post(route('email.change'), {
		preserveScroll: true,
		preserveState: true,
		onSuccess: () => emailForm.reset(),
	});
const cancelEmailChange = () => router.delete(route('email.change.cancel'), { preserveScroll: true, preserveState: true });
const verificationSent = ref(false);
const resendVerification = () =>
	router.post(route('verification.send'), {}, { preserveScroll: true, preserveState: true, onSuccess: () => (verificationSent.value = true) });
const openEmailSettings = () => {
	tab.value = 'security';
	nextTick(() => {
		document.getElementById('settings-email')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
		document.getElementById('settings-new-email')?.focus({ preventScroll: true });
	});
};

const passwordForm = useForm({
	current_password: '',
	password: '',
	password_confirmation: '',
});

const savePassword = () =>
	passwordForm.put(route('password.change'), {
		preserveScroll: true,
		onSuccess: () => passwordForm.reset(),
	});

const deleteForm = useForm({ password: '' });
const confirmingDelete = ref(false);
const deletePassword = ref(null);
const deleteButton = ref(null);

const startDelete = () => {
	confirmingDelete.value = true;
	nextTick(() => deletePassword.value?.focus());
};
const cancelDelete = () => {
	confirmingDelete.value = false;
	deleteForm.reset();
	deleteForm.clearErrors();
	nextTick(() => deleteButton.value?.focus());
};
const deleteAccount = () =>
	deleteForm.delete(route('profile.destroy'), {
		preserveScroll: true,
		onError: () => deletePassword.value?.focus(),
		onFinish: () => deleteForm.reset('password'),
	});
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<PageHeader title="Settings" :description="isCandidate ? 'Manage your profile, resume, job preferences and security.' : 'Manage your profile and security.'" />

		<div class="grid grid-cols-[226px_1fr] gap-[22px] items-start max-[860px]:grid-cols-1">
			<!-- SUB NAV -->
			<div class="snav flex flex-col gap-[3px] sticky top-[92px] max-[860px]:flex-row max-[860px]:flex-wrap max-[860px]:static">
				<button
					v-for="item in tabs"
					:key="item.key"
					:class="{ on: tab === item.key }"
					type="button"
					:aria-pressed="tab === item.key"
					@click="tab = item.key"
				>
					<component :is="item.icon" aria-hidden="true" /> {{ item.label }}
					<span v-if="errorTabs.includes(item.key)" class="ml-auto w-2 h-2 rounded-full bg-[#dc2626]" aria-label="Has errors"></span>
				</button>
			</div>

			<!-- PANELS -->
			<div>
				<form v-if="tab !== 'security'" id="settings-details" class="scroll-mt-24" @submit.prevent="saveAccount">
					<p v-if="hasAccountErrors" class="text-sm text-[#dc2626] font-semibold mb-[18px] rounded-[12px] bg-[#fef2f2] px-4 py-3" role="alert">
						Please fix the fields marked in red<template v-if="errorTabs.some((key) => key !== tab)"> (also on
							<template v-for="(key, index) in errorTabs.filter((key) => key !== tab)" :key="key">{{ index ? ', ' : '' }}<button type="button" class="underline" @click="tab = key">{{ tabs.find((item) => item.key === key)?.label }}</button></template>)</template>.
					</p>
					<p v-else-if="appliedNote && accountForm.isDirty" class="text-sm text-blue-700 font-semibold mb-[18px] rounded-[12px] bg-blue-50 px-4 py-3" role="status">Details from your resume are filled in on the Profile and Resume tabs. Check them, then press Save changes.</p>

					<!-- PROFILE -->
					<template v-if="tab === 'profile'">
						<div class="bg-white border border-line rounded-[18px] mb-[18px]">
							<div class="px-[22px] py-[18px] border-b border-line-2">
								<h3 class="font-display text-[16.5px] font-semibold">Personal details</h3>
								<p class="text-[13px] text-muted mt-[3px]">Your name and how people can reach you.</p>
							</div>
							<div class="px-[22px] py-[22px] grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Full name</span>
									<input v-model="accountForm.name" class="fld" type="text" autocomplete="name">
									<span v-if="accountForm.errors.name" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.name }}</span>
								</label>
								<div>
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Email</span>
									<div class="fld flex items-center justify-between gap-3 bg-paper-3">
										<span class="truncate">{{ settings.email }}</span>
										<button type="button" class="text-[13px] font-semibold text-blue-700 hover:underline shrink-0" @click="openEmailSettings">Change</button>
									</div>
								</div>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Phone</span>
									<input v-model="accountForm.phone_number" class="fld" type="tel" autocomplete="tel">
									<span v-if="accountForm.errors.phone_number" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.phone_number }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">LinkedIn URL</span>
									<input v-model="accountForm.linkedin_profile_url" class="fld" type="url" placeholder="https://linkedin.com/in/…">
									<span v-if="accountForm.errors.linkedin_profile_url" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.linkedin_profile_url }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">{{ isCandidate ? 'Website or portfolio' : 'Website' }}</span>
									<input v-model="accountForm.website" class="fld" type="url" placeholder="https://…">
									<span v-if="accountError('website')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('website') }}</span>
								</label>
							</div>
						</div>

						<div class="bg-white border border-line rounded-[18px] mb-[18px]">
							<div class="px-[22px] py-[18px] border-b border-line-2">
								<h3 class="font-display text-[16.5px] font-semibold">About you</h3>
								<p class="text-[13px] text-muted mt-[3px]">{{ isCandidate ? 'What companies see first on your profile.' : 'A short intro shown on your profile.' }}</p>
							</div>
							<div class="px-[22px] py-[22px]">
								<div v-if="isCandidate" class="grid grid-cols-2 gap-4 mb-4 max-[560px]:grid-cols-1">
									<label class="block">
										<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Headline</span>
										<input v-model="accountForm.headline" class="fld" maxlength="255" placeholder="Your role or area of interest">
										<span v-if="accountError('headline')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('headline') }}</span>
									</label>
									<label class="block">
										<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Field of work or study</span>
										<select v-model="accountForm.field_id" class="fld">
											<option :value="null">Not selected</option>
											<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
										</select>
										<span v-if="accountForm.errors.field_id" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.field_id }}</span>
									</label>
									<label class="block">
										<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Years of experience</span>
										<input v-model.number="accountForm.experience_years" class="fld" type="number" min="0" max="80">
										<span v-if="accountError('experience_years')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('experience_years') }}</span>
									</label>
									<label class="block">
										<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Languages</span>
										<input v-model="languagesText" class="fld" placeholder="Arabic, French, English">
										<span v-if="accountError('languages')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('languages') }}</span>
									</label>
									<label class="block col-span-2 max-[560px]:col-span-1">
										<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Skills</span>
										<input v-model="skillsText" class="fld" placeholder="Python, React, Docker">
										<span class="block text-xs text-muted-2 mt-[7px]">Comma-separated. Shown on your profile and used to match roles.</span>
										<span v-if="accountError('preferences.skills')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.skills') }}</span>
									</label>
								</div>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">About</span>
									<textarea v-model="accountForm.about" class="fld"></textarea>
									<span v-if="accountForm.errors.about" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.about }}</span>
									<span class="block text-xs text-muted-2 mt-[7px]">Brief intro shown at the top of your profile.</span>
								</label>
							</div>
						</div>

						<div class="bg-white border border-line rounded-[18px] mb-[18px]">
							<div class="px-[22px] py-[18px] border-b border-line-2">
								<h3 class="font-display text-[16.5px] font-semibold">Location</h3>
								<p class="text-[13px] text-muted mt-[3px]">Where you are based.</p>
							</div>
							<div class="px-[22px] py-[22px] grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">City or location</span>
									<input v-model="accountForm.location" class="fld" placeholder="Your location anywhere in the world">
									<span v-if="accountForm.errors.location" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.location }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Country code</span>
									<input v-model="accountForm.country_code" class="fld" maxlength="2" placeholder="TN, FR, US...">
									<span v-if="accountForm.errors.country_code" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.country_code }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">City</span>
									<select v-model="accountForm.city_id" class="fld">
										<option :value="null">Not selected</option>
										<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
									</select>
									<span v-if="accountForm.errors.city_id" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.city_id }}</span>
								</label>
							</div>
						</div>
					</template>

					<!-- RESUME & EXPERIENCE -->
					<template v-if="tab === 'resume'">
						<div class="bg-white border border-line rounded-[18px] mb-[18px]">
							<div class="px-[22px] py-[18px] border-b border-line-2">
								<h3 class="font-display text-[16.5px] font-semibold">Resume</h3>
								<p class="text-[13px] text-muted mt-[3px]">A PDF of your resume. Only you can see it, unless you share it on your public profile. We can read it to fill in your profile, and we use it with your profile to tailor CVs and cover letters.</p>
							</div>
							<div class="px-[22px] py-[22px]">
								<div class="flex items-center justify-between gap-4 flex-wrap">
									<div class="flex items-center gap-3 min-w-0">
										<FileText class="w-5 h-5 text-muted shrink-0" aria-hidden="true" />
										<a v-if="settings.resume_name" :href="route('profile.resume.show')" class="text-[14px] font-semibold text-blue-700 hover:underline truncate">{{ settings.resume_name }}</a>
										<span v-else class="text-[13.5px] text-muted">No resume yet.</span>
									</div>
									<div class="flex gap-2.5">
										<label class="cbtn cbtn-secondary cursor-pointer" :class="{ 'opacity-60 pointer-events-none': resumeForm.processing }">
											{{ resumeForm.processing ? 'Uploading…' : settings.resume_name ? 'Replace PDF' : 'Upload PDF' }}
											<input type="file" accept="application/pdf" class="sr-only" @change="uploadResume">
										</label>
										<button v-if="settings.resume_name" class="cbtn cbtn-secondary" type="button" @click="removeResume">Remove</button>
									</div>
								</div>
								<div v-if="resumeForm.errors.resume" class="text-xs text-[#dc2626] mt-[10px]" role="alert">{{ resumeForm.errors.resume }}</div>
								<div v-if="settings.resume_name && !review" class="mt-4 rounded-[14px] bg-blue-50 px-4 py-3.5 flex items-center justify-between gap-3 flex-wrap">
									<p class="text-[13.5px] text-ink-700">Save time: we read your resume and suggest profile details. You check them before anything is saved.</p>
									<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60" type="button" :disabled="reading" @click="readResume">{{ reading ? 'Reading your resume…' : 'Fill my profile from this resume' }}</button>
								</div>
								<p v-if="readError" class="text-xs text-[#dc2626] mt-[10px]" role="alert">{{ readError }}</p>
								<div v-if="review" class="mt-4">
									<h4 class="font-display text-[15px] font-semibold">From your resume</h4>
									<p v-if="!review.length" class="text-[13.5px] text-muted mt-1">Your profile already has everything we found.</p>
									<template v-else>
										<p class="text-[13px] text-muted mt-1 mb-3">Tick what you want to use. It fills the form; nothing is saved until you press Save changes.</p>
										<ul class="flex flex-col gap-2">
											<li v-for="row in review" :key="row.key">
												<label class="flex gap-3 items-start rounded-[12px] bg-paper-3 px-3.5 py-3 cursor-pointer">
													<input v-model="row.use" type="checkbox" class="mt-1">
													<span class="min-w-0 text-[13.5px]">
														<b class="font-semibold block">{{ row.label }}</b>
														<span class="block text-ink-700 whitespace-pre-line break-words">{{ row.next }}</span>
														<span v-if="row.now" class="block text-muted mt-1 break-words">Now: {{ row.now }}</span>
													</span>
												</label>
											</li>
										</ul>
									</template>
									<div class="flex gap-2.5 mt-3">
										<button v-if="review.length" class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="button" @click="applyReview">Use selected</button>
										<button class="cbtn cbtn-secondary" type="button" @click="review = null">Close</button>
									</div>
								</div>
							</div>
						</div>

						<div class="bg-white border border-line rounded-[18px] mb-[18px] px-[22px] pt-1 pb-[22px]">
							<HistoryList v-model="accountForm.experience" name="experience" title="Experience" add-label="Add a job" :fields="historyFields.experience" :errors="accountForm.errors" />
						</div>
						<div class="bg-white border border-line rounded-[18px] mb-[18px] px-[22px] pt-1 pb-[22px]">
							<HistoryList v-model="accountForm.education" name="education" title="Education" add-label="Add a school" :fields="historyFields.education" :errors="accountForm.errors" />
						</div>
						<div class="bg-white border border-line rounded-[18px] mb-[18px] px-[22px] pt-1 pb-[22px]">
							<HistoryList v-model="accountForm.projects" name="projects" title="Projects" add-label="Add a project" :fields="historyFields.projects" :errors="accountForm.errors" />
						</div>
					</template>

					<!-- JOB PREFERENCES -->
					<div v-if="tab === 'preferences'" class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Job preferences</h3>
							<p class="text-[13px] text-muted mt-[3px]">These shape your dashboard. You can still browse every opportunity. Manage email alerts in Saved searches.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<p v-if="accountForm.errors.preferences" data-error class="text-xs text-[#dc2626] mb-4">{{ accountForm.errors.preferences }}</p>
							<fieldset class="mb-5"><legend class="text-[13px] font-semibold mb-2 text-ink-700">Opportunity types</legend><div class="flex gap-5 text-sm"><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="internship"> Internships</label><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="job"> Jobs</label></div><p v-if="accountError('preferences.kinds')" data-error class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.kinds') }}</p></fieldset>
							<fieldset class="mb-5"><legend class="text-[13px] font-semibold mb-2 text-ink-700">Work arrangements</legend><div class="flex flex-wrap gap-5 text-sm"><label v-for="mode in ['onsite', 'hybrid', 'remote']" :key="mode" class="flex gap-2 items-center capitalize"><input v-model="accountForm.preferences.work_types" type="checkbox" :value="mode"> {{ mode }}</label></div><p v-if="accountError('preferences.work_types')" data-error class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.work_types') }}</p></fieldset>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block col-span-2 max-[560px]:col-span-1">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Target countries</span>
									<input v-model="targetCountries" class="fld" placeholder="TN, FR, DE">
									<span class="block text-xs text-muted-2 mt-[7px]">Comma-separated country codes. Leave empty to explore all locations.</span>
									<span v-if="accountError('preferences.countries')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.countries') }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Fields of interest</span>
									<select v-model="accountForm.preferences.fields" class="fld" multiple><option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option></select>
									<span v-if="accountError('preferences.fields')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.fields') }}</span>
								</label>
								<label class="block">
									<span class="block text-[13px] font-semibold mb-[7px] text-ink-700">Experience levels</span>
									<select v-model="accountForm.preferences.seniority" class="fld capitalize" multiple><option v-for="level in ['entry','graduate','mid','senior','lead','executive']" :key="level" :value="level">{{ level }}</option></select>
									<span v-if="accountError('preferences.seniority')" data-error class="block text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.seniority') }}</span>
								</label>
							</div>
							<label class="flex gap-2 items-start mt-5 text-sm"><input v-model="accountForm.preferences.include_unknown_eligibility" type="checkbox" class="mt-1"> Include remote roles whose eligibility from Tunisia is unknown. Check the employer's requirements before applying.</label>
							<p v-if="accountError('preferences.include_unknown_eligibility')" data-error class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.include_unknown_eligibility') }}</p>
						</div>
					</div>

					<div class="sticky bottom-4 z-10 bg-white border border-line rounded-[18px] px-[22px] py-4 flex justify-end items-center gap-2.5 flex-wrap">
						<span v-if="hasAccountErrors" class="text-[13.5px] font-semibold text-[#dc2626]">Not saved. Fix the fields marked in red.</span>
						<span v-else-if="accountForm.isDirty" class="text-[13.5px] text-muted">You have unsaved changes.</span>
						<span v-else-if="accountForm.recentlySuccessful" class="text-[13.5px] font-semibold text-[#047857]">Saved.</span>
						<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60" type="submit" :disabled="accountForm.processing">{{ accountForm.processing ? 'Saving…' : 'Save changes' }}</button>
					</div>
				</form>

				<!-- SECURITY -->
				<section v-if="tab === 'security'">
					<form id="settings-email" class="bg-white border border-line rounded-[18px] mb-[18px] scroll-mt-24" @submit.prevent="changeEmail">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Email address</h3>
							<p class="text-[13px] text-muted mt-[3px]">You sign in with this email, and we send your alerts and messages to it.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<div class="flex items-center gap-2.5 flex-wrap">
								<b class="text-[14.5px] font-semibold break-all">{{ settings.email }}</b>
								<span v-if="settings.email_verified" class="text-xs font-semibold px-[11px] py-[5px] rounded-full bg-[#dcfce7] text-[#047857]">Verified</span>
								<span v-else class="text-xs font-semibold px-[11px] py-[5px] rounded-full bg-[#fef3c7] text-[#b45309]">Not verified</span>
								<span v-if="!settings.email_verified && verificationSent" class="text-[13px] font-semibold text-[#047857]" role="status">Link sent. Check your inbox.</span>
								<button v-else-if="!settings.email_verified" type="button" class="text-[13px] font-semibold text-blue-700 hover:underline" @click="resendVerification">Send the verification link again</button>
							</div>
							<div v-if="settings.pending_email" class="mt-4 rounded-[14px] bg-blue-50 px-4 py-3.5 flex items-center justify-between gap-3 flex-wrap" role="status">
								<p class="text-[13.5px] text-ink-700 min-w-0">We sent a link to <b class="break-all">{{ settings.email }}</b> to confirm the change to <b class="break-all">{{ settings.pending_email }}</b>. Open it within 60 minutes. Nothing changes until you do.</p>
								<button type="button" class="cbtn cbtn-secondary !px-3.5 !py-2 !text-[13.5px]" @click="cancelEmailChange">Cancel the change</button>
							</div>
							<p class="text-[13px] text-muted mt-5 mb-4">{{ settings.email_verified ? 'To change it, we first send a link to your current email so you can confirm. Then you verify the new address.' : 'Your email is not verified yet, so you can correct it here. We then send a verification link to the new address.' }}</p>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div>
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-new-email">New email</label>
									<input id="settings-new-email" v-model="emailForm.email" class="fld" type="email" autocomplete="email" required>
									<div v-if="emailForm.errors.email" class="text-xs text-[#dc2626] mt-[7px]">{{ emailForm.errors.email }}</div>
								</div>
								<div>
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-email-password">Current password</label>
									<input id="settings-email-password" v-model="emailForm.password" class="fld" type="password" autocomplete="current-password" required>
									<div v-if="emailForm.errors.password" class="text-xs text-[#dc2626] mt-[7px]">{{ emailForm.errors.password }}</div>
								</div>
							</div>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
							<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60" type="submit" :disabled="emailForm.processing">{{ emailForm.processing ? 'Sending…' : settings.email_verified ? 'Send confirmation link' : 'Change email' }}</button>
						</div>
					</form>

					<form class="bg-white border border-line rounded-[18px] mb-[18px]" @submit.prevent="savePassword">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Password</h3>
							<p class="text-[13px] text-muted mt-[3px]">Use a strong password you don't reuse elsewhere.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<div class="mb-4">
								<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="current-password">Current password</label>
								<input id="current-password" v-model="passwordForm.current_password" class="fld" type="password" placeholder="••••••••" autocomplete="current-password">
								<div v-if="passwordForm.errors.current_password" class="text-xs text-[#dc2626] mt-[7px]">{{ passwordForm.errors.current_password }}</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="new-password">New password</label>
									<input id="new-password" v-model="passwordForm.password" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
									<div v-if="passwordForm.errors.password" class="text-xs text-[#dc2626] mt-[7px]">{{ passwordForm.errors.password }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="confirm-password">Confirm new password</label>
									<input id="confirm-password" v-model="passwordForm.password_confirmation" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
								</div>
							</div>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
							<span v-if="passwordForm.recentlySuccessful" class="self-center text-[13.5px] font-semibold text-[#047857]">Password updated.</span>
							<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="passwordForm.processing">Update password</button>
						</div>
					</form>

					<div class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold text-[#b91c1c]">Danger zone</h3>
							<p class="text-[13px] text-muted mt-[3px]">Irreversible actions. Please be certain.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<div class="flex items-center justify-between gap-[18px]">
								<div>
									<b class="text-[14.5px] font-semibold block">Delete account</b>
									<span class="text-[13px] text-muted block mt-0.5">Permanently remove your profile, applications, and messages.</span>
								</div>
								<button v-if="!confirmingDelete" ref="deleteButton" class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c] whitespace-nowrap" type="button" @click="startDelete">
									Delete account
								</button>
							</div>
							<form v-if="confirmingDelete" class="mt-[18px] rounded-[14px] border border-[#fecaca] bg-[#fef2f2] p-[18px]" @submit.prevent="deleteAccount" @keydown.esc="cancelDelete">
								<p class="text-[13.5px] text-[#991b1b] font-semibold">This can't be undone. Your profile, applications, and messages will be deleted for good.</p>
								<label class="block text-[13px] font-semibold mt-4 mb-[7px] text-ink-700" for="delete-password">Enter your password to confirm</label>
								<input
									id="delete-password"
									ref="deletePassword"
									v-model="deleteForm.password"
									class="fld max-w-[360px]"
									type="password"
									autocomplete="current-password"
									required
									:aria-invalid="!!deleteForm.errors.password"
									:aria-describedby="deleteForm.errors.password ? 'delete-password-error' : undefined"
								>
								<div v-if="deleteForm.errors.password" id="delete-password-error" class="text-xs text-[#dc2626] mt-[7px]" role="alert">{{ deleteForm.errors.password }}</div>
								<div class="flex flex-wrap gap-2.5 mt-4">
									<button class="cbtn cbtn-secondary !px-[18px] !py-2.5" type="button" @click="cancelDelete">Cancel</button>
									<button class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c] disabled:opacity-60" type="submit" :disabled="deleteForm.processing || !deleteForm.password">
										{{ deleteForm.processing ? 'Deleting…' : 'Delete account' }}
									</button>
								</div>
							</form>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
