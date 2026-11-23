<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import Dialog from '@admin/Components/Dialog.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useMoney } from '@admin/money';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

type Props = {
    baseUrl: string;
    labels: { payment_status: Record<string, string> };
    status: string | null;
    search: string;
    statuses: string[];
    payments: Array<{
        id: number;
        order_number: string | null;
        order_url: string;
        gateway: string;
        status: string;
        amount: number;
        refunded_amount: number;
        created_at: string | null;
        expires_at: string | null;
        can_confirm: boolean;
        can_collect: boolean;
        can_capture: boolean;
        can_refund: boolean;
    }>;
    refunds: Array<{ id: number; payment_id: number; amount: number; reason: string; paying_out: boolean; created_at: string | null; order_number: string | null; order_url: string | null }>;
    can: { confirm: boolean; refund: boolean };
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    sort: ListSortState | null;
    perPageOptions: number[];
    /** Cột/lọc/thao tác hàng loạt/công cụ của plugin (EP v3 X1). */
    extensions: ExtensionList;
    /** Kênh chuyển trả của plugin (EP v3 X5). */
    payouts: Array<{ code: string; label: string }>;
    /** Dòng đối soát lệch chờ xử lý (EP v3 X5). */
    reconciliationUrl: string;
    reconciliation: Array<{ id: number; source: string; reference: string; order_id: number | null; order_number: string | null; order_url: string | null; expected: number | null; actual: number | null; note: string | null; created_at: string }>;
};

const props = defineProps<Props>();

