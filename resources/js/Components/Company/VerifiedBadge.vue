<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
// compact shows only the seal, for cards where the name needs the room.
defineProps({ compact: Boolean, dark: Boolean });
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const tip = computed(() => fr.value ? 'Nous avons trouvé et vérifié le site et les informations de cette entreprise.' : 'We found and checked this company’s website and details.');
</script>

<template>
    <span class="inline-flex shrink-0 items-center gap-1" :class="compact ? '' : ['rounded-full py-0.5 pl-1 pr-2 text-[11px] font-semibold', dark ? 'bg-white/15 text-white' : 'bg-blue-50 text-blue-700']" :title="tip">
        <!-- A solid seal with a white tick: the outline stroke is white too, so it reads as a cut-out edge. -->
        <BadgeCheck :size="compact ? 16 : 15" :stroke-width="2.25" class="shrink-0" :class="dark ? 'fill-white text-navy' : 'fill-blue-600 text-white'" aria-hidden="true" /><span :class="{ 'sr-only': compact }">{{ fr ? 'Vérifiée' : 'Verified' }}</span>
    </span>
</template>
