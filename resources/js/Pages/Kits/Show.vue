<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight, Check, ChevronDown, Copy, Download, Ellipsis, FileText, RefreshCw, Send, Trash2 } from '@lucide/vue';
import axios from 'axios';
import AppShell from '@/Layouts/AppShell.vue';
import CvStudio from '@/Components/Cv/CvStudio.vue';
import Menu from '@/Components/Ui/Menu.vue';
import { menuItem } from '@/utils/menu';
import { downloadCvDocx } from '@/utils/cvDocx';
import { downloadLetterDocx, letterFileName } from '@/utils/letterDocx';
import { appPath } from '@/utils/url';
import { useEditorGuards } from '@/utils/editor';
import { markOpened } from '@/composables/useOpportunityActivity';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits', fill: true }, () => page) });
const props = defineProps({ kit: { type: Object, required: true }, themes: { type: Object, required: true }, locales: { type: Array, default: () => [] } });

const kit = ref(props.kit);
const blankCv = () => ({ name: '', contact: [], headline: '', summary: '', skills: [], experience: [], education: [], projects: [], languages: [] });
const copy = (value) => JSON.parse(JSON.stringify(value || {}));
const cv = ref({ ...blankCv(), ...copy(props.kit.cv) });
const style = ref({ ...props.kit.style });
const letter = ref(props.kit.cover_letter || '');
const saving = ref(false);
const copied = ref(false);
const error = ref('');
const current = () => JSON.stringify([cv.value, style.value, letter.value]);
const snapshot = ref(current());
const dirty = computed(() => current() !== snapshot.value);

const loadFrom = (data) => {
	kit.value = data;
	cv.value = { ...blankCv(), ...copy(data.cv) };
	style.value = { ...data.style };
	letter.value = data.cover_letter || '';
	snapshot.value = current();
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

// Saves in the background so the editor keeps its place. Returns true once the server has the latest version.
const save = async () => {
	if (saving.value) return false;
	if (!dirty.value) return true;
	saving.value = true;
	error.value = '';
	const sent = current();
	try {
		await axios.put(route('kits.update', kit.value.id), { cover_letter: letter.value, cv: cv.value, style: style.value });
		snapshot.value = sent;
		return true;
	} catch (failure) {
		const errors = failure.response?.data?.errors;
		error.value = (errors && Object.values(errors)[0]?.[0]) || failure.response?.data?.message || 'We could not save your changes. Please try again.';
		return false;
	} finally {
		saving.value = false;
	}
};
const { allowLeave } = useEditorGuards({ dirty, save });

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
	if (!window.confirm('Delete this CV and letter?')) return;
	allowLeave();
	router.delete(route('kits.destroy', kit.value.id));
};
const copyLetter = async () => {
	await navigator.clipboard.writeText(letter.value);
	copied.value = true;
	setTimeout(() => (copied.value = false), 2000);
};
const role = computed(() => kit.value.role || {});
// PDFs are printed from the saved version, so unsaved changes are saved first.
const pdf = async (url) => {
	if (await save()) window.location.href = url;
};
const letterDocx = () => downloadLetterDocx({ letter: letter.value, name: cv.value.name, contact: cv.value.contact, locale: kit.value.locale, fileName: letterFileName(cv.value.name, role.value.company, kit.value.locale) });
const cvDocx = () => downloadCvDocx({
	cv: cv.value, labels: kit.value.labels, locale: kit.value.locale, paper: style.value.paper,
	accent: props.themes.accents.find((accent) => accent.id === style.value.accent)?.hex,
	fileName: [cv.value.name || 'Resume', kit.value.labels?.cv, role.value.company].filter(Boolean).join('_').replace(/\s+/g, '_'),
});
const applyHere = computed(() => role.value.is_open && role.value.application_mode === 'internal' && !kit.value.application_id);
// The apply page picks up the letter and the saved CV.
const useToApply = async () => {
	if (!(await save())) return;
	try {
		sessionStorage.setItem(`nextward.cover-letter.${role.value.id}`, letter.value);
		sessionStorage.setItem(`nextward.kit-cv.${role.value.id}`, String(kit.value.id));
	} catch (e) { /* storage may be off */ }
	router.visit(route('applications.create', role.value.id));
};
const applyThere = computed(() => role.value.is_open && role.value.application_mode === 'external' && role.value.application_url && !kit.value.application_id);
const tracking = ref(false);
const markApplied = async () => {
	if (!(await save())) return;
	tracking.value = true;
	router.post(route('applications.track', role.value.id), {}, {
		preserveScroll: true,
		preserveState: true,
		onSuccess: (page) => { kit.value = { ...kit.value, application_id: page.props.kit?.application_id }; },
		onError: (errors) => { error.value = Object.values(errors)[0] || 'This could not be saved. Please try again.'; },
		onFinish: () => { tracking.value = false; },
	});
};
const hasApply = computed(() => !!kit.value.application_id || applyHere.value || !!(role.value.is_open && role.value.application_url));
const meta = computed(() => [role.value.company, `Written in ${kit.value.language}`].filter(Boolean).join(' · '));
const ready = computed(() => kit.value.status !== 'queued' && !(kit.value.status === 'failed' && !kit.value.cv));
</script>

