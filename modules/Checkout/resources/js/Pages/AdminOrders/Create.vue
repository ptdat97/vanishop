<script setup lang="ts">
import { t } from '@admin/i18n';
import Combobox from '@admin/Components/Combobox.vue';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { useAdminForm } from '@admin/forms';
import { HttpError, getJson, postJson } from '@admin/http';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { Option } from '@admin/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

type Line = { variant_id: number; sku: string; name: string; quantity: number };
type Quote = {
    cart: { id: string; token: string };
    lines: Array<{ variant_id: number; unit_price: string; subtotal: string }>;
    shipping_options: Array<{ code: string; label: string; fee: string }>;
    payment_methods: Array<{ code: string; label: string }>;
    subtotal: string; discount: string; shipping: string; total: string; total_amount: number; ready: boolean;
};

/**
 * Tạo đơn trong Admin (A8) — đơn qua điện thoại/Zalo/Facebook: cùng luật giá, khuyến mãi, phí giao, giữ hàng như checkout web
 * (nguồn đơn "admin"). Mỗi lần đổi dòng/địa chỉ/phí giao thì tính lại; "Tạo đơn" gửi kèm tổng đã thấy (đổi giá giữa chừng → báo).
 */
const props = defineProps<{
    idempotencyKey: string;
    urls: { variants: string; quote: string; store: string; orders: string; provinces: string };
}>();

const { form, save } = useAdminForm({
    lines: [] as Line[],
    full_name: '', phone: '', email: '',
    province_code: '', province_name: '', ward_code: '', ward_name: '', street_line: '',
    shipping_method: '', payment_method: '', note: '',
    cart_id: '', cart_token: '', expected_total: 0, idempotency_key: props.idempotencyKey,
});
const quote = ref<Quote | null>(null);
const quoteError = ref('');
const quoting = ref(false);
const needsQuote = ref(false);
const sku = ref('');
const pasted = ref('');
const pasting = ref(false);
const pasteError = ref('');
const provinces = ref<Option[]>([]);
const wards = ref<Option[]>([]);

getJson<{ data: Array<{ code: string; name: string }> }>(props.urls.provinces)
    .then((response) => (provinces.value = response.data.map((province) => ({ value: province.code, label: province.name }))))
    .catch(() => (provinces.value = []));

watch(
    () => form.province_code,
    async (code, _old, onCleanup) => {
        let cancelled = false;
        onCleanup(() => (cancelled = true));
        form.province_name = provinces.value.find((item) => item.value === code)?.label ?? '';
        form.ward_code = '';
        wards.value = [];
        if (!code) return;
        const response = await getJson<{ data: Array<{ code: string; name: string }> }>(`${props.urls.provinces}/${code}/wards`).catch(() => ({ data: [] }));
        if (!cancelled) wards.value = response.data.map((ward) => ({ value: ward.code, label: ward.name }));
    },
);
watch(() => form.ward_code, (code) => (form.ward_name = wards.value.find((item) => item.value === code)?.label ?? ''));

async function searchVariants(term: string): Promise<Option[]> {
    if (term.trim().length < 2) return [];
    const response = await getJson<{ data: Array<{ variant_id: number; sku: string; name: string }> }>(props.urls.variants, { q: term });
    return response.data.map((item) => ({ value: `${item.variant_id}|${item.sku}|${item.name}`, label: `${item.sku} — ${item.name}` }));
}

function addVariant(option: Option): void {
    const [id, code, ...name] = String(option.value).split('|');
    const existing = form.lines.find((line) => line.variant_id === Number(id));
    if (existing) existing.quantity++;
    else form.lines.push({ variant_id: Number(id), sku: code, name: name.join('|'), quantity: 1 });
    sku.value = '';
    recalc();
}

async function pasteVariants(): Promise<void> {
    if (pasting.value) return;
    pasteError.value = '';
    const quantities = new Map<string, number>();
    for (const [index, line] of pasted.value.trim().split(/\r?\n/).entries()) {
        if (!line.trim()) continue;
        const [code, quantity = '1', ...extra] = line.trim().split(/[\t,; ]+/);
        const total = (quantities.get(code) ?? 0) + Number(quantity);
        if (extra.length || !/^\d+$/.test(quantity) || Number(quantity) < 1 || total > 1000) {
            pasteError.value = t('checkout::admin.admin_orders_create.invalid_paste_row', { row: index + 1 });
            return;
        }
        quantities.set(code, total);
    }
    pasting.value = true;
    try {
        const { data } = await postJson<{ data: Omit<Line, 'quantity'>[] }>(props.urls.variants, { skus: [...quantities.keys()] });
        const lines = new Map(form.lines.map((line) => [line.variant_id, { ...line }]));
        for (const { variant_id, sku, name } of data) {
            const key = quantities.has(sku) ? sku : [...quantities.keys()].find((code) => code.toUpperCase() === sku.toUpperCase());
            if (key === undefined) continue;
            const quantity = (lines.get(variant_id)?.quantity ?? 0) + (quantities.get(key) ?? 0);
            if (quantity > 1000) {
                pasteError.value = t('checkout::admin.admin_orders_create.sku_quantity_limit', { sku });
                return;
            }
            lines.set(variant_id, { variant_id, sku, name, quantity });
            quantities.delete(key);
        }
        if (quantities.size || lines.size > 100) {
            pasteError.value = quantities.size ? t('checkout::admin.admin_orders_create.unknown_skus', { skus: [...quantities.keys()].join(', ') }) : t('checkout::admin.admin_orders_create.too_many_skus');
            return;
        }
        form.lines = [...lines.values()];
        pasted.value = '';
        recalc();
    } catch (error) {
        pasteError.value = error instanceof HttpError ? error.message : t('checkout::admin.admin_orders_create.could_not_read_skus');
    } finally {
        pasting.value = false;
    }
}

