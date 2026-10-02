<script setup lang="ts">
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type OrderLine = { sku: string; name: string; color_name: string | null; size_code: string; quantity: number; unit_amount: number; compare_at_amount: number | null; discount_amount: number; total_amount: number; tax_amount: number };

const props = defineProps<{
    baseUrl: string;
    order: {
        id: number;
        number: string;
        orderStatus: string;
        paymentStatus: string;
        fulfillmentStatus: string;
        paymentMethod: string;
        customerStatus: { code: string; label: string };
        amounts: { subtotal: number; discount: number; shipping: number; tax: number; total: number };
        customer: { full_name: string; phone: string; email: string | null };
        shippingAddress: Record<string, string>;
        shippingMethod: { label?: string; fee?: number };
        note: string | null;
        placedAt: string;
        lines: OrderLine[];
        adjustments: Array<{ type: string; code: string | null; label: string; amount: number }>;
        events: Array<{ type: string; from: string | null; to: string | null; reason: string | null; source: string | null; actor: string | null; data: Record<string, unknown> | null; at: string }>;
        lockVersion: number;
    };
    panels: Array<{ title: string; rows: Array<{ label: string; value: string }>; link?: { label: string; url: string } }>;
    can: { confirm: boolean; cancel: boolean; change_address: boolean; note: boolean };
    extensions: ExtensionDetail;
}>();

const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
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
const noteForm = useForm({ note: '' });
const eventLabels: Record<string, string> = { placed: 'Đặt hàng', status_changed: 'Đổi trạng thái', payment_status_changed: 'Thanh toán', address_changed: 'Đổi địa chỉ', note: 'Ghi chú' };

function confirmOrder(): void {
    router.post(`${url.value}/confirm`, {}, { preserveScroll: true });
}

function cancelOrder(): void {
    const reason = prompt('Lý do huỷ đơn:');
    if (reason) router.post(`${url.value}/cancel`, { reason }, { preserveScroll: true });
}

function saveAddress(): void {
    addressForm.put(`${url.value}/shipping-address`, { preserveScroll: true, onSuccess: () => (editingAddress.value = false) });
}

