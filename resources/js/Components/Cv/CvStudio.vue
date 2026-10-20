<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { AlignLeft, ArrowLeft, ArrowRight, Briefcase, Eye, FolderGit2, GraduationCap, IdCard, Languages, Mail, Palette, PenLine, Wrench } from '@lucide/vue';
import CvEditor from '@/Components/Cv/CvEditor.vue';
import CvPreview from '@/Components/Cv/CvPreview.vue';
import StylePicker from '@/Components/Cv/StylePicker.vue';

// The full-height CV editor: sections on the left, one section at a time in the middle, the printed pages on the right.
const cv = defineModel('cv', { type: Object, required: true });
const style = defineModel('style', { type: Object, required: true });
const letter = defineModel('letter', { type: String, default: null });
const props = defineProps({
	themes: { type: Object, required: true },
	locale: { type: String, default: 'en' },
	withLetter: { type: Boolean, default: false },
});

const sections = computed(() => [
	{ id: 'design', label: 'Design', icon: Palette, hint: 'Pick a theme, an accent colour, the type and the paper size.' },
	{ id: 'header', label: 'Name & contact', icon: IdCard, hint: 'Your name, the line under it and how employers can reach you.' },
	{ id: 'summary', label: 'About', icon: AlignLeft, hint: 'A few lines on who you are and what you do best.' },
	{ id: 'experience', label: 'Experience', icon: Briefcase, count: cv.value.experience?.length, hint: 'Your jobs and internships, newest first. Open one to edit it.' },
	{ id: 'education', label: 'Education', icon: GraduationCap, count: cv.value.education?.length, hint: 'Schools, degrees and fields of study.' },
	{ id: 'projects', label: 'Projects', icon: FolderGit2, count: cv.value.projects?.length, hint: 'Things you built, with a link when there is one.' },
	{ id: 'skills', label: 'Skills', icon: Wrench, count: cv.value.skills?.length, hint: 'The tools and skills you want employers to see.' },
	{ id: 'languages', label: 'Languages', icon: Languages, count: cv.value.languages?.length, hint: 'The languages you speak.' },
	...(props.withLetter ? [{ id: 'letter', label: 'Cover letter', icon: Mail, hint: 'The letter you send with this CV.', group: 'letter' }] : []),
]);
const section = ref('design');
const index = computed(() => sections.value.findIndex((item) => item.id === section.value));
const current = computed(() => sections.value[index.value]);
const previous = computed(() => sections.value[index.value - 1]);
const next = computed(() => sections.value[index.value + 1]);

// On small screens the preview replaces the editor.
const showPreview = ref(false);
const previewing = ref('cv');
watch(section, (id) => { previewing.value = id === 'letter' ? 'letter' : 'cv'; });
const pages = ref(1);

const editor = ref(null);
const go = async (id) => {
	section.value = id;
	showPreview.value = false;
	await nextTick();
	editor.value?.scrollTo({ top: 0 });
};
const words = computed(() => (letter.value || '').trim().split(/\s+/).filter(Boolean).length);
defineExpose({ go });
</script>

