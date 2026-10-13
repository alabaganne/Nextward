<template>
	<main-layout title="Students" subtitle="Profiles">
		<template v-slot:header-right>
				<Link v-if="currentUser.is_admin" :href="route('students.create')" class="btn btn-lg btn-primary">Add Student <icon name="arrow-right" class="ml-2 inline-block h-4 w-4 align-middle" /></Link>
		</template>
		<div v-if="students.data.length > 0">
			<div class="grid c-grid-col-sm gap-4">
				<card v-for="student in students.data" :key="student.id">
					<div class="px-6 py-10 flex-center flex-col">
						<img
							class="h-28 w-28 rounded-full object-cover"
							src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=200&h=200&q=60"
							:alt="student.name"
						>
						<div class="mt-6 text-lg font-medium">{{ student.name }}</div>
						<div class="text-sm text-gray-500">{{ student.field.name }} Student</div>
					</div>
					<div class="border-t flex divide-x">
						<Link class="flex-1 px-2 py-4 text-sm font-medium hover:bg-gray-50 flex-center" :href="route('students.show', student.id)">
							<icon name="user-circle" solid class="h-5 w-5 text-gray-400 mr-1.5" />
							Profile
						</Link>
						<button @click="selectedUser = student" class="flex-1 px-2 py-4 text-sm font-medium hover:bg-gray-50 flex-center">
							<icon name="mail" solid class="h-5 w-5 text-gray-400 mr-1.5" />
							Send Message
						</button>
					</div>
				</card>
			</div>
			<pagination :links="students.links" :meta="students" class="mt-4" />
		</div>
		<card v-else class="p-6">
			No students found
		</card>
		<contact-modal :user="selectedUser" @close="selectedUser = null" />
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import Pagination from "@/Components/Pagination.vue";
import ContactModal from "@/Components/Modal/Contact.vue";

export default {
	components: {
		MainLayout,
		Pagination,
		ContactModal,
	},
	props: {
		students: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			selectedUser: null,
		}
	}
};
</script>
