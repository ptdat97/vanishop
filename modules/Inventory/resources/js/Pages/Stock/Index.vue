<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { InventoryLabels } from '../../labels';

type Level = { on_hand: number; reserved: number; safety_stock: number; available: number };

const props = defineProps<{
    baseUrl: string;
    labels: InventoryLabels;
    locationsUrl: string | null;
    transfersUrl: string | null;
    styleCode: string;
    styleName: string | null;
    locations: Array<{ id: number; code: string; name: string; external: boolean }>;
    variants: Array<{ id: number; sku: string; color_code: string; size_code: string; status: string; levels: Level[] }>;
    canAdjust: boolean;
}>();

const search = ref(props.styleCode);
const columns = computed(() => [
    { key: 'sku', label: 'SKU' },
    ...props.locations.map((location, index) => ({ key: `location-${index}`, label: location.code })),
    { key: 'actions', label: '', align: 'right' as const },
]);
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
    <Head title="Tồn kho" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader title="Tồn kho theo sản phẩm" subtitle="Có thể bán = tồn − đang giữ − tồn an toàn.">
        <Link v-if="transfersUrl" :href="transfersUrl" :class="secondaryButton">Chuyển kho</Link>
        <Link v-if="locationsUrl" :href="locationsUrl" :class="secondaryButton">Kho & cửa hàng</Link>
    </PageHeader>

    <form class="mb-4 flex max-w-lg gap-2" @submit.prevent="find">
        <input v-model="search" :class="inputClass" placeholder="Mã sản phẩm, vd. LM-DRESS-01" />
        <button type="submit" :class="primaryButton">Xem</button>
    </form>

    <p v-if="styleCode && !variants.length" class="text-sm text-slate-500">Không tìm thấy biến thể nào cho mã {{ styleCode }}.</p>
    <p v-else-if="variants.length && !locations.length" class="text-sm text-slate-500">Chưa có kho/cửa hàng nào.</p>

    <div v-if="variants.length && locations.length">
        <p v-if="styleName" class="mb-2 font-medium">{{ styleName }}</p>
        <DataTable :columns="columns" :rows="variants" :row-class="() => 'align-top'" :row-label="(variant) => variant.sku">
            <template v-for="(location, index) in locations" :key="location.id" #[`head-location-${index}`]>
                {{ location.code }}<span v-if="location.external" class="ml-1 text-xs text-amber-600">(đồng bộ)</span>
            </template>
            <template #cell-sku="{ row }">
                <div class="font-mono">{{ row.sku }}</div>
                <div class="text-xs text-slate-400">{{ row.color_code }} / {{ row.size_code }}<span v-if="row.status !== 'active'"> · tắt</span></div>
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`cell-location-${index}`]="{ row }">
                <div class="font-semibold" :class="row.levels[index].available > 0 ? 'text-emerald-700' : 'text-slate-400'">{{ row.levels[index].available }}</div>
                <div class="text-xs text-slate-500">tồn {{ row.levels[index].on_hand }} · giữ {{ row.levels[index].reserved }} · an toàn {{ row.levels[index].safety_stock }}</div>
                <button v-if="canAdjust" type="button" class="text-xs text-indigo-600 hover:underline" @click="open(row, index)">Điều chỉnh</button>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`${baseUrl.replace(/\/stock$/, '/movements')}?variant=${row.id}`" class="text-xs text-indigo-600 hover:underline">Lịch sử</Link>
            </template>
        </DataTable>
    </div>

    <form v-if="editing" class="mt-4 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
        <p class="font-medium">{{ editing.sku }} tại {{ editing.location }}</p>
        <Select v-model="form.action" :options="labelOptions(labels.stock_action)" aria-label="Thao tác" />
        <input v-model.number="form.quantity" :class="inputClass" type="number" :placeholder="form.action === 'adjust' ? 'Ví dụ: 10 hoặc -2' : 'Số lượng'" />
        <input v-if="form.action !== 'safety'" v-model="form.reason" :class="inputClass" placeholder="Lý do (bắt buộc)" />
        <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
            <button type="button" :class="secondaryButton" @click="editing = null">Huỷ</button>
        </div>
    </form>
</template>
