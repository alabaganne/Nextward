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
const parts = (line) => line.split(/\[\[(.+?)\]\]/).map((text, index) => ({ text, fill: index % 2 === 1 }));
</script>

<template>
	<div class="landing">
		<section aria-labelledby="legal-title" class="hero-dark">
			<div class="public-container legal-hero">
				<p class="landing-label"><span class="label-dot" aria-hidden="true"></span>{{ label }}</p>
				<h1 id="legal-title" class="hero-title">{{ title }}</h1>
				<p class="hero-lead">{{ lead }}</p>
				<p class="legal-updated">{{ updated }}</p>
			</div>
		</section>

		<div class="public-container legal-grid">
			<nav :aria-label="tocLabel" class="legal-toc">
				<p>{{ tocLabel }}</p>
				<ol>
					<li v-for="section in sections" :key="section.id"><a :href="`#${section.id}`" class="focus-ring">{{ section.title }}</a></li>
				</ol>
			</nav>
			<div class="legal-body">
				<section v-for="section in sections" :id="section.id" :key="section.id" :aria-labelledby="`${section.id}-title`">
					<h2 :id="`${section.id}-title`">{{ section.title }}</h2>
					<template v-for="(block, index) in section.body" :key="index">
						<ul v-if="Array.isArray(block)">
							<li v-for="item in block" :key="item"><template v-for="(part, i) in parts(item)" :key="i"><mark v-if="part.fill" class="legal-fill">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></li>
						</ul>
						<p v-else><template v-for="(part, i) in parts(block)" :key="i"><mark v-if="part.fill" class="legal-fill">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></p>
					</template>
				</section>
				<slot />
			</div>
		</div>
	</div>
</template>

<style scoped>
.legal-hero { padding-block: 64px 72px; }
.legal-hero .hero-title { font-size: clamp(40px, 4.6vw, 64px); }
.legal-hero .hero-lead { max-width: 640px; }
.legal-updated { margin-top: 20px; font-size: 13px; color: #a9b4c8; }
.legal-grid { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 64px; padding-block: 64px 96px; }
.legal-toc { position: sticky; top: 104px; align-self: start; }
.legal-toc p { font-size: 11px; font-weight: 600; letter-spacing: .15em; text-transform: uppercase; color: #67707e; }
.legal-toc ol { margin-top: 14px; display: grid; gap: 2px; border-left: 1px solid #0a17381a; }
.legal-toc a { display: block; padding: 6px 0 6px 16px; border-radius: 2px; font-size: 13.5px; line-height: 1.5; color: #586171; transition: color 180ms; }
.legal-toc a:hover { color: var(--color-brand); }
.legal-body { max-width: 720px; }
.legal-body section { scroll-margin-top: 96px; }
.legal-body section + section { margin-top: 44px; padding-top: 40px; border-top: 1px solid #0a17381a; }
.legal-body h2 { font-family: var(--font-display); font-size: 24px; font-weight: 600; letter-spacing: -.03em; }
.legal-body p, .legal-body li { font-size: 15.5px; line-height: 1.8; color: #334155; }
.legal-body p { margin-top: 14px; }
.legal-body ul { margin-top: 14px; display: grid; gap: 8px; padding-left: 20px; list-style: disc; }
.legal-body li::marker { color: var(--color-brand); }
.legal-fill { padding: 1px 6px; border: 1px dashed #d97706; border-radius: 4px; background: #fef3c7; color: #78350f; font-weight: 600; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
@media (max-width: 899px) {
	.legal-grid { grid-template-columns: 1fr; gap: 40px; padding-block: 44px 72px; }
	.legal-toc { position: static; }
	.legal-hero { padding-block: 44px 52px; }
}
</style>
