<script setup>
import { ArrowUpRight, ChevronRight, GraduationCap, Mail, MapPin, Phone } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PublicReview from '@/Components/Candidate/PublicReview.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'students' }, () => page),
});

const props = defineProps({
	student: { type: Object, required: true },
});

const icons = {
	chevron: ChevronRight,
	pin: MapPin,
	grad: GraduationCap,
	mail: Mail,
	phone: Phone,
	external: ArrowUpRight,
};

const page = usePage();
const isAdmin = computed(() => !!page.props.auth?.user?.is_admin);

const initials = computed(() =>
	(props.student.name || '')
		.split(/\s+/)
		.map((word) => word[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1120px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="isAdmin ? route('students.index') : route('applications.index')">{{ isAdmin ? 'Candidates' : 'Applications' }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ student.name }}</span>
		</div>

		<!-- IDENTITY -->
		<section class="bg-white border border-line rounded-[18px] overflow-hidden mb-[18px]">
			<div class="h-[104px] relative bg-gradient-to-r from-navy-800 to-blue-700 after:content-[''] after:absolute after:inset-0 after:[background-image:linear-gradient(rgba(255,255,255,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.06)_1px,transparent_1px)] after:[background-size:34px_34px] after:[mask-image:radial-gradient(420px_200px_at_78%_-30%,#000,transparent_75%)] after:[-webkit-mask-image:radial-gradient(420px_200px_at_78%_-30%,#000,transparent_75%)]"></div>
			<div class="px-[26px] pb-6 flex gap-[22px] items-end max-[680px]:flex-col max-[680px]:items-start max-[680px]:gap-3.5">
				<div class="w-[108px] h-[108px] rounded-[24px] bg-gradient-to-br from-blue-300 to-blue-700 grid place-items-center text-white font-bold font-display text-[40px] -mt-[46px] border-4 border-white shrink-0">{{ initials }}</div>
				<div class="flex-1 min-w-0 pt-3.5">
					<h2 class="font-display text-[25px] font-semibold">{{ student.name }}</h2>
					<div class="text-[15px] text-ink-700 font-medium mt-[3px]">{{ student.headline || student.field?.name || 'Candidate' }}</div>
					<div class="flex flex-wrap gap-4 mt-3 [&_span]:flex [&_span]:items-center [&_span]:gap-1.5 [&_span]:text-[13.5px] [&_span]:text-muted [&_span]:font-medium [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:text-muted-2">
						<span v-if="student.city?.name"><component :is="icons.pin" aria-hidden="true" />{{ student.city.name }}</span>
						<span v-if="student.field?.name"><component :is="icons.grad" aria-hidden="true" />{{ student.field.name }}</span>
					</div>
				</div>
				<div class="flex gap-2.5 pt-3.5">
					<Link v-if="isAdmin" class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[18px] h-10 rounded-[12px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-all" :href="route('students.edit', student.id)">Edit</Link>
				</div>
			</div>
		</section>

		<section v-if="isAdmin && student.public_status" class="bg-white border border-line rounded-[18px] mb-[18px] px-[22px] py-[18px]">
			<h3 class="font-display text-[16.5px] font-semibold">Public profile</h3>
			<p class="text-[13px] text-muted mt-[3px] mb-3.5">This candidate asked to show their profile on the public site. Their email, phone and resume are never shown there.</p>
			<PublicReview :student="student" />
		</section>

		<div class="grid grid-cols-[1.65fr_1fr] gap-[18px] items-start max-[980px]:grid-cols-1">
			<!-- about -->
			<section class="bg-white border border-line rounded-[18px]">
				<div class="px-[22px] py-[18px] border-b border-line-2"><h3 class="font-display text-[16.5px] font-semibold">About</h3></div>
				<div class="px-[22px] py-5">
					<p v-if="student.about" class="text-[14.5px] text-ink-700 leading-[1.7] whitespace-pre-line">{{ student.about }}</p>
					<p v-else class="text-[14.5px] text-muted leading-[1.7]">This candidate hasn't written a bio yet.</p>
				</div>
			</section>

			<!-- contact -->
			<section class="bg-white border border-line rounded-[18px]">
				<div class="px-[22px] py-[18px] border-b border-line-2"><h3 class="font-display text-[16.5px] font-semibold">Contact</h3></div>
				<div class="px-[22px] py-3 [&_.ci]:flex [&_.ci]:items-center [&_.ci]:gap-3 [&_.ci]:py-2.5 [&_.ci]:text-sm [&_.ci]:text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
					<div class="ci"><component :is="icons.mail" aria-hidden="true" />{{ student.email }}</div>
					<div v-if="student.phone_number" class="ci"><component :is="icons.phone" aria-hidden="true" />{{ student.phone_number }}</div>
					<a v-if="student.linkedin_profile_url" class="ci !text-blue-700 font-semibold" :href="student.linkedin_profile_url" target="_blank" rel="noopener"><component :is="icons.external" aria-hidden="true" />LinkedIn profile</a>
				</div>
			</section>
		</div>
	</div>
</template>
