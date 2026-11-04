<script setup lang="ts">
import { confirmAction } from '@admin/confirm';
import { secondaryButton } from '@admin/styles';
import type { ExtensionAction } from '@admin/types';
import { router } from '@inertiajs/vue3';

/** Nút thao tác do plugin khai báo; Core kiểm tra quyền + ghi audit ở server. */
const props = defineProps<{ actions: ExtensionAction[]; ids: number[] }>();

async function run(action: ExtensionAction): Promise<void> {
    if (props.ids.length === 0 || (action.confirm && (await confirmAction({ title: `${action.label}?`, message: `Áp dụng cho ${props.ids.length} mục đã chọn.` })) === false)) {
        return;
    }
    router.post(action.url, { ids: props.ids }, { preserveScroll: true });
}
</script>

<template>
    <button v-for="action in actions" :key="action.key" type="button" :class="secondaryButton" :disabled="ids.length === 0" :data-extension-action="action.key" @click="run(action)">
        {{ action.label }}
    </button>
</template>
