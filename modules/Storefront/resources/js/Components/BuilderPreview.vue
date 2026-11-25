<script setup lang="ts">
import FullscreenDialog from '@admin/Components/FullscreenDialog.vue';
import { HttpError, postJson } from '@admin/http';
import { t } from '@admin/i18n';
import { secondaryButton } from '@admin/styles';
import { computed, ref, watch } from 'vue';
import type { BuilderBlock } from '../builderFields';

const props = defineProps<{ url: string; blocks: BuilderBlock[] }>();
const open = defineModel<boolean>('open', { default: false });
const html = ref('');
const error = ref('');
const loading = ref(false);
const width = ref(390);
const devices = computed(() => [
    { width: 390, label: t('storefront::admin.builder.mobile') },
    { width: 768, label: t('storefront::admin.builder.tablet') },
    { width: 1280, label: t('storefront::admin.builder.desktop') },
]);

async function refresh(): Promise<void> {
    if (loading.value) return;
    loading.value = true;
    error.value = '';
    html.value = '';
    try {
        const result = await postJson<{ html: string }>(props.url, { blocks: props.blocks });
        html.value = result.html;
    } catch (failure) {
        error.value = failure instanceof HttpError ? Object.values(failure.errors).flat()[0] ?? failure.message : t('storefront::admin.builder.preview_failed');
    } finally {
        loading.value = false;
    }
}

watch(open, (value) => { if (value) void refresh(); });
</script>

<template>
    <FullscreenDialog v-model:open="open" :title="t('storefront::admin.builder.responsive_preview')" :subtitle="t('storefront::admin.builder.preview_help')">
        <div class="flex flex-wrap items-end gap-3 border-b border-slate-200 bg-white p-3">
            <div class="flex flex-wrap gap-1">
                <button v-for="device in devices" :key="device.width" type="button" :class="[secondaryButton, width === device.width ? 'border-slate-900 text-slate-900' : '']" :aria-pressed="width === device.width" @click="width = device.width">
                    {{ device.label }}
                </button>
            </div>
        </div>
        <div class="min-h-0 flex-1 overflow-auto bg-slate-200 p-3">
            <p v-if="error" class="p-3 text-sm text-red-700" role="alert">{{ error }}</p>
            <p v-else-if="loading" class="p-3 text-sm text-slate-600" role="status">{{ t('storefront::admin.builder.preview_loading') }}</p>
            <iframe v-else-if="html" data-builder-preview-frame class="mx-auto block h-full min-h-96 max-w-none bg-white shadow" :style="{ width: `${width}px` }" :title="t('storefront::admin.builder.responsive_preview')" sandbox="" :srcdoc="html" />
        </div>
    </FullscreenDialog>
</template>
