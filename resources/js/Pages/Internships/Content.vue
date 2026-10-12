<template>
	<div class="flex flex-col 2xl:flex-row gap-x-6">
		<filters v-bind="{ fields, cities, companies, filters }" />
		<div class="flex-1">
			<div class="space-y-6">
				<template v-for="internship in internships.data" :key="internship.id">
					<internship-card :internship="internship" />
				</template>
			</div>
			<pagination v-if="internships.data.length > 0" :meta="internships.meta" :links="internships.meta.links" :routeParams="filters" class="mt-4" />
			<card v-else class="p-6">
				<div class="font-semibold text-gray-900">No internships match right now.</div>
				<p class="mt-1 text-sm text-gray-600">Try clearing a filter or searching for a different title. Internships leave the list after their closing date.</p>
			</card>
		</div>
	</div>
</template>

<script>
import InternshipCard from "@/Components/Internship/Card.vue";
import Pagination from "@/Components/Pagination.vue";
import Filters from "./Filters.vue";

export default {
	components: { InternshipCard, Pagination, Filters },
	props: {
		internships: Object,
		fields: Array,
		cities: Array,
		companies: Array,
		filters: Object,
	},
};
</script>
