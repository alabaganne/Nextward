<script setup>
defineProps({ title: { type: String, required: true }, body: String, mascot: String });
</script>
<template>
    <section class="closing-band relative overflow-hidden bg-brand text-white [&_.focus-ring:focus-visible]:outline-white">
        <div class="public-container flex flex-wrap items-center justify-between gap-x-10 gap-y-6 py-16 max-[768px]:py-11">
            <img v-if="mascot" :src="`/nextward-assets/mascots/${mascot}.svg`" alt="" width="768" height="768" loading="lazy" class="-my-4 size-[140px] shrink-0 max-[768px]:m-0 max-[768px]:size-[104px]" />
            <div class="max-w-[576px] flex-[1_1_320px]">
                <h2 class="section-title mt-0 text-[length:clamp(28px,3vw,38px)] text-white">{{ title }}</h2>
                <p v-if="body" class="mt-3 leading-7 text-blue-100">{{ body }}</p>
            </div>
            <div class="flex flex-wrap gap-3"><slot /></div>
        </div>
    </section>
</template>
