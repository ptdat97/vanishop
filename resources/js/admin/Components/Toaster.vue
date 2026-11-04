<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ToastClose, ToastDescription, ToastProvider, ToastRoot, ToastViewport } from 'reka-ui';
import { ref, watch } from 'vue';

/** Thông báo thành công (`flash.success`) dạng toast; gắn một lần trong AdminLayout. */
type Toast = { id: number; message: string; open: boolean };

const page = usePage<{ flash?: { success?: string | null } }>();
const toasts = ref<Toast[]>([]);
let nextId = 0;

// Mỗi phản hồi tạo object flash mới → cùng một thông báo lặp lại (vd. thêm hai ghi chú) vẫn hiện lại.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toasts.value = [...toasts.value.filter((toast) => toast.open), { id: ++nextId, message: flash.success, open: true }];
        }
    },
    { immediate: true },
);
</script>

<template>
    <ToastProvider :duration="4000" label="Thông báo">
        <ToastRoot
            v-for="toast in toasts"
            :key="toast.id"
            v-model:open="toast.open"
            class="flex items-start gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
        >
            <ToastDescription class="flex-1">{{ toast.message }}</ToastDescription>
            <ToastClose class="text-emerald-700 hover:text-emerald-900" aria-label="Đóng">✕</ToastClose>
        </ToastRoot>
        <ToastViewport class="fixed right-4 bottom-4 z-50 flex w-96 max-w-[calc(100vw-2rem)] flex-col gap-2 outline-none" />
    </ToastProvider>
</template>