<template>
	<div class="flex-1 min-h-0 flex flex-col">
		<header class="shrink-0 bg-white border-b border-line px-6 max-[680px]:px-4 py-3 min-h-[68px] flex items-center gap-x-4 gap-y-3 flex-wrap">
			<div class="flex items-center gap-3 min-w-0 flex-1 basis-[260px]">
				<slot name="title" />
			</div>
			<div class="flex items-center gap-2 flex-wrap">
				<button type="button" class="xl:hidden inline-flex items-center gap-2 h-10 px-3.5 rounded-[10px] border border-line bg-white text-sm font-semibold text-ink hover:border-blue-300" :aria-pressed="showPreview" @click="showPreview = !showPreview">
					<component :is="showPreview ? PenLine : Eye" class="size-4" aria-hidden="true" />{{ showPreview ? 'Edit' : 'Preview' }}
				</button>
				<slot name="actions" />
			</div>
		</header>

		<div class="flex-1 min-h-0 grid xl:grid-cols-[auto_clamp(380px,34vw,540px)_minmax(0,1fr)] max-xl:grid-rows-[auto_minmax(0,1fr)]">
			<nav class="bg-white border-line xl:border-r xl:overflow-y-auto xl:py-5 xl:px-3 xl:w-[76px] min-[87.5rem]:w-[228px] max-xl:border-b max-xl:flex max-xl:gap-1.5 max-xl:overflow-x-auto max-xl:px-4 max-xl:py-2.5 [scrollbar-width:thin]" aria-label="Sections">
				<template v-for="(item, at) in sections" :key="item.id">
					<p v-if="at === 0" class="hidden min-[87.5rem]:block px-3 pb-2 text-[11.5px] font-semibold uppercase tracking-[.08em] text-muted-2">CV</p>
					<p v-if="item.group === 'letter'" class="hidden min-[87.5rem]:block px-3 pt-5 pb-2 text-[11.5px] font-semibold uppercase tracking-[.08em] text-muted-2">Letter</p>
					<div v-if="item.group === 'letter'" class="hidden xl:block min-[87.5rem]:hidden h-px bg-line-2 mx-2 my-3" aria-hidden="true" />
					<button
						type="button"
						class="group flex items-center gap-3 rounded-[10px] text-[14px] font-medium transition-colors focus-visible:outline-2 focus-visible:outline-blue-500 xl:w-full xl:py-2.5 xl:mb-0.5 xl:justify-center xl:px-0 min-[87.5rem]:justify-start min-[87.5rem]:px-3 max-xl:shrink-0 max-xl:px-3 max-xl:py-2"
						:class="section === item.id ? 'bg-blue-50 text-blue-700' : 'text-ink-700 hover:bg-paper-2 hover:text-ink'"
						:aria-current="section === item.id ? 'step' : undefined"
						:title="item.label"
						@click="go(item.id)"
					>
						<component :is="item.icon" class="size-[18px] shrink-0" :class="section === item.id ? 'text-blue-600' : 'text-muted-2 group-hover:text-ink-700'" aria-hidden="true" />
						<span class="truncate xl:sr-only min-[87.5rem]:not-sr-only">{{ item.label }}</span>
						<span v-if="item.count" class="hidden min-[87.5rem]:inline ml-auto text-[12px] font-semibold tabular-nums" :class="section === item.id ? 'text-blue-600' : 'text-muted-2'">{{ item.count }}</span>
					</button>
				</template>
			</nav>

			<div ref="editor" class="@container bg-white min-h-0 overflow-y-auto xl:border-r border-line [scrollbar-width:thin]" :class="{ 'max-xl:hidden': showPreview }">
				<div class="px-8 pt-7 pb-10 max-[680px]:px-4">
					<slot name="notice" />
					<h2 class="font-display text-[22px] font-semibold text-ink">{{ current.label }}</h2>
					<p class="text-[13.5px] text-muted mt-1">{{ current.hint }}</p>

					<div class="mt-7">
						<template v-if="section === 'design'">
							<slot name="design" />
							<StylePicker v-model="style" :themes="themes" :cv="cv" :locale="locale" />
						</template>
						<div v-else-if="section === 'letter'">
							<label class="block text-[13px] font-semibold text-ink-700" for="cv-letter">Letter</label>
							<textarea id="cv-letter" v-model="letter" dir="auto" class="fld mt-1.5 !text-[14px] leading-7 min-h-[60vh] field-sizing-content" maxlength="20000"></textarea>
							<div class="flex items-center justify-between gap-3 mt-1.5 text-[12.5px] text-muted">
								<span>{{ words }} words</span>
								<slot name="letter-actions" />
							</div>
						</div>
						<CvEditor v-else v-model="cv" :section="section" />
					</div>

					<div class="mt-12 pt-5 border-t border-line-2 flex items-center justify-between gap-3">
						<button v-if="previous" type="button" class="inline-flex items-center gap-2 rounded-[10px] px-3 py-2 text-[13.5px] font-semibold text-muted hover:bg-paper-2 hover:text-ink" @click="go(previous.id)"><ArrowLeft class="size-4" aria-hidden="true" />{{ previous.label }}</button>
						<span v-else />
						<button v-if="next" type="button" class="inline-flex items-center gap-2 rounded-[10px] bg-paper-2 px-3.5 py-2 text-[13.5px] font-semibold text-ink hover:bg-blue-50 hover:text-blue-700" @click="go(next.id)">Next: {{ next.label }}<ArrowRight class="size-4" aria-hidden="true" /></button>
					</div>
				</div>
			</div>

			<div class="bg-paper-3 min-h-0 overflow-y-auto [scrollbar-width:thin]" :class="{ 'max-xl:hidden': !showPreview }">
				<div class="sticky top-0 z-10 bg-paper-3 px-8 max-[680px]:px-4 py-3 flex items-center justify-between gap-3">
					<div class="flex items-center gap-3">
						<div v-if="withLetter" class="flex rounded-[10px] bg-white p-1 ring-1 ring-line text-[13px] font-semibold">
							<button type="button" class="rounded-[7px] px-3 py-1.5 transition-colors" :class="previewing === 'cv' ? 'bg-navy-800 text-white' : 'text-muted hover:text-ink'" :aria-pressed="previewing === 'cv'" @click="previewing = 'cv'">CV</button>
							<button type="button" class="rounded-[7px] px-3 py-1.5 transition-colors" :class="previewing === 'letter' ? 'bg-navy-800 text-white' : 'text-muted hover:text-ink'" :aria-pressed="previewing === 'letter'" @click="previewing = 'letter'">Cover letter</button>
						</div>
						<p v-else class="text-[13px] font-semibold text-ink-700">Preview</p>
						<p class="text-[12.5px] text-muted whitespace-nowrap max-[87.5rem]:xl:hidden max-[520px]:hidden">{{ pages }} page{{ pages === 1 ? '' : 's' }} · {{ themes.papers.find((paper) => paper.id === style.paper)?.name }}</p>
					</div>
					<slot name="preview-actions" />
				</div>
				<div class="px-8 pb-12 pt-1 max-[680px]:px-4">
					<div class="mx-auto max-w-[820px]">
						<CvPreview :cv="cv" :style-options="style" :locale="locale" :letter="previewing === 'letter' ? letter : null" @pages="pages = $event" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
