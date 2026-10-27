<script setup>
import { ArrowRight, Bookmark } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import RoleCard from '@/Components/Opportunity/RoleCard.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'saved' }, () => page),
});

const props = defineProps({
	likes: { type: Object, default: () => ({ data: [], meta: {} }) },
});

const total = computed(() => props.likes.meta?.total || 0);

const icons = {
	arrow: ArrowRight,
	bookmark: Bookmark,
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Saved roles" :description="`${total} ${total === 1 ? 'role' : 'roles'} saved for later. Apply before they close.`">
			<template #actions>
				<Link class="cta cta-secondary [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="route('app.opportunities.index')">
				Find more roles <component :is="icons.arrow" aria-hidden="true" />
				</Link>
			</template>
		</PageHeader>

		<section v-if="likes.data?.length" class="@container" aria-label="Saved roles">
			<div class="grid grid-cols-1 @3xl:grid-cols-2 gap-5">
				<RoleCard v-for="opportunity in likes.data" :key="opportunity.id" :opportunity="{ ...opportunity, liked: true }" />
			</div>
			<Pagination :meta="likes.meta" />
		</section>

		<!-- empty state -->
		<div v-else class="flex flex-col items-center text-center py-[70px] px-5">
			<div class="w-[72px] h-[72px] rounded-card bg-blue-50 text-blue-600 grid place-items-center mb-5 [&_svg]:w-[34px] [&_svg]:h-[34px]"><component :is="icons.bookmark" aria-hidden="true" /></div>
			<h3 class="mb-2 text-[20px] leading-[1.35] text-navy">No saved roles yet</h3>
			<p class="text-muted text-[15px] max-w-[340px] mb-[22px]">Tap the bookmark on any internship or job to save it here and apply when you're ready.</p>
			<Link class="cta cta-primary" :href="route('app.opportunities.index')">Browse roles</Link>
		</div>
	</div>
</template>
