<template>
	<div>
		<div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
			<div class="space-y-1 text-center">
				<icon name="photo" class="mx-auto h-12 w-12 text-gray-400" />
				<div class="flex text-sm text-gray-600">
					<label :for="inputId" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-hidden focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
						<span>Upload files</span>
						<input :id="inputId" type="file" multiple class="sr-only" @change="onChange" />
					</label>
					<p class="pl-1">or drag and drop</p>
				</div>
				<p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
			</div>
		</div>
		<ul v-if="files.length" class="mt-3 border border-gray-200 rounded-md divide-y divide-gray-200">
			<li v-for="file in files" :key="file.name" class="px-3 py-2 text-sm text-gray-700 flex justify-between">
				<span>{{ file.name }}</span>
				<span class="text-gray-400">{{ Math.ceil(file.size / 1024) }} KB</span>
			</li>
		</ul>
	</div>
</template>

<script>
export default {
	props: {
		modelValue: {
			type: Array,
			default: () => []
		},
		inputId: {
			type: String,
			default: 'file-upload'
		}
	},
	emits: ['update:modelValue'],
	computed: {
		files() { return this.modelValue || []; }
	},
	methods: {
		onChange(event) {
			this.$emit('update:modelValue', Array.from(event.target.files || []));
		}
	}
}
</script>
