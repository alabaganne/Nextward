<template>
	<div class="shrink-0 2xl:w-96 order-2 2xl:order-first mt-6 2xl:mt-0">
		<card title="Filters" subtitle="Use the filters below to find the internships that interest you.">
			<div class="sm:flex flex-wrap">
				<form @submit.prevent="filter" autocomplete="off" class="p-6 w-full">
					<label for="search-internships">Search</label>
					<input
						type="text"
						class="mt-1 form-control focus:placeholder-transparent w-full py-2.5"
						id="search-internships"
						name="search-internships"
						v-model="search"
						placeholder="Search internships by Title"
					/>
				</form>
				<div class="p-6 w-full border-t">
					<label>Fields of Study</label>
					<div class="mt-3 space-y-2">
						<template v-for="field in fields" :key="field.id">
							<div v-if="field.internships_count > 0" class="flex justify-between items-center">
								<div class="flex items-center">
									<input type="checkbox" :value="field.id" v-model="selected.fields" />
									<span class="ml-2 leading-5">{{ field.name }}</span>
								</div>
								<div class="tag tag-sm tag-primary rounded-full h-6 w-6 flex-center">{{ field.internships_count }}</div>
							</div>
						</template>
						<div v-show="noFilters(fields)" class="text-gray-500 text-sm">No filters found.</div>
					</div>
				</div>

				<div v-if="companies.length" class="p-6 w-full border-t">
					<label>Companies</label>
					<div class="mt-3 space-y-2">
						<template v-for="company in companies" :key="company.id">
							<div v-if="company.internships_count > 0" class="flex justify-between items-center">
								<div class="flex items-center">
									<input type="checkbox" :value="company.id" v-model="selected.companies" />
									<span class="ml-2 leading-5">{{ company.name }}</span>
								</div>
								<div class="tag tag-sm tag-primary rounded-full h-6 w-6 flex-center">{{ company.internships_count }}</div>
							</div>
						</template>
						<div v-show="noFilters(companies)" class="text-gray-500 text-sm">No filters found.</div>
					</div>
				</div>

				<div class="p-6 w-full border-t">
					<label>Cities</label>
					<div class="mt-3 space-y-2">
						<template v-for="city in cities" :key="city.id">
							<div v-if="city.internships_count > 0" class="flex justify-between items-center">
								<div class="flex items-center">
									<input type="checkbox" :value="city.id" v-model="selected.cities" />
									<span class="ml-2 leading-5">{{ city.name }}</span>
								</div>
								<div class="tag tag-sm tag-primary rounded-full h-6 w-6 flex-center">{{ city.internships_count }}</div>
							</div>
						</template>
						<div v-show="noFilters(cities)" class="text-gray-500 text-sm">No filters found.</div>
					</div>
				</div>
			</div>
			<template v-slot:footer>
				<div>
					<button @click="reset" class="btn btn-lg btn-primary w-full">
						Reset Filters
					</button>
				</div>
			</template>
		</card>
	</div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import { throttle } from "lodash-es";

export default {
	props: {
		fields: {
			type: Array,
			required: true
		},
		cities: {
			type: Array,
			required: true
		},
		companies: {
			type: Array,
			required: true
		},
		filters: {
			type: Object,
			required: true
		},
	},
	emits: ['filter'],
	data() {
		return {
			selected: {
				fields: this.filters.fields || [],
				cities: this.filters.cities || [],
				companies: this.filters.companies || [],
			},
			search: this.filters.search
		}
	},
	watch: {
		selected: {
			handler: function() {
				this.filter();
			},
			deep: true,
		},
		search: {
			handler: throttle(function () {
				this.filter()
			}, 500)
		}
	},
	methods: {
		filter() {
			router.get(
				this.route('internships.index'),
				{
					...this.selected,
					search: this.search
				},
				{
					preserveState: true,
					preserveScroll: true,
					replace: true
				}
			)
			this.$emit('filter', this.selected);
		},
		reset() {
			router.get(this.route('internships.index'))
		},
		noFilters(arr) {
			return arr.filter(el => el.internships_count !== 0).length === 0;
		},
	},
}
</script>
