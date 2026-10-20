<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowUpRight, Copy, Download, RefreshCw, Trash2 } from '@lucide/vue';
import axios from 'axios';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import CvEditor from '@/Components/Cv/CvEditor.vue';
import CvPreview from '@/Components/Cv/CvPreview.vue';
import StylePicker from '@/Components/Cv/StylePicker.vue';
import { downloadCvDocx } from '@/utils/cvDocx';
import { downloadLetterDocx, letterFileName } from '@/utils/letterDocx';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits' }, () => page) });
const props = defineProps({ kit: { type: Object, required: true }, themes: { type: Object, required: true }, locales: { type: Array, default: () => [] } });

const kit = ref(props.kit);
const blankCv = () => ({ name: '', contact: [], headline: '', summary: '', skills: [], experience: [], education: [], projects: [], languages: [] });
const cv = ref({ ...blankCv(), ...(props.kit.cv || {}) });
const style = ref({ ...props.kit.style });
const letter = ref(props.kit.cover_letter || '');
const saving = ref(false);
const saved = ref(false);
const copied = ref(false);
const error = ref('');
const tab = ref('cv');

const loadFrom = (data) => {
	kit.value = data;
	cv.value = { ...blankCv(), ...(data.cv || {}) };
	style.value = { ...data.style };
	letter.value = data.cover_letter || '';
};

let timer = null;
const poll = () => {
	clearTimeout(timer);
	if (kit.value.status !== 'queued') return;
	timer = setTimeout(async () => {
		try {
			const { data } = await axios.get(route('kits.show', kit.value.id), { headers: { Accept: 'application/json' } });
			if (data.kit.status === 'queued') kit.value = { ...kit.value, ...data.kit };
			else loadFrom(data.kit);
		} catch (e) { /* try again on the next tick */ }
		poll();
	}, 3000);
};
onMounted(poll);
onBeforeUnmount(() => clearTimeout(timer));

