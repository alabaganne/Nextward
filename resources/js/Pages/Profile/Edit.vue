<template>
	<main-layout title="Profile" maxWidthClass="max-w-4xl">
		<app-form
			:form="form"
			:original-data="profile_info"
			route-name="profile"
			model-name="Account"
		>
			<section class="p-6">
				<div>
					<h3 class="text-lg font-medium leading-6">Personal Information</h3>
					<p class="mt-1 text-sm text-gray-500">Use a permanent address where you can receive mail.</p>
				</div>
				<div class="mt-6 grid grid-cols-6 gap-x-4 gap-y-6">
					<div class="col-span-3">
						<label for="name" class="required">Full name</label>
						<input type="text" class="mt-1 form-control" id="name" v-model="form.name">
					</div>
					<div class="col-span-3">
						<label for="email" class="required">Email address</label>
						<input type="email" class="mt-1 form-control" id="email" v-model="form.email" placeholder="johndoe@example.com">
					</div>
					<div class="col-span-4">
						<label for="phone_number">Phone number</label>
						<input type="text" class="mt-1 form-control" id="phone_number" v-model="form.phone_number">
					</div>
					<div class="col-span-5">
						<label for="linkedin_profile_url">
							Linkedin Profile URL
						</label>
						<input type="text" name="linkedin_profile_url" id="linkedin_profile_url" class="mt-1 form-control" v-model="form.linkedin_profile_url" />
					</div>
					<div class="col-span-3">
						<label class="required" for="field">City</label>
						<select name="city" id="city" class="mt-1 form-control" v-model="form.city_id">
							<option selected value=""></option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
					</div>
					<div v-if="currentUser.userable_type === 'student'" class="col-span-3">
						<label class="required" for="field_id">Field of studies</label>
						<select name="field_id" id="field_id" class="mt-1 form-control" v-model="form.field_id">
							<option selected value=""></option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
					</div>
				</div>
			</section>
			<section class="border-t p-6">
				<div>
					<h3 class="text-lg font-medium leading-6">Profile</h3>
					<p class="mt-1 text-sm text-gray-500">This information will be displayed publicly so be careful what you share.</p>
				</div>
				<div class="mt-6 grid grid-cols-6 gap-x-4 gap-y-6">
					<div v-if="currentUser.userable_type === 'company'" class="col-span-4">
						<label for="website">
							Website URL
						</label>
						<input type="text" name="website" id="website" class="mt-1 form-control" placeholder="https://www.example.com" v-model="form.website" />
					</div>
					<div class="col-span-6">
						<label for="about">About</label>
						<textarea id="about" class="mt-1 form-control" rows="7" v-model="form.about" placeholder="Brief description for your profile."></textarea>
					</div>
					<div class="col-span-6">
						<label for="image">Photo</label>
						<div class="mt-1 flex items-center">
							<span class="inline-block h-12 w-12 rounded-full overflow-hidden bg-gray-100">
								<svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
									<path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
								</svg>
							</span>
							<button type="button" class="ml-5 btn btn-secondary">
								Change
							</button>
						</div>
					</div>
				</div>
			</section>
			<section class="border-t p-6">
				<div>
					<h3 class="text-lg font-medium leading-6">Update Password</h3>
					<p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
				</div>
				<div class="mt-6 grid grid-cols-6 gap-x-4 gap-y-6">
					<div class="col-span-3">
						<label for="password">Password</label>
						<input type="password" class="mt-1 form-control" id="password" v-model="form.password">
					</div>
					<div class="col-span-3">
						<label for="password_confirmation">Confirm Password</label>
						<input type="password" class="mt-1 form-control" id="password_confirmation" v-model="form.password_confirmation">
					</div>
				</div>
			</section>
		</app-form>
	</main-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/Main.vue";
import DeleteModal from "@/Components/Modal/Delete.vue";
import AppForm from "@/Components/Form.vue";

export default {
	components: {
		MainLayout,
		DeleteModal,
		AppForm,
	},
	props: {
		cities: {
			type: Array,
			required: true
		},
		fields: {
			type: Array,
			required: false,
		},
		profile_info: {
			type: Object,
			required: true,
		}
	},
	data() {
		return {
			form: useForm({
				name: '',
				email: '',
				phone_number: '',
				city_id: null,
				field_id: null,
				linkedin_profile_url: '',
				about: '',
				website: '',
				password: '',
				password_confirmation: '',
			}),
		}
	},
}
</script>
