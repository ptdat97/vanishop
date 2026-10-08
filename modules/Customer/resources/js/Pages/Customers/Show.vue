<script setup lang="ts">
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    customer: {
        id: string;
        phone: string | null;
        email: string | null;
        full_name: string | null;
        status: string;
        registered: boolean;
        created_at: string | null;
        birth_date: string | null;
        gender: string | null;
        last_login_at: string | null;
        merged_into: string | null;
    };
    addresses: Array<{
        id: number;
        label: string | null;
        full_name: string;
        phone: string;
        street_line: string;
        ward_name: string;
        province_name: string;
        is_default: boolean;
    }>;
    consents: Array<{
        channel: string;
        purpose: string;
        granted: boolean;
        granted_at: string | null;
        revoked_at: string | null;
        source: string;
    }>;
    stats: {
        orders_count: number;
        total_spent: number;
        first_order_at: string | null;
        last_order_at: string | null;
    };
    orders: Array<{
        number: string;
        status: string;
        total: number;
        placed_at: string;
    }>;
    can: { merge: boolean; anonymize: boolean; segment: boolean };
    segment: { customer_group_id: number | null; tags: string[]; groups: Array<{ id: number; code: string; name: string }> };
    extensions: ExtensionDetail & { id: number };
}>();

const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
const statusLabels: Record<string, string> = {
    active: 'Hoạt động',
    merged: 'Đã hợp nhất',
    anonymized: 'Đã ẩn danh',
};
const mergeForm = useForm({ target: '' });
const segmentForm = useForm({ customer_group_id: props.segment.customer_group_id, tags: props.segment.tags.join(', ') });

function saveSegment(): void {
    segmentForm.put(`${props.baseUrl}/${props.customer.id}/segment`, { preserveScroll: true });
}

function merge(): void {
    if (!confirm('Chuyển toàn bộ đơn, địa chỉ, consent của khách này sang khách đích? Không hoàn tác được.')) return;
    mergeForm.post(`${props.baseUrl}/${props.customer.id}/merge`);
}

function anonymize(): void {
    if (!confirm('Ẩn danh hoá khách này (xoá thông tin cá nhân, giữ đơn hàng)? Không hoàn tác được.')) return;
    router.post(`${props.baseUrl}/${props.customer.id}/anonymize`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="customer.full_name ?? customer.id" />
    <PageHeader
        :title="customer.full_name ?? '(chưa có tên)'"
        :subtitle="`${customer.id} · ${statusLabels[customer.status]} · ${customer.registered ? 'Tài khoản' : 'Khách vãng lai'}`"
    >
        <Link :href="baseUrl" :class="secondaryButton">Danh sách</Link>
        <ExtensionActions :actions="extensions.actions" :ids="[extensions.id]" />
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <ExtensionTabs :tabs="extensions.tabs" />
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Đơn hàng gần đây</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="order in orders" :key="order.number" class="border-t border-slate-100">
                            <td class="py-2 font-mono text-xs">
                                {{ order.number }}
                            </td>
                            <td class="py-2">{{ order.status }}</td>
                            <td class="py-2 text-right">
                                {{ vnd(order.total) }}
                            </td>
                        </tr>
                        <tr v-if="!orders.length">
                            <td class="py-2 text-slate-500">Chưa có đơn.</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Thống kê mua</h2>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Số đơn</dt>
                    <dd>{{ stats.orders_count }}</dd>
                    <dt class="text-slate-500">Tổng chi tiêu</dt>
                    <dd>{{ vnd(stats.total_spent) }}</dd>
                    <dt class="text-slate-500">Mua đầu / gần nhất</dt>
                    <dd class="text-xs">{{ stats.first_order_at ?? '—' }} / {{ stats.last_order_at ?? '—' }}</dd>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Consent</h2>
                <div v-for="consent in consents" :key="`${consent.channel}-${consent.purpose}`" class="text-sm">
                    {{ consent.channel }} · {{ consent.purpose }}:
                    <strong :class="consent.granted ? 'text-green-700' : 'text-slate-500'">{{ consent.granted ? 'Đồng ý' : 'Đã rút' }}</strong>
                    <span class="text-xs text-slate-400"> ({{ consent.source }})</span>
                </div>
                <p v-if="!consents.length" class="text-sm text-slate-500">Chưa có consent.</p>
            </section>
        </div>

        <div class="space-y-6">
            <form class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="saveSegment">
                <h2 class="font-semibold">Nhóm & tag</h2>
                <label class="block">
                    <span class="text-slate-500">Nhóm khách (quyết định giá thành viên)</span>
                    <select v-model="segmentForm.customer_group_id" :class="inputClass" :disabled="!can.segment">
                        <option :value="null">— Không nhóm —</option>
                        <option v-for="group in segment.groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-slate-500">Tag (phân tách dấu phẩy)</span>
                    <input v-model="segmentForm.tags" :class="inputClass" :disabled="!can.segment" placeholder="vd. khach-si, kol" />
                </label>
                <p v-for="(error, key) in segmentForm.errors" :key="key" class="text-red-600">{{ error }}</p>
                <button v-if="can.segment" type="submit" :class="secondaryButton" :disabled="segmentForm.processing">Lưu</button>
            </form>
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-3 font-semibold">Hồ sơ</h2>
                <dl class="space-y-1">
                    <div>
                        <dt class="inline text-slate-500">SĐT:</dt>
                        <dd class="inline font-mono">
                            {{ customer.phone ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">Email:</dt>
                        <dd class="inline">{{ customer.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">Ngày sinh:</dt>
                        <dd class="inline">{{ customer.birth_date ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">Đăng nhập gần nhất:</dt>
                        <dd class="inline">
                            {{ customer.last_login_at ?? '—' }}
                        </dd>
                    </div>
                    <div v-if="customer.merged_into">
                        <dt class="inline text-slate-500">Hợp nhất vào:</dt>
                        <dd class="inline">
                            <Link :href="`${baseUrl}/${customer.merged_into}`" class="text-indigo-600 hover:underline">{{ customer.merged_into }}</Link>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-3 font-semibold">Sổ địa chỉ</h2>
                <div v-for="address in addresses" :key="address.id" class="mb-2">
                    <div class="font-medium">
                        {{ address.full_name }} · {{ address.phone }}<span v-if="address.is_default" class="text-xs text-indigo-600"> (mặc định)</span>
                    </div>
                    <div class="text-slate-500">
                        {{ address.street_line }}, {{ address.ward_name }},
                        {{ address.province_name }}
                    </div>
                </div>
                <p v-if="!addresses.length" class="text-slate-500">Chưa có địa chỉ.</p>
            </section>

            <section
                v-if="customer.status === 'active' && (can.merge || can.anonymize)"
                class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm"
            >
                <h2 class="font-semibold">Thao tác</h2>
                <form v-if="can.merge" class="space-y-2" @submit.prevent="merge">
                    <label class="block text-slate-500">Hợp nhất khách này vào (mã khách đích)</label>
                    <input v-model="mergeForm.target" :class="inputClass" placeholder="01J…" />
                    <p v-if="mergeForm.errors.target" class="text-xs text-red-600">
                        {{ mergeForm.errors.target }}
                    </p>
                    <button type="submit" :class="secondaryButton" :disabled="mergeForm.processing">Hợp nhất</button>
                </form>
                <button v-if="can.anonymize" :class="dangerButton" @click="anonymize">Ẩn danh hoá</button>
            </section>
        </div>
    </div>
</template>
