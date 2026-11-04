<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Lỗi chung của form (không gắn với trường nào). Thông báo thành công hiện bằng toast (`Toaster` trong AdminLayout). */
const page = usePage<{ errors: Record<string, string> }>();
const generalError = computed(() => page.props.errors?.category ?? page.props.errors?.lock_version);
</script>

<template>
    <div v-if="generalError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ generalError }}</div>
</template>
