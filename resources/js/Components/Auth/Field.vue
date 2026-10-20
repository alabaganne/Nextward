<template>
	<div class="min-w-0">
		<label :for="id" :class="classes.label">{{ label }} <span v-if="optional" :class="classes.optional">{{ fr ? '(facultatif)' : '(optional)' }}</span></label>
		<div class="relative">
			<input
				v-bind="$attrs"
				:id="id"
				:name="$attrs.name || id"
				:type="type === 'password' && showPassword ? 'text' : type"
				:value="modelValue"
				:class="[classes.input, { 'pr-12': type === 'password' }]"
				:aria-invalid="error ? 'true' : undefined"
				:aria-describedby="error ? `${id}-error` : hint ? `${id}-hint` : undefined"
				@input="$emit('update:modelValue', $event.target.value)"
			/>
			<button v-if="type === 'password'" type="button" class="focus-ring absolute top-0.5 right-0.5 flex size-11 items-center justify-center rounded-[5px] text-subtle-2 transition-colors duration-180 hover:bg-[#f1f4f9] hover:text-navy" :aria-label="fr ? `${showPassword ? 'Masquer' : 'Afficher'} le champ « ${label} »` : `${showPassword ? 'Hide' : 'Show'} ${label.toLowerCase()}`" :aria-pressed="showPassword" :aria-controls="id" @click="showPassword = !showPassword">
				<icon :name="showPassword ? 'eye-slash' : 'eye'" class="size-[19px]" />
			</button>
		</div>
		<p v-if="error" :id="`${id}-error`" :class="classes.error">{{ error }}</p>
		<p v-else-if="hint" :id="`${id}-hint`" :class="classes.hint">{{ hint }}</p>
	</div>
</template>

<script>
import * as classes from './classes';

export default {
	inheritAttrs: false,
	emits: ['update:modelValue'],
	props: {
		id: { type: String, required: true },
		label: { type: String, required: true },
		modelValue: { type: String, default: '' },
		type: { type: String, default: 'text' },
		error: { type: String, default: '' },
		hint: { type: String, default: '' },
		optional: { type: Boolean, default: false },
	},
	data() {
		return { classes: { ...classes }, showPassword: false };
	},
	computed: {
		fr() {
			return this.$page.props.locale === 'fr';
		},
	},
};
</script>
