<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    storeUrl: string;
    orderUrl: string;
    order: { id: number; number: string };
    deadline: string | null;
    lines: Array<{
        id: number;
        sku: string;
        name: string;
        color: string | null;
        size: string;
        ordered: number;
        returnable: number;
    }>;
    reasons: string[];
}>();

const reasonLabels: Record<string, string> = {
    wrong_size: 'Sai size',
    not_as_described: 'Không đúng mô tả',
    defective: 'Lỗi sản phẩm',
    changed_mind: 'Đổi ý',
    other: 'Khác',
};
const form = useForm({
    order_id: props.order.id,
    lines: Object.fromEntries(props.lines.map((line) => [line.id, 0])) as Record<number, number>,
    reason_code: props.reasons[0] ?? 'other',
    note: '',
    exchange: false,
    exchange_skus: Object.fromEntries(props.lines.map((line) => [line.id, ''])) as Record<number, string>,
});
const errors = computed(() => usePage().props.errors as Record<string, string>);
const total = computed(() => Object.values(form.lines).reduce((sum, quantity) => sum + Number(quantity || 0), 0));
const deadlineText = computed(() => (props.deadline ? new Date(props.deadline).toLocaleDateString('vi-VN') : null));

function submit(): void {
    // Đổi hàng: gửi SKU thay thế (mặc định SKU cũ — nhân viên sửa thành size/màu khách muốn); không đổi thì bỏ trống.
    form.transform((data) => ({ ...data, exchange_skus: data.exchange ? data.exchange_skus : {} })).post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Tạo đổi/trả — ${order.number}`" />
    <PageHeader
        :title="`Tạo yêu cầu đổi/trả cho đơn ${order.number}`"
        subtitle="Tạo hộ khách (gọi điện, nhắn tin). Áp dụng cùng chính sách đổi/trả như khi khách tự gửi."
    >
        <Link :href="orderUrl" :class="secondaryButton">← Đơn {{ order.number }}</Link>
    </PageHeader>

    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
        {{ errors.business }}
    </p>
    <p v-if="deadlineText" class="mb-4 text-sm text-slate-600">Hạn đổi/trả theo chính sách: {{ deadlineText }}</p>

    <form class="space-y-6" @submit.prevent="submit">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-2 font-medium">Sản phẩm</th>
                        <th class="px-4 py-2 text-right font-medium">Đã mua</th>
                        <th class="px-4 py-2 text-right font-medium">Còn trả được</th>
                        <th class="px-4 py-2 text-right font-medium">Số lượng trả</th>
                        <th v-if="form.exchange" class="px-4 py-2 font-medium">Đổi sang SKU</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.id" class="border-b border-slate-100">
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ line.name }}</div>
                            <div class="text-xs text-slate-500">
                                {{ line.sku }} ·
                                {{ [line.color, line.size].filter(Boolean).join(' / ') }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right">{{ line.ordered }}</td>
                        <td class="px-4 py-2 text-right">
                            {{ line.returnable }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input
                                v-model.number="form.lines[line.id]"
                                type="number"
                                min="0"
                                :max="line.returnable"
                                :disabled="line.returnable === 0"
                                :class="[inputClass, 'w-24 text-right']"
                            />
                        </td>
                        <td v-if="form.exchange" class="px-4 py-2">
                            <input
                                v-model="form.exchange_skus[line.id]"
                                :disabled="!form.lines[line.id]"
                                :placeholder="line.sku"
                                :class="[inputClass, 'w-48 font-mono uppercase']"
                            />
                            <p v-if="errors[`exchange_skus.${line.id}`]" class="mt-1 text-xs text-red-600">{{ errors[`exchange_skus.${line.id}`] }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid max-w-xl gap-4">
            <label class="flex items-start gap-2 text-sm">
                <input v-model="form.exchange" type="checkbox" class="mt-1" />
                <span>
                    <span class="font-medium">Đổi hàng</span> thay vì hoàn tiền — nhập SKU thay thế cho từng dòng trả. Cùng mẫu (đổi size/màu): không tính chênh;
                    mẫu khác: theo giá hiện tại, khách bù phần thiếu (COD) hoặc được hoàn phần thừa. Đơn thay thế tạo khi nhận hàng trả, miễn phí giao.
                </span>
            </label>
            <FormField label="Lý do" :error="form.errors.reason_code">
                <select v-model="form.reason_code" :class="inputClass">
                    <option v-for="reason in reasons" :key="reason" :value="reason">
                        {{ reasonLabels[reason] ?? reason }}
                    </option>
                </select>
            </FormField>
            <FormField label="Ghi chú (nội dung khách báo)" :error="form.errors.note">
                <textarea v-model="form.note" rows="3" maxlength="500" :class="inputClass" />
            </FormField>
            <p v-if="form.errors.lines" class="text-sm text-red-600">
                {{ form.errors.lines }}
            </p>
            <div>
                <button type="submit" :class="primaryButton" :disabled="form.processing || total === 0">Tạo yêu cầu ({{ total }} sản phẩm)</button>
            </div>
        </div>
    </form>
</template>
