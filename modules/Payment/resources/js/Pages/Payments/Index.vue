<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
    status: string | null;
    statuses: string[];
    payments: Array<{
        id: number;
        order_number: string | null;
        gateway: string;
        status: string;
        amount: number;
        refunded_amount: number;
        created_at: string | null;
        expires_at: string | null;
        can_confirm: boolean;
        can_refund: boolean;
    }>;
    refunds: Array<{ id: number; payment_id: number; amount: number; reason: string; created_at: string | null }>;
    can: { confirm: boolean; refund: boolean };
}>();

const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
const statusLabels: Record<string, string> = {
    pending: 'Chờ thanh toán', paid: 'Đã thu', failed: 'Thất bại', cancelled: 'Đã huỷ', expired: 'Hết hạn', partially_refunded: 'Hoàn một phần', refunded: 'Đã hoàn',
};
const refunding = ref<null | { id: number; max: number }>(null);
const refundForm = useForm({ amount: 0, reason: '', idempotency_key: '' });

function filter(status: string): void {
    router.get(props.baseUrl, status ? { status } : {}, { preserveState: true });
}

function confirmPayment(id: number): void {
    const note = prompt('Ghi chú xác nhận (vd. mã giao dịch ngân hàng):');
    if (note) router.post(`${props.baseUrl}/${id}/confirm`, { note }, { preserveScroll: true });
}

function openRefund(payment: { id: number; amount: number; refunded_amount: number }): void {
    refunding.value = { id: payment.id, max: payment.amount - payment.refunded_amount };
    refundForm.amount = refunding.value.max;
    refundForm.reason = '';
    refundForm.idempotency_key = crypto.randomUUID().replaceAll('-', '').slice(0, 24);
}

function submitRefund(): void {
    refundForm.post(`${props.baseUrl}/${refunding.value!.id}/refunds`, { preserveScroll: true, onSuccess: () => (refunding.value = null) });
}

function completeRefund(id: number): void {
    const note = prompt('Ghi chú (vd. mã giao dịch chuyển trả):');
    if (note) router.post(props.baseUrl.replace(/\/payments$/, `/refunds/${id}/complete`), { note }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Thanh toán · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Thanh toán · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Thanh toán" subtitle="Xác nhận chuyển khoản thủ công, hoàn tiền. Tổng hoàn không vượt số đã thu." />

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">Tất cả</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">{{ statusLabels[value] ?? value }}</button>
    </div>

    <section v-if="refunds.length" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
        <h2 class="mb-2 font-semibold">Chờ hoàn tiền thủ công</h2>
        <div v-for="refund in refunds" :key="refund.id" class="flex items-center justify-between border-t border-amber-100 py-2 text-sm">
            <span>{{ vnd(refund.amount) }} · {{ refund.reason }} · {{ refund.created_at }}</span>
            <button v-if="can.refund" type="button" class="text-indigo-600 hover:underline" @click="completeRefund(refund.id)">Đã chuyển trả</button>
        </div>
    </section>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Đơn</th>
                <th class="px-4 py-2">Phương thức</th>
                <th class="px-4 py-2">Số tiền</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Tạo lúc</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="payment in payments" :key="payment.id" class="border-t border-slate-100">
                <td class="px-4 py-2 font-mono">{{ payment.order_number }}</td>
                <td class="px-4 py-2">{{ payment.gateway }}</td>
                <td class="px-4 py-2">
                    {{ vnd(payment.amount) }}
                    <div v-if="payment.refunded_amount" class="text-xs text-slate-500">đã hoàn {{ vnd(payment.refunded_amount) }}</div>
                </td>
                <td class="px-4 py-2">
                    {{ statusLabels[payment.status] ?? payment.status }}
                    <div v-if="payment.status === 'pending' && payment.expires_at" class="text-xs text-slate-400">hết hạn {{ payment.expires_at }}</div>
                </td>
                <td class="px-4 py-2 text-xs">{{ payment.created_at }}</td>
                <td class="px-4 py-2 text-right">
                    <button v-if="payment.can_confirm && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="confirmPayment(payment.id)">Xác nhận đã nhận tiền</button>
                    <button v-if="payment.can_refund && can.refund" type="button" class="ml-3 text-indigo-600 hover:underline" @click="openRefund(payment)">Hoàn tiền</button>
                </td>
            </tr>
            <tr v-if="!payments.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có thanh toán nào.</td>
            </tr>
        </tbody>
    </table>

    <form v-if="refunding" class="mt-4 grid max-w-md gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitRefund">
        <p class="font-medium">Hoàn tiền (tối đa {{ vnd(refunding.max) }})</p>
        <input v-model.number="refundForm.amount" :class="inputClass" type="number" min="1" :max="refunding.max" />
        <input v-model="refundForm.reason" :class="inputClass" placeholder="Lý do" />
        <p v-for="(error, key) in refundForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="refundForm.processing">Hoàn tiền</button>
            <button type="button" :class="secondaryButton" @click="refunding = null">Huỷ</button>
        </div>
    </form>
</template>
