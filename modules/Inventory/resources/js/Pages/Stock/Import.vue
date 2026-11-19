<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Line = { line: number; sku: string; location: string; current: number | null; new: number | null; status: string };

/** Xem trước nhập tồn CSV: chỉ dòng "Thay đổi" được ghi (như kiểm kê); chưa ghi gì cho tới khi bấm Áp dụng. */
const props = defineProps<{
    preview: { token: string | null; file: string; lines: Line[]; hidden_lines: number; summary: Record<string, number> };
    statusLabels: Record<string, string>;
    urls: { back: string; apply: string | null };
}>();

const columns = [
    { key: 'line', label: t('inventory::admin.stock_import.lines') },
    { key: 'sku', label: 'SKU', class: 'font-mono' },
    { key: 'location', label: 'Kho' },
    { key: 'current', label: t('inventory::admin.stock_import.current'), align: 'right' as const },
    { key: 'new', label: t('inventory::admin.stock_import.new'), align: 'right' as const },
    { key: 'status', label: t('inventory::admin.stock_import.result') },
];
const problem = (status: string) => !['change', 'same'].includes(status);
const onlyRelevant = ref(true);
const shown = computed(() => (onlyRelevant.value ? props.preview.lines.filter((line) => line.status !== 'same') : props.preview.lines).slice(0, 1000));
const applying = ref(false);

function apply(): void {
    if (!props.urls.apply) return;
    applying.value = true;
    router.post(props.urls.apply, {}, { onFinish: () => (applying.value = false) });
}
</script>

<template>
    <Head :title="t('inventory::admin.stock_import.stock_import_preview')" />
    <PageHeader :title="t('inventory::admin.stock_import.preview_file', { file: preview.file })" :subtitle="t('inventory::admin.stock_import.nothing_written_yet_check_then')">
        <Link :href="urls.back" :class="secondaryButton">{{ t('inventory::admin.stock_import.back') }}</Link>
        <button v-if="urls.apply" type="button" :class="primaryButton" :disabled="applying" data-apply @click="apply">{{ t('inventory::admin.stock_import.apply_change_changes', { change: preview.summary.change ?? 0 }) }}</button>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span v-for="(count, status) in preview.summary" :key="status" class="rounded-full px-3 py-1" :class="problem(String(status)) ? 'bg-red-50 text-red-700' : 'bg-slate-100'">
            {{ statusLabels[status] ?? status }}: {{ count }}
        </span>
    </div>
    <p v-if="!urls.apply" class="mb-4 text-sm text-slate-600">{{ t('inventory::admin.stock_import.file_has_no_lines_change') }}</p>
    <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="onlyRelevant" type="checkbox" /> {{ t('inventory::admin.stock_import.hide_unchanged_lines') }}</label>

    <DataTable :columns="columns" :rows="shown" :row-key="(line) => line.line" :row-label="(line) => line.sku" :empty-text="t('inventory::admin.stock_import.no_lines')">
        <template #cell-location="{ row }">{{ row.location || '—' }}</template>
        <template #cell-current="{ row }">{{ row.current ?? '—' }}</template>
        <template #cell-new="{ row }"><span class="font-medium">{{ row.new ?? '—' }}</span></template>
        <template #cell-status="{ row }">
            <span :class="problem(row.status) ? 'text-red-600' : row.status === 'change' ? 'text-indigo-700' : 'text-slate-500'">{{ statusLabels[row.status] ?? row.status }}</span>
        </template>
    </DataTable>
    <p v-if="preview.hidden_lines > 0" class="mt-2 text-xs text-slate-500">
        {{ t('inventory::admin.stock_import.large_file_showing_count_lines', { count: preview.lines.length, hidden_lines: preview.hidden_lines }) }}
    </p>
</template>
