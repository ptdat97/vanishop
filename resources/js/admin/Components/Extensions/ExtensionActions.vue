<script setup lang="ts">
import { confirmAction, promptFields } from '@admin/confirm';
import { secondaryButton } from '@admin/styles';
import type { ExtensionAction } from '@admin/types';
import { router } from '@inertiajs/vue3';

/** Nút thao tác hàng loạt (plugin hoặc module Core); server kiểm tra quyền. `method: 'get'` mở trang/tải file. */
const props = defineProps<{ actions: ExtensionAction[]; ids: number[] }>();

async function run(action: ExtensionAction): Promise<void> {
    if (props.ids.length === 0) {
        return;
    }
    let values: Record<string, string> = {};
    if (action.fields?.length) {
        const result = await promptFields({ title: action.label, message: `Áp dụng cho ${props.ids.length} mục đã chọn.`, confirmLabel: action.label, fields: action.fields });
        if (result === false) return;
        values = result;
    } else if (action.confirm && (await confirmAction({ title: `${action.label}?`, message: `Áp dụng cho ${props.ids.length} mục đã chọn.` })) === false) {
        return;
    }
    if (action.method === 'get') {
        // In phiếu / xuất file: trình duyệt mở hoặc tải trực tiếp (không qua Inertia).
        const query = new URLSearchParams(props.ids.map((id) => ['ids[]', String(id)]));
        const url = `${action.url}${action.url.includes('?') ? '&' : '?'}${query}`;
        if (action.target === '_blank') {
            window.open(url, '_blank', 'noopener');
        } else {
            window.location.href = url;
        }
        return;
    }
    router.post(action.url, { ...(action.data ?? {}), ...values, ids: props.ids }, { preserveScroll: true });
}
</script>

<template>
    <button v-for="action in actions" :key="action.key" type="button" :class="secondaryButton" :disabled="ids.length === 0" :data-extension-action="action.key" @click="run(action)">
        {{ action.label }}
    </button>
</template>
