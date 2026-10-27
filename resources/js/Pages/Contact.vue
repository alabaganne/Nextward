<script setup>
import { computed, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
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
const label = 'mb-2 block text-[14px] font-medium text-navy';
const field = 'w-full rounded-card border border-slate-300 bg-white px-4 py-3 text-[15px] text-ink placeholder:text-muted-2 focus-visible:border-brand focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-brand aria-invalid:border-red-600';
const error = 'mt-1.5 text-[13px] text-red-700';
const answer = 'grid size-10 shrink-0 place-items-center rounded-card bg-blue-50 text-brand';
const link = 'focus-ring font-medium text-brand hover:text-navy';
const hint = 'mt-1.5 text-[13px] text-subtle-2';

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
		<div class="landing bg-pale">
			<LandingHero title-id="contact-title" overlap :label="text('Help and contact', 'Aide et contact')" :lead="text('Ask for help with anything, report a bug or suggest a feature. We read every message and answer by email.', 'Demandez de l’aide sur n’importe quel sujet, signalez un bug ou proposez une idée. Nous lisons chaque message et répondons par e-mail.')">
				{{ text('How can we', 'Comment pouvons-nous') }} <span class="accent">{{ text('help?', 'vous aider ?') }}</span>
			</LandingHero>

			<div class="public-container hero-overlap grid items-start gap-8 pb-20 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
				<section class="rounded-card border border-line bg-white p-8 max-[768px]:p-5" aria-labelledby="message-title">
					<h2 id="message-title" class="text-[length:clamp(24px,2.4vw,30px)] leading-[1.25] text-navy">{{ text('Send us a message', 'Envoyez-nous un message') }}</h2>

					<div v-if="sent" class="mt-7 flex gap-3.5 rounded-card bg-green-50 px-6 py-[22px]" role="status">
						<Icon name="check-circle" class="size-6 shrink-0 text-green-600" />
						<div>
							<p class="font-medium text-navy">{{ text('Thanks, your message is on its way.', 'Merci, votre message est bien parti.') }}</p>
							<p class="mt-1 text-subtle">{{ text('We will reply to', 'Nous répondrons à') }} {{ form.email }}.</p>
							<button type="button" class="mt-3" :class="link" @click="sent = false">{{ text('Send another message', 'Envoyer un autre message') }}</button>
						</div>
					</div>

					<form v-else class="mt-7 grid gap-6" novalidate @submit.prevent="submit">
						<fieldset>
							<legend :class="label">{{ text('What is it about?', 'Quel est le sujet ?') }}</legend>
							<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
								<label v-for="item in topics" :key="item.value" :class="['m-0 flex cursor-pointer flex-col gap-1 rounded-card border p-4 transition-colors duration-160 has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-brand', form.topic === item.value ? 'border-brand bg-blue-50' : 'border-line bg-white hover:border-brand/40']">
									<input v-model="form.topic" type="radio" name="topic" :value="item.value" class="sr-only" />
									<Icon :name="item.icon" class="size-[22px] text-brand" />
									<span class="mt-1.5 text-[14px] leading-[1.35] font-medium text-navy">{{ item.label }}</span>
									<span class="hidden text-[12.5px] leading-[1.5] text-subtle-2 sm:block">{{ item.hint }}</span>
								</label>
							</div>
							<p v-if="form.errors.topic" :class="error">{{ form.errors.topic }}</p>
						</fieldset>
						<div class="grid gap-6 sm:grid-cols-2">
							<div>
								<label for="contact-name" :class="label">{{ text('Your name', 'Votre nom') }}</label>
								<input id="contact-name" :class="field" v-model="form.name" type="text" autocomplete="name" maxlength="120" required :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'contact-name-error' : null" />
								<p v-if="form.errors.name" id="contact-name-error" :class="error">{{ form.errors.name }}</p>
							</div>
							<div>
								<label for="contact-email" :class="label">{{ text('Email address', 'Adresse e-mail') }}</label>
								<input id="contact-email" :class="field" v-model="form.email" type="email" autocomplete="email" maxlength="200" required :aria-invalid="!!form.errors.email" :aria-describedby="form.errors.email ? 'contact-email-error' : null" />
								<p v-if="form.errors.email" id="contact-email-error" :class="error">{{ form.errors.email }}</p>
							</div>
						</div>
						<div>
							<label for="contact-message" :class="label">{{ text('Message', 'Message') }}</label>
							<textarea id="contact-message" :class="field" v-model="form.message" rows="7" minlength="10" maxlength="5000" required :placeholder="topic.placeholder" :aria-invalid="!!form.errors.message" aria-describedby="contact-message-hint" />
							<p v-if="form.errors.message" :class="error">{{ form.errors.message }}</p>
							<p id="contact-message-hint" :class="hint">{{ text('Please don’t send passwords or CVs here.', 'N’envoyez ni mot de passe ni CV ici.') }}</p>
						</div>
						<div v-if="asksForPage">
							<label for="contact-url" :class="label">{{ form.topic === 'listing' ? text('Link to the listing', 'Lien de l’offre') : text('Page where it happened (optional)', 'Page concernée (facultatif)') }}</label>
							<input id="contact-url" :class="field" v-model="form.page_url" type="url" inputmode="url" maxlength="300" placeholder="https://" :aria-invalid="!!form.errors.page_url" :aria-describedby="form.errors.page_url ? 'contact-url-error' : (pageFilled ? 'contact-url-hint' : null)" />
							<p v-if="form.errors.page_url" id="contact-url-error" :class="error">{{ form.errors.page_url }}</p>
							<p v-else-if="pageFilled" id="contact-url-hint" :class="hint">{{ text('We filled in the page you came from. Change or clear it if it’s not the right one.', 'Nous avons mis la page d’où vous venez. Modifiez-la ou effacez-la si ce n’est pas la bonne.') }}</p>
						</div>
						<div v-if="asksForScreenshot">
							<label for="contact-screenshot" :class="label">{{ text('Screenshot (optional)', 'Capture d’écran (facultatif)') }}</label>
							<input id="contact-screenshot" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-[14px] text-subtle file:mr-3 file:cursor-pointer file:rounded-card file:border-0 file:bg-pale file:px-4 file:py-2.5 file:font-medium file:text-navy hover:file:bg-blue-100" :aria-invalid="!!form.errors.screenshot" aria-describedby="contact-screenshot-hint" @change="pickScreenshot" />
							<p v-if="form.errors.screenshot" :class="error">{{ form.errors.screenshot }}</p>
							<p id="contact-screenshot-hint" :class="hint">
								{{ text('PNG, JPG or WebP, up to 5 MB.', 'PNG, JPG ou WebP, 5 Mo maximum.') }}
								<template v-if="form.topic === 'bug'">{{ text('Bug reports also include your browser and device type, to help us find the problem.', 'Les signalements de bug incluent aussi votre navigateur et votre type d’appareil, pour nous aider à trouver le problème.') }}</template>
							</p>
						</div>
						<div class="absolute -left-[10000px] h-px w-px overflow-hidden" aria-hidden="true">
							<label for="contact-website">Website</label>
							<input id="contact-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
						</div>
						<button type="submit" class="cta cta-primary focus-ring disabled:cursor-wait disabled:opacity-70 sm:justify-self-start" :disabled="form.processing">
							{{ form.processing ? text('Sending…', 'Envoi…') : text('Send message', 'Envoyer le message') }}<Icon name="arrow-right" class="action-icon" />
						</button>
					</form>
				</section>

				<aside class="rounded-card border border-line bg-white p-8 max-[768px]:p-5" aria-labelledby="answers-title">
					<img src="/nextward-assets/mascots/04-mentor-pointing.svg" alt="" width="768" height="768" loading="lazy" class="size-24" />
					<h2 id="answers-title" class="mt-4 text-[22px] leading-[1.35] text-navy">{{ text('Quick answers', 'Réponses rapides') }}</h2>
					<ul class="mt-6 grid gap-5 text-[15px] leading-[1.65] text-subtle">
						<li class="flex gap-4"><span :class="answer"><Icon name="lifebuoy" class="size-5" /></span><span>{{ text('Forgot your password?', 'Mot de passe oublié ?') }} <Link :href="`${prefix}/forgot-password`" :class="link">{{ text('Get a reset link', 'Recevoir un lien') }}</Link>.</span></li>
						<li class="flex gap-4"><span :class="answer"><Icon name="mail" class="size-5" /></span><span>{{ text('No confirmation email? Check your spam folder, then ask for a new one from the banner in your dashboard.', 'Pas d’e-mail de confirmation ? Regardez dans vos spams, puis demandez-en un nouveau depuis le bandeau de votre tableau de bord.') }}</span></li>
						<li class="flex gap-4"><span :class="answer"><Icon name="exclamation-triangle" class="size-5" /></span><span>{{ text('To fix or remove a listing, you can also use “Report an issue” on the listing page.', 'Pour corriger ou retirer une offre, vous pouvez aussi utiliser « Signaler un problème » sur la page de l’offre.') }}</span></li>
						<li class="flex gap-4"><span :class="answer"><Icon name="briefcase" class="size-5" /></span><span>{{ text('Employers can post roles for free after creating a company account.', 'Les employeurs peuvent publier des offres gratuitement après avoir créé un compte entreprise.') }}</span></li>
					</ul>
					<div class="mt-8 rounded-card bg-pale p-5 text-[14.5px] leading-[1.6] text-subtle">
						<p>{{ text('Prefer email?', 'Vous préférez l’e-mail ?') }} <a :href="`mailto:${contactEmail}`" class="break-all" :class="link">{{ contactEmail }}</a></p>
						<p class="mt-2"><Link :href="`${prefix}/register?account=company`" :class="link">{{ text('Post a role', 'Publier une offre') }}</Link> · <Link :href="`${prefix}/opportunities`" :class="link">{{ text('Browse roles', 'Voir les offres') }}</Link></p>
					</div>
				</aside>
			</div>
		</div>
	</PublicLayout>
</template>
