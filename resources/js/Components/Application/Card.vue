<template>
	<Link :href="route('applications.show', application)" class="px-6 py-4 flex justify-between items-center group hover:bg-gray-50">
		<div v-if="currentUser.userable_type === 'company'" class="w-5/12 flex items-center">
			<img
				class="h-14 w-14 rounded-full object-cover"
				src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80"
				alt=""
			>
			<div class="ml-4">
				<div class="text-blue-500 font-medium">{{ application.student.name }}</div>
				<div class="mt-1 text-sm text-gray-500 flex items-center">
					<icon name="mail" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
					{{ application.student.email }}
				</div>
			</div>
		</div>
		<div v-else class="w-5/12">
			<div class="text-blue-500 font-medium">{{ application.internship.title }}</div>
			<div class="mt-2 text-sm text-gray-500 flex items-center space-x-4">
				<div class="flex items-center">
					<icon name="office-building" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
					{{ application.company.name }}
				</div>
				<div class="flex items-center">
					<icon name="location-marker" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
					{{ application.internship.city.name }}
				</div>
			</div>
		</div>
		<div class="flex-1 text-sm">
			<div class="font-medium">Applied on {{ application.created_at }}</div>
			<div class="mt-2 text-gray-500 flex items-center">
				<icon v-if="application.status === null" name="clock" solid class="shrink-0 mr-1 h-5 w-5 text-gray-400" />
				<icon v-else-if="application.status === true" solid name="check-circle"  class="shrink-0 mr-1 h-5 w-5 text-green-500" />
				<icon v-else name="x-circle" solid class="shrink-0 mr-1 h-5 w-5 text-red-500" />
				{{ application.status === null ? "This application isn't reviewed yet." : "The company has reviewed this application." }}
			</div>
		</div>
		<div class="text-lg text-gray-600 font-medium transform group-hover:translate-x-1.5 transition duration-150">
			->
		</div>
	</Link>
</template>

<script>
export default {
	props: {
		application: {
			type: Object,
			required: true,
		}
	}
}
</script>
