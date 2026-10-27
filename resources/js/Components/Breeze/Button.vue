<template>
	<button :type="type" class="cta cta-primary focus-ring min-h-[52px] w-full disabled:cursor-wait [&_svg]:size-[18px] [&_svg]:shrink-0">
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
