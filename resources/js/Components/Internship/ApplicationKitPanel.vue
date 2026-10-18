<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import axios from 'axios';

const props = defineProps({
    internship: { type: Object, required: true },
    kits: { type: Array, default: () => [] },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => (fr.value ? french : en);
const locale = ref(page.props.locale === 'fr' ? 'fr' : 'en');
const existing = computed(() => props.kits.find((kit) => kit.locale === locale.value));
const billing = computed(() => page.props.billing || {});
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const busy = ref(false);
const error = ref('');
const billingUrl = ref('');

const start = async () => {
    busy.value = true;
    error.value = '';
    billingUrl.value = '';
    try {
        const { data } = await axios.post(`/opportunities/${props.internship.id}/kit`, { locale: locale.value });
        router.visit(data.url);
    } catch (failure) {
        const status = failure.response?.status;
        billingUrl.value = failure.response?.data?.billing_url || '';
        error.value = status === 429
            ? text('You asked for many kits in a short time. Please wait a little and try again.', 'Vous avez demandé beaucoup de dossiers en peu de temps. Patientez un peu puis réessayez.')
            : failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Impossible de commencer pour le moment. Réessayez dans une minute.');
        busy.value = false;
    }
};
</script>

<template>
    <section class="bg-white border border-line rounded-2xl p-6" aria-labelledby="kit-title">
        <h2 id="kit-title" class="font-display text-lg font-semibold flex items-center gap-2"><Sparkles :size="18" class="text-blue-700" aria-hidden="true" />{{ text('Tailored CV and cover letter', 'CV et lettre sur mesure') }}</h2>
        <p class="text-sm text-slate-600 leading-6 mt-2">{{ text('We write a CV and a cover letter for this role from your profile and resume. You check and edit both before you use them.', 'Nous rédigeons un CV et une lettre pour cette offre à partir de votre profil et de votre CV. Vous les vérifiez et les modifiez avant de les utiliser.') }}</p>
        <fieldset class="mt-4">
            <legend class="text-sm font-semibold">{{ text('Language', 'Langue') }}</legend>
            <div class="flex gap-4 mt-2 text-sm">
                <label class="flex items-center gap-2"><input v-model="locale" type="radio" value="en"> English</label>
                <label class="flex items-center gap-2"><input v-model="locale" type="radio" value="fr"> Français</label>
            </div>
        </fieldset>
        <Link v-if="existing" :href="`/kits/${existing.id}`" class="cta cta-primary w-full mt-5">{{ existing.status === 'queued' ? text('Being written… open it', 'En cours de rédaction… ouvrir') : text('Open your CV and letter', 'Ouvrir votre CV et votre lettre') }}</Link>
        <button v-else type="button" class="cta cta-primary w-full mt-5" :disabled="busy" @click="start">{{ busy ? text('Starting…', 'Démarrage…') : text('Tailor my CV and cover letter', 'Adapter mon CV et ma lettre') }}</button>
        <p class="text-xs text-slate-500 mt-3">
            <template v-if="billing.enabled">{{ text(`Uses 1 credit, with a few rewrites included. You have ${credits}.`, `Utilise 1 crédit, quelques réécritures incluses. Il vous en reste ${credits}.`) }}</template>
            <template v-else>{{ text('Free while we launch.', 'Gratuit pendant le lancement.') }}</template>
            {{ text('Better results with your resume and experience in', 'Meilleur résultat avec votre CV et vos expériences dans') }} <Link href="/settings" class="text-blue-700 underline">{{ text('Settings', 'Paramètres') }}</Link>.
        </p>
        <p v-if="error" class="text-sm text-red-700 mt-3" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">{{ text('See packs', 'Voir les packs') }}</a></p>
    </section>
</template>
