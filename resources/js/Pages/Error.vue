<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import EmptyLayout from '@/Layouts/Empty.vue';

defineOptions({ layout: EmptyLayout });

const props = defineProps({ status: { type: Number, default: 500 } });

const copy = computed(() => ({
	403: { title: 'You do not have access to this page', text: 'Your account cannot open this page. Go back or head to your dashboard.' },
	404: { title: 'Page not found', text: 'This page does not exist or was moved. The opportunity may have closed.' },
	503: { title: 'Back in a few minutes', text: 'Nextward is being updated. Please try again shortly.' },
}[props.status] || { title: 'Something went wrong', text: 'We could not load this page. Please try again in a moment.' }));
</script>

<template>
	<Head :title="`${copy.title} | Nextward`" />
	<main class="min-h-dvh grid place-items-center bg-paper-2 px-5 font-body text-ink">
		<div class="max-w-[440px] text-center">
			<img src="/brand/nextward/symbol-192.png" alt="Nextward" class="size-14 mx-auto mb-6" width="56" height="56">
			<p class="font-display text-sm font-semibold text-blue-700">Error {{ status }}</p>
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight mt-2">{{ copy.title }}</h1>
			<p class="text-muted text-[15px] mt-3">{{ copy.text }}</p>
			<div class="flex justify-center gap-3 mt-7 flex-wrap">
				<a href="/dashboard" class="cbtn cbtn-primary h-11 !py-0">Go to dashboard</a>
				<a href="/opportunities" class="cbtn cbtn-secondary h-11 !py-0">Browse opportunities</a>
			</div>
		</div>
	</main>
</template>
