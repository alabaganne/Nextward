<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h, onBeforeUnmount, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'companies' }, () => page),
});

const props = defineProps({
	company: { type: Object, default: null },
	cities: { type: Array, default: () => [] },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.company);
const page = usePage();

const form = useForm({
	name: props.company?.name || '',
	email: props.company?.email || '',
	phone_number: props.company?.phone_number || '',
	city_id: props.company?.city_id ?? props.company?.city?.id ?? null,
	website: props.company?.website || '',
	about: props.company?.about || '',
	country_code: props.company?.country_code || '',
	location: props.company?.location || '',
	logo: null,
});

// Imported employers have no account to hold a logo.
const canUploadLogo = computed(() => !editing.value || !!props.company.user_id);
const logoPreview = ref(props.company?.image || null);
const dropPreview = () => {
	if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value);
};
const pickLogo = (event) => {
	const file = event.target.files?.[0] || null;
	dropPreview();
	form.logo = file;
	logoPreview.value = file ? URL.createObjectURL(file) : props.company?.image || null;
};
onBeforeUnmount(dropPreview);

const submit = () => {
	if (editing.value) {
		form.transform((data) => ({ ...data, _method: 'put' })).post(route('companies.update', props.company.id));
	} else {
		form.post(route('companies.store'));
	}
};

const destroy = () => {
	if (confirm('Delete this company and all of its opportunities? This cannot be undone.')) {
		router.delete(route('companies.destroy', props.company.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[860px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('app.companies.index')">Companies</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? company.name : 'New company' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">{{ editing ? 'Edit company' : 'Add a company' }}</h1>
			<p class="text-muted text-[15px] mt-1.5">This is what candidates see when they evaluate your roles.</p>
			<p v-if="!editing" class="text-sm text-muted mt-2">We will email them a link to choose their password.</p>
		</div>

		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<div v-if="canUploadLogo" class="flex items-center gap-4 mb-6 pb-6 border-b border-line-2">
					<img v-if="logoPreview" :src="logoPreview" alt="" class="size-16 rounded-[14px] border border-line object-contain bg-white shrink-0">
					<CompanyLogo v-else class="size-16 rounded-[14px] text-[22px]" :name="form.name || 'Company'" />
					<div class="min-w-0">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="logo">Logo</label>
						<input id="logo" class="block w-full text-sm text-muted file:mr-3 file:rounded-[10px] file:border file:border-line file:bg-white file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-ink-700 hover:file:border-blue-300 file:cursor-pointer" type="file" accept="image/*" @change="pickLogo">
						<p class="text-xs text-muted mt-1.5">An image up to 2 MB. A new file replaces the current logo.</p>
						<div v-if="form.errors.logo" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.logo }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="name">Company name</label>
						<input id="name" v-model="form.name" class="fld" type="text">
						<div v-if="form.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.name }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="email">Email</label>
						<input id="email" v-model="form.email" class="fld" type="email" placeholder="careers@example.com">
						<div v-if="form.errors.email" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.email }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="country_code">Country code</label>
						<input id="country_code" v-model="form.country_code" class="fld" maxlength="2" placeholder="TN, FR, US...">
						<div v-if="form.errors.country_code" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.country_code }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="location">City or location</label>
						<input id="location" v-model="form.location" class="fld">
						<div v-if="form.errors.location" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.location }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="phone_number">Phone</label>
						<input id="phone_number" v-model="form.phone_number" class="fld" type="tel">
						<div v-if="form.errors.phone_number" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.phone_number }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="city_id">City</label>
						<select id="city_id" v-model="form.city_id" class="fld">
							<option :value="null">Not specified</option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
						<div v-if="form.errors.city_id" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.city_id }}</div>
					</div>
				</div>
				<div class="mb-4">
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="website">Website</label>
					<input id="website" v-model="form.website" class="fld" type="url" placeholder="https://example.com">
					<div v-if="form.errors.website" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.website }}</div>
				</div>
				<div>
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="about">About</label>
					<textarea id="about" v-model="form.about" class="fld !min-h-[140px]" rows="8" placeholder="What do you build, and what will interns work on?"></textarea>
					<div v-if="form.errors.about" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.about }}</div>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-[18px]">
				<button v-if="editing && page.props.auth?.user?.is_admin" class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c]" type="button" @click="destroy">Delete</button>
				<div class="flex-1"></div>
				<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="editing ? route('companies.show', company.id) : route('app.companies.index')">Cancel</Link>
				<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create company' }}</button>
			</div>
		</form>
	</div>
</template>
