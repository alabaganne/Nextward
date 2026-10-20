<template>
	<auth-heading kicker="One quick check" description="Confirm your password to continue to this secure area of your account.">Make sure<br />it’s you.</auth-heading>
	<auth-feedback :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<auth-field id="password" label="Password" type="password" v-model="form.password" :error="form.errors.password" required autocomplete="current-password" autofocus />
		<breeze-button class="mt-7" :disabled="form.processing">{{ form.processing ? 'Confirming…' : 'Confirm password' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p :class="classes.switchRow"><Link :href="route('dashboard')" :class="classes.link">Back to your dashboard</Link></p>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
export default {
	layout: BreezeGuestLayout,

	components: { AuthHeading, AuthField, AuthFeedback, BreezeButton },

	data() {
		return {
			classes: { ...classes },
			form: useForm({
				password: '',
			})
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('password.confirm'), {
				onFinish: () => this.form.reset(),
			})
		}
	}
}
</script>
