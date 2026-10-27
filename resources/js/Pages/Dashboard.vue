<script setup>
import { ArrowRight, Bookmark, BookOpen, Briefcase, Building2, CalendarCheck, ChevronRight, Database, FileText, GraduationCap, Target } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { appPath } from '@/utils/url';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'dashboard' }, () => page),
});

const props = defineProps({
	opportunities_count: Number,
	candidates_count: Number,
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
const partyName = (application) => (isCompany.value ? application.candidate?.name : application.company?.name) || '';

const description = computed(() => {
	if (isAdmin.value) return 'Keep roles, companies, and candidates up to date.';
	if (isCompany.value) return 'Review your roles and keep applications moving.';
	return 'Here is where your search stands today.';
});

const mascot = computed(() => {
	if (isAdmin.value) return { src: '/nextward-assets/mascots/04-mentor-pointing.svg', width: 768, height: 768 };
	if (isCompany.value) return { src: '/nextward-assets/mascots/18-recruiter-mentor.webp', width: 900, height: 900 };
	return { src: '/nextward-assets/mascots/17-student-presenter.webp', width: 900, height: 900 };
});

const adminLinks = [
	{ label: 'Review queue', route: 'imports.review', icon: Database },
	{ label: 'Job leads', route: 'leads.index', icon: Target },
	{ label: 'Companies', route: 'app.companies.index', icon: Building2 },
	{ label: 'Candidates', route: 'candidates.index', icon: GraduationCap },
	{ label: 'Fields', route: 'fields.index', icon: BookOpen },
];

const cards = computed(() => {
	if (isAdmin.value) {
		return [
			{ label: 'Open roles', value: props.opportunities_count, icon: Briefcase, href: route('app.opportunities.index') },
			{ label: 'Companies', value: props.companies_count, icon: Building2, href: route('app.companies.index') },
			{ label: 'Candidates', value: props.candidates_count, icon: GraduationCap, href: route('candidates.index') },
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
		<PageHeader tone="navy" label="Dashboard" :title="`${is_new_user ? 'Welcome' : 'Welcome back'}, ${firstName}`" document-title="Dashboard" :description="description">
			<template #title>{{ is_new_user ? 'Welcome' : 'Welcome back' }}, <span class="accent">{{ firstName }}</span></template>
			<template #actions>
				<template v-if="isAdmin">
					<Link class="cta cta-light" :href="route('imports.review')">Review queue</Link>
					<Link class="cta cta-ghost" :href="route('leads.index')">Job leads</Link>
				</template>
				<template v-else-if="isCompany">
					<Link class="cta cta-light" :href="route('opportunities.create')">Post a role</Link>
					<Link class="cta cta-ghost" :href="managedRolesHref">Manage your roles</Link>
				</template>
				<template v-else>
					<Link class="cta cta-light" :href="route('app.opportunities.index')">Browse roles<ArrowRight class="size-[18px]" aria-hidden="true" /></Link>
					<Link class="cta cta-ghost" :href="route('settings', { tab: 'preferences' })">Job preferences</Link>
				</template>
			</template>
			<template #art>
				<img :src="mascot.src" alt="" :width="mascot.width" :height="mascot.height" decoding="async" class="h-[210px] w-auto" />
			</template>
		</PageHeader>

		<div class="grid grid-cols-3 gap-5 mb-7 max-[760px]:grid-cols-1 max-[760px]:gap-3">
			<Link
				v-for="card in cards"
				:key="card.label"
				:href="card.href"
				class="group flex items-center gap-5 rounded-card border border-transparent bg-white p-6 transition-colors hover:border-brand/40 focus-visible:outline-2 focus-visible:outline-brand max-[760px]:p-5"
			>
				<span class="grid size-14 shrink-0 place-items-center rounded-card bg-pale text-brand transition-colors group-hover:bg-brand group-hover:text-white">
					<component :is="card.icon" class="size-6" aria-hidden="true" />
				</span>
				<span class="min-w-0">
					<span class="block text-[36px] font-medium leading-none text-navy">{{ card.value ?? 0 }}</span>
					<span class="mt-2 block text-[13px] tracking-caps text-subtle uppercase">{{ card.label }}</span>
				</span>
			</Link>
		</div>

		<div class="grid gap-6 items-start max-[1080px]:grid-cols-1" :class="recommended.length ? 'grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]' : 'grid-cols-1'">
			<section class="rounded-card bg-white overflow-hidden">
				<div class="flex items-center justify-between gap-4 px-7 pt-6 pb-4 max-[560px]:px-5">
					<h2 class="text-[20px] leading-[1.35] text-navy">{{ isAdmin ? 'Manage the catalog' : 'Recent applications' }}</h2>
					<Link v-if="!isAdmin && applications.length" class="landing-link !py-0 text-[13px]!" :href="route('applications.index')">View all<ArrowRight class="action-icon size-4" aria-hidden="true" /></Link>
				</div>

				<Link
					v-for="application in applications"
					:key="application.id"
					class="flex items-center gap-4 border-t border-line-2 px-7 py-4 transition-colors hover:bg-pale max-[560px]:px-5"
					:href="route('applications.show', application.id)"
				>
					<CompanyLogo class="size-11 rounded-card text-[14px]" :name="partyName(application)" :src="isCompany ? null : application.company?.image" />
					<div class="flex-1 min-w-0">
						<b class="block truncate text-[16px] font-medium text-navy">{{ application.opportunity.title }}</b>
						<span class="mt-0.5 block truncate text-[14px] text-subtle">{{ [partyName(application), application.opportunity.city?.name].filter(Boolean).join(', ') }}</span>
					</div>
					<StatusPill :status="application.status" />
					<span class="text-[13.5px] text-subtle-2 whitespace-nowrap max-[760px]:hidden">{{ application.created_at }}</span>
					<ChevronRight class="size-4 text-subtle-2 shrink-0 max-[760px]:hidden" aria-hidden="true" />
				</Link>

				<div v-if="!applications.length" class="px-7 pb-7 text-[15px] leading-[1.7] text-subtle max-[560px]:px-5">
					<div v-if="isAdmin" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
						<Link v-for="item in adminLinks" :key="item.route" class="group flex items-center gap-3 rounded-card bg-pale px-4 py-3.5 text-navy transition-colors hover:bg-brand hover:text-white" :href="route(item.route)">
							<component :is="item.icon" class="size-5 text-brand transition-colors group-hover:text-white" aria-hidden="true" />{{ item.label }}
						</Link>
					</div>
					<p v-else-if="isCompany">No applications yet. Keep your <Link class="text-brand hover:underline" :href="managedRolesHref">role details</Link> up to date to attract candidates.</p>
					<p v-else>You have not applied anywhere yet. <Link class="text-brand hover:underline" :href="route('app.opportunities.index')">Find a role</Link> to get started.</p>
				</div>
			</section>

			<section v-if="recommended.length" class="rounded-card bg-white overflow-hidden">
				<div class="flex items-center justify-between gap-4 px-7 pt-6 max-[560px]:px-5">
					<h2 class="text-[20px] leading-[1.35] text-navy">Picked for you</h2>
					<Link class="landing-link !py-0 text-[13px]!" :href="route('settings', { tab: 'preferences' })">Preferences</Link>
				</div>
				<p class="px-7 pt-2 pb-3 text-[14px] leading-[1.6] text-subtle max-[560px]:px-5">{{ recommendation_reasons.join('. ') }}</p>
				<Link
					v-for="item in recommended"
					:key="item.id"
					class="flex items-center gap-3.5 border-t border-line-2 px-7 py-3.5 transition-colors hover:bg-pale max-[560px]:px-5"
					:href="appPath(item.public_url) || route('app.opportunities.show', item.id)"
				>
					<CompanyLogo class="size-10 rounded-card text-[13px]" :name="item.company?.name || ''" :src="item.company?.image" />
					<div class="min-w-0">
						<b class="block truncate text-[15px] font-medium text-navy">{{ item.title }}</b>
						<span class="mt-0.5 block truncate text-[13.5px] text-subtle">{{ [item.company?.name, item.location_label || item.city?.name].filter(Boolean).join(', ') }}</span>
					</div>
				</Link>
			</section>
		</div>
	</div>
</template>
