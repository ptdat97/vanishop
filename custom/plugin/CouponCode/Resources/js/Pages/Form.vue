<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import { getJson } from '@admin/http';
import DataTable from '@admin/Components/DataTable.vue';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MultiSelect from '@admin/Components/MultiSelect.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { SharedProps } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Option = { value: string; label: string };
type Scope = 'all' | 'products' | 'categories' | 'collections' | 'brands';

/** Form mã giảm giá: mã dùng chung hoặc mã mẫu (phát mã riêng cho khách), ưu đãi, điều kiện, lịch, hiển thị cho khách. */
const props = defineProps<{
    coupon: null | {
        id: number;
        kind: 'shared' | 'template';
        code: string | null;
        prefix: string | null;
        name: string;
        description: string;
        discount_type: 'percent' | 'amount';
        discount_value: number;
        max_discount: number | null;
        min_subtotal: number | null;
        scope_type: Scope;
        scope: Option[];
        exclude_on_sale: boolean;
        customer_groups: string[];
        usage_limit: number | null;
        usage_per_customer: number | null;
        valid_days: number | null;
        starts_at: string | null;
        ends_at: string | null;
        active: boolean;
        exclusive: boolean;
        public: boolean;
        lock_version: number;
        codes_total: number;
        issued_total: number;
        issued: Array<{ code: string; customer: string; used: boolean; expires_at: string | null }>;
        promotion_url: string | null;
    };
    groups: Array<{ value: string; id: number; label: string }>;
    limits: { max_percent: number };
    couponUrls: { index: string; save: string; delete: string | null; generate: string | null; issue: string | null; lookup: string; customers: string };
    can: { manage: boolean };
}>();

