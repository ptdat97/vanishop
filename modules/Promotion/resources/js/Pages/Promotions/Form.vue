<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/**
 * Cấu hình của một rule: JSON một cấp (số, chuỗi, boolean, danh sách giá trị đơn giản) — đủ cho các rule
 * đang có và tương thích với kiểu dữ liệu form của Inertia. Rule cần cấu trúc sâu hơn thì cần form riêng của plugin.
 */
type RuleValue = string | number | boolean | null | Array<string | number | boolean | null>;

type RuleInput = { type: string; config: Record<string, RuleValue> };

const props = defineProps<{
    baseUrl: string;
    promotion: null | {
        id: number;
        name: string;
        status: string;
        priority: number;
        stacking: string;
        requires_voucher: boolean;
        action_type: string;
        action_config: Record<string, number>;
        usage_limit: number | null;
        usage_count: number;
        budget_amount: number | null;
        budget_used_amount: number;
        starts_at: string | null;
        ends_at: string | null;
        lock_version: number;
        rules: Array<{ type: string; label: string | null; config: Record<string, unknown> }>;
    };
    vouchers: Array<{ id: number; code: string; status: string; used_count: number; usage_limit: number | null; expires_at: string | null }>;
    vouchersTotal: number;
    actions: Array<{ type: string; label: string }>;
    ruleTypes: Array<{ type: string; label: string }>;
    stackings: string[];
}>();

const percent = ref(props.promotion?.action_config.basis_points ? props.promotion.action_config.basis_points / 100 : 10);
const amount = ref(props.promotion?.action_config.amount ?? 50000);

const form = useForm({
    name: props.promotion?.name ?? '',
    status: props.promotion?.status ?? 'active',
    starts_at: props.promotion?.starts_at ?? null,
    ends_at: props.promotion?.ends_at ?? null,
    priority: props.promotion?.priority ?? 0,
    stacking: props.promotion?.stacking ?? 'combinable',
    requires_voucher: props.promotion?.requires_voucher ?? true,
    action_type: props.promotion?.action_type ?? 'percent_off',
    action_config: {} as Record<string, number>,
    rules: [] as RuleInput[],
    usage_limit: props.promotion?.usage_limit ?? null,
    budget_amount: props.promotion?.budget_amount ?? null,
    lock_version: props.promotion?.lock_version ?? null,
});

// Điều kiện do plugin cung cấp: config nhập dạng JSON, Core/plugin kiểm tra khi lưu.
const rules = ref<Array<{ type: string; config: string }>>(
    (props.promotion?.rules ?? []).map((rule) => ({ type: rule.type, config: JSON.stringify(rule.config ?? {}) })),
);
const rulesError = ref<string | null>(null);

function addRule(): void {
    if (props.ruleTypes.length) {
        rules.value.push({ type: props.ruleTypes[0].type, config: '{}' });
    }
}

function collectRules(): boolean {
    const parsed: RuleInput[] = [];
    for (const rule of rules.value) {
        try {
            parsed.push({ type: rule.type, config: rule.config.trim() === '' ? {} : (JSON.parse(rule.config) as Record<string, RuleValue>) });
        } catch {
            rulesError.value = `Cấu hình của điều kiện "${rule.type}" không phải JSON hợp lệ.`;
            return false;
        }
    }

    rulesError.value = null;
    form.rules = parsed;

    return true;
}

watch([() => form.action_type, percent, amount], () => {
    form.action_config = form.action_type === 'percent_off' ? { basis_points: Math.round(Number(percent.value) * 100) } : form.action_type === 'amount_off' ? { amount: Number(amount.value) } : {};
}, { immediate: true });

const voucherForm = useForm({ code: '', prefix: '', count: null as number | null, usage_limit: 1 as number | null, expires_at: null as string | null });

function submit(): void {
    if (!collectRules()) {
        return;
    }

    if (props.promotion) {
        form.put(`${props.baseUrl}/${props.promotion.id}`, { preserveScroll: true });
    } else {
        form.post(props.baseUrl);
    }
}

function destroy(): void {
    if (props.promotion && confirm('Xoá khuyến mãi và toàn bộ voucher?')) {
        router.delete(`${props.baseUrl}/${props.promotion.id}`);
    }
}

function createVouchers(): void {
    voucherForm.post(`${props.baseUrl}/${props.promotion!.id}/vouchers`, { preserveScroll: true, onSuccess: () => voucherForm.reset('code', 'count') });
}

