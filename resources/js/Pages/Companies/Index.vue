<script setup>
import { Building2, Search } from '@lucide/vue';
import { h, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyCard from '@/Components/Company/CompanyCard.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'companies' }, () => page),
});

const props = defineProps({
	companies: Object, // plain paginator: { data, links, current_page, ... }
	filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.search || '');
const hiring = ref(!!props.filters.hiring);

let timer;
const reload = () => {
	clearTimeout(timer);
	const query = {};
	if (search.value.trim()) query.search = search.value.trim();
	if (hiring.value) query.hiring = 1;
	router.get(route('app.companies.index'), query, { preserveState: true, preserveScroll: true, replace: true });
};
watch(search, () => {
	clearTimeout(timer);
	timer = setTimeout(reload, 300);
});
watch(hiring, reload);

</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Companies" description="Employers with roles on Nextward.">
			<template v-if="page.props.auth?.user?.is_admin" #actions>
				<Link class="cbtn cbtn-primary h-11 !py-0" :href="route('companies.create')">Add company</Link>
			</template>
		</PageHeader>

		<div class="flex items-center gap-3 mb-5 flex-wrap">
			<label class="flex items-center gap-2.5 bg-white border border-line rounded-[12px] px-3.5 h-11 flex-1 min-w-[240px] focus-within:border-blue-500 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-blue-600">
				<Search class="size-[18px] text-muted-2 shrink-0" aria-hidden="true" />
				<input v-model="search" class="border-0 bg-transparent p-0 outline-none focus:ring-0 text-[14.5px] w-full" type="search" placeholder="Search by company or city" aria-label="Search companies">
			</label>
			<label class="inline-flex items-center gap-2.5 h-11 px-4 rounded-[12px] border border-line bg-white text-sm font-semibold cursor-pointer select-none">
				<input v-model="hiring" type="checkbox" class="ck">
				Hiring now
			</label>
		</div>

		<p class="text-sm text-muted mb-4">{{ companies.total }} {{ companies.total === 1 ? 'company' : 'companies' }}</p>

		<div v-if="companies.data.length" class="grid grid-cols-3 gap-4 max-[1180px]:grid-cols-2 max-[760px]:grid-cols-1">
			<CompanyCard v-for="company in companies.data" :key="company.id" :company="company" />
		</div>

		<div v-else class="flex flex-col items-center text-center py-16 px-5 bg-white border border-line rounded-[16px]">
			<Building2 class="size-8 text-muted-2 mb-3" aria-hidden="true" />
			<h2 class="font-display text-lg font-semibold">No companies found</h2>
			<p class="text-muted text-sm mt-1.5">Try another name or city, or turn off "Hiring now".</p>
		</div>

		<Pagination :meta="companies" />
	</div>
</template>
