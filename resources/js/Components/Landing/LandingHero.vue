<script setup>
defineProps({
    titleId: { type: String, required: true },
    label: String,
    lead: String,
    note: String,
    mascots: { type: Array, default: () => [] },
    align: { type: String, default: 'center' },
    // Leaves room for a card that the page pulls up over the bottom edge (.hero-overlap).
    overlap: { type: Boolean, default: false },
});
</script>
<template>
    <section :aria-labelledby="titleId" class="hero-dark">
        <div class="public-container relative z-[1]" :class="[align === 'center' && 'text-center', overlap ? 'pt-20 pb-36 max-[768px]:pt-12 max-[768px]:pb-28' : 'py-20 max-[768px]:py-12']">
            <slot name="top" />
            <p v-if="label" class="landing-label" :class="align === 'center' && 'justify-center'">{{ label }}</p>
            <h1 :id="titleId" class="hero-title" :class="align === 'center' && 'mx-auto max-w-[920px]'"><slot /></h1>
            <p v-if="lead" class="hero-lead" :class="align === 'center' && 'mx-auto'">{{ lead }}</p>
            <div v-if="$slots.actions" class="hero-buttons" :class="align === 'center' && 'justify-center'"><slot name="actions" /></div>
            <p v-if="note" class="mt-6 flex items-center gap-2 text-[14px] text-mist-2" :class="align === 'center' && 'justify-center'"><Icon name="check-circle" class="h-4 w-4 shrink-0 text-blue-400" />{{ note }}</p>
        </div>
        <img v-for="(name, index) in mascots" :key="name" :src="`/nextward-assets/mascots/${name}.webp`" alt="" width="900" height="900" fetchpriority="high" decoding="async" class="pointer-events-none absolute bottom-0 hidden w-[210px] min-[1180px]:block min-[1440px]:w-[240px]" :class="index ? 'right-[max(0px,calc(50%-700px))]' : 'left-[max(0px,calc(50%-700px))]'" />
    </section>
</template>
