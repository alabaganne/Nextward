<template>
    <div>
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-extrabold text-gray-900">Create your Internly account</h1>
            <p class="mt-2 text-sm text-gray-600">
                Tell us who you are so we can set up the right workspace.
            </p>
        </div>

        <breeze-validation-errors class="mb-4" />

        <form spellcheck="false" @submit.prevent="submit" enctype="multipart/form-data" class="space-y-5">
            <div>
                <breeze-label value="I am joining as" />
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button type="button" @click="form.account_type = 'student'" :class="accountTypeClass('student')">
                        <span class="text-lg">🎓</span>
                        <span class="font-semibold">Student</span>
                        <span class="text-xs text-gray-500">Find and apply to internships.</span>
                    </button>
                    <button type="button" @click="form.account_type = 'company'" :class="accountTypeClass('company')">
                        <span class="text-lg">🏢</span>
                        <span class="font-semibold">Company</span>
                        <span class="text-xs text-gray-500">Complete onboarding before posting.</span>
                    </button>
                </div>
            </div>

            <div>
                <breeze-label for="name" :value="form.account_type === 'company' ? 'Company name' : 'Full name'" />
                <breeze-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
            </div>

            <div>
                <breeze-label for="email" value="Email" />
                <breeze-input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <breeze-label for="password" value="Password" />
                    <breeze-input id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                </div>

                <div>
                    <breeze-label for="password_confirmation" value="Confirm password" />
                    <breeze-input id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <breeze-label for="city_id" value="City" />
                    <select id="city_id" v-model="form.city_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50" required>
                        <option :value="null" disabled>Select city</option>
                        <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                    </select>
                </div>

                <div>
                    <breeze-label for="phone_number" value="Phone number" />
                    <breeze-input id="phone_number" type="text" class="mt-1 block w-full" v-model="form.phone_number" autocomplete="tel" />
                </div>
            </div>

            <div v-if="form.account_type === 'student'">
                <breeze-label for="field_id" value="Field of study" />
                <select id="field_id" v-model="form.field_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50" required>
                    <option :value="null" disabled>Select field</option>
                    <option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
                </select>
            </div>

            <div v-if="form.account_type === 'company'" class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 space-y-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-indigo-900">Company onboarding</h2>
                    <p class="mt-1 text-sm text-indigo-800">
                        Complete your public company profile first. This gives students trust and unlocks internship posting.
                    </p>
                </div>

                <div>
                    <breeze-label for="company_logo" value="Company logo" />
                    <input id="company_logo" type="file" accept="image/*" @change="handleLogo" class="mt-1 block w-full text-sm text-gray-700" required />
                </div>

                <div>
                    <breeze-label for="website" value="Website" />
                    <breeze-input id="website" type="url" class="mt-1 block w-full" v-model="form.website" required placeholder="https://company.com" />
                </div>

                <div>
                    <breeze-label for="linkedin_profile_url" value="LinkedIn page" />
                    <breeze-input id="linkedin_profile_url" type="url" class="mt-1 block w-full" v-model="form.linkedin_profile_url" placeholder="https://linkedin.com/company/example" />
                </div>
            </div>

            <div>
                <breeze-label for="about" :value="form.account_type === 'company' ? 'About your company' : 'Short bio (optional)'" />
                <textarea id="about" v-model="form.about" :required="form.account_type === 'company'" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50" :placeholder="form.account_type === 'company' ? 'What do you build, what is your culture like, and why should students apply?' : 'Tell companies a little about yourself.'"></textarea>
            </div>

            <div class="flex items-center justify-between pt-2">
                <Link :href="route('login')" class="underline text-sm text-gray-600 hover:text-gray-900">
                    Already registered?
                </Link>

                <breeze-button class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ form.account_type === 'company' ? 'Complete onboarding' : 'Create account' }}
                </breeze-button>
            </div>
        </form>
    </div>
</template>

<script>
    import { useForm } from '@inertiajs/vue3';
    import BreezeButton from '@/Components/Breeze/Button.vue'
    import BreezeGuestLayout from '@/Layouts/Guest.vue'
    import BreezeInput from '@/Components/Breeze/Input.vue'
    import BreezeLabel from '@/Components/Breeze/Label.vue'
    import BreezeValidationErrors from '@/Components/ValidationErrors.vue'

    export default {
        layout: BreezeGuestLayout,

        components: {
            BreezeButton,
            BreezeInput,
            BreezeLabel,
            BreezeValidationErrors,
        },

        props: {
            cities: Array,
            fields: Array,
            accountType: {
                type: String,
                default: 'student',
            },
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
                })
            }
        },

        methods: {
            accountTypeClass(type) {
                const active = this.form.account_type === type
                return [
                    'flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition',
                    active ? 'border-indigo-500 bg-white shadow-xs ring-2 ring-indigo-100' : 'border-gray-200 bg-gray-50 hover:bg-white'
                ]
            },
            handleLogo(event) {
                this.form.company_logo = event.target.files[0]
            },
            submit() {
                if (this.form.account_type === 'student') {
                    this.form.website = ''
                    this.form.company_logo = null
                } else {
                    this.form.field_id = null
                }

                this.form.post(this.route('register'), {
                    forceFormData: true,
                    onFinish: () => this.form.reset('password', 'password_confirmation'),
                })
            }
        }
    }
</script>
