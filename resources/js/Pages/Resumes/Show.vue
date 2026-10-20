<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Download, Languages, RefreshCw, Star, Trash2 } from '@lucide/vue';
import axios from 'axios';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import CvEditor from '@/Components/Cv/CvEditor.vue';
import CvPreview from '@/Components/Cv/CvPreview.vue';
import StylePicker from '@/Components/Cv/StylePicker.vue';
import { downloadCvDocx } from '@/utils/cvDocx';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'profile' }, () => page) });
const props = defineProps({
	resume: { type: Object, default: null },
	draft: { type: Object, default: null },
	main: { type: Object, default: null },
	full: { type: Boolean, default: false },
	themes: { type: Object, required: true },
	locales: { type: Array, required: true },
	ai_enabled: { type: Boolean, default: false },
});

const page = usePage();
const resume = ref(props.resume);
const isNew = computed(() => !resume.value);
const start = props.resume || props.draft;
// Props are reactive proxies, which structuredClone can't copy.
const copy = (value) => JSON.parse(JSON.stringify(value || {}));
const cv = ref(copy(start.content));
const style = ref({ ...start.style });
const locale = ref(props.resume?.language?.code || props.draft?.locale || 'en');
const name = ref(start.name);
const replace = ref(!!props.main);
const makeMain = ref(false);
const saving = ref(false);
const error = ref('');
const billingUrl = ref('');
const snapshot = ref(JSON.stringify([cv.value, style.value, locale.value, name.value]));
const dirty = computed(() => isNew.value || JSON.stringify([cv.value, style.value, locale.value, name.value]) !== snapshot.value);
const status = computed(() => resume.value?.status || 'new');
const labels = computed(() => props.locales.find((option) => option.code === locale.value)?.labels || props.locales[0].labels);
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const free = computed(() => !page.props.billing?.enabled || page.props.billing?.free);

const take = (data) => {
	resume.value = data;
	if (data.content) {
		cv.value = copy(data.content);
		style.value = { ...data.style };
		name.value = data.name;
		snapshot.value = JSON.stringify([cv.value, style.value, locale.value, name.value]);
	}
};

let timer = null;
const poll = () => {
	clearTimeout(timer);
	if (resume.value?.status !== 'queued') return;
	timer = setTimeout(async () => {
		try {
			const { data } = await axios.get(route('resumes.show', resume.value.id), { headers: { Accept: 'application/json' } });
			take(data.resume);
		} catch { /* try again on the next tick */ }
		poll();
	}, 3000);
};
onMounted(poll);
onBeforeUnmount(() => clearTimeout(timer));

const save = () => {
	saving.value = true;
	error.value = '';
	const done = { preserveScroll: true, onFinish: () => (saving.value = false), onError: (errors) => (error.value = Object.values(errors)[0] || 'We could not save your resume.') };
	if (isNew.value) {
		router.post(route('resumes.profile'), { name: name.value, locale: locale.value, style: style.value, content: cv.value, replace: replace.value }, done);
		return;
	}
	router.put(route('resumes.update', resume.value.id), {
		name: name.value, content: cv.value, style: style.value,
		...(resume.value.source === 'profile' ? { locale: locale.value } : {}),
		...(makeMain.value ? { main: true } : {}),
	}, { ...done, onSuccess: () => take(page.props.resume) });
};