function toggleVoucher(voucher: { id: number; status: string }): void {
    router.patch(`${props.baseUrl}/${props.promotion!.id}/vouchers/${voucher.id}`, { status: voucher.status === 'active' ? 'inactive' : 'active' }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="promotion ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi'" />
    <PageHeader :title="promotion ? `Sửa khuyến mãi: ${promotion.name}` : 'Thêm khuyến mãi'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="promotion" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField label="Tên" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
        <FormField label="Trạng thái" :error="form.errors.status">
            <select v-model="form.status" :class="inputClass">
                <option value="active">Đang dùng</option>
                <option value="inactive">Tắt</option>
            </select>
        </FormField>
        <FormField label="Loại giảm" :error="form.errors.action_type">
            <select v-model="form.action_type" :class="inputClass">
                <option v-for="action in actions" :key="action.type" :value="action.type">{{ action.label }}</option>
            </select>
        </FormField>
        <FormField v-if="form.action_type === 'percent_off'" label="Phần trăm giảm" :error="form.errors.action_config">
            <input v-model.number="percent" :class="inputClass" type="number" step="0.01" min="0.01" max="100" />
        </FormField>
        <FormField v-else-if="form.action_type === 'amount_off'" label="Số tiền giảm (₫)" :error="form.errors.action_config">
            <input v-model.number="amount" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField label="Hiệu lực từ" :error="form.errors.starts_at"><input v-model="form.starts_at" :class="inputClass" type="datetime-local" /></FormField>
        <FormField label="Hiệu lực đến" :error="form.errors.ends_at"><input v-model="form.ends_at" :class="inputClass" type="datetime-local" /></FormField>
        <FormField label="Priority" hint="Cao hơn được đánh giá trước." :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField label="Kết hợp" :error="form.errors.stacking">
            <select v-model="form.stacking" :class="inputClass">
                <option value="combinable">Cộng dồn với khuyến mãi khác</option>
                <option value="exclusive">Độc quyền</option>
            </select>
        </FormField>
        <FormField label="Tổng lượt dùng tối đa" hint="Để trống = không giới hạn." :error="form.errors.usage_limit">
            <input v-model.number="form.usage_limit" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField label="Ngân sách (₫)" hint="Tổng tiền giảm tối đa. Để trống = không giới hạn." :error="form.errors.budget_amount">
            <input v-model.number="form.budget_amount" :class="inputClass" type="number" min="1" />
        </FormField>
        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.requires_voucher" type="checkbox" /> Cần nhập mã voucher (bỏ chọn = tự động áp dụng)</label>
        <div class="sm:col-span-2">
            <p class="mb-2 text-sm font-medium">Điều kiện áp dụng (rule do Core/plugin cung cấp)</p>
            <p v-if="!ruleTypes.length" class="text-sm text-slate-500">Chưa có loại điều kiện nào. Cài và bật plugin cung cấp rule (ví dụ <code>vani.promotion-rules</code>) để dùng.</p>
            <div v-for="(rule, index) in rules" :key="index" class="mb-2 flex flex-wrap items-start gap-2">
                <select v-model="rule.type" :class="inputClass">
                    <option v-for="type in ruleTypes" :key="type.type" :value="type.type">{{ type.label }}</option>
                </select>
                <input v-model="rule.config" :class="[inputClass, 'flex-1 font-mono text-xs']" placeholder='{"amount": 500000}' />
                <button type="button" :class="dangerButton" @click="rules.splice(index, 1)">Bỏ</button>
                <p v-if="form.errors[`rules.${index}.config`] || form.errors[`rules.${index}.type`]" class="w-full text-sm text-red-600">
                    {{ form.errors[`rules.${index}.config`] ?? form.errors[`rules.${index}.type`] }}
                </p>
            </div>
            <button v-if="ruleTypes.length" type="button" :class="secondaryButton" @click="addRule">Thêm điều kiện</button>
            <p v-if="rulesError" class="mt-2 text-sm text-red-600">{{ rulesError }}</p>
        </div>
        <p v-if="promotion" class="text-sm text-slate-500 sm:col-span-2">
            Đã dùng {{ promotion.usage_count }} lượt<span v-if="promotion.budget_amount">, {{ promotion.budget_used_amount.toLocaleString('vi-VN') }} ₫ ngân sách</span>.
        </p>
        <p v-if="form.errors.lock_version" class="text-sm text-red-600 sm:col-span-2">{{ form.errors.lock_version }}</p>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>

    <section v-if="promotion && promotion.requires_voucher" class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="mb-3 font-semibold">Voucher ({{ vouchersTotal }})</h2>
        <form class="mb-4 grid gap-3 sm:grid-cols-5" @submit.prevent="createVouchers">
            <input v-model="voucherForm.code" :class="inputClass" placeholder="Mã cụ thể, vd. SALE10" />
            <input v-model="voucherForm.prefix" :class="inputClass" placeholder="hoặc tiền tố" />
            <input v-model.number="voucherForm.count" :class="inputClass" type="number" min="1" max="5000" placeholder="số mã sinh" />
            <input v-model.number="voucherForm.usage_limit" :class="inputClass" type="number" min="1" placeholder="lượt/mã" />
            <button type="submit" :class="primaryButton" :disabled="voucherForm.processing">Tạo mã</button>
            <p v-for="(error, key) in voucherForm.errors" :key="key" class="text-sm text-red-600 sm:col-span-5">{{ error }}</p>
        </form>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr><th class="py-1">Mã</th><th>Đã dùng</th><th>Hết hạn</th><th /></tr>
            </thead>
            <tbody>
                <tr v-for="voucher in vouchers" :key="voucher.id" class="border-t border-slate-100">
                    <td class="py-1 font-mono">{{ voucher.code }}<span v-if="voucher.status !== 'active'" class="text-xs text-slate-400"> · tắt</span></td>
                    <td>{{ voucher.used_count }}<span v-if="voucher.usage_limit"> / {{ voucher.usage_limit }}</span></td>
                    <td class="text-xs">{{ voucher.expires_at ?? '—' }}</td>
                    <td class="text-right"><button type="button" class="text-xs text-indigo-600 hover:underline" @click="toggleVoucher(voucher)">{{ voucher.status === 'active' ? 'Tắt' : 'Bật' }}</button></td>
                </tr>
            </tbody>
        </table>
    </section>
</template>
