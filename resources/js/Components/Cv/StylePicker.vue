<script setup>
import { Check } from '@lucide/vue';
import CvPreview from '@/Components/Cv/CvPreview.vue';

// Theme, accent, type and paper for a CV, with live thumbnails of the candidate's own CV.
const style = defineModel({ type: Object, required: true });
defineProps({
	themes: { type: Object, required: true },
	cv: { type: Object, required: true },
	locale: { type: String, default: 'en' },
});
const set = (key, value) => { style.value = { ...style.value, [key]: value }; };
</script>

<template>
	<div class="grid gap-5">
		<fieldset>
			<legend class="text-sm font-semibold mb-2">Theme</legend>
			<div class="grid grid-cols-3 gap-3 max-[720px]:grid-cols-2">
				<label v-for="theme in themes.templates" :key="theme.id" class="relative cursor-pointer rounded-[12px] border p-2 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.template === theme.id ? 'border-blue-600 bg-blue-50' : 'border-line bg-white hover:border-slate-300'">
					<input type="radio" class="sr-only" name="cv-template" :value="theme.id" :checked="style.template === theme.id" @change="set('template', theme.id)">
					<CvPreview :cv="cv" :style-options="{ ...style, template: theme.id }" :locale="locale" thumbnail />
					<span v-if="style.template === theme.id" class="absolute top-3 right-3 grid place-items-center w-6 h-6 rounded-full bg-blue-600 text-white"><Check class="w-3.5 h-3.5" aria-hidden="true" /></span>
					<span class="block mt-2 text-sm font-semibold">{{ theme.name }} <span class="font-normal text-muted">· {{ theme.family }}</span></span>
					<span class="block text-xs text-muted leading-5">{{ theme.desc }}</span>
					<span v-if="!theme.ats_safe" class="inline-block mt-1 rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800">Less ATS-friendly</span>
				</label>
			</div>
		</fieldset>

		<div class="flex flex-wrap gap-x-8 gap-y-4">
			<fieldset>
				<legend class="text-sm font-semibold mb-2">Accent</legend>
				<div class="flex gap-2">
					<label v-for="accent in themes.accents" :key="accent.id" class="cursor-pointer rounded-full p-0.5 border-2 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.accent === accent.id ? 'border-ink' : 'border-transparent'" :title="accent.name">
						<input type="radio" class="sr-only" name="cv-accent" :value="accent.id" :checked="style.accent === accent.id" @change="set('accent', accent.id)">
						<span class="block w-7 h-7 rounded-full" :style="{ background: accent.value }" aria-hidden="true" />
						<span class="sr-only">{{ accent.name }}</span>
					</label>
				</div>
			</fieldset>
			<fieldset>
				<legend class="text-sm font-semibold mb-2">Type</legend>
				<div class="flex rounded-lg bg-paper-2 p-1 text-sm font-semibold">
					<label v-for="font in themes.fonts" :key="font.id" class="cursor-pointer rounded-md px-3 py-1.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.font === font.id ? 'bg-white text-navy' : 'text-slate-600'">
						<input type="radio" class="sr-only" name="cv-font" :value="font.id" :checked="style.font === font.id" @change="set('font', font.id)">{{ font.name }}
					</label>
				</div>
			</fieldset>
			<fieldset>
				<legend class="text-sm font-semibold mb-2">Paper</legend>
				<div class="flex rounded-lg bg-paper-2 p-1 text-sm font-semibold">
					<label v-for="paper in themes.papers" :key="paper.id" class="cursor-pointer rounded-md px-3 py-1.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.paper === paper.id ? 'bg-white text-navy' : 'text-slate-600'">
						<input type="radio" class="sr-only" name="cv-paper" :value="paper.id" :checked="style.paper === paper.id" @change="set('paper', paper.id)">{{ paper.name }}
					</label>
				</div>
			</fieldset>
		</div>
	</div>
</template>
