<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Gift, Lock } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: AppShell });
const props = defineProps({ balance: Number, drafts_per_credit: Number, beta: Boolean, beta_credits: Number, gateways: Array, packs: Array, history: Array });
const names = { konnect: 'Konnect (cards, e-DINAR, wallet · TND)', flouci: 'Flouci (wallet, cards · TND)', lemonsqueezy: 'International card or PayPal (USD)' };
const currencyOf = { konnect: 'TND', flouci: 'TND', lemonsqueezy: 'USD' };
const reasons = { welcome: 'Free credits', monthly: 'Free credits for the month', beta: 'Free beta credits', purchase: 'Pack bought', kit: 'CV and letter for a role', resume: 'Resume translation', resume_read: 'Profile filled from a resume', refund: 'Given back', grant: 'Added by Nextward' };
const gateway = ref(props.gateways[0] || null);
const busy = ref(null);
const price = (pack) => {
	const currency = currencyOf[gateway.value];
	const amount = pack.prices[currency];
	if (!amount) return null;
	return currency === 'TND' ? `${(amount / 1000).toFixed(3)} TND` : `$${(amount / 100).toFixed(2)}`;
};
const buy = (pack) => {
	busy.value = pack.key;
	router.post(route('billing.checkout'), { pack: pack.key, gateway: gateway.value }, { onFinish: () => (busy.value = null) });
};
const claiming = ref(false);
const claim = () => {
	claiming.value = true;
	router.post(route('billing.beta'), {}, { preserveScroll: true, onFinish: () => (claiming.value = false) });
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Credits" :description="`Credits pay for the AI tools. One credit writes a CV and cover letter for one role, translates a resume or makes an uploaded PDF editable (each with ${drafts_per_credit - 1} rewrites included), or fills your profile from a resume. You get 5 free credits every month. Browsing, applying, and making resumes from your profile stay free.`" />

		<div class="grid gap-5 mb-12" :class="beta ? 'md:grid-cols-[minmax(0,1fr)_minmax(0,2.2fr)]' : ''">
			<div class="bg-white border border-line rounded-[18px] px-7 py-6 flex flex-col justify-center">
				<p class="text-sm text-muted">Your balance</p>
				<p class="font-display text-3xl font-semibold mt-1">{{ balance }} credit{{ balance === 1 ? '' : 's' }}</p>
			</div>

			<section v-if="beta" class="bg-blue-50 rounded-[18px] px-7 py-6 flex items-center justify-between gap-6 flex-wrap" aria-labelledby="beta-title">
				<div class="min-w-0 flex-1 basis-[300px]">
					<p class="flex items-center gap-2.5">
						<span class="rounded-full bg-navy-800 text-white text-[11px] font-bold px-2 py-0.5">Beta</span>
						<span id="beta-title" class="font-display text-[17px] font-semibold text-ink">Free credits during the beta</span>
					</p>
					<p class="text-sm leading-relaxed text-ink-700 mt-2.5">Nextward is in beta and payments are still being built. Until they open, get {{ beta_credits }} free credits whenever you need more.</p>
				</div>
				<button class="cbtn cbtn-primary !text-sm !py-3 !px-5 disabled:opacity-60" type="button" :disabled="claiming" @click="claim">
					<Gift class="size-4" aria-hidden="true" />{{ claiming ? 'Adding…' : `Get ${beta_credits} free credits` }}
				</button>
			</section>
		</div>

		<section id="packs" class="mb-12 scroll-mt-20" aria-labelledby="packs-title">
			<h2 id="packs-title" class="font-display text-lg font-semibold mb-4">Buy credits</h2>

			<div v-if="!beta && (!gateways.length || !packs.length)" class="rounded-xl bg-blue-50 px-5 py-4 text-sm text-blue-900">Packs are not on sale yet.</div>
			<div v-else class="relative" :class="beta ? 'rounded-[18px] border border-line bg-white overflow-hidden' : ''">
				<!-- During the beta the packs are shown greyed out under a lock. -->
				<div :class="beta ? 'p-7 max-[680px]:p-5 grayscale opacity-40 pointer-events-none select-none' : ''" :inert="beta || null" :aria-hidden="beta || null">
					<fieldset class="mb-6">
						<legend class="text-sm font-semibold mb-3">Pay with</legend>
						<div class="flex flex-wrap gap-x-6 gap-y-3 text-sm"><label v-for="name in gateways" :key="name" class="flex items-center gap-2"><input v-model="gateway" type="radio" :value="name" :disabled="beta"> {{ names[name] }}</label></div>
					</fieldset>
					<div class="grid sm:grid-cols-3 gap-5">
						<article v-for="pack in packs" :key="pack.key" class="bg-white border border-line rounded-[18px] p-7 flex flex-col">
							<p class="font-display text-2xl font-semibold">{{ pack.credits }} credits</p>
							<p class="text-muted text-sm mt-1.5">{{ pack.credits }} credits for the AI tools</p>
							<p class="font-semibold text-lg mt-5">{{ price(pack) || (beta ? 'Price coming soon' : 'Not available with this method') }}</p>
							<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 mt-6 disabled:opacity-60" type="button" :disabled="beta || !price(pack) || busy" @click="buy(pack)">{{ busy === pack.key ? 'Opening…' : 'Buy' }}</button>
						</article>
					</div>
				</div>

				<div v-if="beta" class="absolute inset-0 bg-paper-3/70 grid place-items-center p-6 max-sm:content-start max-sm:pt-24">
					<div class="bg-white border border-line rounded-[18px] px-8 py-8 max-[680px]:px-6 max-w-md text-center">
						<span class="mx-auto size-14 rounded-2xl bg-paper-2 border border-line grid place-items-center text-ink-700"><Lock class="size-6" aria-hidden="true" /></span>
						<p class="font-display text-lg font-semibold text-ink mt-5">Payments are coming soon</p>
						<p class="text-sm leading-relaxed text-muted mt-2">We're still setting up card and wallet payments. Packs open after the beta; until then, your credits are free.</p>
					</div>
				</div>
			</div>
		</section>

		<h2 class="font-display text-lg font-semibold mb-4">History</h2>
		<ul class="bg-white border border-line rounded-[18px] divide-y divide-line-2">
			<li v-for="(row, index) in history" :key="index" class="flex justify-between gap-4 px-7 py-4 text-sm">
				<span>{{ reasons[row.reason] || row.reason }}<span v-if="row.note" class="text-muted"> · {{ row.note }}</span></span>
				<span class="font-semibold" :class="row.amount > 0 ? 'text-[#047857]' : 'text-ink-700'">{{ row.amount > 0 ? '+' : '' }}{{ row.amount }}</span>
			</li>
			<li v-if="!history.length" class="px-7 py-4 text-sm text-muted">Nothing yet.</li>
		</ul>
	</div>
</template>
