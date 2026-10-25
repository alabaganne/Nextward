<script setup>
import { ArrowUpRight, CalendarDays, ChevronRight, Clock3, MapPin, Users } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyFacts from '@/Components/Company/CompanyFacts.vue';
import CompanyHiring from '@/Components/Company/CompanyHiring.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';
import { gradientFor } from '@/utils/brand';
import { appPath } from '@/utils/url';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'companies' }, () => page),
});

const props = defineProps({
	company: { type: Object, required: true },
});

const icons = {
	chevron: ChevronRight,
	pin: MapPin,
	clock: Clock3,
};
const button = 'inline-flex items-center justify-center gap-1.5 font-semibold text-sm px-4 py-2.5 rounded-[10px] transition-colors';

const opportunities = computed(() => props.company.opportunities?.data ?? props.company.opportunities ?? []);
const pagination = computed(() => props.company.opportunities?.meta);
const totalRoles = computed(() => pagination.value?.total ?? opportunities.value.length);
const page = usePage();
const hiring = computed(() => props.company.hiring || { open: 0, jobs: 0, internships: 0 });
const headline = computed(() => {
	const fields = (hiring.value.fields || []).slice(0, 2).map((field) => field.name);
	return fields.length ? `Hiring in ${fields.join(' and ')}` : { startup: 'Startup', established: 'Established company' }[props.company.stage] || null;
});
const ownsCompany = computed(() => page.props.auth?.user?.id === props.company.user_id);
const canEdit = computed(() => ownsCompany.value || page.props.auth?.user?.is_admin);
const canManageRole = (role) => canEdit.value && (page.props.auth?.user?.is_admin || role.origin === 'native');
const roleHref = (role) => canManageRole(role)
	? route('opportunities.edit', role.id)
	: role.publication_status === 'published' ? appPath(role.public_url) : null;
const roleStatus = (role) => role.publication_status === 'draft' ? 'Draft'
	: role.publication_status === 'suppressed' ? 'Suppressed'
	: role.is_open ? 'Open' : 'Closed';
