<script setup lang="ts">
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
}>();

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const selectedSizes = ref<number[]>([]);
const editing = reactive<Record<number, VariantRow>>({});
const systems = computed(() => [...new Set(props.sizes.map((size) => size.system))]);

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

        <table v-if="variants.length" class="w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr>
                    <th class="py-1">Màu</th>
                    <th class="py-1">Size</th>
                    <th class="py-1">SKU</th>
                    <th class="py-1">Barcode</th>
                    <th class="py-1">Khối lượng (g)</th>
                    <th class="py-1">Trạng thái</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="variant in variants" :key="variant.id" class="border-t border-slate-100 align-top">
                    <td class="py-2">{{ variant.color_code }}</td>
                    <td class="py-2">{{ variant.size_code }}</td>
                    <template v-if="editing[variant.id]">
                        <td class="py-1 pr-2"><input v-model="editing[variant.id].sku" :class="inputClass" /></td>
                        <td class="py-1 pr-2"><input v-model="editing[variant.id].barcode" :class="inputClass" /></td>
                        <td class="py-1 pr-2"><input v-model.number="editing[variant.id].weight_gram" :class="inputClass" type="number" min="0" /></td>
                        <td class="py-1 pr-2">
                            <select v-model="editing[variant.id].status" :class="inputClass">
                                <option value="active">Đang bán</option>
                                <option value="inactive">Ngừng bán</option>
                            </select>
                        </td>
                        <td class="py-2 text-right">
                            <button type="button" class="text-indigo-600" @click="save(variant.id)">Lưu</button>
                            <button type="button" class="ml-2 text-slate-500" @click="delete editing[variant.id]">Huỷ</button>
                        </td>
                    </template>
                    <template v-else>
                        <td class="py-2 font-mono text-xs">{{ variant.sku }}</td>
                        <td class="py-2 font-mono text-xs">{{ variant.barcode ?? '—' }}</td>
                        <td class="py-2">{{ variant.weight_gram ?? '—' }}</td>
                        <td class="py-2">{{ variant.status === 'active' ? 'Đang bán' : 'Ngừng bán' }}</td>
                        <td class="py-2 text-right"><button type="button" class="text-indigo-600" @click="edit(variant)">Sửa</button></td>
                    </template>
                </tr>
            </tbody>
        </table>
        <p v-if="errors.sku || errors.barcode" class="text-sm text-red-600">{{ errors.sku ?? errors.barcode }}</p>
    </div>
</template>
