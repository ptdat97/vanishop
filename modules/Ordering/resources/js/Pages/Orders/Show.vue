<script setup lang="ts">
import { t } from '@admin/i18n';
import Combobox from '@admin/Components/Combobox.vue';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { toast } from '@admin/toast';
import { confirmAction, promptFields, type ConfirmField } from '@admin/confirm';
import { useAdminForm, useLeaveGuard } from '@admin/forms';
import { useMoney } from '@admin/money';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { customerRiskFlag, orderStatusTone, paymentStatusTone, type RiskSignal, riskTone, stageTone } from '../../statusTones';

type Division = { code: string; name: string };
/** Thao tác trên thẻ bên phải (0.3.96) — module sở hữu thẻ (Fulfillment, Payment…) kiểm tra quyền và xử lý POST. */
type PanelAction = {
    key: string;
    label: string;
    url: string;
    data?: Record<string, string | number | boolean>;
    fields?: ConfirmField[];
    confirm?: string;
    tone?: 'primary' | 'secondary' | 'danger';
    primary?: boolean;
};
type OrderLine = {
    id: number;
    cancelled_quantity: number;
    sku: string;
    name: string;
    variant_label: string;
    quantity: number;
    unit_amount: number;
    compare_at_amount: number | null;
    discount_amount: number;
    total_amount: number;
    tax_amount: number;
    options: Record<string, Record<string, string | number | boolean | null>>;
};

const props = defineProps<{
    baseUrl: string;
    listUrl: string;
    order: {
        id: number;
        number: string;
        orderStatus: string;
        paymentStatus: string;
        fulfillmentStatus: string;
        paymentMethod: string;
        customerStatus: { code: string; label: string };
        amounts: {
            subtotal: number;
            discount: number;
            shipping: number;
            tax: number;
            total: number;
        };
        customer: { full_name: string; phone: string; email: string | null };
        shippingAddress: Record<string, string>;
        shippingMethod: { label?: string; fee?: number };
        note: string | null;
        placedAt: string;
        lines: OrderLine[];
        adjustments: Array<{
            type: string;
            code: string | null;
            label: string;
            amount: number;
        }>;
        pricing: {
            list_amount: number;
            markdown: number;
            subtotal: number;
            promotion_discount: number;
            voucher_discount: number;
            other_discount: number;
            shipping: number;
            tax_included: number;
            total: number;
            promotions: Array<{ promotion_id: number; name: string; code: string | null; voucher: boolean; amount: number }>;
            price_lists: Array<{ code: string; markdown: number }>;
        };
        events: Array<{
            type: string;
            from: string | null;
            to: string | null;
            reason: string | null;
            source: string | null;
            actor: string | null;
            data: Record<string, unknown> | null;
            at: string;
            ts?: number;
        }>;
        lockVersion: number;
    };
    panels: Array<{
        title: string;
        rows: Array<{ label: string; value: string }>;
        link?: { label: string; url: string };
        actions?: PanelAction[];
    }>;
    stage: string;
    deliveryIssue: string | null;
    /** Thẻ "Trạng thái đơn" (0.3.98): 4 chiều trạng thái + thao tác của Core và plugin (OrderStatusAction). */
    statusCard: {
        rows: Array<{ key: 'order' | 'payment' | 'fulfillment' | 'return'; label: string; code: string; text: string }>;
        actions: PanelAction[];
        /** Ô chọn trạng thái (0.3.99): theo trình tự (`normal`), ngoài trình tự (`forced`) hoặc bị chặn (`blocked`). */
        change: {
            url: string;
            options: Array<{ value: string; label: string; mode: 'normal' | 'forced' | null; permission: string | null; blocked: string | null; allowed: boolean; reasons: Array<{ value: string; label: string }> }>;
        };
    };
    /** Tín hiệu rủi ro của plugin khi đơn còn chờ xác nhận (EP v3 X2). */
    risks: RiskSignal[];
    customerHistory: { orders: number; delivered: number; returned_to_sender: number; cancelled: number; orders_url: string | null };
    printUrl: string;
    timeline: Array<{ ts: number; at: string; title: string; detail: string | null; source: string | null }>;
    can: {
        confirm: boolean;
        cancel: boolean;
        cancel_lines: boolean;
        change_address: boolean;
        note: boolean;
    };
    extensions: ExtensionDetail;
    labels: {
        order_status: Record<string, string>;
        payment_status: Record<string, string>;
        event_type: Record<string, string>;
        stage: Record<string, string>;
        delivery_issue: Record<string, string>;
        cancellation_reasons: Record<string, string>;
        customer_cancellation_reasons: Record<string, string>;
        status_reasons: Record<string, Record<string, string>>;
    };
    addressDirectory: {
        provinces: Division[];
        wards: Division[];
        wards_url: string;
    } | null;
}>();

