<template>
	<div class="auth-register">
		<header class="auth-heading">
			<p class="auth-kicker">Here’s to what comes next</p>
			<h1>Your beginning<br />starts here.</h1>
			<p>Create an account to find your next opportunity or introduce your company to emerging talent.</p>
		</header>
		<auth-feedback :errors="form.errors" />
		<form @submit.prevent="submit" enctype="multipart/form-data" :aria-busy="form.processing">
			<fieldset>
				<legend class="auth-label">I’m joining as</legend>
				<div class="auth-role-options">
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'student'" @click="selectAccount('student')">
						<span class="auth-role-name"><icon name="academic-cap" />Student</span>
						<icon v-if="form.account_type === 'student'" name="check-circle" class="auth-role-check" />
						<p>Find your fit. Take the first step.</p>
					</button>
					<button type="button" class="auth-role focus-ring" :aria-pressed="form.account_type === 'company'" @click="selectAccount('company')">
						<span class="auth-role-name"><icon name="office-building" />Company</span>
						<icon v-if="form.account_type === 'company'" name="check-circle" class="auth-role-check" />
						<p>Make room for emerging talent.</p>
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
				<p v-if="isCompany" class="auth-section-note">Set up your public profile so students can get to know your company before they apply.</p>
				<div class="auth-fields">
					<div class="auth-two-columns">
						<div class="auth-field">
							<label for="city_id">City</label>
							<select id="city_id" name="city_id" v-model="form.city_id" class="auth-input" :aria-invalid="form.errors.city_id ? 'true' : undefined" :aria-describedby="form.errors.city_id ? 'city_id-error' : undefined" required>
								<option :value="null" disabled>Select your city</option>
								<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
							</select>
							<p v-if="form.errors.city_id" id="city_id-error" class="auth-field-error">{{ form.errors.city_id }}</p>
						</div>
						<auth-field id="phone_number" label="Phone number" type="tel" v-model="form.phone_number" :error="form.errors.phone_number" optional autocomplete="tel" maxlength="255" />
					</div>

					<div v-if="!isCompany" class="auth-field">
						<label for="field_id">Field of study</label>
						<select id="field_id" name="field_id" v-model="form.field_id" class="auth-input" :aria-invalid="form.errors.field_id ? 'true' : undefined" :aria-describedby="form.errors.field_id ? 'field_id-error' : undefined" required>
							<option :value="null" disabled>Select your field</option>
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
						<textarea id="about" name="about" v-model="form.about" :required="isCompany" minlength="30" rows="4" class="auth-input" :aria-invalid="form.errors.about ? 'true' : undefined" :aria-describedby="form.errors.about ? 'about-error' : 'about-hint'" :placeholder="isCompany ? 'What do you do, and what could an intern learn with you?' : 'What are you studying, and what would you like to learn next?'"></textarea>
						<p v-if="form.errors.about" id="about-error" class="auth-field-error">{{ form.errors.about }}</p>
						<p v-else id="about-hint" class="auth-hint">{{ isCompany ? 'Help students get a feel for your team. At least 30 characters.' : 'If you add a bio, use at least 30 characters.' }}</p>
					</div>
				</div>
			</fieldset>

			<breeze-button class="auth-submit-spaced" :disabled="form.processing">{{ form.processing ? 'Creating your account…' : isCompany ? 'Create company account' : 'Create student account' }}<icon name="arrow-right" /></breeze-button>
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
