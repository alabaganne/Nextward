<template>
	<header class="mb-[30px] max-[540px]:mb-7">
		<p class="flex items-center gap-2.5 text-[11px] leading-[1.5] font-semibold tracking-[.15em] text-brand uppercase">{{ kicker }}</p>
		<h1 class="mt-3.5 font-display text-[length:clamp(32px,3vw,40px)] leading-[1.15] font-medium tracking-[-.05em] max-[540px]:text-[34px]"><slot /></h1>
		<p class="mt-3.5 text-[14px] leading-[1.8] text-subtle">{{ description }}</p>
	</header>
</template>

<script>
export default {
	props: {
		kicker: { type: String, required: true },
		description: { type: String, required: true },
	},
};
</script>
