<template>
	<header class="auth-heading">
		<p class="auth-kicker">A fresh start</p>
		<h1>A new password.<br />A fresh start.</h1>
		<p>Choose a new password for your Nextward account.</p>
	</header>
	<auth-feedback :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<div class="auth-fields">
			<auth-field id="email" label="Email address" type="email" v-model="form.email" :error="form.errors.email" required autofocus autocomplete="username" />
			<auth-field id="password" label="New password" type="password" v-model="form.password" :error="form.errors.password" hint="Use at least 8 characters." minlength="8" required autocomplete="new-password" />
			<auth-field id="password_confirmation" label="Confirm new password" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required autocomplete="new-password" />
		</div>
		<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Updating password…' : 'Reset password' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p class="auth-switch"><Link :href="route('login')" class="auth-link focus-ring">Back to log in</Link></p>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
export default {
	layout: BreezeGuestLayout,

	components: { AuthField, AuthFeedback, BreezeButton },

	props: {
		email: String,
		token: String,
	},

	data() {
		return {
			form: useForm({
				token: this.token,
				email: this.email,
				password: '',
				password_confirmation: '',
			})
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('password.update'), {
				onFinish: () => this.form.reset('password', 'password_confirmation'),
			})
		}
	}
}
</script>
