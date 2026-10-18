<script setup>
import { ArrowRight, Bookmark } from '@lucide/vue';
import { h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import RoleCard from '@/Components/Opportunity/RoleCard.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'saved' }, () => page),
});

defineProps({
	likes: { type: Array, default: () => [] },
});

const icons = {
	arrow: ArrowRight,
	bookmark: Bookmark,
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Saved roles" :description="`${likes.length} ${likes.length === 1 ? 'role' : 'roles'} saved for later. Apply before they close.`">
			<template #actions>
				<Link class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-all [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="route('opportunities.index')">
				Find more roles <component :is="icons.arrow" aria-hidden="true" />
				</Link>
			</template>
		</PageHeader>

		<div v-if="likes.length" class="flex flex-col gap-3.5">
			<RoleCard v-for="opportunity in likes" :key="opportunity.id" :opportunity="{ ...opportunity, liked: true }" />
		</div>

		<!-- empty state -->
		<div v-else class="flex flex-col items-center text-center py-[70px] px-5">
			<div class="w-[72px] h-[72px] rounded-[20px] bg-blue-50 text-blue-600 grid place-items-center mb-5 [&_svg]:w-[34px] [&_svg]:h-[34px]"><component :is="icons.bookmark" aria-hidden="true" /></div>
			<h3 class="font-display text-xl font-semibold mb-2">No saved roles yet</h3>
			<p class="text-muted text-[15px] max-w-[340px] mb-[22px]">Tap the bookmark on any internship or job to save it here and apply when you're ready.</p>
			<Link class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('opportunities.index')">Browse opportunities</Link>
		</div>
	</div>
</template>
