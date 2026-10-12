<template>
	<slide-over ref="slideOver" v-if="currentUser.notifications !== null" title="Notifications">
		<template v-slot:trigger>
			<button class="p-2 hover:text-gray-900 hover:bg-gray-100 rounded-full"
				:class="{ 'text-red-600 hover:text-red-600 hover:bg-red-50': currentUser.notifications.unread_count > 0 }"
			>
				<icon name="bell" solid />
			</button>
		</template>
		<template v-slot:content>
			<div
				v-if="currentUser.notifications.data.length > 0"
				:key="routeUrl()"
			>
				<button
					v-for="(notification) in currentUser.notifications.data"
					:key="notification.id"
					@click="markAsRead(notification)"
					class="p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer text-left flex"
				>
					<div
						class="h-16 w-16 shrink-0 flex-center rounded-full"
						:class="notification.read_at === null
							? 'bg-red-100 text-red-600'
							: 'bg-gray-200 text-gray-800'
						"
					>
						<icon name="bell" solid />
					</div>
					<div class="ml-5">
						<div class="font-medium text-gray-900">{{ notification.data.title }}</div>
						<p class="text-gray-500 text-sm mt-1" v-html="notification.data.body" />
						<span class="mt-1 text-xs text-blue-500 font-medium">{{ notification.created_at }}</span>
					</div>
				</button>
			</div>
			<div v-else class="p-4 text-center">No notifications found.</div>
			<div class="p-4 text-center">
				<Link
					v-if="currentUser.notifications.data.length > 3"
					:href="route('notifications.clear')"
					class="link font-medium text-sm"
				>Clear All</Link>
			</div>
		</template>
	</slide-over>
</template>

<script>
import { router } from '@inertiajs/vue3';
import SlideOver from '@/Components/SlideOver.vue';

export default {
	components: { SlideOver },
	methods: {
		markAsRead(notification) {
			router.get(notification.data.action);
			if(! notification.read_at) {
				axios.post(route('notifications.store', notification.id))
					.then(() => {
						setTimeout(() => {
							this.reloadNotification();
						}, 50);
					})
					.catch(error => console.log(error));
			}

			this.$refs.slideOver.toggle();
		},
		reloadNotification() {
			router.reload({ only: ['auth'] });
		}
	},
	mounted() {
		if (!window.Echo) return;

		window.Echo.private('user.' + this.currentUser.id)
			.notification(notification => {
				this.$page.props.toast = notification.toast;
				this.reloadNotification();
			})
	}
}
</script>
