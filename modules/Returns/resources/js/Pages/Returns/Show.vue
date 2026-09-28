<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    brand: { name: string; slug: string };
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
        lines: Array<{ id: number; sku: string; name: string; quantity: number; refund_amount: number; condition: string | null }>;
        events: Array<{ from: string | null; to: string; note: string | null; source: string; at: string }>;
        lockVersion: number;
    };
    can: { approve: boolean; reject: boolean; in_transit: boolean; receive: boolean; resolve: boolean };
}>();

const labels: Record<string, string> = { requested: 'Chờ duyệt', approved: 'Đã duyệt', in_transit: 'Đang gửi về', received: 'Đã nhận hàng', resolved: 'Hoàn tất', rejected: 'Từ chối', cancelled: 'Đã huỷ' };
const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
const url = computed(() => `${props.baseUrl}/${props.return.id}`);
const errors = computed(() => usePage().props.errors as Record<string, string>);
const conditions = reactive<Record<number, string>>(Object.fromEntries(props.return.lines.map((line) => [line.id, 'sellable'])));
const resolveForm = useForm({ amount: props.return.refundAmount, note: '' });

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
    <p class="mb-2 text-sm text-slate-500">Đổi/trả · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
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
                <tr><th class="px-4 py-2">Sản phẩm</th><th class="px-4 py-2">SL</th><th class="px-4 py-2">Tiền hoàn</th><th class="px-4 py-2">Tình trạng</th></tr>
            </thead>
            <tbody>
                <tr v-for="line in props.return.lines" :key="line.id" class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ line.name }}<div class="font-mono text-xs text-slate-400">{{ line.sku }}</div></td>
                    <td class="px-4 py-2">{{ line.quantity }}</td>
                    <td class="px-4 py-2">{{ vnd(line.refund_amount) }}</td>
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

    <form v-if="can.resolve" class="mb-6 grid max-w-md gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="resolve">
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
