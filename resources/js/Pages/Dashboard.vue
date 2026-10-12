<template>
	<main-layout subtitle="Overview" title="Dashboard">
		<div class="grid c-grid-col-sm gap-4">
			<template v-for="card in cards" :key="card.label">
				<card v-if="card.show">
					<div class="py-6 flex items-center">
						<div class="px-8">
							<icon :name="card.icon" class="h-7 w-7 text-blue-500" />
						</div>
						<div>
							<div class="text-gray-500 text-sm uppercase">{{ card.label }}</div>
							<div class="mt-0.5 text-3xl font-extrabold">{{ card.value }}</div>
						</div>
					</div>
					<template v-slot:footer class="px-6 py-4 bg-gray-50">
						<Link :href="card.link" class="link text-sm">View all -></Link>
					</template>
				</card>
			</template>
		</div>
		<div class="mt-5 max-w-5xl">
			<card v-if="applications" title="Recent Applications" subtitle="Lorem Ipsum is simply dummy text of the printing and typesetting industry.">
				<div v-if="applications.length > 0" class="divide-y">
					<application-card v-for="application in applications" :key="application.id" :application="application" />
				</div>
				<template v-if="applications.length  >= 4" v-slot:footer>
					<div class="flex justify-end">
						<Link :href="route('applications.index')" class="btn btn-primary rounded-lg">View all -></Link>
					</div>
				</template>
				<div v-if="applications.length === 0" class="p-6">No records found.</div>
			</card>
		</div>
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import ApplicationCard from "@/Components/Application/Card.vue";

export default {
	components: {
		MainLayout,
		ApplicationCard,
	},
	props: {
		internships_count: Number,
		students_count: Number,
		companies_count: Number,
		applications_count: Number,
		applications: Array,
	},
	data() {
		return {
			cards: [
				{
					label: 'Open Internships',
					value: this.internships_count,
					icon: 'briefcase',
					link: route('internships.index'),
					show: true,
				},
				{
					label: 'Students',
					value: this.students_count,
					icon: 'user-group',
					link: route('students.index'),
					show: this.currentUser.is_admin,
				},
				{
					label: 'Companies',
					value: this.companies_count,
					icon: 'office-building',
					link: route('companies.index'),
					show: true,
				},
				{
					label: 'Pending Applications',
					value: this.applications_count,
					icon: 'folder',
					link: route('applications.index'),
					show: this.currentUser.userable_type === 'student' || this.currentUser.userable_type === 'company'
				},
			],
		};
	},
};
</script>
