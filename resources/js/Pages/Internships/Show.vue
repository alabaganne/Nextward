<script>
import { h } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PublicLayout from '@/Layouts/Public.vue';

export default {
	layout: (_h, page) =>
		page.props?.auth?.user
			? h(AppShell, { active: 'internships' }, () => page)
			: h(PublicLayout, {}, () => page),
};
</script>

<script setup>
import { ArrowRight, ArrowUpRight, CalendarDays, Check, ChevronRight, Clock3, Download, Heart, LockKeyhole, Mail, MapPin, Phone, TriangleAlert, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';

const props = defineProps({
	internship: { type: Object, required: true },
});

const icons = {
	chevron: ChevronRight,
	pin: MapPin,
	calendar: CalendarDays,
	clock: Clock3,
	check: Check,
	heart: Heart,
	arrow: ArrowRight,
	warning: TriangleAlert,
	mail: Mail,
	phone: Phone,
	external: ArrowUpRight,
	lock: LockKeyhole,
	close: X,
};

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isStudent = computed(() => user.value?.userable_type === 'student');
const ownsIt = computed(
	() => user.value?.is_admin || (user.value?.userable_type === 'company' && user.value?.userable_id === props.internship.company.id)
);

const deleteRole = () => {
	if (confirm('Delete this internship and its applications? This cannot be undone.')) {
		router.delete(route('internships.destroy', props.internship.id));
	}
};

const WORK_TYPES = { remote: 'Remote', hybrid: 'Hybrid', onsite: 'On-site' };

const showLoginModal = ref(false);
const openLoginModal = () => (showLoginModal.value = true);
const closeLoginModal = () => (showLoginModal.value = false);

const toggleLike = () => {
	if (!user.value) return openLoginModal();
	router.post(route('likes.store', props.internship.id), {}, { preserveScroll: true });
};
</script>

<template>
	<!-- Breadcrumb -->
	<div :class="user ? 'px-10 max-[680px]:px-5 pt-7 pb-14 w-full max-w-wrap mx-auto' : ''">
		<template v-if="!user">
			<section class="bg-white border-b border-line pt-[26px]">
				<div class="max-w-wrap mx-auto px-7">
					<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium pb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
						<Link class="hover:text-blue-700" :href="route('internships.index')">Internships</Link>
						<component :is="icons.chevron" aria-hidden="true" />
						<span class="text-ink-700 font-semibold">{{ internship.title }}</span>
					</div>
				</div>
			</section>
		</template>
		<div v-else class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('internships.index')">Internships</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ internship.title }}</span>
		</div>

		<div :class="user ? '' : 'max-w-wrap mx-auto px-7'">
			<div class="grid grid-cols-[1fr_350px] gap-6 items-start max-[1080px]:grid-cols-1" :class="{ 'pt-7 pb-20': !user }">
				<!-- LEFT -->
				<div class="flex flex-col gap-4">
					<section class="bg-white border border-line rounded-[18px] overflow-hidden px-8 py-[30px]">
						<div class="flex items-start gap-[18px]">
							<CompanyLogo class="w-[62px] h-[62px] rounded-[16px] text-[22px]" :name="internship.company.name" />
							<div>
								<h1 class="font-display text-[28px] font-semibold leading-[1.1]">{{ internship.title }}</h1>
								<p class="text-[15px] text-muted mt-1.5"><b class="text-ink-700 font-semibold">{{ internship.company.name }}</b> · {{ internship.field.name }}</p>
							</div>
						</div>
						<div class="flex flex-wrap gap-2.5 mt-[22px] [&_.m]:flex [&_.m]:items-center [&_.m]:gap-[7px] [&_.m]:text-[13.5px] [&_.m]:text-ink-700 [&_.m]:font-medium [&_.m]:bg-paper-2 [&_.m]:border [&_.m]:border-line-2 [&_.m]:px-[13px] [&_.m]:py-2 [&_.m]:rounded-[9px] [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:text-blue-600">
							<span class="m"><component :is="icons.pin" aria-hidden="true" />{{ internship.city.name }}<template v-if="internship.work_type"> · {{ WORK_TYPES[internship.work_type] || internship.work_type }}</template></span>
							<span v-if="internship.duration_weeks" class="m"><component :is="icons.clock" aria-hidden="true" />{{ internship.duration_weeks }} weeks</span>
							<span class="m"><component :is="icons.calendar" aria-hidden="true" />Posted {{ internship.created_at }}</span>
							<span class="m"><component :is="icons.clock" aria-hidden="true" />Closing {{ internship.closing_at }}</span>
						</div>
					</section>

					<section class="dbody bg-white border border-line rounded-[18px] px-8 max-[560px]:px-5 py-[30px] [&_h2]:font-display [&_h2]:text-[18px] [&_h2]:font-semibold [&_h2]:mb-3.5 [&_h2:not(:first-child)]:mt-[30px] [&_p]:text-[15.5px] [&_p]:leading-[1.7] [&_p]:text-ink-700 [&_p]:mb-3.5">
						<p class="whitespace-pre-line">{{ internship.description }}</p>
						<template v-if="internship.attachments?.length">
							<div class="h-px bg-line-2 my-[30px]"></div>
							<h2>Attachments</h2>
							<div class="flex flex-col gap-3">
								<a v-for="attachment in internship.attachments" :key="attachment.url || attachment.name" class="inline-flex items-center gap-2 text-blue-700 text-sm font-semibold break-all" :href="attachment.url" target="_blank" rel="noopener"><Download :size="17" class="shrink-0" aria-hidden="true" />{{ attachment.name }}</a>
							</div>
						</template>
						<template v-if="internship.skills?.length">
							<div class="h-px bg-line-2 my-[30px]"></div>
							<h2>Skills &amp; expertise</h2>
							<div class="flex flex-wrap gap-2 [&>span]:text-[13px] [&>span]:px-[13px] [&>span]:py-[7px] [&>span]:rounded-lg [&>span]:font-semibold [&>span]:bg-blue-50 [&>span]:text-blue-700">
								<span v-for="skill in internship.skills" :key="skill">{{ skill }}</span>
							</div>
						</template>
					</section>
				</div>

				<!-- RIGHT -->
				<div class="flex flex-col gap-4 sticky top-[94px]">
					<section class="bg-white border border-line rounded-[18px] p-6">
						<template v-if="internship.pay">
							<div class="flex items-baseline gap-2 mb-1"><b class="font-display text-[30px] font-semibold">${{ internship.pay.amount }}</b><span class="text-sm text-muted">/ {{ internship.pay.unit }}</span></div>
							<p class="text-[13.5px] text-muted mb-5">Paid internship<template v-if="internship.term"> · {{ internship.term }}</template></p>
						</template>
						<!-- Company managing its own role -->
						<template v-if="ownsIt">
							<Link class="w-full justify-center inline-flex items-center gap-2.5 font-semibold text-base px-7 py-4 rounded-[13px] bg-blue-600 text-white hover:bg-blue-700 transition-colors [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="route('internships.applications.index', internship.id)">
								View applicants <component :is="icons.arrow" aria-hidden="true" />
							</Link>
							<Link class="w-full justify-center mt-2.5 inline-flex items-center gap-2.5 font-semibold text-base px-7 py-4 rounded-[13px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-colors" :href="route('internships.edit', internship.id)">
								Edit role
							</Link>
							<button class="w-full mt-2.5 py-3 text-sm font-semibold text-red-600 hover:text-red-700" type="button" @click="deleteRole">Delete role</button>
						</template>

						<!-- Student who already applied -->
						<template v-else-if="internship.application">
							<Link :href="route('applications.show', internship.application.id)" class="flex items-center gap-2.5 w-full justify-center bg-[#dcfce7] text-[#047857] font-semibold text-[14.5px] py-3.5 rounded-[12px] mb-2.5 [&_svg]:w-[18px] [&_svg]:h-[18px]">
								<component :is="icons.check" aria-hidden="true" />
								Applied on {{ internship.application.created_at }}
							</Link>
							<button
								class="w-full justify-center mt-2.5 inline-flex items-center gap-2.5 font-semibold text-base px-7 py-4 rounded-[13px] transition-colors [&_svg]:w-[17px] [&_svg]:h-[17px]"
								:class="internship.liked ? 'bg-[#fef2f2] border border-[#fecaca] text-[#dc2626]' : 'bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700'"
								type="button"
								@click="toggleLike"
							>
								<component :is="icons.heart" aria-hidden="true" />
								{{ internship.liked ? 'Saved' : 'Save this role' }}
							</button>
						</template>

						<template v-else-if="!internship.is_open">
							<p class="rounded-[12px] bg-paper-2 px-4 py-3 text-sm text-ink-700">Applications closed on {{ internship.closing_at }}.</p>
							<Link class="mt-4 block text-center text-sm font-semibold text-blue-700" :href="route('internships.index')">Browse open internships</Link>
						</template>
						<template v-else-if="user && !isStudent">
							<p class="text-sm text-muted">Student accounts can apply for this role.</p>
						</template>

						<!-- Guests and students who have not applied -->
						<template v-else>
							<component
								:is="user && isStudent ? Link : 'button'"
								class="w-full justify-center inline-flex items-center gap-2.5 font-semibold text-base px-7 py-4 rounded-[13px] bg-blue-600 text-white hover:bg-blue-700 transition-colors [&_svg]:w-[17px] [&_svg]:h-[17px]"
								v-bind="user && isStudent ? { href: route('applications.create', internship.id) } : { type: 'button' }"
								@click="!user && openLoginModal()"
							>
								Apply now <component :is="icons.arrow" aria-hidden="true" />
							</component>
							<button
								v-if="!user || isStudent"
								class="w-full justify-center mt-2.5 inline-flex items-center gap-2.5 font-semibold text-base px-7 py-4 rounded-[13px] transition-colors [&_svg]:w-[17px] [&_svg]:h-[17px]"
								:class="internship.liked ? 'bg-[#fef2f2] border border-[#fecaca] text-[#dc2626]' : 'bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700'"
								type="button"
								@click="toggleLike"
							>
								<component :is="icons.heart" aria-hidden="true" />
								{{ internship.liked ? 'Saved' : 'Save this role' }}
							</button>
						</template>

						<div v-if="internship.is_open" class="flex items-center gap-[9px] mt-[18px] pt-[18px] border-t border-line-2 text-[13.5px] text-muted [&_svg]:w-4 [&_svg]:h-4 [&_svg]:text-[#d97706]">
							<component :is="icons.warning" aria-hidden="true" />
							<span>Applications close on <b class="text-ink-700 font-semibold">{{ internship.closing_at }}</b></span>
						</div>
					</section>

					<section class="bg-white border border-line rounded-[18px] p-6">
						<div class="flex items-center gap-[13px] mb-[18px]">
							<CompanyLogo class="w-[46px] h-[46px] rounded-[12px] text-base" :name="internship.company.name" />
							<div><b class="font-display text-base block">{{ internship.company.name }}</b><span class="text-[13px] text-muted">{{ internship.company.city?.name }}</span></div>
						</div>
						<div class="flex flex-col gap-0.5 [&_.ci]:flex [&_.ci]:items-center [&_.ci]:gap-3 [&_.ci]:py-2.5 [&_.ci]:text-sm [&_.ci]:text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
							<div v-if="internship.company.email" class="ci"><component :is="icons.mail" aria-hidden="true" />{{ internship.company.email }}</div>
							<div v-if="internship.company.phone_number" class="ci"><component :is="icons.phone" aria-hidden="true" />{{ internship.company.phone_number }}</div>
							<div v-if="internship.company.city?.name" class="ci"><component :is="icons.pin" aria-hidden="true" />{{ internship.company.city.name }}</div>
						</div>
						<a v-if="internship.company.website" class="inline-flex items-center gap-[7px] mt-3.5 text-sm font-semibold text-blue-700 [&_svg]:w-[15px] [&_svg]:h-[15px]" :href="internship.company.website" target="_blank" rel="noopener">
							Visit website <component :is="icons.external" aria-hidden="true" />
						</a>
					</section>
				</div>
			</div>
		</div>
	</div>

	<!-- LOGIN-REQUIRED MODAL -->
	<Teleport to="body">
		<div
			v-if="showLoginModal"
			class="fixed inset-0 bg-navy-900/55 backdrop-blur-[3px] flex items-center justify-center z-[100] p-6"
			@click.self="closeLoginModal"
			@keydown.esc="closeLoginModal"
		>
			<div class="relative bg-white rounded-[20px] max-w-[420px] w-full px-[34px] py-9 text-center">
				<button class="absolute top-[18px] right-[18px] w-9 h-9 rounded-[10px] border-0 bg-paper-2 text-muted grid place-items-center cursor-pointer hover:bg-paper-3 hover:text-ink [&_svg]:w-[18px] [&_svg]:h-[18px]" aria-label="Close" @click="closeLoginModal"><component :is="icons.close" aria-hidden="true" /></button>
				<div class="w-[62px] h-[62px] rounded-[17px] bg-blue-50 text-blue-600 grid place-items-center mx-auto mb-5 [&_svg]:w-[30px] [&_svg]:h-[30px]"><component :is="icons.lock" aria-hidden="true" /></div>
				<h3 class="font-display text-[22px] font-semibold mb-2.5">Log in to apply</h3>
				<p class="text-[15px] text-muted leading-[1.6] mb-[26px]">You need an Internly account to apply for this internship and track your application. It only takes a minute.</p>
				<div class="flex flex-col gap-2.5">
					<Link class="w-full justify-center inline-flex items-center font-semibold text-base px-7 py-4 rounded-[13px] bg-blue-600 text-white hover:bg-blue-700 transition-colors" :href="route('login')">Log in</Link>
					<Link class="w-full justify-center inline-flex items-center font-semibold text-base px-7 py-4 rounded-[13px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-colors" :href="route('register')">Create an account</Link>
				</div>
			</div>
		</div>
	</Teleport>
</template>
