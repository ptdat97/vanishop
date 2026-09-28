<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<{ flash?: { success?: string | null }; errors: Record<string, string> }>();
const success = computed(() => page.props.flash?.success);
const generalError = computed(() => page.props.errors?.category ?? page.props.errors?.lock_version);
</script>

<template>
    <div v-if="success" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ success }}</div>
    <div v-if="generalError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ generalError }}</div>
</template>
