<script setup lang="ts">
import { secondaryButton } from '@admin/styles';
import type { ExtensionAction } from '@admin/types';
import { router } from '@inertiajs/vue3';

/** Nút thao tác do plugin khai báo; Core kiểm tra quyền + ghi audit ở server. */
const props = defineProps<{ actions: ExtensionAction[]; ids: number[] }>();

function run(action: ExtensionAction): void {
    if (props.ids.length === 0 || (action.confirm && !confirm(`${action.label}?`))) {
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
