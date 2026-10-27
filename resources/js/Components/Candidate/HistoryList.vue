<script setup>
import { Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
	modelValue: { type: Array, default: () => [] },
	name: { type: String, required: true },
	title: { type: String, required: true },
	fields: { type: Array, required: true },
	addLabel: { type: String, required: true },
	errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

const blank = () => Object.fromEntries(props.fields.map((field) => [field.key, field.type === 'checkbox' ? false : '']));
const add = () => emit('update:modelValue', [...props.modelValue, blank()]);
const remove = (index) => emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
const set = (index, key, value) => emit('update:modelValue', props.modelValue.map((row, i) => (i === index ? { ...row, [key]: value } : row)));
const error = (index, key) => props.errors[`${props.name}.${index}.${key}`];
</script>

<template>
	<div class="mt-7">
		<div class="flex items-center justify-between gap-3 mb-3">
			<h3 class="text-[20px] leading-[1.35] text-navy">{{ title }}</h3>
			<button class="cta cta-secondary" type="button" @click="add"><Plus class="w-4 h-4" aria-hidden="true" /> {{ addLabel }}</button>
		</div>
		<p v-if="!modelValue.length" class="text-sm text-muted">Nothing added yet.</p>
		<div v-for="(row, index) in modelValue" :key="index" class="rounded-card bg-paper-3 p-4 mb-3">
			<div class="grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
				<template v-for="field in fields" :key="field.key">
					<label v-if="field.type === 'checkbox'" class="flex gap-2 items-center text-sm font-medium self-end pb-2">
						<input type="checkbox" :checked="!!row[field.key]" @change="set(index, field.key, $event.target.checked)"> {{ field.label }}
					</label>
					<label v-else class="text-sm font-medium" :class="{ 'col-span-2 max-[560px]:col-span-1': field.wide }">
						{{ field.label }}
						<textarea v-if="field.type === 'textarea'" class="fld mt-2 bg-white" rows="3" :value="row[field.key]" :placeholder="field.placeholder" @input="set(index, field.key, $event.target.value)"></textarea>
						<input v-else class="fld mt-2 bg-white" :type="field.type || 'text'" :value="row[field.key]" :placeholder="field.placeholder" :disabled="field.key === 'end' && row.current" @input="set(index, field.key, $event.target.value)">
						<span v-if="error(index, field.key)" data-error class="block text-xs font-normal text-red-600 mt-[7px]">{{ error(index, field.key) }}</span>
					</label>
				</template>
			</div>
			<div class="flex justify-end mt-3">
				<button class="inline-flex items-center gap-1.5 text-[13px] font-medium text-red-700 hover:underline" type="button" @click="remove(index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
			</div>
		</div>
	</div>
</template>
