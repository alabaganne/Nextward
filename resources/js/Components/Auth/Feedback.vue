<template>
	<div v-if="status" class="auth-feedback auth-success" role="status"><icon name="check-circle" /><p>{{ status }}</p></div>
	<div v-if="Object.keys(errors).length" class="auth-feedback auth-error" role="alert">
		<icon name="exclamation-circle" />
		<div><p class="auth-feedback-title">Please check your details.</p><ul><li v-for="(error, key) in errors" :key="key">{{ error }}</li></ul></div>
	</div>
</template>

<script>
export default {
	props: {
		status: { type: String, default: '' },
		errors: { type: Object, default: () => ({}) },
	},
};
</script>
