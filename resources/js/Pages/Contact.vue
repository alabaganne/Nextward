<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import Sparkle from '@/Shared/Sparkle.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const user = computed(() => page.props.auth?.user);
const contactEmail = 'alabaganne9@gmail.com';

const topics = computed(() => [
	['general', text('General question', 'Question générale')],
	['listing', text('Problem with a listing', 'Problème avec une offre')],
	['employer', text('Hiring on Nextward', 'Recruter sur Nextward')],
	['partnership', text('Source or partnership', 'Source ou partenariat')],
	['feedback', text('Product feedback', 'Avis sur le produit')],
]);

const form = useForm({
	name: user.value?.name ?? '',
	email: user.value?.email ?? '',
	topic: 'general',
	message: '',
	page_url: '',
	website: '',
});
const sent = ref(false);

const submit = () => form.post('/contact', {
	preserveScroll: true,
	onSuccess: () => { sent.value = true; form.reset('message', 'page_url'); },
});
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="contact-title" class="hero-dark">
				<div class="hero-grid public-container">
					<div>
						<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>Contact</p>
						<h1 id="contact-title" class="hero-title">{{ text('Let’s talk about your next opportunity.', 'Parlons de votre prochaine opportunité.') }}</h1>
						<p class="hero-lead">{{ text('Students, professionals, employers and source partners can all reach the Nextward team here. We read every message.', 'Étudiants, professionnels, employeurs et partenaires peuvent écrire à l’équipe Nextward ici. Nous lisons chaque message.') }}</p>
					</div>
					<figure class="hero-stage contact-stage">
						<img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" fetchpriority="high" />
						<Sparkle style="top: 14%; right: 10%; --i: 0" />
						<Sparkle class="is-sky" style="top: 30%; left: 12%; width: 14px; height: 14px; --i: 1" />
					</figure>
				</div>
			</section>

			<section class="public-container py-20 grid lg:grid-cols-[1.4fr_1fr] gap-14">
				<div>
					<h2 class="section-title !mt-0">{{ text('Send us a message', 'Envoyez-nous un message') }}</h2>

					<div v-if="sent" class="contact-sent mt-8" role="status">
						<Icon name="check-circle" />
						<div>
							<p class="font-semibold text-navy">{{ text('Thanks, your message is on its way.', 'Merci, votre message est bien parti.') }}</p>
							<p class="mt-1 text-slate-600">{{ text('We will reply to', 'Nous répondrons à') }} {{ form.email }}.</p>
							<button type="button" class="mt-3 font-semibold text-brand hover:text-brand-dark focus-ring" @click="sent = false">{{ text('Send another message', 'Envoyer un autre message') }}</button>
						</div>
					</div>

					<form v-else class="contact-form mt-8" novalidate @submit.prevent="submit">
						<div class="grid sm:grid-cols-2 gap-5">
							<div>
								<label for="contact-name">{{ text('Your name', 'Votre nom') }}</label>
								<input id="contact-name" v-model="form.name" type="text" autocomplete="name" maxlength="120" required :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'contact-name-error' : null" />
								<p v-if="form.errors.name" id="contact-name-error" class="contact-error">{{ form.errors.name }}</p>
							</div>
							<div>
								<label for="contact-email">{{ text('Email address', 'Adresse e-mail') }}</label>
								<input id="contact-email" v-model="form.email" type="email" autocomplete="email" maxlength="200" required :aria-invalid="!!form.errors.email" :aria-describedby="form.errors.email ? 'contact-email-error' : null" />
								<p v-if="form.errors.email" id="contact-email-error" class="contact-error">{{ form.errors.email }}</p>
							</div>
						</div>
						<div>
							<label for="contact-topic">{{ text('What is it about?', 'Quel est le sujet ?') }}</label>
							<select id="contact-topic" v-model="form.topic">
								<option v-for="[value, label] in topics" :key="value" :value="value">{{ label }}</option>
							</select>
						</div>
						<div v-if="form.topic === 'listing'">
							<label for="contact-url">{{ text('Link to the listing', 'Lien de l’offre') }}</label>
							<input id="contact-url" v-model="form.page_url" type="url" inputmode="url" maxlength="300" placeholder="https://" :aria-invalid="!!form.errors.page_url" :aria-describedby="form.errors.page_url ? 'contact-url-error' : null" />
							<p v-if="form.errors.page_url" id="contact-url-error" class="contact-error">{{ form.errors.page_url }}</p>
						</div>
						<div>
							<label for="contact-message">{{ text('Message', 'Message') }}</label>
							<textarea id="contact-message" v-model="form.message" rows="7" minlength="20" maxlength="5000" required :aria-invalid="!!form.errors.message" aria-describedby="contact-message-hint" />
							<p v-if="form.errors.message" class="contact-error">{{ form.errors.message }}</p>
							<p id="contact-message-hint" class="contact-hint">{{ text('Please don’t send passwords or CVs here.', 'N’envoyez ni mot de passe ni CV ici.') }}</p>
						</div>
						<div class="contact-trap" aria-hidden="true">
							<label for="contact-website">Website</label>
							<input id="contact-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
						</div>
						<button type="submit" class="cta cta-primary focus-ring" :disabled="form.processing">
							{{ form.processing ? text('Sending…', 'Envoi…') : text('Send message', 'Envoyer le message') }}<Icon name="arrow-right" class="action-icon" />
						</button>
					</form>
				</div>

				<aside class="self-start rounded-2xl bg-white border border-line p-8">
					<h3 class="text-xl font-semibold text-navy">{{ text('Good to know', 'Bon à savoir') }}</h3>
					<ul class="contact-notes mt-5">
						<li><Icon name="exclamation-triangle" /><span>{{ text('To fix or remove a listing, choose “Problem with a listing” and paste its link. You can also use “Report an issue” on the listing page.', 'Pour corriger ou retirer une offre, choisissez « Problème avec une offre » et collez son lien. Vous pouvez aussi utiliser « Signaler un problème » sur la page de l’offre.') }}</span></li>
						<li><Icon name="briefcase" /><span>{{ text('Employers can post roles for free after creating a company account.', 'Les employeurs peuvent publier des offres gratuitement après avoir créé un compte entreprise.') }}</span></li>
						<li><Icon name="link" /><span>{{ text('Job boards and employers can write to us about permission, attribution or approved feeds.', 'Les sites d’emploi et les employeurs peuvent nous écrire au sujet des autorisations, de l’attribution ou des flux.') }}</span></li>
					</ul>
					<div class="mt-8 pt-6 border-t border-line text-sm text-slate-600">
						<p>{{ text('Prefer email?', 'Vous préférez l’e-mail ?') }} <a :href="`mailto:${contactEmail}`" class="font-semibold text-brand hover:text-brand-dark focus-ring">{{ contactEmail }}</a></p>
						<p class="mt-2"><Link :href="`${prefix}/register?account=company`" class="font-semibold text-brand hover:text-brand-dark focus-ring">{{ text('Post a role', 'Publier une offre') }}</Link> · <Link :href="`${prefix}/opportunities`" class="font-semibold text-brand hover:text-brand-dark focus-ring">{{ text('Browse opportunities', 'Voir les offres') }}</Link></p>
					</div>
				</aside>
			</section>
		</div>
	</PublicLayout>
