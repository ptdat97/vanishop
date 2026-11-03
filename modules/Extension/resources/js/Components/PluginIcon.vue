<script setup lang="ts">
/** Icon plugin (manifest `icon`); không có → chữ cái đầu của tên. */
defineProps<{ src: string | null; name: string; size?: 'sm' | 'lg' }>();
</script>

<template>
    <img v-if="src" :src="src" alt="" :class="[size === 'lg' ? 'h-14 w-14' : 'h-9 w-9', 'shrink-0 rounded-md border border-slate-200 bg-white object-contain p-1']" />
    <span v-else :class="[size === 'lg' ? 'h-14 w-14 text-xl' : 'h-9 w-9 text-sm', 'flex shrink-0 items-center justify-center rounded-md bg-slate-100 font-semibold text-slate-500']">
        {{ name.charAt(0).toUpperCase() }}
    </span>
</template>
