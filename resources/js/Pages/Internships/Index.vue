<script>
import { h } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PublicLayout from '@/Layouts/Public.vue';

// Guests see the public browse chrome; signed-in users the workspace shell.
export default {
	layout: (_h, page) =>
		page.props?.auth?.user
			? h(AppShell, { active: 'internships' }, () => page)
			: h(PublicLayout, {}, () => page),
};
</script>

<script setup>
import { Search } from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import throttle from 'lodash-es/throttle.js';
import RoleCard from '@/Components/Internship/RoleCard.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

const props = defineProps({
	internships: Object, // paginated resource: { data, meta, links }
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
	companies: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const icons = {
	search: Search,
};

const page = usePage();
const authed = computed(() => !!page.props.auth?.user);
const managing = computed(() => page.props.auth?.user?.userable_type === 'company' || page.props.auth?.user?.is_admin);

const selected = reactive({
	fields: (props.filters.fields || []).map(Number),
	cities: (props.filters.cities || []).map(Number),
	companies: (props.filters.companies || []).map(Number),
});
const search = ref(props.filters.search || '');

const applyFilters = () => {
	router.get(
		route('internships.index'),
		{ ...selected, search: search.value || undefined },
		{ preserveState: true, preserveScroll: true, replace: true }
	);
};

watch(selected, applyFilters);
const applySearchFilters = throttle(applyFilters, 650);
watch(search, applySearchFilters);
onBeforeUnmount(() => applySearchFilters.cancel());

const clearAll = () => {
	selected.fields = [];
	selected.cities = [];
	selected.companies = [];
	search.value = '';
};

// Quick tabs: "All roles" + each field.
const activeTab = computed(() => (selected.fields.length === 1 ? selected.fields[0] : null));
const pickTab = (fieldId) => {
	selected.fields = fieldId === null ? [] : [fieldId];
};

const filterSections = computed(() =>
	[
		{ key: 'fields', title: 'Field', options: props.fields },
		{ key: 'cities', title: 'Location', options: props.cities },
		{ key: 'companies', title: 'Company', options: props.companies },
	].filter((section) => section.options.length)
);

const toggle = (key, id) => {
	const list = selected[key];
	const index = list.indexOf(id);
	index === -1 ? list.push(id) : list.splice(index, 1);
};
</script>

<template>
	<!-- Public top section (browse.html) -->
	<section v-if="!authed" class="bg-white border-b border-line pt-10">
		<div class="max-w-wrap mx-auto px-7">
			<h1 class="font-display font-semibold tracking-[-.02em] text-[clamp(28px,3.4vw,40px)] mb-2.5">Browse internships</h1>
			<p class="text-muted text-[16.5px] max-w-[560px]">{{ internships.meta.total }} open roles from real companies. Filter by field, location, and company to find the ones built for you.</p>
			<div class="flex gap-2.5 mt-7 flex-wrap">
				<div class="flex-1 min-w-[200px] flex items-center gap-2.5 bg-white border border-line rounded-[12px] px-3.5 h-[52px] focus-within:border-blue-400 [&_svg]:w-[18px] [&_svg]:h-[18px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
					<component :is="icons.search" aria-hidden="true" />
					<input v-model="search" class="border-0 outline-none bg-transparent text-[15px] text-ink w-full" type="text" placeholder="Role, company, or keyword" @keyup.enter="applyFilters">
				</div>
				<button class="inline-flex items-center justify-center font-semibold text-[15px] h-[52px] px-[26px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" @click="applyFilters">Search</button>
			</div>
			<div class="flex gap-1.5 mt-6 overflow-x-auto">
				<button class="qtab font-body font-semibold text-[14.5px] py-3 mr-[18px] cursor-pointer border-b-2 whitespace-nowrap bg-transparent" :class="{ 'is-active': activeTab === null }" @click="pickTab(null)">All roles</button>
				<button v-for="field in fields" :key="field.id" class="qtab font-body font-semibold text-[14.5px] py-3 mr-[18px] cursor-pointer border-b-2 whitespace-nowrap bg-transparent" :class="{ 'is-active': activeTab === field.id }" @click="pickTab(field.id)">{{ field.name }}</button>
			</div>
		</div>
	</section>

	<div :class="authed ? 'px-10 max-[680px]:px-5 pt-[30px] pb-[50px] w-full max-w-wrap mx-auto' : 'max-w-wrap mx-auto px-7 w-full'">
		<!-- Workspace header (internships.html) -->
		<template v-if="authed">
			<div class="mb-[22px]">
				<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Explore</span>
				<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Internships</h1>
				<p class="text-muted text-[15px] mt-1.5">{{ internships.meta.total }} {{ managing ? 'internships. Review and manage your listings.' : 'open roles. Filter to find the ones built for you.' }}</p>
			</div>

			<div class="flex gap-2.5 flex-wrap">
				<div class="flex-1 min-w-[200px] flex items-center gap-2.5 bg-white border border-line rounded-[12px] px-3.5 h-[50px] focus-within:border-blue-400 [&_svg]:w-[18px] [&_svg]:h-[18px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
					<component :is="icons.search" aria-hidden="true" />
					<input v-model="search" class="border-0 outline-none bg-transparent text-[15px] text-ink w-full" type="text" placeholder="Role, company, or keyword" @keyup.enter="applyFilters">
				</div>
				<button class="inline-flex items-center justify-center font-semibold text-[15px] h-[50px] px-[26px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" @click="applyFilters">Search</button>
			</div>

			<div class="flex mt-[18px] border-b border-line overflow-x-auto">
				<button class="qtab font-body font-semibold text-[14.5px] py-3 mr-[22px] cursor-pointer border-b-2 whitespace-nowrap bg-transparent" :class="{ 'is-active': activeTab === null }" @click="pickTab(null)">All roles</button>
				<button v-for="field in fields" :key="field.id" class="qtab font-body font-semibold text-[14.5px] py-3 mr-[22px] cursor-pointer border-b-2 whitespace-nowrap bg-transparent" :class="{ 'is-active': activeTab === field.id }" @click="pickTab(field.id)">{{ field.name }}</button>
			</div>
		</template>

		<div class="grid grid-cols-[260px_1fr] gap-6 pt-6 items-start max-[1040px]:grid-cols-1" :class="{ 'pb-[90px]': !authed }">
			<!-- FILTERS -->
			<aside class="sticky top-[94px] bg-white border border-line rounded-[18px] p-[22px] max-[1040px]:static">
				<div class="flex items-center justify-between mb-1.5">
					<b class="font-display text-base">Filters</b>
					<button class="text-[13px] text-blue-700 font-semibold" type="button" @click="clearAll">Clear all</button>
				</div>
				<div
					v-for="(section, index) in filterSections"
					:key="section.key"
					:class="index === filterSections.length - 1 ? 'pt-[18px]' : 'py-[18px] border-b border-line-2'"
				>
					<h4 class="text-xs tracking-[.1em] uppercase text-muted-2 font-bold mb-3.5">{{ section.title }}</h4>
					<label
						v-for="option in section.options"
						:key="option.id"
						class="check flex items-center gap-2.5 py-1.5 cursor-pointer text-[14.5px] text-ink-700"
					>
						<input type="checkbox" :checked="selected[section.key].includes(option.id)" @change="toggle(section.key, option.id)">
						{{ option.name }}
						<span class="ml-auto text-[12.5px] text-muted-2 font-semibold">{{ option.internships_count }}</span>
					</label>
				</div>
			</aside>

			<!-- RESULTS -->
			<main>
				<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
					<div class="text-[14.5px] text-muted"><b class="text-ink font-display">{{ internships.meta.total }}</b> internships found</div>
					<div class="flex items-center gap-2 text-sm text-muted">Sort by
						<select class="border border-line rounded-[9px] px-3 py-2.5 font-body font-semibold text-sm text-ink bg-white cursor-pointer"><option>Most recent</option></select>
					</div>
				</div>

				<div class="flex flex-col gap-3.5">
					<RoleCard v-for="internship in internships.data" :key="internship.id" :internship="internship" />
					<div v-if="!internships.data.length" class="bg-white border border-line rounded-[18px] p-[22px] text-[14.5px] text-muted">
						No internships match these filters.
						<button class="font-semibold text-blue-700" type="button" @click="clearAll">Clear all filters</button>
					</div>
				</div>

				<Pagination :meta="internships.meta" />
			</main>
		</div>
	</div>
</template>
