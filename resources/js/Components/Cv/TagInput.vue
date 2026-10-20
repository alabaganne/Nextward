<script setup>
import { ref } from 'vue';
import { X } from '@lucide/vue';

// A list of short items edited as chips: Enter or a comma adds one, Backspace on an empty box removes the last.
const items = defineModel({ type: Array, required: true });
defineProps({ placeholder: { type: String, default: '' }, label: { type: String, required: true } });

const input = ref(null);
const draft = ref('');
const add = (values) => {
	const fresh = values.map((value) => value.trim()).filter((value) => value && !items.value.some((item) => item.toLowerCase() === value.toLowerCase()));
	if (fresh.length) items.value = [...items.value, ...fresh];
};
const commit = () => {
	add(draft.value.split(/[,،\n]/));
	draft.value = '';
};
const keydown = (event) => {
	if (event.key === 'Enter' || event.key === ',' || event.key === '،') {
		event.preventDefault();
		commit();
	} else if (event.key === 'Backspace' && !draft.value && items.value.length) {
		items.value = items.value.slice(0, -1);
	}
};
const paste = (event) => {
	const text = event.clipboardData?.getData('text') || '';
	if (!/[,،\n]/.test(text)) return;
	event.preventDefault();
	add(text.split(/[,،\n]/));
};
const remove = (index) => {
	items.value = items.value.filter((_, at) => at !== index);
	input.value?.focus();
};
</script>

<template>
	<div class="w-full flex flex-wrap items-center gap-1.5 border-[1.5px] border-line rounded-[11px] bg-white p-2 cursor-text transition-colors focus-within:border-blue-500" @click.self="input?.focus()">
		<span v-for="(item, index) in items" :key="`${item}-${index}`" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-900 text-[13.5px] font-medium pl-2.5 pr-1 py-1" dir="auto">
			{{ item }}
			<button type="button" class="grid place-items-center size-5 rounded-md text-blue-700 hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-blue-500" :aria-label="`Remove ${item}`" @click="remove(index)"><X class="size-3.5" aria-hidden="true" /></button>
		</span>
		<input
			ref="input"
			v-model="draft"
			dir="auto"
			class="flex-1 min-w-[140px] border-0 bg-transparent px-1.5 py-1 text-[14px] outline-none focus:ring-0 placeholder:text-muted-2"
			:placeholder="items.length ? 'Add another…' : placeholder"
			:aria-label="label"
			@keydown="keydown"
			@paste="paste"
			@blur="commit"
		>
	</div>
</template>
