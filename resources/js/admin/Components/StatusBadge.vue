<script setup lang="ts">
/** Nhãn trạng thái. Chữ do server dịch sẵn; trang chỉ chọn tông màu. */
withDefaults(defineProps<{ label: string; tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' }>(), { tone: 'neutral' });

const tones = {
    neutral: 'bg-slate-100 text-slate-700',
    info: 'bg-indigo-50 text-indigo-700',
    success: 'bg-emerald-50 text-emerald-700',
    warning: 'bg-amber-50 text-amber-800',
    danger: 'bg-red-50 text-red-700',
};
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap" :class="tones[tone]">{{ label }}</span>
</template>
