<script setup>
import LandingHero from '@/Components/Landing/LandingHero.vue';

defineProps({
	label: String,
	title: String,
	lead: String,
	updated: String,
	tocLabel: String,
	sections: Array,
});
// [[text]] marks a detail the owner must fill in before launch.
const body = 'text-[16px] leading-[1.8] text-ink-700';
const fill = 'rounded-[4px] border border-dashed border-amber-600 bg-amber-100 px-1.5 py-px font-medium text-amber-900 box-decoration-clone';
const parts = (line) => line.split(/\[\[(.+?)\]\]/).map((text, index) => ({ text, fill: index % 2 === 1 }));
</script>

<template>
	<div class="landing">
		<LandingHero title-id="legal-title" :label="label" :lead="lead">
			{{ title }}
			<template #actions><p class="rounded-card bg-white/10 px-4 py-2 text-[13.5px] text-mist">{{ updated }}</p></template>
		</LandingHero>

		<div class="public-container grid grid-cols-[260px_minmax(0,1fr)] gap-16 pt-20 pb-24 max-[900px]:grid-cols-1 max-[900px]:gap-10 max-[900px]:pt-12 max-[900px]:pb-18">
			<nav :aria-label="tocLabel" class="sticky top-[104px] self-start rounded-card bg-pale p-5 max-[900px]:static">
				<p class="px-3 text-[13px] tracking-caps text-brand uppercase">{{ tocLabel }}</p>
				<ol class="mt-3 grid gap-0.5">
					<li v-for="section in sections" :key="section.id"><a :href="`#${section.id}`" class="focus-ring block rounded-card px-3 py-2 text-[14px] leading-[1.5] text-subtle transition-colors duration-150 hover:bg-white hover:text-brand">{{ section.title }}</a></li>
				</ol>
			</nav>
			<div class="max-w-[760px]">
				<section v-for="(section, sectionIndex) in sections" :id="section.id" :key="section.id" class="scroll-mt-24" :class="{ 'mt-12 border-t border-line pt-12': sectionIndex > 0 }" :aria-labelledby="`${section.id}-title`">
					<h2 :id="`${section.id}-title`" class="text-[length:clamp(24px,2.4vw,30px)] leading-[1.3] text-navy">{{ section.title }}</h2>
					<template v-for="(block, index) in section.body" :key="index">
						<ul v-if="Array.isArray(block)" class="mt-4 grid list-disc gap-2 pl-5 marker:text-brand">
							<li v-for="item in block" :key="item" :class="body"><template v-for="(part, i) in parts(item)" :key="i"><mark v-if="part.fill" :class="fill">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></li>
						</ul>
						<p v-else class="mt-4" :class="body"><template v-for="(part, i) in parts(block)" :key="i"><mark v-if="part.fill" :class="fill">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></p>
					</template>
				</section>
				<slot />
			</div>
		</div>
	</div>
</template>
