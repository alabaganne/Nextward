<template>
	<div class="auth-shell" :class="{ 'auth-wide': isRegister }">
		<Head :title="`${pageTitle} | Nextward`" />
		<a href="#auth-main" class="auth-skip focus-ring">Skip to form</a>

		<div class="auth-grid">
			<aside class="auth-story" aria-label="Your next chapter">
				<Link href="/" class="focus-ring auth-brand"><brand-logo size="sm" /><span>Nextward</span></Link>
				<div class="auth-story-inner">
					<p class="auth-kicker"><span aria-hidden="true"></span>Your next chapter</p>
					<h2>Small step.<br /><span>Big beginning.</span></h2>
					<p class="auth-story-description">Find internships and jobs for students and professionals, in Tunisia and worldwide.</p>
					<figure class="hero-stage auth-illustration" :class="{ 'is-scene': illustration.scene }">
						<img :src="illustration.src" alt="" :width="illustration.width" :height="illustration.height" decoding="async" />
						<Sparkle style="top: 10%; left: 6%; --i: 0" />
						<Sparkle class="is-sky" style="top: 4%; right: 10%; width: 14px; height: 14px; --i: 1" />
					</figure>
					<Link :href="route('opportunities.index')" class="auth-story-link focus-ring"><span>Just looking? Explore opportunities</span><icon name="arrow-up-right" /></Link>
					<p class="auth-story-note">No account needed to explore.</p>
				</div>
				<p class="auth-story-foot">A little ambition. A world of possibility.</p>
			</aside>

			<div class="auth-panel">
				<header class="auth-header">
					<Link href="/" class="focus-ring auth-brand auth-brand-mobile"><brand-logo size="sm" /><span>Nextward</span></Link>
					<Link href="/" class="auth-back focus-ring"><icon name="arrow-left" /><span>Back to home</span></Link>
				</header>
				<main id="auth-main" tabindex="-1" class="auth-main">
					<div class="auth-form"><slot /></div>
				</main>
				<footer class="auth-footer">
					<p>Questions about your account? <Link :href="route('contact')" class="focus-ring">Need a hand? Let’s talk</Link></p>
					<nav aria-label="Legal" class="auth-legal">
						<Link :href="route('privacy')" class="focus-ring">Privacy</Link>
						<Link :href="route('terms')" class="focus-ring">Terms</Link>
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
	'Auth/Login': 'Log in',
	'Auth/Register': 'Create an account',
	'Auth/ForgotPassword': 'Forgot password',
	'Auth/ResetPassword': 'Reset password',
	'Auth/ConfirmPassword': 'Confirm password',
	'Auth/VerifyEmail': 'Verify your email',
};

export default {
	components: { Head, Sparkle },
	computed: {
		isRegister() {
			return this.$page.component === 'Auth/Register';
		},
		pageTitle() {
			return pageTitles[this.$page.component] || 'Your account';
		},
		illustration() {
			if (this.isRegister) return { src: '/nextward-assets/mascots/15-student-company-conversation.svg', width: 1100, height: 760, scene: true };
			if (this.$page.component === 'Auth/Login') return { src: '/nextward-assets/mascots/17-student-presenter.webp', width: 900, height: 900 };
			return { src: '/nextward-assets/mascots/04-mentor-pointing.svg', width: 768, height: 768 };
		},
	},
};
</script>

<style src="../../css/auth.css"></style>
