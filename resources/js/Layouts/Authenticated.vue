<template>
	<div v-if="currentUser" class="flex min-h-screen bg-gray-100">
		<toast :toast="$page.props.toast" :popstate="$page.props.popstate" />

		<sidebar :key="routeUrl()" />
		<div class="flex-1 flex flex-col text-gray-900">
			<navbar />
			<slot />
		</div>
	</div>
	<slot v-else />
</template>

<script>
import Sidebar from './Partials/Sidebar.vue';
import Navbar from './Partials/Navbar.vue';
import Toast from './Partials/Toast.vue';

export default {
	components: { Sidebar, Navbar, Toast },
	mounted() {
		if (!this.currentUser || !window.Echo) return;

		window.Echo.private('user.' + this.currentUser.id)
			.listen('.messages', e => {
				if(!route().current('messages.index')) {
					this.$page.props.toast = e.toast
				}
			})
	}
}
</script>
