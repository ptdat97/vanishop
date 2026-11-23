<script setup lang="ts">
import { t } from '@admin/i18n';
import { confirmAction, promptFields } from '@admin/confirm';
import { HttpError, postJson } from '@admin/http';
import { secondaryButton } from '@admin/styles';
import type { ExtensionAction } from '@admin/types';
import { router } from '@inertiajs/vue3';

/** Nút thao tác hàng loạt (plugin hoặc module Core); server kiểm tra quyền. `method: 'get'` mở trang/tải file. */
const props = withDefaults(defineProps<{ actions: ExtensionAction[]; ids: number[]; selectionDescription?: string }>(), {
    selectionDescription: undefined,
});
// Dịch mô tả mặc định lúc chạy (giá trị mặc định của prop chạy lúc import — chưa có bản dịch).
const selection = (): string => props.selectionDescription ?? t('shared::admin.extensions_extension_actions.selected_items');

async function run(action: ExtensionAction): Promise<void> {
    if (props.ids.length === 0) {
        return;
    }
    const ids = [...props.ids];
    let values: Record<string, string> = {};
    if (action.fields?.length) {
        const result = await promptFields({ title: action.label, message: t('shared::admin.extensions_extension_actions.applies_count_selection_description', { count: ids.length, selection_description: selection() }), confirmLabel: action.preview_url && !action.confirm ? t('shared::admin.extensions_extension_actions.preview') : action.label, fields: action.fields });
        if (result === false) return;
        values = result;
    } else if (action.confirm && (await confirmAction({ title: `${action.label}?`, message: t('shared::admin.extensions_extension_actions.applies_count_selection_description', { count: ids.length, selection_description: selection() }) })) === false) {
        return;
    }
    const payload = { ...(action.data ?? {}), ...values, ids };
    if (action.preview_url) {
        // Xem trước (A5): server tính trên đúng dữ liệu sẽ gửi, chưa ghi gì; người dùng xác nhận lần nữa.
        let preview: { message: string; details?: string[]; warning?: boolean; can_apply?: boolean };
        try {
            preview = await postJson(action.preview_url, payload);
        } catch (error) {
            await confirmAction({ title: action.label, message: error instanceof HttpError ? error.message : t('shared::admin.extensions_extension_actions.could_not_preview_try_again'), confirmLabel: t('shared::admin.extensions_extension_actions.close') });
            return;
        }
        if (preview.can_apply === false) {
            await confirmAction({ title: action.label, message: preview.message, details: preview.details, confirmLabel: t('shared::admin.extensions_extension_actions.close') });
            return;
        }
        if ((await confirmAction({ title: `${action.label}?`, message: preview.message, details: preview.details, confirmLabel: action.label, tone: preview.warning ? 'danger' : 'primary' })) === false) {
            return;
        }
    }
    if (action.method === 'get') {
        // In phiếu / xuất file: trình duyệt mở hoặc tải trực tiếp (không qua Inertia).
        const query = new URLSearchParams(ids.map((id) => ['ids[]', String(id)]));
        const url = `${action.url}${action.url.includes('?') ? '&' : '?'}${query}`;
        if (action.target === '_blank') {
            window.open(url, '_blank', 'noopener');
        } else {
            window.location.href = url;
        }
        return;
    }
    router.post(action.url, payload, { preserveScroll: true });
}
</script>

<template>
    <template v-for="action in actions" :key="action.key">
        <slot :name="`action-${action.key}`" :action="action">
            <button type="button" :class="secondaryButton" :disabled="ids.length === 0" :data-extension-action="action.key" @click="run(action)">
                {{ action.label }}
            </button>
        </slot>
    </template>
</template>
