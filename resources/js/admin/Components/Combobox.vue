<script setup lang="ts">
import type { Option } from '@admin/types';
import { ComboboxAnchor, ComboboxContent, ComboboxEmpty, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxPortal, ComboboxRoot, ComboboxTrigger, ComboboxViewport } from 'reka-ui';
import { computed } from 'vue';

/** Chọn một giá trị trong danh sách dài, gõ để lọc (tỉnh/thành, phường/xã…). */
const props = defineProps<{ options: Option[]; placeholder?: string; emptyText?: string; id?: string; disabled?: boolean }>();
const model = defineModel<string>({ required: true });

const labels = computed(() => Object.fromEntries(props.options.map((option) => [option.value, option.label])));
const selected = computed({
    get: () => (model.value === '' ? undefined : model.value),
    set: (value: string | undefined) => (model.value = value ?? ''),
});
</script>

<template>
    <ComboboxRoot v-model="selected" :disabled="disabled" open-on-click reset-search-term-on-blur>
        <ComboboxAnchor class="flex w-full items-center rounded-md border border-slate-300 bg-white text-sm focus-within:border-indigo-500">
            <ComboboxInput
                :id="id"
                :display-value="(value: string) => labels[value] ?? ''"
                :placeholder="placeholder"
                class="w-full rounded-md bg-transparent px-3 py-2 focus:outline-none disabled:opacity-50"
            />
            <ComboboxTrigger class="px-2 text-slate-400" aria-label="Mở danh sách">▾</ComboboxTrigger>
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
                        class="relative flex cursor-pointer items-center rounded px-7 py-1.5 text-sm text-slate-700 outline-none select-none data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
                    >
                        <ComboboxItemIndicator class="absolute left-2 text-indigo-600">✓</ComboboxItemIndicator>
                        {{ option.label }}
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
