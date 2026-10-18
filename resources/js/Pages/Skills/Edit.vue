<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'skills' }, () => page),
});

const props = defineProps({
	skill: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.skill);
const usage = computed(() => (props.skill?.opportunities_count || 0) + (props.skill?.candidates_count || 0));

const form = useForm({
	name: props.skill?.name || '',
});

const submit = () => {
	if (editing.value) {
		form.put(route('skills.update', props.skill.id));
	} else {
		form.post(route('skills.store'));
	}
};

const destroy = () => {
	const note = usage.value ? ` It will be removed from ${usage.value} ${usage.value === 1 ? 'role or profile' : 'roles and profiles'}.` : '';
	if (confirm(`Delete this skill?${note}`)) {
		router.delete(route('skills.destroy', props.skill.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[640px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('skills.index')">Skills</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? skill.name : 'New skill' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">{{ editing ? 'Edit skill' : 'Add a skill' }}</h1>
			<p v-if="editing" class="text-muted text-[15px] mt-1.5">Used by {{ skill.opportunities_count }} {{ skill.opportunities_count === 1 ? 'role' : 'roles' }} and {{ skill.candidates_count }} {{ skill.candidates_count === 1 ? 'candidate' : 'candidates' }}.</p>
		</div>

		<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="name">Name</label>
				<input id="name" v-model="form.name" class="fld" type="text" maxlength="80" placeholder="e.g. Laravel">
				<div v-if="form.errors.name" class="text-xs text-[#dc2626] mt-[7px]">{{ form.errors.name }}</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-[18px]">
				<button v-if="editing" class="cbtn !px-[18px] !py-2.5 bg-[#dc2626] text-white hover:bg-[#b91c1c]" type="button" @click="destroy">Delete</button>
				<div class="flex-1"></div>
				<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="route('skills.index')">Cancel</Link>
				<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create skill' }}</button>
			</div>
		</form>
	</div>
</template>
