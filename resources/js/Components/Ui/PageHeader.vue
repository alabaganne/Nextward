<script setup>
defineProps({
	title: { type: String, required: true },
	description: { type: String, default: '' },
});
</script>

<template>
	<div class="flex items-end justify-between flex-wrap gap-4 mb-7">
		<div class="min-w-0">
			<slot name="before" />
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">{{ title }}</h1>
			<p v-if="description || $slots.description" class="text-muted text-[15px] mt-1.5 max-w-[640px]">
				<slot name="description">{{ description }}</slot>
			</p>
		</div>
		<div v-if="$slots.actions" class="flex items-center gap-2.5 flex-wrap">
			<slot name="actions" />
		</div>
	</div>
</template>
