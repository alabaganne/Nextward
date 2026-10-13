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
	<div class="px-10 max-[680px]:px-5 pt-7 pb-14 w-full max-w-[640px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('fields.index')">Fields</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? field.name : 'New field' }}</span>
		</div>

		<div class="mb-6">
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Catalog</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">{{ editing ? 'Edit field' : 'Add a field of study' }}</h1>
		</div>

		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="name">Name</label>
				<input id="name" v-model="form.name" class="fld" type="text" placeholder="e.g. Computer Science">
				<div v-if="form.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.name }}</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-[18px]">
				<button v-if="editing" class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c]" type="button" @click="destroy">Delete</button>
				<div class="flex-1"></div>
				<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="route('fields.index')">Cancel</Link>
				<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create field' }}</button>
			</div>
		</form>
	</div>
</template>
