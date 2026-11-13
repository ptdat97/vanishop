<script setup lang="ts" generic="T extends string | number">
import { matchesSearch, useAsyncOptions } from '@admin/search';
import { ComboboxAnchor, ComboboxContent, ComboboxEmpty, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxPortal, ComboboxRoot, ComboboxTrigger, ComboboxViewport } from 'reka-ui';
import { computed, reactive, ref, watch } from 'vue';

type Choice = { value: T; label: string };

/**
 * Chọn nhiều giá trị, gõ để lọc (không phân biệt dấu); giá trị đã chọn hiện thành thẻ, bấm ✕ hoặc Backspace ở ô trống để bỏ.
 * Bàn phím: ↑/↓, Enter, Esc. Tìm ở server: `search(term) => Promise<{value,label}[]>`; `selectedOptions` cho nhãn của giá trị
 * đã chọn chưa có trong kết quả.
 */
const props = withDefaults(
    defineProps<{ options?: Choice[]; search?: (term: string) => Promise<Choice[]>; selectedOptions?: Choice[]; placeholder?: string; emptyText?: string; id?: string; ariaLabel?: string; disabled?: boolean }>(),
    { options: () => [], search: undefined, selectedOptions: () => [], placeholder: undefined, emptyText: undefined, id: undefined, ariaLabel: undefined },
);
const model = defineModel<T[]>({ required: true });

const MAX_SHOWN = 200;
const term = ref('');
const remote = props.search ? useAsyncOptions(props.search) : null;
const labels = reactive(new Map<T, string>()) as Map<T, string>;
watch(
    () => [props.options, props.selectedOptions, remote?.results.value ?? []] as const,
    ([local, chosen, found]) => [...local, ...chosen, ...found].forEach((option) => labels.set(option.value, option.label)),
    { immediate: true, deep: true },
);

const matching = computed(() => (remote ? remote.results.value : props.options.filter((option) => matchesSearch(option.label, term.value))));
const shown = computed(() => matching.value.slice(0, MAX_SHOWN));

function remove(value: T): void {
    model.value = model.value.filter((selected) => selected !== value);
}

function onSearch(value: string): void {
    term.value = value;
    remote?.query(value);
}

function onOpen(open: boolean): void {
    if (open && remote && remote.results.value.length === 0) remote.query(term.value);
}

function onBackspace(event: KeyboardEvent): void {
    if (term.value === '' && model.value.length && !(event.target as HTMLInputElement).value) {
        model.value = model.value.slice(0, -1);
    }
}
</script>

<template>
    <ComboboxRoot v-model="model" multiple :disabled="disabled" ignore-filter open-on-click reset-search-term-on-select @update:open="onOpen">
        <ComboboxAnchor class="flex w-full flex-wrap items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm focus-within:border-indigo-500">
            <span v-for="value in model" :key="value" class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                {{ labels.get(value) ?? value }}
                <button v-if="!disabled" type="button" class="text-indigo-400 hover:text-indigo-700" :aria-label="`Bỏ ${labels.get(value) ?? value}`" @click.stop="remove(value)">✕</button>
            </span>
            <ComboboxInput
                :id="id"
                :aria-label="ariaLabel"
                :placeholder="model.length ? '' : (placeholder ?? 'Gõ để tìm')"
                class="min-w-24 flex-1 bg-transparent px-1 py-0.5 focus:outline-none"
                @update:model-value="onSearch"
                @keydown.backspace="onBackspace"
            />
            <ComboboxTrigger class="px-1 text-slate-400" aria-label="Mở danh sách">▾</ComboboxTrigger>
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
                        class="relative flex cursor-pointer items-center rounded py-1.5 pr-3 pl-7 text-sm text-slate-700 outline-none select-none data-highlighted:bg-indigo-50 data-highlighted:text-indigo-700"
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
