<template>
    <breeze-validation-errors class="mb-4" />

    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
        {{ status }}
    </div>

    <p class="mb-4 text-xs text-gray-500 leading-relaxed">
        <strong>Test accounts:</strong><br>
        Platform admin: <strong>admin@example.com</strong> / <strong>password</strong><br>
        Company: <strong>company@example.com</strong> / <strong>password</strong><br>
        Student: <strong>student@example.com</strong> / <strong>password</strong>
    </p>

    <form spellcheck="false" autocomplete="off" @submit.prevent="submit">
        <div>
            <breeze-label for="email" value="Email" />
            <breeze-input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autofocus />
        </div>

        <div class="mt-4">
            <breeze-label for="password" value="Password" />
            <breeze-input id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
        </div>

        <div class="block mt-4">
            <label class="flex items-center">
                <breeze-checkbox name="remember" v-model:checked="form.remember" />
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900">
                Forgot your password?
            </Link>

            <breeze-button class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Log in
            </breeze-button>
        </div>
    </form>
</template>

<script>
    import { useForm } from '@inertiajs/vue3';
    import BreezeButton from '@/Components/Breeze/Button.vue'
    import BreezeGuestLayout from "@/Layouts/Guest.vue"
    import BreezeInput from '@/Components/Breeze/Input.vue'
    import BreezeCheckbox from '@/Components/Breeze/Checkbox.vue'
    import BreezeLabel from '@/Components/Breeze/Label.vue'
    import BreezeValidationErrors from '@/Components/ValidationErrors.vue'

    export default {
        layout: BreezeGuestLayout,

        components: {
            BreezeButton,
            BreezeInput,
            BreezeCheckbox,
            BreezeLabel,
            BreezeValidationErrors
        },

        props: {
            auth: Object,
            canResetPassword: Boolean,
            errors: Object,
            status: String,
        },

        data() {
            return {
                form: useForm({
                    email: 'admin@example.com',
                    password: 'password',
                    remember: false
                })
            }
        },

        methods: {
            submit() {
                this.form
                    .transform(data => ({
                        ... data,
                        remember: this.form.remember ? 'on' : ''
                    }))
                    .post(this.route('login'), {
                        onFinish: () => this.form.reset('password'),
                    })
            }
        }
    }
</script>
