<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import FileInput from '@admin/Components/FileInput.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction, promptFields } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { shipment_status: Record<string, string> };
    filters: { status: string; order: string };
    statuses: string[];
    order: null | { id: number; number: string; status: string; status_label: string; can_create: boolean };
    shipments: Array<{
        id: number;
        order_number: string | null;
        order_url: string;
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
        can_hand_over: boolean;
        can_cancel: boolean;
    }>;
    canManage: boolean;
    /** Nhập bảng kê COD của hãng (A9) — null khi không có quyền xác nhận thanh toán. */
    codStatementUrl: string | null;
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    sort: ListSortState | null;
    perPageOptions: number[];
    /** Cột/lọc/thao tác hàng loạt/công cụ của plugin (EP v3 X1). */
    extensions: ExtensionList;
}>();

// Bảng kê COD của hãng (A9): tải file → trang xem trước, chưa ghi gì.
const showCodStatement = ref(false);
const codStatementForm = useForm<{ file: File | null }>({ file: null });
function submitCodStatement(): void {
    if (props.codStatementUrl) codStatementForm.post(props.codStatementUrl, { forceFormData: true });
}

const filters = reactive({ ...props.filters });
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const handover = useForm({ shipment_ids: [] as number[] });
const readySelected = computed(() => props.shipments.filter((row) => row.can_hand_over && selected.value.includes(row.id)).map((row) => row.id));
const canSelectShipments = computed(() => props.canManage && props.shipments.some((row) => row.can_hand_over));
const hasActiveFilters = computed(() => Boolean(filters.order || filters.status || Object.values(ext).some(Boolean)));
const errors = computed(() => usePage().props.errors as Record<string, string>);
const columns = [
    { key: 'order_number', label: t('fulfillment::admin.shipments_index.order'), class: 'font-mono' },
    { key: 'carrier', label: t('fulfillment::admin.shipments_index.shipping') },
    { key: 'status', label: t('fulfillment::admin.shipments_index.status') },
    { key: 'cod_amount', label: 'COD', sortable: true },
    { key: 'actions', label: '', align: 'right' as const, class: 'space-x-2' },
];
const label = (status: string): string => props.labels.shipment_status[status] ?? status;

function statusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    if (status === 'delivered') return 'success';
    if (['booking_failed', 'failed_attempt', 'cancelled'].includes(status)) return 'danger';
    if (['pending_booking', 'returning', 'returned'].includes(status)) return 'warning';

    return 'info';
}

const list = useListQuery(() => props.baseUrl);
function search(page?: number, perPage = props.pagination.per_page, sort = props.sort): void {
    list.visit({ ...filters, ext: { ...ext }, ...list.sortQuery(sort), page, per_page: perPage });
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
    Object.keys(ext).forEach((key) => (ext[key] = ''));
    search();
}

function create(): void {
    router.post(props.baseUrl, { order_id: props.order!.id }, { preserveScroll: true });
}

async function book(id: number): Promise<void> {
    const values = await promptFields({
        title: t('fulfillment::admin.shipments_index.enter_tracking_number'),
        confirmLabel: t('fulfillment::admin.shipments_index.save'),
        fields: [
            { key: 'tracking_number', label: t('fulfillment::admin.shipments_index.tracking_number'), required: true },
            { key: 'service_code', label: t('fulfillment::admin.shipments_index.carrier_service'), placeholder: 'vd. Viettel Post' },
        ],
    });
    if (values !== false) {
        router.post(`${props.baseUrl}/${id}/book`, { tracking_number: values.tracking_number, service_code: values.service_code || null }, { preserveScroll: true });
    }
}

function move(id: number, status: string): void {
    router.post(`${props.baseUrl}/${id}/status`, { status }, { preserveScroll: true });
}

async function handOverSelected(): Promise<void> {
    const shipmentIds = [...readySelected.value];
    if (handover.processing || !shipmentIds.length) return;
    const confirmed = await confirmAction({
        title: t('fulfillment::admin.shipments_index.handover_confirm', { count: shipmentIds.length }),
        message: t('fulfillment::admin.shipments_index.handover_stock'),
        confirmLabel: t('fulfillment::admin.shipments_index.handover'),
    });
    if (confirmed === false) return;
    handover.shipment_ids = shipmentIds;
    handover.post(`${props.baseUrl}/bulk-hand-over`, { preserveScroll: true, onSuccess: () => (selected.value = []) });
}

async function cancel(id: number): Promise<void> {
    const reason = await confirmAction({ title: t('fulfillment::admin.shipments_index.cancel_shipment'), confirmLabel: t('fulfillment::admin.shipments_index.cancel_shipment_2'), tone: 'danger', reason: { label: t('fulfillment::admin.shipments_index.reason_cancelling_shipment'), required: true } });
    if (reason !== false) {
        router.post(`${props.baseUrl}/${id}/cancel`, { reason }, { preserveScroll: true });
    }
}
</script>

