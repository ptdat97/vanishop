<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

type ReconciliationRun = {
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

defineProps<{
    baseUrl: string;
    runs: ReconciliationRun[];
}>();

const columns = [
    { key: 'source_label', label: t('inventory::admin.reconciliations_index.source') },
    { key: 'started_at', label: t('inventory::admin.reconciliations_index.started'), class: 'text-slate-500' },
    { key: 'checked', label: t('inventory::admin.reconciliations_index.checked'), align: 'right' as const },
    { key: 'discrepancies', label: t('inventory::admin.reconciliations_index.differences'), align: 'right' as const },
    { key: 'repaired', label: t('inventory::admin.reconciliations_index.resolved'), align: 'right' as const },
    { key: 'open', label: t('inventory::admin.reconciliations_index.open'), align: 'right' as const },
];
</script>

<template>
    <Head :title="t('inventory::admin.reconciliations_index.stock_reconciliation')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('inventory::admin.reconciliations_index.inventory') }}</p>
    <PageHeader
        :title="t('inventory::admin.reconciliations_index.stock_reconciliation')"
        :subtitle="t('inventory::admin.reconciliations_index.results_vaniinventoryverify_daily_vaniwarehousereconcile_external')"
    />

    <DataTable :columns="columns" :rows="runs" :empty-text="t('inventory::admin.reconciliations_index.no_reconciliation_sessions_yet')">
        <template #cell-source_label="{ row }"><Link :href="`${baseUrl}/${row.id}`" class="text-indigo-600 hover:underline">{{ row.source_label }}</Link></template>
        <template #cell-open="{ row }"><span :class="row.open > 0 ? 'font-medium text-red-600' : 'text-green-600'">{{ row.open }}</span></template>
    </DataTable>
</template>
