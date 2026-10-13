<script setup>
import { ArrowRight, Briefcase, Building2, ChevronRight, List, MessageSquare, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({
	layout: (h, page) => h(AppShell, { active: 'dashboard' }, () => page),
});

const props = defineProps({
	internships_count: Number,
	students_count: Number,
	companies_count: Number,
	applications_count: Number,
	interviews_count: { type: Number, default: 0 },
	applications: { type: Array, default: () => [] },
	recommended: { type: Array, default: () => [] },
});

const icons = {
	arrow: ArrowRight,
	briefcase: Briefcase,
	building: Building2,
	list: List,
	chat: MessageSquare,
	people: Users,
	chevron: ChevronRight,
};

const page = usePage();
const user = computed(() => page.props.auth?.user);
const firstName = computed(() => (user.value?.name || '').split(' ')[0]);
const isAdmin = computed(() => !!user.value?.is_admin);
const isCompany = computed(() => user.value?.userable_type === 'company');
const isMember = computed(() => ['student', 'company'].includes(user.value?.userable_type));
const partyName = (application) => (user.value?.userable_type === 'student' ? application.company?.name : application.student?.name) || '';
const welcomeNote = computed(() => {
	if (user.value?.is_admin) return 'Keep track of internships, companies, and students across Internly.';
	if (user.value?.userable_type === 'company') return 'Review your roles and keep candidate applications moving.';
	return "Here’s what’s happening with your internship search.";
});

const cards = computed(() =>
	[
		{
			label: 'Open internships',
			value: props.internships_count,
			icon: icons.briefcase,
			gradient: 'linear-gradient(140deg,#3b82f6,#1d4ed8)',
			href: route('internships.index'),
			show: true,
		},
		{
			label: 'Companies',
			value: props.companies_count,
			icon: icons.building,
			gradient: 'linear-gradient(140deg,#6366f1,#4338ca)',
			href: route('companies.index'),
			show: true,
		},
		{
			label: 'Active applications',
			value: props.applications_count,
			icon: icons.list,
			gradient: 'linear-gradient(140deg,#f59e0b,#d97706)',
			href: route('applications.index'),
			show: isMember.value,
		},
		{
			label: 'Interviews',
			value: props.interviews_count,
			icon: icons.chat,
			gradient: 'linear-gradient(140deg,#10b981,#047857)',
			href: route('applications.index'),
			show: isMember.value,
		},
		{
			label: 'Students',
			value: props.students_count,
			icon: icons.people,
			gradient: 'linear-gradient(140deg,#10b981,#047857)',
			href: route('students.index'),
			show: !!user.value?.is_admin,
		},
	].filter((card) => card.show)
);
</script>

<template>
	<div class="px-10 max-[680px]:px-5 pt-[34px] pb-[50px] w-full max-w-wrap mx-auto">
		<div class="flex items-end justify-between flex-wrap gap-4 mb-7">
			<div>
				<span class="block font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600 mb-2">Overview</span>
				<h1 class="font-display text-[32px] font-semibold tracking-[-.02em]">Welcome back, {{ firstName }}</h1>
				<p class="text-muted text-[15px] mt-1.5">{{ welcomeNote }}</p>
			</div>
			<Link class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-all [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="route('internships.index')">
				{{ isCompany ? 'Manage your roles' : 'Browse all roles' }}
				<component :is="icons.arrow" aria-hidden="true" />
			</Link>
		</div>

		<!-- STATS -->
		<div class="grid gap-4 mb-7 max-[1000px]:grid-cols-2" :class="cards.length === 3 ? 'grid-cols-3' : 'grid-cols-4'">
			<div v-for="card in cards" :key="card.label" class="bg-white border border-line rounded-[18px] p-[22px]">
				<div class="flex items-center justify-between mb-[18px]">
					<div class="w-11 h-11 rounded-[12px] grid place-items-center text-white [&_svg]:w-[21px] [&_svg]:h-[21px]" :style="{ background: card.gradient }"><component :is="card.icon" aria-hidden="true" /></div>
				</div>
				<div class="text-[12.5px] tracking-[.08em] uppercase text-muted-2 font-bold">{{ card.label }}</div>
				<div class="font-display text-[34px] font-semibold mt-1 leading-none">{{ card.value ?? 0 }}</div>
				<Link class="inline-flex items-center gap-1.5 mt-3.5 text-[13.5px] font-semibold text-blue-700 [&_svg]:w-3.5 [&_svg]:h-3.5" :href="card.href">
					View all <component :is="icons.arrow" aria-hidden="true" />
				</Link>
			</div>
		</div>

		<!-- TWO COL -->
		<div class="grid gap-4 items-start max-[980px]:grid-cols-1" :class="recommended.length ? 'grid-cols-[1.7fr_1fr]' : 'grid-cols-1'">
			<!-- recent applications -->
			<section class="bg-white border border-line rounded-[18px] overflow-hidden">
				<div class="flex items-center justify-between px-[22px] py-5 border-b border-line-2">
					<div>
						<h3 class="font-display text-[17px] font-semibold">{{ isAdmin ? 'Manage your workspace' : 'Recent applications' }}</h3>
						<p class="text-[13px] text-muted mt-0.5">{{ isAdmin ? 'Keep company profiles, student accounts, and fields up to date.' : isCompany ? 'Review the latest applications for your internships.' : 'Track your recent applications and their status.' }}</p>
					</div>
					<Link v-if="!isAdmin" class="text-[13.5px] font-semibold text-blue-700" :href="route('applications.index')">View all</Link>
				</div>

				<Link
					v-for="(application, index) in applications"
					:key="application.id"
					class="flex items-center gap-[15px] px-[22px] py-4 transition-colors hover:bg-paper-2"
					:class="{ 'border-b border-line-2': index !== applications.length - 1 }"
					:href="route('applications.show', application.id)"
				>
					<CompanyLogo class="w-11 h-11 rounded-[11px] text-[15px]" :name="partyName(application)" />
					<div class="flex-1 min-w-0">
						<b class="text-[15px] font-semibold block">{{ application.internship.title }}</b>
						<span class="text-[13px] text-muted">{{ partyName(application) }} · {{ application.internship.city?.name }}</span>
					</div>
					<StatusPill :status="application.status" />
					<div class="text-[13px] text-muted-2 text-right whitespace-nowrap max-[760px]:hidden">{{ application.created_at }}</div>
					<div class="w-[34px] h-[34px] rounded-[9px] border border-line grid place-items-center text-muted-2 shrink-0 max-[760px]:hidden [&_svg]:w-4 [&_svg]:h-4"><component :is="icons.chevron" aria-hidden="true" /></div>
				</Link>

				<div v-if="!applications.length" class="px-[22px] py-8 text-[14px] text-muted">
					<template v-if="isAdmin">
						<div class="flex flex-wrap gap-x-6 gap-y-3">
							<Link class="font-semibold text-blue-700" :href="route('companies.index')">Manage companies</Link>
							<Link class="font-semibold text-blue-700" :href="route('students.index')">Manage students</Link>
							<Link class="font-semibold text-blue-700" :href="route('fields.index')">Manage fields</Link>
						</div>
					</template>
					<template v-else-if="isCompany">
						No applications received yet.
						<Link class="font-semibold text-blue-700" :href="route('internships.index')">Manage your internships</Link>
						and keep your role details up to date.
					</template>
					<template v-else>
						No applications yet.
						<Link class="font-semibold text-blue-700" :href="route('internships.index')">Browse internships</Link>
						to get started.
					</template>
				</div>
			</section>

			<!-- right column -->
			<div v-if="recommended.length" class="flex flex-col gap-4">
				<!-- recommended -->
				<section v-if="recommended.length" class="bg-white border border-line rounded-[18px] overflow-hidden">
					<div class="flex items-center justify-between px-[22px] py-5 border-b border-line-2">
						<div><h3 class="font-display text-[17px] font-semibold">Recommended for you</h3></div>
						<Link class="text-[13.5px] font-semibold text-blue-700" :href="route('internships.index')">More</Link>
					</div>
					<div class="py-2">
						<Link
							v-for="item in recommended"
							:key="item.id"
							class="flex items-center gap-3 px-[22px] py-[13px] transition-colors hover:bg-paper-2"
							:href="route('internships.show', item.id)"
						>
							<CompanyLogo class="w-10 h-10 rounded-[10px] text-[13px]" :name="item.company" />
							<div>
								<b class="text-sm font-semibold block">{{ item.title }}</b>
								<span class="text-[12.5px] text-muted">{{ item.company }} · {{ item.city }}</span>
							</div>
							<span v-if="item.match" class="ml-auto text-xs font-bold text-blue-700 bg-blue-50 px-[9px] py-1 rounded-[7px] whitespace-nowrap">{{ item.match }}%</span>
						</Link>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
