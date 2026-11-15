<script setup lang="ts">
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useAsyncOptions } from '@admin/search';
import { countSelected, selectableIds, selectionState, toggleGroup, toggleOne, type SkuPickerProduct } from '@admin/skuPicker';
import { inputClass } from '@admin/styles';
import type { ExtensionFilter } from '@admin/types';
import { computed, reactive, ref, useId, watch } from 'vue';

/**
 * Chọn số lượng lớn SKU (thêm vào bảng giá, khuyến mãi, kiểm kê…): mặc định hiện **sản phẩm**; bấm mở rộng để hiện các
 * **SKU** của sản phẩm đó.
 *
 *   <SkuPicker v-model="variantIds" :search="(term) => getJson(url, { q: term }).then((r) => r.data)" />
 *
 * - Ô chọn sản phẩm = chọn/bỏ mọi SKU của nó (gạch ngang khi mới chọn một phần); "Chọn tất cả kết quả" áp cho mọi SKU đang
 *   hiện. Lựa chọn giữ nguyên khi tìm tiếp — tìm nhiều lần để gom SKU của nhiều sản phẩm.
 * - `search(term)` trả `SkuPickerProduct[]` (xem `@admin/skuPicker`): chờ 250 ms sau khi gõ, bỏ kết quả cũ. Từ khoá rỗng
 *   không tìm, trừ khi `searchEmpty`.
 * - v-model: id SKU (variant) đã chọn.
 * - `filters`: bộ lọc phụ của plugin (`adminFilter('variant', …)`, EP v3 X1) — vd. bộ sưu tập; giá trị gửi kèm
 *   `search(term, filterValues)`; chọn bộ lọc thì tìm cả khi chưa gõ.
 */
const props = withDefaults(
    defineProps<{ search: (term: string, filters: Record<string, string>) => Promise<SkuPickerProduct[]>; filters?: ExtensionFilter[]; placeholder?: string; searchEmpty?: boolean; ariaLabel?: string }>(),
    { filters: () => [], placeholder: 'Gõ tên, mã sản phẩm hoặc SKU', searchEmpty: false, ariaLabel: 'Tìm sản phẩm' },
);
const selected = defineModel<number[]>({ default: () => [] });

const uid = useId();
const term = ref('');
const filterValues = reactive<Record<string, string>>({});
const hasFilter = computed(() => Object.values(filterValues).some(Boolean));
const active = computed(() => Boolean(term.value.trim()) || hasFilter.value || props.searchEmpty);
const searched = ref(false);
const remote = useAsyncOptions(async (value: string) => {
    const found = await props.search(value, Object.fromEntries(Object.entries(filterValues).filter(([, selected]) => selected)));
    searched.value = true;

    return found;
});
const products = computed(() => (active.value ? remote.results.value : []));
const expanded = reactive(new Set<number>());

watch(
    [term, filterValues],
    () => {
        if (active.value) {
            remote.query(term.value.trim());
        } else {
            searched.value = false;
        }
    },
    { immediate: props.searchEmpty, deep: true },
);

const allIds = computed(() => products.value.flatMap(selectableIds));
const allState = computed(() => selectionState(allIds.value, selected.value));
const skuCount = computed(() => products.value.reduce((sum, product) => sum + product.variants.length, 0));

function toggleExpanded(id: number): void {
    if (expanded.has(id)) expanded.delete(id);
    else expanded.add(id);
}
const expandAll = computed(() => products.value.length > 0 && products.value.every((product) => expanded.has(product.id)));
function toggleExpandAll(): void {
    const open = !expandAll.value;
    products.value.forEach((product) => (open ? expanded.add(product.id) : expanded.delete(product.id)));
}
</script>

