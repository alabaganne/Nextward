<script setup>
import { useId } from 'vue';
defineProps({ code: { type: String, required: true } });
const id = useId();
</script>

<template>
	<span class="flag" aria-hidden="true">
		<svg v-if="code === 'fr'" viewBox="0 0 3 2" preserveAspectRatio="xMidYMid slice"><path fill="#002654" d="M0 0h1v2H0z" /><path fill="#fff" d="M1 0h1v2H1z" /><path fill="#ce1126" d="M2 0h1v2H2z" /></svg>
		<svg v-else viewBox="0 0 60 30" preserveAspectRatio="xMidYMid slice">
			<clipPath :id="`${id}-s`"><path d="M0 0v30h60V0z" /></clipPath>
			<clipPath :id="`${id}-t`"><path d="M30 15h30v15zv15H0zH0V0zV0h30z" /></clipPath>
			<g :clip-path="`url(#${id}-s)`">
				<path d="M0 0v30h60V0z" fill="#012169" />
				<path d="M0 0l60 30m0-30L0 30" stroke="#fff" stroke-width="6" />
				<path d="M0 0l60 30m0-30L0 30" :clip-path="`url(#${id}-t)`" stroke="#c8102e" stroke-width="4" />
				<path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10" />
				<path d="M30 0v30M0 15h60" stroke="#c8102e" stroke-width="6" />
			</g>
		</svg>
	</span>
</template>

<style scoped>
.flag { display: inline-flex; flex-shrink: 0; width: 20px; height: 20px; overflow: hidden; border-radius: 50%; outline: 1px solid rgb(10 23 56 / .12); outline-offset: -1px; }
.flag svg { width: 100%; height: 100%; }
</style>
