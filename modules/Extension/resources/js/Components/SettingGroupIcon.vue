<script setup lang="ts">
import { computed } from 'vue';

/** Biểu tượng của nhóm Cài đặt hệ thống (SettingGroup) — SVG nội tuyến, nét 1.8 như icon của Admin. */
const props = defineProps<{ name: string }>();

const paths: Record<string, string[]> = {
    store: ['M3 9l1.5-5h15L21 9', 'M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9z', 'M5 13v7h14v-7', 'M10 20v-4h4v4'],
    appearance: ['M12 3a9 9 0 1 0 0 18c1.1 0 1.5-.8 1.5-1.5 0-.9-.7-1.3-.7-2.2 0-.8.7-1.3 1.5-1.3H16a5 5 0 0 0 5-5c0-4.4-4-8-9-8z', 'M7.5 11.5h.01', 'M10.5 7.5h.01', 'M15.5 8.5h.01'],
    seo: ['M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14z', 'M20 20l-4-4'],
    sales: ['M6 3h12v18l-3-2-3 2-3-2-3 2V3z', 'M9 8h6', 'M9 12h6', 'M9 16h3'],
    inventory: ['M3 8l9-5 9 5v12H3V8z', 'M7 20v-8h10v8', 'M7 16h10'],
    orders: ['M4 7l8-4 8 4v10l-8 4-8-4V7z', 'M4 7l8 4 8-4', 'M12 11v10'],
    mail: ['M3 6h18v12H3V6z', 'M3 7l9 6 9-6'],
    other: ['M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z', 'M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z'],
};
const shape = computed(() => paths[props.name] ?? paths.other);
</script>

<template>
    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path v-for="(d, index) in shape" :key="index" :d="d" />
    </svg>
</template>
