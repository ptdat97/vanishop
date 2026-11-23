<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import DateInput from '@admin/Components/DateInput.vue';
import Dialog from '@admin/Components/Dialog.vue';
import FileInput from '@admin/Components/FileInput.vue';
import FormField from '@admin/Components/FormField.vue';
import { useListQuery, type ListSortState } from '@admin/list';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { customerRiskFlag, type DeliveryHistory, paymentStatusTone, type RiskSignal, riskTone, stageTone } from '../../statusTones';

/**
 * Danh sách đơn theo hàng đợi việc (0.3.96): thẻ giai đoạn vận hành (OrderStage) có số đếm, bộ lọc ngày/nguồn/thanh toán,
 * cột tuổi đơn, sản phẩm, tỉnh; cột "Vận đơn" và thao tác hàng loạt (Tạo vận đơn, Đã bàn giao) do Fulfillment đóng góp.
 */
type Filters = { stage: string; aged: string; actionable: string; status: string; payment_status: string; payment_method: string; source: string; from: string; to: string; q: string };

const props = defineProps<{
    baseUrl: string;
    exportUrl: string | null;
    createUrl: string | null;
    filters: Filters;
    sort: ListSortState | null;
    stages: string[];
    actionableStages: string[];
    stageCounts: Record<string, number>;
    agedCount: number;
    statuses: string[];
    bulkStatus: {
        canOverride: boolean;
        options: Array<{ value: string; label: string; reasons: Array<{ value: string; label: string }> }>;
    } | null;
    paymentStatuses: string[];
    labels: {
        order_status: Record<string, string>;
        payment_status: Record<string, string>;
        stage: Record<string, string>;
        delivery_issue: Record<string, string>;
        source: Record<string, string>;
        payment_method: Record<string, string>;
    };
    orders: Array<{
        id: number;
        number: string;
        placed_at: string;
        age_hours: number | null;
        customer: string;
        phone: string;
        province: string;
        items: Array<{ sku: string; quantity: number }>;
        total: number;
        stage: string;
        delivery_issue: string | null;
        order_status: string;
        payment_status: string;
        payment_method: string;
        source: string;
        label: string;
        /** Chỉ có ở đơn "Cần xác nhận": lịch sử nhận hàng của SĐT (các đơn khác). */
        history: DeliveryHistory | null;
        /** Tín hiệu rủi ro của plugin (đơn "Cần xác nhận"). */
        risks: RiskSignal[];
    }>;
    pagination: { current: number; last: number; from: number | null; to: number | null; total: number; per_page: number };
    extensions: ExtensionList;
    /** Công cụ tải file (slot vani.admin.order.list_tools, 0.3.97) — vd. nhập mã vận đơn từ file của hãng. */
    tools: Array<{ key: string; label: string; url: string; accept?: string; max_kb?: number; help?: string; fields?: Array<{ key: string; label: string; placeholder?: string }> }>;
}>();

const openTool = ref<string | null>(null);
const toolForm = useForm<{ file: File | null; [key: string]: string | File | null }>({ file: null });
const importErrors = computed(() => {
    const errors = usePage().props.errors as Record<string, string | string[]>;
    return Object.entries(errors)
        .filter(([key]) => key === 'file' || key === 'import' || key.startsWith('import.'))
        .flatMap(([, message]) => (Array.isArray(message) ? message : [message]));
});

function toggleTool(key: string): void {
    openTool.value = openTool.value === key ? null : key;
    toolForm.reset();
}

function submitTool(tool: { url: string }): void {
    toolForm.post(tool.url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            openTool.value = null;
            toolForm.reset();
        },
    });
}

const form = reactive<Filters>({ ...props.filters });
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const bulkStatusOpen = ref(false);
const bulkStatusForm = useForm({ ids: [] as number[], status: '', reason_code: '', reason: '' });
const bulkStatusAction = computed(() => props.extensions.actions.find((action) => action.key === 'status'));
const bulkStatusTarget = computed(() => props.bulkStatus?.options.find((option) => option.value === bulkStatusForm.status));
watch(() => bulkStatusForm.status, () => {
    bulkStatusForm.reset('reason_code');
    bulkStatusForm.clearErrors('status', 'reason_code', 'reason');
});