<template>
    <div>
    <Head :title="t('fulfillment::admin.shipments_index.fulfilment')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('fulfillment::admin.shipments_index.fulfilment') }}</p>
    <PageHeader :title="t('fulfillment::admin.shipments_index.shipments')" :subtitle="t('fulfillment::admin.shipments_index.stock_deducted_once_every_shipment')">
        <button v-if="codStatementUrl" type="button" :class="secondaryButton" data-cod-statement-toggle @click="showCodStatement = !showCodStatement">{{ t('fulfillment::admin.shipments_index.import_cod_statement') }}</button>
    </PageHeader>
    <form v-if="showCodStatement" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" data-cod-statement @submit.prevent="submitCodStatement">
        <p>{{ t('fulfillment::admin.shipments_index.excel_xlsx_csv_file_carrier') }} <strong>{{ t('fulfillment::admin.shipments_index.tracking_number') }}</strong> {{ t('fulfillment::admin.shipments_index.text') }} <strong>{{ t('fulfillment::admin.shipments_index.cod_amount') }}</strong>{{ t('fulfillment::admin.shipments_index.column') }} <strong>{{ t('fulfillment::admin.shipments_index.fee') }}</strong> {{ t('fulfillment::admin.shipments_index.recorded_if_present_mismatched_rows') }}</p>
        <FileInput v-model="codStatementForm.file" accept=".xlsx,.csv" :max-kb="5120" :label="t('fulfillment::admin.shipments_index.choose_file')" :progress="codStatementForm.progress?.percentage ?? null" />
        <p v-for="(error, key) in codStatementForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!codStatementForm.file || codStatementForm.processing">{{ t('fulfillment::admin.shipments_index.preview') }}</button></div>
    </form>

    <ExtensionTools :tools="extensions.tools ?? []" />

    <form class="mb-4 grid gap-2 sm:grid-cols-3" @submit.prevent="search()">
        <input v-model="filters.order" :class="inputClass" :placeholder="t('fulfillment::admin.shipments_index.orders')" :aria-label="t('fulfillment::admin.shipments_index.orders')" />
        <Select v-model="filters.status" :options="codeOptions(statuses, labels.shipment_status)" :empty-label="t('fulfillment::admin.shipments_index.all_statuses')" :aria-label="t('fulfillment::admin.shipments_index.status')" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="primaryButton">{{ t('fulfillment::admin.shipments_index.apply') }}</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">{{ t('fulfillment::admin.shipments_index.clear_filters') }}</button>
        <span v-if="hasActiveFilters" class="self-center text-xs text-indigo-700" aria-live="polite">{{ t('fulfillment::admin.shipments_index.filtering') }}</span>
    </form>

    <div v-if="order" class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <span>{{ t('fulfillment::admin.shipments_index.order') }} <span class="font-mono">{{ order.number }}</span> · {{ order.status_label }}</span>
        <button v-if="order.can_create && canManage" type="button" :class="primaryButton" @click="create">{{ t('fulfillment::admin.shipments_index.create_shipment') }}</button>
    </div>

    <div v-if="extensions.actions.length || selected.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">{{ t('fulfillment::admin.shipments_index.count_selected', { count: selected.length }) }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" :selection-description="t('fulfillment::admin.shipments_index.shipments_2')" />
        <button v-if="canManage && readySelected.length" type="button" :class="secondaryButton" :disabled="handover.processing" data-bulk-handover @click="handOverSelected">{{ t('fulfillment::admin.shipments_index.handover_count', { count: readySelected.length }) }}</button>
    </div>
    <DataTable v-model:selected="selected" :selectable="canSelectShipments" :columns="columns" :rows="shipments" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, pagination.per_page, next)" :extensions="extensions" :row-label="(row) => row.tracking_number ?? row.order_number ?? String(row.id)" :row-class="() => 'align-top'" :empty-text="t('fulfillment::admin.shipments_index.no_shipments_yet')">
        <template #cell-order_number="{ row }">
            <Link :href="row.order_url" class="text-indigo-600 hover:underline">{{ row.order_number ?? '—' }}</Link>
            <div class="font-sans text-xs text-slate-400">{{ t('fulfillment::admin.shipments_index.items_products', { items: row.items }) }}</div>
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
                <button v-if="row.can_book && row.manual" type="button" class="text-indigo-600 hover:underline" @click="book(row.id)">{{ t('fulfillment::admin.shipments_index.enter_tracking_number') }}</button>
                <button v-for="next in row.next" :key="next" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="move(row.id, next)">{{ label(next) }}</button>
                <button v-if="row.can_cancel" type="button" class="text-xs text-red-600 hover:underline" @click="cancel(row.id)">{{ t('fulfillment::admin.shipments_index.cancel') }}</button>
            </template>
        </template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="goToPage" @per-page-change="changePageSize" />
    </div>
</template>
