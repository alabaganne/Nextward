<template>
	<header class="auth-heading">
		<p class="auth-kicker">{{ text('A fresh start', 'Un nouveau départ') }}</p>
		<h1 v-if="fr">On vous aide<br />à revenir.</h1>
		<h1 v-else>Let’s get you<br />back in.</h1>
		<p>{{ text('Enter the email address you use for Nextward. We’ll send you a link to reset your password.', 'Saisissez l’adresse e-mail de votre compte Nextward. Nous vous enverrons un lien pour changer votre mot de passe.') }}</p>
	</header>
	<auth-feedback :status="status" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<auth-field id="email" :label="text('Email address', 'Adresse e-mail')" type="email" v-model="form.email" :error="form.errors.email" :placeholder="text('you@example.com', 'vous@exemple.com')" required autofocus autocomplete="username" />
		<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? text('Sending link…', 'Envoi du lien…') : text('Send reset link', 'Envoyer le lien') }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p class="auth-switch">{{ text('Remember your password?', 'Vous vous souvenez de votre mot de passe ?') }} <Link :href="`${prefix}/login`" class="auth-link focus-ring">{{ text('Back to log in', 'Retour à la connexion') }}</Link></p>
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

	computed: {
		fr() {
			return this.$page.props.locale === 'fr';
		},
		prefix() {
			return this.fr ? '/fr' : '';
		},
	},
	methods: {
		text(en, french) {
			return this.fr ? french : en;
		},
		submit() {
			this.form.post(`${this.prefix}/forgot-password`)
		}
	}
}
</script>
