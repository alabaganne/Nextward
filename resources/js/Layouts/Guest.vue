<template>
	<div class="min-h-svh bg-white text-navy motion-reduce:**:transition-none">
		<Head :title="`${pageTitle} | Nextward`" />
		<a href="#auth-main" class="focus-ring absolute h-px w-px overflow-hidden [clip-path:inset(50%)] focus:top-5 focus:left-6 focus:z-50 focus:h-auto focus:w-auto focus:rounded-[6px] focus:bg-white focus:px-[18px] focus:py-3 focus:[clip-path:none]">{{ text('Skip to form', 'Aller au formulaire') }}</a>

		<div class="grid min-h-svh grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)] max-[900px]:block">
			<aside class="line-art sticky top-0 flex h-svh flex-col justify-between gap-8 overflow-hidden bg-navy px-[clamp(28px,4.5vw,72px)] py-8 text-white short-screen:gap-5 short-screen:py-6 max-[1024px]:px-8 max-[900px]:hidden" :aria-label="text('Your next chapter', 'Votre prochaine étape')">
				<Link :href="prefix || '/'" :class="brand" class="inline-flex self-start focus-visible:outline-white"><brand-logo size="sm" /><span>Nextward</span></Link>
				<div class="mx-auto w-full max-w-[440px]">
					<p class="landing-label text-white/85">{{ text('Your next chapter', 'Votre prochaine étape') }}</p>
					<h2 class="mt-4 text-[length:clamp(40px,4vw,58px)] leading-[1.12] font-medium max-[1024px]:text-[44px] lg:short-screen:text-[length:clamp(36px,3.4vw,48px)] [&_span]:text-blue-500">
						<template v-if="fr">Petit pas.<br /><span>Grand départ.</span></template>
						<template v-else>Small step.<br /><span>Big beginning.</span></template>
					</h2>
					<p class="mt-5 max-w-[380px] text-[16px] leading-[1.75] text-mist">{{ text('Find IT internships and jobs for students and professionals, around the world.', 'Des stages et des emplois en informatique pour étudiants et professionnels, partout dans le monde.') }}</p>
					<figure class="mx-auto mt-7 mb-6 short-screen:max-h-[clamp(130px,calc(100svh-520px),260px)]" :class="illustration.scene ? 'w-[min(100%,400px)]' : 'w-[min(100%,290px)]'">
						<img :src="illustration.src" alt="" :width="illustration.width" :height="illustration.height" decoding="async" class="h-auto w-full short-screen:mx-auto short-screen:max-h-[inherit] short-screen:w-auto" />
					</figure>
					<Link :href="`${prefix}/opportunities`" class="group focus-ring inline-flex items-center gap-2.5 rounded-[3px] py-[5px] text-[14px] font-medium tracking-caps text-white uppercase focus-visible:outline-white">{{ text('Just looking? Explore roles', 'Juste pour voir ? Explorez les offres') }}<icon name="arrow-up-right" class="size-[17px] shrink-0 transition-transform duration-180 group-hover:translate-x-[2px] group-hover:-translate-y-[2px] motion-reduce:transition-none" /></Link>
					<p class="mt-1.5 text-[13px] text-mist-2">{{ text('No account needed to explore.', 'Pas besoin de compte pour explorer.') }}</p>
				</div>
				<p class="text-[13px] text-mist-2">{{ text('A little ambition. A world of possibility.', 'Un peu d’ambition. Un monde de possibilités.') }}</p>
			</aside>

			<div class="flex min-h-svh min-w-0 flex-col">
				<header class="flex h-[88px] items-center justify-end gap-6 px-[clamp(24px,4.5vw,72px)] max-[1024px]:px-8 max-[900px]:justify-between max-[900px]:bg-navy max-[900px]:text-white max-[540px]:h-[72px] max-[540px]:gap-4 max-[540px]:px-6">
					<Link :href="prefix || '/'" :class="brand" class="hidden self-center max-[900px]:inline-flex max-[900px]:focus-visible:outline-white"><brand-logo size="sm" /><span>Nextward</span></Link>
					<div class="flex items-center gap-4 sm:gap-6">
						<a v-if="languageHref" :href="languageHref" :hreflang="fr ? 'en' : 'fr'" :lang="fr ? 'en' : 'fr'" :class="back">{{ fr ? 'English' : 'Français' }}</a>
						<Link :href="prefix || '/'" :class="back"><icon name="arrow-left" class="size-4" /><span>{{ text('Back to home', 'Retour à l’accueil') }}</span></Link>
					</div>
				</header>
				<main id="auth-main" tabindex="-1" class="flex min-w-0 flex-1 justify-center px-[clamp(28px,4.5vw,72px)] pb-16 outline-none max-[1024px]:px-8 max-[900px]:px-8 max-[900px]:pb-14 max-[540px]:px-6 max-[540px]:pb-12" :class="isRegister ? 'items-start pt-2' : 'items-center pt-4 max-[900px]:pt-12 max-[540px]:items-start max-[540px]:pt-10'">
					<div class="w-full" :class="isRegister ? 'max-w-[560px]' : 'max-w-[420px]'">
						<div v-if="$page.props.toast?.message" class="mb-6 flex items-start gap-3 rounded-card px-5 py-4 text-[14px] leading-[1.7]" :class="$page.props.toast.type === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800'" :role="$page.props.toast.type === 'error' ? 'alert' : 'status'"><icon :name="$page.props.toast.type === 'error' ? 'exclamation-circle' : 'check-circle'" class="mt-0.5 size-[19px] shrink-0" /><p>{{ $page.props.toast.message }}</p></div>
						<slot />
					</div>
				</main>
				<footer class="flex min-h-16 items-center justify-between gap-5 border-t border-line px-[clamp(24px,4.5vw,72px)] py-[18px] text-[13px] text-subtle-2 max-[540px]:flex-wrap max-[540px]:gap-x-[18px] max-[540px]:gap-y-2 max-[540px]:px-6">
					<p>{{ text('Questions about your account?', 'Une question sur votre compte ?') }} <Link :href="`${prefix}/contact`" :class="footerLink">{{ text('Need a hand? Let’s talk', 'Besoin d’aide ? Parlons-en') }}</Link></p>
					<nav :aria-label="text('Legal', 'Informations légales')" class="flex gap-[18px]">
						<Link :href="`${prefix}/privacy`" :class="footerLink">{{ text('Privacy', 'Confidentialité') }}</Link>
						<Link :href="`${prefix}/terms`" :class="footerLink">{{ text('Terms', 'Conditions d’utilisation') }}</Link>
					</nav>
				</footer>
			</div>
		</div>
	</div>
</template>

<script>
import { Head } from '@inertiajs/vue3';

const pageTitles = {
	'Auth/Login': ['Log in', 'Connexion'],
	'Auth/Register': ['Create an account', 'Créer un compte'],
	'Auth/ForgotPassword': ['Forgot password', 'Mot de passe oublié'],
	'Auth/ResetPassword': ['Reset password', 'Nouveau mot de passe'],
	'Auth/ConfirmPassword': ['Confirm password', 'Confirmer le mot de passe'],
	'Auth/VerifyEmail': ['Verify your email', 'Vérifier votre e-mail'],
};

export default {
	components: { Head },
	data() {
		return {
			brand: 'focus-ring items-center gap-2.5 rounded-lg text-[21px] font-semibold [&_img]:!size-9',
			back: 'focus-ring inline-flex items-center gap-2.5 rounded-[3px] py-2 text-[14px] text-subtle transition-colors duration-180 hover:text-brand max-[900px]:text-mist max-[900px]:hover:text-white max-[900px]:focus-visible:outline-white max-[540px]:gap-[7px] max-[540px]:text-[12px]',
			footerLink: 'focus-ring rounded-[2px] font-medium text-subtle hover:text-brand',
		};
	},
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

