<script setup lang="ts">
import Combobox from '@admin/Components/Combobox.vue';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction, promptFields, type ConfirmField } from '@admin/confirm';
import { useMoney } from '@admin/money';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { orderStatusTone, paymentStatusTone, stageTone } from '../../statusTones';

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
    color_name: string | null;
    size_code: string;
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
            options: Array<{ value: string; label: string; mode: 'normal' | 'forced' | null; permission: string | null; blocked: string | null; allowed: boolean }>;
        };
    };
    customerHistory: { orders: number; delivered: number; returned_to_sender: number; cancelled: number; orders_url: string };
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
    labels: { order_status: Record<string, string>; payment_status: Record<string, string>; event_type: Record<string, string>; stage: Record<string, string>; delivery_issue: Record<string, string> };
    addressDirectory: {
        provinces: Division[];
        wards: Division[];
        wards_url: string;
    } | null;
}>();

const money = useMoney();

/** Thanh tiến trình: bước hiện tại theo giai đoạn vận hành (đơn huỷ không hiện). */
const steps = ['Đặt hàng', 'Xác nhận', 'Giao hãng', 'Đang giao', 'Đã giao', 'Hoàn tất'];
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
const history = computed(() =>
    [
        ...props.order.events.map((event, index) => ({ kind: 'order' as const, ts: event.ts ?? 0, order: index, event })),
        ...props.timeline.map((entry, index) => ({ kind: 'module' as const, ts: entry.ts, order: index, entry })),
    ].sort((a, b) => a.ts - b.ts),
);
const actionErrors = computed(() =>
    ['tracking_number', 'service_code', 'amount', 'idempotency_key', 'status', 'order_id'].map((key) => errors.value[key]).filter((message): message is string => Boolean(message)),
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
const customerFlag = computed((): { label: string; tone: 'danger' | 'warning' | 'success' } | null => {
    const history = props.customerHistory;
    if (history.returned_to_sender > 0) return { label: `Từng hoàn hàng ${history.returned_to_sender} lần`, tone: 'danger' };
    if (history.orders === 0) return { label: 'Khách mới', tone: 'warning' };
    if (history.delivered > 0) return { label: 'Khách quen', tone: 'success' };
    return null;
});
const statusForm = useForm({ status: '', reason: '' });
const statusOptions = computed(() =>
    props.statusCard.change.options
        .filter((option) => option.value !== props.order.orderStatus)
        .map((option) => ({ value: option.value, label: option.mode === 'forced' ? `${option.label} (ngoài trình tự)` : option.label, disabled: !option.allowed })),
);
const chosenStatus = computed(() => props.statusCard.change.options.find((option) => option.value === statusForm.status) ?? null);
const canChangeStatus = computed(() => statusOptions.value.some((option) => !option.disabled));
function changeStatus(): void {
    statusForm.post(props.statusCard.change.url, { preserveScroll: true, onSuccess: () => statusForm.reset() });
}
const actionClass = (action: PanelAction): string => (action.tone === 'primary' ? primaryButton : action.tone === 'danger' ? dangerButton : secondaryButton);
const url = computed(() => `${props.baseUrl}/${props.order.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const editingAddress = ref(false);
const address = props.order.shippingAddress;
const addressForm = useForm({
    street_line: address.street_line ?? '',
    ward_name: address.ward_name ?? '',
    ward_code: address.ward_code ?? '',
    province_name: address.province_name ?? '',
    province_code: address.province_code ?? '',
    reason: '',
    lock_version: props.order.lockVersion,
});
const wards = ref<Division[]>(props.addressDirectory?.wards ?? []);
const wardsError = ref(false);
const noteForm = useForm({ note: '' });
const cancellingLines = ref(false);
const cancelLinesForm = useForm({
    lines: Object.fromEntries(props.order.lines.map((line) => [line.id, 0])) as Record<number, number>,
    reason: '',
    cause: 'shop',
    lock_version: props.order.lockVersion,
});
const cancelTotal = computed(() => Object.values(cancelLinesForm.lines).reduce((sum, quantity) => sum + Number(quantity || 0), 0));

function cancelLines(): void {
    cancelLinesForm.post(`${url.value}/cancel-lines`, { preserveScroll: true, onSuccess: () => (cancellingLines.value = false) });
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
    { value: 'shop', label: 'Lỗi shop (hết hàng, sai giá…)' },
    { value: 'customer', label: 'Khách yêu cầu bớt hàng' },
];
watch(() => addressForm.province_code, loadWards);
const lineColumns = computed(() => [
    { key: 'name', label: 'Sản phẩm' },
    { key: 'quantity', label: 'SL' },
    ...(cancellingLines.value ? [{ key: 'cancel', label: 'Huỷ' }] : []),
    { key: 'unit_amount', label: 'Đơn giá' },
    { key: 'discount_amount', label: 'Giảm' },
    { key: 'total_amount', label: 'Thành tiền', align: 'right' as const },
]);

/** Trạng thái đích của sự kiện lịch sử: trạng thái đơn hoặc thanh toán, chưa có nhãn thì in mã. */
function statusLabel(status: string): string {
    return props.labels.order_status[status] ?? props.labels.payment_status[status] ?? status;
}

function saveAddress(): void {
    addressForm.put(`${url.value}/shipping-address`, {
        preserveScroll: true,
        onSuccess: () => (editingAddress.value = false),
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
    <Head :title="`Đơn ${order.number}`" />
    <p class="mb-2 text-sm text-slate-500">Đơn hàng</p>
    <div class="mb-2 flex flex-wrap gap-2">
        <StatusBadge :label="labels.stage[stage] ?? stage" :tone="stageTone(stage)" />
        <StatusBadge :label="labels.order_status[order.orderStatus] ?? order.orderStatus" :tone="orderStatusTone(order.orderStatus)" />
        <StatusBadge :label="labels.payment_status[order.paymentStatus] ?? order.paymentStatus" :tone="paymentStatusTone(order.paymentStatus)" />
    </div>
    <PageHeader :title="`Đơn ${order.number}`" :subtitle="`${order.placedAt} · ${order.customerStatus.label}`">
        <Link :href="listUrl" :class="secondaryButton">Danh sách</Link>
        <a :href="printUrl" target="_blank" rel="noopener" :class="secondaryButton">In phiếu giao</a>
        <button v-if="nextAction" type="button" :class="primaryButton" data-next-action @click="runAction(nextAction)">{{ nextAction.label }}</button>
        <button v-if="can.cancel_lines && !cancellingLines" type="button" :class="secondaryButton" @click="cancellingLines = true">Huỷ một phần</button>
        <ExtensionActions :actions="extensions.actions" :ids="[order.id]" />
    </PageHeader>
    <ol v-if="currentStep >= 0" class="mb-4 flex flex-wrap items-center gap-x-1 gap-y-2 text-xs" aria-label="Tiến trình đơn">
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
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
        {{ errors.business }}
    </p>
    <p v-for="message in actionErrors" :key="message" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ message }}</p>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-slate-200 bg-white">
                <DataTable :columns="lineColumns" :rows="order.lines" :row-key="(line) => line.sku" bare>
                    <template #cell-name="{ row }">
                        {{ row.name }}
                        <div class="font-mono text-xs text-slate-400">{{ row.sku }} · {{ row.color_name }} / {{ row.size_code }}</div>
                        <template v-for="(values, plugin) in row.options" :key="plugin">
                            <div v-for="(value, field) in values" :key="field" class="text-xs text-amber-700">{{ field }}: {{ value }}</div>
                        </template>
                    </template>
                    <template #cell-quantity="{ row }">
                        {{ row.quantity }}
                        <div v-if="row.cancelled_quantity" class="text-xs text-red-600">đã huỷ {{ row.cancelled_quantity }}</div>
                    </template>
                    <template #cell-cancel="{ row }">
                        <input v-model.number="cancelLinesForm.lines[row.id]" type="number" min="0" :max="row.quantity" :class="[inputClass, 'w-20']" :aria-label="`Số lượng huỷ ${row.sku}`" />
                    </template>
                    <template #cell-unit_amount="{ row }">
                        <MoneyText :amount="row.unit_amount" />
                        <div v-if="row.compare_at_amount" class="text-xs text-slate-400 line-through"><MoneyText :amount="row.compare_at_amount" /></div>
                    </template>
                    <template #cell-discount_amount="{ row }"><MoneyText v-if="row.discount_amount" :amount="-row.discount_amount" /><template v-else>—</template></template>
                    <template #cell-total_amount="{ row }"><MoneyText :amount="row.total_amount" /></template>
                </DataTable>
                <form v-if="cancellingLines" class="flex flex-wrap items-end gap-3 border-t border-slate-100 bg-red-50/40 p-4 text-sm" @submit.prevent="cancelLines">
                    <FormField label="Nguyên nhân" :error="cancelLinesForm.errors.cause" class="min-w-56">
                        <Select v-model="cancelLinesForm.cause" :options="causeOptions" />
                    </FormField>
                    <FormField label="Lý do huỷ một phần" :error="cancelLinesForm.errors.reason" class="min-w-64 flex-1">
                        <input v-model="cancelLinesForm.reason" :class="inputClass" maxlength="255" required placeholder="Vd. hết hàng size M" />
                    </FormField>
                    <button type="submit" :class="dangerButton" :disabled="cancelLinesForm.processing || cancelTotal === 0">Huỷ {{ cancelTotal }} sản phẩm</button>
                    <button type="button" :class="secondaryButton" @click="cancellingLines = false">Thôi</button>
                    <p class="w-full text-xs text-slate-500">
                        Tiền phần huỷ: COD giảm số thu hộ; đã thanh toán thì hoàn tự động (cổng hỗ trợ) hoặc tạo yêu cầu hoàn tay. Vận đơn chưa rời kho được tạo lại.
                        <template v-if="cancelLinesForm.cause === 'customer'">
                            Khách bớt hàng: khuyến mãi mà phần còn lại không còn đủ điều kiện (vd. dưới ngưỡng) được thu hồi, trừ vào tiền hoàn/giảm thu hộ — không vượt tiền phần huỷ.
                        </template>
                        <template v-else>Lỗi shop: khách giữ nguyên ưu đãi.</template>
                    </p>
                </form>
                <!-- Tầng giá (Phase 8): niêm yết → giá bán → khuyến mãi → mã giảm giá → giảm khác → phí giao → tổng (VAT đã gồm). -->
                <dl class="space-y-1 border-t border-slate-100 p-4 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <dt>Giá niêm yết</dt>
                        <dd>{{ money(order.pricing.list_amount) }}</dd>
                    </div>
                    <div v-if="order.pricing.markdown" class="flex justify-between text-slate-600">
                        <dt>Giảm giá bán<span v-if="order.pricing.price_lists.length" class="font-mono text-xs"> ({{ order.pricing.price_lists.map((list) => list.code).join(', ') }})</span></dt>
                        <dd>-{{ money(order.pricing.markdown) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Tạm tính</dt>
                        <dd>{{ money(order.pricing.subtotal) }}</dd>
                    </div>
                    <div v-for="promotion in order.pricing.promotions" :key="promotion.promotion_id" class="flex justify-between text-slate-600">
                        <dt>{{ promotion.voucher ? 'Mã giảm giá' : 'Khuyến mãi' }}: {{ promotion.name }}<span v-if="promotion.code" class="font-mono"> ({{ promotion.code }})</span></dt>
                        <dd>-{{ money(promotion.amount) }}</dd>
                    </div>
                    <div v-if="order.pricing.other_discount" class="flex justify-between text-slate-600">
                        <dt>Giảm khác</dt>
                        <dd>-{{ money(order.pricing.other_discount) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Phí giao ({{ order.shippingMethod.label }})</dt>
                        <dd>{{ money(order.amounts.shipping) }}</dd>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <dt>Tổng</dt>
                        <dd>{{ money(order.amounts.total) }}</dd>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <dt>Trong đó VAT</dt>
                        <dd>{{ money(order.amounts.tax) }}</dd>
                    </div>
                </dl>
                <details v-if="order.adjustments.length" class="border-t border-slate-100 px-4 py-2 text-xs text-slate-600">
                    <summary class="cursor-pointer">Lịch sử điều chỉnh ({{ order.adjustments.length }})</summary>
                    <div v-for="(adjustment, index) in order.adjustments" :key="index" class="mt-1 flex justify-between">
                        <span>{{ adjustment.label }}<span v-if="adjustment.code" class="font-mono"> ({{ adjustment.code }})</span></span>
                        <span>{{ money(adjustment.amount) }}</span>
                    </div>
                </details>
            </section>

            <ExtensionTabs :tabs="extensions.tabs" />

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Lịch sử</h2>
                <ol class="space-y-2 text-sm">
                    <li v-for="item in history" :key="`${item.kind}-${item.order}`" class="border-l-2 pl-3" :class="item.kind === 'order' ? 'border-slate-200' : 'border-indigo-200'">
                        <template v-if="item.kind === 'order'">
                            <span class="text-xs text-slate-400">{{ item.event.at }}</span>
                            ·
                            <span class="font-medium">{{ labels.event_type[item.event.type] ?? item.event.type }}</span>
                            <span v-if="item.event.to"> → {{ statusLabel(item.event.to) }}</span>
                            <span v-if="item.event.reason" class="text-slate-500"> ({{ item.event.reason }})</span>
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
                    <input v-model="noteForm.note" :class="inputClass" placeholder="Ghi chú nội bộ" />
                    <button type="submit" :class="secondaryButton" :disabled="noteForm.processing">Thêm</button>
                </form>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm" data-status-card>
                <h2 class="mb-2 font-semibold">Trạng thái đơn</h2>
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
                    <FormField label="Đổi trạng thái đơn" :error="statusForm.errors.status">
                        <Select v-model="statusForm.status" :options="statusOptions" empty-label="— Chọn trạng thái —" aria-label="Trạng thái mới" />
                    </FormField>
                    <p v-if="chosenStatus?.mode === 'forced'" class="rounded bg-amber-50 p-2 text-xs text-amber-800">
                        Ngoài trình tự: bỏ qua các bước giữa, ghi vào lịch sử đơn.
                        <template v-if="chosenStatus.value === 'completed'">Hàng coi như đã giao ngoài hệ thống — vận đơn còn mở bị huỷ, hàng đang giữ được trừ khỏi tồn kho.</template>
                    </p>
                    <FormField label="Lý do (bắt buộc)" :error="statusForm.errors.reason">
                        <input v-model="statusForm.reason" :class="inputClass" maxlength="255" required placeholder="vd. Khách đã nhận hàng tại cửa hàng" />
                    </FormField>
                    <button type="submit" :class="primaryButton" :disabled="statusForm.processing || !statusForm.status || !statusForm.reason.trim()">Đổi trạng thái</button>
                </form>
                <ul v-if="statusCard.change.options.some((option) => option.blocked && option.value !== order.orderStatus)" class="mt-2 space-y-0.5 text-xs text-slate-400">
                    <template v-for="option in statusCard.change.options" :key="`blocked-${option.value}`">
                        <li v-if="option.blocked && option.value !== order.orderStatus">{{ option.label }}: {{ option.blocked }}</li>
                    </template>
                </ul>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="font-semibold">Khách hàng</h2>
                    <StatusBadge v-if="customerFlag" :label="customerFlag.label" :tone="customerFlag.tone" />
                </div>
                <p>{{ order.customer.full_name }}</p>
                <p class="text-slate-600">{{ order.customer.phone }}</p>
                <p v-if="order.customer.email" class="text-slate-600">
                    {{ order.customer.email }}
                </p>
                <dl class="mt-2 grid grid-cols-4 gap-1 rounded bg-slate-50 p-2 text-center text-xs" aria-label="Lịch sử nhận hàng theo số điện thoại">
                    <div><dt class="text-slate-500">Đơn trước</dt><dd class="font-semibold">{{ customerHistory.orders }}</dd></div>
                    <div><dt class="text-slate-500">Đã nhận</dt><dd class="font-semibold text-emerald-700">{{ customerHistory.delivered }}</dd></div>
                    <div><dt class="text-slate-500">Hoàn về</dt><dd class="font-semibold" :class="customerHistory.returned_to_sender ? 'text-red-600' : ''">{{ customerHistory.returned_to_sender }}</dd></div>
                    <div><dt class="text-slate-500">Đã huỷ</dt><dd class="font-semibold">{{ customerHistory.cancelled }}</dd></div>
                </dl>
                <Link v-if="customerHistory.orders" :href="customerHistory.orders_url" class="mt-1 inline-block text-xs text-indigo-600 hover:underline">Xem các đơn của SĐT này</Link>
                <p v-if="order.note" class="mt-2 rounded bg-amber-50 p-2">Ghi chú của khách: {{ order.note }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-semibold">Giao đến</h2>
                    <button
                        v-if="can.change_address && !editingAddress"
                        type="button"
                        class="text-xs text-indigo-600 hover:underline"
                        @click="editingAddress = true"
                    >
                        Sửa
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
                    <FormField label="Số nhà, đường" :error="addressForm.errors.street_line"
                        ><input v-model="addressForm.street_line" :class="inputClass"
                    /></FormField>
                    <template v-if="addressDirectory">
                        <FormField label="Tỉnh/thành" for="order-province" :error="addressForm.errors.province_code">
                            <Combobox id="order-province" v-model="addressForm.province_code" :options="provinceOptions" placeholder="Gõ để tìm tỉnh/thành" />
                        </FormField>
                        <FormField label="Phường/xã" for="order-ward" :error="addressForm.errors.ward_code || (wardsError ? 'Không tải được danh sách phường/xã.' : undefined)">
                            <Combobox id="order-ward" v-model="addressForm.ward_code" :options="wardOptions" placeholder="Gõ để tìm phường/xã" :disabled="!addressForm.province_code" />
                        </FormField>
                    </template>
                    <template v-else>
                        <FormField label="Phường/xã" :error="addressForm.errors.ward_name"
                            ><input v-model="addressForm.ward_name" :class="inputClass"
                        /></FormField>
                        <FormField label="Mã phường/xã" :error="addressForm.errors.ward_code"
                            ><input v-model="addressForm.ward_code" :class="inputClass"
                        /></FormField>
                        <FormField label="Tỉnh/thành" :error="addressForm.errors.province_name"
                            ><input v-model="addressForm.province_name" :class="inputClass"
                        /></FormField>
                        <FormField label="Mã tỉnh/thành" :error="addressForm.errors.province_code"
                            ><input v-model="addressForm.province_code" :class="inputClass"
                        /></FormField>
                    </template>
                    <FormField label="Lý do" :error="addressForm.errors.reason"><input v-model="addressForm.reason" :class="inputClass" /></FormField>
                    <div class="flex gap-2">
                        <button type="submit" :class="primaryButton" :disabled="addressForm.processing">Lưu</button>
                        <button type="button" :class="secondaryButton" @click="editingAddress = false">Huỷ</button>
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