</template>

<style scoped>
.contact-stage { width: min(100%, 340px); justify-self: center; }
.contact-form { display: grid; gap: 20px; padding: 28px; border: 1px solid var(--color-line); border-radius: 16px; background: #fff; }
.contact-form label { display: block; margin-bottom: 6px; color: var(--color-navy); font-size: 14px; font-weight: 600; }
.contact-form input, .contact-form select, .contact-form textarea { width: 100%; border: 1px solid var(--color-line); border-radius: 12px; background-color: #fff; padding: 11px 14px; font-size: 15px; color: var(--color-ink); }
.contact-form input:focus-visible, .contact-form select:focus-visible, .contact-form textarea:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 1px; border-color: var(--color-brand); }
.contact-form [aria-invalid="true"] { border-color: #dc2626; }
.contact-form button[disabled] { opacity: .7; cursor: wait; }
.contact-error { margin-top: 6px; color: #b91c1c; font-size: 13px; }
.contact-hint { margin-top: 6px; color: #67707e; font-size: 13px; }
.contact-trap { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
.contact-sent { display: flex; gap: 14px; padding: 22px 24px; border: 1px solid #bbf7d0; border-radius: 16px; background: #f0fdf4; }
.contact-sent svg { width: 24px; height: 24px; flex-shrink: 0; color: #16a34a; }
.contact-notes { display: grid; gap: 16px; color: #475569; font-size: 15px; line-height: 1.6; }
.contact-notes li { display: flex; gap: 12px; }
.contact-notes svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 3px; color: var(--color-brand); }
</style>
