<script setup>
defineProps({ title: { type: String, required: true }, body: String, mascot: String });
</script>
<template>
    <section class="closing-band">
        <div class="public-container closing-inner">
            <img v-if="mascot" :src="`/nextward-assets/mascots/${mascot}.svg`" alt="" width="768" height="768" loading="lazy" class="closing-art" />
            <div class="closing-copy">
                <h2 class="section-title">{{ title }}</h2>
                <p v-if="body" class="mt-3 leading-7">{{ body }}</p>
            </div>
            <div class="flex flex-wrap gap-3"><slot /></div>
        </div>
    </section>
</template>
