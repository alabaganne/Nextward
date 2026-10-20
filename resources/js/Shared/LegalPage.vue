<script setup>
defineProps({
	label: String,
	title: String,
	lead: String,
	updated: String,
	tocLabel: String,
	sections: Array,
});
// [[text]] marks a detail the owner must fill in before launch.
const body = 'text-[15.5px] leading-[1.8] text-gray-700';
const parts = (line) => line.split(/\[\[(.+?)\]\]/).map((text, index) => ({ text, fill: index % 2 === 1 }));
</script>

<template>
	<div class="landing">
		<section aria-labelledby="legal-title" class="hero-dark">
			<div class="public-container pt-16 pb-18 max-[900px]:py-10">
				<p class="landing-label">{{ label }}</p>
				<h1 id="legal-title" class="hero-title text-[length:clamp(40px,4.6vw,64px)]">{{ title }}</h1>
				<p class="hero-lead max-w-[640px]">{{ lead }}</p>
				<p class="mt-5 text-[13px] text-mist-2">{{ updated }}</p>
			</div>
		</section>

		<div class="public-container grid grid-cols-[240px_minmax(0,1fr)] gap-16 pt-16 pb-24 max-[900px]:grid-cols-1 max-[900px]:gap-10 max-[900px]:pt-11 max-[900px]:pb-18">
			<nav :aria-label="tocLabel" class="sticky top-[104px] self-start max-[900px]:static max-[900px]:rounded-[14px] max-[900px]:border max-[900px]:border-line max-[900px]:p-[18px]">
				<p class="text-[11px] font-semibold tracking-[.15em] text-subtle-2 uppercase">{{ tocLabel }}</p>
				<ol class="mt-3.5 grid gap-0.5">
					<li v-for="section in sections" :key="section.id"><a :href="`#${section.id}`" class="focus-ring block rounded-lg px-2.5 py-1.5 text-[13.5px] leading-[1.5] text-subtle transition-colors duration-150 hover:bg-pale hover:text-brand">{{ section.title }}</a></li>
				</ol>
			</nav>
			<div class="max-w-[720px]">
				<section v-for="(section, sectionIndex) in sections" :id="section.id" :key="section.id" class="scroll-mt-24" :class="{ 'mt-11 border-t border-line pt-10': sectionIndex > 0 }" :aria-labelledby="`${section.id}-title`">
					<h2 :id="`${section.id}-title`" class="font-display text-[24px] font-semibold tracking-[-.03em]">{{ section.title }}</h2>
					<template v-for="(block, index) in section.body" :key="index">
						<ul v-if="Array.isArray(block)" class="mt-3.5 grid list-disc gap-2 pl-5 marker:text-brand">
							<li v-for="item in block" :key="item" :class="body"><template v-for="(part, i) in parts(item)" :key="i"><mark v-if="part.fill" class="rounded-[4px] border border-dashed border-[#d97706] bg-[#fef3c7] px-1.5 py-px font-semibold text-[#78350f] box-decoration-clone">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></li>
						</ul>
						<p v-else class="mt-3.5" :class="body"><template v-for="(part, i) in parts(block)" :key="i"><mark v-if="part.fill" class="rounded-[4px] border border-dashed border-[#d97706] bg-[#fef3c7] px-1.5 py-px font-semibold text-[#78350f] box-decoration-clone">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></p>
					</template>
				</section>
				<slot />
			</div>
		</div>
	</div>
</template>
