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
	<Link :href="post.path" class="group focus-ring flex min-w-0 rounded-card" :class="row ? 'flex-row gap-7 max-[768px]:flex-col max-[768px]:gap-0' : 'flex-col'" :lang="post.locale">
		<div v-if="image" class="relative overflow-hidden rounded-card bg-blue-50" :class="row ? 'min-h-[180px] flex-[0_0_40%] max-[768px]:aspect-[12/7] max-[768px]:min-h-0' : 'aspect-[12/7]'">
			<img :src="image.src" alt="" :width="image.width" :height="image.height" loading="lazy" decoding="async" class="size-full object-cover" />
			<span v-if="post.category" class="absolute top-5 left-5 rounded-card bg-white px-4 py-2 text-[12.5px] leading-5 tracking-caps text-navy uppercase">{{ post.category }}<template v-if="post.draft"> · {{ fr ? 'Brouillon' : 'Draft' }}</template></span>
		</div>
		<div class="flex flex-1 flex-col" :class="row ? 'py-2 max-[768px]:pt-6' : 'pt-6'">
			<template v-if="!image">
				<span class="icon-tile mb-5"><component :is="icon" class="size-[22px]" aria-hidden="true" /></span>
				<p v-if="post.category" class="guide-category mb-2">{{ post.category }}<template v-if="post.draft"> · {{ fr ? 'Brouillon' : 'Draft' }}</template></p>
			</template>
			<div class="flex items-start justify-between gap-5">
				<component :is="headingTag" class="text-[20px] leading-[1.4] text-navy transition-colors duration-180 group-hover:text-brand">{{ post.title }}</component>
				<time v-if="date" :datetime="post.published_at" class="shrink-0 pt-1 text-[14px] text-subtle-2">{{ date }}</time>
			</div>
			<p v-if="excerpt && post.excerpt" class="mt-3 line-clamp-2 text-[15px] leading-[1.7] text-subtle">{{ post.excerpt }}</p>
			<p class="mt-auto flex items-center gap-2 pt-5 text-[13px] font-medium tracking-caps text-brand uppercase">{{ fr ? 'Lire la suite' : 'Read more' }}<span class="font-normal tracking-normal normal-case text-subtle-2">· {{ post.read_time }} {{ fr ? 'min de lecture' : 'min read' }}</span><ArrowRight class="size-4 shrink-0 transition-transform duration-180 group-hover:translate-x-[3px] motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" aria-hidden="true" /></p>
		</div>
	</Link>
</template>
