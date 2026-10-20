<template>
	<div>
		<auth-heading :kicker="text('Here’s to what comes next', 'Place à la suite')" :description="text('Join students and professionals finding internships and jobs around the world, or hire your next team member.', 'Rejoignez les étudiants et professionnels qui trouvent des stages et des emplois partout dans le monde, ou recrutez votre prochain talent.')">
			<template v-if="fr">Votre parcours<br />commence ici.</template>
			<template v-else>Your beginning<br />starts here.</template>
		</auth-heading>
		<auth-feedback :errors="form.errors" />
		<form @submit.prevent="submit" enctype="multipart/form-data" :aria-busy="form.processing">
			<fieldset>
				<legend :class="classes.label">{{ text('I’m joining as', 'Je m’inscris pour') }}</legend>
				<div class="mt-2.5 grid grid-cols-2 gap-3">
					<button type="button" :class="role(form.account_type === 'candidate')" :aria-pressed="form.account_type === 'candidate'" @click="selectAccount('candidate')">
						<span :class="roleName"><icon name="academic-cap" />{{ text('Find opportunities', 'Trouver une opportunité') }}</span>
						<icon v-if="form.account_type === 'candidate'" name="check-circle" :class="roleCheck" />
						<p :class="roleNote">{{ text('For students, graduates, and professionals.', 'Pour les étudiants, jeunes diplômés et professionnels.') }}</p>
					</button>
					<button type="button" :class="role(form.account_type === 'company')" :aria-pressed="form.account_type === 'company'" @click="selectAccount('company')">
						<span :class="roleName"><icon name="office-building" />{{ text('Hire candidates', 'Recruter') }}</span>
						<icon v-if="form.account_type === 'company'" name="check-circle" :class="roleCheck" />
						<p :class="roleNote">{{ text('Post internships and jobs for your team.', 'Publiez des stages et des emplois pour votre équipe.') }}</p>
					</button>
				</div>
			</fieldset>

			<fieldset :class="section">
				<legend :class="sectionLegend">{{ text('Your account', 'Votre compte') }}</legend>
				<div class="clear-both" :class="classes.fields">
					<auth-field id="name" :label="isCompany ? text('Company name', 'Nom de l’entreprise') : text('Full name', 'Nom complet')" v-model="form.name" :error="form.errors.name" required autofocus :autocomplete="isCompany ? 'organization' : 'name'" maxlength="255" />
					<auth-field id="email" :label="text('Email address', 'Adresse e-mail')" type="email" v-model="form.email" :error="form.errors.email" :placeholder="text('you@example.com', 'vous@exemple.com')" required autocomplete="username" maxlength="255" />
					<div :class="twoColumns">
						<auth-field id="password" :label="text('Password', 'Mot de passe')" type="password" v-model="form.password" :error="form.errors.password" :hint="text('Use at least 8 characters.', 'Au moins 8 caractères.')" minlength="8" required autocomplete="new-password" />
						<auth-field id="password_confirmation" :label="text('Confirm password', 'Confirmer le mot de passe')" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required autocomplete="new-password" />
					</div>
				</div>
			</fieldset>

			<fieldset :class="section">
				<legend :class="sectionLegend">{{ isCompany ? text('Your company', 'Votre entreprise') : text('A little about you', 'Un peu plus sur vous') }}</legend>
				<p v-if="isCompany" :class="sectionNote">{{ text('Set up your profile so candidates can get to know your company before they apply.', 'Complétez votre profil pour que les candidats découvrent votre entreprise avant de postuler.') }}</p>
				<p v-else :class="sectionNote">{{ text('You can skip optional details and update your preferences later.', 'Les champs facultatifs peuvent attendre. Vous pourrez changer vos préférences plus tard.') }}</p>
				<div :class="classes.fields">
					<template v-if="!isCompany">
						<fieldset><legend :class="classes.label">{{ text('I’m interested in', 'Je cherche') }}</legend><div class="flex gap-5 mt-2"><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="internship"> {{ text('Internships', 'Des stages') }}</label><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="job"> {{ text('Jobs', 'Des emplois') }}</label></div></fieldset>
					</template>
					<div :class="twoColumns">
						<auth-field id="country_code" :label="text('Country code', 'Code pays')" v-model="form.country_code" :error="form.errors.country_code" placeholder="TN, FR, US..." :hint="text('Two-letter country code.', 'Code pays à deux lettres.')" optional maxlength="2" />
						<auth-field id="location" :label="text('City or location', 'Ville ou lieu')" v-model="form.location" :error="form.errors.location" :placeholder="text('Your location anywhere in the world', 'Votre lieu, partout dans le monde')" optional maxlength="255" />
					</div>
					<auth-field id="phone_number" :label="text('Phone number', 'Numéro de téléphone')" type="tel" v-model="form.phone_number" :error="form.errors.phone_number" optional autocomplete="tel" maxlength="255" />

					<div v-if="!isCompany" class="min-w-0">
						<label for="field_id" :class="classes.label">{{ text('Field of work or study', 'Domaine d’études ou de travail') }} <span :class="classes.optional">{{ text('(optional)', '(facultatif)') }}</span></label>
						<select id="field_id" name="field_id" v-model="form.field_id" :class="classes.input" :aria-invalid="form.errors.field_id ? 'true' : undefined" :aria-describedby="form.errors.field_id ? 'field_id-error' : undefined">
							<option :value="null">{{ text('Not selected', 'Aucun') }}</option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
						<p v-if="form.errors.field_id" id="field_id-error" :class="classes.error">{{ form.errors.field_id }}</p>
					</div>

					<template v-if="isCompany">
						<div class="min-w-0">
							<span :class="classes.label">{{ text('Company logo', 'Logo de l’entreprise') }}</span>
							<label for="company_logo" class="relative flex cursor-pointer items-center gap-3.5 rounded-[7px] border border-dashed border-[#b9c3d2] p-[18px] focus-within:outline-2 focus-within:outline-offset-[3px] focus-within:outline-brand hover:border-brand hover:bg-[#f8faff]">
								<icon name="photo" class="size-[25px] shrink-0 text-brand" />
								<span><strong class="block text-[13px] font-medium wrap-anywhere">{{ form.company_logo ? form.company_logo.name : text('Choose your company logo', 'Choisir le logo de votre entreprise') }}</strong><small class="mt-1 block text-[11px] font-normal text-subtle-2">{{ form.company_logo ? text('An image up to 2 MB. Click to replace.', 'Une image de 2 Mo maximum. Cliquez pour la remplacer.') : text('An image up to 2 MB. Click to upload.', 'Une image de 2 Mo maximum. Cliquez pour l’ajouter.') }}</small></span>
								<input id="company_logo" name="company_logo" type="file" accept="image/*" :aria-label="text('Company logo', 'Logo de l’entreprise')" :aria-invalid="form.errors.company_logo ? 'true' : undefined" :aria-describedby="form.errors.company_logo ? 'company_logo-error' : undefined" @change="handleLogo" required class="absolute inset-0 size-full cursor-pointer opacity-0" />
							</label>
							<p v-if="form.errors.company_logo" id="company_logo-error" :class="classes.error">{{ form.errors.company_logo }}</p>
						</div>
						<auth-field id="website" :label="text('Company website', 'Site web de l’entreprise')" type="url" v-model="form.website" :error="form.errors.website" :placeholder="text('https://yourcompany.com', 'https://votre-entreprise.com')" required autocomplete="url" maxlength="255" />
						<auth-field id="linkedin_profile_url" :label="text('LinkedIn page', 'Page LinkedIn')" type="url" v-model="form.linkedin_profile_url" :error="form.errors.linkedin_profile_url" placeholder="https://linkedin.com/company/yourcompany" optional maxlength="255" />
					</template>

					<div class="min-w-0">
						<label for="about" :class="classes.label">{{ isCompany ? text('About your company', 'À propos de votre entreprise') : text('Short bio', 'Courte présentation') }} <span v-if="!isCompany" :class="classes.optional">{{ text('(optional)', '(facultatif)') }}</span></label>
						<textarea id="about" name="about" v-model="form.about" :required="isCompany" minlength="30" rows="4" :class="classes.input" :aria-invalid="form.errors.about ? 'true' : undefined" :aria-describedby="form.errors.about ? 'about-error' : 'about-hint'" :placeholder="isCompany ? text('What does your team do, and who are you hiring?', 'Que fait votre équipe et qui recrutez-vous ?') : text('Share your background, experience, and interests.', 'Parlez de votre parcours, de votre expérience et de vos centres d’intérêt.')"></textarea>
						<p v-if="form.errors.about" id="about-error" :class="classes.error">{{ form.errors.about }}</p>
						<p v-else id="about-hint" :class="classes.hint">{{ isCompany ? text('Help candidates get a feel for your team. At least 30 characters.', 'Aidez les candidats à découvrir votre équipe. Au moins 30 caractères.') : text('If you add a bio, use at least 30 characters.', 'Si vous ajoutez une présentation, utilisez au moins 30 caractères.') }}</p>
					</div>
				</div>
			</fieldset>

			<label for="terms" class="mt-7 flex cursor-pointer items-start gap-2.5 text-[13px] leading-[1.6] text-subtle">
				<input class="mt-[3px] size-4 shrink-0 shadow-none" id="terms" name="terms" v-model="form.terms" type="checkbox" required :aria-invalid="form.errors.terms ? 'true' : undefined" :aria-describedby="form.errors.terms ? 'terms-error' : undefined" />
				<span v-if="fr">J’accepte les <a href="/fr/terms" target="_blank" rel="noopener" :class="classes.link">conditions d’utilisation</a> et la <a href="/fr/privacy" target="_blank" rel="noopener" :class="classes.link">politique de confidentialité</a>.</span>
				<span v-else>I agree to the <a :href="route('terms')" target="_blank" rel="noopener" :class="classes.link">terms of use</a> and the <a :href="route('privacy')" target="_blank" rel="noopener" :class="classes.link">privacy policy</a>.</span>
			</label>
			<p v-if="form.errors.terms" id="terms-error" class="ml-[26px]" :class="classes.error">{{ form.errors.terms }}</p>
			<breeze-button class="mt-5" :disabled="form.processing">{{ form.processing ? text('Creating your account…', 'Création de votre compte…') : isCompany ? text('Create company account', 'Créer un compte entreprise') : text('Create candidate account', 'Créer un compte candidat') }}<icon name="arrow-right" /></breeze-button>
		</form>
		<p :class="classes.switchRow">{{ text('Already have an account?', 'Vous avez déjà un compte ?') }} <Link :href="`${prefix}/login`" :class="classes.link">{{ text('Log in', 'Se connecter') }}</Link></p>
	</div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';

