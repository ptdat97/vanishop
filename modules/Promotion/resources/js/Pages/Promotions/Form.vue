<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import DataTable from '@admin/Components/DataTable.vue';
import DateInput from '@admin/Components/DateInput.vue';
import WeeklyScheduleInput, { type WeeklyWindow } from '@admin/Components/WeeklyScheduleInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions, labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { SharedProps } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { PromotionLabels } from '../../labels';

/**
 * Cấu hình của một rule: JSON một cấp (số, chuỗi, boolean, danh sách giá trị đơn giản) — đủ cho các rule
 * đang có và tương thích với kiểu dữ liệu form của Inertia. Rule cần cấu trúc sâu hơn thì cần form riêng của plugin.
 */
type RuleValue = string | number | boolean | null | Array<string | number | boolean | null>;

type RuleInput = { type: string; config: Record<string, RuleValue> };

const props = defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
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
        recurrence: WeeklyWindow[] | null;
        usage_limit_per_customer: number | null;
        usage_count: number;
        budget_amount: number | null;
        budget_used_amount: number;
        starts_at: string | null;
        ends_at: string | null;
        lock_version: number;
        rules: Array<{ type: string; label: string | null; config: Record<string, unknown> }>;
        /** Do plugin quản lý (ManagedPromotions): chỉ xem. */
        managed: { owner: string; url: string | null } | null;
    };
    vouchers: Array<{ id: number; code: string; status: string; used_count: number; usage_limit: number | null; expires_at: string | null; customer_id: number | null }>;
    vouchersTotal: number;
    voucherFilters: { q: string };
    voucherPagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number } | null;
    actions: Array<{ type: string; label: string }>;
    ruleTypes: Array<{ type: string; label: string }>;
    stackings: string[];
}>();

const symbol = usePage<SharedProps>().props.app.money.symbol;
const actionOptions = props.actions.map((action) => ({ value: action.type, label: action.label }));
const ruleTypeOptions = props.ruleTypes.map((type) => ({ value: type.type, label: type.label }));
const voucherColumns = [
    { key: 'code', label: t('promotion::admin.promotions_form.code'), class: 'font-mono' },
    { key: 'used_count', label: t('promotion::admin.promotions_form.used') },
    { key: 'customer_id', label: t('promotion::admin.promotions_form.voucher_customer'), class: 'text-xs' },
    { key: 'expires_at', label: t('promotion::admin.promotions_form.expired'), class: 'text-xs' },
    { key: 'actions', label: '', align: 'right' as const },
];
const percent = ref(props.promotion?.action_config.basis_points ? props.promotion.action_config.basis_points / 100 : 10);
const amount = ref(props.promotion?.action_config.amount ?? 50000);
const maxAmount = ref<number | null>(props.promotion?.action_config.max_amount ?? null);

const { form, save } = useAdminForm({
    name: props.promotion?.name ?? '',
    status: props.promotion?.status ?? 'active',
    starts_at: props.promotion?.starts_at ?? null,
    ends_at: props.promotion?.ends_at ?? null,
    recurrence: (props.promotion?.recurrence ?? null) as WeeklyWindow[] | null,
    priority: props.promotion?.priority ?? 0,
    stacking: props.promotion?.stacking ?? 'combinable',
    requires_voucher: props.promotion?.requires_voucher ?? true,
    action_type: props.promotion?.action_type ?? 'percent_off',
    action_config: {} as Record<string, number>,
    rules: [] as RuleInput[],
    usage_limit: props.promotion?.usage_limit ?? null,
    usage_limit_per_customer: props.promotion?.usage_limit_per_customer ?? null,
    budget_amount: props.promotion?.budget_amount ?? null,
    lock_version: props.promotion?.lock_version ?? null,
}, { lockVersion: () => props.promotion?.lock_version });

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
            rulesError.value = t('promotion::admin.promotions_form.settings_condition_type_not_valid', { type: rule.type });
            return false;
        }
    }

    rulesError.value = null;
    form.rules = parsed;

    return true;
}

watch([() => form.action_type, percent, amount, maxAmount], () => {
    form.action_config = form.action_type === 'percent_off'
        ? { basis_points: Math.round(Number(percent.value) * 100), ...(maxAmount.value ? { max_amount: Number(maxAmount.value) } : {}) }
        : form.action_type === 'amount_off' ? { amount: Number(amount.value) } : {};
}, { immediate: true });

const voucherForm = useForm({ code: '', prefix: '', count: null as number | null, usage_limit: 1 as number | null, expires_at: null as string | null });

function submit(): void {
    if (!collectRules()) {
        return;
    }

    if (props.promotion) {
        save('put', `${props.baseUrl}/${props.promotion.id}`);
    } else {
        save('post', props.baseUrl);
    }
}

