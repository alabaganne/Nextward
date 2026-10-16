<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import EmptyLayout from '@/Layouts/Empty.vue';

defineOptions({ layout: EmptyLayout });

const props = defineProps({ status: { type: Number, default: 500 } });
const page = usePage();
// Unknown URLs skip the middleware, so also read the locale from the path.
const fr = computed(() => page.props.locale === 'fr' || /^\/fr(\/|$|\?)/.test(page.url || ''));
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const user = computed(() => page.props.auth?.user);

const copy = computed(() => ({
	403: { title: text('You do not have access to this page', 'Vous n’avez pas accès à cette page'), text: text('Your account cannot open this page. Go back or head to your dashboard.', 'Votre compte ne peut pas ouvrir cette page. Revenez en arrière ou allez sur votre espace.') },
	404: { title: text('Page not found', 'Page introuvable'), text: text('This page does not exist or was moved. The opportunity may have closed.', 'Cette page n’existe pas ou a été déplacée. L’opportunité est peut-être clôturée.') },
	503: { title: text('Back in a few minutes', 'De retour dans quelques minutes'), text: text('Nextward is being updated. Please try again shortly.', 'Nextward est en cours de mise à jour. Réessayez dans un instant.') },
}[props.status] || { title: text('Something went wrong', 'Une erreur est survenue'), text: text('We could not load this page. Please try again in a moment.', 'Impossible de charger cette page. Réessayez dans un instant.') }));
</script>

<template>
	<Head :title="`${copy.title} | Nextward`" />
	<main class="min-h-dvh grid place-items-center bg-paper-2 px-5 font-body text-ink" :lang="fr ? 'fr' : 'en'">
		<div class="max-w-[440px] text-center">
			<img src="/brand/nextward/symbol-192.png" alt="Nextward" class="size-14 mx-auto mb-6" width="56" height="56">
			<p class="font-display text-sm font-semibold text-blue-700">{{ text('Error', 'Erreur') }} {{ status }}</p>
			<h1 class="font-display text-[28px] font-semibold tracking-[-.02em] leading-tight mt-2">{{ copy.title }}</h1>
			<p class="text-muted text-[15px] mt-3">{{ copy.text }}</p>
			<div class="flex justify-center gap-3 mt-7 flex-wrap">
				<a v-if="user" href="/dashboard" class="cbtn cbtn-primary h-11 !py-0">{{ text('Go to dashboard', 'Aller à mon espace') }}</a>
				<a v-else :href="prefix || '/'" class="cbtn cbtn-primary h-11 !py-0">{{ text('Go to home page', 'Retour à l’accueil') }}</a>
				<a :href="`${prefix}/opportunities`" class="cbtn cbtn-secondary h-11 !py-0">{{ text('Browse opportunities', 'Voir les opportunités') }}</a>
			</div>
		</div>
	</main>
</template>
