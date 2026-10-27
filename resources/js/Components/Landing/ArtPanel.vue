<script setup>
// A mascot on an ice panel, with a solid block set off behind it.
defineProps({
    src: { type: String, required: true },
    width: { type: Number, default: 768 },
    height: { type: Number, default: 768 },
    tone: { type: String, default: 'brand' },
});
</script>
<template>
    <div class="relative pr-8 pb-8 max-[768px]:pr-5 max-[768px]:pb-5">
        <div class="absolute inset-0 top-8 left-8 rounded-card max-[768px]:top-5 max-[768px]:left-5" :class="tone === 'navy' ? 'bg-navy' : 'bg-brand'" aria-hidden="true"></div>
        <div class="relative grid aspect-[5/4] place-items-center rounded-card bg-pale"><img :src="src" alt="" :width="width" :height="height" loading="lazy" decoding="async" :class="width > height ? 'w-[86%]' : 'w-[68%]'" /></div>
    </div>
</template>
