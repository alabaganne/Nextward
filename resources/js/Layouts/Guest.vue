<template>
	<div class="auth-shell" :class="{ 'auth-wide': isRegister }">
		<Head :title="`${pageTitle} | Internly`" />
		<a href="#auth-main" class="auth-skip focus-ring">Skip to form</a>
		<header class="auth-header">
			<Link href="/" class="focus-ring auth-brand"><brand-logo /></Link>
			<Link href="/" class="auth-back focus-ring"><icon name="arrow-left" /><span>Back to home</span></Link>
		</header>

		<div class="auth-grid">
			<aside class="auth-story" aria-label="Your next chapter">
				<div class="auth-story-inner">
					<p class="auth-kicker">Your next chapter</p>
					<h2>Small step.<br /><span>Big beginning.</span></h2>
					<p class="auth-story-description">Find your first opportunity, make your introduction, and see where it takes you.</p>
					<img src="/internly-assets/landing/career-steps.webp" alt="" width="1254" height="1254" decoding="async" class="auth-illustration" />
					<Link :href="route('internships.index')" class="auth-story-link focus-ring"><span>Just looking? Explore internships</span><icon name="arrow-up-right" /></Link>
					<p class="auth-story-note">No account needed to explore.</p>
				</div>
			</aside>

			<main id="auth-main" tabindex="-1" class="auth-main">
				<div class="auth-form"><slot /></div>
			</main>
		</div>

		<footer class="auth-footer">
			<p>A little ambition. A world of possibility.</p>
			<Link :href="route('contact')" class="focus-ring">Need a hand? Let’s talk</Link>
		</footer>
	</div>
</template>

<script>
import { Head } from '@inertiajs/vue3';

const pageTitles = {
	'Auth/Login': 'Log in',
	'Auth/Register': 'Create an account',
	'Auth/ForgotPassword': 'Forgot password',
	'Auth/ResetPassword': 'Reset password',
	'Auth/ConfirmPassword': 'Confirm password',
	'Auth/VerifyEmail': 'Verify your email',
};

export default {
	components: { Head },
	computed: {
		isRegister() {
			return this.$page.component === 'Auth/Register';
		},
		pageTitle() {
			return pageTitles[this.$page.component] || 'Your account';
		},
	},
};
</script>

<style src="../../css/auth.css"></style>
