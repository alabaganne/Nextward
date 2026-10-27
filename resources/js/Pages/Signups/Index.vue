<script setup>
import { h } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'signups' }, () => page),
});

defineProps({
	rows: { type: Array, required: true },
});

const columns = [
	{ key: 'last_7', label: '7 days' },
	{ key: 'last_30', label: '30 days' },
	{ key: 'total', label: 'All time' },
	{ key: 'candidates', label: 'Candidates' },
	{ key: 'with_alerts', label: 'With an alert' },
];
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<PageHeader title="Sign-up sources" description="New accounts by the ref tag in the link they first opened, like ?ref=reddit-tunisia. With an alert counts candidates who turned on a saved-search email." />

		<div class="bg-white border border-transparent rounded-card overflow-x-auto">
			<table class="w-full min-w-[640px] text-left">
				<thead class="bg-paper-2 border-b border-line-2">
					<tr class="[&_th]:whitespace-nowrap [&_th]:px-[22px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-medium">
						<th scope="col">Ref</th>
						<th v-for="column in columns" :key="column.key" scope="col" class="text-right">{{ column.label }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(row, index) in rows" :key="row.ref ?? 'none'" :class="{ 'border-b border-line-2': index !== rows.length - 1 }">
						<th scope="row" class="px-[22px] py-4 text-[15px]" :class="row.ref ? 'font-medium' : 'font-normal text-muted'">{{ row.ref ?? 'No ref tag' }}</th>
						<td v-for="column in columns" :key="column.key" class="px-[22px] py-4 text-right text-[14px] tabular-nums">{{ row[column.key] }}</td>
					</tr>
					<tr v-if="!rows.length">
						<td :colspan="columns.length + 1" class="px-[22px] py-8 text-[14px] text-muted">No sign-ups yet.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