function openBulkStatus(): void {
    bulkStatusForm.resetAndClearErrors();
    bulkStatusForm.ids = [...selected.value];
    bulkStatusOpen.value = true;
}

function submitBulkStatus(): void {
    if (!bulkStatusAction.value || !bulkStatusTarget.value || !bulkStatusForm.reason_code || bulkStatusForm.processing) return;

    bulkStatusForm.post(bulkStatusAction.value.url, {
        preserveScroll: true,
        onSuccess: () => {
            bulkStatusOpen.value = false;
            selected.value = [];
        },
    });
}

const moreFilters = ref(['status', 'payment_status', 'payment_method', 'source', 'from', 'to'].some((key) => props.filters[key as keyof Filters] !== ''));
const options = (values: string[] | Record<string, string>, labels: Record<string, string>) =>
    (Array.isArray(values) ? values : Object.keys(values)).map((value) => ({ value, label: labels[value] ?? value }));
const totalCount = computed(() => Object.values(props.stageCounts).reduce((sum, count) => sum + count, 0));
const hasActiveFilters = computed(() => Object.values(form).some((value) => value !== '') || Object.values(ext).some((value) => value !== ''));
const columns = [
    { key: 'number', label: t('ordering::admin.orders_index.order'), sortable: true },
    { key: 'customer', label: t('ordering::admin.orders_index.customer') },
    { key: 'items', label: t('ordering::admin.orders_index.products') },
    { key: 'total', label: t('ordering::admin.orders_index.total'), align: 'right' as const, sortable: true },
    { key: 'stage', label: t('ordering::admin.orders_index.stage') },
    { key: 'payment_status', label: t('ordering::admin.orders_index.payment') },
];

const list = useListQuery(() => props.baseUrl);

/** Xuất Excel theo bộ lọc đang áp dụng (chuỗi truy vấn của trang), tối đa 10.000 đơn. */
function exportHref(mode: 'orders' | 'lines'): string {
    const query = new URLSearchParams(typeof window === 'undefined' ? '' : window.location.search);
    query.delete('page');
    query.delete('per_page');
    query.set('mode', mode);
    return `${props.exportUrl ?? ''}?${query}`;
}

function search(page = 1, pageSize = props.pagination.per_page, sort = props.sort): void {
    selected.value = [];
    list.visit({ ...form, ext: { ...ext }, ...list.sortQuery(sort), page, per_page: pageSize });
}

function openOrder(order: { id: number }): void {
    router.visit(`${props.baseUrl}/${order.id}${window.location.search}`);
}

function changePageSize(pageSize: number): void {
    search(1, pageSize);
}

function openStage(stage: string): void {
    form.stage = stage;
    form.aged = '';
    form.actionable = '';
    search();
}

function toggleActionable(): void {
    form.stage = '';
    form.aged = '';
    form.actionable = form.actionable === '1' ? '' : '1';
    search();
}

function toggleAgedQueue(): void {
    form.stage = '';
    form.actionable = '';
    form.aged = form.aged === '24' ? '' : '24';
    search();
}

function clearAged(): void {
    form.aged = '';
    search();
}

function clearFilters(): void {
    Object.assign(form, { stage: '', aged: '', actionable: '', status: '', payment_status: '', payment_method: '', source: '', from: '', to: '', q: '' });
    for (const key of Object.keys(ext)) {
        ext[key] = '';
    }
    search();
}

/** Tuổi đơn: đơn cần xử lý để lâu đổi màu (quá 24 giờ: cam, quá 48 giờ: đỏ). */
function ageText(hours: number | null): string {
    if (hours === null) return '';
    return hours < 24 ? t('ordering::admin.orders_index.hours_hours', { hours }) : t('ordering::admin.orders_index.hours_days', { hours: Math.floor(hours / 24) });
}
function ageClass(row: { age_hours: number | null; stage: string }): string {
    if (row.age_hours === null || !props.actionableStages.includes(row.stage)) return 'text-slate-400';
    return row.age_hours >= 48 ? 'font-medium text-red-600' : row.age_hours >= 24 ? 'text-amber-600' : 'text-slate-400';
}
</script>

