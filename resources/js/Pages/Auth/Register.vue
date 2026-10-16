<template>
	<div class="auth-register">
		<header class="auth-heading">
			<p class="auth-kicker">Here’s to what comes next</p>
			<h1>Your beginning<br />starts here.</h1>
			<p>Join students and professionals finding internships and jobs in Tunisia and worldwide, or hire your next team member.</p>
		</header>
		<auth-feedback :errors="form.errors" />
		<form @submit.prevent="submit" enctype="multipart/form-data" :aria-busy="form.processing">
			<fieldset>
				<legend class="auth-label">I’m joining as</legend>
				<div class="auth-role-options">
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'student'" @click="selectAccount('student')">
						<span class="auth-role-name"><icon name="academic-cap" />Find opportunities</span>
						<icon v-if="form.account_type === 'student'" name="check-circle" class="auth-role-check" />
						<p>For students, graduates, and professionals.</p>
					</button>
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'company'" @click="selectAccount('company')">
						<span class="auth-role-name"><icon name="office-building" />Hire candidates</span>
						<icon v-if="form.account_type === 'company'" name="check-circle" class="auth-role-check" />
						<p>Post internships and jobs for your team.</p>
					</button>
				</div>
			</fieldset>

			<fieldset class="auth-form-section">
				<legend>Your account</legend>
				<div class="auth-fields">
					<auth-field id="name" :label="isCompany ? 'Company name' : 'Full name'" v-model="form.name" :error="form.errors.name" required autofocus :autocomplete="isCompany ? 'organization' : 'name'" maxlength="255" />
					<auth-field id="email" label="Email address" type="email" v-model="form.email" :error="form.errors.email" placeholder="you@example.com" required autocomplete="username" maxlength="255" />
					<div class="auth-two-columns">
						<auth-field id="password" label="Password" type="password" v-model="form.password" :error="form.errors.password" hint="Use at least 8 characters." minlength="8" required autocomplete="new-password" />
						<auth-field id="password_confirmation" label="Confirm password" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required autocomplete="new-password" />
					</div>
				</div>
			</fieldset>

			<fieldset class="auth-form-section">
				<legend>{{ isCompany ? 'Your company' : 'A little about you' }}</legend>
				<p v-if="isCompany" class="auth-section-note">Set up your profile so candidates can get to know your company before they apply.</p>
				<p v-else class="auth-section-note">You can skip optional details and update your preferences later.</p>
				<div class="auth-fields">
					<template v-if="!isCompany">
						<div class="auth-field">
							<label for="career_stage">Career stage</label>
							<select id="career_stage" v-model="form.career_stage" class="auth-input"><option value="student">Student</option><option value="graduate">Graduate</option><option value="professional">Professional</option></select>
						</div>
						<fieldset><legend class="auth-label">I'm interested in</legend><div class="flex gap-5 mt-2"><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="internship"> Internships</label><label class="flex gap-2 items-center"><input v-model="form.preferences.kinds" type="checkbox" value="job"> Jobs</label></div></fieldset>
					</template>
					<div class="auth-two-columns">
						<auth-field id="country_code" label="Country code" v-model="form.country_code" :error="form.errors.country_code" placeholder="TN, FR, US..." hint="Two-letter country code." optional maxlength="2" />
						<auth-field id="location" label="City or location" v-model="form.location" :error="form.errors.location" placeholder="Your location anywhere in the world" optional maxlength="255" />
					</div>
					<div class="auth-two-columns">
						<div class="auth-field">
							<label for="city_id">Tunisian city <span class="auth-optional">(optional)</span></label>
							<select id="city_id" name="city_id" v-model="form.city_id" class="auth-input" :aria-invalid="form.errors.city_id ? 'true' : undefined" :aria-describedby="form.errors.city_id ? 'city_id-error' : undefined">
								<option :value="null">Not selected</option>
								<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
							</select>
							<p v-if="form.errors.city_id" id="city_id-error" class="auth-field-error">{{ form.errors.city_id }}</p>
						</div>
						<auth-field id="phone_number" label="Phone number" type="tel" v-model="form.phone_number" :error="form.errors.phone_number" optional autocomplete="tel" maxlength="255" />
					</div>

					<div v-if="!isCompany" class="auth-field">
						<label for="field_id">Field of work or study <span class="auth-optional">(optional)</span></label>
						<select id="field_id" name="field_id" v-model="form.field_id" class="auth-input" :aria-invalid="form.errors.field_id ? 'true' : undefined" :aria-describedby="form.errors.field_id ? 'field_id-error' : undefined">
							<option :value="null">Not selected</option>
							<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
						</select>
						<p v-if="form.errors.field_id" id="field_id-error" class="auth-field-error">{{ form.errors.field_id }}</p>
					</div>

					<template v-if="isCompany">
						<div class="auth-field">
							<span class="auth-label">Company logo</span>
							<label for="company_logo" class="auth-upload">
								<icon name="photo" />
								<span><strong>{{ form.company_logo ? form.company_logo.name : 'Choose your company logo' }}</strong><small>An image up to 2 MB. Click to {{ form.company_logo ? 'replace' : 'upload' }}.</small></span>
								<input id="company_logo" name="company_logo" type="file" accept="image/*" aria-label="Company logo" :aria-invalid="form.errors.company_logo ? 'true' : undefined" :aria-describedby="form.errors.company_logo ? 'company_logo-error' : undefined" @change="handleLogo" required />
							</label>
							<p v-if="form.errors.company_logo" id="company_logo-error" class="auth-field-error">{{ form.errors.company_logo }}</p>
						</div>
						<auth-field id="website" label="Company website" type="url" v-model="form.website" :error="form.errors.website" placeholder="https://yourcompany.com" required autocomplete="url" maxlength="255" />
						<auth-field id="linkedin_profile_url" label="LinkedIn page" type="url" v-model="form.linkedin_profile_url" :error="form.errors.linkedin_profile_url" placeholder="https://linkedin.com/company/yourcompany" optional maxlength="255" />
					</template>

					<div class="auth-field">
						<label for="about">{{ isCompany ? 'About your company' : 'Short bio' }} <span v-if="!isCompany" class="auth-optional">(optional)</span></label>
						<textarea id="about" name="about" v-model="form.about" :required="isCompany" minlength="30" rows="4" class="auth-input" :aria-invalid="form.errors.about ? 'true' : undefined" :aria-describedby="form.errors.about ? 'about-error' : 'about-hint'" :placeholder="isCompany ? 'What does your team do, and who are you hiring?' : 'Share your background, experience, and interests.'"></textarea>
						<p v-if="form.errors.about" id="about-error" class="auth-field-error">{{ form.errors.about }}</p>
						<p v-else id="about-hint" class="auth-hint">{{ isCompany ? 'Help candidates get a feel for your team. At least 30 characters.' : 'If you add a bio, use at least 30 characters.' }}</p>
					</div>
				</div>
			</fieldset>

			<label for="terms" class="auth-consent">
				<input id="terms" name="terms" v-model="form.terms" type="checkbox" required :aria-invalid="form.errors.terms ? 'true' : undefined" :aria-describedby="form.errors.terms ? 'terms-error' : undefined" />
				<span>I agree to the <a :href="route('terms')" target="_blank" rel="noopener" class="auth-link focus-ring">terms of use</a> and the <a :href="route('privacy')" target="_blank" rel="noopener" class="auth-link focus-ring">privacy policy</a>.</span>
			</label>
			<p v-if="form.errors.terms" id="terms-error" class="auth-field-error">{{ form.errors.terms }}</p>
			<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Creating your account…' : isCompany ? 'Create company account' : 'Create candidate account' }}<icon name="arrow-right" /></breeze-button>
		</form>
		<p class="auth-switch">Already have an account? <Link :href="route('login')" class="auth-link focus-ring">Log in</Link></p>
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
		isCompany() {
			return this.form.account_type === 'company';
		},
	},
	methods: {
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
			this.form.post(this.route('register'), {
				forceFormData: true,
				onFinish: () => this.form.reset('password', 'password_confirmation'),
			});
		},
	},
};
</script>