const money = useMoney();

/** Thanh tiến trình: bước hiện tại theo giai đoạn vận hành (đơn huỷ không hiện). */
const steps = [t('ordering::admin.orders_show.placed'), t('ordering::admin.orders_show.confirmed'), t('ordering::admin.orders_show.handed_carrier'), t('ordering::admin.orders_show.transit'), t('ordering::admin.orders_show.delivered'), t('ordering::admin.orders_show.completed')];
const currentStep = computed(
    () => ({ to_confirm: 1, awaiting_payment: 1, to_ship: 2, shipping: 3, issue: 3, delivered: 4, returns: 4, completed: 6 })[props.stage] ?? -1,
);
/** Bước tiếp theo do thẻ bên phải đề xuất (Tạo vận đơn, Nhập mã, Đã bàn giao, Đã giao, Xác nhận đã nhận tiền…). */
const nextAction = computed(() => [...props.statusCard.actions, ...props.panels.flatMap((panel) => panel.actions ?? [])].find((action) => action.primary) ?? null);
function statusRowTone(row: { key: string; code: string }): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    if (row.key === 'order') return orderStatusTone(row.code);
    if (row.key === 'payment') return paymentStatusTone(row.code);
    if (row.key === 'fulfillment') return ({ delivered: 'success', shipped: 'info', partially_shipped: 'info', allocated: 'warning', returned_to_sender: 'danger' } as const)[row.code as 'delivered'] ?? 'neutral';
    return row.code === 'none' ? 'neutral' : ['returned', 'partially_returned'].includes(row.code) ? 'success' : 'warning';
}
/** Lịch sử gộp: sự kiện của đơn + vận đơn/thanh toán, theo thời gian. */
function eventReasonLabel(event: typeof props.order.events[number]): string | null {
    const code = event.data?.reason_code;
    if (!event.to || typeof code !== 'string' || !['status_changed', 'status_overridden'].includes(event.type)) return null;

    if (event.to === 'cancelled') {
        const reasons = event.source === 'customer' ? props.labels.customer_cancellation_reasons : props.labels.cancellation_reasons;
        return reasons[code] ?? code;
    }
    return props.labels.status_reasons[event.to]?.[code] ?? code;
}
const history = computed(() =>
    [
        ...props.order.events.map((event, index) => {
            const reasonLabel = eventReasonLabel(event);
            const note = reasonLabel ? event.reason?.replace(/^(staff_changed|staff_completed|override): /, '') : event.reason;
            return { kind: 'order' as const, ts: event.ts ?? 0, order: index, event, reasonLabel, note: note === reasonLabel ? null : note };
        }),
        ...props.timeline.map((entry, index) => ({ kind: 'module' as const, ts: entry.ts, order: index, entry })),
    ].sort((a, b) => a.ts - b.ts),
);
// Lỗi của thao tác ở bảng điều khiển (nhập qua hộp thoại, không có ô trên trang) → toast lỗi.
watch(
    () => ['tracking_number', 'service_code', 'amount', 'idempotency_key', 'status', 'order_id'].map((key) => errors.value[key]).filter((message): message is string => Boolean(message)),
    (messages) => messages.forEach((message) => toast(message, 'error')),
);

