<script setup>
import { h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FileUser } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits' }, () => page) });
defineProps({ kits: { type: Array, default: () => [] } });
const status = { queued: 'Being written', ready: 'Ready', failed: 'Failed' };
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="CV & letters" description="The CVs and cover letters written for the roles you picked. Open one to edit it, download it or use it to apply.">
			<template #actions><Link :href="route('app.opportunities.index')" class="cbtn cbtn-secondary h-11 !py-0">Browse opportunities</Link></template>
		</PageHeader>
		<div v-if="!kits.length" class="bg-white border border-line rounded-[18px] px-6 py-10 text-center">
			<FileUser class="w-9 h-9 text-blue-600 mx-auto" aria-hidden="true" />
			<p class="font-display text-lg font-semibold mt-3">Nothing here yet</p>
			<p class="text-sm text-muted mt-1 max-w-md mx-auto">Open any listing and press “Write my cover letter”. We write a CV for the role too. Add your resume on your profile page first for the best result.</p>
		</div>
		<ul v-else class="bg-white border border-line rounded-[18px] divide-y divide-line-2">
			<li v-for="kit in kits" :key="kit.id">
				<Link :href="route('kits.show', kit.id)" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-paper-2 transition-colors">
					<div class="min-w-0">
						<b class="block font-semibold truncate">{{ kit.role?.title || 'Role removed' }}</b>
						<span class="text-sm text-muted">{{ [kit.role?.company, kit.language, kit.updated_label].filter(Boolean).join(' · ') }}</span>
					</div>
					<span class="shrink-0 text-xs font-semibold rounded-full px-3 py-1" :class="kit.status === 'ready' ? 'bg-blue-50 text-blue-700' : kit.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-paper-3 text-ink-700'">{{ status[kit.status] }}</span>
				</Link>
			</li>
		</ul>
	</div>
</template>
