<template>
	<div v-if="skills.length" class="flex flex-wrap gap-2">
		<span v-for="skill in skills" :key="skill" class="tag tag-primary rounded-full">
			{{ skill }}
		</span>
	</div>
	<p v-else-if="showEmpty" class="text-sm text-gray-500">No skills listed yet.</p>
</template>

<script>
export default {
	props: {
		skills: {
			type: Array,
			default: () => [],
		},
		showEmpty: {
			type: Boolean,
			default: false,
		},
	},
}
</script>