const failed = (failure) => {
	const code = failure.response?.status;
	billingUrl.value = failure.response?.data?.billing_url || '';
	error.value = code === 429 ? 'You asked for many translations in a short time. Please wait a little and try again.'
		: code === 403 ? 'Verify your email address to use the AI tools.'
		: failure.response?.data?.message || 'We could not start right now. Please try again in a minute.';
};
const translate = async (target) => {
	if (dirty.value && !window.confirm('Your latest changes are not saved. Translate the saved version?')) return;
	error.value = '';
	try {
		const { data } = await axios.post(route('resumes.translate', resume.value.id), { locale: target.code });
		router.visit(data.url);
	} catch (failure) { failed(failure); }
};
const regenerate = async () => {
	if (resume.value.status === 'draft' && !window.confirm('Translate it again? Your edits to this draft will be replaced.')) return;
	error.value = '';
	try {
		const { data } = await axios.post(route('resumes.regenerate', resume.value.id));
		resume.value = { ...resume.value, ...data.resume };
		poll();
	} catch (failure) { failed(failure); }
};
const refresh = () => {
	if (window.confirm('Lay this resume out again from your profile as it is now? Edits made here will be replaced.')) {
		router.post(route('resumes.refresh', resume.value.id), {}, { preserveScroll: true, onSuccess: () => take(page.props.resume) });
	}
};
const setMain = () => router.post(route('resumes.main', resume.value.id), {}, { preserveScroll: true, onSuccess: () => (resume.value = { ...resume.value, is_main: true }) });
const remove = () => {
	if (window.confirm(`Delete “${resume.value.name}”?`)) router.delete(route('resumes.destroy', resume.value.id));
};
const word = () => downloadCvDocx({
	cv: cv.value, labels: labels.value, locale: locale.value,
	accent: props.themes.accents.find((accent) => accent.id === style.value.accent)?.hex,
	paper: style.value.paper, fileName: `${(cv.value.name || 'Resume').replace(/\s+/g, '_')}_${labels.value.cv}`,
});
const editable = computed(() => isNew.value || (resume.value.editable && ['ready', 'draft'].includes(resume.value.status)));
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1280px] mx-auto">
		<Link :href="`${route('profile.show')}#resumes`" class="inline-flex items-center gap-1.5 text-[13.5px] font-semibold text-muted hover:text-blue-700 mb-4"><ArrowLeft class="w-4 h-4" aria-hidden="true" /> Your resumes</Link>
		<PageHeader :title="isNew ? 'New resume from your profile' : resume.name" :description="isNew ? 'Your profile, laid out with the theme you pick. Edit anything here; your profile itself does not change.' : [resume.language?.label, { uploaded: 'Uploaded PDF', profile: 'From your profile', translation: 'AI translation' }[resume.source], resume.is_main ? 'Main resume' : null].filter(Boolean).join(' · ')" />

		<p v-if="error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">See packs</a></p>

		<div v-if="status === 'queued'" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center" role="status">
			<RefreshCw class="w-8 h-8 text-blue-600 mx-auto animate-spin" aria-hidden="true" />
			<p class="font-display text-lg font-semibold mt-3">Translating your resume…</p>
			<p class="text-sm text-muted mt-1">This usually takes under a minute. You can leave this page and come back.</p>
		</div>

		<div v-else-if="status === 'failed'" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center">
			<p class="font-display text-lg font-semibold">{{ resume.error || 'Something went wrong.' }}</p>
			<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 mt-5" type="button" @click="regenerate">Try again</button>
		</div>

		<div v-else-if="!editable" class="bg-white border border-line rounded-[18px] px-6 py-8">
			<p class="text-[14.5px] text-ink-700">This is a PDF you uploaded, so it can’t be edited here. Download it, translate it with AI, or make a new resume from your profile.</p>
			<div class="flex flex-wrap gap-2.5 mt-5">
				<a :href="route('resumes.download', resume.id)" class="cbtn cbtn-secondary"><Download class="w-4 h-4" aria-hidden="true" /> Download PDF</a>
				<button v-if="!resume.is_main" type="button" class="cbtn cbtn-secondary" @click="setMain"><Star class="w-4 h-4" aria-hidden="true" /> Make main</button>
				<button v-for="target in resume.targets" :key="target.code" type="button" class="cbtn cbtn-secondary" :disabled="!ai_enabled" @click="translate(target)"><Languages class="w-4 h-4" aria-hidden="true" /> {{ target.label }} version</button>
				<button type="button" class="cbtn cbtn-secondary text-[#b91c1c]" @click="remove"><Trash2 class="w-4 h-4" aria-hidden="true" /> Delete</button>
			</div>
		</div>

		<template v-else>
			<p v-if="status === 'draft'" class="mb-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Check every line, then save. The AI only translated your resume; fix anything that is not exactly right.</p>
			<p v-else-if="isNew && full && !replace" class="mb-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">You already have 10 resumes. Replace your main one, or delete one first.</p>

			<div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-6 items-start max-[1080px]:grid-cols-1">
				<form class="grid gap-[18px]" @submit.prevent="save">
					<section class="bg-white border border-line rounded-[18px] px-[22px] py-[22px] grid gap-4">
						<div class="grid grid-cols-[1fr_auto] gap-4 max-[560px]:grid-cols-1">
							<label class="text-sm font-semibold">Name of this resume<input v-model="name" class="fld mt-2" maxlength="150" required></label>
							<label v-if="isNew || resume.source === 'profile'" class="text-sm font-semibold">Section titles in<select v-model="locale" class="fld mt-2"><option v-for="option in locales" :key="option.code" :value="option.code">{{ option.label }}</option></select></label>
						</div>
						<StylePicker v-model="style" :themes="themes" :cv="cv" :locale="locale" />
					</section>
					<section class="bg-white border border-line rounded-[18px] px-[22px] py-[22px]">
						<CvEditor v-model="cv" />
					</section>

					<div class="sticky bottom-0 bg-white py-3 px-1 flex flex-wrap items-center gap-3 border-t border-line">
						<label v-if="isNew && main" class="flex items-center gap-2 text-sm font-semibold"><input v-model="replace" type="checkbox" class="rounded"> Replace my current resume ({{ main.name }})</label>
						<label v-if="!isNew && status === 'draft' && !resume.is_main" class="flex items-center gap-2 text-sm font-semibold"><input v-model="makeMain" type="checkbox" class="rounded"> Make it my main resume</label>
						<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 ml-auto" type="submit" :disabled="saving || (isNew && full && !replace)">{{ saving ? 'Saving…' : isNew ? 'Save resume' : 'Save changes' }}</button>
					</div>
				</form>

				<aside class="lg:sticky top-6 grid gap-3">
					<CvPreview :cv="cv" :style-options="style" :locale="locale" />
					<div class="flex flex-wrap gap-2">
						<a v-if="!isNew && resume.has_file && status === 'ready'" :href="route('resumes.download', resume.id)" class="cbtn cbtn-secondary" :title="dirty ? 'Save first to download your latest changes' : null"><Download class="w-4 h-4" aria-hidden="true" /> PDF</a>
						<button type="button" class="cbtn cbtn-secondary" @click="word"><Download class="w-4 h-4" aria-hidden="true" /> Word</button>
						<button v-if="!isNew && !resume.is_main && status === 'ready'" type="button" class="cbtn cbtn-secondary" @click="setMain"><Star class="w-4 h-4" aria-hidden="true" /> Make main</button>
						<button v-if="!isNew && resume.source === 'profile'" type="button" class="cbtn cbtn-secondary" @click="refresh"><RefreshCw class="w-4 h-4" aria-hidden="true" /> Refresh from my profile</button>
						<button v-for="target in (!isNew ? resume.targets : [])" :key="target.code" type="button" class="cbtn cbtn-secondary" :disabled="!ai_enabled" @click="translate(target)"><Languages class="w-4 h-4" aria-hidden="true" /> {{ target.label }} version</button>
						<button v-if="!isNew && status === 'draft'" type="button" class="cbtn cbtn-secondary" @click="regenerate"><RefreshCw class="w-4 h-4" aria-hidden="true" /> Translate again</button>
						<button v-if="!isNew" type="button" class="cbtn cbtn-secondary text-[#b91c1c]" @click="remove"><Trash2 class="w-4 h-4" aria-hidden="true" /> Delete</button>
					</div>
					<p class="text-xs text-muted">
						<template v-if="dirty && !isNew">Save to update the PDF. </template>
						<template v-if="!isNew && resume.targets?.length && !free">A translation uses 1 credit (3 rewrites included). You have {{ credits }}.</template>
						<template v-if="!isNew && resume.rewrites_left !== null && resume.rewrites_left !== undefined"> {{ resume.rewrites_left }} rewrite(s) left on this credit.</template>
					</p>
				</aside>
			</div>
		</template>
	</div>
</template>
