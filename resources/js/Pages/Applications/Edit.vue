<script setup>
import { ArrowRight, Check, ChevronRight, Clock3, MapPin } from '@lucide/vue';
import { computed, h, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'applications' }, () => page),
});

const props = defineProps({
	internship: { type: Object, required: true },
	application: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
	pin: MapPin,
	clock: Clock3,
	arrow: ArrowRight,
	check: Check,
};

const editing = computed(() => !!props.application);

const form = useForm({
	cover_letter: props.application?.cover_letter || '',
	message: props.application?.message || '',
	attachment_files: [],
});

// A draft from the cover letter button on the opportunity page.
onMounted(() => {
	try {
		const key = `nextward.cover-letter.${props.internship.id}`;
		const draft = sessionStorage.getItem(key);
		sessionStorage.removeItem(key);
		if (draft && !form.cover_letter) form.cover_letter = draft;
	} catch { /* storage blocked */ }
});

const submit = () => {
	if (editing.value) {
		form.transform((data) => ({ ...data, _method: 'put' })).post(route('applications.update', props.application.id));
	} else {
		form.post(route('applications.store', props.internship.id));
	}
};

const pickFiles = (event) => {
	form.attachment_files = Array.from(event.target.files || []);
};
// Errors for single files come back as attachment_files.0, attachment_files.1…
const attachmentError = computed(() =>
	Object.entries(form.errors).find(([key]) => key === 'attachment_files' || key.startsWith('attachment_files.'))?.[1]
);

const tips = [
	'Lead with your strongest, most relevant qualification.',
	'Show evidence (projects, links, numbers), not adjectives.',
	'Keep it short: cut anything that isn’t earning its place.',
];
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('opportunities.index')">Opportunities</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<Link class="hover:text-blue-700" :href="internship.public_url || route('internships.show', internship.id)">{{ internship.title }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-semibold">{{ editing ? 'Edit application' : 'Apply' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight">{{ editing ? 'Edit your application' : 'Introduce yourself' }}</h1>
			<p class="text-muted text-[15px] mt-1.5">What makes you a strong candidate for this opportunity?</p>
		</div>

		<div class="grid grid-cols-[1fr_350px] gap-6 items-start max-[1080px]:grid-cols-1">
			<!-- FORM -->
			<form class="bg-white border border-line rounded-[18px]" @submit.prevent="submit">
				<div class="px-8 max-[560px]:px-5 py-[26px]">
					<div class="mb-6">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="cover_letter">Cover letter</label>
						<p class="text-[13px] text-muted mb-2.5">Tell the company a little bit about yourself and any work you’ve done that’s relevant to this opportunity.</p>
						<textarea id="cover_letter" v-model="form.cover_letter" class="fld !min-h-[220px]" rows="10"></textarea>
						<div class="flex justify-between mt-[7px] text-xs">
							<span class="text-[#dc2626]">{{ form.errors.cover_letter }}</span>
							<span class="text-muted-2">{{ form.cover_letter.length }} / 200 min</span>
						</div>
					</div>

					<div class="mb-6">
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="message">Message <span class="text-muted-2 font-medium">(optional)</span></label>
						<p class="text-[13px] text-muted mb-2.5">Any questions about this opportunity or the company? You can even ask for a video call.</p>
						<textarea id="message" v-model="form.message" class="fld !min-h-[130px]" rows="6"></textarea>
						<div class="flex justify-between mt-[7px] text-xs">
							<span class="text-[#dc2626]">{{ form.errors.message }}</span>
							<span class="text-muted-2">{{ form.message?.length || 0 }} / 100 min if provided</span>
						</div>
					</div>

					<div>
						<label class="block text-[13px] font-semibold mb-[7px] text-ink-700" for="attachment_files">Attachments</label>
						<p id="attachment_files-hint" class="text-[13px] text-muted mb-2.5">Up to 10 files, 10&nbsp;MB each. PDF, Word, text, or RTF files. Your résumé, work samples, or anything that supports your application.</p>
						<input id="attachment_files" class="fld !py-[9px]" type="file" accept=".pdf,.doc,.docx,.txt,.rtf" multiple aria-describedby="attachment_files-hint" @input="pickFiles">
						<div v-if="attachmentError" class="text-xs text-[#dc2626] mt-[7px]">{{ attachmentError }}</div>
						<div v-if="application?.attachments?.length" class="flex flex-wrap gap-2 mt-3">
							<span v-for="attachment in application.attachments" :key="attachment.url || attachment.name" class="inline-flex items-center px-[11px] py-[5px] rounded-lg text-xs font-semibold bg-paper-3 text-ink-700">{{ attachment.name }}</span>
						</div>
					</div>
				</div>
				<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-[18px]">
					<Link class="cbtn bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700" :href="editing ? route('applications.show', application.id) : internship.public_url || route('internships.show', internship.id)">Cancel</Link>
					<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 [&_svg]:w-[17px] [&_svg]:h-[17px]" type="submit" :disabled="form.processing">
						{{ editing ? 'Save changes' : 'Submit application' }} <component :is="icons.arrow" aria-hidden="true" />
					</button>
				</div>
			</form>

			<!-- RIGHT -->
			<div class="flex flex-col gap-4 sticky top-[94px]">
				<section class="bg-white border border-line rounded-[18px] p-6">
					<h3 class="text-sm tracking-[.04em] text-muted font-bold font-display mb-4">YOU'RE APPLYING TO</h3>
					<div class="flex items-center gap-[13px] mb-4">
						<CompanyLogo class="w-[46px] h-[46px] rounded-[12px] text-base" :name="internship.company.name" :src="internship.company.image" />
						<div>
							<b class="font-display text-base block">{{ internship.title }}</b>
							<span class="text-[13px] text-muted">{{ internship.company.name }} · {{ internship.location_label || internship.city?.name }}</span>
						</div>
					</div>
					<div class="flex flex-col gap-0.5 [&_.ci]:flex [&_.ci]:items-center [&_.ci]:gap-3 [&_.ci]:py-2.5 [&_.ci]:text-sm [&_.ci]:text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
						<div class="ci"><component :is="icons.clock" aria-hidden="true" />{{ internship.closing_at ? `Closes ${internship.closing_at}` : 'No deadline listed' }}</div>
						<div v-if="internship.duration_weeks" class="ci"><component :is="icons.pin" aria-hidden="true" />{{ internship.duration_weeks }} weeks<template v-if="internship.term"> · {{ internship.term }}</template></div>
					</div>
				</section>

				<section class="bg-white border border-line rounded-[18px] p-6">
					<h3 class="text-sm tracking-[.04em] text-muted font-bold font-display mb-4">TIPS</h3>
					<ul class="list-none flex flex-col gap-[11px] [&_li]:flex [&_li]:items-start [&_li]:gap-2.5 [&_li]:text-[13.5px] [&_li]:text-ink-700 [&_li]:leading-[1.5]">
						<li v-for="tip in tips" :key="tip">
							<span class="w-[18px] h-[18px] rounded-md grid place-items-center shrink-0 bg-blue-50 text-blue-600 mt-px [&_svg]:w-[11px] [&_svg]:h-[11px]"><component :is="icons.check" aria-hidden="true" /></span>
							{{ tip }}
						</li>
					</ul>
				</section>
			</div>
		</div>
	</div>
</template>