async function destroy(): Promise<void> {
    if (props.promotion && (await confirmAction({ title: t('promotion::admin.promotions_form.delete_promotion'), message: t('promotion::admin.promotions_form.all_promotion_s_vouchers_deleted'), confirmLabel: t('promotion::admin.promotions_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.promotion.id}`);
    }
}

function createVouchers(): void {
    voucherForm.post(`${props.baseUrl}/${props.promotion!.id}/vouchers`, { preserveScroll: true, onSuccess: () => voucherForm.reset('code', 'count') });
}

// Tìm mã / chuyển trang danh sách voucher: chỉ tải lại phần voucher, giữ vị trí cuộn và dữ liệu form đang sửa.
const voucherSearch = ref(props.voucherFilters.q);
function loadVouchers(page = 1): void {
    router.get(
        `${props.baseUrl}/${props.promotion!.id}/edit`,
        { voucher_q: voucherSearch.value.trim() || undefined, voucher_page: page > 1 ? page : undefined },
        { only: ['vouchers', 'voucherFilters', 'voucherPagination'], preserveState: true, preserveScroll: true, replace: true },
    );
}

function toggleVoucher(voucher: { id: number; status: string }): void {
    router.patch(`${props.baseUrl}/${props.promotion!.id}/vouchers/${voucher.id}`, { status: voucher.status === 'active' ? 'inactive' : 'active' }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="promotion ? t('promotion::admin.promotions_form.edit_promotion') : t('promotion::admin.promotions_form.add_promotion')" />
    <PageHeader :title="promotion ? t('promotion::admin.promotions_form.edit_promotion_name', { name: promotion.name }) : t('promotion::admin.promotions_form.add_promotion')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('promotion::admin.promotions_form.back') }}</Link>
        <a v-if="promotion?.managed?.url" :href="promotion.managed.url" :class="primaryButton">{{ t('promotion::admin.promotions_form.managed_open') }}</a>
        <button v-if="promotion && !promotion.managed" type="button" :class="dangerButton" @click="destroy">{{ t('promotion::admin.promotions_form.delete') }}</button>
    </PageHeader>

    <p v-if="promotion?.managed" class="mb-4 max-w-3xl rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800" data-managed-promotion>{{ t('promotion::admin.promotions_form.managed_note', { owner: promotion.managed.owner }) }}</p>
    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <fieldset :disabled="!!promotion?.managed" class="contents">
        <FormField :label="t('promotion::admin.promotions_form.name')" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
        <FormField :label="t('promotion::admin.promotions_form.status')" :error="form.errors.status">
            <Select v-model="form.status" :options="labelOptions(labels.promotion_status)" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.discount_type')" :error="form.errors.action_type">
            <Select v-model="form.action_type" :options="actionOptions" />
        </FormField>
        <FormField v-if="form.action_type === 'percent_off' || (promotion?.managed && promotion.action_config.basis_points !== undefined)" :label="t('promotion::admin.promotions_form.discount_percentage')" :error="form.errors.action_config">
            <input v-model.number="percent" :class="inputClass" type="number" step="0.01" min="0.01" max="100" />
        </FormField>
        <FormField v-if="form.action_type === 'percent_off'" :label="t('promotion::admin.promotions_form.max_discount_symbol', { symbol })" :hint="t('promotion::admin.promotions_form.leave_empty_unlimited')">
            <input v-model.number="maxAmount" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField v-else-if="form.action_type === 'amount_off'" :label="t('promotion::admin.promotions_form.discount_amount_symbol', { symbol })" :error="form.errors.action_config">
            <input v-model.number="amount" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.valid_from')" :error="form.errors.starts_at"><DateInput v-model="form.starts_at" with-time /></FormField>
        <FormField :label="t('promotion::admin.promotions_form.valid_until')" :error="form.errors.ends_at"><DateInput v-model="form.ends_at" with-time /></FormField>
        <div class="sm:col-span-2"><WeeklyScheduleInput v-model="form.recurrence" :error="form.errors.recurrence" /></div>
        <FormField label="Priority" :hint="t('promotion::admin.promotions_form.higher_evaluated_first')" :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.combination')" :error="form.errors.stacking">
            <Select v-model="form.stacking" :options="codeOptions(stackings, labels.stacking)" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.maximum_total_uses')" :hint="t('promotion::admin.promotions_form.leave_empty_unlimited')" :error="form.errors.usage_limit">
            <input v-model.number="form.usage_limit" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.uses_per_customer')" :hint="t('promotion::admin.promotions_form.uses_per_customer_hint')" :error="form.errors.usage_limit_per_customer">
            <input v-model.number="form.usage_limit_per_customer" :class="inputClass" type="number" min="1" />
        </FormField>
        <FormField :label="t('promotion::admin.promotions_form.budget_symbol', { symbol })" :hint="t('promotion::admin.promotions_form.maximum_total_discount_leave_empty')" :error="form.errors.budget_amount">
            <input v-model.number="form.budget_amount" :class="inputClass" type="number" min="1" />
        </FormField>
        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.requires_voucher" type="checkbox" /> {{ t('promotion::admin.promotions_form.requires_voucher_code_unchecked_applied') }}</label>
        <div class="sm:col-span-2">
            <p class="mb-2 text-sm font-medium">{{ t('promotion::admin.promotions_form.conditions_rules_provided_core_plugins') }}</p>
            <p v-if="!ruleTypes.length" class="text-sm text-slate-500">{{ t('promotion::admin.promotions_form.no_condition_types_yet_install') }} <code>vani.promotion-rules</code>{{ t('promotion::admin.promotions_form.use_them') }}</p>
            <div v-for="(rule, index) in rules" :key="index" class="mb-2 flex flex-wrap items-start gap-2">
                <div class="w-56"><Select v-model="rule.type" :options="ruleTypeOptions" :aria-label="t('promotion::admin.promotions_form.condition_type')" /></div>
                <input v-model="rule.config" :class="[inputClass, 'flex-1 font-mono text-xs']" placeholder='{"amount": 500000}' />
                <button type="button" :class="dangerButton" @click="rules.splice(index, 1)">{{ t('promotion::admin.promotions_form.remove') }}</button>
                <p v-if="form.errors[`rules.${index}.config`] || form.errors[`rules.${index}.type`]" class="w-full text-sm text-red-600">
                    {{ form.errors[`rules.${index}.config`] ?? form.errors[`rules.${index}.type`] }}
                </p>
            </div>
            <button v-if="ruleTypes.length" type="button" :class="secondaryButton" @click="addRule">{{ t('promotion::admin.promotions_form.add_condition') }}</button>
            <p v-if="rulesError" class="mt-2 text-sm text-red-600">{{ rulesError }}</p>
        </div>
        <p v-if="promotion" class="text-sm text-slate-500 sm:col-span-2">
            {{ t('promotion::admin.promotions_form.used_usage_count_times', { usage_count: promotion.usage_count }) }}<span v-if="promotion.budget_amount">, <MoneyText :amount="promotion.budget_used_amount" /> {{ t('promotion::admin.promotions_form.budget') }}</span>.
        </p>
        <div v-if="!promotion?.managed" class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('promotion::admin.promotions_form.save') }}</button></div>
        </fieldset>
    </form>

    <section v-if="promotion && promotion.requires_voucher" class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="mb-3 font-semibold">Voucher ({{ vouchersTotal }})</h2>
        <form v-if="!promotion.managed" class="mb-4 grid gap-3 sm:grid-cols-5" @submit.prevent="createVouchers">
            <input v-model="voucherForm.code" :class="inputClass" :placeholder="t('promotion::admin.promotions_form.specific_code_sale10')" />
            <input v-model="voucherForm.prefix" :class="inputClass" :placeholder="t('promotion::admin.promotions_form.prefix')" />
            <input v-model.number="voucherForm.count" :class="inputClass" type="number" min="1" max="5000" :placeholder="t('promotion::admin.promotions_form.codes_generate')" />
            <input v-model.number="voucherForm.usage_limit" :class="inputClass" type="number" min="1" :placeholder="t('promotion::admin.promotions_form.uses_code')" />
            <button type="submit" :class="primaryButton" :disabled="voucherForm.processing">{{ t('promotion::admin.promotions_form.create_codes') }}</button>
            <p v-for="(error, key) in voucherForm.errors" :key="key" class="text-sm text-red-600 sm:col-span-5">{{ error }}</p>
        </form>
        <form class="mb-3 flex gap-2" role="search" @submit.prevent="loadVouchers()">
            <input v-model="voucherSearch" :class="inputClass" type="search" :placeholder="t('promotion::admin.promotions_form.search_codes_sale10')" :aria-label="t('promotion::admin.promotions_form.search_vouchers_code')" />
            <button type="submit" :class="secondaryButton">{{ t('promotion::admin.promotions_form.search') }}</button>
        </form>
        <DataTable :columns="voucherColumns" :rows="vouchers" :empty-text="voucherFilters.q ? t('promotion::admin.promotions_form.no_matching_codes') : t('promotion::admin.promotions_form.no_vouchers_yet')">
            <template #cell-code="{ row }">{{ row.code }}<span v-if="row.status !== 'active'" class="font-sans text-xs text-slate-400"> {{ t('promotion::admin.promotions_form.off') }}</span></template>
            <template #cell-used_count="{ row }">{{ row.used_count }}<span v-if="row.usage_limit"> / {{ row.usage_limit }}</span></template>
            <template #cell-expires_at="{ row }">{{ row.expires_at ?? '—' }}</template>
            <template #cell-customer_id="{ row }">{{ row.customer_id ? `#${row.customer_id}` : t('promotion::admin.promotions_form.voucher_everyone') }}</template>
            <template #cell-actions="{ row }">
                <button v-if="!promotion.managed" type="button" class="text-xs text-indigo-600 hover:underline" @click="toggleVoucher(row)">{{ row.status === 'active' ? t('promotion::admin.promotions_form.off_2') : t('promotion::admin.promotions_form.enable') }}</button>
            </template>
        </DataTable>
        <Pagination
            v-if="voucherPagination"
            :current="voucherPagination.page"
            :last="voucherPagination.last_page"
            :from="voucherPagination.from"
            :to="voucherPagination.to"
            :total="voucherPagination.total"
            @change="loadVouchers($event)"
        />
    </section>
</template>