const payload = () => ({ cover_letter: letter.value, cv: cv.value, style: style.value });
const save = () => {
	saving.value = true;
	router.put(route('kits.update', kit.value.id), payload(), {
		preserveScroll: true,
		onSuccess: () => { saved.value = true; setTimeout(() => (saved.value = false), 2500); },
		onFinish: () => (saving.value = false),
	});
};
const regenerate = async () => {
	if (!window.confirm('Write a new CV and letter for this role? Your edits will be replaced.')) return;
	error.value = '';
	try {
		const { data } = await axios.post(route('kits.regenerate', kit.value.id));
		kit.value = { ...kit.value, ...data.kit };
		poll();
	} catch (failure) {
		error.value = failure.response?.data?.message || 'We could not start right now. Please try again in a minute.';
	}
};
const remove = () => {
	if (window.confirm('Delete this CV and letter?')) router.delete(route('kits.destroy', kit.value.id));
};
const copyLetter = async () => {
	await navigator.clipboard.writeText(letter.value);
	copied.value = true;
	setTimeout(() => (copied.value = false), 2000);
};
const role = computed(() => kit.value.role || {});
const letterDocx = () => downloadLetterDocx({ letter: letter.value, name: cv.value.name, contact: cv.value.contact, locale: kit.value.locale, fileName: letterFileName(cv.value.name, role.value.company, kit.value.locale) });
const cvDocx = () => downloadCvDocx({
	cv: cv.value, labels: kit.value.labels, locale: kit.value.locale, paper: style.value.paper,
	accent: props.themes.accents.find((accent) => accent.id === style.value.accent)?.hex,
	fileName: [cv.value.name || 'Resume', kit.value.labels?.cv, role.value.company].filter(Boolean).join('_').replace(/\s+/g, '_'),
});
const applyHere = computed(() => role.value.is_open && role.value.application_mode === 'internal' && !kit.value.application_id);
// The apply page picks up the letter and this CV.
const useToApply = () => {
	try {
		sessionStorage.setItem(`nextward.cover-letter.${role.value.id}`, letter.value);
		sessionStorage.setItem(`nextward.kit-cv.${role.value.id}`, String(kit.value.id));
	} catch (e) { /* storage may be off */ }
	router.visit(`/opportunities/${role.value.id}/apply`);
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1280px] mx-auto">
		<PageHeader :title="role.title || 'CV & letter'" :description="[role.company, `Written in ${kit.language}`].filter(Boolean).join(' · ')">
			<template #actions>
				<a v-if="role.url" :href="role.url" class="cbtn cbtn-secondary h-11 !py-0">Open the listing <ArrowUpRight class="w-4 h-4" aria-hidden="true" /></a>
			</template>
		</PageHeader>

		<div v-if="kit.status === 'queued'" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center" role="status">
			<RefreshCw class="w-8 h-8 text-blue-600 mx-auto animate-spin" aria-hidden="true" />
			<p class="font-display text-lg font-semibold mt-3">Writing your CV and cover letter…</p>
			<p class="text-sm text-muted mt-1">This usually takes under a minute. You can leave this page and come back.</p>
		</div>

		<div v-else-if="kit.status === 'failed' && !kit.cv" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center">
			<p class="font-display text-lg font-semibold">{{ kit.error || 'Something went wrong.' }}</p>
			<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 mt-5" type="button" @click="regenerate">Try again</button>
		</div>

		<template v-else>
			<p v-if="kit.error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ kit.error }}</p>
			<p v-if="error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>
			<p class="mb-5 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-900">Check every line before you use it. The AI only had your profile and resume to work with, so fix anything that is not exactly right.</p>

			<div class="flex flex-wrap gap-2.5 mb-5">
				<button type="button" class="cbtn" :class="tab === 'cv' ? 'bg-navy-900 text-white' : 'cbtn-secondary'" :aria-pressed="tab === 'cv'" @click="tab = 'cv'">CV</button>
				<button type="button" class="cbtn" :class="tab === 'letter' ? 'bg-navy-900 text-white' : 'cbtn-secondary'" :aria-pressed="tab === 'letter'" @click="tab = 'letter'">Cover letter</button>
			</div>

			<div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-6 items-start max-[1080px]:grid-cols-1">
			<form class="bg-white border border-line rounded-[18px]" @submit.prevent="save">
				<div v-show="tab === 'cv'" class="px-[22px] py-[22px] grid gap-6">
					<StylePicker v-model="style" :themes="themes" :cv="cv" :locale="kit.locale" />
					<CvEditor v-model="cv" />
				</div>

				<div v-show="tab === 'letter'" class="px-[22px] py-[22px]">
					<label class="text-sm font-semibold">Cover letter<textarea v-model="letter" class="fld mt-2 leading-7" rows="18"></textarea></label>
				</div>

				<div class="px-[22px] py-4 border-t border-line-2 flex flex-wrap justify-between gap-2.5">
					<div class="flex flex-wrap gap-2.5">
						<a :href="route('kits.cv', kit.id)" class="cbtn cbtn-secondary"><Download class="w-4 h-4" aria-hidden="true" /> CV (PDF)</a>
						<button type="button" class="cbtn cbtn-secondary" @click="cvDocx"><Download class="w-4 h-4" aria-hidden="true" /> CV (Word)</button>
						<a :href="route('kits.letter', kit.id)" class="cbtn cbtn-secondary"><Download class="w-4 h-4" aria-hidden="true" /> Letter (PDF)</a>
						<button type="button" class="cbtn cbtn-secondary" :disabled="!letter.trim()" @click="letterDocx"><Download class="w-4 h-4" aria-hidden="true" /> Letter (Word)</button>
						<button type="button" class="cbtn cbtn-secondary" @click="copyLetter"><Copy class="w-4 h-4" aria-hidden="true" /> {{ copied ? 'Copied' : 'Copy letter' }}</button>
					</div>
					<div class="flex flex-wrap items-center gap-2.5">
						<span v-if="saved" class="text-[13.5px] font-semibold text-[#047857]">Saved.</span>
						<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="saving">Save changes</button>
					</div>
				</div>
			</form>
			<aside class="lg:sticky top-6">
				<CvPreview v-if="tab === 'cv'" :cv="cv" :style-options="style" :locale="kit.locale" />
				<CvPreview v-else :cv="cv" :style-options="style" :locale="kit.locale" :letter="letter" />
				<p class="text-xs text-muted mt-2">Save your changes before downloading, so the PDF has them.</p>
			</aside>
			</div>

			<div class="flex flex-wrap gap-2.5 mt-6">
				<Link v-if="kit.application_id" :href="route('applications.show', kit.application_id)" class="cbtn bg-blue-600 text-white hover:bg-blue-700">See your application</Link>
				<button v-else-if="applyHere" type="button" class="cbtn bg-blue-600 text-white hover:bg-blue-700" @click="useToApply">Apply with this CV and letter</button>
				<a v-else-if="role.is_open && role.application_url" :href="role.application_url" target="_blank" rel="noopener noreferrer" class="cbtn bg-blue-600 text-white hover:bg-blue-700">Apply on the employer’s site <ArrowUpRight class="w-4 h-4" aria-hidden="true" /></a>
				<button type="button" class="cbtn cbtn-secondary" @click="regenerate"><RefreshCw class="w-4 h-4" aria-hidden="true" /> Write again</button>
				<span v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="self-center text-sm text-muted">{{ kit.rewrites_left }} rewrite(s) left on this credit</span>
				<button type="button" class="cbtn cbtn-secondary text-[#b91c1c] ml-auto" @click="remove"><Trash2 class="w-4 h-4" aria-hidden="true" /> Delete</button>
			</div>
		</template>
	</div>
</template>
