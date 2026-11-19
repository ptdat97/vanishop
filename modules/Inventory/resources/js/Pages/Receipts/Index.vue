<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useListQuery } from '@admin/list';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Receipt = { id: number; number: string; location: string | null; supplier: string | null; lines: number; units: number; status: string; created_at: string; received_at: string | null };

/** Phiếu nhập nhanh (A6). */
const props = defineProps<{
    filters: { q: string; status: string | null };
    statusLabels: Record<string, string>;
    receipts: { data: Receipt[]; current: number; last: number; from: number | null; to: number | null; total: number; per_page: number };
    canManage: boolean;
    urls: { base: string; create: string; stock: string };
}>();

const q = ref(props.filters.q);
const status = ref(props.filters.status ?? '');
const list = useListQuery(() => props.urls.base);
const columns = [
    { key: 'number', label: t('inventory::admin.receipts_index.receipt_number'), class: 'font-mono' },
    { key: 'supplier', label: t('inventory::admin.receipts_index.supplier') },
    { key: 'location', label: 'Kho' },
    { key: 'units', label: t('inventory::admin.receipts_index.quantity'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: t('inventory::admin.receipts_index.status') },
    { key: 'date', label: t('inventory::admin.receipts_index.date'), class: 'whitespace-nowrap tabular-nums' },
];

function search(page = 1): void {
    list.visit({ q: q.value, status: status.value, page });
}
</script>

<template>
    <Head :title="t('inventory::admin.receipts_index.goods_receipts')" />
    <PageHeader :title="t('inventory::admin.receipts_index.goods_receipts')" :subtitle="t('inventory::admin.receipts_index.total_goods_receipts', { total: receipts.total })">
        <Link :href="urls.stock" :class="secondaryButton">{{ t('inventory::admin.receipts_index.inventory') }}</Link>
        <Link v-if="canManage" :href="urls.create" :class="primaryButton">{{ t('inventory::admin.receipts_index.create_goods_receipt') }}</Link>
    </PageHeader>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" :placeholder="t('inventory::admin.receipts_index.receipt_number_supplier')" :aria-label="t('inventory::admin.receipts_index.search_goods_receipts')" />
        <div class="w-44"><Select v-model="status" :options="labelOptions(statusLabels)" :empty-label="t('inventory::admin.receipts_index.all_statuses')" :aria-label="t('inventory::admin.receipts_index.status')" /></div>
        <button type="submit" :class="secondaryButton">{{ t('inventory::admin.receipts_index.filter') }}</button>
    </form>

    <DataTable :columns="columns" :rows="receipts.data" :loading="list.loading.value" :row-label="(row) => row.number" :row-click="(row) => router.visit(`${urls.base}/${row.id}`)" :empty-text="t('inventory::admin.receipts_index.no_goods_receipts_yet')">
        <template #cell-supplier="{ row }">{{ row.supplier ?? '—' }}</template>
        <template #cell-units="{ row }">{{ row.units }} <span class="text-xs text-slate-500">({{ row.lines }} SKU)</span></template>
        <template #cell-status="{ row }">
            <StatusBadge :label="statusLabels[row.status] ?? row.status" :tone="row.status === 'received' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
        <template #cell-date="{ row }">{{ row.received_at ?? row.created_at }}</template>
    </DataTable>
    <Pagination :current="receipts.current" :last="receipts.last" :from="receipts.from" :to="receipts.to" :total="receipts.total" :per-page="receipts.per_page" @change="search" />
</template>
