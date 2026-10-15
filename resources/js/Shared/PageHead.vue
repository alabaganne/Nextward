<script setup>
import { computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
const page = usePage();
onMounted(() => watch(() => page.props.locale, (locale) => { document.documentElement.lang = locale || 'en'; }, { immediate: true }));
const seo = computed(() => page.props.seo || { title: 'Nextward', robots: 'noindex, nofollow' });
const json = computed(() => JSON.stringify(seo.value.structured_data || []).replace(/</g, '\\u003c'));
</script>
<template>
    <Head :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description || ''" />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link v-if="seo.canonical" head-key="canonical" rel="canonical" :href="seo.canonical" />
        <link v-for="alternate in seo.alternates || []" :key="alternate.lang" :head-key="`language-${alternate.lang}`" rel="alternate" :hreflang="alternate.lang" :href="alternate.url" />
        <meta head-key="og-site" property="og:site_name" content="Nextward" />
        <meta head-key="og-title" property="og:title" :content="seo.title" />
        <meta head-key="og-description" property="og:description" :content="seo.description || ''" />
        <meta head-key="og-type" property="og:type" :content="seo.type || 'website'" />
        <meta head-key="og-url" property="og:url" :content="seo.canonical || ''" />
        <meta v-if="seo.locale" head-key="og-locale" property="og:locale" :content="seo.locale" />
        <meta v-if="seo.image" head-key="og-image" property="og:image" :content="seo.image" />
        <meta v-if="seo.image" head-key="og-image-alt" property="og:image:alt" :content="seo.image_alt || seo.title" />
        <meta v-if="seo.published_time" head-key="article-published" property="article:published_time" :content="seo.published_time" />
        <meta v-if="seo.modified_time" head-key="article-modified" property="article:modified_time" :content="seo.modified_time" />
        <meta head-key="twitter-card" name="twitter:card" :content="seo.image ? 'summary_large_image' : 'summary'" />
        <meta head-key="twitter-title" name="twitter:title" :content="seo.title" />
        <meta head-key="twitter-description" name="twitter:description" :content="seo.description || ''" />
        <meta v-if="seo.image" head-key="twitter-image" name="twitter:image" :content="seo.image" />
        <component :is="'script'" v-if="seo.structured_data?.length" head-key="structured-data" type="application/ld+json">{{ json }}</component>
    </Head>
</template>
