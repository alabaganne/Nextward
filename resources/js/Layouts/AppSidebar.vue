<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
	Bookmark,
	BookOpen,
	Briefcase,
	Building2,
	ChevronDown,
	Compass,
	Database,
	FileText,
	FileUser,
	GraduationCap,
	LayoutDashboard,
	MessageSquare,
	SearchCheck,
	Sparkles,
	Target,
	UserPlus,
	Users,
	X,
} from '@lucide/vue';

const props = defineProps({
	active: { type: String, default: '' },
	mobileOpen: { type: Boolean, default: false },
});

defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isCompany = computed(() => user.value?.userable_type === 'company');
const isCandidate = computed(() => !!user.value?.is_candidate);
const isAdmin = computed(() => !!user.value?.is_admin);
const badges = computed(() => page.props.badges || {});

const main = computed(() => [
	{ id: 'dashboard', label: 'Dashboard', route: 'dashboard', icon: LayoutDashboard },
	{ id: 'opportunities', label: 'Roles', route: 'app.opportunities.index', icon: Compass },
	...(isCandidate.value || isCompany.value ? [{ id: 'applications', label: 'Applications', route: 'applications.index', icon: FileText, badge: badges.value.applications }] : []),
	...(isCompany.value ? [{ id: 'roles', label: 'My roles', href: `${route('companies.show', user.value?.userable_id)}#opportunities`, icon: Briefcase }] : []),
	...(isCandidate.value ? [
		{ id: 'saved', label: 'Saved roles', route: 'likes.index', icon: Bookmark, badge: user.value?.likes_count },
		{ id: 'saved-searches', label: 'Saved searches', route: 'saved-searches.index', icon: SearchCheck },
		{ id: 'kits', label: 'CV & letters', route: 'kits.index', icon: FileUser },
	] : []),
	{ id: 'messages', label: 'Messages', route: 'messages.index', icon: MessageSquare, badge: badges.value.messages },
	{ id: 'companies', label: 'Companies', route: 'app.companies.index', icon: Building2 },
	...(isAdmin.value || isCompany.value ? [{ id: 'talent', label: 'Talent', route: 'talent.index', icon: Users }] : []),
]);

const admin = computed(() => [
	{ id: 'leads', label: 'Job leads', route: 'leads.index', icon: Target },
	{ id: 'imports', label: 'Imports', icon: Database, children: [
		{ id: 'imports.sources', label: 'Sources', route: 'imports.sources.index' },
		{ id: 'imports.review', label: 'Review queue', route: 'imports.review', badge: badges.value.imports_review },
		{ id: 'imports.reports', label: 'Reports', route: 'imports.reports', badge: badges.value.reports },
		{ id: 'imports.claims', label: 'Employer claims', route: 'imports.claims', badge: badges.value.claims },
		{ id: 'imports.runs', label: 'Runs & health', route: 'imports.runs' },
	] },
	{ id: 'signups', label: 'Sign-up sources', route: 'signups.index', icon: UserPlus },
	{ id: 'candidates', label: 'Candidates', route: 'candidates.index', icon: GraduationCap, badge: badges.value.public_profiles },
	{ id: 'fields', label: 'Fields', route: 'fields.index', icon: BookOpen },
	{ id: 'skills', label: 'Skills', route: 'skills.index', icon: Sparkles },
]);

// A group opens on its own when one of its pages is active, and stays as the person leaves it.
const open = reactive({});
const inGroup = (group, id = props.active) => !!id?.startsWith(`${group.id}.`);
watch(() => props.active, (id) => {
	for (const group of admin.value.filter((item) => item.children)) {
		if (inGroup(group, id)) open[group.id] = true;
	}
}, { immediate: true });
const total = (group) => group.children.reduce((sum, child) => sum + (child.badge || 0), 0);

const navItem = (active, size = 'px-3 py-2.5 text-[14.5px]') => [
	'flex items-center gap-3 rounded-card transition-colors focus-visible:outline-2 focus-visible:outline-white',
	size,
	active ? 'bg-brand text-white' : 'text-mist-2 hover:bg-white/10 hover:text-white',
];
const badge = 'ml-auto grid h-5 min-w-5 place-items-center rounded-md bg-white px-1.5 text-[11px] font-semibold text-navy';
</script>

