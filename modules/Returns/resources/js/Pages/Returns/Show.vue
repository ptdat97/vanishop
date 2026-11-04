<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
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
    };
    exchangeQuote: null | {
        lines: Array<{ return_line_id: number; sku: string; quantity: number; unit_amount: number; credit: number; same_style: boolean }>;
        payable: number;
        refund: number;
    };
    replacementOrder: null | { number: string | null; url: string };
    can: { approve: boolean; reject: boolean; in_transit: boolean; receive: boolean; resolve: boolean };
}>();

const money = useMoney();
const columns = computed(() => [
    { key: 'name', label: 'Sản phẩm' },
    { key: 'quantity', label: 'SL' },
    { key: 'refund_amount', label: isExchange.value ? 'Giá trị đã trả' : 'Tiền hoàn' },
    ...(isExchange.value ? [{ key: 'exchange_sku', label: 'Đổi sang', class: 'font-mono text-xs' }] : []),
    { key: 'condition', label: 'Tình trạng' },
]);
const url = computed(() => `${props.baseUrl}/${props.return.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const conditions = reactive<Record<number, string>>(Object.fromEntries(props.return.lines.map((line) => [line.id, 'sellable'])));
const resolveForm = useForm({ amount: props.return.refundAmount, note: '' });
const isExchange = computed(() => props.return.resolution === 'exchange');

async function transition(to: string): Promise<void> {
    let note: string | null = null;
    if (to === 'rejected') {
        const reason = await confirmAction({ title: `Từ chối yêu cầu ${props.return.number}?`, confirmLabel: 'Từ chối', tone: 'danger', reason: { label: 'Lý do từ chối', required: true } });
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
    <Head :title="`Đổi/trả ${props.return.number}`" />
    <p class="mb-2 text-sm text-slate-500">Đổi/trả</p>
    <div class="mb-2"><StatusBadge :label="labels.return_status[props.return.status] ?? props.return.status" :tone="returnStatusTone(props.return.status)" /></div>
    <PageHeader :title="`Yêu cầu ${props.return.number}`" :subtitle="labels.return_status[props.return.status] ?? props.return.status">
        <Link :href="baseUrl" :class="secondaryButton">Danh sách</Link>
        <Link :href="orderUrl" :class="secondaryButton">Đơn {{ orderNumber }}</Link>
        <button v-if="can.approve" type="button" :class="primaryButton" @click="transition('approved')">Duyệt</button>
        <button v-if="can.in_transit" type="button" :class="secondaryButton" @click="transition('in_transit')">Khách đã gửi</button>
        <button v-if="can.reject" type="button" :class="dangerButton" @click="transition('rejected')">Từ chối</button>
    </PageHeader>
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>
    <p v-if="props.return.customerNote" class="mb-4 rounded bg-amber-50 p-3 text-sm">Khách ghi chú: {{ props.return.customerNote }}</p>

    <section class="mb-6 space-y-2">
        <DataTable :columns="columns" :rows="props.return.lines" :row-label="(line) => line.sku">
            <template #cell-name="{ row }">{{ row.name }}<div class="font-mono text-xs text-slate-400">{{ row.sku }}</div></template>
            <template #cell-refund_amount="{ row }"><MoneyText :amount="row.refund_amount" /></template>
            <template #cell-exchange_sku="{ row }">{{ row.exchange_sku ?? '—' }}</template>
            <template #cell-condition="{ row }">
                <Select v-if="can.receive" v-model="conditions[row.id]" :options="labelOptions(labels.condition)" aria-label="Tình trạng" />
                <StatusBadge v-else-if="row.condition" :label="labels.condition[row.condition] ?? row.condition" :tone="row.condition === 'damaged' ? 'danger' : 'success'" />
                <span v-else>—</span>
            </template>
        </DataTable>
        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-sm">
            <span>Tổng tiền hoàn tính được: <strong><MoneyText :amount="props.return.refundAmount" /></strong><span v-if="props.return.refundedAmount !== null"> · đã hoàn <MoneyText :amount="props.return.refundedAmount" /></span></span>
            <button v-if="can.receive" type="button" :class="primaryButton" @click="receive">Xác nhận đã nhận hàng</button>
        </div>
    </section>

    <p v-if="replacementOrder" class="mb-6 rounded bg-emerald-50 p-3 text-sm">
        Đã đổi hàng — đơn thay thế <Link :href="replacementOrder.url" class="font-medium text-indigo-600 hover:underline">{{ replacementOrder.number }}</Link>.
    </p>

    <form v-if="can.resolve && isExchange && exchangeQuote" class="mb-6 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="resolve">
        <p class="font-medium">Hoàn tất đổi hàng</p>
        <ul class="space-y-1 text-sm">
            <li v-for="line in exchangeQuote.lines" :key="line.return_line_id" class="flex justify-between gap-4">
                <span><span class="font-mono">{{ line.sku }}</span> × {{ line.quantity }} <span class="text-xs text-slate-500">{{ line.same_style ? '(cùng mẫu — không tính chênh)' : `(giá hiện tại ${money(line.unit_amount)})` }}</span></span>
                <span>{{ money(line.same_style ? 0 : line.unit_amount * line.quantity - line.credit) }}</span>
            </li>
        </ul>
        <p class="text-sm">
            Khách bù (thu hộ COD trên đơn thay thế): <strong><MoneyText :amount="exchangeQuote.payable" /></strong> · Hoàn cho khách: <strong><MoneyText :amount="exchangeQuote.refund" /></strong> · Phí giao: <MoneyText :amount="0" />
        </p>
        <input v-model="resolveForm.note" :class="inputClass" placeholder="Ghi chú" />
        <p v-for="(error, key) in resolveForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="errors.business" class="text-sm text-red-600">{{ errors.business }}</p>
        <button type="submit" :class="primaryButton" :disabled="resolveForm.processing">Tạo đơn thay thế và hoàn tất</button>
    </form>

    <form v-if="can.resolve && !isExchange" class="mb-6 grid max-w-md gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="resolve">
        <p class="font-medium">Hoàn tất và hoàn tiền</p>
        <input v-model.number="resolveForm.amount" :class="inputClass" type="number" min="0" :max="props.return.refundAmount" />
        <input v-model="resolveForm.note" :class="inputClass" placeholder="Ghi chú (vd. trừ phí hư hỏng)" />
        <p v-for="(error, key) in resolveForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <button type="submit" :class="primaryButton" :disabled="resolveForm.processing">Hoàn tất</button>
    </form>

    <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <h2 class="mb-2 font-semibold">Lịch sử</h2>
        <ol class="space-y-1">
            <li v-for="(event, index) in props.return.events" :key="index">
                <span class="text-xs text-slate-400">{{ event.at }}</span> · {{ labels.return_status[event.to] ?? event.to }}
                <span v-if="event.note" class="text-slate-500">({{ event.note }})</span>
                <span class="text-xs text-slate-400"> · {{ event.source }}</span>
            </li>
        </ol>
    </section>
</template>
