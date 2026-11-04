<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { orderStatusTone, paymentStatusTone } from '../../statusTones';

const props = defineProps<{
    baseUrl: string;
    filters: { status: string; payment_status: string; q: string };
    statuses: string[];
    paymentStatuses: string[];
    labels: { order_status: Record<string, string>; payment_status: Record<string, string> };
    orders: Array<{
        id: number;
        number: string;
        placed_at: string;
        customer: string;
        phone: string;
        total: number;
        order_status: string;
        payment_status: string;
        payment_method: string;
        label: string;
    }>;
    pagination: { current: number; last: number; total: number };
    extensions: ExtensionList;
}>();

const form = reactive({ ...props.filters });
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const statusOptions = props.statuses.map((status) => ({ value: status, label: props.labels.order_status[status] ?? status }));
const paymentStatusOptions = props.paymentStatuses.map((status) => ({ value: status, label: props.labels.payment_status[status] ?? status }));
const columns = [
    { key: 'number', label: 'Đơn' },
    { key: 'customer', label: 'Khách' },
    { key: 'total', label: 'Tổng', align: 'right' as const },
    { key: 'order_status', label: 'Trạng thái' },
    { key: 'payment_status', label: 'Thanh toán' },
];

function search(page = 1): void {
    router.get(props.baseUrl, { ...form, ext, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Đơn hàng" />
    <p class="mb-2 text-sm text-slate-500">Đơn hàng</p>
    <PageHeader title="Đơn hàng" :subtitle="`${pagination.total} đơn`" />

    <form class="mb-4 grid gap-2 sm:grid-cols-4" @submit.prevent="search()">
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <input v-model="form.q" :class="inputClass" placeholder="Số đơn hoặc SĐT" aria-label="Tìm đơn" />
        <Select v-model="form.status" :options="statusOptions" empty-label="Mọi trạng thái" aria-label="Trạng thái đơn" />
        <Select v-model="form.payment_status" :options="paymentStatusOptions" empty-label="Mọi trạng thái thanh toán" aria-label="Trạng thái thanh toán" />
        <button type="submit" :class="primaryButton">Lọc</button>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex items-center gap-2 text-sm">
        <span class="text-slate-500">Đã chọn {{ selected.length }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="orders" :extensions="extensions" :row-label="(order) => order.number" empty-text="Không có đơn nào.">
        <template #cell-number="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-mono text-indigo-600 hover:underline">{{ row.number }}</Link>
            <div class="text-xs text-slate-400">{{ row.placed_at }}</div>
        </template>
        <template #cell-customer="{ row }">
            {{ row.customer }}
            <div class="text-xs text-slate-400">{{ row.phone }}</div>
        </template>
        <template #cell-total="{ row }"><MoneyText :amount="row.total" /></template>
        <template #cell-order_status="{ row }">
            <StatusBadge :label="labels.order_status[row.order_status] ?? row.order_status" :tone="orderStatusTone(row.order_status)" />
            <div class="mt-1 text-xs text-slate-400">{{ row.label }}</div>
        </template>
        <template #cell-payment_status="{ row }">
            <StatusBadge :label="labels.payment_status[row.payment_status] ?? row.payment_status" :tone="paymentStatusTone(row.payment_status)" />
            <div class="mt-1 text-xs text-slate-400">{{ row.payment_method }}</div>
        </template>
    </DataTable>

    <Pagination :current="pagination.current" :last="pagination.last" @change="search" />
</template>
