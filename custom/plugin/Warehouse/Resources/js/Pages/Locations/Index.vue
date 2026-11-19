<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import type { WarehouseLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: WarehouseLabels;
    locations: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        priority: number;
        status: string;
        ships_online_orders: boolean;
        stock_authority: string;
    }>;
}>();

const columns = [
    { key: 'name', label: 'Location' },
    { key: 'type', label: t('vani-warehouse::admin.locations_index.type') },
    { key: 'priority', label: 'Priority' },
    { key: 'ships_online_orders', label: t('vani-warehouse::admin.locations_index.online_sales') },
    { key: 'stock_authority', label: t('vani-warehouse::admin.locations_index.stock_managed'), class: 'font-mono text-xs' },
];
</script>

<template>
    <Head :title="t('vani-warehouse::admin.locations_index.warehouses_stores')" />
    <PageHeader :title="t('vani-warehouse::admin.locations_index.warehouses_stores')" :subtitle="t('vani-warehouse::admin.locations_index.higher_priority_reserves_stock_first')">
        <Link :href="`${baseUrl}/create`" :class="primaryButton">{{ t('vani-warehouse::admin.locations_index.add_location') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="locations" :row-label="(location) => location.code" :row-click="(location) => router.visit(`${props.baseUrl}/${location.id}/edit`)" :empty-text="t('vani-warehouse::admin.locations_index.no_locations_yet')">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.status !== 'active'"> · {{ labels.location_status[row.status] ?? row.status }}</span></div>
        </template>
        <template #cell-type="{ row }">{{ labels.location_type[row.type] ?? row.type }}</template>
        <template #cell-ships_online_orders="{ row }">{{ row.ships_online_orders ? t('vani-warehouse::admin.locations_index.yes') : '—' }}</template>
    </DataTable>
</template>
