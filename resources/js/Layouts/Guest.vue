<template>
	<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
		<div>
			<Link href="/" class="inline-flex rounded-lg focus:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-4">
				<breeze-application-logo />
			</Link>
		</div>

		<div class="w-full sm:max-w-2xl mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
			<slot />
		</div>
	</div>
</template>

<script>
	import BreezeApplicationLogo from '@/Components/Breeze/ApplicationLogo.vue'

	export default {
		components: {
			BreezeApplicationLogo,
		}
	}
</script>
