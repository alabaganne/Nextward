<script setup>
import { ArrowRight, Bookmark, Briefcase, Clock3, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';

const props = defineProps({
	internship: { type: Object, required: true },
});

const icons = {
	pin: MapPin,
	clock: Clock3,
	briefcase: Briefcase,
	save: Bookmark,
	arrow: ArrowRight,
};

const page = usePage();
const isStudent = computed(() => page.props.auth?.user?.userable_type === 'student');

const WORK_TYPES = { remote: 'Remote', hybrid: 'Hybrid', onsite: 'On-site' };
const payLabel = (pay) => `$${pay.amount}/${pay.unit === 'hour' ? 'hr' : 'mo'}`;

const toggleSave = () => {
	router.post(route('likes.store', props.internship.id), {}, { preserveScroll: true });
};
</script>

<template>
	<article class="bg-white border border-line rounded-[18px] p-[22px] flex flex-col transition-colors hover:border-blue-300">
		<div class="flex gap-[18px] items-start max-[560px]:flex-col">
			<CompanyLogo class="w-[54px] h-[54px] rounded-[14px] text-[19px]" :name="internship.company.name" />
			<div class="flex-1 min-w-0">
				<div class="flex items-start justify-between gap-3">
					<div>
						<h3 class="font-display text-[18.5px] font-semibold mb-[3px]">
							<Link class="hover:text-blue-700" :href="route('internships.show', internship.id)">{{ internship.title }}</Link>
						</h3>
						<p class="text-sm text-muted mb-3.5"><b class="text-ink-700 font-semibold">{{ internship.company.name }}</b> · {{ internship.city.name }}</p>
					</div>
				</div>
				<div class="flex flex-wrap gap-[18px] mb-3.5 [&_span]:flex [&_span]:items-center [&_span]:gap-1.5 [&_span]:text-[13.5px] [&_span]:text-muted [&_span]:font-medium [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:text-muted-2">
					<span v-if="internship.work_type"><component :is="icons.pin" aria-hidden="true" />{{ WORK_TYPES[internship.work_type] || internship.work_type }}</span>
					<span v-if="internship.duration_weeks"><component :is="icons.clock" aria-hidden="true" />{{ internship.duration_weeks }} weeks</span>
					<span><component :is="icons.briefcase" aria-hidden="true" />{{ internship.term || internship.field.name }}</span>
				</div>
				<div v-if="internship.skills?.length" class="flex flex-wrap gap-[7px]">
					<span v-for="skill in internship.skills" :key="skill" class="inline-flex items-center px-[11px] py-[5px] rounded-lg text-xs font-semibold bg-paper-3 text-ink-700">{{ skill }}</span>
				</div>
			</div>
			<div class="flex flex-col items-end gap-3 shrink-0">
				<span v-if="internship.application" class="text-[11px] font-bold text-[#047857] bg-[#dcfce7] px-[9px] py-1 rounded-[7px] tracking-[.02em]">APPLIED</span>
				<span v-else-if="!internship.is_open" class="text-[11px] font-bold text-muted bg-paper-3 px-[9px] py-1 rounded-[7px] tracking-[.02em]">CLOSED</span>
				<span v-else-if="internship.is_new" class="text-[11px] font-bold text-blue-700 bg-blue-50 px-[9px] py-1 rounded-[7px] tracking-[.03em]">NEW</span>
				<button
					v-if="isStudent"
					class="w-[38px] h-[38px] rounded-[10px] border grid place-items-center cursor-pointer transition-all [&_svg]:w-[18px] [&_svg]:h-[18px]"
					:class="internship.liked
						? 'border-blue-200 bg-blue-50 text-blue-600 hover:bg-[#fef2f2] hover:border-[#fecaca] hover:text-[#dc2626]'
						: 'border-line text-muted-2 hover:border-blue-300 hover:text-blue-600'"
					type="button"
					:title="internship.liked ? 'Remove from saved' : 'Save role'"
					@click="toggleSave"
				><component :is="icons.save" aria-hidden="true" /></button>
				<div v-if="internship.pay" class="text-right">
					<b class="font-display text-[17px] block">{{ payLabel(internship.pay) }}</b>
					<span class="text-xs text-muted">Paid</span>
				</div>
			</div>
		</div>
		<div class="flex items-center justify-between w-full mt-[18px] border-t border-line-2">
			<span class="text-[13px] text-muted-2 pt-[15px]">Posted {{ internship.created_at }}<template v-if="internship.applications_count !== undefined"> · {{ internship.applications_count }} applicants</template></span>
			<Link class="pt-[15px] text-blue-700 font-semibold inline-flex items-center gap-[7px] hover:gap-[11px] transition-all [&_svg]:w-4 [&_svg]:h-4" :href="route('internships.show', internship.id)">
				View role <component :is="icons.arrow" aria-hidden="true" />
			</Link>
		</div>
	</article>
</template>
