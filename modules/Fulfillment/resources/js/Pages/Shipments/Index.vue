<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction, promptFields } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { shipment_status: Record<string, string> };
    filters: { status: string; order: string };
    statuses: string[];
    order: null | { id: number; number: string; status: string; status_label: string; can_create: boolean };
    shipments: Array<{
        id: number;
        order_number: string | null;
        carrier: string;
        manual: boolean;
        service_code: string | null;
        tracking_number: string | null;
        status: string;
        items: number;
        cod_amount: number;
        last_error: string | null;
        next: string[];
        can_book: boolean;
        can_cancel: boolean;
    }>;
    canManage: boolean;
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    perPageOptions: number[];
}>();

const filters = reactive({ ...props.filters });
const hasActiveFilters = computed(() => Boolean(filters.order || filters.status));
const errors = computed(() => usePage().props.errors as Record<string, string>);
const columns = [
    { key: 'order_number', label: 'Đơn', class: 'font-mono' },
    { key: 'carrier', label: 'Vận chuyển' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'cod_amount', label: 'COD' },
    { key: 'actions', label: '', align: 'right' as const, class: 'space-x-2' },
];
const label = (status: string): string => props.labels.shipment_status[status] ?? status;

function statusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    if (status === 'delivered') return 'success';
    if (['booking_failed', 'failed_attempt', 'cancelled'].includes(status)) return 'danger';
    if (['pending_booking', 'returning', 'returned'].includes(status)) return 'warning';

    return 'info';
}

function search(page?: number, perPage = props.pagination.per_page): void {
    router.get(props.baseUrl, { ...filters, page, per_page: perPage }, { preserveState: true });
}

function goToPage(page: number): void {
    search(page);
}

function changePageSize(perPage: number): void {
    search(undefined, perPage);
}

function clearFilters(): void {
    filters.order = '';
    filters.status = '';
    search();
}

function create(): void {
    router.post(props.baseUrl, { order_id: props.order!.id }, { preserveScroll: true });
}

async function book(id: number): Promise<void> {
    const values = await promptFields({
        title: 'Nhập mã vận đơn',
        confirmLabel: 'Lưu',
        fields: [
            { key: 'tracking_number', label: 'Mã vận đơn', required: true },
            { key: 'service_code', label: 'Hãng / dịch vụ', placeholder: 'vd. Viettel Post' },
        ],
    });
    if (values !== false) {
        router.post(`${props.baseUrl}/${id}/book`, { tracking_number: values.tracking_number, service_code: values.service_code || null }, { preserveScroll: true });
    }
}

function move(id: number, status: string): void {
    router.post(`${props.baseUrl}/${id}/status`, { status }, { preserveScroll: true });
}

async function cancel(id: number): Promise<void> {
    const reason = await confirmAction({ title: 'Huỷ vận đơn?', confirmLabel: 'Huỷ vận đơn', tone: 'danger', reason: { label: 'Lý do huỷ vận đơn', required: true } });
    if (reason !== false) {
        router.post(`${props.baseUrl}/${id}/cancel`, { reason }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Giao hàng" />
    <p class="mb-2 text-sm text-slate-500">Giao hàng</p>
    <PageHeader title="Vận đơn" subtitle="Tồn kho được trừ khi mọi vận đơn của đơn đã rời kho; hàng hoàn về được nhập lại kho." />
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>

    <form class="mb-4 grid gap-2 sm:grid-cols-3" @submit.prevent="search()">
        <input v-model="filters.order" :class="inputClass" placeholder="Số đơn" aria-label="Số đơn" />
        <Select v-model="filters.status" :options="codeOptions(statuses, labels.shipment_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" />
        <button type="submit" :class="primaryButton">Áp dụng</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">Bỏ bộ lọc</button>
        <span v-if="hasActiveFilters" class="self-center text-xs text-indigo-700" aria-live="polite">Đang lọc</span>
    </form>

    <div v-if="order" class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <span>Đơn <span class="font-mono">{{ order.number }}</span> · {{ order.status_label }}</span>
        <button v-if="order.can_create && canManage" type="button" :class="primaryButton" @click="create">Tạo vận đơn</button>
    </div>

    <DataTable :columns="columns" :rows="shipments" :row-class="() => 'align-top'" empty-text="Chưa có vận đơn.">
        <template #cell-order_number="{ row }">
            {{ row.order_number }}
            <div class="font-sans text-xs text-slate-400">{{ row.items }} sản phẩm</div>
        </template>
        <template #cell-carrier="{ row }">
            {{ row.carrier }}<span v-if="row.service_code"> · {{ row.service_code }}</span>
            <div class="font-mono text-xs text-slate-500">{{ row.tracking_number ?? '—' }}</div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="label(row.status)" :tone="statusTone(row.status)" />
            <div v-if="row.last_error" class="mt-1 text-xs text-red-600">{{ row.last_error }}</div>
        </template>
        <template #cell-cod_amount="{ row }"><MoneyText v-if="row.cod_amount" :amount="row.cod_amount" /><template v-else>—</template></template>
        <template #cell-actions="{ row }">
            <template v-if="canManage">
                <button v-if="row.can_book && row.manual" type="button" class="text-indigo-600 hover:underline" @click="book(row.id)">Nhập mã vận đơn</button>
                <button v-for="next in row.next" :key="next" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="move(row.id, next)">{{ label(next) }}</button>
                <button v-if="row.can_cancel" type="button" class="text-xs text-red-600 hover:underline" @click="cancel(row.id)">Huỷ</button>
            </template>
        </template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="goToPage" @per-page-change="changePageSize" />
</template>
