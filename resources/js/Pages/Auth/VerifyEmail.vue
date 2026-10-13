<template>
	<div class="auth-message-icon"><icon name="mail" /></div>
	<header class="auth-heading">
		<p class="auth-kicker">Almost there</p>
		<h1>Check your inbox.</h1>
		<p>Follow the link in your verification email to confirm your address and get started.</p>
	</header>
	<auth-feedback :status="verificationLinkSent ? 'A new verification link is on its way. Check your inbox and spam folder.' : ''" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<p class="auth-hint">Can’t find the email? Check your spam folder, or send a new link below.</p>
		<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Sending email…' : 'Resend verification email' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<div class="auth-switch"><Link :href="route('logout')" method="post" as="button" class="auth-link focus-ring">Log out</Link></div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
export default {
	layout: BreezeGuestLayout,

	components: {
		BreezeButton, AuthFeedback,
	},

	props: {
		status: String
	},

	data() {
		return {
			form: useForm()
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('verification.send'))
		},
	},

	computed: {
		verificationLinkSent() {
			return this.status === 'verification-link-sent';
		}
	}
}
</script>
