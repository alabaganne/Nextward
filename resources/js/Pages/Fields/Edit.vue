<template>
	<main-layout>
		<div class="max-w-xl mx-auto mt-6">
			<app-form
				title="Field of Studies"
				subtitle="Lorem Ipsum is simply dummy text of the printing and typesetting industry."
				:form="form"
				:original-data="field"
				route-name="fields"
				:route-params="{ field }"
				withDelete
				model-name="Field"
			>
				<div class="p-6">
					<label for="name" class="required">Name</label>
					<input v-model="form.name" type="text" name="field" id="field" class="mt-1 form-control" placeholder="e.g. Computer Science" />
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
		field: {
			type: Object,
			required: false
		}
	},
	data() {
		return {
			form: useForm({
				name: "",
			})
		}
	},
}
</script>
