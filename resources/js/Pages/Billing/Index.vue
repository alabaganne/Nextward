<script setup>
import { h, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'billing' }, () => page) });
const props = defineProps({ balance: Number, drafts_per_credit: Number, gateways: Array, packs: Array, history: Array });
const names = { konnect: 'Konnect (cards, e-DINAR, wallet · TND)', flouci: 'Flouci (wallet, cards · TND)', lemonsqueezy: 'International card or PayPal (USD)' };
const currencyOf = { konnect: 'TND', flouci: 'TND', lemonsqueezy: 'USD' };
const reasons = { welcome: 'Free credits', monthly: 'Free credits for the month', purchase: 'Pack bought', kit: 'CV and letter for a role', resume: 'Resume translation', resume_read: 'Profile filled from a resume', refund: 'Given back', grant: 'Added by Nextward' };
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
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Credits" :description="`Credits pay for the AI tools. One credit writes a CV and cover letter for one role or translates a resume (each with ${drafts_per_credit - 1} rewrites included), or fills your profile from a resume. You get 5 free credits every month. Browsing, applying, and making resumes from your profile stay free.`" />
		<div class="bg-white border border-line rounded-[18px] px-6 py-5 mb-6 flex items-center justify-between gap-4 flex-wrap">
			<div><p class="text-sm text-muted">Your balance</p><p class="font-display text-3xl font-semibold">{{ balance }} credit{{ balance === 1 ? '' : 's' }}</p></div>
		</div>

		<div v-if="!gateways.length || !packs.length" class="rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-900 mb-6">Packs are not on sale yet.</div>
		<template v-else>
			<fieldset class="mb-5">
				<legend class="text-sm font-semibold mb-2">Pay with</legend>
				<div class="flex flex-wrap gap-4 text-sm"><label v-for="name in gateways" :key="name" class="flex items-center gap-2"><input v-model="gateway" type="radio" :value="name"> {{ names[name] }}</label></div>
			</fieldset>
			<div class="grid sm:grid-cols-3 gap-4 mb-8">
				<article v-for="pack in packs" :key="pack.key" class="bg-white border border-line rounded-[18px] p-6 flex flex-col">
					<p class="font-display text-2xl font-semibold">{{ pack.credits }} credits</p>
					<p class="text-muted text-sm mt-1">{{ pack.credits }} credits for the AI tools</p>
					<p class="font-semibold text-lg mt-4">{{ price(pack) || 'Not available with this method' }}</p>
					<button class="cbtn bg-blue-600 text-white hover:bg-blue-700 mt-5 disabled:opacity-60" type="button" :disabled="!price(pack) || busy" @click="buy(pack)">{{ busy === pack.key ? 'Opening…' : 'Buy' }}</button>
				</article>
			</div>
		</template>

		<h2 class="font-display text-lg font-semibold mb-3">History</h2>
		<ul class="bg-white border border-line rounded-[18px] divide-y divide-line-2">
			<li v-for="(row, index) in history" :key="index" class="flex justify-between gap-4 px-6 py-3 text-sm">
				<span>{{ reasons[row.reason] || row.reason }}<span v-if="row.note" class="text-muted"> · {{ row.note }}</span></span>
				<span class="font-semibold" :class="row.amount > 0 ? 'text-[#047857]' : 'text-ink-700'">{{ row.amount > 0 ? '+' : '' }}{{ row.amount }}</span>
			</li>
			<li v-if="!history.length" class="px-6 py-3 text-sm text-muted">Nothing yet.</li>
		</ul>
	</div>
</template>
