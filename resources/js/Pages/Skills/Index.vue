<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'skills' }, () => page),
});

const props = defineProps({
	skills: { type: Object, required: true },
});

const meta = computed(() => props.skills.meta ?? props.skills);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<div class="flex items-end justify-between flex-wrap gap-4 mb-6">
			<div>
				<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">Skills</h1>
				<p class="text-muted text-[15px] mt-1.5">The skill tags roles and profiles can use.</p>
			</div>
			<Link class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('skills.create')">Add skill</Link>
		</div>

		<div class="bg-white border border-line rounded-[18px] overflow-hidden">
			<div class="grid grid-cols-[1fr_160px_160px_90px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-bold [&_span:last-child]:text-right">
				<span>Skill</span><span>Roles</span><span>Candidates</span><span></span>
			</div>
			<div
				v-for="(skill, index) in skills.data"
				:key="skill.id"
				class="grid grid-cols-[1fr_160px_160px_90px] gap-4 items-center px-[22px] py-4 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
				:class="{ 'border-b border-line-2': index !== skills.data.length - 1 }"
			>
				<b class="text-[15px] font-semibold">{{ skill.name }}</b>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ skill.internships_count }}</span>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ skill.students_count }}</span>
				<Link class="justify-self-end text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] hover:bg-blue-100 transition-colors" :href="route('skills.edit', skill.id)">Edit</Link>
			</div>
			<div v-if="!skills.data.length" class="px-[22px] py-8 text-[14px] text-muted">No skills yet.</div>
		</div>

		<Pagination :meta="meta" />
	</div>
</template>
