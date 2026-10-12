<template>
	<div class="bg-white rounded-lg shadow-sm overflow-hidden">
		<div v-if="title" class="p-6 border-b flex justify-between items-center">
			<div>
				<h3 class="text-lg leading-6 font-medium text-gray-900">{{ title }}</h3>
				<p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-gray-500">{{ subtitle }}</p>
			</div>
			<div>
				<slot name="header-right" />
			</div>
		</div>
		<slot />
		<div v-if="$slots['footer']" class="px-6 py-3 bg-gray-50 border-t border-gray-100">
			<slot name="footer" />
		</div>
	</div>
</template>

<script>
export default {
	props: {
		title: String,
		subtitle: String,
	}
}
</script>
