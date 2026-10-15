<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'students' }, () => page),
});

const props = defineProps({
	students: { type: Object, required: true },
});

const initialsFor = (name) =>
	(name || '')
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase();

const meta = computed(() => props.students.meta ?? props.students);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
			<div>
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">Candidates</h1>
			<p class="text-muted text-[15px] mt-1.5">Every candidate profile on Nextward.</p>
			</div>
			<Link class="cbtn bg-blue-600 text-white hover:bg-blue-700" :href="route('students.create')">Add candidate</Link>
		</div>

		<div class="grid grid-cols-3 gap-[18px] max-[1180px]:grid-cols-2 max-[760px]:grid-cols-1">
			<article
				v-for="student in students.data"
				:key="student.id"
				class="bg-white border border-line rounded-[18px] flex flex-col transition-all hover:border-blue-300 hover:-translate-y-[3px]"
			>
				<div class="px-6 py-8 flex flex-col items-center text-center">
					<div class="w-[72px] h-[72px] rounded-[20px] bg-gradient-to-br from-blue-300 to-blue-700 grid place-items-center text-white font-bold font-display text-[26px]">{{ initialsFor(student.name) }}</div>
					<h3 class="font-display text-[18px] font-semibold mt-4 leading-[1.2]">{{ student.name }}</h3>
					<span class="text-[13px] text-muted mt-1">{{ [student.field?.name, student.career_stage || 'candidate'].filter(Boolean).join(' · ') }}</span>
				</div>
				<div class="flex gap-2.5 px-6 pb-6 mt-auto">
					<Link class="flex-1 justify-center h-[42px] inline-flex items-center font-semibold text-[15px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('students.show', student.id)">Profile</Link>
					<Link class="flex-1 justify-center h-[42px] inline-flex items-center font-semibold text-[15px] rounded-[12px] bg-paper-2 border border-line text-ink-700 hover:border-blue-300 hover:text-blue-700 transition-colors" :href="route('messages.show', student.user_id)">Message</Link>
				</div>
			</article>
		</div>

		<div v-if="!students.data.length" class="text-center py-[60px] text-muted">No candidates found.</div>

		<Pagination :meta="meta" />
	</div>
</template>
