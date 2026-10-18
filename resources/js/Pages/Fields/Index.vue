<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'fields' }, () => page),
});

const props = defineProps({
	fields: { type: Object, required: true },
});

const meta = computed(() => props.fields.meta ?? props.fields);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<PageHeader title="Fields of study" description="The areas that opportunities and candidates are grouped by.">
			<template #actions>
				<Link class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('fields.create')">Add field</Link>
			</template>
		</PageHeader>

		<div class="bg-white border border-line rounded-[18px] overflow-hidden">
			<div class="grid grid-cols-[1fr_160px_160px_90px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-bold [&_span:last-child]:text-right">
				<span>Field</span><span>Opportunities</span><span>Candidates</span><span></span>
			</div>
			<div
				v-for="(field, index) in fields.data"
				:key="field.id"
				class="grid grid-cols-[1fr_160px_160px_90px] gap-4 items-center px-[22px] py-4 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
				:class="{ 'border-b border-line-2': index !== fields.data.length - 1 }"
			>
				<b class="text-[15px] font-semibold">{{ field.name }}</b>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ field.opportunities_count }}</span>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ field.candidates_count }}</span>
				<Link class="justify-self-end text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] hover:bg-blue-100 transition-colors" :href="route('fields.edit', field.id)">Edit</Link>
			</div>
			<div v-if="!fields.data.length" class="px-[22px] py-8 text-[14px] text-muted">No fields yet.</div>
		</div>

		<Pagination :meta="meta" />
	</div>
</template>
