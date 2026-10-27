<script setup>
import { nextTick, ref } from 'vue';
import { Plus, X } from '@lucide/vue';

// Bullet points edited one per box: Enter starts a new one, Backspace in an empty one removes it.
const items = defineModel({ type: Array, required: true });
const list = ref(null);

const focusAt = async (index) => {
	await nextTick();
	list.value?.querySelectorAll('textarea')[index]?.focus();
};
const set = (index, value) => {
	items.value = items.value.map((item, at) => (at === index ? value.replace(/\n/g, ' ') : item));
};
const insert = (index) => {
	items.value = [...items.value.slice(0, index), '', ...items.value.slice(index)];
	focusAt(index);
};
const remove = (index) => {
	items.value = items.value.filter((_, at) => at !== index);
	focusAt(Math.max(0, index - 1));
};
const keydown = (event, index) => {
	if (event.key === 'Enter') {
		event.preventDefault();
		insert(index + 1);
	} else if (event.key === 'Backspace' && !items.value[index]) {
		event.preventDefault();
		remove(index);
	}
};
</script>

<template>
	<div ref="list" class="grid gap-2">
		<div v-for="(item, index) in items" :key="index" class="group flex items-start gap-2">
			<span class="mt-[15px] size-1.5 shrink-0 rounded-full bg-muted-2" aria-hidden="true" />
			<textarea
				:value="item"
				dir="auto"
				rows="1"
				class="fld !min-h-0 !py-2 !text-[14px] resize-none field-sizing-content"
				:aria-label="`Point ${index + 1}`"
				@input="set(index, $event.target.value)"
				@keydown="keydown($event, index)"
			></textarea>
			<button type="button" class="mt-1.5 grid place-items-center size-7 shrink-0 rounded-lg text-muted-2 hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-blue-500" :aria-label="`Remove point ${index + 1}`" @click="remove(index)"><X class="size-4" aria-hidden="true" /></button>
		</div>
		<button type="button" class="justify-self-start inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[13px] font-medium text-blue-700 hover:bg-blue-50" @click="insert(items.length)"><Plus class="size-4" aria-hidden="true" /> Add a point</button>
	</div>
</template>
