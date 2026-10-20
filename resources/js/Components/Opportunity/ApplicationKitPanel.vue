<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Copy, Download, FileText, RefreshCw, Sparkles } from '@lucide/vue';
import axios from 'axios';
import { downloadLetterDocx, letterFileName } from '@/utils/letterDocx';

const props = defineProps({
    opportunity: { type: Object, required: true },
    kits: { type: Array, default: () => [] },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => (fr.value ? french : en);
const locale = ref(page.props.locale === 'fr' ? 'fr' : 'en');
const kits = ref(Object.fromEntries(props.kits.map((kit) => [kit.locale, kit])));
const kit = computed(() => kits.value[locale.value] || null);
const billing = computed(() => page.props.billing || {});
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const busy = ref(false);
const error = ref('');
const billingUrl = ref('');
const letter = ref('');
const savedLetter = ref('');
const saveState = ref('');
const copied = ref(false);
const exporting = ref(false);

const failed = (failure) => {
    const status = failure.response?.status;
    billingUrl.value = failure.response?.data?.billing_url || '';
    error.value = status === 429
        ? text('You asked for many letters in a short time. Please wait a little and try again.', 'Vous avez demandé beaucoup de lettres en peu de temps. Patientez un peu puis réessayez.')
        : failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Impossible de commencer pour le moment. Réessayez dans une minute.');
};

const take = (data) => {
    kits.value = { ...kits.value, [data.locale]: data };
    if (data.locale === locale.value && data.status !== 'queued') {
        letter.value = savedLetter.value = data.cover_letter || '';
    }
};

let timer = null;
const load = async () => {
    clearTimeout(timer);
    if (!kit.value) return;
    try {
        const { data } = await axios.get(`/kits/${kit.value.id}`, { headers: { Accept: 'application/json' } });
        take(data.kit);
    } catch { /* try again on the next tick */ }
    if (kit.value?.status === 'queued') timer = setTimeout(load, 3000);
};
watch(locale, () => { error.value = ''; letter.value = savedLetter.value = ''; load(); }, { immediate: true });
onBeforeUnmount(() => { clearTimeout(timer); clearTimeout(saveTimer); });

const start = async () => {
    busy.value = true;
    error.value = '';
    billingUrl.value = '';
    try {
        const { data } = await axios.post(`/opportunities/${props.opportunity.id}/kit`, { locale: locale.value });
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    } finally {
        busy.value = false;
    }
};

const rewrite = async () => {
    if (!window.confirm(text('Write a new letter for this role? Your edits will be replaced.', 'Écrire une nouvelle lettre pour cette offre ? Vos modifications seront remplacées.'))) return;
    error.value = '';
    try {
        const { data } = await axios.post(`/kits/${kit.value.id}/regenerate`);
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    }
};

// Edits save on their own a moment after typing stops.
let saveTimer = null;
const save = async () => {
    if (!kit.value || letter.value === savedLetter.value) return;
    const value = letter.value;
    saveState.value = 'saving';
    try {
        await axios.put(`/kits/${kit.value.id}`, { cover_letter: value }, { headers: { Accept: 'application/json' } });
        savedLetter.value = value;
        saveState.value = letter.value === value ? 'saved' : 'saving';
    } catch {
        saveState.value = 'error';
    }
};
const edited = () => {
    saveState.value = 'saving';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(save, 1200);
};

const copy = async () => {
    try {
        await navigator.clipboard.writeText(letter.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        error.value = text('Could not copy. Select the text and copy it instead.', 'Copie impossible. Sélectionnez le texte et copiez-le.');
    }
};

const exportDocx = async () => {
    exporting.value = true;
    try {
        const cv = kit.value.cv || {};
        await downloadLetterDocx({ letter: letter.value, name: cv.name, contact: cv.contact || [], locale: kit.value.locale, fileName: letterFileName(cv.name, props.opportunity.company?.name, kit.value.locale) });
    } catch {
        error.value = text('We could not make the Word file. Please try again.', 'Impossible de créer le fichier Word. Réessayez.');
    } finally {
        exporting.value = false;
    }
};

const applyHere = computed(() => props.opportunity.application_mode === 'internal' && !props.opportunity.application);
const useToApply = () => {
    try { sessionStorage.setItem(`nextward.cover-letter.${props.opportunity.id}`, letter.value); } catch { /* storage may be off */ }
    router.visit(`/opportunities/${props.opportunity.id}/apply`);
};
</script>

<template>
    <section id="cover-letter" class="bg-white border border-line rounded-2xl p-6 sm:p-8 scroll-mt-24" aria-labelledby="kit-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h2 id="kit-title" class="font-display text-xl font-semibold flex items-center gap-2"><Sparkles :size="19" class="text-blue-700" aria-hidden="true" />{{ text('Cover letter for this role', 'Lettre de motivation pour cette offre') }}</h2>
                <p class="text-sm text-slate-600 leading-6 mt-2">{{ text('Written from your profile and resume. Edit it here, then copy it or download it as a Word file.', 'Rédigée à partir de votre profil et de votre CV. Modifiez-la ici, puis copiez-la ou téléchargez-la en fichier Word.') }}</p>
            </div>
            <fieldset class="flex rounded-lg bg-paper-2 p-1 text-sm font-semibold">
                <legend class="sr-only">{{ text('Language of the letter', 'Langue de la lettre') }}</legend>
                <label v-for="option in [['en', 'English'], ['fr', 'Français']]" :key="option[0]" class="cursor-pointer rounded-md px-3 py-1.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="locale === option[0] ? 'bg-white text-navy' : 'text-slate-600'">
                    <input v-model="locale" type="radio" :value="option[0]" class="sr-only">{{ option[1] }}
                </label>
            </fieldset>
        </div>

        <div v-if="!kit" class="mt-6">
            <button type="button" class="cta cta-primary" :disabled="busy" @click="start"><Sparkles :size="17" aria-hidden="true" />{{ busy ? text('Starting…', 'Démarrage…') : text('Write my cover letter', 'Écrire ma lettre') }}</button>
            <p class="text-xs text-slate-500 leading-5 mt-3">
                <template v-if="billing.enabled">{{ text(`Uses 1 credit, with a few rewrites included. You have ${credits}.`, `Utilise 1 crédit, quelques réécritures incluses. Il vous en reste ${credits}.`) }}</template>
                <template v-else>{{ text('Free while we launch.', 'Gratuit pendant le lancement.') }}</template>
                {{ text('We also write a CV for this role. Better results with your resume and experience in', 'Nous rédigeons aussi un CV pour cette offre. Meilleur résultat avec votre CV et vos expériences dans') }} <Link href="/settings" class="text-blue-700 underline">{{ text('Settings', 'Paramètres') }}</Link>.
            </p>
        </div>

        <div v-else-if="kit.status === 'queued'" class="mt-6 rounded-xl bg-paper-2 px-5 py-8 text-center" role="status">
            <RefreshCw class="w-7 h-7 text-blue-700 mx-auto animate-spin" aria-hidden="true" />
            <p class="font-semibold mt-3">{{ text('Writing your cover letter…', 'Rédaction de votre lettre…') }}</p>
            <p class="text-sm text-slate-600 mt-1">{{ text('This usually takes under a minute.', 'Cela prend en général moins d’une minute.') }}</p>
        </div>

        <div v-else-if="kit.status === 'failed' && !letter" class="mt-6 rounded-xl bg-paper-2 px-5 py-8 text-center">
            <p class="font-semibold">{{ kit.error || text('Something went wrong.', 'Une erreur est survenue.') }}</p>
            <button type="button" class="cta cta-primary mt-4" @click="rewrite">{{ text('Try again', 'Réessayer') }}</button>
        </div>

        <div v-else class="mt-6">
            <p class="rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ text('Read it through and fix anything that is not exactly right before you send it.', 'Relisez-la et corrigez ce qui n’est pas tout à fait juste avant de l’envoyer.') }}</p>
            <label class="sr-only" for="cover-letter-text">{{ text('Cover letter', 'Lettre de motivation') }}</label>
            <textarea id="cover-letter-text" v-model="letter" :lang="kit.locale" rows="16" maxlength="20000" class="mt-4 w-full rounded-xl border-line p-4 text-[15px] leading-7 text-slate-800 focus:border-brand focus:ring-brand" @input="edited" @blur="save" />
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button type="button" class="cta cta-primary !py-2.5" @click="copy"><Copy :size="17" aria-hidden="true" />{{ copied ? text('Copied', 'Copiée') : text('Copy', 'Copier') }}</button>
                <button type="button" class="cta cta-secondary !py-2.5" :disabled="exporting || !letter.trim()" @click="exportDocx"><Download :size="17" aria-hidden="true" />{{ text('Word (.docx)', 'Word (.docx)') }}</button>
                <button v-if="applyHere" type="button" class="cta cta-secondary !py-2.5" @click="useToApply">{{ text('Use it to apply', 'L’utiliser pour postuler') }}</button>
                <span class="text-sm text-slate-500" role="status">{{ { saving: text('Saving…', 'Enregistrement…'), saved: text('Saved', 'Enregistrée'), error: text('Not saved. Check your connection.', 'Non enregistrée. Vérifiez votre connexion.') }[saveState] }}</span>
            </div>
            <div class="mt-5 border-t border-line pt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                <button type="button" class="inline-flex items-center gap-1.5 text-blue-700" @click="rewrite"><RefreshCw :size="15" aria-hidden="true" />{{ text('Write it again', 'La réécrire') }}</button>
                <Link :href="`/kits/${kit.id}`" class="inline-flex items-center gap-1.5 text-blue-700"><FileText :size="15" aria-hidden="true" />{{ text('See the CV written for this role', 'Voir le CV rédigé pour cette offre') }}</Link>
                <span v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="font-normal text-slate-500">{{ text(`${kit.rewrites_left} rewrite(s) left`, `${kit.rewrites_left} réécriture(s) restante(s)`) }}</span>
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-700 mt-4" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">{{ text('See packs', 'Voir les packs') }}</a></p>
    </section>
</template>
