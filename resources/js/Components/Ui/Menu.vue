<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

// A small dropdown: the button slot gets `open` and `toggle`, the default slot holds the entries (see utils/menu.js).
defineProps({ width: { type: String, default: 'w-60' } });
const root = ref(null);
const open = ref(false);
const toggle = () => (open.value = !open.value);
const outside = (event) => {
	if (root.value && !root.value.contains(event.target)) open.value = false;
};
onMounted(() => document.addEventListener('click', outside));
onBeforeUnmount(() => document.removeEventListener('click', outside));
</script>

<template>
	<div ref="root" class="relative" @keydown.esc="open = false">
		<slot name="button" :open="open" :toggle="toggle" />
		<Transition
			enter-active-class="transition duration-150 ease-out"
			enter-from-class="opacity-0 -translate-y-1"
			leave-active-class="transition duration-150 ease-in"
			leave-to-class="opacity-0 -translate-y-1"
		>
			<div v-if="open" class="absolute right-0 top-[calc(100%+6px)] z-30 rounded-xl border border-line bg-white p-1.5" :class="width" role="menu" @click="open = false">
				<slot />
			</div>
		</Transition>
	</div>
</template>
