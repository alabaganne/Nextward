<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

// Shows the CV (or letter) exactly as the PDF prints it: the server renders the HTML, scaled here to fit.
// Each printed page is drawn as its own sheet, cut where the PDF breaks.
const props = defineProps({
	cv: { type: Object, required: true },
	styleOptions: { type: Object, required: true },
	locale: { type: String, default: 'en' },
	letter: { type: String, default: null },
	thumbnail: { type: Boolean, default: false },
});
const emit = defineEmits(['pages']);

const PAGE = { a4: { width: 794, height: 1123 }, letter: { width: 816, height: 1056 } };
const box = ref(null);
const frame = ref(null);
const html = ref('');
const scale = ref(0.5);
const height = ref(1123);
const breaks = ref([]);
const topPad = ref(0);
const side = ref(null);
const failed = ref(false);
const page = () => PAGE[props.styleOptions.paper] || PAGE.a4;

let timer = null;
let request = 0;
const load = () => {
	clearTimeout(timer);
	timer = setTimeout(async () => {
		const id = ++request;
		try {
			const { data } = await axios.post(route('cv.preview'), { cv: props.cv, style: props.styleOptions, locale: props.locale, letter: props.letter ?? undefined }, { responseType: 'text' });
			if (id === request) { html.value = data; failed.value = false; }
		} catch {
			if (id === request) failed.value = true;
		}
	}, !html.value ? 0 : props.thumbnail ? 1500 : 450);
};
watch(() => [props.cv, props.styleOptions, props.locale, props.letter], load, { deep: true, immediate: true });

const fit = () => {
	if (box.value) scale.value = box.value.clientWidth / page().width;
};

// Replays the print page breaks (from Resume Studio): blocks that can't split move to the next page.
const UNSPLIT = '.d-head, .d-sec-title, .d-entry-head, .d-bullets li, .d-skill-row, .d-summary, .d-note, .d-side-skill, .d-side-line';
const KEEP = '.d-head, .d-sec-title, .d-entry-head';
const measure = () => {
	const doc = frame.value?.contentDocument;
	const tpl = doc?.querySelector('.tpl');
	if (!tpl) return;
	height.value = Math.max(tpl.offsetHeight, page().height);
	if (props.thumbnail) return;
	const pageHeight = page().height;
	const flowBox = tpl.querySelector('.tpl-atlas > .d-body, .tpl-soren > .d-main') || tpl;
	const styles = doc.defaultView.getComputedStyle(flowBox);
	const top = parseFloat(styles.paddingTop) || 0;
	const bottom = parseFloat(styles.paddingBottom) || 0;
	const flow = tpl.classList.contains('tpl-soren') ? flowBox : tpl;
	const origin = tpl.getBoundingClientRect().top;
	const blocks = Array.from(flow.querySelectorAll(UNSPLIT)).map((el) => {
		const rect = el.getBoundingClientRect();
		return { top: rect.top - origin, bottom: rect.bottom - origin, keep: el.matches(KEEP) };
	});
	const found = [];
	let shift = 0;
	let current = 0;
	blocks.forEach((block, index) => {
		if (block.bottom + shift <= (current + 1) * pageHeight - bottom) return;
		let first = index;
		while (first > 0 && blocks[first - 1].keep && blocks[first - 1].top + shift >= current * pageHeight) first--;
		const start = blocks[first].top;
		if (found.length && start <= found[found.length - 1]) return;
		current++;
		shift = current * pageHeight + top - start;
		found.push(start);
	});
	breaks.value = found;
	topPad.value = top;
	// The two-column theme prints its side column down every page.
	const sideColumn = tpl.classList.contains('tpl-soren') ? tpl.querySelector('.d-side') : null;
	side.value = sideColumn ? { width: sideColumn.offsetWidth, color: doc.defaultView.getComputedStyle(sideColumn).backgroundColor } : null;
};
const loaded = async () => {
	await nextTick();
	measure();
	// Fonts can change the height once they load.
	frame.value?.contentDocument?.fonts?.ready.then(measure);
};

// Each sheet shows the slice of the document between two breaks; later pages start below the top margin.
const sheets = computed(() => {
	const pageHeight = page().height;
	return [0, ...breaks.value].map((start, index) => {
		const offset = index === 0 ? 0 : topPad.value;
		const end = breaks.value[index] ?? height.value;
		return { start, offset, height: Math.max(0, Math.min(end - start, pageHeight - offset)) };
	});
});
watch(() => sheets.value.length, (count) => emit('pages', count), { immediate: true });
const sheetBackground = computed(() => side.value
	? `linear-gradient(to right, ${side.value.color} ${side.value.width * scale.value}px, #fff ${side.value.width * scale.value}px)`
	: '#fff');
const setFrame = (index) => (el) => { if (index === 0) frame.value = el; };

let observer = null;
onMounted(() => {
	fit();
	observer = new ResizeObserver(fit);
	box.value && observer.observe(box.value);
});
onBeforeUnmount(() => { observer?.disconnect(); clearTimeout(timer); });
</script>

<template>
	<div v-if="thumbnail" ref="box" class="relative w-full overflow-hidden bg-white pointer-events-none" :style="{ height: `${page().height * 0.62 * scale}px` }">
		<iframe
			v-if="html"
			ref="frame"
			:srcdoc="html"
			sandbox="allow-same-origin"
			title="Preview"
			tabindex="-1"
			class="absolute top-0 left-0 origin-top-left border-0"
			:style="{ width: `${page().width}px`, height: `${height}px`, transform: `scale(${scale})` }"
			@load="loaded"
		/>
	</div>

	<div v-else ref="box" class="relative w-full">
		<div class="grid gap-6">
			<div
				v-for="(sheet, index) in sheets"
				:key="index"
				class="relative overflow-hidden rounded-[4px] ring-1 ring-[#dde3ec]"
				:style="{ height: `${page().height * scale}px`, background: sheetBackground }"
				:aria-label="sheets.length > 1 ? `Page ${index + 1} of ${sheets.length}` : null"
			>
				<div class="absolute inset-x-0 overflow-hidden" :style="{ top: `${sheet.offset * scale}px`, height: `${sheet.height * scale}px` }">
					<iframe
						v-if="html"
						:ref="setFrame(index)"
						:srcdoc="html"
						sandbox="allow-same-origin"
						:title="index === 0 ? 'Preview' : `Preview, page ${index + 1}`"
						:tabindex="index === 0 ? 0 : -1"
						:aria-hidden="index === 0 ? null : 'true'"
						class="absolute left-0 origin-top-left border-0 pointer-events-none"
						:style="{ top: `${-sheet.start * scale}px`, width: `${page().width}px`, height: `${height}px`, transform: `scale(${scale})` }"
						@load="index === 0 && loaded()"
					/>
				</div>
			</div>
		</div>
		<p v-if="failed" class="absolute inset-x-0 top-4 text-center text-sm text-red-700" role="alert">The preview could not load. Your changes are still here.</p>
	</div>
</template>
