<script setup lang="ts">
import { confirmState, settleConfirm } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogOverlay, AlertDialogPortal, AlertDialogRoot, AlertDialogTitle } from 'reka-ui';
import { computed, reactive, watch } from 'vue';

/** Hộp xác nhận dùng chung, gắn một lần trong AdminLayout; trang gọi `confirmAction()` hoặc `promptFields()`. */
const values = reactive<Record<string, string>>({});
const options = computed(() => confirmState.options);
const blocked = computed(() => (options.value?.fields ?? []).some((field) => field.required === true && (values[field.key] ?? '').trim() === ''));

watch(
    () => confirmState.open,
    (open) => {
        if (open) {
            for (const key of Object.keys(values)) {
                delete values[key];
            }
            for (const field of options.value?.fields ?? []) {
                values[field.key] = field.value ?? '';
            }
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
        settleConfirm(Object.fromEntries(Object.entries(values).map(([key, value]) => [key, value.trim()])));
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
                    <label v-for="(field, index) in options.fields" :key="field.key" class="mt-4 block text-sm">
                        <span class="mb-1 block font-medium text-slate-700">{{ field.label }}</span>
                        <input
                            v-model="values[field.key]"
                            :class="inputClass"
                            :maxlength="field.maxlength ?? 255"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            :autofocus="index === 0"
                        />
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
