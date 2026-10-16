<template>
	<div class="auth-field">
		<label :for="id">{{ label }} <span v-if="optional" class="auth-optional">{{ fr ? '(facultatif)' : '(optional)' }}</span></label>
		<div class="auth-input-wrap">
			<input
				v-bind="$attrs"
				:id="id"
				:name="$attrs.name || id"
				:type="type === 'password' && showPassword ? 'text' : type"
				:value="modelValue"
				class="auth-input"
				:class="{ 'auth-input-password': type === 'password' }"
				:aria-invalid="error ? 'true' : undefined"
				:aria-describedby="error ? `${id}-error` : hint ? `${id}-hint` : undefined"
				@input="$emit('update:modelValue', $event.target.value)"
			/>
			<button v-if="type === 'password'" type="button" class="auth-password-toggle focus-ring" :aria-label="fr ? `${showPassword ? 'Masquer' : 'Afficher'} le champ « ${label} »` : `${showPassword ? 'Hide' : 'Show'} ${label.toLowerCase()}`" :aria-pressed="showPassword" :aria-controls="id" @click="showPassword = !showPassword">
				<icon :name="showPassword ? 'eye-slash' : 'eye'" />
			</button>
		</div>
		<p v-if="error" :id="`${id}-error`" class="auth-field-error">{{ error }}</p>
		<p v-else-if="hint" :id="`${id}-hint`" class="auth-hint">{{ hint }}</p>
	</div>
</template>

<script>
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
		return { showPassword: false };
	},
	computed: {
		fr() {
			return this.$page.props.locale === 'fr';
		},
	},
};
</script>
