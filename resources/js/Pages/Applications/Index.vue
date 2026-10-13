<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'applications' }, () => page),
});

const props = defineProps({
	applications: Object, // paginated resource
});

const icons = {
	chevron: ChevronRight,
};

const page = usePage();
const isStudent = computed(() => page.props.auth?.user?.userable_type === 'student');

// Legacy statuses are boolean/null; the Phase-3 enum slots straight in here.
const bucketFor = (status) => {
	if (status === true || status === 'accepted' || status === 'offer') return 'offer';
	if (status === 'interview') return 'interview';
	if (status === false || status === 'closed' || status === 'rejected') return 'closed';
	return 'review';
};

const buckets = [
	{ key: 'all', label: 'All', dot: 'bg-blue-500' },
	{ key: 'review', label: 'In review', dot: 'bg-[#d97706]' },
	{ key: 'interview', label: 'Interview', dot: 'bg-[#059669]' },
	{ key: 'offer', label: 'Offer', dot: 'bg-[#7c3aed]' },
	{ key: 'closed', label: 'Closed', dot: 'bg-muted-2' },
];

const filter = ref('all');

const countFor = (key) =>
	key === 'all'
		? props.applications.data.length
		: props.applications.data.filter((a) => bucketFor(a.status) === key).length;

const rows = computed(() =>
	filter.value === 'all'
		? props.applications.data
		: props.applications.data.filter((a) => bucketFor(a.status) === filter.value)
);

const partyName = (application) => (isStudent.value ? application.company?.name : application.student?.name) || '';
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-[30px] pb-[50px] w-full max-w-wrap mx-auto">
		<div class="mb-[22px]">
			<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Your pipeline</span>
			<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Applications</h1>
			<p class="text-muted text-[15px] mt-1.5">
				{{ isStudent ? "Track every role you've applied to and see where you stand at a glance." : 'Review every application to your open roles at a glance.' }}
			</p>
		</div>

		<!-- PIPELINE -->
		<p class="text-xs text-muted mb-3">Filter applications on this page</p>
		<div class="grid grid-cols-5 gap-3.5 mb-[26px] max-[1000px]:grid-cols-3">
			<button
				v-for="bucket in buckets"
				:key="bucket.key"
				class="pcard text-left bg-white border border-line rounded-[18px] px-5 py-[18px] cursor-pointer transition-colors hover:border-blue-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
				:class="{ 'is-active': filter === bucket.key }"
				type="button"
				:aria-pressed="filter === bucket.key"
				@click="filter = bucket.key"
			>
				<span class="block font-display text-[30px] font-semibold leading-none">{{ countFor(bucket.key) }}</span>
				<span class="text-[13px] text-muted font-semibold mt-[7px] flex items-center gap-[7px]">
					<span class="w-2 h-2 rounded-full" :class="bucket.dot"></span>{{ bucket.label }}
				</span>
			</button>
		</div>

		<!-- TABLE -->
		<div class="bg-white border border-line rounded-[18px] overflow-hidden">
			<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
				<b class="text-base font-display font-semibold">All applications</b>
			</div>
			<div class="grid grid-cols-[1fr_150px_130px_40px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-bold [&_span:last-child]:text-right">
				<span>{{ isStudent ? 'Role' : 'Applicant · Role' }}</span><span>Applied</span><span>Status</span><span></span>
			</div>
			<div>
				<Link
					v-for="(application, index) in rows"
					:key="application.id"
					class="grid grid-cols-[1fr_150px_130px_40px] gap-4 items-center px-[22px] py-4 cursor-pointer transition-colors hover:bg-paper-2 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
					:class="{ 'border-b border-line-2': index !== rows.length - 1 }"
					:href="route('applications.show', application.id)"
				>
					<div class="flex items-center gap-3.5 min-w-0">
						<CompanyLogo class="w-[46px] h-[46px] rounded-[12px] text-[15px]" :name="partyName(application)" />
						<div>
							<b class="text-[15px] font-semibold block">{{ application.internship.title }}</b>
							<span class="text-[13px] text-muted">{{ partyName(application) }} · {{ application.internship.city?.name }}</span>
						</div>
					</div>
					<div class="text-[13px] text-muted-2 max-[760px]:hidden">{{ application.created_at }}</div>
					<div><StatusPill :status="application.status" /></div>
					<div class="w-[34px] h-[34px] rounded-[9px] border border-line grid place-items-center text-muted-2 justify-self-end max-[760px]:hidden [&_svg]:w-4 [&_svg]:h-4"><component :is="icons.chevron" aria-hidden="true" /></div>
				</Link>

				<div v-if="!rows.length" class="px-[22px] py-8 text-[14px] text-muted">
					No applications here yet.
					<Link v-if="isStudent" class="font-semibold text-blue-700" :href="route('internships.index')">Browse internships</Link>
				</div>
			</div>
		</div>

		<Pagination :meta="applications.meta" />
	</div>
</template>
