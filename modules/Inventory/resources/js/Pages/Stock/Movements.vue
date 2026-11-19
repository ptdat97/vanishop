<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    backUrl: string;
    variant: { sku: string; style_code: string };
    movements: Array<{
        id: number;
        at: string | null;
        location: string;
        type: string;
        on_hand_delta: number;
        reserved_delta: number;
        on_hand_after: number;
        reserved_after: number;
        reason: string | null;
        reference: string | null;
        actor: string | null;
    }>;
}>();

const columns = [
    { key: 'at', label: t('inventory::admin.stock_movements.time'), class: 'text-xs' },
    { key: 'location', label: 'Location', class: 'font-mono text-xs' },
    { key: 'type', label: t('inventory::admin.stock_movements.type') },
    { key: 'on_hand', label: t('inventory::admin.stock_movements.stock') },
    { key: 'reserved', label: t('inventory::admin.stock_movements.reserved') },
    { key: 'reason', label: t('inventory::admin.stock_movements.reason_reference'), class: 'text-xs' },
    { key: 'actor', label: t('inventory::admin.stock_movements.performed'), class: 'text-xs' },
];
const signed = (value: number): string => (value > 0 ? `+${value}` : value === 0 ? '—' : String(value));
</script>

<template>
    <Head :title="t('inventory::admin.stock_movements.stock_history_sku', { sku: variant.sku })" />
    <p class="mb-2 text-sm text-slate-500">{{ t('inventory::admin.stock_movements.inventory') }}</p>
    <PageHeader :title="t('inventory::admin.stock_movements.stock_history_sku_2', { sku: variant.sku })" :subtitle="t('inventory::admin.stock_movements.append_only_ledger_latest_100')">
        <Link :href="backUrl" :class="secondaryButton">{{ t('inventory::admin.stock_movements.back') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="movements" :empty-text="t('inventory::admin.stock_movements.no_movements_yet')">
        <template #cell-on_hand="{ row }">{{ signed(row.on_hand_delta) }} → {{ row.on_hand_after }}</template>
        <template #cell-reserved="{ row }">{{ signed(row.reserved_delta) }} → {{ row.reserved_after }}</template>
        <template #cell-reason="{ row }">{{ row.reason ?? '' }} <span class="font-mono text-slate-400">{{ row.reference ?? '' }}</span></template>
        <template #cell-actor="{ row }">{{ row.actor ?? '—' }}</template>
    </DataTable>
</template>
