<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { MailCheck } from '@lucide/vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const show = computed(() => user.value && user.value.email_verified === false);
const sending = ref(false);
const sent = ref(false);

const resend = () => {
	if (sending.value) return;
	sending.value = true;
	router.post(route('verification.send'), {}, {
		preserveScroll: true,
		onSuccess: () => { sent.value = true; },
		onFinish: () => { sending.value = false; },
	});
};
</script>

<template>
	<div v-if="show" class="flex flex-wrap items-center gap-x-4 gap-y-2 bg-blue-600 text-white px-6 py-3 text-[14px]" role="status">
		<MailCheck :size="18" aria-hidden="true" class="shrink-0" />
		<p class="flex-1 min-w-[220px]">
			<b class="font-medium">Confirm your email.</b>
			We sent a link to {{ user.email }}. You need it to apply, message employers and get alerts.
		</p>
		<span v-if="sent" class="font-medium">New link sent.</span>
		<button v-else type="button" class="font-medium underline underline-offset-4 decoration-white/60 hover:decoration-white focus-visible:outline-2 focus-visible:outline-white focus-visible:outline-offset-2 rounded" :disabled="sending" @click="resend">
			{{ sending ? 'Sending…' : 'Send a new link' }}
		</button>
	</div>
</template>
