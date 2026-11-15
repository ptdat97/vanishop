<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { stateTone } from '../../priceListState';

const props = defineProps<{
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_state: Record<string, string> };
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group: string | null;
        priority: number;
        status: string;
        state: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
    }>;
    canManage: boolean;
}>();

const columns = [
    { key: 'name', label: 'Bảng giá' },
    { key: 'state', label: 'Trạng thái' },
    { key: 'type', label: 'Loại' },
    { key: 'period', label: 'Hiệu lực', class: 'text-xs' },
    { key: 'prices_count', label: 'Số SKU' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Bảng giá" />
    <p class="mb-2 text-sm text-slate-500">Giá bán</p>
    <PageHeader title="Bảng giá" subtitle="Bấm vào bảng giá để xem và sửa giá. Bảng giá ưu tiên cao hơn thắng; giá niêm yết làm giá gạch khi đang giảm.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bảng giá</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="priceLists" :row-label="(list) => list.name" :row-click="(list) => router.visit(`${props.baseUrl}/${list.id}/prices`)" empty-text="Chưa có bảng giá nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.price_list_state[row.state] ?? row.state" :tone="stateTone(row.state)" /></template>
        <template #cell-type="{ row }">
            {{ labels.price_list_type[row.type] ?? row.type }}
            <div v-if="row.customer_group" class="text-xs text-slate-500">Nhóm: {{ row.customer_group }}</div>
        </template>
        <template #cell-period="{ row }">
            <template v-if="row.starts_at || row.ends_at">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
            <span v-else class="text-slate-400">Không giới hạn</span>
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline" @click.stop>Thông tin</Link>
        </template>
    </DataTable>
</template>
