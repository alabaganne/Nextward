<template>
	<button :type="type" class="focus-ring inline-flex min-h-12 w-full items-center justify-center gap-2.5 rounded-[7px] bg-brand px-[18px] py-3 text-[14px] leading-[1.5] font-semibold text-white transition-colors duration-180 hover:bg-brand-dark disabled:cursor-wait disabled:opacity-60 [&_svg]:size-[18px] [&_svg]:shrink-0">
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
