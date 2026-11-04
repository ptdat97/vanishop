<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { labelOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface VariantRow {
    id: number;
    sku: string;
    barcode: string | null;
    status: string;
    weight_gram: number | null;
    color_code: string;
    size_code: string;
}

const props = defineProps<{
    productUrl: string;
    variants: VariantRow[];
    sizes: Array<{ id: number; label: string; system: string }>;
    statusLabels: Record<string, string>;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const selectedSizes = ref<number[]>([]);
const editing = reactive<Record<number, VariantRow>>({});
const systems = computed(() => [...new Set(props.sizes.map((size) => size.system))]);
const columns = [
    { key: 'color_code', label: 'Màu' },
    { key: 'size_code', label: 'Size' },
    { key: 'sku', label: 'SKU' },
    { key: 'barcode', label: 'Barcode' },
    { key: 'weight_gram', label: 'Khối lượng (g)' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];

function generate(): void {
    router.post(`${props.productUrl}/variants/generate`, { size_ids: selectedSizes.value }, { preserveScroll: true, onSuccess: () => (selectedSizes.value = []) });
}

function edit(variant: VariantRow): void {
    editing[variant.id] = { ...variant };
}

function save(id: number): void {
    const row = editing[id];
    router.put(`${props.productUrl}/variants/${id}`, { sku: row.sku, barcode: row.barcode, status: row.status, weight_gram: row.weight_gram }, {
        preserveScroll: true,
        onSuccess: () => delete editing[id],
    });
}
</script>

<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-medium">Biến thể (màu × size)</h2>

        <div class="rounded-md bg-slate-50 p-3">
            <p class="mb-2 text-xs text-slate-500">Chọn size để tạo biến thể cho mọi màu. Biến thể đã có không bị thay đổi.</p>
            <div v-for="system in systems" :key="system" class="mb-2 flex flex-wrap items-center gap-3 text-sm">
                <span class="w-16 text-xs uppercase text-slate-400">{{ system }}</span>
                <label v-for="size in sizes.filter((s) => s.system === system)" :key="size.id" class="flex items-center gap-1">
                    <input v-model="selectedSizes" type="checkbox" :value="size.id" />
                    {{ size.label }}
                </label>
            </div>
            <p v-if="errors.size_ids" class="mb-2 text-sm text-red-600">{{ errors.size_ids }}</p>
            <button type="button" :class="secondaryButton" :disabled="!selectedSizes.length" @click="generate">Tạo biến thể</button>
        </div>

        <DataTable v-if="variants.length" :columns="columns" :rows="variants" :row-label="(variant) => variant.sku">
            <template #cell-sku="{ row }">
                <input v-if="editing[row.id]" v-model="editing[row.id].sku" :class="inputClass" aria-label="SKU" />
                <span v-else class="font-mono text-xs">{{ row.sku }}</span>
            </template>
            <template #cell-barcode="{ row }">
                <input v-if="editing[row.id]" v-model="editing[row.id].barcode" :class="inputClass" aria-label="Barcode" />
                <span v-else class="font-mono text-xs">{{ row.barcode ?? '—' }}</span>
            </template>
            <template #cell-weight_gram="{ row }">
                <input v-if="editing[row.id]" v-model.number="editing[row.id].weight_gram" :class="inputClass" type="number" min="0" aria-label="Khối lượng (g)" />
                <template v-else>{{ row.weight_gram ?? '—' }}</template>
            </template>
            <template #cell-status="{ row }">
                <Select v-if="editing[row.id]" v-model="editing[row.id].status" :options="labelOptions(statusLabels)" aria-label="Trạng thái" />
                <StatusBadge v-else :label="statusLabels[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : 'neutral'" />
            </template>
            <template #cell-actions="{ row }">
                <template v-if="editing[row.id]">
                    <button type="button" class="text-indigo-600" @click="save(row.id)">Lưu</button>
                    <button type="button" class="ml-2 text-slate-500" @click="delete editing[row.id]">Huỷ</button>
                </template>
                <button v-else type="button" class="text-indigo-600" @click="edit(row)">Sửa</button>
            </template>
        </DataTable>
        <p v-if="errors.sku || errors.barcode" class="text-sm text-red-600">{{ errors.sku ?? errors.barcode }}</p>
    </div>
</template>
