<template>
	<div>
		<div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
			<div class="space-y-1 text-center">
				<svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
					<path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
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
