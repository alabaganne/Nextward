<script setup>
import { ArrowRight, Check, ChevronRight, Clock3, MapPin } from '@lucide/vue';
import { computed, h, onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { appPath } from '@/utils/url';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'applications' }, () => page),
});

const props = defineProps({
	opportunity: { type: Object, required: true },
	application: { type: Object, default: null },
	resumes: { type: Array, default: () => [] },
	kits: { type: Array, default: () => [] },
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

// The resume goes first; other files are added below.
const attachedResume = computed(() => props.application?.attachments?.find((file) => file.resume) || null);
const otherAttachments = computed(() => props.application?.attachments?.filter((file) => !file.resume) || []);
const sources = { uploaded: 'Uploaded PDF', profile: 'From your profile', translation: 'AI translation', copy: 'Editable copy' };
const resumeOptions = computed(() => [
	...(attachedResume.value ? [{ value: 'keep', label: attachedResume.value.name, hint: 'Already sent with this application' }] : []),
	...props.kits.map((kit) => ({ value: `kit:${kit.id}`, label: 'Tailored CV for this role', hint: `${kit.language} · updated ${kit.updated_label}` })),
	...props.resumes.map((resume) => ({ value: `resume:${resume.id}`, label: resume.name, hint: [resume.is_main && 'Main resume', resume.language, sources[resume.source]].filter(Boolean).join(' · ') })),
	{ value: 'none', label: 'No resume', hint: 'Attach your files below instead' },
]);
const mainResume = props.resumes.find((resume) => resume.is_main);
const choice = ref(attachedResume.value ? 'keep' : editing.value ? 'none' : mainResume ? `resume:${mainResume.id}` : 'none');

// A draft from the cover letter button on the opportunity page, and the CV picked on the kit page.
onMounted(() => {
	try {
		const key = `nextward.cover-letter.${props.opportunity.id}`;
		const draft = sessionStorage.getItem(key);
		sessionStorage.removeItem(key);
		if (draft && !form.cover_letter) form.cover_letter = draft;

		const kitKey = `nextward.kit-cv.${props.opportunity.id}`;
		const kit = Number(sessionStorage.getItem(kitKey));
		sessionStorage.removeItem(kitKey);
		if (!editing.value && props.kits.some((item) => item.id === kit)) choice.value = `kit:${kit}`;
	} catch { /* storage blocked */ }
});

const resumeData = () => {
	const [type, id] = choice.value.split(':');
	return {
		resume_id: type === 'resume' ? Number(id) : null,
		kit_id: type === 'kit' ? Number(id) : null,
		remove_resume: choice.value === 'none' && !!attachedResume.value,
	};
};

const submit = () => {
	if (editing.value) {
		form.transform((data) => ({ ...data, ...resumeData(), _method: 'put' })).post(route('applications.update', props.application.id));
	} else {
		form.transform((data) => ({ ...data, ...resumeData() })).post(route('applications.store', props.opportunity.id));
	}
};
const resumeError = computed(() => form.errors.resume_id || form.errors.kit_id || form.errors.remove_resume);

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
			<Link class="hover:text-blue-700" :href="route('app.opportunities.index')">Roles</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<Link class="hover:text-blue-700" :href="appPath(opportunity.public_url) || route('app.opportunities.show', opportunity.id)">{{ opportunity.title }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? 'Edit application' : 'Apply' }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? 'Edit your application' : 'Introduce yourself' }}</h1>
			<p class="text-muted text-[15px] mt-1.5">What makes you a strong candidate for this role?</p>
		</div>

		<div class="grid grid-cols-[1fr_350px] gap-6 items-start max-[1080px]:grid-cols-1">
			<!-- FORM -->
			<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
				<div class="px-8 max-[560px]:px-5 py-[26px]">
					<div class="mb-6">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="cover_letter">Cover letter</label>
						<p class="text-[13px] text-muted mb-2.5">Tell the company a little bit about yourself and any work you’ve done that’s relevant to this role.</p>
						<textarea id="cover_letter" v-model="form.cover_letter" class="fld !min-h-[220px]" rows="10"></textarea>
						<div class="flex justify-between mt-[7px] text-xs">
							<span class="text-red-600">{{ form.errors.cover_letter }}</span>
							<span class="text-muted-2">{{ form.cover_letter.length }} / 200 min</span>
						</div>
					</div>

					<div class="mb-6">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="message">Message <span class="text-muted-2 font-medium">(optional)</span></label>
						<p class="text-[13px] text-muted mb-2.5">Any questions about this role or the company? You can even ask for a video call.</p>
						<textarea id="message" v-model="form.message" class="fld !min-h-[130px]" rows="6"></textarea>
						<div class="flex justify-between mt-[7px] text-xs">
							<span class="text-red-600">{{ form.errors.message }}</span>
							<span class="text-muted-2">{{ form.message?.length || 0 }} / 100 min if provided</span>
						</div>
					</div>

					<fieldset class="mb-6">
						<legend class="block text-[14px] font-medium mb-2 text-navy">Resume</legend>
						<p class="text-[13px] text-muted mb-2.5">
							Sent as a PDF with your application.
							<template v-if="!resumes.length && !kits.length">You have no saved resume yet: <Link class="text-blue-700 font-medium hover:underline" :href="`${route('profile.show')}#resumes`">add one on your profile</Link>, or attach it below.</template>
						</p>
						<div class="flex flex-col gap-2">
							<label
								v-for="option in resumeOptions"
								:key="option.value"
								class="flex items-center gap-3 rounded-card border px-4 py-3 cursor-pointer transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-600"
								:class="choice === option.value ? 'border-blue-600 bg-blue-50' : 'border-line hover:border-blue-300'"
							>
								<input v-model="choice" class="accent-blue-600 w-4 h-4 shrink-0" type="radio" name="resume" :value="option.value">
								<span class="min-w-0">
									<b class="block text-[13.5px] font-medium text-ink truncate">{{ option.label }}</b>
									<span class="block text-xs text-muted">{{ option.hint }}</span>
								</span>
							</label>
						</div>
						<div v-if="resumeError" class="text-xs text-red-600 mt-[7px]">{{ resumeError }}</div>
					</fieldset>

					<div>
						<label class="block text-[14px] font-medium mb-2 text-navy" for="attachment_files">Attachments</label>
						<p id="attachment_files-hint" class="text-[13px] text-muted mb-2.5">Up to 10 files in all, your resume included, 10&nbsp;MB each. PDF, Word, text, or RTF files: work samples or anything that supports your application.</p>
						<input id="attachment_files" class="fld !py-[9px]" type="file" accept=".pdf,.doc,.docx,.txt,.rtf" multiple aria-describedby="attachment_files-hint" @input="pickFiles">
						<div v-if="attachmentError" class="text-xs text-red-600 mt-[7px]">{{ attachmentError }}</div>
						<div v-if="otherAttachments.length" class="flex flex-wrap gap-2 mt-3">
							<span v-for="attachment in otherAttachments" :key="attachment.url || attachment.name" class="inline-flex items-center px-[11px] py-[5px] rounded-lg text-[14px] font-medium bg-paper-3 text-navy">{{ attachment.name }}</span>
						</div>
					</div>
				</div>
				<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-card">
					<Link class="cta cta-secondary" :href="editing ? route('applications.show', application.id) : appPath(opportunity.public_url) || route('app.opportunities.show', opportunity.id)">Cancel</Link>
					<button class="cta cta-primary [&_svg]:w-[17px] [&_svg]:h-[17px]" type="submit" :disabled="form.processing">
						{{ editing ? 'Save changes' : 'Submit application' }} <component :is="icons.arrow" aria-hidden="true" />
					</button>
				</div>
			</form>

			<!-- RIGHT -->
			<div class="flex flex-col gap-4 sticky top-[94px]">
				<section class="bg-white border border-transparent rounded-card p-6">
					<h3 class="mb-4 text-[13px] tracking-caps text-brand uppercase">YOU'RE APPLYING TO</h3>
					<div class="flex items-center gap-[13px] mb-4">
						<CompanyLogo class="w-[46px] h-[46px] rounded-card text-base" :name="opportunity.company.name" :src="opportunity.company.image" />
						<div>
							<b class="text-base block">{{ opportunity.title }}</b>
							<span class="text-[13px] text-muted">{{ opportunity.company.name }} · {{ opportunity.location_label || opportunity.city?.name }}</span>
						</div>
					</div>
					<div class="flex flex-col gap-0.5 [&_.ci]:flex [&_.ci]:items-center [&_.ci]:gap-3 [&_.ci]:py-2.5 [&_.ci]:text-sm [&_.ci]:text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">
						<div class="ci"><component :is="icons.clock" aria-hidden="true" />{{ opportunity.closing_at ? `Closes ${opportunity.closing_at}` : 'No deadline listed' }}</div>
						<div v-if="opportunity.duration_weeks" class="ci"><component :is="icons.pin" aria-hidden="true" />{{ opportunity.duration_weeks }} weeks<template v-if="opportunity.term"> · {{ opportunity.term }}</template></div>
					</div>
				</section>

				<section class="bg-white border border-transparent rounded-card p-6">
					<h3 class="mb-4 text-[13px] tracking-caps text-brand uppercase">TIPS</h3>
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
