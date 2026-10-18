<script setup>
import { FileText, ShieldCheck, UserRound } from '@lucide/vue';
import { computed, h, nextTick, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'settings' }, () => page),
});

const props = defineProps({
	settings: { type: Object, required: true },
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
});

const icons = {
	person: UserRound,
	shield: ShieldCheck,
};

const page = usePage();
const isStudent = computed(() => props.settings.userable_type === 'student');
const initials = computed(() =>
	(props.settings.name || '')
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);

const tabs = [
	{ key: 'account', label: 'Account', icon: icons.person },
	{ key: 'security', label: 'Security', icon: icons.shield },
];
const tab = ref('account');

const accountForm = useForm({
	name: props.settings.name,
	email: props.settings.email,
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
	accountForm.put(route('profile.update'), { preserveScroll: true });
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

const publicBusy = ref(false);
const setPublic = (value) =>
	router.put(route('profile.public.update'), { public: value }, {
		preserveScroll: true,
		onStart: () => (publicBusy.value = true),
		onFinish: () => (publicBusy.value = false),
	});
const publicText = computed(() => ({
	pending: 'Waiting for review. Your profile is still private.',
	approved: 'Your profile is public.',
	declined: 'Not approved. Update your profile and ask again.',
}[props.settings.public_status] || 'Your profile is private.'));

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
		<PageHeader title="Settings" description="Manage your profile, opportunity preferences, and security." />

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
				</button>
			</div>

			<!-- PANELS -->
			<div>
				<!-- ACCOUNT -->
				<section v-if="tab === 'account'">
					<div class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Profile photo</h3>
							<p class="text-[13px] text-muted mt-[3px]">Shown on your profile and applications.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<div class="flex items-center gap-[18px]">
								<div class="w-[72px] h-[72px] rounded-[20px] bg-gradient-to-br from-blue-300 to-blue-700 grid place-items-center text-white font-bold font-display text-[27px] shrink-0">{{ initials }}</div>
								<div>
									<b class="text-[14.5px] font-semibold block">{{ settings.name }}</b>
									<p class="text-[12.5px] text-muted my-[3px]">Initials are generated from your name.</p>
								</div>
							</div>
						</div>
					</div>

					<form class="bg-white border border-line rounded-[18px] mb-[18px]" @submit.prevent="saveAccount">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Personal details</h3>
							<p class="text-[13px] text-muted mt-[3px]">Your basic information.</p>
						</div>
						<div class="px-[22px] py-[22px]">
							<p v-if="hasAccountErrors" class="text-sm text-[#dc2626] font-semibold mb-4" role="alert">Please fix the fields below.</p>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-name">Full name</label>
									<input id="settings-name" v-model="accountForm.name" class="fld" type="text" autocomplete="name">
									<div v-if="accountForm.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.name }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-email">Email</label>
									<input id="settings-email" v-model="accountForm.email" class="fld" type="email" autocomplete="email">
									<div v-if="accountForm.errors.email" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.email }}</div>
								</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-phone">Phone</label>
									<input id="settings-phone" v-model="accountForm.phone_number" class="fld" type="tel" autocomplete="tel">
									<div v-if="accountForm.errors.phone_number" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.phone_number }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-linkedin">LinkedIn URL</label>
									<input id="settings-linkedin" v-model="accountForm.linkedin_profile_url" class="fld" type="url" placeholder="https://linkedin.com/in/…">
									<div v-if="accountForm.errors.linkedin_profile_url" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.linkedin_profile_url }}</div>
								</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-city">Location</label>
									<select id="settings-city" v-model="accountForm.city_id" class="fld">
										<option :value="null">Not selected</option>
										<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
									</select>
									<div v-if="accountForm.errors.city_id" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.city_id }}</div>
								</div>
								<div v-if="isStudent" class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-field">Field of work or study</label>
									<select id="settings-field" v-model="accountForm.field_id" class="fld">
										<option :value="null">Not selected</option>
										<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
									</select>
									<div v-if="accountForm.errors.field_id" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.field_id }}</div>
								</div>
								<div v-if="!isStudent" class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-website">Website</label>
									<input id="settings-website" v-model="accountForm.website" class="fld" type="url" placeholder="https://…">
									<div v-if="accountForm.errors.website" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.website }}</div>
								</div>
							</div>
							<div>
								<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="settings-about">About</label>
								<textarea id="settings-about" v-model="accountForm.about" class="fld" aria-describedby="settings-about-hint"></textarea>
								<div v-if="accountForm.errors.about" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.about }}</div>
								<div id="settings-about-hint" class="text-xs text-muted-2 mt-[7px]">Brief intro shown at the top of your profile.</div>
							</div>
							<div class="grid grid-cols-2 gap-4 mt-5 max-[560px]:grid-cols-1">
								<label class="text-sm font-semibold">Country code<input v-model="accountForm.country_code" class="fld mt-2" maxlength="2" placeholder="TN, FR, US..."><span v-if="accountForm.errors.country_code" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountForm.errors.country_code }}</span></label>
								<label class="text-sm font-semibold">City or location<input v-model="accountForm.location" class="fld mt-2" placeholder="Your location anywhere in the world"><span v-if="accountForm.errors.location" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountForm.errors.location }}</span></label>
							</div>
							<template v-if="isStudent">
								<h3 class="font-display text-lg font-semibold mt-7 mb-4">Your experience</h3>
								<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
									<label class="text-sm font-semibold">Years of experience<input v-model.number="accountForm.experience_years" class="fld mt-2" type="number" min="0" max="80"><span v-if="accountError('experience_years')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('experience_years') }}</span></label>
									<label class="text-sm font-semibold">Headline<input v-model="accountForm.headline" class="fld mt-2" maxlength="255" placeholder="Your role or area of interest"><span v-if="accountError('headline')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('headline') }}</span></label>
									<label class="text-sm font-semibold">Languages<input v-model="languagesText" class="fld mt-2" placeholder="Arabic, French, English"><span v-if="accountError('languages')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('languages') }}</span></label>
									<label class="text-sm font-semibold">Website or portfolio<input v-model="accountForm.website" class="fld mt-2" type="url" placeholder="https://…"><span v-if="accountError('website')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('website') }}</span></label>
								</div>
								<h3 class="font-display text-lg font-semibold mt-7 mb-2">Opportunity preferences</h3>
								<p class="text-sm text-muted mb-4">These preferences shape your dashboard. You can still browse every opportunity. Manage email alerts in Saved searches.</p>
								<p v-if="accountForm.errors.preferences" class="text-xs text-[#dc2626] mb-4">{{ accountForm.errors.preferences }}</p>
								<fieldset class="mb-5"><legend class="text-sm font-semibold mb-2">Opportunity types</legend><div class="flex gap-5"><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="internship"> Internships</label><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="job"> Jobs</label></div><p v-if="accountError('preferences.kinds')" class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.kinds') }}</p></fieldset>
								<fieldset class="mb-5"><legend class="text-sm font-semibold mb-2">Work arrangements</legend><div class="flex flex-wrap gap-5"><label v-for="mode in ['onsite', 'hybrid', 'remote']" :key="mode" class="flex gap-2 items-center capitalize"><input v-model="accountForm.preferences.work_types" type="checkbox" :value="mode"> {{ mode }}</label></div><p v-if="accountError('preferences.work_types')" class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.work_types') }}</p></fieldset>
								<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
									<label class="text-sm font-semibold">Target countries<input v-model="targetCountries" class="fld mt-2" placeholder="TN, FR, DE"><span class="text-xs font-normal text-muted">Comma-separated country codes. Leave empty to explore all locations.</span><span v-if="accountError('preferences.countries')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('preferences.countries') }}</span></label>
									<label class="text-sm font-semibold">Skills to match<input v-model="skillsText" class="fld mt-2" placeholder="Python, sales, design"><span class="text-xs font-normal text-muted">Comma-separated skills.</span><span v-if="accountError('preferences.skills')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('preferences.skills') }}</span></label>
									<label class="text-sm font-semibold">Fields of interest<select v-model="accountForm.preferences.fields" class="fld mt-2" multiple><option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option></select><span v-if="accountError('preferences.fields')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('preferences.fields') }}</span></label>
									<label class="text-sm font-semibold">Experience levels<select v-model="accountForm.preferences.seniority" class="fld mt-2" multiple><option v-for="level in ['entry','graduate','mid','senior','lead','executive']" :key="level" :value="level">{{ level }}</option></select><span v-if="accountError('preferences.seniority')" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ accountError('preferences.seniority') }}</span></label>
								</div>
								<label class="flex gap-2 items-start mt-5 text-sm"><input v-model="accountForm.preferences.include_unknown_eligibility" type="checkbox" class="mt-1"> Include remote roles whose eligibility from Tunisia is unknown. Check the employer's requirements before applying.</label>
								<p v-if="accountError('preferences.include_unknown_eligibility')" class="text-xs text-[#dc2626] mt-[7px]">{{ accountError('preferences.include_unknown_eligibility') }}</p>
							</template>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
							<span v-if="accountForm.recentlySuccessful" class="self-center text-[13.5px] font-semibold text-[#047857]">Saved.</span>
							<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="accountForm.processing">Save changes</button>
						</div>
					</form>

					<div v-if="isStudent" class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Resume</h3>
							<p class="text-[13px] text-muted mt-[3px]">A PDF of your resume. Only you can see it. We use it with your profile to draft cover letters.</p>
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
						</div>
					</div>

					<div v-if="isStudent" class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">Public profile</h3>
							<p class="text-[13px] text-muted mt-[3px]">Show your profile on Nextward's public candidate page so companies can find you. The Nextward team checks each profile first. Your email, phone number and resume are never shown.</p>
						</div>
						<div class="px-[22px] py-[22px] flex items-center justify-between gap-4 flex-wrap">
							<div class="min-w-0">
								<b class="text-[14.5px] font-semibold block" role="status">{{ publicText }}</b>
								<a v-if="settings.public_status" :href="settings.public_url" target="_blank" rel="noopener" class="text-[13.5px] font-semibold text-blue-700 hover:underline">{{ settings.public_status === 'approved' ? 'Open your public page' : 'Preview your public page' }}</a>
								<p v-else-if="!settings.email_verified" class="text-[13px] text-muted mt-1">Verify your email address first.</p>
							</div>
							<button v-if="['pending', 'approved'].includes(settings.public_status)" class="cbtn cbtn-secondary" type="button" :disabled="publicBusy" @click="setPublic(false)">Make it private</button>
							<button v-else class="cbtn bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60" type="button" :disabled="publicBusy || !settings.email_verified" @click="setPublic(true)">Ask to make it public</button>
						</div>
					</div>
				</section>

				<!-- SECURITY -->
				<section v-if="tab === 'security'">
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
