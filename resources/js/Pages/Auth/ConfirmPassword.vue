<template>
	<header class="auth-heading">
		<p class="auth-kicker">One quick check</p>
		<h1>Make sure<br />it’s you.</h1>
		<p>Confirm your password to continue to this secure area of your account.</p>
	</header>
	<auth-feedback :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<auth-field id="password" label="Password" type="password" v-model="form.password" :error="form.errors.password" required autocomplete="current-password" autofocus />
		<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Confirming…' : 'Confirm password' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p class="auth-switch"><Link :href="route('dashboard')" class="auth-link focus-ring">Back to your dashboard</Link></p>
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

	data() {
		return {
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
