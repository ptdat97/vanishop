<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionAction } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { type ReturnLabels, returnStatusTone } from '../../labels';

const props = defineProps<{
    reasonsUrl: string;
    baseUrl: string;
    labels: ReturnLabels;
    status: string;
    statuses: string[];
    counts: Record<string, number>;
    /** Thao tác hàng loạt (0.3.102): Duyệt, Đã nhận hàng. */
    actions: ExtensionAction[];
    returns: Array<{ id: number; number: string; status: string; reason_code: string; refund_amount: number; source: string; created_at: string | null }>;
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    perPageOptions: number[];
}>();

const selected = ref<number[]>([]);
const bulk = { columns: [], values: {}, actions: props.actions };
const columns = [
    { key: 'number', label: 'Yêu cầu' },
    { key: 'reason_code', label: 'Lý do' },
    { key: 'refund_amount', label: 'Tiền hoàn' },
    { key: 'status', label: 'Trạng thái' },
];

function filter(status: string, page?: number, perPage = props.pagination.per_page): void {
    router.get(props.baseUrl, { ...(status ? { status } : {}), page, per_page: perPage }, { preserveState: true });
}

function goToPage(page: number): void {
    filter(props.status, page);
}

function changePageSize(perPage: number): void {
    filter(props.status, undefined, perPage);
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

    <div v-if="actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">Đã chọn {{ selected.length }}</span>
        <ExtensionActions :actions="actions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="returns" :extensions="bulk" :row-label="(item) => item.number" empty-text="Chưa có yêu cầu nào.">
        <template #cell-number="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-mono text-indigo-600 hover:underline">{{ row.number }}</Link>
            <div class="text-xs text-slate-400">{{ row.created_at }} · {{ row.source === 'customer' ? 'khách gửi' : 'nhân viên tạo' }}</div>
        </template>
        <template #cell-reason_code="{ row }">{{ labels.reason[row.reason_code] ?? row.reason_code }}</template>
        <template #cell-refund_amount="{ row }"><MoneyText :amount="row.refund_amount" /></template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.return_status[row.status] ?? row.status" :tone="returnStatusTone(row.status)" /></template>
    </DataTable>
    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="goToPage" @per-page-change="changePageSize" />
</template>
