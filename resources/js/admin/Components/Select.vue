<script setup lang="ts" generic="T extends string | number | boolean | null">
import { SelectContent, SelectItem, SelectItemIndicator, SelectItemText, SelectPortal, SelectRoot, SelectTrigger, SelectValue, SelectViewport } from 'reka-ui';
import { computed } from 'vue';

/**
 * Chọn một giá trị (chuỗi, số, boolean). `emptyLabel` thêm lựa chọn rỗng ứng với `emptyValue` (mặc định chuỗi rỗng;
 * truyền `null` cho trường số tuỳ chọn). Reka chỉ nhận khoá chuỗi khác rỗng nên giá trị được đổi qua khoá nội bộ.
 */
const props = withDefaults(
    defineProps<{
        options: Array<{ value: T; label: string; disabled?: boolean }>;
        emptyLabel?: string;
        emptyValue?: T;
        placeholder?: string;
        id?: string;
        ariaLabel?: string;
        disabled?: boolean;
    }>(),
    { emptyLabel: undefined, emptyValue: '' as never, placeholder: undefined, id: undefined, ariaLabel: undefined },
);
const model = defineModel<T>({ required: true });
const emit = defineEmits<{ change: [value: T] }>();

const EMPTY = '__vani_empty__';
const keyOf = (value: T): string => (props.emptyLabel !== undefined && value === props.emptyValue ? EMPTY : `${typeof value}:${String(value)}`);
const items = computed(() => [
    ...(props.emptyLabel === undefined ? [] : [{ key: EMPTY, value: props.emptyValue as T, label: props.emptyLabel, disabled: false }]),
    ...props.options.map((option) => ({ key: keyOf(option.value), value: option.value, label: option.label, disabled: option.disabled ?? false })),
]);
const selected = computed({
    get: () => (items.value.some((item) => item.key === keyOf(model.value)) ? keyOf(model.value) : undefined),
    set: (key: string | undefined) => {
        const item = items.value.find((candidate) => candidate.key === key);
        if (item !== undefined) {
            model.value = item.value;
            emit('change', item.value);
        }
    },
});
</script>

<template>
    <SelectRoot v-model="selected" :disabled="disabled">
        <SelectTrigger
            :id="id"
            :aria-label="ariaLabel"
            class="flex w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm focus:border-indigo-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 data-placeholder:text-slate-400"
        >
            <SelectValue :placeholder="placeholder ?? '— Chọn —'" class="truncate" />
            <span aria-hidden="true" class="text-slate-400">▾</span>
        </SelectTrigger>
        <SelectPortal>
            <SelectContent position="popper" :side-offset="4" class="z-50 max-h-72 min-w-(--reka-select-trigger-width) overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                <SelectViewport class="p-1">
                    <SelectItem
                        v-for="item in items"
                        :key="item.key"
                        :value="item.key"
                        :disabled="item.disabled"
                        class="relative flex cursor-pointer items-center rounded py-1.5 pr-3 pl-7 text-sm text-slate-700 outline-none select-none data-disabled:cursor-not-allowed data-disabled:opacity-50 data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
                    >
                        <SelectItemIndicator class="absolute left-2 text-indigo-600">✓</SelectItemIndicator>
                        <SelectItemText>{{ item.label }}</SelectItemText>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>
