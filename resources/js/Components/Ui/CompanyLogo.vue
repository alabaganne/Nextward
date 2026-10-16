<script setup>
import { computed, ref, watch } from 'vue';
import { gradientFor, initialsFor } from '@/utils/brand';

const props = defineProps({
	name: { type: String, default: '' },
	src: { type: String, default: null },
	initials: { type: String, default: null },
	gradient: { type: String, default: null },
});

const failed = ref(false);
watch(() => props.src, () => { failed.value = false; });

// Only full URLs or site paths; bare storage paths can't be shown.
const showImage = computed(() => !!props.src && /^(https?:\/\/|\/)/.test(props.src) && !failed.value);
const style = computed(() => ({ background: props.gradient || gradientFor(props.name) }));
const label = computed(() => props.initials || initialsFor(props.name));
</script>

<template>
	<!-- Size/radius classes come from the caller so each screen keeps the
	     exact classes of its design reference. -->
	<div v-if="showImage" class="grid place-items-center overflow-hidden bg-white shrink-0">
		<img :src="src" :alt="name ? `${name} logo` : ''" class="w-full h-full object-contain" loading="lazy" @error="failed = true" />
	</div>
	<div v-else class="grid place-items-center text-white font-bold font-display shrink-0" :style="style" aria-hidden="true">
		{{ label }}
	</div>
</template>
