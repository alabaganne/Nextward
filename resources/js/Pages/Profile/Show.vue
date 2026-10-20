<script setup>
import { ArrowUpRight, Check, GraduationCap, Link2, Mail, MapPin, Pencil } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';

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
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1120px] mx-auto">
		<ProfileBanner class="mb-6" :name="profile.name" :headline="headline" :items="bannerItems">
			<Link class="inline-flex items-center justify-center gap-2 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors [&_svg]:w-4 [&_svg]:h-4" :href="route('settings')">
				<component :is="icons.pencil" aria-hidden="true" />
				Edit profile
			</Link>
		</ProfileBanner>

		<div class="grid grid-cols-[1.65fr_1fr] gap-[18px] items-start max-[980px]:grid-cols-1">
			<!-- LEFT -->
			<div>
				<!-- about -->
				<section v-if="isCandidate || isCompany" class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
						<h3 class="font-display text-[16.5px] font-semibold">About</h3>
						<Link class="inline-flex items-center gap-[7px] text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] cursor-pointer hover:bg-blue-100 transition-colors [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings')"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
					</div>
					<div class="px-[22px] py-5">
						<p v-if="profile.about" class="text-[14.5px] text-ink-700 leading-[1.7] whitespace-pre-line">{{ profile.about }}</p>
						<p v-else class="text-[14.5px] text-muted leading-[1.7]">
							Nothing here yet.
							<Link class="font-semibold text-blue-700" :href="route('settings')">Write a short bio</Link>
							so {{ isCandidate ? 'recruiters know who you are' : 'candidates know what you do' }}.
						</p>
					</div>
				</section>

				<!-- skills -->
				<section v-if="isCandidate" class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
						<h3 class="font-display text-[16.5px] font-semibold">Skills</h3>
						<Link class="inline-flex items-center gap-[7px] text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] cursor-pointer hover:bg-blue-100 transition-colors [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings')"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
					</div>
					<div class="px-[22px] py-5">
						<div v-if="profile.skills?.length" class="flex flex-wrap gap-[9px]">
							<span v-for="(skill, index) in profile.skills" :key="skill" class="inline-flex items-center text-[13px] font-semibold px-3.5 py-2 rounded-[9px]" :class="index < 3 ? 'text-blue-700 bg-blue-50' : 'text-ink-700 bg-paper-3'">{{ skill }}</span>
						</div>
						<p v-else class="text-[14.5px] text-muted leading-[1.7]">No skills added yet.</p>
					</div>
				</section>

				<!-- experience, education, projects -->
				<template v-if="isCandidate">
					<section v-for="list in history" :key="list.key" class="bg-white border border-line rounded-[18px] mb-[18px]">
						<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
							<h3 class="font-display text-[16.5px] font-semibold">{{ list.title }}</h3>
							<Link class="inline-flex items-center gap-[7px] text-[13px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-[7px] rounded-[9px] cursor-pointer hover:bg-blue-100 transition-colors [&_svg]:w-3.5 [&_svg]:h-3.5" :href="route('settings')"><component :is="icons.pencil" aria-hidden="true" /> Edit</Link>
						</div>
						<ul class="px-[22px] py-2">
							<li v-for="(row, index) in list.rows" :key="index" class="py-3.5 border-b border-line-2 last:border-b-0">
								<div class="flex items-baseline justify-between gap-3 flex-wrap">
									<b class="text-[14.5px] font-semibold">{{ row.title }}</b>
									<span v-if="row.dates" class="text-[12.5px] text-muted">{{ row.dates }}</span>
								</div>
								<p v-if="row.sub" class="text-[13.5px] text-ink-700 mt-0.5">{{ row.sub }}</p>
								<p v-if="row.body" class="text-[13.5px] text-muted mt-1.5 leading-[1.6] whitespace-pre-line">{{ row.body }}</p>
								<a v-if="row.url" :href="row.url" target="_blank" rel="noopener" class="text-[13px] font-semibold text-blue-700 hover:underline mt-1 inline-block">{{ row.url }}</a>
							</li>
						</ul>
					</section>
				</template>

				<!-- company details -->
				<section v-if="isCompany" class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
						<h3 class="font-display text-[16.5px] font-semibold">Company details</h3>
					</div>
					<div class="px-[22px] py-5 [&_.prow]:flex [&_.prow]:items-center [&_.prow]:justify-between [&_.prow]:gap-3.5 [&_.prow]:py-[13px] [&_.prow]:border-b [&_.prow]:border-line-2 [&_.prow]:text-sm [&_.prow:first-child]:pt-0 [&_.prow:last-child]:border-b-0 [&_.prow:last-child]:pb-0 [&_.k]:text-muted [&_.k]:font-medium [&_.v]:font-semibold [&_.v]:text-ink [&_.v]:text-right">
						<div v-if="profile.website" class="prow"><span class="k">Website</span><a class="v text-blue-700" :href="profile.website" target="_blank" rel="noopener">{{ profile.website }}</a></div>
						<div v-if="profile.city?.name" class="prow"><span class="k">Location</span><span class="v">{{ profile.city.name }}</span></div>
						<div class="prow"><span class="k">Opportunities</span><span class="v">{{ profile.opportunities_count ?? 0 }}</span></div>
					</div>
				</section>
			</div>

			<!-- RIGHT -->
			<div>
				<!-- profile strength -->
				<section class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="px-[22px] py-5">
						<h3 class="font-display text-base font-semibold mb-1">Profile strength</h3>
						<p class="text-[13px] text-muted mb-4">Give companies a clearer picture of your background.</p>
						<div class="h-[9px] rounded-full bg-paper-3 overflow-hidden mb-2.5"><i class="block h-full rounded-full bg-gradient-to-r from-blue-400 to-blue-600" :style="{ width: strength + '%' }"></i></div>
						<div class="flex justify-between text-[13px] font-semibold"><span class="text-muted">Completeness</span><span class="text-blue-700">{{ strength }}%</span></div>
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
				<section v-if="profile.linkedin_profile_url" class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2">
						<h3 class="font-display text-[16.5px] font-semibold">Links</h3>
					</div>
					<div class="px-[22px] py-5">
						<a class="flex items-center gap-[13px] border border-line rounded-[13px] px-[15px] py-[13px] transition-colors hover:border-blue-300 group" :href="profile.linkedin_profile_url" target="_blank" rel="noopener">
							<span class="w-[42px] h-[42px] rounded-[10px] grid place-items-center text-[11px] font-bold font-display bg-blue-50 text-blue-700 shrink-0"><Link2 :size="20" aria-hidden="true" /></span>
							<span class="flex-1 min-w-0"><b class="text-sm font-semibold block">LinkedIn</b><span class="text-[12.5px] text-muted truncate block">{{ profile.linkedin_profile_url }}</span></span>
							<span class="text-muted-2 group-hover:text-blue-700 w-[34px] h-[34px] rounded-[9px] grid place-items-center shrink-0 [&_svg]:w-[17px] [&_svg]:h-[17px]"><component :is="icons.external" aria-hidden="true" /></span>
						</a>
					</div>
				</section>

				<!-- activity -->
				<section v-if="isCandidate" class="bg-white border border-line rounded-[18px] mb-[18px]">
					<div class="flex items-center justify-between px-[22px] py-[18px] border-b border-line-2"><h3 class="font-display text-[16.5px] font-semibold">Your activity</h3></div>
					<div class="px-[22px] py-5">
						<div class="flex text-center [&>div]:flex-1 [&>div]:px-1.5 [&>div]:py-1 [&>div+div]:border-l [&>div+div]:border-line-2 [&_b]:font-display [&_b]:text-2xl [&_b]:font-semibold [&_b]:block [&_span]:text-xs [&_span]:text-muted [&_span]:mt-0.5 [&_span]:block">
							<div><b>{{ profile.applications_count ?? 0 }}</b><span>Applications</span></div>
							<div><b>{{ profile.likes_count ?? 0 }}</b><span>Saved roles</span></div>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
