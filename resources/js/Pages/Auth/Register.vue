<template>
	<div class="auth-register">
		<header class="auth-heading">
			<p class="auth-kicker">{{ text('Here’s to what comes next', 'Place à la suite') }}</p>
			<h1 v-if="fr">Votre parcours<br />commence ici.</h1>
			<h1 v-else>Your beginning<br />starts here.</h1>
			<p>{{ text('Join students and professionals finding internships and jobs in Tunisia and worldwide, or hire your next team member.', 'Rejoignez les étudiants et professionnels qui trouvent des stages et des emplois en Tunisie et à l’international, ou recrutez votre prochain talent.') }}</p>
		</header>
		<auth-feedback :errors="form.errors" />
		<form @submit.prevent="submit" enctype="multipart/form-data" :aria-busy="form.processing">
			<fieldset>
				<legend class="auth-label">{{ text('I’m joining as', 'Je m’inscris pour') }}</legend>
				<div class="auth-role-options">
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'student'" @click="selectAccount('student')">
						<span class="auth-role-name"><icon name="academic-cap" />{{ text('Find opportunities', 'Trouver une opportunité') }}</span>
						<icon v-if="form.account_type === 'student'" name="check-circle" class="auth-role-check" />
						<p>{{ text('For students, graduates, and professionals.', 'Pour les étudiants, jeunes diplômés et professionnels.') }}</p>
					</button>
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'company'" @click="selectAccount('company')">
						<span class="auth-role-name"><icon name="office-building" />{{ text('Hire candidates', 'Recruter') }}</span>
						<icon v-if="form.account_type === 'company'" name="check-circle" class="auth-role-check" />
						<p>{{ text('Post internships and jobs for your team.', 'Publiez des stages et des emplois pour votre équipe.') }}</p>
					</button>
				</div>
			</fieldset>

			<fieldset class="auth-form-section">
				<legend>{{ text('Your account', 'Votre compte') }}</legend>
				<div class="auth-fields">
					<auth-field id="name" :label="isCompany ? text('Company name', 'Nom de l’entreprise') : text('Full name', 'Nom complet')" v-model="form.name" :error="form.errors.name" required autofocus :autocomplete="isCompany ? 'organization' : 'name'" maxlength="255" />
					<auth-field id="email" :label="text('Email address', 'Adresse e-mail')" type="email" v-model="form.email" :error="form.errors.email" :placeholder="text('you@example.com', 'vous@exemple.com')" required autocomplete="username" maxlength="255" />
					<div class="auth-two-columns">
						<auth-field id="password" :label="text('Password', 'Mot de passe')" type="password" v-model="form.password" :error="form.errors.password" :hint="text('Use at least 8 characters.', 'Au moins 8 caractères.')" minlength="8" required autocomplete="new-password" />
						<auth-field id="password_confirmation" :label="text('Confirm password', 'Confirmer le mot de passe')" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required autocomplete="new-password" />
					</div>
				</div>
			</fieldset>

			<fieldset class="auth-form-section">
				<legend>{{ isCompany ? text('Your company', 'Votre entreprise') : text('A little about you', 'Un peu plus sur vous') }}</legend>
				<p v-if="isCompany" class="auth-section-note">{{ text('Set up your profile so candidates can get to know your company before they apply.', 'Complétez votre profil pour que les candidats découvrent votre entreprise avant de postuler.') }}</p>
				<p v-else class="auth-section-note">{{ text('You can skip optional details and update your preferences later.', 'Les champs facultatifs peuvent attendre. Vous pourrez changer vos préférences plus tard.') }}</p>
				<div class="auth-fields">
					<template v-if="!isCompany">
						<div class="auth-field">
							<label for="career_stage">{{ text('Career stage', 'Situation') }}</label>
							<select id="career_stage" v-model="form.career_stage" class="auth-input"><option value="student">{{ text('Student', 'Étudiant') }}</option><option value="graduate">{{ text('Graduate', 'Jeune diplômé') }}</option><option value="professional">{{ text('Professional', 'Professionnel') }}</option></select>
						</div>
						<fieldset><legend class="auth-label">{{ text('I’m interested in', 'Je cherche') }}</legend><div class="flex gap-5 mt-2"><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="internship"> {{ text('Internships', 'Des stages') }}</label><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="job"> {{ text('Jobs', 'Des emplois') }}</label></div></fieldset>
					</template>
					<div class="auth-two-columns">
						<auth-field id="country_code" :label="text('Country code', 'Code pays')" v-model="form.country_code" :error="form.errors.country_code" placeholder="TN, FR, US..." :hint="text('Two-letter country code.', 'Code pays à deux lettres.')" optional maxlength="2" />
						<auth-field id="location" :label="text('City or location', 'Ville ou lieu')" v-model="form.location" :error="form.errors.location" :placeholder="text('Your location anywhere in the world', 'Votre lieu, partout dans le monde')" optional maxlength="255" />
					</div>
					<div class="auth-two-columns">
						<div class="auth-field">
							<label for="city_id">{{ text('Tunisian city', 'Ville en Tunisie') }} <span class="auth-optional">{{ text('(optional)', '(facultatif)') }}</span></label>
							<select id="city_id" name="city_id" v-model="form.city_id" class="auth-input" :aria-invalid="form.errors.city_id ? 'true' : undefined" :aria-describedby="form.errors.city_id ? 'city_id-error' : undefined">
								<option :value="null">{{ text('Not selected', 'Aucune') }}</option>
								<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
							</select>
							<p v-if="form.errors.city_id" id="city_id-error" class="auth-field-error">{{ form.errors.city_id }}</p>
						</div>
						<auth-field id="phone_number" :label="text('Phone number', 'Numéro de téléphone')" type="tel" v-model="form.phone_number" :error="form.errors.phone_number" optional autocomplete="tel" maxlength="255" />
					</div>

					<div v-if="!isCompany" class="auth-field">
						<label for="field_id">{{ text('Field of work or study', 'Domaine d’études ou de travail') }} <span class="auth-optional">{{ text('(optional)', '(facultatif)') }}</span></label>
						<select id="field_id" name="field_id" v-model="form.field_id" class="auth-input" :aria-invalid="form.errors.field_id ? 'true' : undefined" :aria-describedby="form.errors.field_id ? 'field_id-error' : undefined">
							<option :value="null">{{ text('Not selected', 'Aucun') }}</option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
						<p v-if="form.errors.field_id" id="field_id-error" class="auth-field-error">{{ form.errors.field_id }}</p>
					</div>

					<template v-if="isCompany">
						<div class="auth-field">
							<span class="auth-label">{{ text('Company logo', 'Logo de l’entreprise') }}</span>
							<label for="company_logo" class="auth-upload">
								<icon name="photo" />
								<span><strong>{{ form.company_logo ? form.company_logo.name : text('Choose your company logo', 'Choisir le logo de votre entreprise') }}</strong><small>{{ form.company_logo ? text('An image up to 2 MB. Click to replace.', 'Une image de 2 Mo maximum. Cliquez pour la remplacer.') : text('An image up to 2 MB. Click to upload.', 'Une image de 2 Mo maximum. Cliquez pour l’ajouter.') }}</small></span>
								<input id="company_logo" name="company_logo" type="file" accept="image/*" :aria-label="text('Company logo', 'Logo de l’entreprise')" :aria-invalid="form.errors.company_logo ? 'true' : undefined" :aria-describedby="form.errors.company_logo ? 'company_logo-error' : undefined" @change="handleLogo" required />
							</label>
							<p v-if="form.errors.company_logo" id="company_logo-error" class="auth-field-error">{{ form.errors.company_logo }}</p>
						</div>
						<auth-field id="website" :label="text('Company website', 'Site web de l’entreprise')" type="url" v-model="form.website" :error="form.errors.website" :placeholder="text('https://yourcompany.com', 'https://votre-entreprise.com')" required autocomplete="url" maxlength="255" />
						<auth-field id="linkedin_profile_url" :label="text('LinkedIn page', 'Page LinkedIn')" type="url" v-model="form.linkedin_profile_url" :error="form.errors.linkedin_profile_url" placeholder="https://linkedin.com/company/yourcompany" optional maxlength="255" />
					</template>

					<div class="auth-field">
						<label for="about">{{ isCompany ? text('About your company', 'À propos de votre entreprise') : text('Short bio', 'Courte présentation') }} <span v-if="!isCompany" class="auth-optional">{{ text('(optional)', '(facultatif)') }}</span></label>
						<textarea id="about" name="about" v-model="form.about" :required="isCompany" minlength="30" rows="4" class="auth-input" :aria-invalid="form.errors.about ? 'true' : undefined" :aria-describedby="form.errors.about ? 'about-error' : 'about-hint'" :placeholder="isCompany ? text('What does your team do, and who are you hiring?', 'Que fait votre équipe et qui recrutez-vous ?') : text('Share your background, experience, and interests.', 'Parlez de votre parcours, de votre expérience et de vos centres d’intérêt.')"></textarea>
						<p v-if="form.errors.about" id="about-error" class="auth-field-error">{{ form.errors.about }}</p>
						<p v-else id="about-hint" class="auth-hint">{{ isCompany ? text('Help candidates get a feel for your team. At least 30 characters.', 'Aidez les candidats à découvrir votre équipe. Au moins 30 caractères.') : text('If you add a bio, use at least 30 characters.', 'Si vous ajoutez une présentation, utilisez au moins 30 caractères.') }}</p>
					</div>
				</div>
			</fieldset>

			<label for="terms" class="auth-consent">
				<input id="terms" name="terms" v-model="form.terms" type="checkbox" required :aria-invalid="form.errors.terms ? 'true' : undefined" :aria-describedby="form.errors.terms ? 'terms-error' : undefined" />
				<span v-if="fr">J’accepte les <a href="/fr/terms" target="_blank" rel="noopener" class="auth-link focus-ring">conditions d’utilisation</a> et la <a href="/fr/privacy" target="_blank" rel="noopener" class="auth-link focus-ring">politique de confidentialité</a>.</span>
				<span v-else>I agree to the <a :href="route('terms')" target="_blank" rel="noopener" class="auth-link focus-ring">terms of use</a> and the <a :href="route('privacy')" target="_blank" rel="noopener" class="auth-link focus-ring">privacy policy</a>.</span>
			</label>
			<p v-if="form.errors.terms" id="terms-error" class="auth-field-error">{{ form.errors.terms }}</p>
			<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? text('Creating your account…', 'Création de votre compte…') : isCompany ? text('Create company account', 'Créer un compte entreprise') : text('Create candidate account', 'Créer un compte candidat') }}<icon name="arrow-right" /></breeze-button>
		</form>
		<p class="auth-switch">{{ text('Already have an account?', 'Vous avez déjà un compte ?') }} <Link :href="`${prefix}/login`" class="auth-link focus-ring">{{ text('Log in', 'Se connecter') }}</Link></p>
	</div>
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
		cities: Array,
		fields: Array,
		accountType: { type: String, default: 'student' },
		careerStage: { type: String, default: 'student' },
	},
	data() {
		return {
			form: useForm({
				account_type: this.accountType,
				name: '',
				email: '',
				password: '',
				password_confirmation: '',
				phone_number: '',
				linkedin_profile_url: '',
				city_id: null,
				field_id: null,
				website: '',
				about: '',
				company_logo: null,
				career_stage: this.careerStage,
				country_code: '',
				location: '',
				preferences: { kinds: this.careerStage === 'professional' ? ['job'] : ['internship', 'job'] },
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
