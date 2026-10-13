<template>
	<button :type="type" class="auth-submit focus-ring">
		<slot />
	</button>
</template>

<script>
	export default {
		props: {
			type: {
				type: String,
				default: 'submit',
			},
		}
	}
</script>