const label = (status: string): string => props.labels.payment_status[status] ?? status;
const tone = (status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' =>
    (({ pending: 'warning', authorized: 'info', paid: 'success', failed: 'danger', expired: 'danger' }) as const)[status as 'pending'] ?? 'neutral';
const columns = [
    { key: 'order_number', label: t('payment::admin.payments_index.order'), class: 'font-mono' },
    { key: 'gateway', label: t('payment::admin.payments_index.method') },
    { key: 'amount', label: t('payment::admin.payments_index.amount'), sortable: true },
    { key: 'status', label: t('payment::admin.payments_index.status') },
    { key: 'created_at', label: t('payment::admin.payments_index.created'), class: 'text-xs', sortable: true },
    { key: 'actions', label: '', align: 'right' as const },
];
const refunding = ref<null | { id: number; max: number; order: string | null }>(null);
const refundForm = useForm({ amount: 0, reason: '', idempotency_key: '' });
const refundReasons = computed(() => ['reason_return', 'reason_missing', 'reason_overpayment'].map((reason) => t(`payment::admin.payments_index.${reason}`)));
const refundOpen = computed({
    get: () => refunding.value !== null,
    set: (open: boolean) => {
        if (!open) refunding.value = null;
    },
});
const money = useMoney();
const search = ref(props.search);
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const checkedMismatches = ref<number[]>([]);
const reconciliationForm = useForm({ ids: [] as number[], note: '' });
watch(() => props.reconciliation, (entries) => {
    const visibleIds = new Set(entries.map((entry) => entry.id));
    checkedMismatches.value = checkedMismatches.value.filter((id) => visibleIds.has(id));
});

const list = useListQuery(() => props.baseUrl);
function filter(status: string, page?: number, perPage = props.pagination.per_page, sort = props.sort): void {
    list.visit({ status, q: search.value.trim(), ext: { ...ext }, ...list.sortQuery(sort), page, per_page: perPage });
}

function hasActions(row: Props['payments'][number]): boolean {
    return ((row.can_confirm || row.can_collect || row.can_capture) && props.can.confirm) || (row.can_refund && props.can.refund);
}

function hint(row: Props['payments'][number]): string | null {
    if (row.status === 'pending' && !row.can_confirm && !row.can_collect) {
        return t('payment::admin.payments_index.waiting_customer_pay_via_gateway');
    }
    if (row.status === 'authorized') {
        return t('payment::admin.payments_index.collected_automatically_when_shipment_leaves');
    }

    return null;
}

function goToPage(page: number): void {
    filter(props.status ?? '', page);
}

function changePageSize(perPage: number): void {
    filter(props.status ?? '', undefined, perPage);
}

async function collectPayment(row: { id: number; amount: number }): Promise<void> {
    const note = await confirmAction({
        title: t('payment::admin.payments_index.record_cash_collected_delivery'),
        message: t('payment::admin.payments_index.use_when_store_delivers_itself'),
        confirmLabel: t('payment::admin.payments_index.cash_collected'),
        reason: { label: t('payment::admin.payments_index.note'), placeholder: t('payment::admin.payments_index.store_courier_handed_cash'), required: true },
    });
    if (note !== false) {
        router.post(`${props.baseUrl}/${row.id}/collect`, { note }, { preserveScroll: true });
    }
}

async function capturePayment(id: number): Promise<void> {
    if ((await confirmAction({ title: t('payment::admin.payments_index.capture_authorised_payment'), confirmLabel: t('payment::admin.payments_index.capture') })) !== false) {
        router.post(`${props.baseUrl}/${id}/capture`, {}, { preserveScroll: true });
    }
}

async function confirmPayment(id: number): Promise<void> {
    const note = await confirmAction({ title: t('payment::admin.payments_index.confirm_money_was_received'), confirmLabel: t('payment::admin.payments_index.confirmed'), reason: { label: t('payment::admin.payments_index.confirmation_note'), placeholder: t('payment::admin.payments_index.bank_transaction_id'), required: true } });
    if (note !== false) {
        router.post(`${props.baseUrl}/${id}/confirm`, { note }, { preserveScroll: true });
    }
}

function openRefund(payment: Props['payments'][number]): void {
    refunding.value = { id: payment.id, max: payment.amount - payment.refunded_amount, order: payment.order_number };
    refundForm.amount = refunding.value.max;
    refundForm.reason = '';
    refundForm.idempotency_key = crypto.randomUUID().replaceAll('-', '').slice(0, 24);
    refundForm.clearErrors();
    refundForm.defaults();
}

function submitRefund(): void {
    if (!refunding.value || refundForm.processing) return;
    refundForm.post(`${props.baseUrl}/${refunding.value!.id}/refunds`, { preserveScroll: true, onSuccess: () => (refunding.value = null) });
}

async function resolveSelected(): Promise<void> {
    const ids = [...checkedMismatches.value];
    if (!ids.length || reconciliationForm.processing) return;
    const note = await confirmAction({
        title: t('payment::admin.payments_index.resolve_count', { count: ids.length }),
        message: t('payment::admin.payments_index.common_resolution_hint'),
        confirmLabel: t('payment::admin.payments_index.resolved'),
        reason: { label: t('payment::admin.payments_index.resolution'), maxlength: 255, required: true },
    });
    if (note === false) return;
    reconciliationForm.ids = ids;
    reconciliationForm.note = note;
    reconciliationForm.post(props.reconciliationUrl, { preserveScroll: true, onSuccess: () => (checkedMismatches.value = []) });
}

async function payout(id: number, channel: { code: string; label: string }): Promise<void> {
    if ((await confirmAction({ title: t('payment::admin.payments_index.pay_out_refund_via_label', { label: channel.label }), confirmLabel: t('payment::admin.payments_index.pay_out') })) !== false) {
        router.post(props.baseUrl.replace(/\/payments$/, `/refunds/${id}/payout/${channel.code}`), {}, { preserveScroll: true });
    }
}

async function resolveEntry(id: number): Promise<void> {
    const note = await confirmAction({ title: t('payment::admin.payments_index.mark_reconciliation_line_resolved'), confirmLabel: t('payment::admin.payments_index.resolved'), reason: { label: t('payment::admin.payments_index.resolution'), placeholder: t('payment::admin.payments_index.customer_underpaid_called_top_up'), required: true } });
    if (note !== false) {
        router.post(props.baseUrl.replace(/\/payments$/, `/reconciliation/${id}/resolve`), { note }, { preserveScroll: true });
    }
}

async function completeRefund(id: number): Promise<void> {
    const note = await confirmAction({ title: t('payment::admin.payments_index.refund_paid_customer'), confirmLabel: t('payment::admin.payments_index.refund_paid'), reason: { label: t('payment::admin.payments_index.note'), placeholder: t('payment::admin.payments_index.refund_transfer_transaction_id'), required: true } });
    if (note !== false) {
        router.post(props.baseUrl.replace(/\/payments$/, `/refunds/${id}/complete`), { note }, { preserveScroll: true });
    }
}
</script>

<template>
    <div>
    <Head :title="t('payment::admin.payments_index.payment')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('payment::admin.payments_index.payment') }}</p>
    <PageHeader :title="t('payment::admin.payments_index.payment')" :subtitle="t('payment::admin.payments_index.confirm_bank_transfers_record_cod')" />
    <ExtensionTools :tools="extensions.tools ?? []" />

    <form class="mb-3 flex max-w-md gap-2" @submit.prevent="filter(status ?? '')">
        <input v-model="search" :class="inputClass" type="search" :aria-label="t('payment::admin.payments_index.search')" :placeholder="t('payment::admin.payments_index.search_order_number_vn2611_000006')" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">{{ t('payment::admin.payments_index.search') }}</button>
    </form>

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">{{ t('payment::admin.payments_index.all') }}</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">{{ label(value) }}</button>
    </div>

    <section v-if="reconciliation.length" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4" data-reconciliation>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">{{ t('payment::admin.payments_index.reconciliation_mismatches') }}</h2>
            <button v-if="can.confirm && checkedMismatches.length" type="button" :class="secondaryButton" :disabled="reconciliationForm.processing" data-resolve-mismatches @click="resolveSelected">{{ t('payment::admin.payments_index.resolve_count', { count: checkedMismatches.length }) }}</button>
        </div>
        <p v-for="(error, field) in reconciliationForm.errors" :key="field" class="text-sm text-red-600" role="alert">{{ error }}</p>
        <div v-for="entry in reconciliation" :key="entry.id" class="flex items-center justify-between gap-3 border-t border-amber-100 py-2 text-sm">
            <input v-if="can.confirm" v-model="checkedMismatches" type="checkbox" :value="entry.id" :aria-label="entry.reference" :disabled="reconciliationForm.processing" />
            <span class="min-w-0 flex-1 wrap-anywhere">
                <Link v-if="entry.order_url && entry.order_number" :href="entry.order_url" class="mr-2 font-mono text-xs text-indigo-600 hover:underline">{{ entry.order_number }}</Link>
                <span class="font-mono text-xs">{{ entry.reference }}</span> · {{ entry.note }}
                <template v-if="entry.expected !== null"> {{ t('payment::admin.payments_index.expected') }} <MoneyText :amount="entry.expected" /></template>
                <template v-if="entry.actual !== null"> {{ t('payment::admin.payments_index.received') }} <MoneyText :amount="entry.actual" /></template>
                <span class="text-xs text-slate-500"> · {{ entry.source }}</span>
            </span>
            <button v-if="can.confirm" type="button" class="shrink-0 text-indigo-600 hover:underline" :disabled="reconciliationForm.processing" @click="resolveEntry(entry.id)">{{ t('payment::admin.payments_index.resolved') }}</button>
        </div>
    </section>

    <section v-if="refunds.length" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4" data-refund-queue>
        <h2 class="mb-2 font-semibold">{{ t('payment::admin.payments_index.awaiting_manual_refund') }}</h2>
        <div v-for="refund in refunds" :key="refund.id" class="flex flex-wrap items-center justify-between gap-2 border-t border-amber-100 py-2 text-sm">
            <span class="min-w-0 wrap-anywhere"><Link v-if="refund.order_url && refund.order_number" :href="refund.order_url" class="mr-2 font-mono text-xs text-indigo-600 hover:underline">{{ refund.order_number }}</Link><MoneyText :amount="refund.amount" /> · {{ refund.reason }} · {{ refund.created_at }}<template v-if="refund.paying_out"> · <strong data-paying-out>{{ t('payment::admin.payments_index.paying_out_check_bank_before') }}</strong></template></span>
            <span v-if="can.refund" class="flex flex-wrap gap-3">
                <button v-for="channel in refund.paying_out ? [] : payouts" :key="channel.code" type="button" class="text-indigo-600 hover:underline" :data-payout="channel.code" @click="payout(refund.id, channel)">{{ t('payment::admin.payments_index.pay_out_via_label', { label: channel.label }) }}</button>
                <button type="button" class="text-indigo-600 hover:underline" @click="completeRefund(refund.id)">{{ t('payment::admin.payments_index.refund_paid') }}</button>
            </span>
        </div>
    </section>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">{{ t('payment::admin.payments_index.count_selected', { count: selected.length }) }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" :selection-description="t('payment::admin.payments_index.payments')" />
    </div>
    <DataTable v-model:selected="selected" :sort="sort" :loading="list.loading.value" @sort="(next) => filter(props.status ?? '', 1, pagination.per_page, next)" :extensions="extensions" :row-label="(row) => row.order_number ?? String(row.id)" :columns="columns" :rows="payments" :empty-text="search ? t('payment::admin.payments_index.no_payments_order') : t('payment::admin.payments_index.no_payments_yet')">
        <template #cell-order_number="{ row }">
            <Link :href="row.order_url" class="text-indigo-600 hover:underline">{{ row.order_number ?? '—' }}</Link>
        </template>
        <template #cell-amount="{ row }">
            <MoneyText :amount="row.amount" />
            <div v-if="row.refunded_amount" class="text-xs text-slate-500">{{ t('payment::admin.payments_index.refunded') }} <MoneyText :amount="row.refunded_amount" /></div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="label(row.status)" :tone="tone(row.status)" />
            <div v-if="row.status === 'pending' && row.expires_at" class="mt-1 text-xs text-slate-400">{{ t('payment::admin.payments_index.expires_expires', { expires_at: row.expires_at }) }}</div>
        </template>
        <template #cell-actions="{ row }">
            <div v-if="hasActions(row)" class="flex flex-wrap justify-end gap-3">
                <button v-if="row.can_confirm && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="confirmPayment(row.id)">{{ t('payment::admin.payments_index.confirm_money_received') }}</button>
                <button v-if="row.can_collect && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="collectPayment(row)">{{ t('payment::admin.payments_index.cash_collected') }}</button>
                <button v-if="row.can_capture && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="capturePayment(row.id)">{{ t('payment::admin.payments_index.capture') }}</button>
                <button v-if="row.can_refund && can.refund" type="button" class="text-indigo-600 hover:underline" @click="openRefund(row)">{{ t('payment::admin.payments_index.refund') }}</button>
            </div>
            <span v-else-if="hint(row)" class="text-xs text-slate-400">{{ hint(row) }}</span>
        </template>
    </DataTable>
    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="goToPage" @per-page-change="changePageSize" />

    <Dialog v-model:open="refundOpen" :title="t('payment::admin.payments_index.refund')" :subtitle="refunding ? t('payment::admin.payments_index.most_refunding', { refunding: money(refunding.max) }) : undefined" :dirty="refundForm.isDirty && !refundForm.processing">
        <template #default>
            <form v-if="refunding" id="refund-form" class="grid gap-3" data-refund-form @submit.prevent="submitRefund">
                <p v-if="refunding.order" class="text-sm text-slate-600">{{ t('payment::admin.payments_index.order') }} <strong>{{ refunding.order }}</strong></p>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">{{ t('payment::admin.payments_index.refund_amount') }}</span>
                    <input v-model.number="refundForm.amount" :class="inputClass" type="number" min="1" :max="refunding.max" required />
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">{{ t('payment::admin.payments_index.reason') }}</span>
                    <input v-model="refundForm.reason" :class="inputClass" :placeholder="t('payment::admin.payments_index.customer_returned_items_short_delivery')" maxlength="255" required />
                </label>
                <div class="flex flex-wrap gap-2">
                    <button v-for="reason in refundReasons" :key="reason" type="button" :class="secondaryButton" class="text-xs" :aria-pressed="refundForm.reason === reason" @click="refundForm.reason = reason">{{ reason }}</button>
                </div>
                <p v-for="(error, key) in refundForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
            </form>
        </template>
        <template #footer="{ close }">
            <button type="submit" form="refund-form" :class="primaryButton" :disabled="refundForm.processing">{{ t('payment::admin.payments_index.refund') }}</button>
            <button type="button" :class="secondaryButton" @click="close">{{ t('payment::admin.payments_index.cancel') }}</button>
        </template>
    </Dialog>
    </div>
</template>
