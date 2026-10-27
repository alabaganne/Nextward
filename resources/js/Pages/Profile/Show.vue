<script setup>
import { ArrowUpRight, Check, Eye, GraduationCap, Link2, Mail, MapPin, Pencil } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';
import SkillList from '@/Components/Candidate/SkillList.vue';
import ResumeList from '@/Components/Candidate/ResumeList.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'profile' }, () => page),
});

const props = defineProps({
	profile: { type: Object, required: true },
});

const icons = {
	pencil: Pencil,
	check: Check,
	external: ArrowUpRight,
};

const isCandidate = computed(() => props.profile.userable_type === 'candidate');
const isCompany = computed(() => props.profile.userable_type === 'company');
const headline = computed(() =>
	isCandidate.value
		? props.profile.headline || props.profile.field?.name || 'Candidate'
		: isCompany.value ? 'Company' : 'Administrator'
);
const bannerItems = computed(() => [
	{ icon: MapPin, label: [props.profile.location || props.profile.city?.name, props.profile.country_code].filter(Boolean).join(', ') },
	{ icon: GraduationCap, label: isCandidate.value ? props.profile.field?.name : null },
	{ icon: Mail, label: props.profile.email },
]);

// Honest completeness: computed from what's actually filled in.
const checklist = computed(() => {
	const items = [
		{ label: 'Add basic details', done: !!(props.profile.name && props.profile.email) },
		{ label: 'Add a LinkedIn profile', done: !!props.profile.linkedin_profile_url },
	];
	if (isCandidate.value || isCompany.value) {
		items.splice(1, 0,
			{ label: 'Add your location', done: !!(props.profile.city?.name || props.profile.location || props.profile.country_code) },
			{ label: 'Write a short bio', done: !!props.profile.about }
		);
	}
	if (isCandidate.value) {
		items.splice(2, 0, { label: 'Pick your field', done: !!props.profile.field?.name });
		items.push({ label: 'Add skills', done: (props.profile.skills || []).length > 0 });
		items.push({ label: 'Add your experience or studies', done: (props.profile.experience || []).length + (props.profile.education || []).length > 0 });
		items.push({ label: 'Add a resume', done: (props.profile.resumes || []).some((resume) => resume.is_main) });
	} else if (isCompany.value) {
		items.push({ label: 'Add a website', done: !!props.profile.website });
	}
	return items;
});
const dates = (row) => [row.start, row.current ? 'Now' : row.end].filter(Boolean).join(' – ');
const history = computed(() => [
	{ key: 'experience', title: 'Experience', rows: (props.profile.experience || []).map((row) => ({ title: row.title, sub: [row.company, row.location].filter(Boolean).join(' · '), dates: dates(row), body: row.summary })) },
	{ key: 'education', title: 'Education', rows: (props.profile.education || []).map((row) => ({ title: row.school, sub: [row.degree, row.field].filter(Boolean).join(' · '), dates: dates(row) })) },
	{ key: 'projects', title: 'Projects', rows: (props.profile.projects || []).map((row) => ({ title: row.name, body: row.summary, url: row.url })) },
].filter((list) => list.rows.length));
const strength = computed(() =>
	Math.round((checklist.value.filter((item) => item.done).length / checklist.value.length) * 100)
);

const publicBusy = ref(false);
const updatePublic = (data) =>
	router.put(route('profile.public.update'), data, {
		preserveScroll: true,
		onStart: () => (publicBusy.value = true),
		onFinish: () => (publicBusy.value = false),
	});
