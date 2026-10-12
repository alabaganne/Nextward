<template>
	<main-layout title="Company" subtitle="View">
		<template v-slot:header-right>
			<Link v-if="currentUser.userable_type === 'company' && currentUser.userable_id === company.id" :href="route('internships.create')" class="btn btn-lg btn-primary">Add Internship -></Link>
		</template>
		<div class="lg:grid lg:grid-cols-3 gap-6">
			<div class="col-span-1">
				<card class="p-6">
					<div>
						<div v-if="company.image" class="mb-5 h-24 w-24 overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 shadow-xs">
							<img :src="`/storage/${company.image}`" :alt="`${company.name} logo`" class="h-full w-full object-cover" />
						</div>
						<h1 class="text-3xl font-extrabold">{{ company.name }}</h1>
						<div class="mt-6 space-y-4">
							<div class="flex items-center">
								<icon name="location-marker" class="h-5 w-5 text-blue-400 mr-1.5" />
								{{ company.city.name }}
							</div>
							<div class="flex items-center">
								<icon name="phone" class="h-5 w-5 text-blue-400 mr-1.5" />
								{{ company.phone_number }}
							</div>
							<div class="flex items-center">
								<icon name="mail" class="h-5 w-5 text-blue-400 mr-1.5" />
								{{ company.email }}
							</div>
							<div class="flex items-center">
								<icon name="cursor-click" class="h-5 w-5 text-blue-400 mr-1.5" />
								<a :href="company.website" target="_blank" class="link">Visit website</a>
							</div>
						</div>
					</div>
					<p class="mt-6 text-gray-700 text-lg leading-8">{{ company.about }}</p>
					<div class="mt-8 flex justify-between">
						<button v-if="currentUser.id !== company.user_id && currentUser.userable_type === 'student'" @click="selectedUser = company" class="btn btn-secondary">
							Send Message
						</button>
						<div v-if="currentUser.is_admin || currentUser.id === company.user_id" class="flex">
							<Link :href="route('companies.edit', company)" class="btn btn-dark">
								Edit
							</Link>
							<delete-modal
								v-if="currentUser.is_admin"
								title="Delete Company"
								message="Are you sure you want to delete this company? All the data related to it will be permanently deleted. This action cannot be undone."
								:url="route('companies.destroy', company)"
							>
								<button class="ml-1.5 btn btn-danger">Delete</button>
							</delete-modal>
						</div>
					</div>
				</card>
			</div>
			<div class="col-span-2 mt-6 lg:mt-0">
				<div v-if="company.internships.data.length > 0">
					<div class="space-y-6">
						<div v-for="internship in company.internships.data" :key="internship.id">
							<internship-card :internship="internship" :full="false" />
						</div>
					</div>
					<pagination :links="company.internships.meta.links" :meta="company.internships.meta" class="mt-4" />
				</div>
				<card v-else class="p-6">No internships found.</card>
			</div>
		</div>
		<contact-modal :user="selectedUser" @close="selectedUser = null" />
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import InternshipCard from "@/Components/Internship/Card.vue";
import DeleteModal from "@/Components/Modal/Delete.vue";
import ContactModal from "@/Components/Modal/Contact.vue";
import Pagination from "@/Components/Pagination.vue";

export default {
	components: {
		MainLayout,
		InternshipCard,
		DeleteModal,
		ContactModal,
		Pagination
	},
	props: {
		company: Object,
	},
	data() {
		return {
			selectedUser: null
		}
	}
}
</script>
