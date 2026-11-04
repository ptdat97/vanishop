<script setup lang="ts" generic="T extends string | number">
import { ComboboxAnchor, ComboboxContent, ComboboxEmpty, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxPortal, ComboboxRoot, ComboboxTrigger, ComboboxViewport } from 'reka-ui';
import { computed } from 'vue';

/** Chọn nhiều giá trị, gõ để lọc; giá trị đã chọn hiện thành thẻ, bấm ✕ để bỏ. */
const props = defineProps<{ options: Array<{ value: T; label: string }>; placeholder?: string; emptyText?: string; id?: string; disabled?: boolean }>();
const model = defineModel<T[]>({ required: true });

const labels = computed(() => new Map(props.options.map((option) => [option.value, option.label])));

function remove(value: T): void {
    model.value = model.value.filter((selected) => selected !== value);
}
</script>

<template>
    <ComboboxRoot v-model="model" multiple :disabled="disabled" open-on-click reset-search-term-on-select>
        <ComboboxAnchor class="flex w-full flex-wrap items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm focus-within:border-indigo-500">
            <span v-for="value in model" :key="value" class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                {{ labels.get(value) ?? value }}
                <button v-if="!disabled" type="button" class="text-indigo-400 hover:text-indigo-700" :aria-label="`Bỏ ${labels.get(value) ?? value}`" @click.stop="remove(value)">✕</button>
            </span>
            <ComboboxInput :id="id" :placeholder="model.length ? '' : (placeholder ?? 'Gõ để tìm')" class="min-w-24 flex-1 bg-transparent px-1 py-0.5 focus:outline-none" />
            <ComboboxTrigger class="px-1 text-slate-400" aria-label="Mở danh sách">▾</ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent position="popper" :side-offset="4" class="z-50 max-h-72 w-(--reka-combobox-trigger-width) overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg">
                <ComboboxViewport class="p-1">
                    <ComboboxEmpty class="px-3 py-2 text-sm text-slate-500">{{ emptyText ?? 'Không có kết quả.' }}</ComboboxEmpty>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :text-value="option.label"
                        class="relative flex cursor-pointer items-center rounded py-1.5 pr-3 pl-7 text-sm text-slate-700 outline-none select-none data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
                    >
                        <ComboboxItemIndicator class="absolute left-2 text-indigo-600">✓</ComboboxItemIndicator>
                        {{ option.label }}
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
