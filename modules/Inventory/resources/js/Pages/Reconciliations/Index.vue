<script setup lang="ts">
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
    { key: 'source_label', label: 'Nguồn' },
    { key: 'started_at', label: 'Bắt đầu', class: 'text-slate-500' },
    { key: 'checked', label: 'Đã kiểm', align: 'right' as const },
    { key: 'discrepancies', label: 'Chênh lệch', align: 'right' as const },
    { key: 'repaired', label: 'Đã xử lý', align: 'right' as const },
    { key: 'open', label: 'Còn mở', align: 'right' as const },
];
</script>

<template>
    <Head title="Đối soát tồn kho" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader
        title="Đối soát tồn kho"
        subtitle="Kết quả của vani:inventory:verify (chạy hằng ngày) và vani:warehouse:reconcile (nguồn ngoài, plugin Vận hành kho). Chênh lệch còn mở phải xử lý tay."
    />

    <DataTable :columns="columns" :rows="runs" empty-text="Chưa có phiên đối soát nào.">
        <template #cell-source_label="{ row }"><Link :href="`${baseUrl}/${row.id}`" class="text-indigo-600 hover:underline">{{ row.source_label }}</Link></template>
        <template #cell-open="{ row }"><span :class="row.open > 0 ? 'font-medium text-red-600' : 'text-green-600'">{{ row.open }}</span></template>
    </DataTable>
</template>
