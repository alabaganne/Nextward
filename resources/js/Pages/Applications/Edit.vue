<template>
	<main-layout title="Application" subtitle="Submit" maxWidthClass="max-w-4xl">
		<internship-card :internship="internship" :full="false" />
		<app-form
			class="mt-6"
			title="Introduce yourself"
			subtitle="What makes you a strong candidate for this internship?"
			:form="form"
			:original-data="application"
			route-name="applications"
			:route-params="{
				'internship': internship,
				'application': application
			}"
		>
			<div class="space-y-6 p-6">
				<div>
					<label for="cover_letter">
						<div class="required">Cover letter</div>
						<p class="mt-1 text-gray-500 font-light">Tell the company a little bit about yourself and any work you’ve done that’s relevant to this internship.</p>
					</label>
					<textarea v-model="form.cover_letter" id="cover_letter" class="mt-1.5 form-control" rows="10"></textarea>
					<div class="p-1.5 pb-0 flex justify-end text-xs text-gray-500">
						{{ form.cover_letter.length }} / 200
					</div>
				</div>
				<div>
					<label for="message">
						<div>Message</div>
						<p class="mt-1 text-gray-500 font-light">You can include any questions you may have about this internship or the company, even request a video call.</p>
					</label>
					<textarea v-model="form.message" id="message" class="mt-1.5 form-control" rows="6"></textarea>
					<div class="p-1.5 pb-0 flex justify-end text-xs text-gray-500">
						{{ form.message.length }} / 100
					</div>
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700">
						Attachments
					</label>
					<files-upload class="mt-1" v-model="form.attachment_files" input-id="applications-attachments" />
					<p class="mt-1 text-gray-500 text-sm font-light">You may attach up to 10 files under the size of <span class="font-semibold">10MB</span> each. Include your résumé, work samples or other documents to support your application.</p>
				</div>
			</div>
		</app-form>
	</main-layout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/Main.vue";
import InternshipCard from "@/Components/Internship/Card.vue";
import FilesUpload from "@/Components/FilesUpload.vue";
import AppForm from "@/Components/Form.vue";

export default {
	components: {
		MainLayout,
		InternshipCard,
		FilesUpload,
		AppForm,
	},
	props: {
		internship: Object,
		application: Object,
	},
	data() {
		return {
			form: useForm({
				cover_letter: "",
				message: "",
				attachments: [],
				attachment_files: [],
			})
		}
	}
}
</script>
