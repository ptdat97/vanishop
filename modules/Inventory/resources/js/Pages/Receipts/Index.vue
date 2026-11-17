<script setup lang="ts">
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
    { key: 'number', label: 'Số phiếu', class: 'font-mono' },
    { key: 'supplier', label: 'Nhà cung cấp' },
    { key: 'location', label: 'Kho' },
    { key: 'units', label: 'Số lượng', align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'date', label: 'Ngày', class: 'whitespace-nowrap tabular-nums' },
];

function search(page = 1): void {
    list.visit({ q: q.value, status: status.value, page });
}
</script>

<template>
    <Head title="Nhập hàng" />
    <PageHeader title="Nhập hàng" :subtitle="`${receipts.total} phiếu nhập`">
        <Link :href="urls.stock" :class="secondaryButton">Tồn kho</Link>
        <Link v-if="canManage" :href="urls.create" :class="primaryButton">Tạo phiếu nhập</Link>
    </PageHeader>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" placeholder="Số phiếu hoặc nhà cung cấp" aria-label="Tìm phiếu nhập" />
        <div class="w-44"><Select v-model="status" :options="labelOptions(statusLabels)" empty-label="Mọi trạng thái" aria-label="Trạng thái" /></div>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <DataTable :columns="columns" :rows="receipts.data" :loading="list.loading.value" :row-label="(row) => row.number" :row-click="(row) => router.visit(`${urls.base}/${row.id}`)" empty-text="Chưa có phiếu nhập nào.">
        <template #cell-supplier="{ row }">{{ row.supplier ?? '—' }}</template>
        <template #cell-units="{ row }">{{ row.units }} <span class="text-xs text-slate-500">({{ row.lines }} SKU)</span></template>
        <template #cell-status="{ row }">
            <StatusBadge :label="statusLabels[row.status] ?? row.status" :tone="row.status === 'received' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
        <template #cell-date="{ row }">{{ row.received_at ?? row.created_at }}</template>
    </DataTable>
    <Pagination :current="receipts.current" :last="receipts.last" :from="receipts.from" :to="receipts.to" :total="receipts.total" :per-page="receipts.per_page" @change="search" />
</template>