<template>
	<CvStudio v-if="ready" v-model:cv="cv" v-model:style="style" v-model:letter="letter" :themes="themes" :locale="kit.locale" with-letter>
		<template #title>
			<Link :href="route('kits.index')" class="icon-btn shrink-0 -ml-2" aria-label="Back to CV & letters"><ArrowLeft class="size-5" /></Link>
			<div class="min-w-0">
				<h1 class="font-display text-[17px] font-semibold text-ink truncate" dir="auto">{{ role.title || 'CV & letter' }}</h1>
				<p class="text-[13px] text-muted truncate" dir="auto">{{ meta }}</p>
			</div>
		</template>

		<template #actions>
			<span class="hidden xl:inline-flex items-center gap-1.5 text-[13px] text-muted mr-1" role="status">
				<template v-if="saving">Saving…</template>
				<template v-else-if="dirty"><span class="size-2 rounded-full bg-amber-500" aria-hidden="true" />Unsaved changes</template>
				<template v-else><Check class="size-4 text-[#047857]" aria-hidden="true" />All changes saved</template>
			</span>
			<Menu width="w-64">
				<template #button="{ open, toggle }">
					<button type="button" class="icon-btn border border-line" aria-label="More actions" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Ellipsis class="size-5" /></button>
				</template>
				<Link v-if="role.url" :class="menuItem" role="menuitem" :href="appPath(role.url)"><ArrowUpRight class="size-4 text-muted-2" />Open the listing</Link>
				<button :class="menuItem" role="menuitem" type="button" @click="regenerate"><RefreshCw class="size-4 text-muted-2" />Write again</button>
				<p v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="px-3 pb-1.5 -mt-0.5 pl-[38px] text-xs text-muted">{{ kit.rewrites_left }} rewrite{{ kit.rewrites_left === 1 ? '' : 's' }} left on this credit</p>
				<div class="h-px bg-line-2 my-1.5" />
				<button :class="menuItem" class="!text-red-700 hover:!bg-red-50 hover:!text-red-800" role="menuitem" type="button" @click="remove"><Trash2 class="size-4 text-red-600" />Delete</button>
			</Menu>
			<button type="button" class="cbtn h-10 !py-0 !px-4 !text-sm disabled:opacity-60" :class="hasApply ? 'cbtn-secondary' : 'cbtn-primary'" :disabled="saving || !dirty" @click="save">{{ saving ? 'Saving…' : 'Save' }}</button>
			<Link v-if="kit.application_id" :href="route('applications.show', kit.application_id)" class="cbtn cbtn-primary h-10 !py-0 !px-4 !text-sm">See your application</Link>
			<button v-else-if="applyHere" type="button" class="cbtn cbtn-primary h-10 !py-0 !px-4 !text-sm" @click="useToApply"><Send class="size-4" aria-hidden="true" />Apply with this CV</button>
			<template v-else-if="role.is_open && role.application_url">
				<button v-if="applyThere" type="button" class="cbtn cbtn-secondary h-10 !py-0 !px-4 !text-sm disabled:opacity-60" :disabled="tracking" @click="markApplied"><Check class="size-4" aria-hidden="true" />{{ tracking ? 'Saving…' : 'I applied' }}</button>
				<a :href="role.application_url" target="_blank" rel="noopener noreferrer" class="cbtn cbtn-primary h-10 !py-0 !px-4 !text-sm" @click="markOpened(role)" @auxclick="markOpened(role)">Apply on their site <ArrowUpRight class="size-4" aria-hidden="true" /></a>
			</template>
		</template>

		<template #notice>
			<p v-if="kit.error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-[13px] text-red-800" role="alert">{{ kit.error }}</p>
			<p v-if="error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-[13px] text-red-800" role="alert">{{ error }}</p>
			<p class="mb-7 rounded-xl bg-blue-50 px-4 py-3 text-[13px] leading-relaxed text-blue-900">Check every line before you use it. The AI only had your profile and resume to work with, so fix anything that is not exactly right.</p>
		</template>

		<template #letter-actions>
			<button type="button" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 font-semibold text-blue-700 hover:bg-blue-50" :disabled="!letter.trim()" @click="copyLetter"><Copy class="size-3.5" aria-hidden="true" />{{ copied ? 'Copied' : 'Copy the letter' }}</button>
		</template>

		<template #preview-actions>
			<Menu width="w-56">
				<template #button="{ open, toggle }">
					<button type="button" class="inline-flex items-center gap-2 h-9 px-3.5 rounded-[10px] bg-white ring-1 ring-line text-[13.5px] font-semibold text-ink hover:ring-blue-300" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Download class="size-4" aria-hidden="true" />Download<ChevronDown class="size-4 text-muted-2" aria-hidden="true" /></button>
				</template>
				<p class="px-3 pt-1.5 pb-1 text-[11.5px] font-semibold uppercase tracking-[.08em] text-muted-2">CV</p>
				<button :class="menuItem" role="menuitem" type="button" @click="pdf(route('kits.cv', kit.id))"><FileText class="size-4 text-muted-2" />PDF</button>
				<button :class="menuItem" role="menuitem" type="button" @click="cvDocx"><FileText class="size-4 text-muted-2" />Word</button>
				<p class="px-3 pt-2.5 pb-1 text-[11.5px] font-semibold uppercase tracking-[.08em] text-muted-2">Cover letter</p>
				<button :class="menuItem" role="menuitem" type="button" :disabled="!letter.trim()" @click="pdf(route('kits.letter', kit.id))"><FileText class="size-4 text-muted-2" />PDF</button>
				<button :class="menuItem" role="menuitem" type="button" :disabled="!letter.trim()" @click="letterDocx"><FileText class="size-4 text-muted-2" />Word</button>
			</Menu>
		</template>
	</CvStudio>

	<div v-else class="flex-1 overflow-y-auto">
		<div class="px-8 max-[680px]:px-5 pt-7 pb-14">
			<Link :href="route('kits.index')" class="inline-flex items-center gap-1.5 text-[13.5px] font-semibold text-muted hover:text-blue-700"><ArrowLeft class="size-4" aria-hidden="true" /> CV & letters</Link>
			<h1 class="font-display text-2xl font-semibold text-ink mt-3" dir="auto">{{ role.title || 'CV & letter' }}</h1>
			<p class="text-sm text-muted mt-1" dir="auto">{{ meta }}</p>

			<div v-if="kit.status === 'queued'" class="mt-8 bg-white border border-line rounded-[18px] px-6 py-14 text-center" role="status">
				<RefreshCw class="size-8 text-blue-600 mx-auto animate-spin" aria-hidden="true" />
				<p class="font-display text-lg font-semibold mt-4">Writing your CV and cover letter…</p>
				<p class="text-sm text-muted mt-1">This usually takes under a minute. You can leave this page and come back.</p>
			</div>
			<div v-else class="mt-8 bg-white border border-line rounded-[18px] px-6 py-14 text-center">
				<p class="font-display text-lg font-semibold">{{ kit.error || 'Something went wrong.' }}</p>
				<p v-if="error" class="text-sm text-red-700 mt-2" role="alert">{{ error }}</p>
				<button class="cbtn cbtn-primary mt-6" type="button" @click="regenerate">Try again</button>
			</div>
		</div>
	</div>
</template>
