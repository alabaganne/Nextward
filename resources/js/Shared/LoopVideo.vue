<template>
	<div ref="root" class="relative aspect-3/2 overflow-hidden bg-pale">
		<img v-if="reducedMotion" :src="poster" alt="" width="1200" height="800" :loading="lazy ? 'lazy' : 'eager'" decoding="async" class="h-full w-full object-cover" />
		<template v-else-if="near">
			<!-- MP4 first: it has the same pale background as this frame, so it looks identical everywhere. -->
			<video
				ref="video"
				:poster="poster"
				width="1200"
				height="800"
				muted
				loop
				playsinline
				disablepictureinpicture
				disableremoteplayback
				preload="auto"
				aria-hidden="true"
				class="h-full w-full object-cover"
			>
				<source :src="`${base}.mp4`" type="video/mp4" />
				<source :src="`${base}.webm`" type="video/webm" />
			</video>
			<button
				type="button"
				class="focus-ring absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-navy shadow-sm ring-1 ring-navy/10 transition hover:bg-white hover:text-brand"
				:aria-label="shared.paused ? 'Play animation' : 'Pause animation'"
				:title="shared.paused ? 'Play animation' : 'Pause animation'"
				@click="shared.paused = !shared.paused"
			>
				<icon :name="shared.paused ? 'play' : 'pause'" solid class="h-4 w-4" />
			</button>
		</template>
	</div>
</template>

<script>
import { reactive } from 'vue';

// Shared by every loop on the page: pausing one pauses them all.
const shared = reactive({ paused: false });

export default {
	props: {
		// File name in /internly-assets/loops, e.g. "conversation" or "success".
		name: {
			type: String,
			required: true,
		},
		// Wait until the frame is close to the viewport before loading the poster and video.
		lazy: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			shared,
			reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
			near: !this.lazy,
			visible: false,
		};
	},
	computed: {
		base() {
			return `/internly-assets/loops/${this.name}`;
		},
		poster() {
			return `${this.base}-poster.png`;
		},
	},
	watch: {
		'shared.paused'() {
			this.sync();
		},
	},
	mounted() {
		if (this.reducedMotion || !('IntersectionObserver' in window)) {
			this.near = true;
			return;
		}

		// Play only while most of the loop is on screen; pause it as soon as it scrolls away.
		this.observer = new IntersectionObserver(([entry]) => {
			this.visible = entry.intersectionRatio >= 0.4;
			this.$nextTick(this.sync);
		}, { threshold: [0, 0.4] });
		this.observer.observe(this.$refs.root);

		if (!this.near) {
			this.nearObserver = new IntersectionObserver(([entry]) => {
				if (entry.isIntersecting) {
					this.near = true;
					this.nearObserver.disconnect();
					this.$nextTick(this.sync);
				}
			}, { rootMargin: '400px 0px' });
			this.nearObserver.observe(this.$refs.root);
		}
	},
	beforeUnmount() {
		this.observer?.disconnect();
		this.nearObserver?.disconnect();
	},
	methods: {
		sync() {
			const video = this.$refs.video;
			if (!video) return;

			if (this.visible && !shared.paused) {
				video.play().catch(() => {});
			} else {
				video.pause();
			}
		},
	},
};
</script>