const symbol = usePage<SharedProps>().props.app.money.symbol;
const { form, save } = useAdminForm({
    kind: props.coupon?.kind ?? ('shared' as 'shared' | 'template'),
    code: props.coupon?.code ?? '',
    prefix: props.coupon?.prefix ?? '',
    name: props.coupon?.name ?? '',
    description: props.coupon?.description ?? '',
    discount_type: props.coupon?.discount_type ?? ('percent' as 'percent' | 'amount'),
    discount_value: props.coupon?.discount_value ?? 10,
    max_discount: props.coupon?.max_discount ?? (null as number | null),
    min_subtotal: props.coupon?.min_subtotal ?? (null as number | null),
    scope_type: props.coupon?.scope_type ?? ('all' as Scope),
    scope_values: (props.coupon?.scope ?? []).map((option) => option.value),
    exclude_on_sale: props.coupon?.exclude_on_sale ?? false,
    customer_groups: props.coupon?.customer_groups ?? ([] as string[]),
    usage_limit: props.coupon?.usage_limit ?? (null as number | null),
    usage_per_customer: props.coupon?.usage_per_customer ?? (1 as number | null),
    valid_days: props.coupon?.valid_days ?? (null as number | null),
    starts_at: props.coupon?.starts_at ?? (null as string | null),
    ends_at: props.coupon?.ends_at ?? (null as string | null),
    active: props.coupon?.active ?? true,
    exclusive: props.coupon?.exclusive ?? false,
    public: props.coupon?.public ?? false,
    lock_version: props.coupon?.lock_version ?? (null as number | null),
}, { lockVersion: () => props.coupon?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

const scopeOptions = computed<Array<{ value: Scope; label: string }>>(() =>
    (['all', 'products', 'categories', 'collections', 'brands'] as Scope[]).map((value) => ({ value, label: t(`vani-coupon-code::admin.form.scope_${value}`) })),
);
watch(() => form.scope_type, () => (form.scope_values = []));
const searchScope = async (term: string): Promise<Option[]> => (await getJson<{ data: Option[] }>(props.couponUrls.lookup, { scope: form.scope_type, q: term })).data;

function submit(): void {
    form.transform((data) => {
        const payload: Record<string, unknown> = { ...data, scope_values: data.scope_values.map(Number) };
        if (props.coupon) {
            delete payload.kind;
            delete payload.code;
        }
        return payload;
    });
    save(props.coupon ? 'put' : 'post', props.couponUrls.save);
}

async function destroy(): Promise<void> {
    if (props.couponUrls.delete && (await confirmAction({ title: t('vani-coupon-code::admin.form.delete_title'), message: t('vani-coupon-code::admin.form.delete_message'), confirmLabel: t('vani-coupon-code::admin.form.delete'), tone: 'danger' })) !== false) {
        router.delete(props.couponUrls.delete);
    }
}

// Sinh thêm mã dùng chung.
const codesForm = useForm({ prefix: '', count: 10 as number | null, usage_limit: 1 as number | null });
const generate = (): void => void (props.couponUrls.generate && codesForm.post(props.couponUrls.generate, { preserveScroll: true }));

// Phát mã riêng từ mã mẫu.
const issueForm = useForm({ target: 'customers' as 'customers' | 'segment', customer_ids: [] as string[], group_id: null as number | null, tag: '' });
const chosenCustomers = ref<Option[]>([]);
const searchCustomers = async (term: string): Promise<Option[]> => (await getJson<{ data: Option[] }>(props.couponUrls.customers, { q: term })).data;
const groupIdOptions = computed(() => props.groups.map((group) => ({ value: group.id, label: group.label })));
function issue(): void {
    if (!props.couponUrls.issue) return;
    issueForm.transform((data) => ({ ...data, customer_ids: data.customer_ids.map(Number) })).post(props.couponUrls.issue, {
        preserveScroll: true,
        onSuccess: () => issueForm.reset('customer_ids', 'tag'),
    });
}
const issuedColumns = [
    { key: 'code', label: t('vani-coupon-code::admin.form.code'), class: 'font-mono' },
    { key: 'customer', label: t('vani-coupon-code::admin.form.customer') },
    { key: 'status', label: t('vani-coupon-code::admin.index.status') },
    { key: 'expires_at', label: t('vani-coupon-code::admin.form.expires_at'), class: 'text-xs' },
];
</script>

<template>
    <div>
        <Head :title="coupon ? coupon.name : t('vani-coupon-code::admin.form.add')" />
        <PageHeader :title="coupon ? coupon.name : t('vani-coupon-code::admin.form.add')">
            <Link :href="couponUrls.index" :class="secondaryButton">{{ t('vani-coupon-code::admin.form.list') }}</Link>
            <Link v-if="coupon?.promotion_url" :href="coupon.promotion_url" :class="secondaryButton">{{ t('vani-coupon-code::admin.form.promotion') }}</Link>
            <button v-if="coupon && can.manage" type="button" :class="dangerButton" @click="destroy">{{ t('vani-coupon-code::admin.form.delete') }}</button>
        </PageHeader>

        <form class="grid max-w-3xl gap-4" @submit.prevent="submit">
            <fieldset :disabled="!can.manage" class="contents">
                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <FormField v-if="!coupon" class="sm:col-span-2" :label="t('vani-coupon-code::admin.form.kind')" :hint="t(`vani-coupon-code::admin.form.kind_${form.kind}_hint`)">
                        <Select v-model="form.kind" :options="[{ value: 'shared', label: t('vani-coupon-code::admin.form.kind_shared') }, { value: 'template', label: t('vani-coupon-code::admin.form.kind_template') }]" />
                    </FormField>
                    <FormField v-if="form.kind === 'shared'" :label="t('vani-coupon-code::admin.form.code') + ' *'" :error="errors.code" :hint="coupon ? t('vani-coupon-code::admin.form.code_fixed') : t('vani-coupon-code::admin.form.code_hint')">
                        <input v-model="form.code" :class="[inputClass, 'font-mono uppercase']" maxlength="32" :disabled="!!coupon" />
                    </FormField>
                    <FormField v-else :label="t('vani-coupon-code::admin.form.prefix') + ' *'" :error="errors.prefix" :hint="t('vani-coupon-code::admin.form.prefix_hint')">
                        <input v-model="form.prefix" :class="[inputClass, 'font-mono uppercase']" maxlength="12" />
                    </FormField>
                    <FormField :label="t('vani-coupon-code::admin.form.name') + ' *'" :error="errors.name" :hint="t('vani-coupon-code::admin.form.name_hint')">
                        <input v-model="form.name" :class="inputClass" maxlength="120" />
                    </FormField>
                    <FormField class="sm:col-span-2" :label="t('vani-coupon-code::admin.form.description')" :error="errors.description" :hint="t('vani-coupon-code::admin.form.description_hint')">
                        <textarea v-model="form.description" :class="inputClass" rows="2" maxlength="300" />
                    </FormField>
                </section>

                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <h2 class="font-medium sm:col-span-2">{{ t('vani-coupon-code::admin.form.offer') }}</h2>
                    <FormField :label="t('vani-coupon-code::admin.form.discount_type')">
                        <Select v-model="form.discount_type" :options="[{ value: 'percent', label: t('vani-coupon-code::admin.form.percent') }, { value: 'amount', label: t('vani-coupon-code::admin.form.amount', { symbol }) }]" />
                    </FormField>
                    <FormField :label="(form.discount_type === 'percent' ? t('vani-coupon-code::admin.form.percent_value') : t('vani-coupon-code::admin.form.amount_value', { symbol })) + ' *'" :error="errors.discount_value">
                        <input v-model.number="form.discount_value" type="number" min="1" :max="form.discount_type === 'percent' ? limits.max_percent : undefined" :class="inputClass" />
                    </FormField>
                    <FormField v-if="form.discount_type === 'percent'" :label="t('vani-coupon-code::admin.form.max_discount', { symbol })" :error="errors.max_discount" :hint="t('vani-coupon-code::admin.form.empty_unlimited')">
                        <input v-model.number="form.max_discount" type="number" min="1" :class="inputClass" />
                    </FormField>
                    <FormField :label="t('vani-coupon-code::admin.form.min_subtotal', { symbol })" :error="errors.min_subtotal" :hint="t('vani-coupon-code::admin.form.min_subtotal_hint')">
                        <input v-model.number="form.min_subtotal" type="number" min="1" :class="inputClass" />
                    </FormField>
                </section>

                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <h2 class="font-medium sm:col-span-2">{{ t('vani-coupon-code::admin.form.conditions') }}</h2>
                    <FormField :label="t('vani-coupon-code::admin.form.scope')">
                        <Select v-model="form.scope_type" :options="scopeOptions" />
                    </FormField>
                    <FormField v-if="form.scope_type !== 'all'" :label="t(`vani-coupon-code::admin.form.scope_${form.scope_type}`) + ' *'" :error="errors.scope_values">
                        <MultiSelect :key="form.scope_type" v-model="form.scope_values" :search="searchScope" :selected-options="coupon?.scope ?? []" :disabled="!can.manage" />
                    </FormField>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.exclude_on_sale" type="checkbox" /> {{ t('vani-coupon-code::admin.form.exclude_on_sale') }}</label>
                    <FormField v-if="groups.length" class="sm:col-span-2" :label="t('vani-coupon-code::admin.form.customer_groups')" :hint="t('vani-coupon-code::admin.form.customer_groups_hint')" :error="errors.customer_groups">
                        <MultiSelect v-model="form.customer_groups" :options="groups" :disabled="!can.manage" />
                    </FormField>
                    <FormField :label="t('vani-coupon-code::admin.form.usage_limit')" :hint="t('vani-coupon-code::admin.form.empty_unlimited')" :error="errors.usage_limit">
                        <input v-model.number="form.usage_limit" type="number" min="1" :class="inputClass" />
                    </FormField>
                    <FormField :label="t('vani-coupon-code::admin.form.usage_per_customer')" :hint="t('vani-coupon-code::admin.form.usage_per_customer_hint')" :error="errors.usage_per_customer">
                        <input v-model.number="form.usage_per_customer" type="number" min="1" :class="inputClass" />
                    </FormField>
                </section>

                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <h2 class="font-medium sm:col-span-2">{{ t('vani-coupon-code::admin.form.schedule') }}</h2>
                    <FormField :label="t('vani-coupon-code::admin.form.starts_at')" :error="errors.starts_at"><DateInput v-model="form.starts_at" with-time :disabled="!can.manage" /></FormField>
                    <FormField :label="t('vani-coupon-code::admin.form.ends_at')" :error="errors.ends_at"><DateInput v-model="form.ends_at" with-time :disabled="!can.manage" /></FormField>
                    <FormField v-if="form.kind === 'template'" :label="t('vani-coupon-code::admin.form.valid_days')" :hint="t('vani-coupon-code::admin.form.valid_days_hint')" :error="errors.valid_days">
                        <input v-model.number="form.valid_days" type="number" min="1" max="365" :class="inputClass" />
                    </FormField>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.active" type="checkbox" /> {{ t('vani-coupon-code::admin.form.active') }}</label>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.exclusive" type="checkbox" /> {{ t('vani-coupon-code::admin.form.exclusive') }}</label>
                    <label v-if="form.kind === 'shared'" class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.public" type="checkbox" /> {{ t('vani-coupon-code::admin.form.public') }}</label>
                </section>

                <div v-if="can.manage"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-coupon-code::admin.form.save') }}</button></div>
            </fieldset>
        </form>

        <section v-if="coupon && coupon.kind === 'shared' && can.manage" class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-1 font-medium">{{ t('vani-coupon-code::admin.codes.title', { count: coupon.codes_total }) }}</h2>
            <p class="mb-3 text-sm text-slate-500">{{ t('vani-coupon-code::admin.codes.hint') }}</p>
            <form class="grid gap-3 sm:grid-cols-4" @submit.prevent="generate">
                <input v-model="codesForm.prefix" :class="[inputClass, 'font-mono uppercase']" maxlength="12" :placeholder="t('vani-coupon-code::admin.form.prefix')" :aria-label="t('vani-coupon-code::admin.form.prefix')" />
                <input v-model.number="codesForm.count" type="number" min="1" max="5000" :class="inputClass" :aria-label="t('vani-coupon-code::admin.codes.count')" />
                <input v-model.number="codesForm.usage_limit" type="number" min="1" :class="inputClass" :aria-label="t('vani-coupon-code::admin.codes.usage_limit')" :placeholder="t('vani-coupon-code::admin.codes.usage_limit')" />
                <button type="submit" :class="primaryButton" :disabled="codesForm.processing">{{ t('vani-coupon-code::admin.codes.generate') }}</button>
                <p v-for="(error, key) in codesForm.errors" :key="key" class="text-sm text-red-600 sm:col-span-4">{{ error }}</p>
            </form>
        </section>

        <section v-if="coupon && coupon.kind === 'template'" class="mt-6 max-w-3xl rounded-lg border border-slate-200 bg-white p-5" data-coupon-issue>
            <h2 class="mb-1 font-medium">{{ t('vani-coupon-code::admin.issue.title', { count: coupon.issued_total }) }}</h2>
            <p class="mb-3 text-sm text-slate-500">{{ t('vani-coupon-code::admin.issue.hint') }}</p>
            <form v-if="can.manage" class="mb-4 grid gap-3" @submit.prevent="issue">
                <Select v-model="issueForm.target" :options="[{ value: 'customers', label: t('vani-coupon-code::admin.issue.customers') }, { value: 'segment', label: t('vani-coupon-code::admin.issue.segment') }]" />
                <MultiSelect v-if="issueForm.target === 'customers'" v-model="issueForm.customer_ids" :search="searchCustomers" :selected-options="chosenCustomers" :placeholder="t('vani-coupon-code::admin.issue.search_customers')" />
                <div v-else class="grid gap-3 sm:grid-cols-2">
                    <Select v-model="issueForm.group_id" :options="groupIdOptions" :empty-label="t('vani-coupon-code::admin.issue.any_group')" :empty-value="null" />
                    <input v-model="issueForm.tag" :class="inputClass" maxlength="40" :placeholder="t('vani-coupon-code::admin.issue.tag')" :aria-label="t('vani-coupon-code::admin.issue.tag')" />
                </div>
                <div><button type="submit" :class="primaryButton" :disabled="issueForm.processing">{{ t('vani-coupon-code::admin.issue.submit') }}</button></div>
                <p v-for="(error, key) in issueForm.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
            </form>
            <DataTable :columns="issuedColumns" :rows="coupon.issued" :empty-text="t('vani-coupon-code::admin.issue.empty')">
                <template #cell-status="{ row }">{{ row.used ? t('vani-coupon-code::admin.issue.used') : t('vani-coupon-code::admin.issue.unused') }}</template>
                <template #cell-expires_at="{ row }">{{ row.expires_at ?? '—' }}</template>
            </DataTable>
        </section>
    </div>
</template>
