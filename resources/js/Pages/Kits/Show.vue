<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowUpRight, Copy, Download, Plus, RefreshCw, Trash2 } from '@lucide/vue';
import axios from 'axios';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits' }, () => page) });
const props = defineProps({ kit: { type: Object, required: true } });

const kit = ref(props.kit);
const blankCv = () => ({ name: '', contact: [], headline: '', summary: '', skills: [], experience: [], education: [], projects: [], languages: [] });
const cv = ref({ ...blankCv(), ...(props.kit.cv || {}) });
const letter = ref(props.kit.cover_letter || '');
const skillsText = ref(cv.value.skills.join(', '));
const languagesText = ref(cv.value.languages.join(', '));
const contactText = ref(cv.value.contact.join('\n'));
const saving = ref(false);
const saved = ref(false);
const copied = ref(false);
const error = ref('');
const tab = ref('cv');

const loadFrom = (data) => {
	kit.value = data;
	cv.value = { ...blankCv(), ...(data.cv || {}) };
	letter.value = data.cover_letter || '';
	skillsText.value = cv.value.skills.join(', ');
	languagesText.value = cv.value.languages.join(', ');
	contactText.value = cv.value.contact.join('\n');
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

const split = (value) => value.split(/[,\n]/).map((part) => part.trim()).filter(Boolean);
const lines = (value) => value.split('\n').map((part) => part.trim()).filter(Boolean);
const payload = () => ({
	cover_letter: letter.value,
	cv: { ...cv.value, skills: split(skillsText.value), languages: split(languagesText.value), contact: lines(contactText.value) },
});
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
const applyHere = computed(() => role.value.is_open && role.value.application_mode === 'internal');
const useToApply = () => {
	try { sessionStorage.setItem(`nextward.cover-letter.${role.value.id}`, letter.value); } catch (e) { /* storage may be off */ }
	router.visit(`/opportunities/${role.value.id}/apply`);
};
const addRow = (list, row) => cv.value[list].push(row);
const removeRow = (list, index) => cv.value[list].splice(index, 1);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<PageHeader :title="role.title || 'CV & letter'" :description="[role.company, kit.locale === 'fr' ? 'Written in French' : 'Written in English'].filter(Boolean).join(' · ')">
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

			<form class="bg-white border border-line rounded-[18px]" @submit.prevent="save">
				<div v-show="tab === 'cv'" class="px-[22px] py-[22px] grid gap-4">
					<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
						<label class="text-sm font-semibold">Name<input v-model="cv.name" class="fld mt-2"></label>
						<label class="text-sm font-semibold">Headline<input v-model="cv.headline" class="fld mt-2"></label>
					</div>
					<label class="text-sm font-semibold">Contact lines<textarea v-model="contactText" class="fld mt-2" rows="3"></textarea><span class="text-xs font-normal text-muted">One per line: email, phone, city, links.</span></label>
					<label class="text-sm font-semibold">Profile<textarea v-model="cv.summary" class="fld mt-2" rows="3"></textarea></label>
					<label class="text-sm font-semibold">Skills<input v-model="skillsText" class="fld mt-2"><span class="text-xs font-normal text-muted">Comma-separated.</span></label>

					<div>
						<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Experience</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('experience', { title: '', company: '', location: '', start: '', end: '', highlights: [] })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
						<div v-for="(job, index) in cv.experience" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
							<label class="text-sm font-semibold">Title<input v-model="job.title" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">Company<input v-model="job.company" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">Start<input v-model="job.start" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">End<input v-model="job.end" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold col-span-2 max-[560px]:col-span-1">Highlights<textarea class="fld mt-2 bg-white" rows="4" :value="(job.highlights || []).join('\n')" @input="job.highlights = lines($event.target.value)"></textarea><span class="text-xs font-normal text-muted">One per line.</span></label>
							<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('experience', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
						</div>
					</div>

					<div>
						<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Education</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('education', { school: '', degree: '', field: '', start: '', end: '' })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
						<div v-for="(school, index) in cv.education" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
							<label class="text-sm font-semibold">School<input v-model="school.school" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">Degree<input v-model="school.degree" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">Start<input v-model="school.start" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">End<input v-model="school.end" class="fld mt-2 bg-white"></label>
							<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('education', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
						</div>
					</div>

					<div>
						<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Projects</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('projects', { name: '', summary: '', url: '' })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
						<div v-for="(project, index) in cv.projects" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
							<label class="text-sm font-semibold">Name<input v-model="project.name" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold">Link<input v-model="project.url" class="fld mt-2 bg-white"></label>
							<label class="text-sm font-semibold col-span-2 max-[560px]:col-span-1">What it is<textarea v-model="project.summary" class="fld mt-2 bg-white" rows="2"></textarea></label>
							<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('projects', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
						</div>
					</div>

					<label class="text-sm font-semibold">Languages<input v-model="languagesText" class="fld mt-2"><span class="text-xs font-normal text-muted">Comma-separated.</span></label>
				</div>

				<div v-show="tab === 'letter'" class="px-[22px] py-[22px]">
					<label class="text-sm font-semibold">Cover letter<textarea v-model="letter" class="fld mt-2 leading-7" rows="18"></textarea></label>
				</div>

				<div class="px-[22px] py-4 border-t border-line-2 flex flex-wrap justify-between gap-2.5">
					<div class="flex flex-wrap gap-2.5">
						<a :href="route('kits.cv', kit.id)" class="cbtn cbtn-secondary"><Download class="w-4 h-4" aria-hidden="true" /> CV (PDF)</a>
						<a :href="route('kits.letter', kit.id)" class="cbtn cbtn-secondary"><Download class="w-4 h-4" aria-hidden="true" /> Letter (PDF)</a>
						<button type="button" class="cbtn cbtn-secondary" @click="copyLetter"><Copy class="w-4 h-4" aria-hidden="true" /> {{ copied ? 'Copied' : 'Copy letter' }}</button>
					</div>
					<div class="flex flex-wrap items-center gap-2.5">
						<span v-if="saved" class="text-[13.5px] font-semibold text-[#047857]">Saved.</span>
						<button class="cbtn bg-blue-600 text-white hover:bg-blue-700" type="submit" :disabled="saving">Save changes</button>
					</div>
				</div>
			</form>
			<p class="text-xs text-muted mt-2">Save your changes before downloading, so the PDF has them.</p>

			<div class="flex flex-wrap gap-2.5 mt-6">
				<button v-if="applyHere" type="button" class="cbtn bg-blue-600 text-white hover:bg-blue-700" @click="useToApply">Use the letter to apply</button>
				<a v-else-if="role.is_open && role.application_url" :href="role.application_url" target="_blank" rel="noopener noreferrer" class="cbtn bg-blue-600 text-white hover:bg-blue-700">Apply on the employer’s site <ArrowUpRight class="w-4 h-4" aria-hidden="true" /></a>
				<button type="button" class="cbtn cbtn-secondary" @click="regenerate"><RefreshCw class="w-4 h-4" aria-hidden="true" /> Write again</button>
				<span v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="self-center text-sm text-muted">{{ kit.rewrites_left }} rewrite(s) left on this credit</span>
				<button type="button" class="cbtn cbtn-secondary text-[#b91c1c] ml-auto" @click="remove"><Trash2 class="w-4 h-4" aria-hidden="true" /> Delete</button>
			</div>
		</template>
	</div>
</template>
