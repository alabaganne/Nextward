<template>
  <main-layout>
		<div class="max-w-3xl mx-auto mt-6">
			<app-form
				:form="form"
				:original-data="company"
				route-name="companies"
				:route-params="{ company }"
				title="Company Information"
				withDelete
				model-name="Company"
			>
				<div class="grid grid-cols-6 gap-x-4 gap-y-6 p-6">
					<div class="col-span-2">
						<label for="name" class="required">Company name</label>
						<input v-model="form.name" type="text" name="name" id="name" class="mt-1 form-control" />
					</div>
					<div class="col-span-4">
						<label for="email" class="required">Email address</label>
						<input v-model="form.email" name="email" id="email" type="email" class="mt-1 form-control" placeholder="john@example.com" />
					</div>
					<div class="col-span-4">
						<label for="phone_number">Phone number</label>
						<input v-model="form.phone_number" name="phone_number" id="phone_number" type="text" class="mt-1 form-control" />
					</div>
					<div class="col-span-6 sm:col-span-3">
						<label class="required" for="city">City</label>
						<select v-model="form.city_id" name="city" id="city" class="mt-1 form-control">
							<option></option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
					</div>
					<div class="col-span-4">
						<label for="company_website" class="block text-sm font-medium text-gray-700">
							Website URL
						</label>
						<input v-model="form.website" name="website" id="website" type="text" class="mt-1 form-control" placeholder="www.example.com" />
					</div>
					<div class="col-span-6">
						<label for="id_card_number">About</label>
						<textarea class="mt-1 form-control" rows="10" v-model="form.about"></textarea>
					</div>
				</div>
			</app-form>
		</div>
	</main-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/Main.vue";
import AppForm from "@/Components/Form.vue";

export default {
	components: {
		MainLayout,
		AppForm,
	},
	props: {
		company: Object,
		cities: Array
	},
	data() {
		return {
			form: useForm({
				name: "",
				email: "",
				phone_number: "",
				about: "",
				city_id: null,
				website: "",
			})
		}
	},
}
</script>
