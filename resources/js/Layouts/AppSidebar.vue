<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
	Bookmark,
	BookOpen,
	Briefcase,
	Building2,
	CircleUserRound,
	FileText,
	GraduationCap,
	LayoutDashboard,
	LogOut,
	MessageSquare,
	Settings,
	Sparkles,
	X,
} from '@lucide/vue';

defineProps({
	active: { type: String, default: '' },
	mobileOpen: { type: Boolean, default: false },
});

defineEmits(['close']);

const navIcons = {
	dashboard: LayoutDashboard,
	applications: FileText,
	internships: Briefcase,
	saved: Bookmark,
	messages: MessageSquare,
	companies: Building2,
	profile: CircleUserRound,
	settings: Settings,
	students: GraduationCap,
	fields: BookOpen,
	skills: Sparkles,
	'saved-searches': Bookmark,
	imports: Briefcase,
};

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isCompany = computed(() => user.value?.userable_type === 'company');
const isStudent = computed(() => user.value?.userable_type === 'student');
const isAdmin = computed(() => !!user.value?.is_admin);

// Students and professionals share candidate accounts and saved opportunities.
const groups = computed(() => {
	const workspace = [
		{ id: 'dashboard', label: 'Dashboard', route: 'dashboard' },
		...(isAdmin.value ? [] : [{ id: 'applications', label: 'Applications', route: 'applications.index' }]),
		{ id: 'internships', label: 'Opportunities', route: 'opportunities.index' },
		...(isStudent.value ? [{ id: 'saved', label: 'Saved roles', route: 'likes.index' }] : []),
		...(isStudent.value ? [{ id: 'saved-searches', label: 'Saved searches', route: 'saved-searches.index' }] : []),
		{ id: 'messages', label: 'Messages', route: 'messages.index' },
		{ id: 'companies', label: 'Companies', route: 'companies.index' },
	];

	const account = [
		{ id: 'profile', label: 'Profile', route: 'profile.show' },
		{ id: 'settings', label: 'Settings', route: 'settings' },
	];

	const sections = [{ label: 'Workspace', items: workspace }];
	if (isAdmin.value) {
		sections.push({
			label: 'Catalog',
			items: [
				{ id: 'imports', label: 'Sources and imports', route: 'imports.index' },
				{ id: 'students', label: 'Candidates', route: 'students.index' },
				{ id: 'fields', label: 'Fields', route: 'fields.index' },
				{ id: 'skills', label: 'Skills', route: 'skills.index' },
			],
		});
	}
	sections.push({ label: 'Account', items: account });
	return sections;
});
const tag = computed(() =>
	isAdmin.value ? 'ADMIN WORKSPACE' : isCompany.value ? 'COMPANY WORKSPACE' : 'CANDIDATE WORKSPACE'
);
const roleLabel = computed(() => (isAdmin.value ? 'Admin' : isCompany.value ? 'Company' : (user.value?.career_stage === 'professional' ? 'Professional' : user.value?.career_stage === 'graduate' ? 'Graduate' : 'Student')));

const initials = computed(() =>
	(user.value?.name || '')
		.split(' ')
		.map((w) => w[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);

// Counts arrive via the shared `badges` prop; `saved` falls back to the
// like count that is already shared on auth.user.
const badge = (item) => {
	const badges = page.props.badges || {};
	if (badges[item.id]) return badges[item.id];
	if (item.id === 'saved') return user.value?.likes_count || null;
	return null;
};

const hrefFor = (item) => (route().has(item.route) ? route(item.route) : '#');

const logout = () => router.post(route('logout'));
</script>

<template>
	<aside id="workspace-navigation" class="flex flex-col sticky top-0 h-dvh bg-navy-900 text-[#aeb9cf] px-4 py-[22px] overflow-y-auto max-[900px]:fixed max-[900px]:left-0 max-[900px]:z-50 max-[900px]:w-[248px]" :class="{ 'max-[900px]:hidden': !mobileOpen }">
		<div class="flex items-center justify-between px-2 pt-1.5 pb-[26px]">
			<Link :href="route('home')"><BrandLogo dark size="md" :tagline="tag" /></Link>
			<button class="hidden max-[900px]:grid place-items-center w-8 h-8 rounded-lg hover:bg-white/10" type="button" aria-label="Close navigation" @click="$emit('close')"><X :size="20" /></button>
		</div>

		<template v-for="group in groups" :key="group.label">
			<div class="text-[11px] tracking-[.13em] uppercase text-[#5d6a85] font-bold px-3 pt-[18px] pb-2">{{ group.label }}</div>
			<nav class="flex flex-col gap-[3px]">
				<Link
					v-for="item in group.items"
					:key="item.id"
					class="flex items-center gap-3 px-[13px] py-[11px] rounded-[11px] text-[14.5px] font-medium transition-colors [&_svg]:w-[19px] [&_svg]:h-[19px] [&_svg]:shrink-0"
					:class="item.id === active ? 'bg-blue-600 text-white' : 'text-[#aeb9cf] hover:bg-white/[.06] hover:text-white'"
					:href="hrefFor(item)"
					@click="$emit('close')"
				>
					<component :is="navIcons[item.id]" :size="19" :stroke-width="2" />
					<span>{{ item.label }}</span>
					<span
						v-if="badge(item)"
						class="ml-auto text-[11px] font-bold text-white min-w-[20px] h-5 rounded-full grid place-items-center px-1.5"
						:class="item.id === active ? 'bg-white/25' : 'bg-blue-600'"
					>{{ badge(item) }}</span>
				</Link>
			</nav>
		</template>

		<div class="flex-1"></div>
		<div class="flex items-center gap-[11px] px-3 py-[11px] rounded-[12px] bg-white/5 mt-2">
			<span class="w-9 h-9 rounded-full grid place-items-center text-white font-bold font-display text-sm bg-gradient-to-br from-blue-300 to-blue-700 shrink-0">{{ initials }}</span>
			<div class="min-w-0">
				<b class="block text-white text-[13.5px] truncate">{{ user?.name }}</b>
				<span class="text-xs text-[#7c89a3]">{{ roleLabel }}</span>
			</div>
			<button
				class="ml-auto w-[34px] h-[34px] rounded-[9px] grid place-items-center text-[#7c89a3] bg-transparent border-0 cursor-pointer transition-colors hover:bg-red-500/[.16] hover:text-red-300"
				type="button"
				title="Log out"
				aria-label="Log out"
				@click="logout"
			><LogOut :size="18" /></button>
		</div>
	</aside>
</template>
