<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Briefcase, Building2, Compass, GraduationCap } from '@lucide/vue';
import { guideImage } from './guideImages';
const props = defineProps({
	post: { type: Object, required: true },
	row: { type: Boolean, default: false },
	excerpt: { type: Boolean, default: true },
	headingTag: { type: String, default: 'h3' },
});
const fr = computed(() => props.post.locale === 'fr');
const image = computed(() => guideImage(props.post));
const icon = computed(() => ({ students: GraduationCap, professionals: Briefcase, companies: Building2 }[props.post.audience] || Compass));
const date = computed(() => props.post.published_at ? new Date(props.post.published_at).toLocaleDateString(fr.value ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }) : '');
</script>
<template>
	<Link :href="post.path" class="group focus-ring flex min-w-0 overflow-hidden rounded-[18px] border border-line bg-white transition-[border-color] duration-180 hover:border-blue-200" :class="row ? 'flex-row max-[768px]:flex-col' : 'flex-col'" :lang="post.locale">
		<div v-if="image" class="overflow-hidden bg-blue-50" :class="row ? 'min-h-[150px] flex-[0_0_38%] max-[768px]:aspect-[12/5] max-[768px]:min-h-0' : 'aspect-[12/5]'"><img :src="image.src" alt="" :width="image.width" :height="image.height" loading="lazy" decoding="async" class="size-full object-cover" /></div>
		<div class="flex flex-1 flex-col px-[22px] pt-5 pb-[22px]">
			<span v-if="!image" class="mb-5 grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-brand"><component :is="icon" class="size-[22px]" aria-hidden="true" /></span>
			<p v-if="post.category" class="guide-category">{{ post.category }}<template v-if="post.draft"> · {{ fr ? 'Brouillon' : 'Draft' }}</template></p>
			<component :is="headingTag" class="mt-2 text-[20px] leading-[1.3] font-semibold tracking-[-.025em] transition-colors duration-180 group-hover:text-brand">{{ post.title }}</component>
			<p v-if="excerpt && post.excerpt" class="mt-2 line-clamp-2 text-[14.5px] leading-[1.7] text-subtle">{{ post.excerpt }}</p>
			<p class="mt-auto flex items-center justify-between gap-3 pt-4 text-[13px] text-subtle-2"><span><time v-if="date" :datetime="post.published_at">{{ date }}</time> · {{ post.read_time }} {{ fr ? 'min de lecture' : 'min read' }}</span><ArrowRight class="size-5 shrink-0 text-navy transition-[color,translate] duration-180 group-hover:translate-x-[3px] group-hover:text-brand motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" aria-hidden="true" /></p>
		</div>
	</Link>
</template>
