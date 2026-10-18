<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'candidates' }, () => page),
});

const props = defineProps({
	candidate: { type: Object, default: null },
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
});

const icons = {
	chevron: ChevronRight,
};
const editing = computed(() => !!props.candidate);

const form = useForm({
	name: props.candidate?.name || '',
	email: props.candidate?.email || '',
	phone_number: props.candidate?.phone_number || '',
	city_id: props.candidate?.city_id ?? props.candidate?.city?.id ?? null,
	field_id: props.candidate?.field_id ?? props.candidate?.field?.id ?? null,
	about: props.candidate?.about || '',
	headline: props.candidate?.headline || '',
	experience_years: props.candidate?.experience_years ?? null,
	country_code: props.candidate?.country_code || '',
	location: props.candidate?.location || '',
});

const submit = () => editing.value ? form.put(route('candidates.update', props.candidate.id)) : form.post(route('candidates.store'));

const destroy = () => {
	if (confirm('Delete this candidate and everything attached to them? This cannot be undone.')) {
		router.delete(route('candidates.destroy', props.candidate.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[860px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('candidates.index')">Candidates</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? candidate.name : 'New candidate' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">{{ editing ? 'Edit candidate' : 'Add a candidate' }}</h1>
			<p v-if="!editing" class="text-sm text-muted mt-2">They can use Forgot password to set their password.</p>
		</div>

		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<p v-if="Object.keys(form.errors).length" class="text-sm text-[#dc2626] font-semibold mb-4" role="alert">Please fix the fields below.</p>
				<div class="grid grid-cols-2 gap-4 mb-5 max-[560px]:grid-cols-1">
					<label class="text-sm font-semibold">Headline<input v-model="form.headline" class="fld mt-2" maxlength="255"><span v-if="form.errors.headline" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ form.errors.headline }}</span></label>
					<label class="text-sm font-semibold">Country code<input v-model="form.country_code" class="fld mt-2" maxlength="2" placeholder="TN, FR, US..."><span v-if="form.errors.country_code" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ form.errors.country_code }}</span></label>
					<label class="text-sm font-semibold">City or location<input v-model="form.location" class="fld mt-2"><span v-if="form.errors.location" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ form.errors.location }}</span></label>
					<label class="text-sm font-semibold">Years of experience<input v-model.number="form.experience_years" class="fld mt-2" type="number" min="0" max="80"><span v-if="form.errors.experience_years" class="block text-xs font-normal text-[#dc2626] mt-[7px]">{{ form.errors.experience_years }}</span></label>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-name">Full name</label>
						<input id="candidate-name" v-model="form.name" class="fld" type="text">
						<div v-if="form.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.name }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-email">Email</label>
						<input id="candidate-email" v-model="form.email" class="fld" type="email">
						<div v-if="form.errors.email" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.email }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-phone">Phone</label>
						<input id="candidate-phone" v-model="form.phone_number" class="fld" type="tel">
						<div v-if="form.errors.phone_number" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.phone_number }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-city">City</label>
						<select id="candidate-city" v-model="form.city_id" class="fld">
							<option :value="null">-</option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
						<div v-if="form.errors.city_id" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.city_id }}</div>
					</div>
				</div>
				<div class="mb-4">
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-field">Field of work or study</label>
					<select id="candidate-field" v-model="form.field_id" class="fld">
						<option :value="null">-</option>
						<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
					</select>
					<div v-if="form.errors.field_id" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.field_id }}</div>
				</div>
				<div>
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="candidate-about">About</label>
					<textarea id="candidate-about" v-model="form.about" class="fld"></textarea>
					<div v-if="form.errors.about" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.about }}</div>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-[18px]">
				<button v-if="editing" class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c]" type="button" @click="destroy">Delete</button>
				<div class="flex-1"></div>
				<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="editing ? route('candidates.show', candidate.id) : route('candidates.index')">Cancel</Link>
				<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create candidate' }}</button>
			</div>
		</form>
	</div>
</template>
