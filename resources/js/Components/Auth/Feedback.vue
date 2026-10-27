<template>
	<div v-if="status" :class="[box, 'bg-green-50 text-green-800']" role="status"><icon name="check-circle" :class="icon" /><p>{{ status }}</p></div>
	<div v-if="Object.keys(errors).length" :class="[box, 'bg-red-50 text-red-800']" role="alert">
		<icon name="exclamation-circle" :class="icon" />
		<div><p class="font-medium">{{ $page.props.locale === 'fr' ? 'Vérifiez les informations saisies.' : 'Please check your details.' }}</p><ul class="mt-1"><li v-for="(error, key) in errors" :key="key">{{ error }}</li></ul></div>
	</div>
</template>

<script>
export default {
	props: {
		status: { type: String, default: '' },
		errors: { type: Object, default: () => ({}) },
	},
	data() {
		return {
			box: 'mb-6 flex items-start gap-3 rounded-card px-5 py-4 text-[14px] leading-[1.7]',
			icon: 'mt-0.5 size-[19px] shrink-0',
		};
	},
};
</script>
