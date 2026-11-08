<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { type ReturnLabels, returnStatusTone } from '../../labels';

const props = defineProps<{
    reasonsUrl: string;
    baseUrl: string;
    labels: ReturnLabels;
    status: string;
    statuses: string[];
    counts: Record<string, number>;
    returns: Array<{ id: number; number: string; status: string; reason_code: string; refund_amount: number; source: string; created_at: string | null }>;
}>();

const columns = [
    { key: 'number', label: 'Yêu cầu' },
    { key: 'reason_code', label: 'Lý do' },
    { key: 'refund_amount', label: 'Tiền hoàn' },
    { key: 'status', label: 'Trạng thái' },
];

function filter(status: string): void {
    router.get(props.baseUrl, status ? { status } : {}, { preserveState: true });
}
</script>

<template>
    <Head title="Đổi/trả" />
    <p class="mb-2 text-sm text-slate-500">Đổi/trả</p>
    <PageHeader title="Yêu cầu đổi/trả" subtitle="Tiền hoàn tính từ thành tiền dòng đã trừ giảm giá; không hoàn phí giao.">
        <Link :href="reasonsUrl" :class="secondaryButton">Lý do đổi trả</Link>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">Tất cả</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">
            {{ labels.return_status[value] ?? value }}
            <span class="ml-1 text-xs opacity-70">{{ counts[value] ?? 0 }}</span>
        </button>
    </div>

    <DataTable :columns="columns" :rows="returns" :row-label="(item) => item.number" empty-text="Chưa có yêu cầu nào.">
        <template #cell-number="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-mono text-indigo-600 hover:underline">{{ row.number }}</Link>
            <div class="text-xs text-slate-400">{{ row.created_at }} · {{ row.source === 'customer' ? 'khách gửi' : 'nhân viên tạo' }}</div>
        </template>
        <template #cell-reason_code="{ row }">{{ labels.reason[row.reason_code] ?? row.reason_code }}</template>
        <template #cell-refund_amount="{ row }"><MoneyText :amount="row.refund_amount" /></template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.return_status[row.status] ?? row.status" :tone="returnStatusTone(row.status)" /></template>
    </DataTable>
</template>
