<template>
	<main-layout title="Profile" :subtitle="subtitle" maxWidthClass="max-w-7xl">
		<template v-slot:header-right>
			<Link :href="route('profile.edit')" class="btn btn-dark">Edit profile -></Link>
		</template>

		<card>
			<img class="h-56 w-full object-cover" src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1400&q=80" alt="cover photo">
			<div class="px-8 pb-12">
				<div class="flex justify-between items-center">
					<div class="flex items-center">
						<div class="h-36 w-36 -mt-16 border-4 border-white rounded-full overflow-hidden bg-gray-100 flex-center">
							<img v-if="profile.image" :src="profile.image" :alt="profile.name" class="w-full h-full object-cover">
							<icon v-else name="user-circle" class="h-24 w-24 text-gray-300" />
						</div>
						<div class="ml-6">
							<div class="text-3xl text-gray-800 font-extrabold">{{ profile.name }}</div>
							<div class="text-base text-gray-500">{{ roleLabel }}</div>
						</div>
					</div>
				</div>

				<div v-if="profile.is_admin" class="mt-12">
					<div class="text-xl font-bold text-gray-800">Website admin</div>
					<p class="mt-3 text-lg text-gray-700">{{ profile.name }}</p>
				</div>

				<div v-else class="mt-12 grid grid-cols-7 gap-10">
					<div class="col-span-5">
						<div class="text-xl font-bold text-gray-800">About</div>
						<p class="mt-3 text-lg text-gray-700 leading-8 whitespace-pre-line">{{ profile.about || 'No profile description yet.' }}</p>
					</div>
					<div class="col-span-2">
						<div class="text-xl font-bold text-gray-800">Details</div>
						<div class="mt-3 space-y-3">
							<div class="flex items-center"><icon name="mail" class="h-5 w-5 text-blue-400 mr-3" />{{ profile.email }}</div>
							<div v-if="profile.phone_number" class="flex items-center"><icon name="phone" class="h-5 w-5 text-blue-400 mr-3" />{{ profile.phone_number }}</div>
							<div v-if="profile.city" class="flex items-center"><icon name="location-marker" class="h-5 w-5 text-blue-400 mr-3" />{{ profile.city.name }}</div>
							<div v-if="profile.field" class="flex items-center"><icon name="light-bulb" class="h-5 w-5 text-blue-400 mr-3" />{{ profile.field.name }}</div>
							<div v-if="profile.website" class="flex items-center"><icon name="cursor-click" class="h-5 w-5 text-blue-400 mr-3" /><a :href="profile.website" target="_blank" class="link text-base text-blue-500">Company website -></a></div>
							<div v-if="profile.linkedin_profile_url" class="flex items-center"><icon name="cursor-click" class="h-5 w-5 text-blue-400 mr-3" /><a :href="profile.linkedin_profile_url" target="_blank" class="link text-base text-blue-500">LinkedIn profile -></a></div>
							<div v-if="profile.userable_type === 'company'" class="flex items-center"><icon name="briefcase" class="h-5 w-5 text-blue-400 mr-3" />{{ profile.internships_count }} internships posted</div>
						</div>
					</div>
				</div>
			</div>
		</card>
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";

export default {
	components: { MainLayout },
	props: { profile: { type: Object, required: true } },
	computed: {
		subtitle() {
			if (this.profile.is_admin) return 'Admin';
			return this.profile.userable_type === 'company' ? 'Company' : 'Student';
		},
		roleLabel() {
			if (this.profile.is_admin) return 'Website administrator';
			if (this.profile.userable_type === 'company') return 'Company profile';
			return `${this.profile.field?.name || 'Student'} profile`;
		}
	}
}
</script>
