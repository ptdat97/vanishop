<script setup lang="ts">
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
import { computed, ref, watch } from 'vue';

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
const sku = ref('');
const provinces = ref<Option[]>([]);
const wards = ref<Option[]>([]);

getJson<{ data: Array<{ code: string; name: string }> }>(props.urls.provinces)
    .then((response) => (provinces.value = response.data.map((province) => ({ value: province.code, label: province.name }))))
    .catch(() => (provinces.value = []));

watch(
    () => form.province_code,
    async (code) => {
        form.province_name = provinces.value.find((item) => item.value === code)?.label ?? '';
        form.ward_code = '';
        wards.value = [];
        if (!code) return;
        const response = await getJson<{ data: Array<{ code: string; name: string }> }>(`${props.urls.provinces}/${code}/wards`).catch(() => ({ data: [] }));
        wards.value = response.data.map((ward) => ({ value: ward.code, label: ward.name }));
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

const priceOf = (variantId: number) => quote.value?.lines.find((line) => line.variant_id === variantId);
const lineColumns = [
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'name', label: 'Sản phẩm' },
    { key: 'quantity', label: 'Số lượng', align: 'right' as const },
    { key: 'price', label: 'Đơn giá', align: 'right' as const, class: 'tabular-nums' },
    { key: 'subtotal', label: 'Thành tiền', align: 'right' as const, class: 'tabular-nums' },
    { key: 'remove', label: '' },
];

let timer: ReturnType<typeof setTimeout> | undefined;
function recalc(): void {
    clearTimeout(timer);
    timer = setTimeout(runQuote, 300);
}

async function runQuote(): Promise<void> {
    if (!form.lines.length) {
        quote.value = null;
        return;
    }
    quoting.value = true;
    quoteError.value = '';
    try {
        const result = await postJson<Quote>(props.urls.quote, { ...form.data() });
        quote.value = result;
        form.cart_id = result.cart.id;
        form.cart_token = result.cart.token;
        form.expected_total = result.total_amount;
        if (!form.shipping_method && result.shipping_options[0]) form.shipping_method = result.shipping_options[0].code;
        if (!form.payment_method && result.payment_methods[0]) form.payment_method = result.payment_methods[0].code;
    } catch (error) {
        quoteError.value = error instanceof HttpError ? error.message : 'Không tính được tiền.';
    } finally {
        quoting.value = false;
    }
}

watch(() => [form.ward_code, form.shipping_method, form.payment_method], recalc);
const canSubmit = computed(() => quote.value !== null && !quoting.value && form.lines.length > 0 && form.cart_id !== '');

function submit(): void {
    save('post', props.urls.store);
}
</script>

<template>
    <Head title="Tạo đơn" />
    <PageHeader title="Tạo đơn" subtitle="Đơn nhận qua điện thoại, Zalo, Facebook… Cùng giá, khuyến mãi và giữ hàng như đơn web.">
        <Link :href="urls.orders" :class="secondaryButton">Danh sách đơn</Link>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <section class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-2 font-medium">Sản phẩm</h2>
                <Combobox v-model="sku" :search="searchVariants" placeholder="Gõ SKU hoặc tên sản phẩm" aria-label="Thêm sản phẩm" empty-text="Không tìm thấy SKU đang bán" data-add-variant @select="addVariant" />
            </div>
            <DataTable :columns="lineColumns" :rows="form.lines" :row-key="(line) => line.variant_id" :row-label="(line) => line.sku" empty-text="Chưa có sản phẩm nào." data-order-lines>
                <template #cell-quantity="{ row }">
                    <input v-model.number="row.quantity" :class="inputClass" class="w-20 text-right tabular-nums" type="number" min="1" max="1000" required :aria-label="`Số lượng ${row.sku}`" @change="recalc" />
                </template>
                <template #cell-price="{ row }">{{ priceOf(row.variant_id)?.unit_price ?? '—' }}</template>
                <template #cell-subtotal="{ row }">{{ priceOf(row.variant_id)?.subtotal ?? '—' }}</template>
                <template #cell-remove="{ row }">
                    <button type="button" class="text-red-600 hover:underline" @click="form.lines.splice(form.lines.indexOf(row), 1); recalc()">Bỏ</button>
                </template>
            </DataTable>

            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2">
                <h2 class="font-medium sm:col-span-2">Người nhận</h2>
                <FormField label="Họ tên" :error="form.errors.full_name"><input v-model="form.full_name" :class="inputClass" maxlength="120" required /></FormField>
                <FormField label="Số điện thoại" :error="form.errors.phone"><input v-model="form.phone" :class="inputClass" type="tel" maxlength="20" required /></FormField>
                <FormField label="Email (không bắt buộc)" :error="form.errors.email"><input v-model="form.email" :class="inputClass" type="email" maxlength="190" /></FormField>
                <div class="hidden sm:block" />
                <FormField label="Tỉnh/thành" :error="form.errors.province_code"><Combobox v-model="form.province_code" :options="provinces" placeholder="Chọn tỉnh/thành" aria-label="Tỉnh/thành" /></FormField>
                <FormField label="Phường/xã" :error="form.errors.ward_code"><Combobox v-model="form.ward_code" :options="wards" :disabled="!wards.length" placeholder="Chọn phường/xã" aria-label="Phường/xã" /></FormField>
                <FormField label="Số nhà, đường" :error="form.errors.street_line" class="sm:col-span-2"><input v-model="form.street_line" :class="inputClass" maxlength="255" required @change="recalc" /></FormField>
                <FormField label="Ghi chú đơn" :error="form.errors.note" class="sm:col-span-2"><textarea v-model="form.note" :class="inputClass" rows="2" maxlength="1000"></textarea></FormField>
            </div>
        </section>

        <aside class="space-y-3 self-start rounded-lg border border-slate-200 bg-white p-4 text-sm" data-order-summary>
            <FormField label="Phí giao" :error="form.errors.shipping_method">
                <Select v-model="form.shipping_method" :options="(quote?.shipping_options ?? []).map((option) => ({ value: option.code, label: `${option.label} — ${option.fee}` }))" empty-label="— Tính tiền để chọn —" aria-label="Phí giao" />
            </FormField>
            <FormField label="Thanh toán" :error="form.errors.payment_method">
                <Select v-model="form.payment_method" :options="(quote?.payment_methods ?? []).map((method) => ({ value: method.code, label: method.label }))" empty-label="— Tính tiền để chọn —" aria-label="Thanh toán" />
            </FormField>
            <dl v-if="quote" class="space-y-1 border-t border-slate-100 pt-3">
                <div class="flex justify-between"><dt>Tạm tính</dt><dd class="tabular-nums">{{ quote.subtotal }}</dd></div>
                <div class="flex justify-between"><dt>Giảm giá</dt><dd class="tabular-nums">{{ quote.discount }}</dd></div>
                <div class="flex justify-between"><dt>Phí giao</dt><dd class="tabular-nums">{{ quote.shipping }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>Tổng</dt><dd class="tabular-nums" data-order-total>{{ quote.total }}</dd></div>
            </dl>
            <p v-if="quoteError" class="text-red-600" role="alert">{{ quoteError }}</p>
            <p v-for="(error, key) in form.errors" :key="key" class="text-red-600">{{ error }}</p>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="button" :class="secondaryButton" :disabled="quoting || !form.lines.length" data-quote @click="runQuote">{{ quoting ? 'Đang tính…' : 'Tính tiền' }}</button>
                <button type="submit" :class="primaryButton" :disabled="!canSubmit || form.processing" data-place>Tạo đơn</button>
            </div>
        </aside>
    </form>
</template>
