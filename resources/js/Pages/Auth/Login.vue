<template>
	<header class="auth-heading">
		<p class="auth-kicker">Hello again</p>
		<h1>Welcome back.</h1>
		<p>Log in to pick up where you left off.</p>
	</header>
	<auth-feedback :status="status" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<div class="auth-fields">
			<auth-field id="email" label="Email address" type="email" v-model="form.email" :error="form.errors.email" placeholder="you@example.com" required autofocus autocomplete="username" />
			<auth-field id="password" label="Password" type="password" v-model="form.password" :error="form.errors.password" required autocomplete="current-password" />
		</div>
		<div class="auth-options">
			<label class="auth-remember"><breeze-checkbox name="remember" v-model:checked="form.remember" /><span>Keep me logged in</span></label>
			<Link v-if="canResetPassword" :href="route('password.request')" class="auth-link focus-ring">Forgot password?</Link>
		</div>
		<breeze-button :disabled="form.processing">{{ form.processing ? 'Logging in…' : 'Log in' }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p class="auth-switch">New here? <Link :href="route('register')" class="auth-link focus-ring">Create an account</Link></p>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import BreezeCheckbox from '@/Components/Breeze/Checkbox.vue';
export default {
	layout: BreezeGuestLayout,

	components: { AuthField, AuthFeedback, BreezeButton, BreezeCheckbox },

	props: {
		canResetPassword: Boolean,
		status: String,
	},

	data() {
		return {
			form: useForm({
				email: '',
				password: '',
				remember: false
			})
		}
	},

	methods: {
		submit() {
			this.form
				.transform(data => ({
					...data,
					remember: this.form.remember ? 'on' : ''
				}))
				.post(this.route('login'), {
					onFinish: () => this.form.reset('password'),
				})
		}
	}
}
</script>
