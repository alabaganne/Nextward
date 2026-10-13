<script setup>
import { computed } from 'vue';
import { gradientFor, initialsFor } from '@/utils/brand';

const props = defineProps({
	name: { type: String, default: '' },
	initials: { type: String, default: null },
	gradient: { type: String, default: null },
});

const style = computed(() => ({ background: props.gradient || gradientFor(props.name) }));
const label = computed(() => props.initials || initialsFor(props.name));
</script>

<template>
	<!-- Size/radius classes come from the caller so each screen keeps the
	     exact classes of its design reference. -->
	<div class="grid place-items-center text-white font-bold font-display shrink-0" :style="style">
		{{ label }}
	</div>
</template>
