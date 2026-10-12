<template>
  <main-layout>
		<div class="max-w-3xl mx-auto mt-6">
			<app-form
				:form="form"
				:original-data="student"
				route-name="students"
				:route-params="{ student }"
				title="Student Information"
			>
				<div class="grid grid-cols-6 gap-x-4 gap-y-6 p-6">
					<div class="col-span-6 sm:col-span-6">
						<label for="name" class="required">Full name</label>
						<input v-model="form.name" type="text" name="name" id="name" class="mt-1 form-control" />
					</div>
					<div class="col-span-5">
						<label for="email" class="required">Email address</label>
						<input v-model="form.email" name="email" id="email" type="email" class="mt-1 form-control" placeholder="john@example.com" />
					</div>
					<div class="col-span-4">
						<label for="phone_number">Phone number</label>
						<input v-model="form.phone_number" name="phone_number" id="phone_number" type="text" class="mt-1 form-control" />
					</div>
					<div class="col-span-6 sm:col-span-3">
						<label for="field" class="required">Field of studies</label>
						<select v-model="form.field_id" name="field" id="field" class="mt-1 form-control">
							<option></option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
					</div>
					<div class="col-span-6 sm:col-span-3">
						<label for="city">City</label>
						<select v-model="form.city_id" name="city" id="city" class="mt-1 form-control">
							<option></option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
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
		AppForm
	},
	props: {
		student: Object,
		fields: Array,
		cities: Array
	},
	data() {
		return {
			form: useForm({
				name: "",
				email: "",
				phone_number: "",
				field_id: null,
				city_id: null,
				about: "",
			})
		}
	},
}
</script>
