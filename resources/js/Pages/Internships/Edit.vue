<template>
	<main-layout>
		<div class="max-w-3xl mx-auto mt-6">
			<app-form
				:form="form"
				:original-data="internship"
				route-name="internships"
				:route-params="{ internship }"
				title="Internship Information"
				subtitle="Lorem Ipsum is simply dummy text of the printing and typesetting industry."
				model-name="Internship"
			>
				<div class="grid gap-x-3 gap-y-6 p-6">
					<div class="col-span-6 sm:col-span-6">
						<label for="title" class="required">Title</label>
						<input v-model="form.title" type="text" name="title" id="title" class="mt-1 form-control" />
					</div>
					<div class="col-span-3">
						<label for="field" class="required">Field of studies</label>
						<select v-model="form.field_id" name="field" id="field" class="mt-1 form-control">
							<option></option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
					</div>
					<div class="col-span-3">
						<label for="closing_at" class="required">Closing date</label>
						<input v-model="form.closing_at" name="closing_at" id="closing_at" type="date" class="mt-1 form-control" />
					</div>
					<div class="col-span-6">
						<label for="description" class="required">Description</label>
						<textarea v-model="form.description" name="description" id="description" rows="12" class="mt-1 form-control"></textarea>
					</div>
					<div class="col-span-6">
						<label class="block text-sm font-medium text-gray-700">
							Attachments
						</label>
						<files-upload class="mt-1" v-model="form.attachment_files" input-id="internships-attachments" />
						<p class="mt-1 text-gray-500 text-sm font-light">You may attach up to 10 files under the size of <span class="font-semibold">10MB</span> each.</p>
					</div>
				</div>
			</app-form>
		</div>
	</main-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/Main.vue";
import BreezeValidationErrors from "@/Components/ValidationErrors.vue";
import AppForm from "@/Components/Form.vue";
import FilesUpload from "@/Components/FilesUpload.vue";

export default {
	components: {
		MainLayout,
		BreezeValidationErrors,
		AppForm,
		FilesUpload,
	},
	props: {
		fields: {
			type: Array,
			required: true
		},
		internship: {
			type: Object,
			required: false
		}
	},
	data() {
		return {
			form: useForm({
				title: "",
				description: "",
				attachments: [],
				attachment_files: [],
				closing_at: "",
				field_id: null,
			}),

			value: null,
			options: ['list', 'of', 'options']
		}
	},
}
</script>
