<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, BadgeCheck, Briefcase, Building2, CalendarDays, Globe2, Link2, Mail, MapPin, Phone, Users } from '@lucide/vue';

// Company facts for the side column; email and phone only when the page passes them.
const props = defineProps({ company: { type: Object, required: true } });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => fr.value ? french : en;
const facts = computed(() => props.company.facts || {});
const place = computed(() => {
	const location = facts.value.location || props.company.location;
	const country = facts.value.country;
	return location && country && !location.includes(country) ? `${location}, ${country}` : location || country;
});
const rows = computed(() => [
	{ icon: MapPin, label: text('Head office', 'Siège'), value: place.value },
	{ icon: CalendarDays, label: text('Founded', 'Fondée en'), value: props.company.founded_year },
	{ icon: Users, label: text('Size', 'Taille'), value: props.company.size && text(`${props.company.size} people`, `${props.company.size} personnes`) },
	{ icon: Building2, label: text('Type', 'Type'), value: { startup: text('Startup', 'Start-up'), established: text('Established company', 'Entreprise établie') }[props.company.stage] },
	{ icon: Globe2, label: text('Website', 'Site web'), value: facts.value.site || props.company.website, href: props.company.website },
	{ icon: Briefcase, label: text('Careers page', 'Page carrières'), value: props.company.careers_url && text('See their careers page', 'Voir leur page carrières'), href: props.company.careers_url },
	{ icon: Link2, label: 'LinkedIn', value: props.company.linkedin_url && text('Company page', 'Page entreprise'), href: props.company.linkedin_url },
	{ icon: Mail, label: text('Email', 'E-mail'), value: props.company.email, href: props.company.email && `mailto:${props.company.email}` },
	{ icon: Phone, label: text('Phone', 'Téléphone'), value: props.company.phone_number },
].filter((row) => row.value));
const checked = computed(() => facts.value.checked_at && new Date(`${facts.value.checked_at}T12:00:00`).toLocaleDateString(fr.value ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }));
</script>

<template>
	<section class="bg-white border border-line rounded-2xl p-6" aria-labelledby="company-facts-title">
		<h2 id="company-facts-title" class="font-display text-lg font-semibold">{{ text('Company facts', 'Infos sur l’entreprise') }}</h2>
		<ul v-if="rows.length" class="mt-4 grid gap-3.5 text-[14.5px]">
			<li v-for="row in rows" :key="row.label" class="flex items-center gap-3 min-w-0">
				<span class="grid size-[34px] shrink-0 place-items-center rounded-[10px] bg-blue-50 text-blue-700"><component :is="row.icon" :size="16" aria-hidden="true" /></span>
				<span class="min-w-0">
					<span class="block text-xs text-slate-500">{{ row.label }}</span>
					<a v-if="row.href" :href="row.href" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:underline break-all">{{ row.value }}<ArrowUpRight :size="14" class="shrink-0" aria-hidden="true" /></a>
					<span v-else class="block font-semibold text-navy-900 break-words">{{ row.value }}</span>
				</span>
			</li>
		</ul>
		<p v-else class="mt-3 text-sm text-slate-400">{{ text('No company facts yet.', 'Pas encore d’infos sur cette entreprise.') }}</p>
		<p v-if="checked" class="mt-5 flex items-start gap-2 rounded-xl bg-[#EEF4FF] px-3.5 py-3 text-[13px] leading-5 text-blue-900">
			<BadgeCheck :size="16" class="mt-0.5 shrink-0 text-blue-700" aria-hidden="true" />
			<span>{{ text(`Verified: we found and checked ${facts.site || 'its website'} on ${checked}.`, `Vérifiée : nous avons trouvé et vérifié ${facts.site || 'son site'} le ${checked}.`) }}</span>
		</p>
	</section>
</template>
