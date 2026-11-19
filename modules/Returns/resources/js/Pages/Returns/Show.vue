<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import type { ExtensionDetail } from '@admin/types';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useMoney } from '@admin/money';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { type ReturnLabels, returnStatusTone } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: ReturnLabels;
    orderUrl: string;
    orderNumber: string | null;
    return: {
        id: number;
        number: string;
        status: string;
        reasonCode: string;
        customerNote: string | null;
        refundAmount: number;
        refundedAmount: number | null;
        lines: Array<{ id: number; sku: string; name: string; quantity: number; refund_amount: number; condition: string | null; exchange_sku: string | null }>;
        events: Array<{ from: string | null; to: string; note: string | null; source: string; at: string }>;
        lockVersion: number;
        resolution: 'refund' | 'exchange';
        returnTrackingNumber: string | null;
        attachments: Array<{ id: number; url: string }>;
    };
    exchangeQuote: null | {
        lines: Array<{ return_line_id: number; sku: string; quantity: number; unit_amount: number; credit: number; same_style: boolean }>;
        payable: number;
        refund: number;
    };
    replacementOrder: null | { number: string | null; url: string };
    /** Thao tác + tab của plugin (EP v3 X1). */
    extensions: ExtensionDetail & { id: number };
    can: { approve: boolean; reject: boolean; in_transit: boolean; receive: boolean; resolve: boolean };
    /** Hãng đã giao đơn có thể đến lấy hàng trả (EP v3 X3). */
    pickupCarrier: string | null;
    returnShipping: { store_fault: boolean; fee: number; payer: 'customer' | 'store' | null };
}>();

async function bookPickup(): Promise<void> {
    if ((await confirmAction({ title: t('returns::admin.returns_show.ask_pickup_carrier_collect_return', { pickup_carrier: props.pickupCarrier }), message: t('returns::admin.returns_show.carrier_goes_order_s_delivery'), confirmLabel: t('returns::admin.returns_show.call_carrier') })) !== false) {
        router.post(`${props.baseUrl}/${props.return.id}/pickup`, {}, { preserveScroll: true });
    }
}

