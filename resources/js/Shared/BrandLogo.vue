<template>
	<span class="inline-flex shrink-0 flex-col" :class="stacked ? 'items-center gap-2 text-center' : 'items-start gap-1'">
		<template v-if="stacked">
			<img :src="file('mark')" :alt="showText ? '' : 'Internly'" :width="markSize" :height="markSize" :style="{ height: `${markSize}px` }" class="block w-auto" />
			<img v-if="showText" :src="file('wordmark')" alt="Internly" :width="Math.round(wordmarkHeight * 188 / 64)" :height="wordmarkHeight" :style="{ height: `${wordmarkHeight}px` }" class="block w-auto" />
		</template>
		<img v-else-if="showText" :src="file('logo')" alt="Internly" :width="Math.round(height * 258 / 64)" :height="height" :style="{ height: `${height}px` }" class="block w-auto" />
		<img v-else :src="file('mark')" alt="Internly" :width="height" :height="height" :style="{ height: `${height}px` }" class="block w-auto" />
		<span v-if="tagline" class="text-[11px] font-semibold uppercase tracking-[0.14em]" :class="dark ? 'text-blue-200' : 'text-gray-500'">{{ tagline }}</span>
	</span>
</template>

<script>
const SIZES = { sm: 24, md: 32, lg: 40 };

// Outlined artwork from /public/brand/internly; light-surface files first, dark-surface files second.
const FILES = {
	logo: ['logo.svg', 'logo-dark.svg'],
	mark: ['mark.svg', 'mark-light.svg'],
	wordmark: ['wordmark.svg', 'wordmark-white.svg'],
};

export default {
	props: {
		showText: {
			type: Boolean,
			default: true,
		},
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
		markSize() {
			return this.height + 8;
		},
		wordmarkHeight() {
			return Math.round(this.height * 0.85);
		},
	},
	methods: {
		file(kind) {
			return `/brand/internly/${FILES[kind][this.dark ? 1 : 0]}`;
		},
	},
};
</script>
