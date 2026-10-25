<script setup>
import { ArrowRight, Briefcase, ChevronRight, Clock3, Globe2, Mail, MapPin, Phone } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
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
	briefcase: Briefcase,
	globe: Globe2,
	clock: Clock3,
	mail: Mail,
	phone: Phone,
	arrow: ArrowRight,
};

const opportunities = computed(() => props.company.opportunities?.data ?? props.company.opportunities ?? []);
const pagination = computed(() => props.company.opportunities?.meta);
const totalRoles = computed(() => pagination.value?.total ?? opportunities.value.length);
const page = usePage();
const logoFailed = ref(false);
const facts = computed(() => [
    { startup: 'Startup', established: 'Established company' }[props.company.stage],
    props.company.size && `${props.company.size} people`,
    props.company.founded_year && `Founded in ${props.company.founded_year}`,
].filter(Boolean));
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

		<!-- BANNER -->
		<div class="relative bg-gradient-to-br from-navy-800 to-navy-900 rounded-[26px] px-[34px] py-8 overflow-hidden mb-6">
			<div class="absolute inset-0 bg-[radial-gradient(560px_240px_at_12%_-40%,rgba(59,130,246,.32),transparent_70%)]"></div>
			<div class="relative z-[1] flex items-center gap-5 flex-wrap">
				<img v-if="company.image && !logoFailed" :src="company.image" :alt="`${company.name} logo`" width="78" height="78" class="w-[78px] h-[78px] rounded-[20px] object-contain bg-white shrink-0" @error="logoFailed = true">
				<div v-else class="w-[78px] h-[78px] rounded-[20px] grid place-items-center text-white font-bold font-display text-[30px] shrink-0" :style="{ background: gradientFor(company.name) }">{{ company.name.slice(0, 1).toUpperCase() }}</div>
				<div>
					<div class="flex flex-wrap items-center gap-x-3 gap-y-2">
						<h1 class="font-display text-white text-[30px] font-semibold leading-[1.1]">{{ company.name }}</h1>
						<VerifiedBadge v-if="company.verified" dark class="text-xs!" />
					</div>
					<div class="flex items-center gap-3.5 flex-wrap mt-2.5 text-[#aebfdd] text-sm [&_span]:inline-flex [&_span]:items-center [&_span]:gap-1.5 [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:opacity-80">
						<span v-if="company.city?.name"><component :is="icons.pin" aria-hidden="true" />{{ company.city.name }}</span>
						<span v-if="company.website"><component :is="icons.globe" aria-hidden="true" />{{ company.website }}</span>
						<span v-for="fact in facts" :key="fact">{{ fact }}</span>
					</div>
				</div>
				<div class="ml-auto flex gap-2.5 max-[760px]:ml-0 max-[760px]:w-full">
					<Link v-if="canEdit" class="inline-flex items-center justify-center font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('companies.edit', company.id)">Edit company</Link>
					<Link v-if="!ownsCompany && company.user_id" class="inline-flex items-center justify-center font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-navy-900 hover:bg-[#eef2f7] transition-colors" :href="route('messages.show', company.user_id)">Message company</Link>
				</div>
			</div>
		</div>

		<div class="grid grid-cols-[1fr_320px] gap-6 items-start max-[1080px]:grid-cols-1">
			<!-- LEFT -->
			<div class="flex flex-col gap-6">
				<!-- ABOUT -->
				<section v-if="company.about" class="bg-white border border-line rounded-[18px] p-[26px]">
					<h2 class="font-display text-[18px] font-semibold mb-3">About {{ company.name }}</h2>
					<p class="text-[15px] leading-[1.72] text-ink-700 whitespace-pre-line">{{ company.about }}</p>
				</section>

				<!-- OPPORTUNITIES -->
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
									<span><component :is="icons.pin" aria-hidden="true" />{{ opportunity.location_label }}</span>
									<span><component :is="icons.clock" aria-hidden="true" />{{ opportunity.closing_at ? `Closes ${opportunity.closing_at}` : 'No deadline listed' }}</span>
									<span class="font-semibold">{{ roleStatus(opportunity) }}</span>
								</span>
								<span v-if="canManageRole(opportunity)" class="block mt-2 text-[13px] text-blue-700 font-semibold">Edit opportunity</span>
							</span>
						</component>
						<div v-if="!opportunities.length" class="px-3 py-6 text-[14px] text-muted">No opportunities posted yet.</div>
						<Pagination v-if="pagination" :meta="pagination" />
					</div>
				</section>
			</div>

			<!-- RIGHT -->
			<div class="flex flex-col gap-4 sticky top-[94px]">
				<form v-if="canClaim" class="bg-white border border-line rounded-[18px] px-6 py-[22px]" @submit.prevent="requestClaim">
					<h3 class="font-display font-semibold mb-2">Represent this company?</h3>
					<p class="text-sm text-muted mb-4">Request a review of your connection to this employer. Imported applications keep their original destination.</p>
					<label class="text-sm font-semibold">Proof of ownership<textarea v-model="claimForm.evidence" class="fld mt-2" required maxlength="3000" placeholder="Describe your company domain and how your connection can be verified."></textarea></label>
					<p v-if="claimForm.errors.evidence" class="text-sm text-red-600 mt-2" role="alert">{{ claimForm.errors.evidence }}</p>
					<button type="submit" class="cbtn bg-blue-600 text-white mt-3" :disabled="claimForm.processing">Request company review</button>
				</form>
				<section class="bg-white border border-line rounded-[18px] px-6 py-[22px]">
					<h3 class="text-sm tracking-[.04em] text-muted font-bold font-display mb-4">OVERVIEW</h3>
					<div class="flex gap-3">
						<div class="flex-1 text-center bg-paper-2 border border-line-2 rounded-[12px] px-2 py-4">
							<div class="font-display text-2xl font-semibold leading-none">{{ totalRoles }}</div>
							<div class="text-xs text-muted mt-1.5">Roles</div>
						</div>
					</div>
					<Link class="w-full justify-center mt-[18px] inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="canEdit ? `${route('companies.show', company.id)}#opportunities` : route('app.opportunities.index', { companies: [company.id] })">
						{{ canEdit ? 'Manage opportunities' : 'View open roles' }} <component :is="icons.arrow" aria-hidden="true" />
					</Link>
				</section>

				<section class="bg-white border border-line rounded-[18px] px-6 py-[22px]">
					<h3 class="text-sm tracking-[.04em] text-muted font-bold font-display mb-4">CONTACT</h3>
					<div v-if="company.email" class="flex items-center gap-[13px] py-3 text-sm text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.mail" aria-hidden="true" />{{ company.email }}</div>
					<div v-if="company.website" class="flex items-center gap-[13px] py-3 text-sm text-ink-700 border-t border-line-2 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.globe" aria-hidden="true" /><a class="text-blue-700 font-semibold" :href="company.website" target="_blank" rel="noopener">{{ company.website }}</a></div>
					<div v-if="company.linkedin_url" class="flex items-center gap-[13px] py-3 text-sm text-ink-700 border-t border-line-2 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.globe" aria-hidden="true" /><a class="text-blue-700 font-semibold" :href="company.linkedin_url" target="_blank" rel="noopener nofollow">LinkedIn</a></div>
					<div v-if="company.phone_number" class="flex items-center gap-[13px] py-3 text-sm text-ink-700 border-t border-line-2 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.phone" aria-hidden="true" />{{ company.phone_number }}</div>
					<div v-if="company.city?.name" class="flex items-center gap-[13px] py-3 text-sm text-ink-700 border-t border-line-2 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.pin" aria-hidden="true" />{{ company.city.name }}</div>
				</section>
			</div>
		</div>
	</div>
</template>
