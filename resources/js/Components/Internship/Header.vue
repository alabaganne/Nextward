<template>
	<div>
		<h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">{{ truncateTitle ? internship.title : truncate(internship.title, 50) }}</h2>
		<div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-1 text-sm text-gray-500">
			<div class="sm:mr-6 mt-2 flex items-center">
				<icon name="office-building" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
				{{ internship.company.name }}
			</div>
			<div class="sm:mr-6 mt-2 flex items-center">
				<icon name="location-marker" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
				{{ internship.city.name }}
			</div>
			<div class="sm:mr-6 mt-2 flex items-center">
				<icon name="light-bulb" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
				{{ internship.field.name }}
			</div>
			<div v-if="full" class="sm:mr-6 mt-2 flex items-center">
				<icon name="clock" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
				Posted {{ internship.created_at }}
			</div>
			<div class="sm:mr-6 mt-2 flex items-center">
        <icon name="calendar" class="shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
				{{ internship.is_open === false ? 'Closed on' : 'Closing on' }} {{ internship.closing_at }}
			</div>
		</div>
	</div>
</template>

<script>
export default {
	props: {
		internship: {
			type: Object,
			required: true,
		},
		full: {
			type: Boolean,
			default: true,
		},
		truncateTitle: {
			type: Boolean,
			default: false,
		}
	}
}
</script>
