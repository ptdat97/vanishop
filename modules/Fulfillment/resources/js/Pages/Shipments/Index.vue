<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    baseUrl: string;
    filters: { status: string; order: string };
    statuses: string[];
    order: null | { id: number; number: string; status: string; can_create: boolean };
    shipments: Array<{
        id: number;
        order_number: string | null;
        carrier: string;
        manual: boolean;
        service_code: string | null;
        tracking_number: string | null;
        status: string;
        items: number;
        cod_amount: number;
        last_error: string | null;
        next: string[];
        can_book: boolean;
        can_cancel: boolean;
    }>;
    canManage: boolean;
}>();

const labels: Record<string, string> = {
    pending_booking: 'Chờ đặt vận đơn', booking_failed: 'Đặt lỗi', created: 'Đã có mã', picked_up: 'Đã lấy hàng', in_transit: 'Đang vận chuyển',
    out_for_delivery: 'Đang giao', failed_attempt: 'Giao không thành công', delivered: 'Đã giao', returning: 'Đang hoàn', returned: 'Đã hoàn về', cancelled: 'Đã huỷ',
};
const filters = reactive({ ...props.filters });
const errors = computed(() => usePage().props.errors as Record<string, string>);
const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;

function search(): void {
    router.get(props.baseUrl, filters, { preserveState: true });
}

function create(): void {
    router.post(props.baseUrl, { order_id: props.order!.id }, { preserveScroll: true });
}

function book(id: number): void {
    const tracking = prompt('Mã vận đơn:');
    if (!tracking) return;
    const service = prompt('Hãng / dịch vụ (vd. Viettel Post):') ?? '';
    router.post(`${props.baseUrl}/${id}/book`, { tracking_number: tracking, service_code: service || null }, { preserveScroll: true });
}

function move(id: number, status: string): void {
    router.post(`${props.baseUrl}/${id}/status`, { status }, { preserveScroll: true });
}

function cancel(id: number): void {
    const reason = prompt('Lý do huỷ vận đơn:');
    if (reason) router.post(`${props.baseUrl}/${id}/cancel`, { reason }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Giao hàng" />
    <p class="mb-2 text-sm text-slate-500">Giao hàng</p>
    <PageHeader title="Vận đơn" subtitle="Tồn kho được trừ khi mọi vận đơn của đơn đã rời kho; hàng hoàn về được nhập lại kho." />
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>

    <form class="mb-4 grid gap-2 sm:grid-cols-3" @submit.prevent="search">
        <input v-model="filters.order" :class="inputClass" placeholder="Số đơn" />
        <select v-model="filters.status" :class="inputClass">
            <option value="">Mọi trạng thái</option>
            <option v-for="status in statuses" :key="status" :value="status">{{ labels[status] ?? status }}</option>
        </select>
        <button type="submit" :class="primaryButton">Lọc</button>
    </form>

    <div v-if="order" class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <span>Đơn <span class="font-mono">{{ order.number }}</span> · {{ order.status }}</span>
        <button v-if="order.can_create && canManage" type="button" :class="primaryButton" @click="create">Tạo vận đơn</button>
    </div>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Đơn</th>
                <th class="px-4 py-2">Vận chuyển</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">COD</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="shipment in shipments" :key="shipment.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2 font-mono">{{ shipment.order_number }}<div class="text-xs text-slate-400">{{ shipment.items }} sản phẩm</div></td>
                <td class="px-4 py-2">
                    {{ shipment.carrier }}<span v-if="shipment.service_code"> · {{ shipment.service_code }}</span>
                    <div class="font-mono text-xs text-slate-500">{{ shipment.tracking_number ?? '—' }}</div>
                </td>
                <td class="px-4 py-2">
                    {{ labels[shipment.status] ?? shipment.status }}
                    <div v-if="shipment.last_error" class="text-xs text-red-600">{{ shipment.last_error }}</div>
                </td>
                <td class="px-4 py-2">{{ shipment.cod_amount ? vnd(shipment.cod_amount) : '—' }}</td>
                <td class="space-x-2 px-4 py-2 text-right">
                    <template v-if="canManage">
                        <button v-if="shipment.can_book && shipment.manual" type="button" class="text-indigo-600 hover:underline" @click="book(shipment.id)">Nhập mã vận đơn</button>
                        <button v-for="next in shipment.next" :key="next" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="move(shipment.id, next)">{{ labels[next] }}</button>
                        <button v-if="shipment.can_cancel" type="button" class="text-xs text-red-600 hover:underline" @click="cancel(shipment.id)">Huỷ</button>
                    </template>
                </td>
            </tr>
            <tr v-if="!shipments.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Chưa có vận đơn.</td>
            </tr>
        </tbody>
    </table>
</template>
