<template>
	<div class="mb-6 flex size-12 items-center justify-center rounded-card bg-blue-50 text-brand"><icon name="mail" class="size-[23px]" /></div>
	<auth-heading kicker="Almost there" description="Follow the link in your verification email to confirm your address and get started.">Check your inbox.</auth-heading>
	<auth-feedback :status="verificationLinkSent ? 'A new verification link is on its way. Check your inbox and spam folder.' : ''" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<p :class="classes.hint">Can’t find the email? Check your spam folder, or send a new link below.</p>
		<breeze-button class="mt-7" :disabled="form.processing">{{ form.processing ? 'Sending email…' : 'Resend verification email' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<div :class="classes.switchRow"><Link :href="route('logout')" method="post" as="button" :class="classes.link">Log out</Link></div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
export default {
	layout: BreezeGuestLayout,

	components: {
		BreezeButton, AuthFeedback, AuthHeading,
	},

	props: {
		status: String
	},

	data() {
		return {
			classes: { ...classes },
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
