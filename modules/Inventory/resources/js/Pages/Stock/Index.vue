<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Level = { on_hand: number; reserved: number; safety_stock: number; available: number };

const props = defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
    locationsUrl: string | null;
    styleCode: string;
    styleName: string | null;
    locations: Array<{ id: number; code: string; name: string; external: boolean }>;
    variants: Array<{ id: number; sku: string; color_code: string; size_code: string; status: string; levels: Level[] }>;
    canAdjust: boolean;
}>();

const search = ref(props.styleCode);
const actionLabels: Record<string, string> = { adjust: 'Cộng/trừ tồn', count: 'Kiểm kê (đặt số đếm)', safety: 'Tồn an toàn' };
const editing = ref<null | { sku: string; location: string }>(null);
const form = useForm({ location_id: 0, variant_id: 0, action: 'adjust', quantity: 0, reason: '' });

function find(): void {
    router.get(props.baseUrl, { style: search.value.trim() }, { preserveState: true });
}

function open(variant: (typeof props.variants)[number], index: number): void {
    const location = props.locations[index];
    form.reset();
    form.clearErrors();
    form.location_id = location.id;
    form.variant_id = variant.id;
    editing.value = { sku: variant.sku, location: location.code };
}

function submit(): void {
    form.post(props.baseUrl, { preserveScroll: true, onSuccess: () => (editing.value = null) });
}
</script>

<template>
    <Head :title="`Tồn kho · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Tồn kho theo sản phẩm" subtitle="Có thể bán = tồn − đang giữ − tồn an toàn.">
        <Link v-if="locationsUrl" :href="locationsUrl" :class="secondaryButton">Kho & cửa hàng</Link>
    </PageHeader>

    <form class="mb-4 flex max-w-lg gap-2" @submit.prevent="find">
        <input v-model="search" :class="inputClass" placeholder="Mã sản phẩm, vd. LM-DRESS-01" />
        <button type="submit" :class="primaryButton">Xem</button>
    </form>

    <p v-if="styleCode && !variants.length" class="text-sm text-slate-500">Không tìm thấy biến thể nào cho mã {{ styleCode }}.</p>
    <p v-else-if="variants.length && !locations.length" class="text-sm text-slate-500">Brand chưa được gán vào location nào.</p>

    <div v-if="variants.length && locations.length" class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <p v-if="styleName" class="border-b border-slate-100 px-4 py-2 font-medium">{{ styleName }}</p>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">SKU</th>
                    <th v-for="location in locations" :key="location.id" class="px-4 py-2">
                        {{ location.code }}<span v-if="location.external" class="ml-1 text-xs text-amber-600">(đồng bộ)</span>
                    </th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="variant in variants" :key="variant.id" class="border-t border-slate-100 align-top">
                    <td class="px-4 py-2">
                        <div class="font-mono">{{ variant.sku }}</div>
                        <div class="text-xs text-slate-400">{{ variant.color_code }} / {{ variant.size_code }}<span v-if="variant.status !== 'active'"> · tắt</span></div>
                    </td>
                    <td v-for="(level, index) in variant.levels" :key="index" class="px-4 py-2">
                        <div class="font-semibold" :class="level.available > 0 ? 'text-emerald-700' : 'text-slate-400'">{{ level.available }}</div>
                        <div class="text-xs text-slate-500">tồn {{ level.on_hand }} · giữ {{ level.reserved }} · an toàn {{ level.safety_stock }}</div>
                        <button v-if="canAdjust" type="button" class="text-xs text-indigo-600 hover:underline" @click="open(variant, index)">Điều chỉnh</button>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <Link :href="`${baseUrl.replace(/\/stock$/, '/movements')}?variant=${variant.id}`" class="text-xs text-indigo-600 hover:underline">Lịch sử</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <form v-if="editing" class="mt-4 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
        <p class="font-medium">{{ editing.sku }} tại {{ editing.location }}</p>
        <select v-model="form.action" :class="inputClass">
            <option v-for="(label, value) in actionLabels" :key="value" :value="value">{{ label }}</option>
        </select>
        <input v-model.number="form.quantity" :class="inputClass" type="number" :placeholder="form.action === 'adjust' ? 'Ví dụ: 10 hoặc -2' : 'Số lượng'" />
        <input v-if="form.action !== 'safety'" v-model="form.reason" :class="inputClass" placeholder="Lý do (bắt buộc)" />
        <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
            <button type="button" :class="secondaryButton" @click="editing = null">Huỷ</button>
        </div>
    </form>
</template>
