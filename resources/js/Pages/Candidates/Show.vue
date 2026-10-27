<script setup>
import { ArrowUpRight, ChevronRight, GraduationCap, Mail, MapPin, Phone } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import ProfileBanner from '@/Components/Candidate/ProfileBanner.vue';
import PublicReview from '@/Components/Candidate/PublicReview.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'candidates' }, () => page),
});

const props = defineProps({
	candidate: { type: Object, required: true },
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

</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1120px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="isAdmin ? route('candidates.index') : route('applications.index')">{{ isAdmin ? 'Candidates' : 'Applications' }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ candidate.name }}</span>
		</div>

		<ProfileBanner class="mb-6" :name="candidate.name" :headline="candidate.headline || candidate.field?.name || 'Candidate'" :items="[{ icon: icons.pin, label: candidate.city?.name }, { icon: icons.grad, label: candidate.field?.name }]">
			<Link v-if="isAdmin" class="cta cta-primary cta-sm" :href="route('candidates.edit', candidate.id)">Edit candidate</Link>
		</ProfileBanner>

		<section v-if="isAdmin && candidate.public_status" class="bg-white border border-line rounded-card mb-[18px] px-[22px] py-[18px]">
			<h3 class="text-[20px] leading-[1.35] text-navy">Public profile</h3>
			<p class="text-[15px] text-subtle mt-1 mb-3.5">This candidate asked to show their profile on the public site. Their email, phone and resume show there only if they chose to share them.</p>
			<PublicReview :candidate="candidate" />
		</section>

		<div class="grid grid-cols-[1.65fr_1fr] gap-[18px] items-start max-[980px]:grid-cols-1">
			<!-- about -->
			<section class="bg-white border border-line rounded-card">
				<div class="px-7 pt-6 pb-1 max-[560px]:px-5"><h3 class="text-[20px] leading-[1.35] text-navy">About</h3></div>
				<div class="px-[22px] py-5">
					<p v-if="candidate.about" class="text-[14.5px] text-ink-700 leading-[1.7] whitespace-pre-line">{{ candidate.about }}</p>
					<p v-else class="text-[14.5px] text-muted leading-[1.7]">This candidate hasn't written a bio yet.</p>
				</div>
			</section>

			<!-- contact -->
			<section class="bg-white border border-line rounded-card">
				<div class="px-7 pt-6 pb-1 max-[560px]:px-5"><h3 class="text-[20px] leading-[1.35] text-navy">Contact</h3></div>
				<div class="px-[22px] py-3 [&_.ci]:flex [&_.ci]:items-center [&_.ci]:gap-3 [&_.ci]:py-2.5 [&_.ci]:text-sm [&_.ci]:text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
					<div class="ci"><component :is="icons.mail" aria-hidden="true" />{{ candidate.email }}</div>
					<div v-if="candidate.phone_number" class="ci"><component :is="icons.phone" aria-hidden="true" />{{ candidate.phone_number }}</div>
					<a v-if="candidate.linkedin_profile_url" class="ci !text-blue-700 font-medium" :href="candidate.linkedin_profile_url" target="_blank" rel="noopener"><component :is="icons.external" aria-hidden="true" />LinkedIn profile</a>
				</div>
			</section>
		</div>
	</div>
</template>