const money = useMoney();
const columns = computed(() => [
    { key: 'name', label: t('returns::admin.returns_show.products') },
    { key: 'quantity', label: 'SL' },
    { key: 'refund_amount', label: isExchange.value ? t('returns::admin.returns_show.value_returned') : t('returns::admin.returns_show.refund') },
    ...(isExchange.value ? [{ key: 'exchange_sku', label: t('returns::admin.returns_show.exchange'), class: 'font-mono text-xs' }] : []),
    { key: 'condition', label: t('returns::admin.returns_show.availability') },
]);
const url = computed(() => `${props.baseUrl}/${props.return.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const conditions = reactive<Record<number, string>>(Object.fromEntries(props.return.lines.map((line) => [line.id, 'sellable'])));
// Phí ship trả hàng (L10): lý do "lỗi do cửa hàng" → mặc định cửa hàng chịu (không trừ tiền hoàn); CSKH đổi được.
const resolveForm = useForm({
    amount: props.return.refundAmount,
    note: '',
    return_shipping_fee: 0 as number,
    return_shipping_payer: (props.returnShipping.store_fault ? 'store' : 'customer') as 'customer' | 'store',
});
const refundAfterShipping = computed(() =>
    resolveForm.return_shipping_payer === 'customer' ? Math.max(0, (resolveForm.amount || 0) - (resolveForm.return_shipping_fee || 0)) : resolveForm.amount || 0,
);
const isExchange = computed(() => props.return.resolution === 'exchange');

async function transition(to: string): Promise<void> {
    let note: string | null = null;
    if (to === 'rejected') {
        const reason = await confirmAction({ title: t('returns::admin.returns_show.reject_request_number', { number: props.return.number }), confirmLabel: t('returns::admin.returns_show.reject'), tone: 'danger', reason: { label: t('returns::admin.returns_show.rejection_reason'), required: true } });
        if (reason === false) return;
        note = reason;
    }
    router.post(`${url.value}/transition`, { to, note, lock_version: props.return.lockVersion }, { preserveScroll: true });
}

function receive(): void {
    router.post(`${url.value}/receive`, { conditions }, { preserveScroll: true });
}

function resolve(): void {
    resolveForm.post(`${url.value}/resolve`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('returns::admin.returns_show.return_exchange_number', { number: props.return.number })" />
    <p class="mb-2 text-sm text-slate-500">{{ t('returns::admin.returns_show.return_exchange') }}</p>
    <div class="mb-2"><StatusBadge :label="labels.return_status[props.return.status] ?? props.return.status" :tone="returnStatusTone(props.return.status)" /></div>
    <PageHeader :title="t('returns::admin.returns_show.request_number', { number: props.return.number })" :subtitle="labels.return_status[props.return.status] ?? props.return.status">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('returns::admin.returns_show.list') }}</Link>
        <Link :href="orderUrl" :class="secondaryButton">{{ t('returns::admin.returns_show.order_order_number', { order_number: orderNumber }) }}</Link>
        <button v-if="can.approve" type="button" :class="primaryButton" @click="transition('approved')">{{ t('returns::admin.returns_show.approve') }}</button>
        <button v-if="can.in_transit" type="button" :class="secondaryButton" @click="transition('in_transit')">{{ t('returns::admin.returns_show.customer_shipped') }}</button>
        <button v-if="pickupCarrier" type="button" :class="secondaryButton" data-pickup @click="bookPickup">{{ t('returns::admin.returns_show.ask_pickup_carrier_collect_return_2', { pickup_carrier: pickupCarrier }) }}</button>
        <button v-if="can.reject" type="button" :class="dangerButton" @click="transition('rejected')">{{ t('returns::admin.returns_show.reject') }}</button>
        <ExtensionActions :actions="extensions.actions" :ids="[extensions.id]" />
    </PageHeader>
    <ExtensionTabs :tabs="extensions.tabs" class="mb-4" />
    <p v-if="props.return.customerNote" class="mb-4 rounded bg-amber-50 p-3 text-sm">{{ t('returns::admin.returns_show.customer_note_customer_note', { customer_note: props.return.customerNote }) }}</p>
    <div v-if="props.return.attachments?.length" class="mb-4 flex flex-wrap gap-2" data-return-photos>
        <a v-for="photo in props.return.attachments" :key="photo.id" :href="photo.url" target="_blank" rel="noopener"><img :src="photo.url" :alt="t('returns::admin.returns_show.customer_photos')" class="h-24 w-24 rounded border border-slate-200 object-cover" /></a>
    </div>
    <p v-if="props.return.returnTrackingNumber" class="mb-4 rounded bg-slate-50 p-3 text-sm">{{ t('returns::admin.returns_show.return_tracking_number_sent_customer') }} <span class="font-mono">{{ props.return.returnTrackingNumber }}</span></p>

    <section class="mb-6 space-y-2">
        <DataTable :columns="columns" :rows="props.return.lines" :row-label="(line) => line.sku">
            <template #cell-name="{ row }">{{ row.name }}<div class="font-mono text-xs text-slate-400">{{ row.sku }}</div></template>
            <template #cell-refund_amount="{ row }"><MoneyText :amount="row.refund_amount" /></template>
            <template #cell-exchange_sku="{ row }">{{ row.exchange_sku ?? '—' }}</template>
            <template #cell-condition="{ row }">
                <Select v-if="can.receive" v-model="conditions[row.id]" :options="labelOptions(labels.condition)" :aria-label="t('returns::admin.returns_show.availability')" />
                <StatusBadge v-else-if="row.condition" :label="labels.condition[row.condition] ?? row.condition" :tone="row.condition === 'damaged' ? 'danger' : 'success'" />
                <span v-else>—</span>
            </template>
        </DataTable>
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-sm">
            <span>
                {{ t('returns::admin.returns_show.calculated_refund_total') }} <strong><MoneyText :amount="props.return.refundAmount" /></strong><span v-if="props.return.refundedAmount !== null"> {{ t('returns::admin.returns_show.refunded') }} <MoneyText :amount="props.return.refundedAmount" /></span>
                <span v-if="returnShipping.fee > 0"> {{ t('returns::admin.returns_show.return_shipping_fee') }} <MoneyText :amount="returnShipping.fee" /> ({{ returnShipping.payer === 'store' ? t('returns::admin.returns_show.paid_store') : t('returns::admin.returns_show.paid_customer') }})</span>
            </span>
            <button v-if="can.receive" type="button" :class="primaryButton" @click="receive">{{ t('returns::admin.returns_show.confirm_goods_received') }}</button>
        </div>
    </section>

    <p v-if="replacementOrder" class="mb-6 rounded bg-emerald-50 p-3 text-sm">
        {{ t('returns::admin.returns_show.exchanged_replacement_order') }} <Link :href="replacementOrder.url" class="font-medium text-indigo-600 hover:underline">{{ replacementOrder.number }}</Link>.
    </p>

    <form v-if="can.resolve && isExchange && exchangeQuote" class="mb-6 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="resolve">
        <p class="font-medium">{{ t('returns::admin.returns_show.complete_exchange') }}</p>
        <ul class="space-y-1 text-sm">
            <li v-for="line in exchangeQuote.lines" :key="line.return_line_id" class="flex justify-between gap-4">
                <span><span class="font-mono">{{ line.sku }}</span> × {{ line.quantity }} <span class="text-xs text-slate-500">{{ line.same_style ? t('returns::admin.returns_show.same_style_no_price_difference') : t('returns::admin.returns_show.current_price_unit_amount', { unit_amount: money(line.unit_amount) }) }}</span></span>
                <span>{{ money(line.same_style ? 0 : line.unit_amount * line.quantity - line.credit) }}</span>
            </li>
        </ul>
        <p class="text-sm">
            {{ t('returns::admin.returns_show.customer_pays_cod_replacement_order') }} <strong><MoneyText :amount="exchangeQuote.payable" /></strong> {{ t('returns::admin.returns_show.refund_customer') }} <strong><MoneyText :amount="exchangeQuote.refund" /></strong> {{ t('returns::admin.returns_show.shipping_fee') }} <MoneyText :amount="0" />
        </p>
        <input v-model="resolveForm.note" :class="inputClass" :placeholder="t('returns::admin.returns_show.note')" />
        <p v-for="(error, key) in resolveForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" :class="primaryButton" :disabled="resolveForm.processing">{{ t('returns::admin.returns_show.create_replacement_order_complete') }}</button>
    </form>

    <form v-if="can.resolve && !isExchange" class="mb-6 grid max-w-md gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="resolve">
        <p class="font-medium">{{ t('returns::admin.returns_show.complete_refund') }}</p>
        <input v-model.number="resolveForm.amount" :class="inputClass" type="number" min="0" :max="props.return.refundAmount" :aria-label="t('returns::admin.returns_show.refund')" />
        <fieldset class="grid gap-2 rounded border border-slate-200 p-3 text-sm" data-return-shipping>
            <legend class="px-1 font-medium">{{ t('returns::admin.returns_show.return_shipping_fee_2') }}</legend>
            <input v-model.number="resolveForm.return_shipping_fee" :class="inputClass" type="number" min="0" placeholder="0" :aria-label="t('returns::admin.returns_show.return_shipping_fee_2')" />
            <label class="flex items-center gap-2"><input v-model="resolveForm.return_shipping_payer" type="radio" value="store" /> {{ t('returns::admin.returns_show.paid_store_not_deducted_from') }}</label>
            <label class="flex items-center gap-2"><input v-model="resolveForm.return_shipping_payer" type="radio" value="customer" /> {{ t('returns::admin.returns_show.paid_customer_deducted_from_refund') }}</label>
            <p v-if="returnShipping.store_fault" class="text-xs text-amber-700">{{ t('returns::admin.returns_show.return_reason_store_s_fault') }}</p>
            <p class="text-slate-600">{{ t('returns::admin.returns_show.customer_receives') }} <strong><MoneyText :amount="refundAfterShipping" /></strong></p>
        </fieldset>
        <input v-model="resolveForm.note" :class="inputClass" :placeholder="t('returns::admin.returns_show.note_damage_fee_deducted')" />
        <p v-for="(error, key) in resolveForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" :class="primaryButton" :disabled="resolveForm.processing">{{ t('returns::admin.returns_show.completed') }}</button>
    </form>

    <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <h2 class="mb-2 font-semibold">{{ t('returns::admin.returns_show.history') }}</h2>
        <ol class="space-y-1">
            <li v-for="(event, index) in props.return.events" :key="index">
                <span class="text-xs text-slate-400">{{ event.at }}</span> · {{ labels.return_status[event.to] ?? event.to }}
                <span v-if="event.note" class="text-slate-500">({{ event.note }})</span>
                <span class="text-xs text-slate-400"> · {{ event.source }}</span>
            </li>
        </ol>
    </section>
</template>
