<script setup>
import { computed, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import Sparkle from '@/Shared/Sparkle.vue';
import { previousPageUrl } from '@/composables/usePreviousPage';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const user = computed(() => page.props.auth?.user);
const contactEmail = 'alabaganne9@gmail.com';

const topics = computed(() => [
	{ value: 'help', icon: 'lifebuoy', label: text('Get help', 'Obtenir de l’aide'), hint: text('Stuck with your account, an application or a page.', 'Bloqué avec votre compte, une candidature ou une page.'), placeholder: text('Tell us what you are trying to do and where you are stuck.', 'Dites-nous ce que vous essayez de faire et où vous bloquez.') },
	{ value: 'bug', icon: 'bug', label: text('Report a bug', 'Signaler un bug'), hint: text('Something is broken, wrong or will not load.', 'Quelque chose ne marche pas, s’affiche mal ou ne charge pas.'), placeholder: text('What did you do, what did you expect, and what happened instead?', 'Qu’avez-vous fait, à quoi vous attendiez-vous et que s’est-il passé ?') },
	{ value: 'feature', icon: 'light-bulb', label: text('Suggest a feature', 'Proposer une idée'), hint: text('Something Nextward should do or do better.', 'Ce que Nextward devrait faire, ou mieux faire.'), placeholder: text('What would you like Nextward to do, and how would it help you?', 'Que voudriez-vous que Nextward fasse, et en quoi cela vous aiderait ?') },
	{ value: 'listing', icon: 'exclamation-triangle', label: text('Problem with a listing', 'Problème avec une offre'), hint: text('Wrong details, a dead link or a suspicious role.', 'Infos fausses, lien mort ou offre suspecte.'), placeholder: text('What is wrong with this listing?', 'Quel est le problème avec cette offre ?') },
	{ value: 'business', icon: 'briefcase', label: text('Hiring or partnership', 'Recrutement ou partenariat'), hint: text('Post roles, share a job feed or work with us.', 'Publier des offres, partager un flux ou travailler avec nous.'), placeholder: text('Tell us about your company or project.', 'Parlez-nous de votre entreprise ou de votre projet.') },
	{ value: 'other', icon: 'chat-alt-2', label: text('Something else', 'Autre chose'), hint: text('Any other question or comment.', 'Toute autre question ou remarque.'), placeholder: text('How can we help?', 'Comment pouvons-nous vous aider ?') },
]);

const askedTopic = new URLSearchParams(page.url.split('?')[1] ?? '').get('topic');
const form = useForm({
	name: user.value?.name ?? '',
	email: user.value?.email ?? '',
	topic: ['help', 'bug', 'feature', 'listing', 'business', 'other'].includes(askedTopic) ? askedTopic : 'help',
	message: '',
	page_url: '',
	screenshot: null,
	website: '',
});
const topic = computed(() => topics.value.find((item) => item.value === form.topic));
const asksForPage = computed(() => ['help', 'bug', 'listing'].includes(form.topic));
const asksForScreenshot = computed(() => ['help', 'bug'].includes(form.topic));
const pageFilled = ref(false);
const sent = ref(false);

onMounted(() => {
	form.page_url = previousPageUrl();
	pageFilled.value = !!form.page_url;
});

const pickScreenshot = (event) => { form.screenshot = event.target.files[0] ?? null; };

const submit = () => form
	.transform((data) => ({ ...data, page_url: asksForPage.value ? data.page_url : '', screenshot: asksForScreenshot.value ? data.screenshot : null }))
	.post('/contact', {
		preserveScroll: true,
		onSuccess: () => { sent.value = true; pageFilled.value = false; form.reset('message', 'page_url', 'screenshot'); },
	});
</script>

<template>
	<PublicLayout>
		<div class="landing">
			<section aria-labelledby="contact-title" class="hero-dark">
				<div class="public-container hero-grid hero-pad">
					<div>
						<p class="landing-label">{{ text('Help and contact', 'Aide et contact') }}</p>
						<h1 id="contact-title" class="hero-title">{{ text('How can we help?', 'Comment pouvons-nous vous aider ?') }}</h1>
						<p class="hero-lead">{{ text('Ask for help with anything, report a bug or suggest a feature. We read every message and answer by email.', 'Demandez de l’aide sur n’importe quel sujet, signalez un bug ou proposez une idée. Nous lisons chaque message et répondons par e-mail.') }}</p>
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
						<fieldset>
							<legend>{{ text('What is it about?', 'Quel est le sujet ?') }}</legend>
							<div class="topic-grid">
								<label v-for="item in topics" :key="item.value" class="topic-option" :class="{ 'is-picked': form.topic === item.value }">
									<input v-model="form.topic" type="radio" name="topic" :value="item.value" class="sr-only" />
									<Icon :name="item.icon" />
									<span class="topic-label">{{ item.label }}</span>
									<span class="topic-hint">{{ item.hint }}</span>
								</label>
							</div>
							<p v-if="form.errors.topic" class="contact-error">{{ form.errors.topic }}</p>
						</fieldset>
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
							<label for="contact-message">{{ text('Message', 'Message') }}</label>
							<textarea id="contact-message" v-model="form.message" rows="7" minlength="10" maxlength="5000" required :placeholder="topic.placeholder" :aria-invalid="!!form.errors.message" aria-describedby="contact-message-hint" />
							<p v-if="form.errors.message" class="contact-error">{{ form.errors.message }}</p>
							<p id="contact-message-hint" class="contact-hint">{{ text('Please don’t send passwords or CVs here.', 'N’envoyez ni mot de passe ni CV ici.') }}</p>
						</div>
						<div v-if="asksForPage">
							<label for="contact-url">{{ form.topic === 'listing' ? text('Link to the listing', 'Lien de l’offre') : text('Page where it happened (optional)', 'Page concernée (facultatif)') }}</label>
							<input id="contact-url" v-model="form.page_url" type="url" inputmode="url" maxlength="300" placeholder="https://" :aria-invalid="!!form.errors.page_url" :aria-describedby="form.errors.page_url ? 'contact-url-error' : (pageFilled ? 'contact-url-hint' : null)" />
							<p v-if="form.errors.page_url" id="contact-url-error" class="contact-error">{{ form.errors.page_url }}</p>
							<p v-else-if="pageFilled" id="contact-url-hint" class="contact-hint">{{ text('We filled in the page you came from. Change or clear it if it’s not the right one.', 'Nous avons mis la page d’où vous venez. Modifiez-la ou effacez-la si ce n’est pas la bonne.') }}</p>
						</div>
						<div v-if="asksForScreenshot">
							<label for="contact-screenshot">{{ text('Screenshot (optional)', 'Capture d’écran (facultatif)') }}</label>
							<input id="contact-screenshot" type="file" accept="image/png,image/jpeg,image/webp" class="contact-file" :aria-invalid="!!form.errors.screenshot" aria-describedby="contact-screenshot-hint" @change="pickScreenshot" />
							<p v-if="form.errors.screenshot" class="contact-error">{{ form.errors.screenshot }}</p>
							<p id="contact-screenshot-hint" class="contact-hint">
								{{ text('PNG, JPG or WebP, up to 5 MB.', 'PNG, JPG ou WebP, 5 Mo maximum.') }}
								<template v-if="form.topic === 'bug'">{{ text('Bug reports also include your browser and device type, to help us find the problem.', 'Les signalements de bug incluent aussi votre navigateur et votre type d’appareil, pour nous aider à trouver le problème.') }}</template>
							</p>
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

				<aside class="self-start rounded-2xl bg-pale p-8">
					<h3 class="font-display text-xl font-semibold text-navy">{{ text('Quick answers', 'Réponses rapides') }}</h3>
					<ul class="contact-notes mt-5">
						<li><Icon name="lifebuoy" /><span>{{ text('Forgot your password?', 'Mot de passe oublié ?') }} <Link :href="`${prefix}/forgot-password`" class="font-semibold text-brand hover:text-brand-dark focus-ring">{{ text('Get a reset link', 'Recevoir un lien') }}</Link>.</span></li>
						<li><Icon name="mail" /><span>{{ text('No confirmation email? Check your spam folder, then ask for a new one from the banner in your dashboard.', 'Pas d’e-mail de confirmation ? Regardez dans vos spams, puis demandez-en un nouveau depuis le bandeau de votre tableau de bord.') }}</span></li>
						<li><Icon name="exclamation-triangle" /><span>{{ text('To fix or remove a listing, you can also use “Report an issue” on the listing page.', 'Pour corriger ou retirer une offre, vous pouvez aussi utiliser « Signaler un problème » sur la page de l’offre.') }}</span></li>
						<li><Icon name="briefcase" /><span>{{ text('Employers can post roles for free after creating a company account.', 'Les employeurs peuvent publier des offres gratuitement après avoir créé un compte entreprise.') }}</span></li>
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
.contact-stage { width: min(100%, 320px); }
.contact-form { display: grid; gap: 20px; padding: 28px; border: 1px solid var(--color-line); border-radius: 16px; background: #fff; }
.contact-form label, .contact-form legend { display: block; margin-bottom: 6px; color: var(--color-navy); font-size: 14px; font-weight: 600; }
.contact-form input:not([type="radio"]):not([type="file"]), .contact-form select, .contact-form textarea { width: 100%; border: 1px solid var(--color-line); border-radius: 12px; background-color: #fff; padding: 11px 14px; font-size: 15px; color: var(--color-ink); }
.contact-form input:focus-visible, .contact-form select:focus-visible, .contact-form textarea:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 1px; border-color: var(--color-brand); }
.contact-form [aria-invalid="true"] { border-color: #dc2626; }
.contact-form button[disabled] { opacity: .7; cursor: wait; }
.topic-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 4px; }
@media (min-width: 640px) { .topic-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.contact-form .topic-option { display: flex; flex-direction: column; gap: 4px; margin: 0; padding: 14px; border: 1px solid var(--color-line); border-radius: 12px; background: #fff; cursor: pointer; transition: background-color 160ms, border-color 160ms; }
.topic-option:hover { border-color: #b9c8ef; }
.topic-option.is-picked { border-color: var(--color-brand); background: #eef4ff; }
.topic-option:has(input:focus-visible) { outline: 2px solid var(--color-brand); outline-offset: 2px; }
.topic-option svg { width: 22px; height: 22px; color: var(--color-brand); }
.topic-label { margin-top: 4px; color: var(--color-navy); font-size: 14px; font-weight: 600; line-height: 1.3; }
.topic-hint { display: none; color: #67707e; font-size: 12.5px; font-weight: 400; line-height: 1.45; }
@media (min-width: 640px) { .topic-hint { display: block; } }
.contact-file { display: block; width: 100%; color: #475569; font-size: 14px; }
.contact-file::file-selector-button { margin-right: 12px; padding: 8px 14px; border: 1px solid var(--color-line); border-radius: 10px; background: #f6f8fc; color: var(--color-navy); font-weight: 600; cursor: pointer; }
.contact-error { margin-top: 6px; color: #b91c1c; font-size: 13px; }
.contact-hint { margin-top: 6px; color: #67707e; font-size: 13px; }
.contact-trap { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
.contact-sent { display: flex; gap: 14px; padding: 22px 24px; border: 1px solid #bbf7d0; border-radius: 16px; background: #f0fdf4; }
.contact-sent svg { width: 24px; height: 24px; flex-shrink: 0; color: #16a34a; }
.contact-notes { display: grid; gap: 16px; color: #475569; font-size: 15px; line-height: 1.6; }
.contact-notes li { display: flex; gap: 12px; }
.contact-notes svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 3px; color: var(--color-brand); }
</style>
