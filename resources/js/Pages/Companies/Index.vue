<script setup>
import { Mail, MapPin, Search } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'companies' }, () => page),
});

const props = defineProps({
	companies: Object, // plain paginator: { data, links, current_page, ... }
});

const icons = {
	search: Search,
	pin: MapPin,
	mail: Mail,
};

const query = ref('');
const page = usePage();
const filter = ref('all');

const chips = [
	{ key: 'all', label: 'All companies' },
	{ key: 'hiring', label: 'With opportunities' },
];

const visible = computed(() =>
	props.companies.data.filter((company) => {
		const term = query.value.trim().toLowerCase();
		const matchesTerm =
			!term ||
			company.name.toLowerCase().includes(term) ||
			(company.city?.name || '').toLowerCase().includes(term);
		const matchesFilter = filter.value === 'all' || company.internships_count > 0;
		return matchesTerm && matchesFilter;
	})
);

// The controller uses a plain paginator, so pagination fields sit at the top level.
const meta = computed(() => props.companies.meta ?? props.companies);
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-[34px] pb-14 w-full max-w-wrap mx-auto">
		<div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
			<div>
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Directory</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Companies</h1>
			<p class="text-muted text-[15px] mt-1.5">Explore companies on Nextward and the opportunities they have posted.</p>
			</div>
			<Link v-if="page.props.auth?.user?.is_admin" class="cbtn bg-blue-600 text-white hover:bg-blue-700" :href="route('companies.create')">Add company</Link>
		</div>

		<!-- TOOLBAR -->
		<div class="flex items-center gap-3.5 mb-[22px] flex-wrap">
			<label class="flex items-center gap-2.5 bg-white border border-line rounded-[12px] px-3.5 h-[46px] flex-1 min-w-[240px] focus-within:border-blue-400 [&_svg]:w-[18px] [&_svg]:h-[18px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
				<component :is="icons.search" aria-hidden="true" />
				<input v-model="query" class="border-0 bg-transparent outline-none text-[14.5px] w-full" type="text" placeholder="Filter by company name or city…">
			</label>
			<div class="flex gap-2 flex-wrap">
				<button
					v-for="chip in chips"
					:key="chip.key"
					class="chip inline-flex items-center gap-[7px] h-[46px] px-4 rounded-[12px] border text-[13.5px] font-semibold cursor-pointer transition-colors"
					:class="{ 'is-active': filter === chip.key }"
					type="button"
					:aria-pressed="filter === chip.key"
					@click="filter = chip.key"
				>{{ chip.label }}</button>
			</div>
		</div>

		<p class="text-[13.5px] text-muted font-medium mb-4"><b class="text-ink-700 font-bold">{{ visible.length }}</b> of {{ companies.data.length }} companies</p>

		<!-- GRID -->
		<div class="grid grid-cols-3 gap-[18px] max-[1180px]:grid-cols-2 max-[760px]:grid-cols-1">
			<article
				v-for="company in visible"
				:key="company.id"
				class="bg-white border border-line rounded-[18px] p-6 flex flex-col transition-all hover:border-blue-300 hover:-translate-y-[3px]"
			>
				<div class="flex items-start gap-3.5">
					<CompanyLogo class="w-[52px] h-[52px] rounded-[14px] text-[19px]" :name="company.name" />
					<div class="flex-1 min-w-0">
						<h3 class="font-display text-[18px] font-semibold leading-[1.2]">{{ company.name }}</h3>
						<span class="inline-flex items-center gap-[5px] text-[13px] text-muted mt-[5px] [&_svg]:w-[13px] [&_svg]:h-[13px] [&_svg]:text-muted-2"><component :is="icons.pin" aria-hidden="true" />{{ company.city?.name }}</span>
					</div>
					<span v-if="company.internships_count" class="ml-auto text-xs font-bold text-blue-700 bg-blue-50 px-[11px] py-[5px] rounded-full whitespace-nowrap">{{ company.internships_count }} roles</span>
				</div>
				<div class="flex flex-col gap-[9px] mt-[18px] pt-4 border-t border-line-2 flex-1">
					<div class="flex items-center gap-2.5 text-[13px] text-ink-700 [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.mail" aria-hidden="true" />{{ company.email }}</div>
				</div>
				<div class="flex gap-2.5 mt-[18px]">
					<Link class="flex-1 justify-center h-[42px] inline-flex items-center font-semibold text-[15px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('companies.show', company.id)">View</Link>
					<Link v-if="company.user_id !== page.props.auth?.user?.id" class="flex-1 justify-center h-[42px] inline-flex items-center font-semibold text-[15px] rounded-[12px] bg-paper-2 border border-line text-ink-700 hover:border-blue-300 hover:text-blue-700 transition-colors" :href="route('messages.show', company.user_id)">Message</Link>
				</div>
			</article>
		</div>

		<div v-if="!visible.length" class="text-center py-[60px] text-muted">No companies match your filters.</div>

		<Pagination :meta="meta" />
	</div>
</template>
