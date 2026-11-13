<script setup lang="ts">
import { matchesSearch, useAsyncOptions } from '@admin/search';
import type { Option } from '@admin/types';
import { ComboboxAnchor, ComboboxContent, ComboboxEmpty, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxPortal, ComboboxRoot, ComboboxTrigger, ComboboxViewport } from 'reka-ui';
import { computed, reactive, ref, watch } from 'vue';

/**
 * Chọn một giá trị trong danh sách dài, gõ để lọc (tỉnh/thành, phường/xã, sản phẩm…). Bàn phím: ↑/↓ chọn dòng, Enter chọn,
 * Esc đóng. Lọc không phân biệt dấu ("ha noi" → "Hà Nội").
 * - Lựa chọn có sẵn: `options`.
 * - Tìm ở server: `search(term) => Promise<Option[]>` (chờ 250 ms sau khi gõ, bỏ kết quả cũ); `selectedLabel` là nhãn của giá
 *   trị hiện tại khi nó chưa có trong kết quả. Sự kiện `select` phát cả lựa chọn (giá trị + nhãn).
 */
const props = withDefaults(
    defineProps<{ options?: Option[]; search?: (term: string) => Promise<Option[]>; selectedLabel?: string; placeholder?: string; emptyText?: string; id?: string; ariaLabel?: string; disabled?: boolean }>(),
    { options: () => [], search: undefined, selectedLabel: undefined, placeholder: undefined, emptyText: undefined, id: undefined, ariaLabel: undefined },
);
const model = defineModel<string>({ required: true });
const emit = defineEmits<{ select: [option: Option] }>();

const MAX_SHOWN = 200;
const term = ref('');
const remote = props.search ? useAsyncOptions(props.search) : null;
const labels = reactive(new Map<string, string>());
watch(
    () => [props.options, remote?.results.value ?? []] as const,
    ([local, found]) => [...local, ...found].forEach((option) => labels.set(option.value, option.label)),
    { immediate: true, deep: true },
);
watch(
    () => props.selectedLabel,
    (label) => {
        if (label && model.value) labels.set(model.value, label);
    },
    { immediate: true },
);

const matching = computed(() => (remote ? remote.results.value : props.options.filter((option) => matchesSearch(option.label, term.value))));
const shown = computed(() => matching.value.slice(0, MAX_SHOWN));
const selected = computed({
    get: () => (model.value === '' ? undefined : model.value),
    set: (value: string | undefined) => {
        model.value = value ?? '';
        const option = value === undefined ? undefined : matching.value.find((candidate) => candidate.value === value);
        if (option) emit('select', option);
    },
});

function onSearch(value: string): void {
    term.value = value;
    remote?.query(value);
}

function onOpen(open: boolean): void {
    if (open && remote && remote.results.value.length === 0) remote.query(term.value);
}
</script>

<template>
    <ComboboxRoot v-model="selected" :disabled="disabled" ignore-filter open-on-click reset-search-term-on-blur @update:open="onOpen">
        <ComboboxAnchor class="flex w-full items-center rounded-md border border-slate-300 bg-white text-sm focus-within:border-indigo-500">
            <ComboboxInput
                :id="id"
                :aria-label="ariaLabel"
                :display-value="(value: string) => labels.get(value) ?? ''"
                :placeholder="placeholder"
                class="w-full rounded-md bg-transparent px-3 py-2 focus:outline-none disabled:opacity-50"
                @update:model-value="onSearch"
            />
            <ComboboxTrigger class="px-2 text-slate-400" aria-label="Mở danh sách">▾</ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent position="popper" :side-offset="4" class="z-50 max-h-72 w-(--reka-combobox-trigger-width) overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg">
                <ComboboxViewport class="p-1">
                    <p v-if="remote?.loading.value" class="px-3 py-2 text-sm text-slate-500" aria-live="polite">Đang tìm…</p>
                    <p v-else-if="remote?.failed.value" class="px-3 py-2 text-sm text-red-600" aria-live="polite">Không tìm được, gõ lại để thử.</p>
                    <ComboboxEmpty v-else class="px-3 py-2 text-sm text-slate-500">{{ emptyText ?? 'Không có kết quả.' }}</ComboboxEmpty>
                    <ComboboxItem
                        v-for="option in shown"
                        :key="option.value"
                        :value="option.value"
                        :text-value="option.label"
                        class="relative flex cursor-pointer items-center rounded px-7 py-1.5 text-sm text-slate-700 outline-none select-none data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
                    >
                        <ComboboxItemIndicator class="absolute left-2 text-indigo-600">✓</ComboboxItemIndicator>
                        {{ option.label }}
                    </ComboboxItem>
                    <p v-if="matching.length > MAX_SHOWN" class="px-3 py-2 text-xs text-slate-500">Còn {{ matching.length - MAX_SHOWN }} kết quả — gõ thêm để thu hẹp.</p>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
