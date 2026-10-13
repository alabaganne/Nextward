<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps({
	meta: { type: Object, required: true },
});

// Laravel's meta.links: [{url, label, active}] with «/» on the ends.
const links = computed(() =>
	(props.meta?.links || []).map((link, index, all) => ({
		...link,
		label: index === 0 ? 'Previous page' : index === all.length - 1 ? 'Next page' : link.label,
		icon: index === 0 ? ChevronLeft : index === all.length - 1 ? ChevronRight : null,
	}))
);
</script>

<template>
	<div v-if="meta && meta.last_page > 1" class="flex justify-center items-center gap-2 mt-[30px] [&_a]:min-w-[40px] [&_a]:h-10 [&_a]:px-2.5 [&_a]:rounded-[10px] [&_a]:border [&_a]:border-line [&_a]:grid [&_a]:place-items-center [&_a]:font-semibold [&_a]:text-sm [&_a]:text-ink-700 [&_a]:bg-white [&_span]:min-w-[40px] [&_span]:h-10 [&_span]:px-2.5 [&_span]:rounded-[10px] [&_span]:border [&_span]:border-line [&_span]:grid [&_span]:place-items-center [&_span]:font-semibold [&_span]:text-sm [&_span]:text-muted-2 [&_span]:bg-white">
		<template v-for="(link, index) in links" :key="index">
			<Link
				v-if="link.url"
				:href="link.url"
				:class="{ '!bg-blue-600 !text-white !border-blue-600': link.active }"
				:aria-label="link.icon ? link.label : `Page ${link.label}`"
				:aria-current="link.active ? 'page' : undefined"
				preserve-scroll
			><component v-if="link.icon" :is="link.icon" :size="18" aria-hidden="true" /><template v-else>{{ link.label }}</template></Link>
			<span v-else :aria-label="link.icon ? link.label : undefined" aria-disabled="true"><component v-if="link.icon" :is="link.icon" :size="18" aria-hidden="true" /><template v-else>{{ link.label }}</template></span>
		</template>
	</div>
</template>
