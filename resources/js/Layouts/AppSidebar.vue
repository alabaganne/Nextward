<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
	Bookmark,
	BookOpen,
	Briefcase,
	Building2,
	Compass,
	Database,
	FileText,
	GraduationCap,
	LayoutDashboard,
	MessageSquare,
	SearchCheck,
	Sparkles,
	X,
} from '@lucide/vue';

defineProps({
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
	{ id: 'internships', label: 'Opportunities', route: 'opportunities.index', icon: Compass },
	...(isAdmin.value ? [] : [{ id: 'applications', label: 'Applications', route: 'applications.index', icon: FileText, badge: badges.value.applications }]),
	...(isCompany.value ? [{ id: 'roles', label: 'My roles', href: `/companies/${user.value?.userable_id}#opportunities`, icon: Briefcase }] : []),
	...(isCandidate.value ? [
		{ id: 'saved', label: 'Saved roles', route: 'likes.index', icon: Bookmark, badge: user.value?.likes_count },
		{ id: 'saved-searches', label: 'Saved searches', route: 'saved-searches.index', icon: SearchCheck },
	] : []),
	{ id: 'messages', label: 'Messages', route: 'messages.index', icon: MessageSquare, badge: badges.value.messages },
	{ id: 'companies', label: 'Companies', route: 'companies.index', icon: Building2 },
]);

const admin = [
	{ id: 'imports', label: 'Sources and imports', route: 'imports.index', icon: Database },
	{ id: 'students', label: 'Candidates', route: 'students.index', icon: GraduationCap },
	{ id: 'fields', label: 'Fields', route: 'fields.index', icon: BookOpen },
	{ id: 'skills', label: 'Skills', route: 'skills.index', icon: Sparkles },
];
</script>

<template>
	<aside
		id="workspace-navigation"
		class="flex flex-col sticky top-0 h-dvh bg-navy-900 text-[#aeb9cf] px-3.5 pt-5 pb-4 overflow-y-auto max-[900px]:fixed max-[900px]:left-0 max-[900px]:z-50 max-[900px]:w-[264px] max-[900px]:transition-[transform,visibility] max-[900px]:duration-200"
		:class="mobileOpen ? 'max-[900px]:translate-x-0' : 'max-[900px]:-translate-x-full max-[900px]:invisible'"
		aria-label="Workspace"
	>
		<div class="flex items-center justify-between px-2.5 pb-6">
			<Link :href="route('dashboard')" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-blue-400">
				<img src="/brand/nextward/symbol-192.png" alt="" class="size-9 shrink-0" width="36" height="36">
				<span class="font-display text-white text-[19px] font-semibold tracking-[-.01em]">Nextward</span>
			</Link>
			<button class="hidden max-[900px]:grid place-items-center size-8 rounded-lg text-[#aeb9cf] hover:bg-white/10 hover:text-white" type="button" aria-label="Close navigation" @click="$emit('close')">
				<X class="size-5" />
			</button>
		</div>

		<nav class="flex flex-col gap-0.5" aria-label="Main">
			<Link
				v-for="item in main"
				:key="item.id"
				class="nav-item"
				:class="{ 'is-active': item.id === active }"
				:href="item.href || route(item.route)"
				:aria-current="item.id === active ? 'page' : undefined"
				@click="$emit('close')"
			>
				<component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
				<span class="truncate">{{ item.label }}</span>
				<span v-if="item.badge" class="nav-badge">{{ item.badge > 99 ? '99+' : item.badge }}</span>
			</Link>
		</nav>

		<template v-if="isAdmin">
			<div class="text-xs text-[#6b7894] font-semibold px-3 pt-6 pb-2">Admin</div>
			<nav class="flex flex-col gap-0.5" aria-label="Admin">
				<Link
					v-for="item in admin"
					:key="item.id"
					class="nav-item"
					:class="{ 'is-active': item.id === active }"
					:href="item.href || route(item.route)"
					:aria-current="item.id === active ? 'page' : undefined"
					@click="$emit('close')"
				>
					<component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
					<span class="truncate">{{ item.label }}</span>
				</Link>
			</nav>
		</template>

		<div class="flex-1"></div>

		<div class="border-t border-white/[.07] pt-3 mt-4 flex flex-col gap-0.5">
			<Link class="nav-item !text-[13.5px]" :href="route('blog.index')" @click="$emit('close')">Career guides</Link>
			<Link class="nav-item !text-[13.5px]" :href="route('contact')" @click="$emit('close')">Help and contact</Link>
		</div>
	</aside>
</template>
