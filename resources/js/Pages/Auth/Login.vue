<template>
	<auth-heading :kicker="text('Hello again', 'Bon retour')" :description="text('Log in to pick up where you left off.', 'Connectez-vous pour reprendre là où vous en étiez.')">{{ text('Welcome back.', 'Content de vous revoir.') }}</auth-heading>
	<auth-feedback :status="status" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<div :class="classes.fields">
			<auth-field id="email" :label="text('Email address', 'Adresse e-mail')" type="email" v-model="form.email" :error="form.errors.email" :placeholder="text('you@example.com', 'vous@exemple.com')" required autofocus autocomplete="username" />
			<auth-field id="password" :label="text('Password', 'Mot de passe')" type="password" v-model="form.password" :error="form.errors.password" required autocomplete="current-password" />
		</div>
		<div class="my-6 flex flex-wrap items-center justify-between gap-3 text-[14px]">
			<label class="m-0 inline-flex cursor-pointer items-center gap-2.5 text-[14px] font-normal text-subtle"><breeze-checkbox name="remember" v-model:checked="form.remember" /><span>{{ text('Keep me logged in', 'Rester connecté') }}</span></label>
			<Link v-if="canResetPassword" :href="`${prefix}/forgot-password`" :class="classes.link">{{ text('Forgot password?', 'Mot de passe oublié ?') }}</Link>
		</div>
		<breeze-button :disabled="form.processing">{{ form.processing ? text('Logging in…', 'Connexion…') : text('Log in', 'Se connecter') }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p :class="classes.switchRow">{{ text('New here?', 'Nouveau sur Nextward ?') }} <Link :href="`${prefix}/register`" :class="classes.link">{{ text('Create an account', 'Créer un compte') }}</Link></p>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import BreezeCheckbox from '@/Components/Breeze/Checkbox.vue';
export default {
	layout: BreezeGuestLayout,

	components: { AuthHeading, AuthField, AuthFeedback, BreezeButton, BreezeCheckbox },

	props: {
		canResetPassword: Boolean,
		status: String,
	},

	data() {
		return {
			classes: { ...classes },
			form: useForm({
				email: '',
				password: '',
				remember: false
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
			this.form
				.transform(data => ({
					...data,
					remember: this.form.remember ? 'on' : ''
				}))
				.post(`${this.prefix}/login`, {
					onFinish: () => this.form.reset('password'),
				})
		}
	}
}
</script>
