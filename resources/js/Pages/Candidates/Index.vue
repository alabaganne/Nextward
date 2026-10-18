<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PublicReview from '@/Components/Candidate/PublicReview.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'candidates' }, () => page),
});

const props = defineProps({
	candidates: { type: Object, required: true },
	filter: { type: String, default: null },
	pending_count: { type: Number, default: 0 },
});

const filters = computed(() => [
	{ key: null, label: 'All' },
	{ key: 'pending', label: `Waiting for review${props.pending_count ? ` (${props.pending_count})` : ''}` },
	{ key: 'approved', label: 'Public' },
]);

const initialsFor = (name) =>
	(name || '')
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase();

const meta = computed(() => props.candidates.meta ?? props.candidates);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="mb-6 flex items-end justify-between gap-4 flex-wrap">
			<div>
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">Candidates</h1>
			<p class="text-muted text-[15px] mt-1.5">Every candidate profile on Nextward. Approve the ones that asked to be shown on the public site.</p>
			</div>
			<Link class="cbtn bg-blue-600 text-white hover:bg-blue-700" :href="route('candidates.create')">Add candidate</Link>
		</div>

		<nav class="flex flex-wrap gap-2 mb-5" aria-label="Filter candidates">
			<Link
				v-for="item in filters"
				:key="item.key || 'all'"
				class="chip"
				:class="{ 'is-active': filter === item.key }"
				:href="route('candidates.index', item.key ? { public: item.key } : {})"
				:aria-current="filter === item.key ? 'page' : undefined"
				preserve-scroll
			>{{ item.label }}</Link>
		</nav>

		<div class="grid grid-cols-3 gap-[18px] max-[1180px]:grid-cols-2 max-[760px]:grid-cols-1">
			<article
				v-for="candidate in candidates.data"
				:key="candidate.id"
				class="bg-white border border-line rounded-[18px] flex flex-col transition-all hover:border-blue-300 hover:-translate-y-[3px]"
			>
				<div class="px-6 py-8 flex flex-col items-center text-center">
					<div class="w-[72px] h-[72px] rounded-[20px] bg-gradient-to-br from-blue-300 to-blue-700 grid place-items-center text-white font-bold font-display text-[26px]">{{ initialsFor(candidate.name) }}</div>
					<h3 class="font-display text-[18px] font-semibold mt-4 leading-[1.2]">{{ candidate.name }}</h3>
					<span class="text-[13px] text-muted mt-1">{{ candidate.headline || candidate.field?.name || 'Candidate' }}</span>
				</div>
				<div v-if="candidate.public_status" class="px-6 pb-4">
					<PublicReview :candidate="candidate" compact />
				</div>
				<div class="flex gap-2.5 px-6 pb-6 mt-auto">
					<Link class="flex-1 justify-center h-[42px] inline-flex items-center font-semibold text-[15px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('candidates.show', candidate.id)">Profile</Link>
				</div>
			</article>
		</div>

		<div v-if="!candidates.data.length" class="text-center py-[60px] text-muted">{{ filter === 'pending' ? 'No profiles are waiting for review.' : filter === 'approved' ? 'No public profiles yet.' : 'No candidates found.' }}</div>

		<Pagination :meta="meta" />
	</div>
</template>
