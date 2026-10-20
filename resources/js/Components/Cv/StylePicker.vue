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
const legend = 'block text-[13px] font-semibold text-ink-700 mb-2.5';
</script>

<template>
	<div class="grid gap-8">
		<fieldset>
			<legend :class="legend">Theme</legend>
			<div class="grid grid-cols-2 gap-3 @2xl:grid-cols-3">
				<label v-for="theme in themes.templates" :key="theme.id" class="relative cursor-pointer rounded-[14px] border-[1.5px] p-2 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.template === theme.id ? 'border-blue-600 bg-blue-50' : 'border-line bg-white hover:border-slate-300'">
					<input type="radio" class="sr-only" name="cv-template" :value="theme.id" :checked="style.template === theme.id" @change="set('template', theme.id)">
					<div class="rounded-[8px] overflow-hidden border border-line-2">
						<CvPreview :cv="cv" :style-options="{ ...style, template: theme.id }" :locale="locale" thumbnail />
					</div>
					<span v-if="style.template === theme.id" class="absolute top-3.5 right-3.5 grid place-items-center size-6 rounded-full bg-blue-600 text-white"><Check class="size-3.5" aria-hidden="true" /></span>
					<span class="block px-1 pt-2.5 text-[13.5px] font-semibold text-ink">{{ theme.name }} <span class="font-normal text-muted">· {{ theme.family }}</span></span>
					<span class="block px-1 pb-1 text-[12px] text-muted leading-5">{{ theme.desc }}</span>
					<span v-if="!theme.ats_safe" class="inline-block mx-1 mb-1 rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800">Less ATS-friendly</span>
				</label>
			</div>
		</fieldset>

		<fieldset>
			<legend :class="legend">Accent colour</legend>
			<div class="flex flex-wrap gap-2.5">
				<label v-for="accent in themes.accents" :key="accent.id" class="cursor-pointer rounded-full p-1 border-2 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.accent === accent.id ? 'border-ink' : 'border-transparent hover:border-line'" :title="accent.name">
					<input type="radio" class="sr-only" name="cv-accent" :value="accent.id" :checked="style.accent === accent.id" @change="set('accent', accent.id)">
					<span class="block size-8 rounded-full" :style="{ background: accent.value }" aria-hidden="true" />
					<span class="sr-only">{{ accent.name }}</span>
				</label>
			</div>
		</fieldset>

		<div class="grid gap-8">
			<fieldset>
				<legend :class="legend">Type</legend>
				<div class="grid grid-flow-col auto-cols-fr rounded-[11px] bg-paper-2 p-1 text-[13.5px] font-semibold">
					<label v-for="font in themes.fonts" :key="font.id" class="cursor-pointer rounded-lg px-3 py-2 text-center transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.font === font.id ? 'bg-white text-navy ring-1 ring-line' : 'text-slate-600 hover:text-ink'">
						<input type="radio" class="sr-only" name="cv-font" :value="font.id" :checked="style.font === font.id" @change="set('font', font.id)">{{ font.name }}
					</label>
				</div>
			</fieldset>
			<fieldset>
				<legend :class="legend">Paper</legend>
				<div class="grid grid-flow-col auto-cols-fr rounded-[11px] bg-paper-2 p-1 text-[13.5px] font-semibold">
					<label v-for="paper in themes.papers" :key="paper.id" class="cursor-pointer rounded-lg px-3 py-2 text-center transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="style.paper === paper.id ? 'bg-white text-navy ring-1 ring-line' : 'text-slate-600 hover:text-ink'">
						<input type="radio" class="sr-only" name="cv-paper" :value="paper.id" :checked="style.paper === paper.id" @change="set('paper', paper.id)">{{ paper.name }}
					</label>
				</div>
			</fieldset>
		</div>
	</div>
</template>
