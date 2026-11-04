<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { InventoryLabels } from '../../labels';

type Line = {
    id: number;
    location: string;
    sku: string;
    classification: string;
    expected: number;
    actual: number;
    difference: number;
    note: string | null;
    detected_at: string | null;
    resolved_at: string | null;
    resolution: string | null;
};

const props = defineProps<{
    backUrl: string;
    labels: InventoryLabels;
    run: {
        id: number;
        source: string;
        source_label: string;
        checked: number;
        discrepancies: number;
        repaired: number;
        open: number;
        started_at: string | null;
        finished_at: string | null;
    };
    lines: Line[];
}>();

const columns = [
    { key: 'location', label: 'Kho', class: 'font-mono text-xs' },
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'classification', label: 'Loại chênh lệch' },
    { key: 'expected', label: 'Đúng ra', align: 'right' as const },
    { key: 'actual', label: 'Hiện tại', align: 'right' as const },
    { key: 'difference', label: 'Chênh', align: 'right' as const, class: 'font-medium' },
    { key: 'resolution', label: 'Trạng thái' },
];

const title = computed(() => `Đối soát #${props.run.id} — ${props.run.source_label}`);
</script>

<template>
    <Head title="Đối soát tồn kho" />
    <p class="mb-2 text-sm text-slate-500">
        <Link :href="backUrl" class="text-indigo-600 hover:underline">Tồn kho</Link>
    </p>
    <PageHeader :title="title" subtitle="Chênh lệch expected − actual; dòng còn mở cần xử lý tay (kiểm kê hoặc duyệt áp số nguồn)." />

    <dl class="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-5 text-sm sm:grid-cols-5">
        <div><dt class="text-slate-500">Nguồn</dt><dd class="mt-1 font-medium">{{ run.source_label }}</dd></div>
        <div><dt class="text-slate-500">Bắt đầu</dt><dd class="mt-1">{{ run.started_at }}</dd></div>
        <div><dt class="text-slate-500">Đã kiểm</dt><dd class="mt-1">{{ run.checked }}</dd></div>
        <div><dt class="text-slate-500">Chênh lệch</dt><dd class="mt-1">{{ run.discrepancies }}</dd></div>
        <div><dt class="text-slate-500">Đã xử lý / còn mở</dt><dd class="mt-1">{{ run.repaired }} / {{ run.open }}</dd></div>
    </dl>

    <DataTable :columns="columns" :rows="lines" :row-class="() => 'align-top'" empty-text="Phiên này không có chênh lệch nào.">
        <template #cell-classification="{ row }">{{ labels.discrepancy[row.classification] ?? row.classification }}</template>
        <template #cell-difference="{ row }">{{ row.difference > 0 ? `+${row.difference}` : row.difference }}</template>
        <template #cell-resolution="{ row }">
            <StatusBadge v-if="row.resolution" :label="labels.resolution[row.resolution] ?? row.resolution" :tone="row.resolution === 'rejected' ? 'warning' : 'success'" />
            <StatusBadge v-else label="Còn mở" tone="danger" />
            <div v-if="row.note" class="mt-1 max-w-md text-xs text-slate-500">{{ row.note }}</div>
        </template>
    </DataTable>
</template>