<template>
    <div class="space-y-2" data-sku-picker>
        <div class="flex flex-wrap gap-2">
            <input v-model="term" :class="inputClass" class="min-w-60 flex-1" type="search" :placeholder="placeholder" :aria-label="ariaLabel" autocomplete="off" />
            <ExtensionFilters v-if="filters.length" v-model="filterValues" :filters="filters" />
        </div>

        <div v-if="products.length" class="rounded-md border border-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        :checked="allState === 'all'"
                        :indeterminate="allState === 'some'"
                        :disabled="!allIds.length"
                        data-select-all
                        @change="selected = toggleGroup(allIds, selected)"
                    />
                    Chọn tất cả kết quả ({{ products.length }} sản phẩm · {{ skuCount }} SKU)
                </label>
                <button type="button" class="text-xs text-indigo-600 hover:underline" @click="toggleExpandAll">{{ expandAll ? 'Thu gọn tất cả' : 'Mở rộng tất cả' }}</button>
            </div>

            <ul class="max-h-96 overflow-y-auto" role="list">
                <li v-for="product in products" :key="product.id" class="border-b border-slate-100 last:border-0">
                    <div class="flex items-center gap-2 px-3 py-2 text-sm">
                        <input
                            type="checkbox"
                            :checked="selectionState(selectableIds(product), selected) === 'all'"
                            :indeterminate="selectionState(selectableIds(product), selected) === 'some'"
                            :disabled="!selectableIds(product).length"
                            :aria-label="`Chọn mọi SKU của ${product.name}`"
                            :data-product="product.code"
                            @change="selected = toggleGroup(selectableIds(product), selected)"
                        />
                        <button
                            type="button"
                            class="flex flex-1 items-center gap-2 rounded text-left hover:bg-slate-50"
                            :aria-expanded="expanded.has(product.id)"
                            :aria-controls="`${uid}-${product.id}`"
                            :data-expand="product.code"
                            @click="toggleExpanded(product.id)"
                        >
                            <svg class="size-4 shrink-0 text-slate-400 transition-transform" :class="expanded.has(product.id) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.2 14.8a.75.75 0 0 1 0-1.06L10.94 10 7.2 6.26a.75.75 0 1 1 1.06-1.06l4.27 4.27a.75.75 0 0 1 0 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
                            </svg>
                            <span class="flex-1">
                                <span class="font-medium">{{ product.name }}</span>
                                <span class="ml-2 font-mono text-xs text-slate-400">{{ product.code }}</span>
                                <span v-if="product.meta" class="block text-xs text-slate-500">{{ product.meta }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-slate-500">
                                <template v-if="countSelected(selectableIds(product), selected)">{{ countSelected(selectableIds(product), selected) }}/</template>{{ product.variants.length }} SKU
                            </span>
                        </button>
                    </div>
                    <ul v-if="expanded.has(product.id)" :id="`${uid}-${product.id}`" class="border-t border-slate-100 bg-slate-50/60 py-1 pl-9 pr-3" role="list">
                        <li v-for="variant in product.variants" :key="variant.id">
                            <label class="flex items-center gap-2 py-1 text-sm" :class="variant.disabled ? 'text-slate-400' : ''">
                                <input type="checkbox" :checked="selected.includes(variant.id)" :disabled="variant.disabled" :aria-label="`Chọn ${variant.sku}`" :data-sku="variant.sku" @change="selected = toggleOne(variant.id, selected)" />
                                <span class="font-mono text-xs">{{ variant.sku }}</span>
                                <span v-if="variant.label" class="text-slate-600">{{ variant.label }}</span>
                                <StatusBadge v-if="variant.status && variant.status !== 'active'" label="Ngừng bán" tone="neutral" />
                                <span v-if="variant.meta" class="ml-auto text-xs text-slate-500">{{ variant.meta }}</span>
                            </label>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <p v-else-if="remote.loading.value" class="text-sm text-slate-500">Đang tìm…</p>
        <p v-else-if="remote.failed.value" class="text-sm text-red-600">Không tìm được — thử lại.</p>
        <p v-else-if="searched && active" class="text-sm text-slate-500">Không tìm thấy sản phẩm nào khớp<template v-if="term.trim()"> “{{ term.trim() }}”</template>.</p>
        <p v-else class="text-sm text-slate-500">Gõ để tìm. Chọn cả sản phẩm, hoặc bấm vào sản phẩm để chọn từng SKU.</p>

        <div v-if="selected.length" class="flex items-center justify-between text-sm text-indigo-700" data-selected-count>
            <span>Đã chọn {{ selected.length }} SKU (giữ lựa chọn khi tìm tiếp)</span>
            <button type="button" class="text-xs hover:underline" @click="selected = []">Bỏ chọn tất cả</button>
        </div>
    </div>
</template>
