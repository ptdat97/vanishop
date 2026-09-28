<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
    filters: { status: string; payment_status: string; q: string };
    statuses: string[];
    paymentStatuses: string[];
    orders: Array<{
        id: number;
        number: string;
        placed_at: string;
        customer: string;
        phone: string;
        total: number;
        order_status: string;
        payment_status: string;
        payment_method: string;
        label: string;
    }>;
    pagination: { current: number; last: number; total: number };
}>();

const form = reactive({ ...props.filters });
const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
const statusLabels: Record<string, string> = { pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', processing: 'Đang xử lý', completed: 'Hoàn tất', cancelled: 'Đã huỷ' };

function search(page = 1): void {
    router.get(props.baseUrl, { ...form, page }, { preserveState: true });
}
</script>

<template>
    <Head :title="`Đơn hàng · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Đơn hàng · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Đơn hàng" :subtitle="`${pagination.total} đơn`" />

    <form class="mb-4 grid gap-2 sm:grid-cols-4" @submit.prevent="search()">
        <input v-model="form.q" :class="inputClass" placeholder="Số đơn hoặc SĐT" />
        <select v-model="form.status" :class="inputClass">
            <option value="">Mọi trạng thái</option>
            <option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] ?? status }}</option>
        </select>
        <select v-model="form.payment_status" :class="inputClass">
            <option value="">Mọi trạng thái thanh toán</option>
            <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
        </select>
        <button type="submit" :class="primaryButton">Lọc</button>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Đơn</th>
                <th class="px-4 py-2">Khách</th>
                <th class="px-4 py-2">Tổng</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Thanh toán</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="order in orders" :key="order.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <Link :href="`${baseUrl}/${order.id}`" class="font-mono text-indigo-600 hover:underline">{{ order.number }}</Link>
                    <div class="text-xs text-slate-400">{{ order.placed_at }}</div>
                </td>
                <td class="px-4 py-2">{{ order.customer }}<div class="text-xs text-slate-400">{{ order.phone }}</div></td>
                <td class="px-4 py-2">{{ vnd(order.total) }}</td>
                <td class="px-4 py-2">{{ statusLabels[order.order_status] ?? order.order_status }}<div class="text-xs text-slate-400">{{ order.label }}</div></td>
                <td class="px-4 py-2 text-xs">{{ order.payment_method }} · {{ order.payment_status }}</td>
            </tr>
            <tr v-if="!orders.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Không có đơn nào.</td>
            </tr>
        </tbody>
    </table>

    <div v-if="pagination.last > 1" class="mt-4 flex gap-2">
        <button v-if="pagination.current > 1" type="button" :class="secondaryButton" @click="search(pagination.current - 1)">Trang trước</button>
        <span class="px-2 py-2 text-sm text-slate-500">{{ pagination.current }} / {{ pagination.last }}</span>
        <button v-if="pagination.current < pagination.last" type="button" :class="secondaryButton" @click="search(pagination.current + 1)">Trang sau</button>
    </div>
</template>