<template>
    <Head :title="t('ordering::admin.orders_index.orders')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('ordering::admin.orders_index.orders') }}</p>
    <PageHeader :title="t('ordering::admin.orders_index.orders')" :subtitle="t('ordering::admin.orders_index.total_orders', { total: pagination.total })">
        <a v-if="exportUrl" :href="exportHref('orders')" :class="secondaryButton" data-export="orders">{{ t('ordering::admin.orders_index.export_excel') }}</a>
        <a v-if="exportUrl" :href="exportHref('lines')" :class="secondaryButton" data-export="lines">{{ t('ordering::admin.orders_index.export_sku_line') }}</a>
        <Link v-if="createUrl" :href="createUrl" :class="primaryButton" data-create-order>{{ t('ordering::admin.orders_index.create_order') }}</Link>
        <button v-for="tool in tools" :key="tool.key" type="button" :class="secondaryButton" :aria-expanded="openTool === tool.key" @click="toggleTool(tool.key)">
            {{ tool.label }}
        </button>
    </PageHeader>
    <template v-for="tool in tools" :key="`panel-${tool.key}`">
        <form v-if="openTool === tool.key" class="mb-4 space-y-2 rounded-lg border border-slate-200 bg-white p-4 text-sm" :data-tool="tool.key" @submit.prevent="submitTool(tool)">
            <p v-if="tool.help" class="text-slate-500">{{ tool.help }}</p>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <span>File</span>
                    <FileInput :model-value="toolForm.file" @update:model-value="toolForm.file = $event as File | null" :accept="tool.accept" :max-kb="tool.max_kb ?? null" :progress="toolForm.progress?.percentage ?? null" />
                </div>
                <label v-for="field in tool.fields ?? []" :key="field.key" class="flex flex-col gap-1">
                    <span>{{ field.label }}</span>
                    <input v-model="toolForm[field.key] as string" :class="inputClass" :placeholder="field.placeholder" />
                </label>
                <button type="submit" :class="primaryButton" :disabled="toolForm.processing || !toolForm.file">{{ tool.label }}</button>
            </div>
        </form>
    </template>
    <ul v-if="importErrors.length" class="mb-4 space-y-1 rounded bg-red-50 p-3 text-sm text-red-700" role="alert">
        <li v-for="message in importErrors" :key="message">{{ message }}</li>
    </ul>

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm" :aria-label="t('ordering::admin.orders_index.order_queue')">
        <button
            type="button"
            class="shrink-0 border-b-2 px-3 py-2"
            :class="form.stage === '' ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-current="form.stage === '' ? 'page' : undefined"
            @click="openStage('')"
        >
            {{ t('ordering::admin.orders_index.all') }} <span class="text-xs text-slate-400">{{ totalCount }}</span>
        </button>
        <button
            type="button"
            class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2"
            :class="form.actionable === '1' ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-pressed="form.actionable === '1'"
            @click="toggleActionable"
        >
            {{ t('ordering::admin.orders_index.needs_action') }} <span class="text-xs text-slate-400">{{ actionableStages.reduce((sum, stage) => sum + (stageCounts[stage] ?? 0), 0) }}</span>
        </button>
        <button
            v-for="stage in stages"
            :key="stage"
            type="button"
            class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2"
            :class="form.stage === stage ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-current="form.stage === stage ? 'page' : undefined"
            :data-stage="stage"
            @click="openStage(stage)"
        >
            {{ labels.stage[stage] ?? stage }}
            <span
                v-if="actionableStages.includes(stage) && stageCounts[stage] > 0"
                class="rounded-full bg-amber-100 px-1.5 text-xs font-medium text-amber-800"
                >{{ stageCounts[stage] }}</span
            >
            <span v-else class="text-xs text-slate-400">{{ stageCounts[stage] ?? 0 }}</span>
        </button>
        <button
            type="button"
            class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2"
            :class="form.aged === '24' ? 'border-red-600 font-medium text-red-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-pressed="form.aged === '24'"
            @click="toggleAgedQueue"
        >
            {{ t('ordering::admin.orders_index.over_24_hours') }} <span class="text-xs text-slate-400">{{ agedCount }}</span>
        </button>
    </nav>

    <p v-if="form.aged" class="mb-3 flex items-center justify-between rounded bg-red-50 px-3 py-2 text-sm text-red-800" data-aged-filter>
        {{ t('ordering::admin.orders_index.showing_orders_not_yet_confirmed', { aged: form.aged }) }}
        <button type="button" class="underline" @click="clearAged">{{ t('ordering::admin.orders_index.clear_filter') }}</button>
    </p>
    <form class="mb-4 space-y-2" @submit.prevent="search()">
        <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]">
            <input v-model="form.q" :class="inputClass" :placeholder="t('ordering::admin.orders_index.order_number_phone')" :aria-label="t('ordering::admin.orders_index.search_orders')" />
            <button type="button" :class="secondaryButton" :aria-expanded="moreFilters" @click="moreFilters = !moreFilters">{{ t('ordering::admin.orders_index.filters') }}</button>
            <button type="submit" :class="primaryButton">{{ t('ordering::admin.orders_index.filter') }}</button>
            <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">{{ t('ordering::admin.orders_index.clear_all_filters') }}</button>
        </div>
        <div v-if="moreFilters" class="grid gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <ExtensionFilters v-model="ext" :filters="extensions.filters" />
            <Select v-model="form.payment_method" :options="options(labels.payment_method, labels.payment_method)" :empty-label="t('ordering::admin.orders_index.all_payment_methods')" :aria-label="t('ordering::admin.orders_index.payment_method')" />
            <Select v-model="form.payment_status" :options="options(paymentStatuses, labels.payment_status)" :empty-label="t('ordering::admin.orders_index.all_payment_statuses')" :aria-label="t('ordering::admin.orders_index.payment_status')" />
            <Select v-model="form.source" :options="options(labels.source, labels.source)" :empty-label="t('ordering::admin.orders_index.all_order_sources')" :aria-label="t('ordering::admin.orders_index.order_source')" />
            <Select v-model="form.status" :options="options(statuses, labels.order_status)" :empty-label="t('ordering::admin.orders_index.all_order_statuses')" :aria-label="t('ordering::admin.orders_index.order_status')" />
            <DateInput v-model="form.from" :aria-label="t('ordering::admin.orders_index.placed_from')" />
            <DateInput v-model="form.to" :aria-label="t('ordering::admin.orders_index.placed')" />
        </div>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm" aria-live="polite">
        <span class="text-slate-500">{{ t('ordering::admin.orders_index.count_orders_current_page_selected', { count: selected.length }) }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" :selection-description="t('ordering::admin.orders_index.orders_current_page')">
            <template #action-status="{ action }">
                <button v-if="bulkStatus" type="button" :class="secondaryButton" :disabled="selected.length === 0 || bulkStatusForm.processing" data-extension-action="status" @click="openBulkStatus">
                    {{ action.label }}
                </button>
            </template>
        </ExtensionActions>
    </div>
    <Dialog v-if="bulkStatus" v-model:open="bulkStatusOpen" :title="t('ordering::admin.status_change.submit')" :subtitle="t('ordering::admin.orders_index.count_orders_current_page_selected', { count: bulkStatusForm.ids.length })">
        <form class="space-y-3" data-bulk-status @submit.prevent="submitBulkStatus">
            <FormField :label="t('ordering::admin.status_change.new_status')" :error="bulkStatusForm.errors.status">
                <Select v-model="bulkStatusForm.status" :options="bulkStatus.options" :empty-label="t('ordering::admin.orders_show.choose_status')" :aria-label="t('ordering::admin.status_change.new_status')" />
            </FormField>
            <template v-if="bulkStatusTarget">
                <FormField :label="t('ordering::admin.status_change.reason')" :error="bulkStatusForm.errors.reason_code">
                    <Select v-model="bulkStatusForm.reason_code" :options="bulkStatusTarget.reasons" :empty-label="t('ordering::admin.status_change.choose_reason')" :aria-label="t('ordering::admin.status_change.reason')" />
                </FormField>
                <FormField for="bulk-status-note" :label="t('ordering::admin.status_change.note')" :error="bulkStatusForm.errors.reason" :hint="bulkStatus.canOverride && ['processing', 'completed'].includes(bulkStatusForm.status) ? t('ordering::admin.status_change.bulk_override_note') : undefined">
                    <input id="bulk-status-note" v-model="bulkStatusForm.reason" :class="inputClass" maxlength="255" />
                </FormField>
            </template>
            <button type="submit" :class="primaryButton" :disabled="bulkStatusForm.processing || !bulkStatusForm.reason_code">{{ t('ordering::admin.status_change.submit') }}</button>
        </form>
    </Dialog>
    <DataTable v-model:selected="selected" :columns="columns" :rows="orders" :extensions="extensions" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, pagination.per_page, next)" :row-label="(order) => order.number" :row-click="openOrder" :empty-text="t('ordering::admin.orders_index.no_orders')">
        <template #cell-number="{ row }">
            <span class="font-mono">{{ row.number }}</span>
            <div class="text-xs text-slate-400">{{ row.placed_at }}</div>
            <div class="text-xs" :class="ageClass(row)">{{ ageText(row.age_hours) }}<template v-if="row.source !== 'web'"> · {{ labels.source[row.source] ?? row.source }}</template></div>
        </template>
        <template #cell-customer="{ row }">
            {{ row.customer }}
            <div class="text-xs text-slate-400">{{ row.phone }}<template v-if="row.province"> · {{ row.province }}</template></div>
            <div v-if="customerRiskFlag(row.history) || row.risks.length" class="mt-1 flex flex-wrap gap-1">
                <StatusBadge v-if="customerRiskFlag(row.history)" :label="customerRiskFlag(row.history)!.label" :tone="customerRiskFlag(row.history)!.tone" />
                <span v-for="risk in row.risks" :key="`${risk.source}:${risk.label}`" :title="risk.reason ?? undefined" data-risk><StatusBadge :label="risk.label" :tone="riskTone(risk.level)" /></span>
            </div>
        </template>
        <template #cell-items="{ row }">
            <div v-for="item in row.items.slice(0, 2)" :key="item.sku" class="font-mono text-xs">{{ item.sku }} × {{ item.quantity }}</div>
            <div v-if="row.items.length > 2" class="text-xs text-slate-400">{{ t('ordering::admin.orders_index.items_products', { items: row.items.length - 2 }) }}</div>
        </template>
        <template #cell-total="{ row }"><MoneyText :amount="row.total" /></template>
        <template #cell-stage="{ row }">
            <StatusBadge :label="labels.stage[row.stage] ?? row.stage" :tone="stageTone(row.stage)" />
            <div v-if="row.delivery_issue" class="mt-1 text-xs font-medium text-red-600" data-delivery-issue>{{ labels.delivery_issue[row.delivery_issue] ?? row.delivery_issue }}</div>
            <div v-else class="mt-1 text-xs text-slate-400">{{ labels.order_status[row.order_status] ?? row.order_status }}</div>
        </template>
        <template #cell-payment_status="{ row }">
            <StatusBadge :label="labels.payment_status[row.payment_status] ?? row.payment_status" :tone="paymentStatusTone(row.payment_status)" />
            <div class="mt-1 text-xs text-slate-400">{{ labels.payment_method[row.payment_method] ?? row.payment_method }}</div>
        </template>
    </DataTable>

    <Pagination :current="pagination.current" :last="pagination.last" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="[30, 50, 100]" @change="search" @per-page-change="changePageSize" />
</template>