export default {
	layout: BreezeGuestLayout,
	components: { AuthField, AuthFeedback, AuthHeading, BreezeButton },
	props: {
		fields: Array,
		accountType: { type: String, default: 'candidate' },
	},
	data() {
		return {
			classes: { ...classes },
			roleName: 'inline-flex items-center gap-[9px] text-[13px] font-semibold max-[540px]:gap-1.5 max-[540px]:text-[12px] [&_svg]:size-5 [&_svg]:shrink-0 [&_svg]:text-brand max-[540px]:[&_svg]:size-[18px]',
			roleCheck: 'size-[17px] self-center text-brand max-[540px]:size-[15px]',
			roleNote: 'col-span-full text-[11px] leading-[1.65] text-subtle',
			section: 'mt-7 min-w-0 border-t border-navy/10 pt-[26px]',
			sectionLegend: 'float-left mb-5 w-full font-display text-[18px] font-medium tracking-[-.025em]',
			sectionNote: 'clear-both -mt-2.5 mb-5 text-[12px] leading-[1.7] text-subtle-2',
			twoColumns: 'grid grid-cols-2 gap-5 max-[1024px]:grid-cols-1 max-[900px]:grid-cols-2 max-[540px]:grid-cols-1 max-[540px]:gap-[22px]',
			form: useForm({
				account_type: this.accountType,
				name: '',
				email: '',
				password: '',
				password_confirmation: '',
				phone_number: '',
				linkedin_profile_url: '',
				field_id: null,
				website: '',
				about: '',
				company_logo: null,
				country_code: '',
				location: '',
				preferences: { kinds: ['internship', 'job'] },
				terms: false,
			}),
		};
	},
	computed: {
		fr() {
			return this.$page.props.locale === 'fr';
		},
		prefix() {
			return this.fr ? '/fr' : '';
		},
		isCompany() {
			return this.form.account_type === 'company';
		},
	},
	methods: {
		role(picked) {
			return ['focus-ring grid grid-cols-[1fr_auto] gap-x-3 gap-y-[7px] rounded-[7px] border p-4 text-left transition-colors duration-180 max-[540px]:gap-2 max-[540px]:px-3 max-[540px]:py-3.5', picked ? 'border-brand bg-[#f1f5ff]' : 'border-[#d7dbe2] hover:border-brand-soft hover:bg-[#f8faff]'];
		},
		text(en, french) {
			return this.fr ? french : en;
		},
		selectAccount(type) {
			if (type === this.form.account_type) return;
			this.form.account_type = type;
			this.form.company_logo = null;
			this.form.clearErrors();
		},
		handleLogo(event) {
			this.form.company_logo = event.target.files[0] || null;
		},
		submit() {
			if (this.isCompany) {
				this.form.field_id = null;
			} else {
				this.form.website = '';
				this.form.company_logo = null;
			}
			this.form.post(`${this.prefix}/register`, {
				forceFormData: true,
				onFinish: () => this.form.reset('password', 'password_confirmation'),
			});
		},
	},
};
</script>
