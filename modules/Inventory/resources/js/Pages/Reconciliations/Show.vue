<script setup lang="ts">
import { t } from '@admin/i18n';
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
    { key: 'classification', label: t('inventory::admin.reconciliations_show.difference_type') },
    { key: 'expected', label: t('inventory::admin.reconciliations_show.expected'), align: 'right' as const },
    { key: 'actual', label: t('inventory::admin.reconciliations_show.current'), align: 'right' as const },
    { key: 'difference', label: t('inventory::admin.reconciliations_show.difference'), align: 'right' as const, class: 'font-medium' },
    { key: 'resolution', label: t('inventory::admin.reconciliations_show.status') },
];

const title = computed(() => t('inventory::admin.reconciliations_show.reconciliation_id_source_label', { id: props.run.id, source_label: props.run.source_label }));
</script>

<template>
    <Head :title="t('inventory::admin.reconciliations_show.stock_reconciliation')" />
    <p class="mb-2 text-sm text-slate-500">
        <Link :href="backUrl" class="text-indigo-600 hover:underline">{{ t('inventory::admin.reconciliations_show.inventory') }}</Link>
    </p>
    <PageHeader :title="title" :subtitle="t('inventory::admin.reconciliations_show.difference_expected_actual_open_lines')" />

    <dl class="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-5 text-sm sm:grid-cols-5">
        <div><dt class="text-slate-500">{{ t('inventory::admin.reconciliations_show.source') }}</dt><dd class="mt-1 font-medium">{{ run.source_label }}</dd></div>
        <div><dt class="text-slate-500">{{ t('inventory::admin.reconciliations_show.started') }}</dt><dd class="mt-1">{{ run.started_at }}</dd></div>
        <div><dt class="text-slate-500">{{ t('inventory::admin.reconciliations_show.checked') }}</dt><dd class="mt-1">{{ run.checked }}</dd></div>
        <div><dt class="text-slate-500">{{ t('inventory::admin.reconciliations_show.differences') }}</dt><dd class="mt-1">{{ run.discrepancies }}</dd></div>
        <div><dt class="text-slate-500">{{ t('inventory::admin.reconciliations_show.resolved_open') }}</dt><dd class="mt-1">{{ run.repaired }} / {{ run.open }}</dd></div>
    </dl>

    <DataTable :columns="columns" :rows="lines" :row-class="() => 'align-top'" :empty-text="t('inventory::admin.reconciliations_show.session_has_no_differences')">
        <template #cell-classification="{ row }">{{ labels.discrepancy[row.classification] ?? row.classification }}</template>
        <template #cell-difference="{ row }">{{ row.difference > 0 ? `+${row.difference}` : row.difference }}</template>
        <template #cell-resolution="{ row }">
            <StatusBadge v-if="row.resolution" :label="labels.resolution[row.resolution] ?? row.resolution" :tone="row.resolution === 'rejected' ? 'warning' : 'success'" />
            <StatusBadge v-else :label="t('inventory::admin.reconciliations_show.open')" tone="danger" />
            <div v-if="row.note" class="mt-1 max-w-md text-xs text-slate-500">{{ row.note }}</div>
        </template>
    </DataTable>
</template>
