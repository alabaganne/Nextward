<script setup>
import { ArrowRight, ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'internships' }, () => page),
});

const props = defineProps({
	fields: { type: Array, required: true },
	internship: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
	arrow: ArrowRight,
};

const editing = computed(() => !!props.internship);

const form = useForm({
	title: props.internship?.title || '',
	description: props.internship?.description || '',
	field_id: props.internship?.field_id || null,
	closing_at: props.internship?.closing_at ? String(props.internship.closing_at).slice(0, 10) : '',
	work_type: props.internship?.work_type || 'onsite',
	duration_weeks: props.internship?.duration_weeks || null,
	term: props.internship?.term || '',
	pay_amount: props.internship?.pay_amount || null,
	pay_unit: props.internship?.pay_unit || 'hour',
	attachment_files: [],
});

const submit = () => {
	if (editing.value) {
		form.transform((data) => ({ ...data, _method: 'put' })).post(route('internships.update', props.internship.id));
	} else {
		form.post(route('internships.store'));
	}
};

const pickFiles = (event) => {
	form.attachment_files = Array.from(event.target.files || []);
};

const workTypes = [
	{ value: 'remote', label: 'Remote' },
	{ value: 'hybrid', label: 'Hybrid' },
	{ value: 'onsite', label: 'On-site' },
];
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-7 pb-14 w-full max-w-[1080px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('internships.index')">Internships</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? `Edit — ${internship.title}` : 'Post a role' }}</span>
		</div>

		<div class="mb-6">
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">{{ editing ? 'Your role' : 'New role' }}</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">{{ editing ? 'Edit internship' : 'Post an internship' }}</h1>
			<p class="text-muted text-[15px] mt-1.5">Clear details attract stronger candidates — say what interns will build and what they'll learn.</p>
		</div>

		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<div class="mb-4">
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="title">Title</label>
					<input id="title" v-model="form.title" class="fld" type="text" placeholder="e.g. Backend Developer Intern (Node.js)">
					<div v-if="form.errors.title" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.title }}</div>
				</div>

				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="field">Field</label>
						<select id="field" v-model="form.field_id" class="fld">
							<option :value="null" disabled>Select field</option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
						<div v-if="form.errors.field_id" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.field_id }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="closing_at">Closing date</label>
						<input id="closing_at" v-model="form.closing_at" class="fld" type="date">
						<div v-if="form.errors.closing_at" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.closing_at }}</div>
					</div>
				</div>

				<div class="mb-4">
					<span class="block text-[13px] font-semibold mb-[9px] text-ink-700">Work mode</span>
					<div class="grid grid-cols-3 gap-3 max-[560px]:grid-cols-1">
						<button
							v-for="option in workTypes"
							:key="option.value"
							class="border-[1.5px] rounded-[12px] px-4 py-3 text-sm font-semibold transition-colors"
							:class="form.work_type === option.value ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-line bg-white text-ink-700 hover:border-blue-300'"
							type="button"
							:aria-pressed="form.work_type === option.value"
							@click="form.work_type = option.value"
						>{{ option.label }}</button>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="duration">Duration (weeks)</label>
						<input id="duration" v-model.number="form.duration_weeks" class="fld" type="number" min="1" max="104" placeholder="12">
						<div v-if="form.errors.duration_weeks" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.duration_weeks }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="term">Term</label>
						<input id="term" v-model="form.term" class="fld" type="text" placeholder="e.g. Summer 2026">
					</div>
				</div>

				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="pay_amount">Pay <span class="text-muted-2 font-medium">(optional)</span></label>
						<input id="pay_amount" v-model.number="form.pay_amount" class="fld" type="number" min="0" placeholder="25">
						<div v-if="form.errors.pay_amount" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.pay_amount }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="pay_unit">Per</label>
						<select id="pay_unit" v-model="form.pay_unit" class="fld">
							<option value="hour">Hour</option>
							<option value="month">Month</option>
						</select>
					</div>
				</div>

				<div class="mb-4">
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="description">Description</label>
					<textarea id="description" v-model="form.description" class="fld !min-h-[220px]" rows="12" placeholder="What will interns build? What are the responsibilities, requirements, and benefits?"></textarea>
					<div v-if="form.errors.description" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.description }}</div>
				</div>

				<div>
					<label class="block text-[13px] font-semibold mb-[7px] text-ink-700">Attachments</label>
					<p class="text-[13px] text-muted mb-2.5">Up to 10 files, 10&nbsp;MB each.</p>
					<input class="fld !py-[9px]" type="file" multiple @input="pickFiles">
					<div v-if="form.errors.attachment_files" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.attachment_files }}</div>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
				<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="editing ? route('internships.show', internship.id) : route('internships.index')">Cancel</Link>
				<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 [&_svg]:w-[17px] [&_svg]:h-[17px]" type="submit" :disabled="form.processing">
					{{ editing ? 'Save changes' : 'Publish role' }} <component :is="icons.arrow" aria-hidden="true" />
				</button>
			</div>
		</form>
	</div>
</template>
