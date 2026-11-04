<script setup lang="ts">
import Select from '@admin/Components/Select.vue';
import { getJson } from '@admin/http';
import { inputClass } from '@admin/styles';
import { computed, inject, ref } from 'vue';
import { type LinkValue, menuEditorKey } from '../menu';

/** Chọn link có kiểu: loại → giá trị (tìm đối tượng, chọn trang cố định, hoặc nhập URL). */
const props = defineProps<{ itemId: string }>();
const link = defineModel<LinkValue | null>({ required: true });
const context = inject(menuEditorKey)!;

const type = computed(() => context.linkTypes.find((candidate) => candidate.type === link.value?.type) ?? null);
const typeOptions = computed(() => context.linkTypes.map((option) => ({ value: option.type, label: option.label })));
const query = ref('');
const results = ref<Array<{ value: string; label: string }>>([]);
const searching = ref(false);
const searchError = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

const selectedLabel = computed(() => context.linkLabels[props.itemId]);

function setType(value: string): void {
    link.value = value === '' ? null : { type: value, value: '', label: {} };
    results.value = [];
    query.value = '';
    delete context.linkLabels[props.itemId];
}

function search(): void {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (!link.value) return;
        searching.value = true;
        searchError.value = false;
        try {
            results.value = (await getJson<{ data: Array<{ value: string; label: string }> }>(context.searchUrl, { type: link.value.type, q: query.value })).data;
        } catch {
            searchError.value = true;
        } finally {
            searching.value = false;
        }
    }, 250);
}

function pick(result: { value: string; label: string }): void {
    if (!link.value) return;
    link.value = { ...link.value, value: result.value };
    context.linkLabels[props.itemId] = { label: result.label, exists: true };
    results.value = [];
    query.value = '';
}

function pickOption(value: string): void {
    if (!link.value) return;
    link.value = { ...link.value, value };
    const option = type.value?.options.find((candidate) => candidate.value === value);
    context.linkLabels[props.itemId] = { label: option?.label ?? value, exists: true };
}
</script>

<template>
    <div class="space-y-2">
        <Select :model-value="link?.type ?? ''" :options="typeOptions" empty-label="— Không có link (tiêu đề nhóm) —" aria-label="Loại link" @change="setType" />
        <template v-if="link && type">
            <Select v-if="type.options.length" :model-value="link.value" :options="type.options" placeholder="Chọn trang" aria-label="Trang" @change="pickOption" />
            <template v-else-if="type.searchable">
                <p v-if="link.value" class="text-xs" :class="selectedLabel?.exists === false ? 'text-red-600' : 'text-slate-600'">
                    Đang trỏ tới: <strong>{{ selectedLabel?.label ?? `#${link.value}` }}</strong>
                    <span v-if="selectedLabel?.exists === false"> — đối tượng đã ẩn hoặc xoá, mục sẽ không hiện</span>
                </p>
                <input v-model="query" :class="inputClass" :placeholder="`Tìm ${type.label.toLowerCase()}…`" :aria-label="`Tìm ${type.label}`" @input="search" @focus="search" />
                <p v-if="searching" class="text-xs text-slate-400">Đang tìm…</p>
                <p v-else-if="searchError" class="text-xs text-red-600">Không tìm được, thử lại.</p>
                <ul v-if="results.length" class="max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white text-sm">
                    <li v-for="result in results" :key="result.value">
                        <button type="button" class="block w-full px-3 py-1.5 text-left hover:bg-indigo-50" @click="pick(result)">{{ result.label }}</button>
                    </li>
                </ul>
            </template>
            <input v-else v-model="link.value" :class="inputClass" placeholder="/duong-dan, https://…, mailto:…, tel:…" aria-label="Đường dẫn" />
        </template>
    </div>
</template>
