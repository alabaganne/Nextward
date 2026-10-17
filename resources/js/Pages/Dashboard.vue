<script setup>
import { Bookmark, Briefcase, Building2, CalendarCheck, ChevronRight, FileText, GraduationCap } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'dashboard' }, () => page),
});

const props = defineProps({
	internships_count: Number,
	students_count: Number,
	companies_count: Number,
	applications_count: Number,
	interviews_count: { type: Number, default: 0 },
	saved_count: { type: Number, default: 0 },
	roles_count: { type: Number, default: 0 },
	applications: { type: Array, default: () => [] },
	recommended: { type: Array, default: () => [] },
	recommendation_reasons: { type: Array, default: () => [] },
	is_new_user: { type: Boolean, default: false },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const firstName = computed(() => (user.value?.name || '').split(' ')[0]);
const isAdmin = computed(() => !!user.value?.is_admin);
const isCompany = computed(() => user.value?.userable_type === 'company');
const managedRolesHref = computed(() => `${route('companies.show', user.value?.userable_id)}#opportunities`);
const partyName = (application) => (isCompany.value ? application.student?.name : application.company?.name) || '';

const description = computed(() => {
	if (isAdmin.value) return 'Keep opportunities, companies, and candidates up to date.';
	if (isCompany.value) return 'Review your roles and keep applications moving.';
	return 'Here is where your search stands today.';
});

const cards = computed(() => {
	if (isAdmin.value) {
		return [
			{ label: 'Open opportunities', value: props.internships_count, icon: Briefcase, href: route('opportunities.index') },
			{ label: 'Companies', value: props.companies_count, icon: Building2, href: route('companies.index') },
			{ label: 'Candidates', value: props.students_count, icon: GraduationCap, href: route('students.index') },
		];
	}
	if (isCompany.value) {
		return [
			{ label: 'Open roles', value: props.roles_count, icon: Briefcase, href: managedRolesHref.value },
			{ label: 'Active applications', value: props.applications_count, icon: FileText, href: route('applications.index') },
			{ label: 'Interviews', value: props.interviews_count, icon: CalendarCheck, href: route('applications.index', { status: 'interview' }) },
		];
	}
	return [
		{ label: 'Active applications', value: props.applications_count, icon: FileText, href: route('applications.index') },
		{ label: 'Interviews', value: props.interviews_count, icon: CalendarCheck, href: route('applications.index', { status: 'interview' }) },
		{ label: 'Saved roles', value: props.saved_count, icon: Bookmark, href: route('likes.index') },
	];
});
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader :title="`${is_new_user ? 'Welcome' : 'Welcome back'}, ${firstName}`" document-title="Dashboard" :description="description">
			<template #actions>
				<Link v-if="isCompany" class="cbtn cbtn-secondary h-11 !py-0" :href="managedRolesHref">Manage your roles</Link>
				<Link v-else-if="!isAdmin" class="cbtn cbtn-primary h-11 !py-0" :href="route('opportunities.index')">Browse opportunities</Link>
			</template>
		</PageHeader>

		<div class="grid grid-cols-3 gap-4 mb-6 max-[480px]:gap-2">
			<Link
				v-for="card in cards"
				:key="card.label"
				:href="card.href"
				class="group bg-white border border-line rounded-[16px] p-5 max-[480px]:p-4 transition-colors hover:border-blue-300 focus-visible:outline-2 focus-visible:outline-blue-500"
			>
				<div class="flex items-center justify-between gap-2 text-muted">
					<span class="text-sm font-semibold leading-snug">{{ card.label }}</span>
					<component :is="card.icon" class="size-[18px] shrink-0 text-muted-2 group-hover:text-blue-600 transition-colors max-[480px]:hidden" aria-hidden="true" />
				</div>
				<div class="font-display text-[34px] max-[480px]:text-[28px] font-semibold mt-3 leading-none">{{ card.value ?? 0 }}</div>
			</Link>
		</div>

		<div class="grid gap-5 items-start max-[1080px]:grid-cols-1" :class="recommended.length ? 'grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]' : 'grid-cols-1'">
			<section class="bg-white border border-line rounded-[16px] overflow-hidden">
				<div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-line-2">
					<h2 class="font-display text-[17px] font-semibold">{{ isAdmin ? 'Manage the catalog' : 'Recent applications' }}</h2>
					<Link v-if="!isAdmin && applications.length" class="text-sm font-semibold text-blue-700 hover:underline" :href="route('applications.index')">View all</Link>
				</div>

				<Link
					v-for="(application, index) in applications"
					:key="application.id"
					class="flex items-center gap-3.5 px-5 py-3.5 transition-colors hover:bg-paper-2"
					:class="{ 'border-b border-line-2': index !== applications.length - 1 }"
					:href="route('applications.show', application.id)"
				>
					<CompanyLogo class="size-10 rounded-[10px] text-[14px]" :name="partyName(application)" :src="isCompany ? null : application.company?.image" />
					<div class="flex-1 min-w-0">
						<b class="text-[15px] font-semibold block truncate">{{ application.internship.title }}</b>
						<span class="text-[13px] text-muted block truncate">{{ [partyName(application), application.internship.city?.name].filter(Boolean).join(', ') }}</span>
					</div>
					<StatusPill :status="application.status" />
					<span class="text-[13px] text-muted-2 whitespace-nowrap max-[760px]:hidden">{{ application.created_at }}</span>
					<ChevronRight class="size-4 text-muted-2 shrink-0 max-[760px]:hidden" aria-hidden="true" />
				</Link>

				<div v-if="!applications.length" class="px-5 py-8 text-[14.5px] text-muted">
					<div v-if="isAdmin" class="flex flex-wrap gap-x-6 gap-y-3">
						<Link class="font-semibold text-blue-700 hover:underline" :href="route('imports.index')">Sources and imports</Link>
						<Link class="font-semibold text-blue-700 hover:underline" :href="route('companies.index')">Companies</Link>
						<Link class="font-semibold text-blue-700 hover:underline" :href="route('students.index')">Candidates</Link>
						<Link class="font-semibold text-blue-700 hover:underline" :href="route('fields.index')">Fields</Link>
					</div>
					<p v-else-if="isCompany">No applications yet. Keep your <Link class="font-semibold text-blue-700 hover:underline" :href="managedRolesHref">role details</Link> up to date to attract candidates.</p>
					<p v-else>You have not applied anywhere yet. <Link class="font-semibold text-blue-700 hover:underline" :href="route('opportunities.index')">Find a role</Link> to get started.</p>
				</div>
			</section>

			<section v-if="recommended.length" class="bg-white border border-line rounded-[16px] overflow-hidden">
				<div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-line-2">
					<h2 class="font-display text-[17px] font-semibold">Picked for you</h2>
					<Link class="text-sm font-semibold text-blue-700 hover:underline" :href="route('settings')">Preferences</Link>
				</div>
				<p class="px-5 pt-3.5 text-[13px] text-muted">{{ recommendation_reasons.join('. ') }}</p>
				<div class="py-1.5">
					<Link
						v-for="item in recommended"
						:key="item.id"
						class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-paper-2"
						:href="item.public_url || route('internships.show', item.id)"
					>
						<CompanyLogo class="size-9 rounded-[9px] text-[12px]" :name="item.company?.name || ''" :src="item.company?.image" />
						<div class="min-w-0">
							<b class="text-sm font-semibold block truncate">{{ item.title }}</b>
							<span class="text-[12.5px] text-muted block truncate">{{ [item.company?.name, item.location_label || item.city?.name].filter(Boolean).join(', ') }}</span>
						</div>
					</Link>
				</div>
			</section>
		</div>
	</div>
</template>