async function runAction(action: PanelAction): Promise<void> {
    let values: Record<string, string> = {};
    if (action.fields?.length) {
        const result = await promptFields({ title: action.label, confirmLabel: action.label, tone: action.tone === 'danger' ? 'danger' : 'primary', fields: action.fields });
        if (result === false) return;
        values = result;
    } else if (action.confirm && (await confirmAction({ title: action.confirm, confirmLabel: action.label, tone: action.tone === 'danger' ? 'danger' : 'primary' })) === false) {
        return;
    }
    router.post(action.url, { ...(action.data ?? {}), ...values }, { preserveScroll: true });
}
/** Nhãn rủi ro khách theo lịch sử nhận hàng (SĐT): từng hoàn hàng / khách mới / khách quen. */
const customerFlag = computed(() => customerRiskFlag(props.customerHistory));
const statusForm = useForm({ status: '', reason_code: '', reason: '' });
const statusOptions = computed(() =>
    props.statusCard.change.options
        .filter((option) => option.value !== props.order.orderStatus)
        .map((option) => ({ value: option.value, label: option.mode === 'forced' ? t('ordering::admin.orders_show.label_out_sequence', { label: option.label }) : option.label, disabled: !option.allowed })),
);
const chosenStatus = computed(() => props.statusCard.change.options.find((option) => option.value === statusForm.status) ?? null);
const canChangeStatus = computed(() => statusOptions.value.some((option) => !option.disabled));
watch(() => statusForm.status, () => {
    statusForm.reset('reason_code');
    statusForm.clearErrors('status', 'reason_code', 'reason');
});

