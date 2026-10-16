<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, Copy, PenLine, RotateCcw } from '@lucide/vue';
import axios from 'axios';

const props = defineProps({ internship: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;

const letter = ref('');
const hasResume = ref(true);
const loading = ref(false);
const error = ref('');
const copied = ref(false);
const canApplyHere = computed(() => props.internship.application_mode !== 'external' && props.internship.is_open && !props.internship.application);

const write = async () => {
    loading.value = true;
    error.value = '';
    copied.value = false;
    try {
        const { data } = await axios.post(`/opportunities/${props.internship.id}/cover-letter`);
        letter.value = data.letter;
        hasResume.value = data.has_resume;
    } catch (failure) {
        error.value = failure.response?.status === 429
            ? text('You asked for many drafts in a short time. Please wait a bit and try again.', 'Vous avez demandé beaucoup de brouillons en peu de temps. Attendez un peu et réessayez.')
            : failure.response?.data?.message || text('We could not write a draft right now. Please try again.', 'Impossible de rédiger un brouillon pour le moment. Réessayez.');
    } finally {
        loading.value = false;
    }
};

const copy = async () => {
    try { await navigator.clipboard.writeText(letter.value); copied.value = true; }
    catch { copied.value = false; }
};

const useToApply = () => {
    try { sessionStorage.setItem(`nextward.cover-letter.${props.internship.id}`, letter.value); } catch { /* the draft stays on this page */ }
    router.visit(`/internships/${props.internship.id}/apply`);
};
</script>

<template>
    <section class="bg-white border border-line rounded-2xl p-6">
        <h2 class="font-display text-xl font-semibold">{{ text('Cover letter', 'Lettre de motivation') }}</h2>
        <p class="text-sm text-slate-600 mt-2 leading-6">{{ text('Get a first draft written from your profile and resume. Read it and make it yours before you send it.', 'Obtenez un premier brouillon rédigé à partir de votre profil et de votre CV. Relisez-le et adaptez-le avant de l’envoyer.') }}</p>
        <button v-if="!letter" type="button" class="cta cta-secondary w-full mt-4" :disabled="loading" @click="write">
            <PenLine :size="17" />{{ loading ? text('Writing your draft…', 'Rédaction en cours…') : text('Write my cover letter', 'Rédiger ma lettre') }}
        </button>
        <template v-else>
            <label class="sr-only" for="cover-letter-draft">{{ text('Your cover letter draft', 'Votre brouillon de lettre') }}</label>
            <textarea id="cover-letter-draft" v-model="letter" rows="14" dir="auto" class="w-full border-line rounded-lg mt-4 text-sm leading-6" />
            <p v-if="!hasResume" class="text-xs text-slate-500 mt-2 leading-5">{{ text('Tip: add your resume in', 'Astuce : ajoutez votre CV dans') }} <Link href="/settings" class="text-blue-700 font-semibold">{{ text('Settings', 'Paramètres') }}</Link> {{ text('for a more precise draft.', 'pour un brouillon plus précis.') }}</p>
            <button v-if="canApplyHere" type="button" class="cta cta-primary w-full mt-4" @click="useToApply">{{ text('Use it to apply', 'L’utiliser pour postuler') }}</button>
            <div class="flex gap-3 mt-3">
                <button type="button" class="cta cta-secondary flex-1" @click="copy"><component :is="copied ? Check : Copy" :size="16" />{{ copied ? text('Copied', 'Copiée') : text('Copy', 'Copier') }}</button>
                <button type="button" class="cta cta-secondary flex-1" :disabled="loading" @click="write"><RotateCcw :size="16" />{{ loading ? text('Writing…', 'Rédaction…') : text('Write again', 'Réécrire') }}</button>
            </div>
        </template>
        <p v-if="error" class="text-sm text-red-600 mt-3" role="alert">{{ error }}</p>
    </section>
</template>
