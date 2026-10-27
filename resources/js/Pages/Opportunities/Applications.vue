<script setup>
import { ChevronRight } from '@lucide/vue';
import { h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import Pagination from '@/Components/Ui/Pagination.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'opportunities' }, () => page),
});

defineProps({
	applications: { type: Object, required: true },
	opportunity: { type: Object, required: true },
});

const icons = {
	chevron: ChevronRight,
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('app.opportunities.index')">Roles</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<Link class="hover:text-blue-700" :href="route('app.opportunities.show', opportunity.id)">{{ opportunity.title }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">Applicants</span>
		</div>

		<div class="mb-[22px]">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">Applicants</h1>
			<p class="text-muted text-[15px] mt-1.5">Everyone who applied to <b class="text-ink-700 font-medium">{{ opportunity.title }}</b>.</p>
		</div>

		<div class="bg-white border border-transparent rounded-card overflow-hidden">
			<div class="grid grid-cols-[1fr_150px_130px_40px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-medium [&_span:last-child]:text-right">
				<span>Applicant</span><span>Applied</span><span>Status</span><span></span>
			</div>
			<Link
				v-for="(application, index) in applications.data"
				:key="application.id"
				class="grid grid-cols-[1fr_150px_130px_40px] gap-4 items-center px-[22px] py-4 cursor-pointer transition-colors hover:bg-paper-2 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
				:class="{ 'border-b border-line-2': index !== applications.data.length - 1 }"
				:href="route('applications.show', application.id)"
			>
				<div class="flex items-center gap-3.5 min-w-0">
					<CompanyLogo class="w-[46px] h-[46px] rounded-card text-[15px]" :name="application.candidate?.name" />
					<div>
						<b class="text-[15px] font-medium block">{{ application.candidate?.name }}</b>
						<span class="text-[13px] text-muted">{{ application.candidate?.email }}</span>
					</div>
				</div>
				<div class="text-[13px] text-muted-2 max-[760px]:hidden">{{ application.created_at }}</div>
				<div><StatusPill :status="application.status" /></div>
				<div class="w-[34px] h-[34px] rounded-[9px] border border-line grid place-items-center text-muted-2 justify-self-end max-[760px]:hidden [&_svg]:w-4 [&_svg]:h-4"><component :is="icons.chevron" aria-hidden="true" /></div>
			</Link>

			<div v-if="!applications.data.length" class="px-[22px] py-8 text-[14px] text-muted">No applications yet.</div>
		</div>

		<Pagination :meta="applications.meta" />
	</div>
</template>
