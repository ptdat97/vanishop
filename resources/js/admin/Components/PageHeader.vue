<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <div class="flex gap-2"><slot /></div>
    </div>
</template>