const canClaim = computed(() => page.props.auth?.user?.userable_type === 'company' && !props.company.user_id && !props.company.claimed_by_company_id);
const claimForm = useForm({ evidence: '' });
const requestClaim = () => claimForm.post(route('companies.claim', props.company.id), { preserveScroll: true, onSuccess: () => claimForm.reset() });
const roleInitials = (title) =>
	title
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase();
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('app.companies.index')">Companies</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ company.name }}</span>
		</div>

		<ProfileBanner class="mb-6" :name="company.name" :headline="headline" :items="[
			{ icon: icons.pin, label: company.city?.name || company.location },
			{ icon: Users, label: company.size && `${company.size} people` },
			{ icon: CalendarDays, label: company.founded_year && `Founded in ${company.founded_year}` },
		]">
			<template #avatar><CompanyLogo class="w-[78px] h-[78px] rounded-[20px] text-[28px]" :name="company.name" :src="company.image" /></template>
			<template #badge><VerifiedBadge v-if="company.verified" dark class="text-xs!" /></template>
			<Link v-if="canEdit" :class="[button, 'bg-blue-600 text-white hover:bg-blue-700']" :href="route('companies.edit', company.id)">Edit company</Link>
			<Link v-else-if="hiring.open" :class="[button, 'bg-blue-600 text-white hover:bg-blue-700']" :href="route('app.opportunities.index', { companies: [company.id] })">View {{ hiring.open }} open {{ hiring.open === 1 ? 'role' : 'roles' }}</Link>
			<Link v-if="!ownsCompany && company.user_id" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']" :href="route('messages.show', company.user_id)">Message company</Link>
			<a v-if="company.website" :href="company.website" target="_blank" rel="noopener noreferrer" :class="[button, 'bg-white text-navy-900 hover:bg-[#eef2f7]']">Website<ArrowUpRight :size="15" aria-hidden="true" /></a>
		</ProfileBanner>

		<div class="grid grid-cols-[minmax(0,1fr)_320px] gap-6 items-start max-[1080px]:grid-cols-1">
			<div class="flex flex-col gap-6 min-w-0">
				<section class="bg-white border border-line rounded-[18px] p-[26px]">
					<h2 class="font-display text-[18px] font-semibold mb-3">About {{ company.name }}</h2>
					<p v-if="company.about" class="text-[15px] leading-[1.72] text-ink-700 whitespace-pre-line" dir="auto">{{ company.about }}</p>
					<p v-else class="text-[15px] text-slate-400">No description yet.<Link v-if="canEdit" :href="route('companies.edit', company.id)" class="ml-1.5 font-semibold text-blue-700 hover:underline">Add one</Link></p>
				</section>

				<section id="opportunities" class="bg-white border border-line rounded-[18px] scroll-mt-24">
					<div class="flex items-center justify-between px-[26px] pt-[22px]">
						<h2 class="font-display text-[18px] font-semibold">{{ canEdit ? 'Manage opportunities' : 'Open opportunities' }}</h2>
						<span class="text-[13px] text-muted font-semibold bg-paper-2 border border-line-2 px-[11px] py-1 rounded-full">{{ totalRoles }} {{ totalRoles === 1 ? 'role' : 'roles' }}</span>
					</div>
					<div class="px-3.5 pt-2.5 pb-3.5">
						<component
							:is="roleHref(opportunity) ? Link : 'div'"
							v-for="(opportunity, index) in opportunities"
							:key="opportunity.id"
							class="flex items-center gap-4 px-3 py-[18px] rounded-[14px] transition-colors no-underline"
							:class="{ 'border-t border-line-2': index !== 0, 'hover:bg-paper-2': roleHref(opportunity) }"
							:href="roleHref(opportunity) || undefined"
						>
							<span class="w-[46px] h-[46px] rounded-[12px] grid place-items-center text-white font-bold font-display text-base shrink-0" :style="{ background: gradientFor(company.name) }">{{ roleInitials(opportunity.title) }}</span>
							<span class="flex-1 min-w-0">
								<b class="text-base font-semibold block">{{ opportunity.title }}</b>
								<span class="flex flex-wrap gap-3.5 mt-[7px] text-muted text-[13px] [&_span]:inline-flex [&_span]:items-center [&_span]:gap-[5px] [&_svg]:w-[13px] [&_svg]:h-[13px] [&_svg]:text-muted-2">
									<span>{{ opportunity.kind === 'job' ? 'Job' : 'Internship' }}</span>
									<span><component :is="icons.pin" aria-hidden="true" />{{ opportunity.location_label }}</span>
									<span><component :is="icons.clock" aria-hidden="true" />{{ opportunity.closing_at ? `Closes ${opportunity.closing_at}` : 'No deadline listed' }}</span>
									<span class="font-semibold">{{ roleStatus(opportunity) }}</span>
								</span>
								<span v-if="canManageRole(opportunity)" class="block mt-2 text-[13px] text-blue-700 font-semibold">Edit opportunity</span>
							</span>
						</component>
						<div v-if="!opportunities.length" class="px-3 py-6 text-[14px] text-slate-400">No opportunities posted yet.</div>
						<Pagination v-if="pagination" :meta="pagination" />
					</div>
				</section>
			</div>

			<div class="flex flex-col gap-6 min-w-0">
				<form v-if="canClaim" class="bg-white border border-line rounded-[18px] px-6 py-[22px]" @submit.prevent="requestClaim">
					<h3 class="font-display font-semibold mb-2">Represent this company?</h3>
					<p class="text-sm text-muted mb-4">Request a review of your connection to this employer. Imported applications keep their original destination.</p>
					<label class="text-sm font-semibold">Proof of ownership<textarea v-model="claimForm.evidence" class="fld mt-2" required maxlength="3000" placeholder="Describe your company domain and how your connection can be verified."></textarea></label>
					<p v-if="claimForm.errors.evidence" class="text-sm text-red-600 mt-2" role="alert">{{ claimForm.errors.evidence }}</p>
					<button type="submit" class="cbtn bg-blue-600 text-white mt-3" :disabled="claimForm.processing">Request company review</button>
				</form>
				<CompanyHiring :hiring="hiring" />
				<CompanyFacts :company="company" />
			</div>
		</div>
	</div>
</template>
