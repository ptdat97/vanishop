<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-3 border-b border-slate-200 pb-5">
        <div class="min-w-0">
            <h1 class="font-display text-2xl leading-tight tracking-[0.01em] text-slate-900">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1.5 max-w-3xl text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-2"><slot /></div>
    </div>
</template>
