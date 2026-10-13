<script setup>
import { ShieldCheck, UserRound } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

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
});

const saveAccount = () => accountForm.put(route('profile.update'), { preserveScroll: true });

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

const deleteAccount = () => {
	if (!confirmingDelete.value) {
		confirmingDelete.value = true;
		setTimeout(() => (confirmingDelete.value = false), 4000);
		return;
	}
	const password = prompt('Enter your password to permanently delete your account:');
	if (!password) return;
	deleteForm.password = password;
	deleteForm.delete(route('profile.destroy'));
};
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-[30px] pb-16 w-full max-w-[1080px] mx-auto">
		<div class="mb-6">
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Account</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Settings</h1>
			<p class="text-muted text-[15px] mt-1.5">Manage your account details and security.</p>
		</div>

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
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Full name</label>
									<input v-model="accountForm.name" class="fld" type="text">
									<div v-if="accountForm.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.name }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Email</label>
									<input v-model="accountForm.email" class="fld" type="email">
									<div v-if="accountForm.errors.email" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.email }}</div>
								</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Phone</label>
									<input v-model="accountForm.phone_number" class="fld" type="tel">
									<div v-if="accountForm.errors.phone_number" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.phone_number }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">LinkedIn URL</label>
									<input v-model="accountForm.linkedin_profile_url" class="fld" type="url" placeholder="https://linkedin.com/in/…">
									<div v-if="accountForm.errors.linkedin_profile_url" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.linkedin_profile_url }}</div>
								</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Location</label>
									<select v-model="accountForm.city_id" class="fld">
										<option :value="null">—</option>
										<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
									</select>
								</div>
								<div v-if="isStudent" class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Field of study</label>
									<select v-model="accountForm.field_id" class="fld">
										<option :value="null">—</option>
										<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
									</select>
								</div>
								<div v-else class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Website</label>
									<input v-model="accountForm.website" class="fld" type="url" placeholder="https://…">
									<div v-if="accountForm.errors.website" class="text-xs text-[#dc2626] mt-[7px]">{{ accountForm.errors.website }}</div>
								</div>
							</div>
							<div>
								<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">About</label>
								<textarea v-model="accountForm.about" class="fld"></textarea>
								<div class="text-xs text-muted-2 mt-[7px]">Brief intro shown at the top of your profile.</div>
							</div>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
							<span v-if="accountForm.recentlySuccessful" class="self-center text-[13.5px] font-semibold text-[#047857]">Saved.</span>
							<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="accountForm.processing">Save changes</button>
						</div>
					</form>
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
								<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Current password</label>
								<input v-model="passwordForm.current_password" class="fld" type="password" placeholder="••••••••" autocomplete="current-password">
								<div v-if="passwordForm.errors.current_password" class="text-xs text-[#dc2626] mt-[7px]">{{ passwordForm.errors.current_password }}</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">New password</label>
									<input v-model="passwordForm.password" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
									<div v-if="passwordForm.errors.password" class="text-xs text-[#dc2626] mt-[7px]">{{ passwordForm.errors.password }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Confirm new password</label>
									<input v-model="passwordForm.password_confirmation" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
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
								<button class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c] whitespace-nowrap" type="button" @click="deleteAccount">
									{{ confirmingDelete ? 'Click again to confirm' : 'Delete account' }}
								</button>
							</div>
							<div v-if="deleteForm.errors.password" class="text-xs text-[#dc2626] mt-[10px]">{{ deleteForm.errors.password }}</div>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
