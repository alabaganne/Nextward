<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleUserRound, LogOut, Menu, Plus, Search, Settings } from '@lucide/vue';

defineProps({ navigationOpen: { type: Boolean, default: false } });
defineEmits(['notifications', 'navigation']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isCompany = computed(() => user.value?.userable_type === 'company');
const unread = computed(() => user.value?.notifications?.unread_count || 0);
const roleLabel = computed(() => {
	if (user.value?.is_admin) return 'Admin';
	if (isCompany.value) return 'Company';
	return { professional: 'Professional', graduate: 'Graduate' }[user.value?.career_stage] || 'Student';
});
const initials = computed(() =>
	(user.value?.name || '')
		.split(' ')
		.map((w) => w[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);

const search = ref(new URLSearchParams(page.url.split('?')[1] || '').get('search') || '');
const submitSearch = () => {
	router.get(route('opportunities.index'), search.value.trim() ? { search: search.value.trim() } : {});
};

const menu = ref(null);
const menuOpen = ref(false);
const closeMenu = (event) => {
	if (menu.value && !menu.value.contains(event.target)) menuOpen.value = false;
};
onMounted(() => document.addEventListener('click', closeMenu));
onBeforeUnmount(() => document.removeEventListener('click', closeMenu));

const logout = () => router.post(route('logout'));
</script>

<template>
	<header class="h-16 shrink-0 bg-white/95 backdrop-blur border-b border-line flex items-center gap-3 px-6 max-[680px]:px-4 sticky top-0 z-20">
		<button class="icon-btn hidden max-[900px]:grid -ml-1.5" type="button" aria-label="Open navigation" aria-controls="workspace-navigation" :aria-expanded="navigationOpen" @click="$emit('navigation')">
			<Menu class="size-5" />
		</button>

		<form class="flex-1 min-w-0 max-w-[480px]" role="search" @submit.prevent="submitSearch">
			<label class="flex items-center gap-2.5 bg-paper-2 border border-transparent rounded-[10px] px-3 h-10 transition-colors focus-within:bg-white focus-within:border-blue-500 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-blue-600">
				<Search class="size-4 shrink-0 text-muted-2" aria-hidden="true" />
				<input
					v-model="search"
					aria-label="Search opportunities"
					class="border-0 bg-transparent p-0 outline-none focus:ring-0 text-sm w-full placeholder:text-muted-2"
					type="search"
					placeholder="Search roles, companies, skills…"
				>
			</label>
		</form>

		<div class="ml-auto flex items-center gap-1.5 shrink-0">
			<Link v-if="isCompany" class="cbtn cbtn-primary !py-0 h-10 !px-4 !text-sm max-[680px]:!px-0 max-[680px]:w-10" :href="route('internships.create')" aria-label="Post opportunity">
				<Plus class="size-4" aria-hidden="true" /><span class="max-[680px]:hidden">Post opportunity</span>
			</Link>

			<button v-if="user?.notifications" class="icon-btn" type="button" :aria-label="unread ? `Notifications, ${unread} unread` : 'Notifications'" @click="$emit('notifications')">
				<Bell class="size-5" />
				<span v-if="unread" class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold grid place-items-center ring-2 ring-white">{{ unread > 9 ? '9+' : unread }}</span>
			</button>

			<div ref="menu" class="relative" @keydown.esc="menuOpen = false">
				<button
					class="flex items-center gap-2 h-10 pl-1 pr-2 rounded-[10px] transition-colors hover:bg-paper-2 focus-visible:outline-2 focus-visible:outline-blue-500"
					type="button"
					aria-haspopup="menu"
					:aria-expanded="menuOpen"
					aria-label="Account menu"
					@click="menuOpen = !menuOpen"
				>
					<span class="size-8 rounded-full grid place-items-center text-white font-bold font-display text-[13px] bg-gradient-to-br from-blue-400 to-blue-700">{{ initials }}</span>
					<span class="text-left leading-tight max-[760px]:hidden">
						<b class="block text-[13.5px] font-semibold max-w-[140px] truncate">{{ user?.name }}</b>
						<span class="block text-xs text-muted">{{ roleLabel }}</span>
					</span>
					<ChevronDown class="size-4 text-muted-2 max-[760px]:hidden" aria-hidden="true" />
				</button>

				<Transition enter-active-class="transition duration-100" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-75" leave-to-class="opacity-0">
					<div v-if="menuOpen" class="absolute right-0 top-[calc(100%+6px)] w-60 rounded-xl border border-line bg-white p-1.5 z-30" role="menu">
						<div class="px-3 py-2.5 border-b border-line-2 mb-1.5">
							<b class="block text-sm font-semibold truncate">{{ user?.name }}</b>
							<span class="block text-xs text-muted truncate">{{ user?.email }}</span>
						</div>
						<Link class="menu-item" role="menuitem" :href="route('profile.show')" @click="menuOpen = false"><CircleUserRound class="size-4 text-muted-2" />Profile</Link>
						<Link class="menu-item" role="menuitem" :href="route('settings')" @click="menuOpen = false"><Settings class="size-4 text-muted-2" />Settings</Link>
						<div class="h-px bg-line-2 my-1.5"></div>
						<button class="menu-item hover:!text-red-600" role="menuitem" type="button" @click="logout"><LogOut class="size-4 text-muted-2" />Log out</button>
					</div>
				</Transition>
			</div>
		</div>
	</header>
</template>
