<template>
	<div class="auth-shell" :class="{ 'auth-wide': isRegister }">
		<Head :title="`${pageTitle} | Nextward`" />
		<a href="#auth-main" class="auth-skip focus-ring">{{ text('Skip to form', 'Aller au formulaire') }}</a>

		<div class="auth-grid">
			<aside class="auth-story" :aria-label="text('Your next chapter', 'Votre prochaine étape')">
				<Link :href="prefix || '/'" class="focus-ring auth-brand"><brand-logo size="sm" /><span>Nextward</span></Link>
				<div class="auth-story-inner">
					<p class="auth-kicker">{{ text('Your next chapter', 'Votre prochaine étape') }}</p>
					<h2 v-if="fr">Petit pas.<br /><span>Grand départ.</span></h2>
					<h2 v-else>Small step.<br /><span>Big beginning.</span></h2>
					<p class="auth-story-description">{{ text('Find internships and jobs for students and professionals, in Tunisia and worldwide.', 'Des stages et des emplois pour étudiants et professionnels, en Tunisie et à l’international.') }}</p>
					<figure class="hero-stage auth-illustration" :class="{ 'is-scene': illustration.scene }">
						<img :src="illustration.src" alt="" :width="illustration.width" :height="illustration.height" decoding="async" />
						<Sparkle style="top: 10%; left: 6%; --i: 0" />
						<Sparkle class="is-sky" style="top: 4%; right: 10%; width: 14px; height: 14px; --i: 1" />
					</figure>
					<Link :href="`${prefix}/opportunities`" class="auth-story-link focus-ring"><span>{{ text('Just looking? Explore opportunities', 'Juste pour voir ? Explorez les opportunités') }}</span><icon name="arrow-up-right" /></Link>
					<p class="auth-story-note">{{ text('No account needed to explore.', 'Pas besoin de compte pour explorer.') }}</p>
				</div>
				<p class="auth-story-foot">{{ text('A little ambition. A world of possibility.', 'Un peu d’ambition. Un monde de possibilités.') }}</p>
			</aside>

			<div class="auth-panel">
				<header class="auth-header">
					<Link :href="prefix || '/'" class="focus-ring auth-brand auth-brand-mobile"><brand-logo size="sm" /><span>Nextward</span></Link>
					<div class="flex items-center gap-4 sm:gap-6">
						<a v-if="languageHref" :href="languageHref" :hreflang="fr ? 'en' : 'fr'" :lang="fr ? 'en' : 'fr'" class="auth-back focus-ring">{{ fr ? 'English' : 'Français' }}</a>
						<Link :href="prefix || '/'" class="auth-back focus-ring"><icon name="arrow-left" /><span>{{ text('Back to home', 'Retour à l’accueil') }}</span></Link>
					</div>
				</header>
				<main id="auth-main" tabindex="-1" class="auth-main">
					<div class="auth-form">
						<div v-if="$page.props.toast?.message" class="auth-feedback" :class="$page.props.toast.type === 'error' ? 'auth-error' : 'auth-success'" :role="$page.props.toast.type === 'error' ? 'alert' : 'status'"><icon :name="$page.props.toast.type === 'error' ? 'exclamation-circle' : 'check-circle'" /><p>{{ $page.props.toast.message }}</p></div>
						<slot />
					</div>
				</main>
				<footer class="auth-footer">
					<p>{{ text('Questions about your account?', 'Une question sur votre compte ?') }} <Link :href="`${prefix}/contact`" class="focus-ring">{{ text('Need a hand? Let’s talk', 'Besoin d’aide ? Parlons-en') }}</Link></p>
					<nav :aria-label="text('Legal', 'Informations légales')" class="auth-legal">
						<Link :href="`${prefix}/privacy`" class="focus-ring">{{ text('Privacy', 'Confidentialité') }}</Link>
						<Link :href="`${prefix}/terms`" class="focus-ring">{{ text('Terms', 'Conditions d’utilisation') }}</Link>
					</nav>
				</footer>
			</div>
		</div>
	</div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import Sparkle from '@/Shared/Sparkle.vue';

const pageTitles = {
	'Auth/Login': ['Log in', 'Connexion'],
	'Auth/Register': ['Create an account', 'Créer un compte'],
	'Auth/ForgotPassword': ['Forgot password', 'Mot de passe oublié'],
	'Auth/ResetPassword': ['Reset password', 'Nouveau mot de passe'],
	'Auth/ConfirmPassword': ['Confirm password', 'Confirmer le mot de passe'],
	'Auth/VerifyEmail': ['Verify your email', 'Vérifier votre e-mail'],
};

export default {
	components: { Head, Sparkle },
	computed: {
		fr() {
			return this.$page.props.locale === 'fr';
		},
		prefix() {
			return this.fr ? '/fr' : '';
		},
		languageHref() {
			if (!['Auth/Login', 'Auth/Register', 'Auth/ForgotPassword'].includes(this.$page.component)) return null;
			return this.fr ? this.$page.url.replace(/^\/fr/, '') : `/fr${this.$page.url}`;
		},
		isRegister() {
			return this.$page.component === 'Auth/Register';
		},
		pageTitle() {
			return this.text(...(pageTitles[this.$page.component] || ['Your account', 'Votre compte']));
		},
		illustration() {
			if (this.isRegister) return { src: '/nextward-assets/mascots/15-student-company-conversation.svg', width: 1100, height: 760, scene: true };
			if (this.$page.component === 'Auth/Login') return { src: '/nextward-assets/mascots/17-student-presenter.webp', width: 900, height: 900 };
			return { src: '/nextward-assets/mascots/04-mentor-pointing.svg', width: 768, height: 768 };
		},
	},
	methods: {
		text(en, french) {
			return this.fr ? french : en;
		},
	},
};
</script>

<style src="../../css/auth.css"></style>
