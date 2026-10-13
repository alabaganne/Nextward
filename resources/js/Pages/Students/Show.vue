<template>
	<main-layout title="Profile" subtitle="Student" maxWidthClass="max-w-7xl">
		<template v-slot:header-right>
			<Link :href="route('students.edit', student.id)" class="btn btn-dark">
				Edit <icon name="arrow-right" class="ml-2 inline-block h-4 w-4 align-middle" />
			</Link>
			<delete-modal
				title="Delete Student"
				message="Are you sure you want to delete this student? All the data related to him will be permanently deleted. This action cannot be undone."
				:url="route('students.destroy', student.id)"
			>
				<button class="ml-1.5 btn btn-danger">Delete</button>
			</delete-modal>
		</template>
		<card>
			<img
				class="h-60 w-full object-cover"
				src="https://images.unsplash.com/photo-1588392382834-a891154bca4d?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1355&q=80"
				alt="cover photo"
			>
			<div class="px-8 pb-12">
				<!-- header -->
				<div class="flex justify-between items-center">
					<div class="flex items-center">
						<div class="h-44 w-44 -mt-20 border-4 border-white rounded-full overflow-hidden">
							<img
								src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=256&h=256&q=60"
								alt=""
								class="w-full"
							>
						</div>
						<div class="ml-6">
							<div class="text-2xl 2xl:text-3xl text-gray-800 font-extrabold">{{ student.name }}</div>
							<div class="text-sm 2xl:text-base text-gray-500">{{ student.field.name }} Student</div>
						</div>
					</div>
					<div class="flex items-center space-x-1">
						<button @click="selectedUser = student" class="btn btn-secondary">
							Send Message
						</button>
					</div>
				</div>
				<!-- about -->
				<div class="mt-16 grid grid-cols-7 gap-10" :class="{ 'text-left': !student.about }">
					<div v-if="student.about" class="col-span-5">
						<div class="text-xl font-bold text-gray-800">About</div>
						<p class="mt-3 text-lg text-gray-700 leading-8" v-html="student.about" />
					</div>
					<div class="col-span-2" :class="{ 'col-span-full': !student.about }">
						<div class="text-xl font-bold text-gray-800">Contact details</div>
						<div class="mt-3 space-y-3">
							<div class="flex items-center">
								<icon name="mail" class="shrink-0 h-5 w-5 text-blue-400 mr-3" />
								{{ student.email }}
							</div>
							<div v-if="student.phone_number" class="flex items-center">
								<icon name="phone" class="shrink-0 h-5 w-5 text-blue-400 mr-3" />
								{{ student.phone_number }}
							</div>
							<div v-if="student.city" class="flex items-center">
								<icon name="location-marker" class="shrink-0 h-5 w-5 text-blue-400 mr-3" />
								{{ student.city.name }}
							</div>
							<div v-if="student.linkedin_profile_url" class="flex items-center">
								<icon name="cursor-click" class="shrink-0 h-5 w-5 text-blue-400 mr-3" />
								<a :href="student.linkedin_profile_url" target="_blank" class="link text-base text-blue-500">Linkedin profile <icon name="arrow-right" class="ml-2 inline-block h-4 w-4 align-middle" /></a>
							</div>
						</div>
					</div>
					<div class="col-span-full">
						<div class="text-xl font-bold text-gray-800">Skills and expertise</div>
						<skills class="mt-2" :skills="student.skills" show-empty />
					</div>
				</div>
			</div>
		</card>
		<contact-modal :user="selectedUser" @close="selectedUser = null" />
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import Skills from "@/Components/Skills.vue";
import DeleteModal from "@/Components/Modal/Delete.vue"
import ContactModal from "@/Components/Modal/Contact.vue";

export default {
	components: {
		MainLayout,
		Skills,
		DeleteModal,
		ContactModal,
	},
	props: {
		student: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			selectedUser: null,
		}
	}
}
</script>
