<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { payment_status: Record<string, string> };
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
        can_capture: boolean;
        can_refund: boolean;
    }>;
    refunds: Array<{ id: number; payment_id: number; amount: number; reason: string; created_at: string | null }>;
    can: { confirm: boolean; refund: boolean };
}>();

const label = (status: string): string => props.labels.payment_status[status] ?? status;
const tone = (status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' =>
    (({ pending: 'warning', authorized: 'info', paid: 'success', failed: 'danger', expired: 'danger' }) as const)[status as 'pending'] ?? 'neutral';
const columns = [
    { key: 'order_number', label: 'Đơn', class: 'font-mono' },
    { key: 'gateway', label: 'Phương thức' },
    { key: 'amount', label: 'Số tiền' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'created_at', label: 'Tạo lúc', class: 'text-xs' },
    { key: 'actions', label: '', align: 'right' as const },
];
const refunding = ref<null | { id: number; max: number }>(null);
const refundForm = useForm({ amount: 0, reason: '', idempotency_key: '' });

function filter(status: string): void {
    router.get(props.baseUrl, status ? { status } : {}, { preserveState: true });
}

async function capturePayment(id: number): Promise<void> {
    if ((await confirmAction({ title: 'Thu khoản đã giữ tiền này?', confirmLabel: 'Thu tiền' })) !== false) {
        router.post(`${props.baseUrl}/${id}/capture`, {}, { preserveScroll: true });
    }
}

async function confirmPayment(id: number): Promise<void> {
    const note = await confirmAction({ title: 'Xác nhận đã nhận tiền?', confirmLabel: 'Xác nhận', reason: { label: 'Ghi chú xác nhận', placeholder: 'vd. mã giao dịch ngân hàng', required: true } });
    if (note !== false) {
        router.post(`${props.baseUrl}/${id}/confirm`, { note }, { preserveScroll: true });
    }
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

async function completeRefund(id: number): Promise<void> {
    const note = await confirmAction({ title: 'Đã chuyển trả tiền cho khách?', confirmLabel: 'Đã chuyển trả', reason: { label: 'Ghi chú', placeholder: 'vd. mã giao dịch chuyển trả', required: true } });
    if (note !== false) {
        router.post(props.baseUrl.replace(/\/payments$/, `/refunds/${id}/complete`), { note }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Thanh toán" />
    <p class="mb-2 text-sm text-slate-500">Thanh toán</p>
    <PageHeader title="Thanh toán" subtitle="Xác nhận chuyển khoản thủ công, hoàn tiền. Tổng hoàn không vượt số đã thu." />

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">Tất cả</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">{{ label(value) }}</button>
    </div>

    <section v-if="refunds.length" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
        <h2 class="mb-2 font-semibold">Chờ hoàn tiền thủ công</h2>
        <div v-for="refund in refunds" :key="refund.id" class="flex items-center justify-between border-t border-amber-100 py-2 text-sm">
            <span><MoneyText :amount="refund.amount" /> · {{ refund.reason }} · {{ refund.created_at }}</span>
            <button v-if="can.refund" type="button" class="text-indigo-600 hover:underline" @click="completeRefund(refund.id)">Đã chuyển trả</button>
        </div>
    </section>

    <DataTable :columns="columns" :rows="payments" empty-text="Chưa có thanh toán nào.">
        <template #cell-amount="{ row }">
            <MoneyText :amount="row.amount" />
            <div v-if="row.refunded_amount" class="text-xs text-slate-500">đã hoàn <MoneyText :amount="row.refunded_amount" /></div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="label(row.status)" :tone="tone(row.status)" />
            <div v-if="row.status === 'pending' && row.expires_at" class="mt-1 text-xs text-slate-400">hết hạn {{ row.expires_at }}</div>
        </template>
        <template #cell-actions="{ row }">
            <button v-if="row.can_confirm && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="confirmPayment(row.id)">Xác nhận đã nhận tiền</button>
            <button v-if="row.can_capture && can.confirm" type="button" class="text-indigo-600 hover:underline" @click="capturePayment(row.id)">Thu tiền</button>
            <button v-if="row.can_refund && can.refund" type="button" class="ml-3 text-indigo-600 hover:underline" @click="openRefund(row)">Hoàn tiền</button>
        </template>
    </DataTable>

    <form v-if="refunding" class="mt-4 grid max-w-md gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitRefund">
        <p class="font-medium">Hoàn tiền (tối đa <MoneyText :amount="refunding.max" />)</p>
        <input v-model.number="refundForm.amount" :class="inputClass" type="number" min="1" :max="refunding.max" />
        <input v-model="refundForm.reason" :class="inputClass" placeholder="Lý do" />
        <p v-for="(error, key) in refundForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="refundForm.processing">Hoàn tiền</button>
            <button type="button" :class="secondaryButton" @click="refunding = null">Huỷ</button>
        </div>
    </form>
</template>