function changeStatus(): void {
    statusForm.post(props.statusCard.change.url, { preserveScroll: true, onSuccess: () => statusForm.reset() });
}
const actionClass = (action: PanelAction): string => (action.tone === 'primary' ? primaryButton : action.tone === 'danger' ? dangerButton : secondaryButton);
const url = computed(() => `${props.baseUrl}/${props.order.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const editingAddress = ref(false);
const addressData = () => ({
    street_line: props.order.shippingAddress.street_line ?? '',
    ward_name: props.order.shippingAddress.ward_name ?? '',
    ward_code: props.order.shippingAddress.ward_code ?? '',
    province_name: props.order.shippingAddress.province_name ?? '',
    province_code: props.order.shippingAddress.province_code ?? '',
    reason: '',
    lock_version: props.order.lockVersion,
});
const { form: addressForm, save: submitAddress } = useAdminForm(addressData(), { leaveGuard: false, lockVersion: () => props.order.lockVersion });
// Khoá lạc quan: lấy phiên bản đơn **lúc bắt đầu sửa** (mở form) — các thao tác trước đó trên trang (đổi trạng thái, huỷ một phần…)
// đã tăng phiên bản; chỉ thay đổi của người khác trong lúc đang sửa mới bị báo xung đột.
watch(editingAddress, (open) => {
    if (open) {
        addressForm.defaults(addressData());
        addressForm.reset();
        addressForm.clearErrors();
        wards.value = props.addressDirectory?.wards ?? [];
        wardsError.value = false;
    }
});
const wards = ref<Division[]>(props.addressDirectory?.wards ?? []);
const wardsError = ref(false);
const noteForm = useForm({ note: '' });
const cancellingLines = ref(false);
const cancelLinesData = () => ({
    lines: Object.fromEntries(props.order.lines.map((line) => [line.id, 0])) as Record<number, number>,
    reason: '',
    cause: 'shop',
    lock_version: props.order.lockVersion,
});
const { form: cancelLinesForm, save: submitCancelLines } = useAdminForm(cancelLinesData(), { leaveGuard: false, lockVersion: () => props.order.lockVersion });
watch(cancellingLines, (open) => {
    if (open) {
        cancelLinesForm.defaults(cancelLinesData());
        cancelLinesForm.reset();
        cancelLinesForm.clearErrors();
    }
});
useLeaveGuard(() => (editingAddress.value && addressForm.isDirty) || (cancellingLines.value && cancelLinesForm.isDirty));
const cancelTotal = computed(() => Object.values(cancelLinesForm.lines).reduce((sum, quantity) => sum + Number(quantity || 0), 0));

function cancelLines(): void {
    submitCancelLines('post', `${url.value}/cancel-lines`, { preserveScroll: true, onSuccess: () => { cancellingLines.value = false; } });
}

async function loadWards(): Promise<void> {
    addressForm.ward_code = '';
    wards.value = [];
    wardsError.value = false;
    if (!props.addressDirectory || !addressForm.province_code) return;
    try {
        const response = await fetch(`${props.addressDirectory.wards_url}/${encodeURIComponent(addressForm.province_code)}/wards`, {
            headers: { Accept: 'application/json' },
        });
        wards.value = ((await response.json()) as { data: Division[] }).data;
    } catch {
        wardsError.value = true;
    }
}
const provinceOptions = computed(() => (props.addressDirectory?.provinces ?? []).map((province) => ({ value: province.code, label: province.name })));
const wardOptions = computed(() => wards.value.map((ward) => ({ value: ward.code, label: ward.name })));
const causeOptions = [
    { value: 'shop', label: t('ordering::admin.orders_show.store_error_out_stock_wrong') },
    { value: 'customer', label: t('ordering::admin.orders_show.customer_asked_remove_items') },
];
const lineColumns = computed(() => [
    { key: 'name', label: t('ordering::admin.orders_show.products') },
    { key: 'quantity', label: 'SL' },
    ...(cancellingLines.value ? [{ key: 'cancel', label: t('ordering::admin.orders_show.cancel') }] : []),
    { key: 'unit_amount', label: t('ordering::admin.orders_show.unit_price') },
    { key: 'discount_amount', label: t('ordering::admin.orders_show.reduce') },
    { key: 'total_amount', label: t('ordering::admin.orders_show.amount'), align: 'right' as const },
]);

/** Trạng thái đích của sự kiện lịch sử: trạng thái đơn hoặc thanh toán, chưa có nhãn thì in mã. */
function statusLabel(status: string): string {
    return props.labels.order_status[status] ?? props.labels.payment_status[status] ?? status;
}

function saveAddress(): void {
    submitAddress('put', `${url.value}/shipping-address`, {
        onSuccess: () => { editingAddress.value = false; },
    });
}

function addNote(): void {
    noteForm.post(`${url.value}/notes`, {
        preserveScroll: true,
        onSuccess: () => noteForm.reset(),
    });
}
</script>

<template>
    <Head :title="t('ordering::admin.orders_show.order_number', { number: order.number })" />
    <p class="mb-2 text-sm text-slate-500">{{ t('ordering::admin.orders_show.orders') }}</p>
    <div class="mb-2 flex flex-wrap gap-2">
        <StatusBadge :label="labels.stage[stage] ?? stage" :tone="stageTone(stage)" />
        <StatusBadge :label="labels.order_status[order.orderStatus] ?? order.orderStatus" :tone="orderStatusTone(order.orderStatus)" />
        <StatusBadge :label="labels.payment_status[order.paymentStatus] ?? order.paymentStatus" :tone="paymentStatusTone(order.paymentStatus)" />
    </div>
    <PageHeader :title="t('ordering::admin.orders_show.order_number', { number: order.number })" :subtitle="`${order.placedAt} · ${order.customerStatus.label}`">
        <Link :href="listUrl" :class="secondaryButton">{{ t('ordering::admin.orders_show.list') }}</Link>
        <a :href="printUrl" target="_blank" rel="noopener" :class="secondaryButton">{{ t('ordering::admin.orders_show.print_packing_slip') }}</a>
        <span v-if="stage === 'to_confirm' && customerFlag" class="self-center" data-confirm-risk><StatusBadge :label="customerFlag.label" :tone="customerFlag.tone" /></span>
        <span v-for="risk in risks" :key="`${risk.source}:${risk.label}`" class="self-center" :title="risk.reason ?? undefined" data-risk><StatusBadge :label="risk.label" :tone="riskTone(risk.level)" /></span>
        <button v-if="nextAction" type="button" :class="primaryButton" data-next-action @click="runAction(nextAction)">{{ nextAction.label }}</button>
        <button v-if="can.cancel_lines && !cancellingLines" type="button" :class="secondaryButton" @click="cancellingLines = true">{{ t('ordering::admin.orders_show.partial_cancellation') }}</button>
        <ExtensionActions :actions="extensions.actions" :ids="[order.id]" />
    </PageHeader>
    <ol v-if="currentStep >= 0" class="mb-4 flex flex-wrap items-center gap-x-1 gap-y-2 text-xs" :aria-label="t('ordering::admin.orders_show.order_progress')">
        <li v-for="(step, index) in steps" :key="step" class="flex items-center gap-1">
            <span
                class="rounded-full px-2.5 py-1"
                :class="index < currentStep ? 'bg-emerald-100 text-emerald-800' : index === currentStep ? (stage === 'issue' ? 'bg-red-100 font-medium text-red-700' : 'bg-indigo-600 font-medium text-white') : 'bg-slate-100 text-slate-500'"
                :aria-current="index === currentStep ? 'step' : undefined"
                >{{ index === currentStep && stage === 'issue' ? (labels.delivery_issue[deliveryIssue ?? ''] ?? labels.stage.issue) : step }}</span
            >
            <span v-if="index < steps.length - 1" class="text-slate-300">›</span>
        </li>
    </ol>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-slate-200 bg-white">
                <DataTable :columns="lineColumns" :rows="order.lines" :row-key="(line) => line.sku" bare>
                    <template #cell-name="{ row }">
                        {{ row.name }}
                        <div class="font-mono text-xs text-slate-400">{{ row.sku }}<template v-if="row.variant_label"> · {{ row.variant_label }}</template></div>
                        <template v-for="(values, plugin) in row.options" :key="plugin">
                            <div v-for="(value, field) in values" :key="field" class="text-xs text-amber-700">{{ field }}: {{ value }}</div>
                        </template>
                    </template>
                    <template #cell-quantity="{ row }">
                        {{ row.quantity }}
                        <div v-if="row.cancelled_quantity" class="text-xs text-red-600">{{ t('ordering::admin.orders_show.cancelled_cancelled_quantity', { cancelled_quantity: row.cancelled_quantity }) }}</div>
                    </template>
                    <template #cell-cancel="{ row }">
                        <input v-model.number="cancelLinesForm.lines[row.id]" type="number" min="0" :max="row.quantity" :class="[inputClass, 'w-20']" :aria-label="t('ordering::admin.orders_show.quantity_cancel_sku', { sku: row.sku })" />
                    </template>
                    <template #cell-unit_amount="{ row }">
                        <MoneyText :amount="row.unit_amount" />
                        <div v-if="row.compare_at_amount" class="text-xs text-slate-400 line-through"><MoneyText :amount="row.compare_at_amount" /></div>
                    </template>
                    <template #cell-discount_amount="{ row }"><MoneyText v-if="row.discount_amount" :amount="-row.discount_amount" /><template v-else>—</template></template>
                    <template #cell-total_amount="{ row }"><MoneyText :amount="row.total_amount" /></template>
                </DataTable>
                <form v-if="cancellingLines" class="flex flex-wrap items-end gap-3 border-t border-slate-100 bg-red-50/40 p-4 text-sm" @submit.prevent="cancelLines">
                    <FormField :label="t('ordering::admin.orders_show.cause')" :error="cancelLinesForm.errors.cause" class="min-w-56">
                        <Select v-model="cancelLinesForm.cause" :options="causeOptions" />
                    </FormField>
                    <FormField :label="t('ordering::admin.orders_show.reason_partial_cancellation')" :error="cancelLinesForm.errors.reason" class="min-w-64 flex-1">
                        <input v-model="cancelLinesForm.reason" :class="inputClass" maxlength="255" required :placeholder="t('ordering::admin.orders_show.size_m_out_stock')" />
                    </FormField>
                    <button type="submit" :class="dangerButton" :disabled="cancelLinesForm.processing || cancelTotal === 0">{{ t('ordering::admin.orders_show.cancel_cancel_total_items', { cancel_total: cancelTotal }) }}</button>
                    <button type="button" :class="secondaryButton" @click="cancellingLines = false">{{ t('ordering::admin.orders_show.never_mind') }}</button>
                    <p class="w-full text-xs text-slate-500">
                        {{ t('ordering::admin.orders_show.cancelled_amount_cod_reduces_amount') }}
                        <template v-if="cancelLinesForm.cause === 'customer'">
                            {{ t('ordering::admin.orders_show.customer_removed_items_promotions_remaining') }}
                        </template>
                        <template v-else>{{ t('ordering::admin.orders_show.store_error_customer_keeps_their') }}</template>
                    </p>
                </form>
                <!-- Tầng giá (Phase 8): niêm yết → giá bán → khuyến mãi → mã giảm giá → giảm khác → phí giao → tổng (VAT đã gồm). -->
                <dl class="space-y-1 border-t border-slate-100 p-4 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <dt>{{ t('ordering::admin.orders_show.list_price') }}</dt>
                        <dd>{{ money(order.pricing.list_amount) }}</dd>
                    </div>
                    <div v-if="order.pricing.markdown" class="flex justify-between text-slate-600">
                        <dt>{{ t('ordering::admin.orders_show.sale_discount') }}<span v-if="order.pricing.price_lists.length" class="font-mono text-xs"> ({{ order.pricing.price_lists.map((list) => list.code).join(', ') }})</span></dt>
                        <dd>-{{ money(order.pricing.markdown) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>{{ t('ordering::admin.orders_show.subtotal') }}</dt>
                        <dd>{{ money(order.pricing.subtotal) }}</dd>
                    </div>
                    <div v-for="promotion in order.pricing.promotions" :key="promotion.promotion_id" class="flex justify-between text-slate-600">
                        <dt>{{ promotion.voucher ? t('ordering::admin.orders_show.discount_code') : t('ordering::admin.orders_show.promotion') }}: {{ promotion.name }}<span v-if="promotion.code" class="font-mono"> ({{ promotion.code }})</span></dt>
                        <dd>-{{ money(promotion.amount) }}</dd>
                    </div>
                    <div v-if="order.pricing.other_discount" class="flex justify-between text-slate-600">
                        <dt>{{ t('ordering::admin.orders_show.other_discount') }}</dt>
                        <dd>-{{ money(order.pricing.other_discount) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>{{ t('ordering::admin.orders_show.shipping_fee_label', { label: order.shippingMethod.label }) }}</dt>
                        <dd>{{ money(order.amounts.shipping) }}</dd>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <dt>{{ t('ordering::admin.orders_show.total') }}</dt>
                        <dd>{{ money(order.amounts.total) }}</dd>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <dt>{{ t('ordering::admin.orders_show.which_vat') }}</dt>
                        <dd>{{ money(order.amounts.tax) }}</dd>
                    </div>
                </dl>
                <details v-if="order.adjustments.length" class="border-t border-slate-100 px-4 py-2 text-xs text-slate-600">
                    <summary class="cursor-pointer">{{ t('ordering::admin.orders_show.adjustment_history_count', { count: order.adjustments.length }) }}</summary>
                    <div v-for="(adjustment, index) in order.adjustments" :key="index" class="mt-1 flex justify-between">
                        <span>{{ adjustment.label }}<span v-if="adjustment.code" class="font-mono"> ({{ adjustment.code }})</span></span>
                        <span>{{ money(adjustment.amount) }}</span>
                    </div>
                </details>
            </section>

            <ExtensionTabs :tabs="extensions.tabs" />

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ t('ordering::admin.orders_show.history') }}</h2>
                <ol class="space-y-2 text-sm">
                    <li v-for="item in history" :key="`${item.kind}-${item.order}`" class="border-l-2 pl-3" :class="item.kind === 'order' ? 'border-slate-200' : 'border-indigo-200'">
                        <template v-if="item.kind === 'order'">
                            <span class="text-xs text-slate-400">{{ item.event.at }}</span>
                            ·
                            <span class="font-medium">{{ labels.event_type[item.event.type] ?? item.event.type }}</span>
                            <span v-if="item.event.to"> → {{ statusLabel(item.event.to) }}</span>
                            <span v-if="item.reasonLabel" class="text-slate-700"> · {{ item.reasonLabel }}</span>
                            <span v-if="item.note" class="text-slate-500"> ({{ item.note }})</span>
                            <span v-if="item.event.actor || item.event.source" class="text-xs text-slate-400"> · {{ item.event.actor ?? item.event.source }}</span>
                            <p v-if="item.event.type === 'note' && item.event.data" class="text-slate-700">
                                {{ item.event.data.note }}
                            </p>
                        </template>
                        <template v-else>
                            <span class="text-xs text-slate-400">{{ item.entry.at }}</span>
                            ·
                            <span class="font-medium">{{ item.entry.title }}</span>
                            <span v-if="item.entry.detail" class="text-slate-500"> ({{ item.entry.detail }})</span>
                            <span v-if="item.entry.source" class="text-xs text-slate-400"> · {{ item.entry.source }}</span>
                        </template>
                    </li>
                </ol>
                <form v-if="can.note" class="mt-3 flex gap-2" @submit.prevent="addNote">
                    <input v-model="noteForm.note" :class="inputClass" :placeholder="t('ordering::admin.orders_show.internal_note')" />
                    <button type="submit" :class="secondaryButton" :disabled="noteForm.processing">{{ t('ordering::admin.orders_show.add') }}</button>
                </form>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm" data-status-card>
                <h2 class="mb-2 font-semibold">{{ t('ordering::admin.orders_show.order_status') }}</h2>
                <dl class="space-y-1.5">
                    <div v-for="row in statusCard.rows" :key="row.key" class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">{{ row.label }}</dt>
                        <dd><StatusBadge :label="row.text" :tone="statusRowTone(row)" /></dd>
                    </div>
                </dl>
                <div v-if="statusCard.actions.length" class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    <button
                        v-for="action in statusCard.actions"
                        :key="action.key"
                        type="button"
                        :class="[actionClass(action), '!px-2.5 !py-1 text-xs']"
                        :data-status-action="action.key"
                        @click="runAction(action)"
                    >
                        {{ action.label }}
                    </button>
                </div>
                <form v-if="canChangeStatus" class="mt-3 space-y-2 border-t border-slate-100 pt-3" data-status-change @submit.prevent="changeStatus">
                    <FormField :label="t('ordering::admin.orders_show.change_order_status')" :error="statusForm.errors.status">
                        <Select v-model="statusForm.status" :options="statusOptions" :empty-label="t('ordering::admin.orders_show.choose_status')" :aria-label="t('ordering::admin.orders_show.new_status')" />
                    </FormField>
                    <p v-if="chosenStatus?.mode === 'forced'" class="rounded bg-amber-50 p-2 text-xs text-amber-800">
                        {{ t('ordering::admin.orders_show.out_sequence_skips_intermediate_steps') }}
                        <template v-if="chosenStatus.value === 'completed'">{{ t('ordering::admin.orders_show.goods_treated_delivered_outside_system') }}</template>
                    </p>
                    <template v-if="chosenStatus?.allowed">
                        <FormField :label="t('ordering::admin.status_change.reason')" :error="statusForm.errors.reason_code">
                            <Select v-model="statusForm.reason_code" :options="chosenStatus.reasons" :empty-label="t('ordering::admin.status_change.choose_reason')" :aria-label="t('ordering::admin.status_change.reason')" />
                        </FormField>
                        <FormField for="order-status-note" :label="t(chosenStatus.mode === 'forced' ? 'ordering::admin.status_change.note_required' : 'ordering::admin.status_change.note')" :error="statusForm.errors.reason">
                            <input id="order-status-note" v-model="statusForm.reason" :class="inputClass" maxlength="255" :required="chosenStatus.mode === 'forced'" />
                        </FormField>
                    </template>
                    <button type="submit" :class="primaryButton" :disabled="statusForm.processing || !chosenStatus?.allowed || !statusForm.reason_code || (chosenStatus.mode === 'forced' && !statusForm.reason.trim())">{{ t('ordering::admin.status_change.submit') }}</button>
                </form>
                <ul v-if="statusCard.change.options.some((option) => option.blocked && option.value !== order.orderStatus)" class="mt-2 space-y-0.5 text-xs text-slate-400">
                    <template v-for="option in statusCard.change.options" :key="`blocked-${option.value}`">
                        <li v-if="option.blocked && option.value !== order.orderStatus">{{ option.label }}: {{ option.blocked }}</li>
                    </template>
                </ul>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="font-semibold">{{ t('ordering::admin.orders_show.customers') }}</h2>
                    <StatusBadge v-if="customerFlag" :label="customerFlag.label" :tone="customerFlag.tone" />
                </div>
                <p>{{ order.customer.full_name }}</p>
                <p class="text-slate-600">{{ order.customer.phone }}</p>
                <p v-if="order.customer.email" class="text-slate-600">
                    {{ order.customer.email }}
                </p>
                <dl class="mt-2 grid grid-cols-4 gap-1 rounded bg-slate-50 p-2 text-center text-xs" :aria-label="t('ordering::admin.orders_show.delivery_history_phone_number')">
                    <div><dt class="text-slate-500">{{ t('ordering::admin.orders_show.previous_orders') }}</dt><dd class="font-semibold">{{ customerHistory.orders }}</dd></div>
                    <div><dt class="text-slate-500">{{ t('ordering::admin.orders_show.received') }}</dt><dd class="font-semibold text-emerald-700">{{ customerHistory.delivered }}</dd></div>
                    <div><dt class="text-slate-500">{{ t('ordering::admin.orders_show.returned_sender') }}</dt><dd class="font-semibold" :class="customerHistory.returned_to_sender ? 'text-red-600' : ''">{{ customerHistory.returned_to_sender }}</dd></div>
                    <div><dt class="text-slate-500">{{ t('ordering::admin.orders_show.cancelled') }}</dt><dd class="font-semibold">{{ customerHistory.cancelled }}</dd></div>
                </dl>
                <Link v-if="customerHistory.orders && customerHistory.orders_url" :href="customerHistory.orders_url" class="mt-1 inline-block text-xs text-indigo-600 hover:underline">{{ t('ordering::admin.orders_show.view_phone_number_s_orders') }}</Link>
                <p v-if="order.note" class="mt-2 rounded bg-amber-50 p-2">{{ t('ordering::admin.orders_show.customer_note_note', { note: order.note }) }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-semibold">{{ t('ordering::admin.orders_show.deliver') }}</h2>
                    <button
                        v-if="can.change_address && !editingAddress"
                        id="order-edit-address"
                        type="button"
                        class="text-xs text-indigo-600 hover:underline"
                        @click="editingAddress = true"
                    >
                        {{ t('ordering::admin.orders_show.edit') }}
                    </button>
                </div>
                <template v-if="!editingAddress">
                    <p>{{ order.shippingAddress.street_line }}</p>
                    <p class="text-slate-600">
                        {{ order.shippingAddress.ward_name }},
                        {{ order.shippingAddress.province_name }}
                    </p>
                </template>
                <form v-else class="space-y-2" @submit.prevent="saveAddress">
                    <FormField for="order-street-line" :label="t('ordering::admin.orders_show.house_number_street')" :error="addressForm.errors.street_line"
                        ><input id="order-street-line" v-model="addressForm.street_line" :class="inputClass"
                    /></FormField>
                    <template v-if="addressDirectory">
                        <FormField :label="t('ordering::admin.orders_show.province_city')" for="order-province" :error="addressForm.errors.province_code">
                            <Combobox id="order-province" v-model="addressForm.province_code" :options="provinceOptions" :placeholder="t('ordering::admin.orders_show.type_search_provinces_cities')" @select="loadWards" />
                        </FormField>
                        <FormField :label="t('ordering::admin.orders_show.ward')" for="order-ward" :error="addressForm.errors.ward_code || (wardsError ? t('ordering::admin.orders_show.could_not_load_list_wards') : undefined)">
                            <Combobox id="order-ward" v-model="addressForm.ward_code" :options="wardOptions" :placeholder="t('ordering::admin.orders_show.type_search_wards')" :disabled="!addressForm.province_code" />
                        </FormField>
                    </template>
                    <template v-else>
                        <FormField :label="t('ordering::admin.orders_show.ward')" :error="addressForm.errors.ward_name"
                            ><input v-model="addressForm.ward_name" :class="inputClass"
                        /></FormField>
                        <FormField :label="t('ordering::admin.orders_show.ward_code')" :error="addressForm.errors.ward_code"
                            ><input v-model="addressForm.ward_code" :class="inputClass"
                        /></FormField>
                        <FormField :label="t('ordering::admin.orders_show.province_city')" :error="addressForm.errors.province_name"
                            ><input v-model="addressForm.province_name" :class="inputClass"
                        /></FormField>
                        <FormField :label="t('ordering::admin.orders_show.province_city_code')" :error="addressForm.errors.province_code"
                            ><input v-model="addressForm.province_code" :class="inputClass"
                        /></FormField>
                    </template>
                    <FormField :label="t('ordering::admin.orders_show.reason')" :error="addressForm.errors.reason"><input v-model="addressForm.reason" :class="inputClass" /></FormField>
                    <div class="flex gap-2">
                        <button type="submit" :class="primaryButton" :disabled="addressForm.processing">{{ t('ordering::admin.orders_show.save') }}</button>
                        <button type="button" :class="secondaryButton" @click="editingAddress = false">{{ t('ordering::admin.orders_show.cancel') }}</button>
                    </div>
                </form>
            </section>

            <section v-for="(panel, index) in panels" :key="index" class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-2 font-semibold">{{ panel.title }}</h2>
                <dl class="space-y-1">
                    <div v-for="row in panel.rows" :key="row.label" class="flex justify-between gap-3">
                        <dt class="text-slate-500">{{ row.label }}</dt>
                        <dd class="text-right">{{ row.value }}</dd>
                    </div>
                </dl>
                <div v-if="panel.actions?.length" class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="action in panel.actions"
                        :key="action.key"
                        type="button"
                        :class="[actionClass(action), '!px-2.5 !py-1 text-xs']"
                        :data-panel-action="action.key"
                        @click="runAction(action)"
                    >
                        {{ action.label }}
                    </button>
                </div>
                <Link v-if="panel.link" :href="panel.link.url" class="mt-2 inline-block text-xs text-indigo-600 hover:underline">{{ panel.link.label }}</Link>
            </section>
        </aside>
    </div>
</template>
