<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
// compact shows only the icon, for cards where the name needs the room.
defineProps({ compact: Boolean, dark: Boolean });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const tip = computed(() => fr.value ? 'Nous avons trouvé et vérifié le site et les informations de cette entreprise.' : 'We found and checked this company’s website and details.');
</script>

<template>
    <span class="inline-flex shrink-0 items-center gap-1 rounded-full text-[11px] font-semibold" :class="[compact ? 'p-0.5' : 'px-2 py-0.5', dark ? 'bg-white/15 text-white' : 'bg-blue-50 text-blue-700']" :title="tip">
        <BadgeCheck :size="compact ? 14 : 13" aria-hidden="true" /><span :class="{ 'sr-only': compact }">{{ fr ? 'Vérifiée' : 'Verified' }}</span>
    </span>
</template>
