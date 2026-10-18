<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'applications' }, () => page),
});

const props = defineProps({
	applications: Object, // paginated resource
	counts: { type: Object, default: () => ({}) }, // totals per status across every page
	status: { type: String, default: 'all' },
});

const icons = {
	chevron: ChevronRight,
};

const page = usePage();
const isCandidate = computed(() => page.props.auth?.user?.userable_type === 'candidate');
const description = computed(() => isCandidate.value
	? "Track every role you've applied to and see where you stand at a glance."
	: 'Review every application to your open roles at a glance.');

const buckets = [
	{ key: 'all', label: 'All', dot: 'bg-blue-500' },
	{ key: 'review', label: 'In review', dot: 'bg-[#d97706]' },
	{ key: 'interview', label: 'Interview', dot: 'bg-[#059669]' },
	{ key: 'offer', label: 'Offer', dot: 'bg-[#7c3aed]' },
	{ key: 'closed', label: 'Closed', dot: 'bg-muted-2' },
];

const rows = computed(() => props.applications.data);

const setFilter = (key) => {
	if (key === props.status) return;
	router.get(route('applications.index'), key === 'all' ? {} : { status: key }, { preserveScroll: true, preserveState: true });
};

const partyName = (application) => (isCandidate.value ? application.company?.name : application.candidate?.name) || '';
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Applications" :description="description" />

		<div class="flex gap-2 p-1 -mx-1 -mt-1 mb-4 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by status">
			<button
				v-for="bucket in buckets"
				:key="bucket.key"
				class="chip inline-flex items-center gap-2 h-10 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors"
				:class="{ 'is-active': status === bucket.key }"
				type="button"
				:aria-pressed="status === bucket.key"
				@click="setFilter(bucket.key)"
			>
				<span v-if="bucket.key !== 'all'" class="size-2 rounded-full" :class="bucket.dot"></span>
				{{ bucket.label }}
				<span class="text-xs opacity-70">{{ counts[bucket.key] ?? 0 }}</span>
			</button>
		</div>

		<!-- TABLE -->
		<div class="bg-white border border-line rounded-[18px] overflow-hidden">
			<div class="grid grid-cols-[1fr_150px_130px_40px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-bold [&_span:last-child]:text-right">
				<span>{{ isCandidate ? 'Role' : 'Applicant · Role' }}</span><span>Applied</span><span>Status</span><span></span>
			</div>
			<div>
				<Link
					v-for="(application, index) in rows"
					:key="application.id"
					class="grid grid-cols-[1fr_150px_130px_40px] gap-4 items-center px-[22px] py-4 cursor-pointer transition-colors hover:bg-paper-2 focus-visible:-outline-offset-2 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
					:class="{ 'border-b border-line-2': index !== rows.length - 1 }"
					:href="route('applications.show', application.id)"
				>
					<div class="flex items-center gap-3.5 min-w-0">
						<CompanyLogo class="w-[46px] h-[46px] rounded-[12px] text-[15px]" :name="partyName(application)" :src="isCandidate ? application.company?.image : null" />
						<div>
							<b class="text-[15px] font-semibold block">{{ application.opportunity.title }}</b>
							<span class="text-[13px] text-muted">{{ partyName(application) }} · {{ application.opportunity.city?.name }}</span>
						</div>
					</div>
					<div class="text-[13px] text-muted-2 max-[760px]:hidden">{{ application.created_at }}</div>
					<div><StatusPill :status="application.status" /><span v-if="application.channel === 'external'" class="block text-xs text-muted mt-1">Your reported status</span></div>
					<div class="w-[34px] h-[34px] rounded-[9px] border border-line grid place-items-center text-muted-2 justify-self-end max-[760px]:hidden [&_svg]:w-4 [&_svg]:h-4"><component :is="icons.chevron" aria-hidden="true" /></div>
				</Link>

				<div v-if="!rows.length && status !== 'all'" class="px-[22px] py-8 text-[14px] text-muted">
					No applications with this status.
					<button class="font-semibold text-blue-700" type="button" @click="setFilter('all')">Show all</button>
				</div>
				<div v-else-if="!rows.length" class="px-[22px] py-8 text-[14px] text-muted">
					No applications here yet.
					<Link v-if="isCandidate" class="font-semibold text-blue-700" :href="route('opportunities.index')">Browse opportunities</Link>
				</div>
			</div>
		</div>

		<Pagination :meta="applications.meta" />
	</div>
</template>