function addNote(): void {
    noteForm.post(`${url.value}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() });
}
</script>

<template>
    <Head :title="`Đơn ${order.number}`" />
    <p class="mb-2 text-sm text-slate-500">Đơn hàng</p>
    <PageHeader :title="`Đơn ${order.number}`" :subtitle="`${order.placedAt} · ${order.customerStatus.label}`">
        <Link :href="baseUrl" :class="secondaryButton">Danh sách</Link>
        <button v-if="can.confirm" type="button" :class="primaryButton" @click="confirmOrder">Xác nhận đơn</button>
        <button v-if="can.cancel" type="button" :class="dangerButton" @click="cancelOrder">Huỷ đơn</button>
        <ExtensionActions :actions="extensions.actions" :ids="[order.id]" />
    </PageHeader>
    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr><th class="px-4 py-2">Sản phẩm</th><th class="px-4 py-2">SL</th><th class="px-4 py-2">Đơn giá</th><th class="px-4 py-2">Giảm</th><th class="px-4 py-2 text-right">Thành tiền</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in order.lines" :key="line.sku" class="border-t border-slate-100">
                            <td class="px-4 py-2">{{ line.name }}<div class="font-mono text-xs text-slate-400">{{ line.sku }} · {{ line.color_name }} / {{ line.size_code }}</div></td>
                            <td class="px-4 py-2">{{ line.quantity }}</td>
                            <td class="px-4 py-2">{{ vnd(line.unit_amount) }}<div v-if="line.compare_at_amount" class="text-xs text-slate-400 line-through">{{ vnd(line.compare_at_amount) }}</div></td>
                            <td class="px-4 py-2">{{ line.discount_amount ? `−${vnd(line.discount_amount)}` : '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ vnd(line.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
                <dl class="space-y-1 border-t border-slate-100 p-4 text-sm">
                    <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ vnd(order.amounts.subtotal) }}</dd></div>
                    <div v-for="adjustment in order.adjustments" :key="adjustment.label" class="flex justify-between text-slate-600">
                        <dt>{{ adjustment.label }}<span v-if="adjustment.code" class="font-mono"> ({{ adjustment.code }})</span></dt><dd>{{ vnd(adjustment.amount) }}</dd>
                    </div>
                    <div class="flex justify-between"><dt>Phí giao ({{ order.shippingMethod.label }})</dt><dd>{{ vnd(order.amounts.shipping) }}</dd></div>
                    <div class="flex justify-between font-semibold"><dt>Tổng</dt><dd>{{ vnd(order.amounts.total) }}</dd></div>
                    <div class="flex justify-between text-xs text-slate-500"><dt>Trong đó VAT</dt><dd>{{ vnd(order.amounts.tax) }}</dd></div>
                </dl>
            </section>

            <ExtensionTabs :tabs="extensions.tabs" />

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Lịch sử</h2>
                <ol class="space-y-2 text-sm">
                    <li v-for="(event, index) in order.events" :key="index" class="border-l-2 border-slate-200 pl-3">
                        <span class="text-xs text-slate-400">{{ event.at }}</span> · <span class="font-medium">{{ eventLabels[event.type] ?? event.type }}</span>
                        <span v-if="event.to"> → {{ event.to }}</span>
                        <span v-if="event.reason" class="text-slate-500"> ({{ event.reason }})</span>
                        <span v-if="event.actor || event.source" class="text-xs text-slate-400"> · {{ event.actor ?? event.source }}</span>
                        <p v-if="event.type === 'note' && event.data" class="text-slate-700">{{ event.data.note }}</p>
                    </li>
                </ol>
                <form v-if="can.note" class="mt-3 flex gap-2" @submit.prevent="addNote">
                    <input v-model="noteForm.note" :class="inputClass" placeholder="Ghi chú nội bộ" />
                    <button type="submit" :class="secondaryButton" :disabled="noteForm.processing">Thêm</button>
                </form>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-2 font-semibold">Khách hàng</h2>
                <p>{{ order.customer.full_name }}</p>
                <p class="text-slate-600">{{ order.customer.phone }}</p>
                <p v-if="order.customer.email" class="text-slate-600">{{ order.customer.email }}</p>
                <p v-if="order.note" class="mt-2 rounded bg-amber-50 p-2">Ghi chú của khách: {{ order.note }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-semibold">Giao đến</h2>
                    <button v-if="can.change_address && !editingAddress" type="button" class="text-xs text-indigo-600 hover:underline" @click="editingAddress = true">Sửa</button>
                </div>
                <template v-if="!editingAddress">
                    <p>{{ order.shippingAddress.street_line }}</p>
                    <p class="text-slate-600">{{ order.shippingAddress.ward_name }}, {{ order.shippingAddress.province_name }}</p>
                </template>
                <form v-else class="space-y-2" @submit.prevent="saveAddress">
                    <FormField label="Số nhà, đường" :error="addressForm.errors.street_line"><input v-model="addressForm.street_line" :class="inputClass" /></FormField>
                    <FormField label="Phường/xã" :error="addressForm.errors.ward_name"><input v-model="addressForm.ward_name" :class="inputClass" /></FormField>
                    <FormField label="Mã phường/xã" :error="addressForm.errors.ward_code"><input v-model="addressForm.ward_code" :class="inputClass" /></FormField>
                    <FormField label="Tỉnh/thành" :error="addressForm.errors.province_name"><input v-model="addressForm.province_name" :class="inputClass" /></FormField>
                    <FormField label="Mã tỉnh/thành" :error="addressForm.errors.province_code"><input v-model="addressForm.province_code" :class="inputClass" /></FormField>
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
                    <div v-for="row in panel.rows" :key="row.label" class="flex justify-between gap-3"><dt class="text-slate-500">{{ row.label }}</dt><dd class="text-right">{{ row.value }}</dd></div>
                </dl>
                <Link v-if="panel.link" :href="panel.link.url" class="mt-2 inline-block text-xs text-indigo-600 hover:underline">{{ panel.link.label }}</Link>
            </section>
        </aside>
    </div>
</template>
