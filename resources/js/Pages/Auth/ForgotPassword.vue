<template>
	<header class="auth-heading">
		<p class="auth-kicker">A fresh start</p>
		<h1>Let’s get you<br />back in.</h1>
		<p>Enter the email address you use for Internly. We’ll send you a link to reset your password.</p>
	</header>
	<auth-feedback :status="status" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<auth-field id="email" label="Email address" type="email" v-model="form.email" :error="form.errors.email" placeholder="you@example.com" required autofocus autocomplete="username" />
		<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Sending link…' : 'Send reset link' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p class="auth-switch">Remember your password? <Link :href="route('login')" class="auth-link focus-ring">Back to log in</Link></p>
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
		status: String
	},

	data() {
		return {
			form: useForm({
				email: ''
			})
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('password.email'))
		}
	}
}
</script>
