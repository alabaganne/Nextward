<script setup>
import Sparkle from '@/Shared/Sparkle.vue';
defineProps({ titleId: { type: String, required: true }, label: String, lead: String, note: String, mascots: { type: Array, default: () => [] } });
</script>
<template>
    <section :aria-labelledby="titleId" class="hero-dark">
        <div class="public-container hero-grid hero-pad pb-34 max-[768px]:pb-26">
            <div>
                <p class="landing-label">{{ label }}</p>
                <h1 :id="titleId" class="hero-title"><slot /></h1>
                <p class="hero-lead">{{ lead }}</p>
                <div v-if="$slots.actions" class="hero-buttons"><slot name="actions" /></div>
                <p v-if="note" class="mt-[18px] flex items-center gap-2 text-[14px] text-mist-2"><Icon name="check-circle" class="h-4 w-4 shrink-0 text-blue-300" />{{ note }}</p>
            </div>
            <figure class="hero-stage" :class="{ 'flex w-[min(100%,540px)] items-end before:inset-[20%_2%_4%] max-[768px]:w-[min(100%,360px)] [&>img]:w-[64%] [&>img+img]:-ml-[24%]': mascots.length > 1 }">
                <img v-for="name in mascots" :key="name" :src="`/nextward-assets/mascots/${name}.webp`" alt="" width="900" height="900" fetchpriority="high" decoding="async" />
                <Sparkle style="top: 10%; left: 12%; --i: 0" />
                <Sparkle class="is-sky" style="top: 4%; right: 18%; width: 16px; height: 16px; --i: 1" />
                <Sparkle style="bottom: 30%; right: 2%; width: 14px; height: 14px; --i: 2" />
            </figure>
        </div>
    </section>
</template>