const priceOf = (variantId: number) => quote.value?.lines.find((line) => line.variant_id === variantId);
const lineColumns = [
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'name', label: t('checkout::admin.admin_orders_create.products') },
    { key: 'quantity', label: t('checkout::admin.admin_orders_create.quantity'), align: 'right' as const },
    { key: 'price', label: t('checkout::admin.admin_orders_create.unit_price'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'subtotal', label: t('checkout::admin.admin_orders_create.amount'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'remove', label: '' },
];

let timer: ReturnType<typeof setTimeout> | undefined;
let unmounted = false;
onUnmounted(() => {
    unmounted = true;
    clearTimeout(timer);
});
function recalc(): void {
    needsQuote.value = true;
    clearTimeout(timer);
    timer = setTimeout(runQuote, 300);
}

async function runQuote(): Promise<void> {
    clearTimeout(timer);
    if (quoting.value || form.processing) return;
    needsQuote.value = false;
    quoteError.value = '';
    if (!form.lines.length) {
        quote.value = null;
        return;
    }
    quoting.value = true;
    try {
        const result = await postJson<Quote>(props.urls.quote, { ...form.data() });
        form.cart_id = result.cart.id;
        form.cart_token = result.cart.token;
        if (needsQuote.value) return;
        quote.value = result;
        form.expected_total = result.total_amount;
        if (!form.shipping_method && result.shipping_options[0]) form.shipping_method = result.shipping_options[0].code;
        if (!form.payment_method && result.payment_methods[0]) form.payment_method = result.payment_methods[0].code;
    } catch (error) {
        if (!needsQuote.value) quoteError.value = error instanceof HttpError ? error.message : t('checkout::admin.admin_orders_create.could_not_calculate_total');
    } finally {
        quoting.value = false;
        if (needsQuote.value && !unmounted) recalc();
    }
}

watch(() => [form.province_code, form.ward_code, form.shipping_method, form.payment_method, form.street_line], recalc, { flush: 'sync' });
const canSubmit = computed(() => quote.value?.ready && !pasting.value && !quoting.value && !needsQuote.value && !quoteError.value && form.lines.length > 0 && form.cart_id !== '');

function submit(): void {
    if (canSubmit.value) save('post', props.urls.store);
}
</script>

<template>
    <div>
    <Head :title="t('checkout::admin.admin_orders_create.create_order')" />
    <PageHeader :title="t('checkout::admin.admin_orders_create.create_order')" :subtitle="t('checkout::admin.admin_orders_create.orders_received_phone_zalo_facebook')">
        <Link :href="urls.orders" :class="secondaryButton">{{ t('checkout::admin.admin_orders_create.order_list') }}</Link>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <section class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-2 font-medium">{{ t('checkout::admin.admin_orders_create.products') }}</h2>
                <Combobox v-model="sku" :search="searchVariants" :placeholder="t('checkout::admin.admin_orders_create.type_sku_product_name')" :aria-label="t('checkout::admin.admin_orders_create.add_product')" :empty-text="t('checkout::admin.admin_orders_create.no_sku_sale_found')" data-add-variant @select="addVariant" />
                <details class="mt-3 text-sm">
                    <summary class="cursor-pointer font-medium">{{ t('checkout::admin.admin_orders_create.paste_skus') }}</summary>
                    <FormField class="mt-2" :label="t('checkout::admin.admin_orders_create.paste_hint')" :error="pasteError">
                        <textarea v-model="pasted" :class="inputClass" rows="3" maxlength="12000" :disabled="pasting" data-paste-skus></textarea>
                    </FormField>
                    <button type="button" :class="secondaryButton" class="mt-2" :disabled="pasting || !pasted.trim()" data-add-pasted-skus @click="pasteVariants">{{ t('checkout::admin.admin_orders_create.add_pasted_skus') }}</button>
                </details>
            </div>
            <DataTable :columns="lineColumns" :rows="form.lines" :row-key="(line) => line.variant_id" :row-label="(line) => line.sku" :empty-text="t('checkout::admin.admin_orders_create.no_products_yet')" data-order-lines>
                <template #cell-quantity="{ row }">
                    <input v-model.number="row.quantity" :class="inputClass" class="w-20 text-right tabular-nums" type="number" min="1" max="1000" required :aria-label="t('checkout::admin.admin_orders_create.quantity_sku', { sku: row.sku })" @input="recalc" />
                </template>
                <template #cell-price="{ row }">{{ priceOf(row.variant_id)?.unit_price ?? '—' }}</template>
                <template #cell-subtotal="{ row }">{{ priceOf(row.variant_id)?.subtotal ?? '—' }}</template>
                <template #cell-remove="{ row }">
                    <button type="button" class="text-red-600 hover:underline" @click="form.lines.splice(form.lines.indexOf(row), 1); recalc()">{{ t('checkout::admin.admin_orders_create.remove') }}</button>
                </template>
            </DataTable>

            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2">
                <h2 class="font-medium sm:col-span-2">{{ t('checkout::admin.admin_orders_create.recipient') }}</h2>
                <FormField :label="t('checkout::admin.admin_orders_create.full_name')" :error="form.errors.full_name"><input v-model="form.full_name" :class="inputClass" maxlength="120" required /></FormField>
                <FormField :label="t('checkout::admin.admin_orders_create.phone_number')" :error="form.errors.phone"><input v-model="form.phone" :class="inputClass" type="tel" maxlength="20" required /></FormField>
                <FormField :label="t('checkout::admin.admin_orders_create.email_optional')" :error="form.errors.email"><input v-model="form.email" :class="inputClass" type="email" maxlength="190" /></FormField>
                <div class="hidden sm:block" />
                <FormField :label="t('checkout::admin.admin_orders_create.province_city')" :error="form.errors.province_code"><Combobox v-model="form.province_code" :options="provinces" :placeholder="t('checkout::admin.admin_orders_create.choose_province_city')" :aria-label="t('checkout::admin.admin_orders_create.province_city')" /></FormField>
                <FormField :label="t('checkout::admin.admin_orders_create.ward')" :error="form.errors.ward_code"><Combobox v-model="form.ward_code" :options="wards" :disabled="!wards.length" :placeholder="t('checkout::admin.admin_orders_create.choose_ward')" :aria-label="t('checkout::admin.admin_orders_create.ward')" /></FormField>
                <FormField :label="t('checkout::admin.admin_orders_create.house_number_street')" :error="form.errors.street_line" class="sm:col-span-2"><input v-model="form.street_line" :class="inputClass" maxlength="255" required @change="recalc" /></FormField>
                <FormField :label="t('checkout::admin.admin_orders_create.order_note')" :error="form.errors.note" class="sm:col-span-2"><textarea v-model="form.note" :class="inputClass" rows="2" maxlength="1000"></textarea></FormField>
            </div>
        </section>

        <aside class="space-y-3 self-start rounded-lg border border-slate-200 bg-white p-4 text-sm" data-order-summary>
            <FormField :label="t('checkout::admin.admin_orders_create.shipping_fee')" :error="form.errors.shipping_method">
                <Select v-model="form.shipping_method" :options="(quote?.shipping_options ?? []).map((option) => ({ value: option.code, label: `${option.label} — ${option.fee}` }))" :empty-label="t('checkout::admin.admin_orders_create.calculate_choose')" :aria-label="t('checkout::admin.admin_orders_create.shipping_fee')" />
            </FormField>
            <FormField :label="t('checkout::admin.admin_orders_create.payment')" :error="form.errors.payment_method">
                <Select v-model="form.payment_method" :options="(quote?.payment_methods ?? []).map((method) => ({ value: method.code, label: method.label }))" :empty-label="t('checkout::admin.admin_orders_create.calculate_choose')" :aria-label="t('checkout::admin.admin_orders_create.payment')" />
            </FormField>
            <dl v-if="quote" class="space-y-1 border-t border-slate-100 pt-3">
                <div class="flex justify-between"><dt>{{ t('checkout::admin.admin_orders_create.subtotal') }}</dt><dd class="tabular-nums">{{ quote.subtotal }}</dd></div>
                <div class="flex justify-between"><dt>{{ t('checkout::admin.admin_orders_create.discount') }}</dt><dd class="tabular-nums">{{ quote.discount }}</dd></div>
                <div class="flex justify-between"><dt>{{ t('checkout::admin.admin_orders_create.shipping_fee') }}</dt><dd class="tabular-nums">{{ quote.shipping }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>{{ t('checkout::admin.admin_orders_create.total') }}</dt><dd class="tabular-nums" data-order-total>{{ quote.total }}</dd></div>
            </dl>
            <p v-if="quoteError" class="text-red-600" role="alert">{{ quoteError }}</p>
            <p v-for="(error, key) in form.errors" :key="key" class="text-red-600">{{ error }}</p>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="button" :class="secondaryButton" :disabled="quoting || !form.lines.length" data-quote @click="runQuote">{{ quoting ? t('checkout::admin.admin_orders_create.calculating') : t('checkout::admin.admin_orders_create.calculate') }}</button>
                <button type="submit" :class="primaryButton" :disabled="!canSubmit || form.processing" data-place>{{ t('checkout::admin.admin_orders_create.create_order') }}</button>
            </div>
        </aside>
    </form>
    </div>
</template>
