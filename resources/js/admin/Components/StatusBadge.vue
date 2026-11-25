<script setup lang="ts">
/** Nhãn trạng thái. Chữ do server dịch sẵn; trang chỉ chọn tông màu. */
withDefaults(defineProps<{ label: string; tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' }>(), { tone: 'neutral' });

const tones = {
    neutral: 'border-slate-200 bg-slate-50 text-slate-700',
    info: 'border-indigo-200 bg-indigo-50 text-indigo-700',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    danger: 'border-red-200 bg-red-50 text-red-700',
};
</script>

<template>
    <span class="inline-flex items-center rounded-sm border px-2 py-0.5 text-[11px] font-medium tracking-[0.04em] whitespace-nowrap uppercase" :class="tones[tone]">{{ label }}</span>
</template>
