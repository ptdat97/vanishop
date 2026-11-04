<script setup lang="ts">
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
    { key: 'at', label: 'Thời điểm', class: 'text-xs' },
    { key: 'location', label: 'Location', class: 'font-mono text-xs' },
    { key: 'type', label: 'Loại' },
    { key: 'on_hand', label: 'Tồn' },
    { key: 'reserved', label: 'Giữ' },
    { key: 'reason', label: 'Lý do / tham chiếu', class: 'text-xs' },
    { key: 'actor', label: 'Người thực hiện', class: 'text-xs' },
];
const signed = (value: number): string => (value > 0 ? `+${value}` : value === 0 ? '—' : String(value));
</script>

<template>
    <Head :title="`Lịch sử tồn · ${variant.sku}`" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader :title="`Lịch sử tồn: ${variant.sku}`" subtitle="Sổ cái chỉ ghi thêm; 100 dòng gần nhất.">
        <Link :href="backUrl" :class="secondaryButton">Quay lại</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="movements" empty-text="Chưa có biến động nào.">
        <template #cell-on_hand="{ row }">{{ signed(row.on_hand_delta) }} → {{ row.on_hand_after }}</template>
        <template #cell-reserved="{ row }">{{ signed(row.reserved_delta) }} → {{ row.reserved_after }}</template>
        <template #cell-reason="{ row }">{{ row.reason ?? '' }} <span class="font-mono text-slate-400">{{ row.reference ?? '' }}</span></template>
        <template #cell-actor="{ row }">{{ row.actor ?? '—' }}</template>
    </DataTable>
</template>
