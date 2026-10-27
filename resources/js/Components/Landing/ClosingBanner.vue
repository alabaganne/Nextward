<script setup>
defineProps({ title: { type: String, required: true }, body: String, mascot: String });
</script>
<template>
    <section class="closing-band relative [&_.focus-ring:focus-visible]:outline-white">
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-pale" aria-hidden="true"></div>
        <div class="public-container relative">
            <div class="relative overflow-hidden line-art rounded-card bg-brand px-8 py-20 text-center text-white max-[768px]:px-6 max-[768px]:py-14">
                <img v-if="mascot" :src="`/nextward-assets/mascots/${mascot}.svg`" alt="" width="768" height="768" loading="lazy" class="pointer-events-none absolute bottom-0 left-6 hidden size-[160px] min-[1100px]:block" />
                <h2 class="section-title mx-auto max-w-[640px] text-white">{{ title }}</h2>
                <p v-if="body" class="mx-auto mt-4 max-w-[560px] text-[17px] leading-[1.7] text-blue-50">{{ body }}</p>
                <div class="mt-9 flex flex-wrap justify-center gap-4"><slot /></div>
            </div>
        </div>
    </section>
</template>
