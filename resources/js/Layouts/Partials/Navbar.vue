<template>
	<nav class="bg-white text-gray-700 h-20 flex justify-between items-center px-4 md:px-6 border-b"> <!-- xl:hidden -->
		<button @click="toggleSidebar" class="p-2 hover:text-gray-900 hover:bg-gray-100 rounded-full">
			<icon name="menu" solid />
		</button>
		<div class="flex items-center space-x-2">
			<Link
				v-if="currentUser.userable_type === 'student'"
				:href="route('likes.index')"
				class="p-2 hover:bg-gray-100 rounded-full block"
				:class="{
					'text-red-600 hover:bg-red-50': currentUser.likes_count > 0,
				}"
			>
				<icon name="heart" solid />
			</Link>

			<Link
				v-if="['student', 'company'].includes(currentUser.userable_type)"
				:href="route('messages.index')"
				class="p-2 hover:bg-gray-100 rounded-full"
			>
				<icon name="chat-alt-2" solid />
			</Link>

			<notifications-slide-over :key="routeUrl" />

			<breeze-dropdown width="w-80">
				<template v-slot:trigger>
					<button class="px-2 flex items-center text-sm">
						<img
							class="h-9 w-9 rounded-full object-cover"
							src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80"
							alt=""
						/>
					</button>
				</template>
				<template v-slot:content>
						<div class="flex items-center px-5 py-4">
							<div class="shrink-0">
								<img
									class="h-12 w-12 rounded-full"
									src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80"
									alt=""
								/>
							</div>
							<div class="ml-3">
								<div class="font-medium">{{ currentUser.name }}</div>
								<div class="text-sm leading-none text-gray-500">{{ currentUser.email }}</div>
							</div>
						</div>
												<breeze-dropdown-link :href="route('profile.show')">My Profile</breeze-dropdown-link>
						<breeze-dropdown-link :href="route('logout')" as="button" method="POST">Logout</breeze-dropdown-link>
				</template>
			</breeze-dropdown>
		</div>
	</nav>
</template>

<script>
import BreezeDropdown from '@/Components/Breeze/Dropdown.vue';
import BreezeDropdownLink from '@/Components/Breeze/DropdownLink.vue';
import NotificationsSlideOver from './NotificationsSlideOver.vue';

import { mapActions, mapGetters } from 'vuex';

export default {
	components: {
		BreezeDropdown,
		BreezeDropdownLink,
		NotificationsSlideOver,
	},
	computed: {
		...mapGetters(['sidebarActive']),
	},
	methods: {
		...mapActions(['toggleSidebar']),
	},
};
</script>