<template>
	<aside
		id="workspace-navigation"
		class="line-art flex flex-col sticky top-0 h-dvh bg-navy text-mist-2 px-3.5 pt-5 pb-4 overflow-y-auto max-[900px]:fixed max-[900px]:left-0 max-[900px]:z-50 max-[900px]:w-[264px] max-[900px]:transition-[transform,visibility] max-[900px]:duration-200"
		:class="mobileOpen ? 'max-[900px]:translate-x-0' : 'max-[900px]:-translate-x-full max-[900px]:invisible'"
		aria-label="Workspace"
	>
		<div class="flex items-center justify-between px-2.5 pb-6">
			<Link :href="route('dashboard')" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-white">
				<img src="/brand/nextward/symbol-192.png" alt="" class="size-9 shrink-0" width="36" height="36">
				<span class="text-white text-[20px] font-semibold">Nextward</span>
			</Link>
			<button class="hidden max-[900px]:grid place-items-center size-8 rounded-lg text-mist-2 hover:bg-white/10 hover:text-white" type="button" aria-label="Close navigation" @click="$emit('close')">
				<X class="size-5" />
			</button>
		</div>

		<nav class="flex flex-col gap-0.5" aria-label="Main">
			<Link
				v-for="item in main"
				:key="item.id"
				:class="navItem(item.id === active)"
				:href="item.href || route(item.route)"
				:aria-current="item.id === active ? 'page' : undefined"
				@click="$emit('close')"
			>
				<component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
				<span class="truncate">{{ item.label }}</span>
				<span v-if="item.badge" :class="badge">{{ item.badge > 99 ? '99+' : item.badge }}</span>
			</Link>
		</nav>

		<template v-if="isAdmin">
			<div class="px-3 pt-7 pb-2 text-[12px] tracking-caps text-mist-2/70 uppercase">Admin</div>
			<nav class="flex flex-col gap-0.5" aria-label="Admin">
				<template v-for="item in admin" :key="item.id">
					<template v-if="item.children">
						<button
							type="button"
							class="w-full text-left"
							:class="navItem(!open[item.id] && inGroup(item))"
							:aria-expanded="!!open[item.id]"
							:aria-controls="`nav-group-${item.id}`"
							@click="open[item.id] = !open[item.id]"
						>
							<component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
							<span class="truncate">{{ item.label }}</span>
							<span v-if="!open[item.id] && total(item)" :class="badge" :aria-label="`${total(item)} waiting for review`">{{ total(item) > 99 ? '99+' : total(item) }}</span>
							<ChevronDown class="size-4 shrink-0 transition-transform" :class="[open[item.id] ? 'rotate-180' : '', !open[item.id] && total(item) ? '' : 'ml-auto']" aria-hidden="true" />
						</button>
						<div v-show="open[item.id]" :id="`nav-group-${item.id}`" class="flex flex-col gap-0.5">
							<Link
								v-for="child in item.children"
								:key="child.id"
								:class="navItem(child.id === active, 'py-2 pr-3 pl-[42px] text-[14px]')"
								:href="route(child.route)"
								:aria-current="child.id === active ? 'page' : undefined"
								@click="$emit('close')"
							>
								<span class="truncate">{{ child.label }}</span>
								<span v-if="child.badge" :class="badge" :aria-label="`${child.badge} waiting for review`">{{ child.badge > 99 ? '99+' : child.badge }}</span>
							</Link>
						</div>
					</template>
					<Link
						v-else
						:class="navItem(item.id === active)"
						:href="item.href || route(item.route)"
						:aria-current="item.id === active ? 'page' : undefined"
						@click="$emit('close')"
					>
						<component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
						<span class="truncate">{{ item.label }}</span>
						<span v-if="item.badge" :class="badge" :aria-label="`${item.badge} waiting for review`">{{ item.badge > 99 ? '99+' : item.badge }}</span>
					</Link>
				</template>
			</nav>
		</template>

		<div class="flex-1"></div>

		<div class="border-t border-white/10 pt-3 mt-4 flex flex-col gap-0.5">
			<Link :class="navItem(false, 'px-3 py-2.5 text-[13.5px]')" :href="route('home')" @click="$emit('close')">Nextward website</Link>
			<Link :class="navItem(false, 'px-3 py-2.5 text-[13.5px]')" :href="route('blog.index')" @click="$emit('close')">Blog</Link>
			<Link :class="navItem(false, 'px-3 py-2.5 text-[13.5px]')" :href="route('contact')" @click="$emit('close')">Help and feedback</Link>
		</div>
	</aside>
</template>
