<template>
	<div v-if="status" :class="[box, 'bg-[#edf6ef] text-[#286144]']" role="status"><icon name="check-circle" :class="icon" /><p>{{ status }}</p></div>
	<div v-if="Object.keys(errors).length" :class="[box, 'bg-[#fff2f1] text-[#ae3434]']" role="alert">
		<icon name="exclamation-circle" :class="icon" />
		<div><p class="font-semibold">{{ $page.props.locale === 'fr' ? 'Vérifiez les informations saisies.' : 'Please check your details.' }}</p><ul class="mt-1"><li v-for="(error, key) in errors" :key="key">{{ error }}</li></ul></div>
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
			box: 'mb-6 flex items-start gap-2.5 rounded-[6px] px-4 py-3.5 text-[13px] leading-[1.7]',
			icon: 'mt-0.5 size-[19px] shrink-0',
		};
	},
};
</script>
