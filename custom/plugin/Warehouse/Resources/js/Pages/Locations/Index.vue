<script setup lang="ts">
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
    { key: 'type', label: 'Loại' },
    { key: 'priority', label: 'Priority' },
    { key: 'ships_online_orders', label: 'Bán online' },
    { key: 'stock_authority', label: 'Quản lý tồn', class: 'font-mono text-xs' },
];
</script>

<template>
    <Head title="Kho & cửa hàng" />
    <PageHeader title="Kho & cửa hàng" subtitle="Priority cao hơn được giữ hàng trước khi bán online.">
        <Link :href="`${baseUrl}/create`" :class="primaryButton">Thêm location</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="locations" :row-label="(location) => location.code" :row-click="(location) => router.visit(`${props.baseUrl}/${location.id}/edit`)" empty-text="Chưa có location nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.status !== 'active'"> · {{ labels.location_status[row.status] ?? row.status }}</span></div>
        </template>
        <template #cell-type="{ row }">{{ labels.location_type[row.type] ?? row.type }}</template>
        <template #cell-ships_online_orders="{ row }">{{ row.ships_online_orders ? 'Có' : '—' }}</template>
    </DataTable>
</template>
