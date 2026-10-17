<script setup>
import Sparkle from '@/Shared/Sparkle.vue';
defineProps({ titleId: { type: String, required: true }, label: String, lead: String, note: String, mascots: { type: Array, default: () => [] } });
</script>
<template>
    <section :aria-labelledby="titleId" class="hero-dark">
        <div class="public-container hero-grid hero-pad has-overlap">
            <div>
                <p class="landing-label">{{ label }}</p>
                <h1 :id="titleId" class="hero-title"><slot /></h1>
                <p class="hero-lead">{{ lead }}</p>
                <div v-if="$slots.actions" class="hero-buttons"><slot name="actions" /></div>
                <p v-if="note" class="hero-note flex items-center gap-2"><Icon name="check-circle" class="h-4 w-4 shrink-0 text-blue-300" />{{ note }}</p>
            </div>
            <figure class="hero-stage" :class="{ 'is-duo': mascots.length > 1 }">
                <img v-for="name in mascots" :key="name" :src="`/nextward-assets/mascots/${name}.webp`" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                <Sparkle style="top: 10%; left: 12%; --i: 0" />
                <Sparkle class="is-sky" style="top: 4%; right: 18%; width: 16px; height: 16px; --i: 1" />
                <Sparkle style="bottom: 30%; right: 2%; width: 14px; height: 14px; --i: 2" />
            </figure>
        </div>
    </section>
</template>
