<script setup lang="ts">
import { confirmState, settleConfirm } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogOverlay, AlertDialogPortal, AlertDialogRoot, AlertDialogTitle } from 'reka-ui';
import { computed, ref, watch } from 'vue';

/** Hộp xác nhận dùng chung, gắn một lần trong AdminLayout; trang gọi `confirmAction()`. */
const reason = ref('');
const options = computed(() => confirmState.options);
const blocked = computed(() => options.value?.reason?.required === true && reason.value.trim() === '');

watch(
    () => confirmState.open,
    (open) => {
        if (open) {
            reason.value = '';
        }
    },
);

function onOpenChange(open: boolean): void {
    if (!open && confirmState.open) {
        settleConfirm(false);
    }
}

function accept(): void {
    if (!blocked.value) {
        settleConfirm(reason.value.trim());
    }
}
</script>

<template>
    <AlertDialogRoot :open="confirmState.open" @update:open="onOpenChange">
        <AlertDialogPortal>
            <AlertDialogOverlay class="fixed inset-0 z-50 bg-slate-900/40" />
            <AlertDialogContent v-if="options" class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white p-5 shadow-xl focus:outline-none">
                <form @submit.prevent="accept">
                    <AlertDialogTitle class="text-base font-semibold text-slate-900">{{ options.title }}</AlertDialogTitle>
                    <AlertDialogDescription v-if="options.message" class="mt-2 text-sm text-slate-600">{{ options.message }}</AlertDialogDescription>
                    <label v-if="options.reason" class="mt-4 block text-sm">
                        <span class="mb-1 block font-medium text-slate-700">{{ options.reason.label }}</span>
                        <input v-model="reason" :class="inputClass" :maxlength="options.reason.maxlength ?? 255" :placeholder="options.reason.placeholder" :required="options.reason.required" />
                    </label>
                    <div class="mt-5 flex justify-end gap-2">
                        <AlertDialogCancel type="button" :class="secondaryButton">Thôi</AlertDialogCancel>
                        <button type="submit" :class="options.tone === 'danger' ? dangerButton : primaryButton" :disabled="blocked">{{ options.confirmLabel ?? 'Đồng ý' }}</button>
                    </div>
                </form>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
