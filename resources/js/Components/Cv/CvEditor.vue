<script setup>
import { ref, watch } from 'vue';
import { Plus, Trash2 } from '@lucide/vue';

// Edits a CV (the shape of App\Support\Cv) in place.
const cv = defineModel({ type: Object, required: true });

const split = (value) => value.split(/[,،\n]/).map((part) => part.trim()).filter(Boolean);
const lines = (value) => value.split('\n').map((part) => part.trim()).filter(Boolean);
const texts = ref({});
const reset = () => {
	texts.value = { skills: (cv.value.skills || []).join(', '), languages: (cv.value.languages || []).join(', '), contact: (cv.value.contact || []).join('\n') };
};
reset();
// A new CV (loaded or written again) resets the text boxes.
watch(() => cv.value, reset);
const setList = (key, value, parse) => {
	texts.value[key] = value;
	cv.value[key] = parse(value);
};
const addRow = (list, row) => cv.value[list].push(row);
const removeRow = (list, index) => cv.value[list].splice(index, 1);
const label = 'text-sm font-semibold';
</script>

<template>
	<div class="grid gap-4">
		<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
			<label :class="label">Name<input v-model="cv.name" dir="auto" class="fld mt-2" maxlength="150"></label>
			<label :class="label">Headline<input v-model="cv.headline" dir="auto" class="fld mt-2" maxlength="150"></label>
		</div>
		<label :class="label">Contact lines<textarea :value="texts.contact" dir="auto" class="fld mt-2" rows="3" @input="setList('contact', $event.target.value, lines)"></textarea><span class="text-xs font-normal text-muted">One per line: email, phone, city, links.</span></label>
		<label :class="label">Summary<textarea v-model="cv.summary" dir="auto" class="fld mt-2" rows="4" maxlength="5000"></textarea></label>
		<label :class="label">Skills<input :value="texts.skills" dir="auto" class="fld mt-2" @input="setList('skills', $event.target.value, split)"><span class="text-xs font-normal text-muted">Comma-separated.</span></label>

		<div>
			<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Experience</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('experience', { title: '', company: '', location: '', start: '', end: '', current: false, highlights: [] })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
			<div v-for="(job, index) in cv.experience" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
				<label :class="label">Title<input v-model="job.title" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">Company<input v-model="job.company" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">Location<input v-model="job.location" dir="auto" class="fld mt-2 bg-white"></label>
				<label class="text-sm font-semibold flex items-center gap-2 self-end pb-3"><input v-model="job.current" type="checkbox" class="rounded"> I work here now</label>
				<label :class="label">Start<input v-model="job.start" dir="auto" class="fld mt-2 bg-white" placeholder="2024-09"></label>
				<label :class="label">End<input v-model="job.end" dir="auto" class="fld mt-2 bg-white" :disabled="job.current" placeholder="2025-06"></label>
				<label class="text-sm font-semibold col-span-2 max-[560px]:col-span-1">Highlights<textarea dir="auto" class="fld mt-2 bg-white" rows="4" :value="(job.highlights || []).join('\n')" @input="job.highlights = lines($event.target.value)"></textarea><span class="text-xs font-normal text-muted">One per line.</span></label>
				<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('experience', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
			</div>
		</div>

		<div>
			<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Education</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('education', { school: '', degree: '', field: '', start: '', end: '' })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
			<div v-for="(school, index) in cv.education" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
				<label :class="label">School<input v-model="school.school" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">Degree<input v-model="school.degree" dir="auto" class="fld mt-2 bg-white"></label>
				<label class="text-sm font-semibold col-span-2 max-[560px]:col-span-1">Field of study<input v-model="school.field" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">Start<input v-model="school.start" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">End<input v-model="school.end" dir="auto" class="fld mt-2 bg-white"></label>
				<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('education', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
			</div>
		</div>

		<div>
			<div class="flex items-center justify-between mt-3 mb-2"><h3 class="font-display text-lg font-semibold">Projects</h3><button type="button" class="cbtn cbtn-secondary" @click="addRow('projects', { name: '', summary: '', url: '' })"><Plus class="w-4 h-4" aria-hidden="true" /> Add</button></div>
			<div v-for="(project, index) in cv.projects" :key="index" class="rounded-[14px] bg-paper-3 p-4 mb-3 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
				<label :class="label">Name<input v-model="project.name" dir="auto" class="fld mt-2 bg-white"></label>
				<label :class="label">Link<input v-model="project.url" dir="auto" class="fld mt-2 bg-white"></label>
				<label class="text-sm font-semibold col-span-2 max-[560px]:col-span-1">What it is<textarea v-model="project.summary" dir="auto" class="fld mt-2 bg-white" rows="2"></textarea></label>
				<button type="button" class="justify-self-end col-span-2 max-[560px]:col-span-1 inline-flex items-center gap-1.5 text-[13px] font-semibold text-[#b91c1c]" @click="removeRow('projects', index)"><Trash2 class="w-3.5 h-3.5" aria-hidden="true" /> Remove</button>
			</div>
		</div>

		<label :class="label">Languages<input :value="texts.languages" dir="auto" class="fld mt-2" @input="setList('languages', $event.target.value, split)"><span class="text-xs font-normal text-muted">Comma-separated.</span></label>
	</div>
</template>