const publicState = computed(() => ({
	pending: { title: 'Waiting for review', text: 'The Nextward team is checking your profile. It stays private until it is approved.' },
	approved: { title: 'Your profile is public', text: 'Companies and visitors can find you on the Talent page.' },
	declined: { title: 'Not approved yet', text: 'Update your profile, then ask again.' },
}[props.profile.public_status] || { title: 'Let companies find you', text: 'Show your profile on the Talent page so companies can find you. The Nextward team checks each profile first. Your email, phone number and resume stay hidden unless you choose to share them.' }));
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1120px] mx-auto">
		<ProfileBanner class="mb-6" :name="profile.name" :headline="headline" :items="bannerItems">
			<span v-if="profile.public_status === 'approved'" class="self-center text-xs font-medium px-3 py-1.5 rounded-full bg-green-100 text-emerald-700">Public</span>
			<span v-else-if="profile.public_status === 'pending'" class="self-center text-xs font-medium px-3 py-1.5 rounded-full bg-amber-100 text-amber-700">Under review</span>
			<Link class="cta cta-primary cta-sm [&_svg]:w-4 [&_svg]:h-4" :href="route('settings')">
				<component :is="icons.pencil" aria-hidden="true" />
				Edit profile
			</Link>
		</ProfileBanner>

		<!-- public profile -->
		<section v-if="isCandidate" class="rounded-card mb-[18px]" :class="profile.public_status === 'approved' ? 'bg-emerald-50' : 'bg-white'" aria-labelledby="public-profile-title">
			<div class="px-7 py-6 flex items-center justify-between gap-4 flex-wrap max-[560px]:px-5">
				<div class="flex items-start gap-3.5 min-w-0 flex-1">
					<span class="w-10 h-10 rounded-card grid place-items-center shrink-0 text-white [&_svg]:w-5 [&_svg]:h-5" :class="profile.public_status === 'approved' ? 'bg-emerald-600' : 'bg-blue-600'"><Eye aria-hidden="true" /></span>
					<div class="min-w-0">
						<h2 id="public-profile-title" class="text-[20px] leading-[1.35] text-navy" role="status">{{ publicState.title }}</h2>
						<p class="text-[15px] leading-[1.6] text-subtle mt-1 max-w-[640px]">{{ publicState.text }}</p>
						<a v-if="profile.public_status" :href="profile.public_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[13.5px] font-medium text-blue-700 hover:underline mt-1.5">{{ profile.public_status === 'approved' ? 'Open your public page' : 'Preview your public page' }}<ArrowUpRight class="w-3.5 h-3.5" aria-hidden="true" /></a>
						<p v-if="!profile.public_status && !profile.email_verified" class="text-[13px] text-muted mt-1.5">
							<Link class="font-medium text-blue-700 hover:underline" :href="route('settings', { tab: 'security' })">Verify your email</Link> first.
						</p>
					</div>
				</div>
				<button v-if="profile.public_status === 'pending'" class="cta cta-secondary" type="button" :disabled="publicBusy" @click="updatePublic({ public: false })">Cancel request</button>
				<button v-else-if="profile.public_status === 'approved'" class="cta cta-secondary" type="button" :disabled="publicBusy" @click="updatePublic({ public: false })">Make it private</button>
				<button v-else class="cta cta-primary disabled:opacity-60" type="button" :disabled="publicBusy || !profile.email_verified" @click="updatePublic({ public: true })">{{ profile.public_status === 'declined' ? 'Ask again' : 'Ask to make it public' }}</button>
			</div>
			<label class="px-7 py-4 border-t flex items-start gap-3 cursor-pointer max-[560px]:px-5" :class="profile.public_status === 'approved' ? 'border-emerald-100' : 'border-line-2'">
				<input type="checkbox" class="mt-1 w-4 h-4 accent-blue-600 shrink-0" :checked="profile.public_contact" :disabled="publicBusy" @change="updatePublic({ contact: $event.target.checked })">
				<span>
					<b class="text-[14px] font-medium block">Share my main resume, email and phone number</b>
					<span class="text-[13px] text-muted block mt-0.5">Anyone who opens your public profile can download your resume and contact you. Turn this off at any time.</span>
				</span>
			</label>
		</section>

		<ResumeList v-if="isCandidate" :resumes="profile.resumes || []" :limit="profile.resume_limit" :email-verified="profile.email_verified" />

		<div class="grid grid-cols-[1.65fr_1fr] gap-[18px] items-start max-[980px]:grid-cols-1">
			<!-- LEFT -->
			<div>
				<!-- about -->
				<section v-if="isCandidate || isCompany" class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5">
						<h3 class="text-[20px] leading-[1.35] text-navy">About</h3>
						<Link class="cta cta-secondary cta-sm [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings')"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
					</div>
					<div class="px-[22px] py-5">
						<p v-if="profile.about" class="text-[14.5px] text-ink-700 leading-[1.7] whitespace-pre-line">{{ profile.about }}</p>
						<p v-else class="text-[14.5px] text-muted leading-[1.7]">
							Nothing here yet.
							<Link class="font-medium text-blue-700" :href="route('settings')">Write a short bio</Link>
							so {{ isCandidate ? 'recruiters know who you are' : 'candidates know what you do' }}.
						</p>
					</div>
				</section>

				<!-- skills -->
				<section v-if="isCandidate" class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5">
						<h3 class="text-[20px] leading-[1.35] text-navy">Skills</h3>
						<Link class="cta cta-secondary cta-sm [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings')"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
					</div>
					<div class="px-[22px] py-5">
						<SkillList v-if="profile.skills?.length || profile.languages?.length" :skills="profile.skills" :languages="profile.languages" />
						<p v-else class="text-[14.5px] text-muted leading-[1.7]">No skills added yet.</p>
					</div>
				</section>

				<!-- experience, education, projects -->
				<template v-if="isCandidate">
					<section v-for="list in history" :key="list.key" class="bg-white border border-transparent rounded-card mb-[18px]">
						<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5">
							<h3 class="text-[20px] leading-[1.35] text-navy">{{ list.title }}</h3>
							<Link class="cta cta-secondary cta-sm [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings', { tab: 'resume' })"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
						</div>
						<ul class="px-[22px] py-2">
							<li v-for="(row, index) in list.rows" :key="index" class="py-3.5 border-b border-line-2 last:border-b-0">
								<div class="flex items-baseline justify-between gap-3 flex-wrap">
									<b class="text-[14.5px] font-medium">{{ row.title }}</b>
									<span v-if="row.dates" class="text-[12.5px] text-muted">{{ row.dates }}</span>
								</div>
								<p v-if="row.sub" class="text-[13.5px] text-ink-700 mt-0.5">{{ row.sub }}</p>
								<p v-if="row.body" class="text-[13.5px] text-muted mt-1.5 leading-[1.6] whitespace-pre-line">{{ row.body }}</p>
								<a v-if="row.url" :href="row.url" target="_blank" rel="noopener" class="text-[13px] font-medium text-blue-700 hover:underline mt-1 inline-block">{{ row.url }}</a>
							</li>
						</ul>
					</section>
				</template>

				<!-- company details -->
				<section v-if="isCompany" class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5">
						<h3 class="text-[20px] leading-[1.35] text-navy">Company details</h3>
					</div>
					<div class="px-[22px] py-5 [&_.prow]:flex [&_.prow]:items-center [&_.prow]:justify-between [&_.prow]:gap-3.5 [&_.prow]:py-[13px] [&_.prow]:border-b [&_.prow]:border-line-2 [&_.prow]:text-sm [&_.prow:first-child]:pt-0 [&_.prow:last-child]:border-b-0 [&_.prow:last-child]:pb-0 [&_.k]:text-muted [&_.k]:font-medium [&_.v]:font-medium [&_.v]:text-ink [&_.v]:text-right">
						<div v-if="profile.website" class="prow"><span class="k">Website</span><a class="v text-blue-700" :href="profile.website" target="_blank" rel="noopener">{{ profile.website }}</a></div>
						<div v-if="profile.city?.name" class="prow"><span class="k">Location</span><span class="v">{{ profile.city.name }}</span></div>
						<div class="prow"><span class="k">Roles</span><span class="v">{{ profile.opportunities_count ?? 0 }}</span></div>
					</div>
				</section>
			</div>

			<!-- RIGHT -->
			<div>
				<!-- profile strength -->
				<section class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="px-[22px] py-5">
						<h3 class="text-base font-medium mb-1">Profile strength</h3>
						<p class="text-[13px] text-muted mb-4">Give companies a clearer picture of your background.</p>
						<div class="h-[9px] rounded-full bg-paper-3 overflow-hidden mb-2.5"><i class="block h-full rounded-full bg-brand" :style="{ width: strength + '%' }"></i></div>
						<div class="flex justify-between text-[13px] font-medium"><span class="text-muted">Completeness</span><span class="text-blue-700">{{ strength }}%</span></div>
						<ul class="list-none mt-4 flex flex-col gap-[11px] [&_li]:flex [&_li]:items-center [&_li]:gap-2.5 [&_li]:text-[13.5px] [&_li]:text-ink-700">
							<li v-for="item in checklist" :key="item.label">
								<span v-if="item.done" class="w-[18px] h-[18px] rounded-md grid place-items-center shrink-0 bg-blue-600 text-white [&_svg]:w-[11px] [&_svg]:h-[11px]"><component :is="icons.check" aria-hidden="true" /></span>
								<span v-else class="w-[18px] h-[18px] rounded-md grid place-items-center shrink-0 border-[1.5px] border-line"></span>
								<span :class="{ 'text-muted-2 line-through': item.done }">{{ item.label }}</span>
							</li>
						</ul>
					</div>
				</section>

				<!-- links -->
				<section v-if="profile.linkedin_profile_url" class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5">
						<h3 class="text-[20px] leading-[1.35] text-navy">Links</h3>
					</div>
					<div class="px-[22px] py-5">
						<a class="flex items-center gap-[13px] border border-line rounded-card px-[15px] py-[13px] transition-colors hover:border-blue-300 group" :href="profile.linkedin_profile_url" target="_blank" rel="noopener">
							<span class="w-[42px] h-[42px] rounded-card grid place-items-center text-[11px] font-medium bg-blue-50 text-blue-700 shrink-0"><Link2 :size="20" aria-hidden="true" /></span>
							<span class="flex-1 min-w-0"><b class="text-sm font-medium block">LinkedIn</b><span class="text-[12.5px] text-muted truncate block">{{ profile.linkedin_profile_url }}</span></span>
							<span class="text-muted-2 group-hover:text-blue-700 w-[34px] h-[34px] rounded-[9px] grid place-items-center shrink-0 [&_svg]:w-[17px] [&_svg]:h-[17px]"><component :is="icons.external" aria-hidden="true" /></span>
						</a>
					</div>
				</section>

				<!-- activity -->
				<section v-if="isCandidate" class="bg-white border border-transparent rounded-card mb-[18px]">
					<div class="flex items-center justify-between px-7 pt-6 pb-1 max-[560px]:px-5"><h3 class="text-[20px] leading-[1.35] text-navy">Your activity</h3></div>
					<div class="px-[22px] py-5">
						<div class="flex text-center [&>div]:flex-1 [&>div]:px-1.5 [&>div]:py-1 [&>div+div]:border-l [&>div+div]:border-line-2 [&_b]:text-2xl [&_b]:font-medium [&_b]:block [&_span]:text-xs [&_span]:text-muted [&_span]:mt-0.5 [&_span]:block">
							<div><b>{{ profile.applications_count ?? 0 }}</b><span>Applications</span></div>
							<div><b>{{ profile.likes_count ?? 0 }}</b><span>Saved roles</span></div>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
