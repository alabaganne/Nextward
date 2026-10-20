<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, FileText, Languages, Pencil, Plus, Star, Trash2, Upload } from '@lucide/vue';
import axios from 'axios';

// The candidate's resumes on their profile page: upload, make one from the profile, translate, pick the main one.
const props = defineProps({
	resumes: { type: Array, default: () => [] },
	limit: { type: Number, default: 10 },
	emailVerified: { type: Boolean, default: false },
});
const page = usePage();
const billing = computed(() => page.props.billing || {});
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const free = computed(() => !billing.value.enabled || billing.value.free);
const full = computed(() => props.resumes.length >= props.limit);
const upload = useForm({ resume: null });
const fileInput = ref(null);
const busy = ref(null);
const error = ref('');
const billingUrl = ref('');

const pick = (event) => {
	upload.resume = event.target.files?.[0] || null;
	if (upload.resume) upload.post(route('resumes.store'), { forceFormData: true, preserveScroll: true, onFinish: () => { upload.reset(); if (fileInput.value) fileInput.value.value = ''; } });
};
const makeMain = (resume) => router.post(route('resumes.main', resume.id), {}, { preserveScroll: true });
const remove = (resume) => {
	if (window.confirm(`Delete “${resume.name}”?${resume.is_main ? ' Your newest other resume becomes the main one.' : ''}`)) router.delete(route('resumes.destroy', resume.id), { preserveScroll: true });
};
const translate = async (resume, target) => {
	error.value = '';
	billingUrl.value = '';
	busy.value = `${resume.id}-${target.code}`;
	try {
		const { data } = await axios.post(route('resumes.translate', resume.id), { locale: target.code });
		router.visit(data.url);
	} catch (failure) {
		const status = failure.response?.status;
		billingUrl.value = failure.response?.data?.billing_url || '';
		error.value = status === 429 ? 'You asked for many translations in a short time. Please wait a little and try again.'
			: status === 403 ? 'Verify your email address to use the AI tools.'
			: failure.response?.data?.message || 'We could not start the translation. Please try again in a minute.';
	} finally {
		busy.value = null;
	}
};
const source = { uploaded: 'Uploaded PDF', profile: 'From your profile', translation: 'AI translation' };
const status = { queued: 'Being translated…', draft: 'Draft: check and save it', failed: 'Translation failed' };
</script>

<template>
	<section id="resumes" class="bg-white border border-line rounded-[18px] mb-[18px] scroll-mt-24" aria-labelledby="resumes-title">
		<div class="flex flex-wrap items-center justify-between gap-3 px-[22px] py-[18px] border-b border-line-2">
			<div>
				<h3 id="resumes-title" class="font-display text-[16.5px] font-semibold">Resumes</h3>
				<p class="text-[13px] text-muted mt-0.5">Your main resume is the one you share, apply with by default, and the one the AI reads.</p>
			</div>
			<div class="flex flex-wrap gap-2">
				<Link v-if="!full" :href="route('resumes.create')" class="cbtn bg-blue-600 text-white hover:bg-blue-700"><Plus class="w-4 h-4" aria-hidden="true" /> Make one from my profile</Link>
				<label v-if="!full" class="cbtn cbtn-secondary cursor-pointer has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand">
					<Upload class="w-4 h-4" aria-hidden="true" /> {{ upload.processing ? 'Uploading…' : 'Upload PDF' }}
					<input ref="fileInput" type="file" accept="application/pdf" class="sr-only" :disabled="upload.processing" @change="pick">
				</label>
			</div>
		</div>

		<p v-if="upload.errors.resume" class="mx-[22px] mt-4 text-sm text-red-700" role="alert">{{ upload.errors.resume }}</p>
		<p v-if="error" class="mx-[22px] mt-4 text-sm text-red-700" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">See packs</a></p>

		<ul v-if="resumes.length" class="divide-y divide-line-2">
			<li v-for="resume in resumes" :key="resume.id" class="px-[22px] py-4 flex flex-wrap items-start justify-between gap-3">
				<div class="flex items-start gap-3 min-w-0">
					<span class="w-10 h-10 rounded-[10px] grid place-items-center shrink-0 bg-blue-50 text-blue-700"><FileText class="w-5 h-5" aria-hidden="true" /></span>
					<div class="min-w-0">
						<p class="font-semibold text-[14.5px] truncate">{{ resume.name }}</p>
						<p class="flex flex-wrap items-center gap-1.5 mt-1 text-xs">
							<span v-if="resume.is_main" class="rounded-full bg-blue-600 text-white px-2 py-0.5 font-semibold">Main</span>
							<span class="rounded-full bg-paper-2 px-2 py-0.5 text-ink-700">{{ source[resume.source] }}</span>
							<span v-if="resume.language" class="rounded-full bg-paper-2 px-2 py-0.5 text-ink-700">{{ resume.language.label }}</span>
							<span v-if="status[resume.status]" class="rounded-full px-2 py-0.5 font-semibold" :class="resume.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'">{{ status[resume.status] }}</span>
							<span class="text-muted">Updated {{ resume.updated_label }}</span>
						</p>
					</div>
				</div>
				<div class="flex flex-wrap items-center gap-2 text-[13px]">
					<a v-if="resume.has_file && resume.status === 'ready'" :href="route('resumes.download', resume.id)" class="cbtn cbtn-secondary !py-1.5"><Download class="w-4 h-4" aria-hidden="true" /> PDF</a>
					<Link v-if="resume.editable" :href="route('resumes.show', resume.id)" class="cbtn cbtn-secondary !py-1.5"><Pencil class="w-4 h-4" aria-hidden="true" /> {{ resume.status === 'draft' ? 'Check draft' : 'Open' }}</Link>
					<button v-if="!resume.is_main && resume.status === 'ready'" type="button" class="cbtn cbtn-secondary !py-1.5" @click="makeMain(resume)"><Star class="w-4 h-4" aria-hidden="true" /> Make main</button>
					<button v-for="target in resume.targets" :key="target.code" type="button" class="cbtn cbtn-secondary !py-1.5" :disabled="full || busy !== null || !emailVerified" :title="!emailVerified ? 'Verify your email to use the AI tools' : null" @click="translate(resume, target)"><Languages class="w-4 h-4" aria-hidden="true" /> {{ busy === `${resume.id}-${target.code}` ? 'Starting…' : `${target.label} version` }}</button>
					<button type="button" class="cbtn cbtn-secondary !py-1.5 text-[#b91c1c]" :aria-label="`Delete ${resume.name}`" @click="remove(resume)"><Trash2 class="w-4 h-4" aria-hidden="true" /></button>
				</div>
			</li>
		</ul>
		<div v-else class="px-[22px] py-6 text-[14.5px] text-muted leading-[1.7]">
			No resume yet. Upload the PDF you already have, or make one from your profile with one of our themes. It’s free.
		</div>
		<p class="px-[22px] py-3 border-t border-line-2 text-xs text-muted">
			{{ resumes.length }} of {{ limit }} resumes. Making a resume from your profile, changing its theme and downloading are free.
			<template v-if="!free">An AI translation uses 1 credit (3 rewrites included). You have {{ credits }}.</template>
		</p>
	</section>
</template>
