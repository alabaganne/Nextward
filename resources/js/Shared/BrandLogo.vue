<template>
	<span class="inline-flex shrink-0 flex-col" :class="stacked ? 'items-center gap-2 text-center' : 'items-start gap-1'">
		<img
			src="/brand/nextward/Nextward-colorful-symbol.png"
			alt="Nextward"
			:width="height"
			:height="height"
			:style="{ width: `${height}px`, height: `${height}px` }"
			class="block shrink-0 object-contain"
		/>
		<span v-if="tagline" class="text-[11px] font-semibold uppercase tracking-[0.14em]" :class="dark ? 'text-blue-200' : 'text-gray-500'">{{ tagline }}</span>
	</span>
</template>

<script>
const SIZES = { sm: 40, md: 48, lg: 56 };

export default {
	props: {
		tagline: {
			type: String,
			default: '',
		},
		size: {
			type: String,
			default: 'md',
		},
		dark: {
			type: Boolean,
			default: false,
		},
		stacked: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		height() {
			return SIZES[this.size] || SIZES.md;
		},
	},
};
</script>
