<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'fields' }, () => page),
});

const props = defineProps({
	field: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.field);

const form = useForm({
	name: props.field?.name || '',
});

const submit = () => {
	if (editing.value) {
		form.put(route('fields.update', props.field.id));
	} else {
		form.post(route('fields.store'));
	}
};

const destroy = () => {
	if (confirm('Delete this field? Internships and students referencing it may break.')) {
		router.delete(route('fields.destroy', props.field.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[640px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('fields.index')">Fields</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? field.name : 'New field' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? 'Edit field' : 'Add a field of study' }}</h1>
		</div>

		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<label class="block text-[14px] font-medium mb-2 text-navy" for="name">Name</label>
				<input id="name" v-model="form.name" class="fld" type="text" placeholder="e.g. Computer Science">
				<div v-if="form.errors.name" class="text-xs text-red-600 mt-[7px]">{{ form.errors.name }}</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-card">
				<button v-if="editing" class="cta !px-[18px] !py-2.5 cta-danger" type="button" @click="destroy">Delete</button>
				<div class="flex-1"></div>
				<Link class="cta cta-secondary" :href="route('fields.index')">Cancel</Link>
				<button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create field' }}</button>
			</div>
		</form>
	</div>
</template>
