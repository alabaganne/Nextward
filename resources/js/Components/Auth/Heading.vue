<template>
	<header class="mb-[30px] max-[540px]:mb-7">
		<p class="landing-label">{{ kicker }}</p>
		<h1 class="mt-3 text-[length:clamp(32px,3vw,42px)] leading-[1.2] font-medium text-navy max-[540px]:text-[32px]"><slot /></h1>
		<p class="mt-4 text-[15px] leading-[1.75] text-subtle">{{ description }}</p>
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
