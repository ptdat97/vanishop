<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_status: Record<string, string> };
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group: string | null;
        priority: number;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
    }>;
    canManage: boolean;
}>();

const columns = [
    { key: 'name', label: 'Bảng giá' },
    { key: 'type', label: 'Loại' },
    { key: 'priority', label: 'Priority' },
    { key: 'period', label: 'Hiệu lực', class: 'text-xs' },
    { key: 'prices_count', label: 'Số giá' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Bảng giá" />
    <p class="mb-2 text-sm text-slate-500">Giá bán</p>
    <PageHeader title="Bảng giá" subtitle="Bảng giá priority cao hơn thắng; giá niêm yết (base) được dùng làm giá gốc khi đang khuyến mãi.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bảng giá</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="priceLists" :row-label="(list) => list.code" :row-click="canManage ? (list) => router.visit(`${props.baseUrl}/${list.id}/edit`) : undefined" empty-text="Chưa có bảng giá nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.status !== 'active'"> · {{ labels.price_list_status[row.status] ?? row.status }}</span></div>
        </template>
        <template #cell-type="{ row }">
            {{ labels.price_list_type[row.type] ?? row.type }}
            <div v-if="row.customer_group" class="text-xs text-slate-500">Nhóm: {{ row.customer_group }}</div>
        </template>
        <template #cell-period="{ row }">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
        <template #cell-actions="{ row }">
            <Link :href="`${baseUrl}/${row.id}/prices`" class="text-indigo-600 hover:underline">Giá</Link>
        </template>
    </DataTable>
</template>
