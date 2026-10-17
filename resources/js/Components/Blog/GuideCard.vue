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
	<Link :href="post.path" class="guide-card focus-ring" :class="{ 'is-row': row }" :lang="post.locale">
		<div v-if="image" class="guide-media"><img :src="image.src" alt="" :width="image.width" :height="image.height" loading="lazy" decoding="async" /></div>
		<div class="guide-body">
			<span v-if="!image" class="icon-tile mb-5"><component :is="icon" aria-hidden="true" /></span>
			<p v-if="post.category" class="guide-category">{{ post.category }}<template v-if="post.draft"> · {{ fr ? 'Brouillon' : 'Draft' }}</template></p>
			<component :is="headingTag" class="guide-card-title">{{ post.title }}</component>
			<p v-if="excerpt && post.excerpt" class="guide-excerpt">{{ post.excerpt }}</p>
			<p class="guide-meta"><span><time v-if="date" :datetime="post.published_at">{{ date }}</time> · {{ post.read_time }} {{ fr ? 'min de lecture' : 'min read' }}</span><ArrowRight class="guide-arrow" aria-hidden="true" /></p>
		</div>
	</Link>
</template>
