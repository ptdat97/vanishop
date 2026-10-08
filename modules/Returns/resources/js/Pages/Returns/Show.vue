<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    baseUrl: string;
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

const labels: Record<string, string> = { requested: 'Chờ duyệt', approved: 'Đã duyệt', in_transit: 'Đang gửi về', received: 'Đã nhận hàng', resolved: 'Hoàn tất', rejected: 'Từ chối', cancelled: 'Đã huỷ' };
const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
const url = computed(() => `${props.baseUrl}/${props.return.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const conditions = reactive<Record<number, string>>(Object.fromEntries(props.return.lines.map((line) => [line.id, 'sellable'])));
const resolveForm = useForm({ amount: props.return.refundAmount, note: '' });
const isExchange = computed(() => props.return.resolution === 'exchange');

function transition(to: string): void {
    const note = to === 'rejected' ? prompt('Lý do từ chối:') : null;
    if (to === 'rejected' && !note) return;
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
    <PageHeader :title="`Yêu cầu ${props.return.number}`" :subtitle="labels[props.return.status] ?? props.return.status">
        <Link :href="baseUrl" :class="secondaryButton">Danh sách</Link>
        <Link :href="orderUrl" :class="secondaryButton">Đơn {{ orderNumber }}</Link>
        <button v-if="can.approve" type="button" :class="primaryButton" @click="transition('approved')">Duyệt</button>
        <button v-if="can.in_transit" type="button" :class="secondaryButton" @click="transition('in_transit')">Khách đã gửi</button>
        <button v-if="can.reject" type="button" :class="dangerButton" @click="transition('rejected')">Từ chối</button>
    </PageHeader>
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>
    <p v-if="props.return.customerNote" class="mb-4 rounded bg-amber-50 p-3 text-sm">Khách ghi chú: {{ props.return.customerNote }}</p>

    <section class="mb-6 rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Sản phẩm</th><th class="px-4 py-2">SL</th><th class="px-4 py-2">{{ isExchange ? 'Giá trị đã trả' : 'Tiền hoàn' }}</th>
                    <th v-if="isExchange" class="px-4 py-2">Đổi sang</th><th class="px-4 py-2">Tình trạng</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="line in props.return.lines" :key="line.id" class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ line.name }}<div class="font-mono text-xs text-slate-400">{{ line.sku }}</div></td>
                    <td class="px-4 py-2">{{ line.quantity }}</td>
                    <td class="px-4 py-2">{{ vnd(line.refund_amount) }}</td>
                    <td v-if="isExchange" class="px-4 py-2 font-mono text-xs">{{ line.exchange_sku ?? '—' }}</td>
                    <td class="px-4 py-2">
                        <select v-if="can.receive" v-model="conditions[line.id]" :class="inputClass">
                            <option value="sellable">Bán lại được (nhập kho)</option>
                            <option value="damaged">Hư hỏng (không nhập kho)</option>
                        </select>
                        <span v-else>{{ line.condition === 'damaged' ? 'Hư hỏng' : line.condition === 'sellable' ? 'Bán lại được' : '—' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex items-center justify-between border-t border-slate-100 p-4 text-sm">
            <span>Tổng tiền hoàn tính được: <strong>{{ vnd(props.return.refundAmount) }}</strong><span v-if="props.return.refundedAmount !== null"> · đã hoàn {{ vnd(props.return.refundedAmount) }}</span></span>
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
                <span><span class="font-mono">{{ line.sku }}</span> × {{ line.quantity }} <span class="text-xs text-slate-500">{{ line.same_style ? '(cùng mẫu — không tính chênh)' : `(giá hiện tại ${vnd(line.unit_amount)})` }}</span></span>
                <span>{{ line.same_style ? '0 ₫' : vnd(line.unit_amount * line.quantity - line.credit) }}</span>
            </li>
        </ul>
        <p class="text-sm">
            Khách bù (thu hộ COD trên đơn thay thế): <strong>{{ vnd(exchangeQuote.payable) }}</strong> · Hoàn cho khách: <strong>{{ vnd(exchangeQuote.refund) }}</strong> · Phí giao: 0 ₫
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
                <span class="text-xs text-slate-400">{{ event.at }}</span> · {{ labels[event.to] ?? event.to }}
                <span v-if="event.note" class="text-slate-500">({{ event.note }})</span>
                <span class="text-xs text-slate-400"> · {{ event.source }}</span>
            </li>
        </ol>
    </section>
</template>
