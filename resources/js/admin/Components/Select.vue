<script setup lang="ts">
import type { Option } from '@admin/types';
import { SelectContent, SelectItem, SelectItemIndicator, SelectItemText, SelectPortal, SelectRoot, SelectTrigger, SelectValue, SelectViewport } from 'reka-ui';
import { computed } from 'vue';

/**
 * Chọn một giá trị. `emptyLabel` thêm lựa chọn "tất cả" ứng với chuỗi rỗng (Reka không nhận giá trị rỗng nên đổi qua
 * giá trị đánh dấu ở bên trong).
 */
const props = defineProps<{ options: Option[]; emptyLabel?: string; placeholder?: string; id?: string; ariaLabel?: string; disabled?: boolean }>();
const model = defineModel<string>({ required: true });

const EMPTY = '__vani_empty__';
const selected = computed({
    get: () => (model.value === '' && props.emptyLabel !== undefined ? EMPTY : model.value),
    set: (value: string) => (model.value = value === EMPTY ? '' : value),
});
const items = computed<Option[]>(() => (props.emptyLabel === undefined ? props.options : [{ value: EMPTY, label: props.emptyLabel }, ...props.options]));
</script>

<template>
    <SelectRoot v-model="selected" :disabled="disabled">
        <SelectTrigger
            :id="id"
            :aria-label="ariaLabel"
            class="flex w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm focus:border-indigo-500 focus:outline-none disabled:opacity-50 data-placeholder:text-slate-400"
        >
            <SelectValue :placeholder="placeholder" />
            <span aria-hidden="true" class="text-slate-400">▾</span>
        </SelectTrigger>
        <SelectPortal>
            <SelectContent position="popper" :side-offset="4" class="z-50 max-h-72 min-w-(--reka-select-trigger-width) overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                <SelectViewport class="p-1">
                    <SelectItem
                        v-for="option in items"
                        :key="option.value"
                        :value="option.value"
                        class="relative flex cursor-pointer items-center rounded px-7 py-1.5 text-sm text-slate-700 outline-none select-none data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
                    >
                        <SelectItemIndicator class="absolute left-2 text-indigo-600">✓</SelectItemIndicator>
                        <SelectItemText>{{ option.label }}</SelectItemText>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>
