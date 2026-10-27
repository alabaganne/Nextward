<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Coins, Gift } from '@lucide/vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const billing = computed(() => page.props.billing || {});
const credits = computed(() => user.value?.credits ?? 0);
const canClaim = computed(() => billing.value.beta && user.value?.email_verified);
const claiming = ref(false);
const claim = () => {
	claiming.value = true;
	router.post(route('billing.beta'), {}, { preserveScroll: true, onFinish: () => (claiming.value = false) });
};
const nextTopUp = computed(() => {
	const now = new Date();
	return new Date(now.getFullYear(), now.getMonth() + 1, 1).toLocaleDateString('en-GB', { day: 'numeric', month: 'long' });
});

// Opens on hover with a mouse, and on click or tap otherwise. A click while hovering keeps it open.
const root = ref(null);
const open = ref(false);
let hovering = false;
let closeTimer = null;
const close = () => {
	clearTimeout(closeTimer);
	open.value = false;
	hovering = false;
};
const enter = (event) => {
	if (event.pointerType !== 'mouse') return;
	clearTimeout(closeTimer);
	hovering = true;
	open.value = true;
};
const leave = (event) => {
	if (event.pointerType === 'mouse' && hovering) closeTimer = setTimeout(close, 150);
};
const toggle = () => {
	if (hovering) hovering = false;
	else open.value = !open.value;
};
const focusOut = (event) => {
	if (!root.value?.contains(event.relatedTarget) && !hovering) close();
};
const clickOutside = (event) => {
	if (root.value && !root.value.contains(event.target)) close();
};
onMounted(() => document.addEventListener('click', clickOutside));
onBeforeUnmount(() => {
	document.removeEventListener('click', clickOutside);
	clearTimeout(closeTimer);
});
</script>

<template>
	<div ref="root" class="relative" @pointerenter="enter" @pointerleave="leave" @focusout="focusOut" @keydown.esc="close">
		<button
			class="flex items-center gap-1.5 h-11 px-3.5 rounded-card text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-brand"
			:class="credits ? 'bg-pale text-brand hover:bg-blue-100' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
			type="button"
			aria-controls="credits-panel"
			:aria-expanded="open"
			:aria-label="`${credits} credit${credits === 1 ? '' : 's'} left`"
			@click="toggle"
		>
			<Coins class="size-4" aria-hidden="true" />
			<span>{{ credits }}</span>
			<span class="max-[680px]:hidden">credit{{ credits === 1 ? '' : 's' }}</span>
		</button>

		<Transition
			enter-active-class="transition duration-200 ease-out"
			enter-from-class="opacity-0 -translate-y-1.5 scale-[.98]"
			enter-to-class="opacity-100 translate-y-0 scale-100"
			leave-active-class="transition duration-200 ease-in"
			leave-from-class="opacity-100 translate-y-0 scale-100"
			leave-to-class="opacity-0 -translate-y-1.5 scale-[.98]"
		>
			<div v-if="open" id="credits-panel" class="absolute right-0 top-full pt-1.5 z-30 origin-top-right max-[680px]:fixed max-[680px]:inset-x-4 max-[680px]:top-[58px]">
				<div class="w-80 max-[680px]:w-auto rounded-card border border-line bg-white p-5">
					<p class="text-[12px] tracking-caps text-brand uppercase">Your credits</p>
					<p class="text-2xl font-medium text-navy mt-1">{{ credits }} credit{{ credits === 1 ? '' : 's' }} left</p>
					<p class="text-[13px] leading-relaxed text-ink-700 mt-3">
						One credit writes a CV and cover letter for one role, translates a resume or makes an uploaded PDF editable (each with {{ billing.rewrites }} rewrites), or fills your profile from a resume. Browsing and applying stay free.
					</p>
					<p v-if="user?.email_verified" class="rounded-card bg-pale text-navy text-[13px] px-3.5 py-2.5 mt-4">{{ billing.monthly }} free credits on {{ nextTopUp }}</p>
					<p v-else class="rounded-card bg-amber-50 text-amber-900 text-[13px] leading-relaxed px-3.5 py-2.5 mt-4">Verify your email to use the AI tools and get {{ billing.monthly }} free credits each month<template v-if="billing.beta">, and free credits whenever you need them during the beta</template>.</p>
					<div v-if="billing.beta" class="rounded-card bg-pale px-3.5 py-3 mt-2.5">
						<p class="flex items-center gap-1.5 text-[13px] font-medium text-navy"><span class="rounded-md bg-navy text-white text-[10.5px] font-medium px-1.5 py-px">Beta</span>Credits are free for now</p>
						<p class="text-[12.5px] leading-relaxed text-muted mt-1.5">Nextward is in beta and payments are still being built. Get {{ billing.beta_credits }} free credits whenever you run low.</p>
					</div>
					<button v-if="canClaim" class="cta cta-primary w-full !text-sm !py-2.5 mt-5 disabled:opacity-60" type="button" :disabled="claiming" @click="claim">
						<Gift class="size-4" aria-hidden="true" />{{ claiming ? 'Adding…' : `Get ${billing.beta_credits} free credits` }}
					</button>
					<Link class="cta w-full !text-sm !py-2.5" :class="canClaim ? 'cta-secondary mt-2.5' : 'cta-primary mt-5'" :href="`${route('billing.index')}#packs`" @click="close">
						Buy more credits
					</Link>
				</div>
			</div>
		</Transition>
	</div>
</template>
