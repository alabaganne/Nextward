<script setup>
import { h } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'skills' }, () => page),
});

defineProps({
	skills: { type: Array, default: () => [] },
});
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-[34px] pb-14 w-full max-w-[1080px] mx-auto">
		<div class="mb-6">
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Catalog</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Skills</h1>
			<p class="text-muted text-[15px] mt-1.5">The skill tags internships and profiles can use.</p>
		</div>

		<div class="bg-white border border-line rounded-[18px] p-[22px]">
			<div v-if="skills.length" class="flex flex-wrap gap-[9px]">
				<span v-for="skill in skills" :key="skill.id" class="inline-flex items-center text-[13px] font-semibold text-ink-700 bg-paper-3 px-3.5 py-2 rounded-[9px]">{{ skill.name }}</span>
			</div>
			<p v-else class="text-[14.5px] text-muted">No skills yet.</p>
		</div>
	</div>
</template>
