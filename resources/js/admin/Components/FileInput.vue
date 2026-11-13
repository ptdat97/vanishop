<script setup lang="ts">
import { secondaryButton } from '@admin/styles';
import { formatSize, partitionFiles } from '@admin/upload';
import { computed, ref, useId } from 'vue';

/**
 * Ô chọn tệp dùng chung của Admin (thay `<input type="file">` thô):
 *   <FileInput v-model="form.file" accept=".csv,text/csv" :max-kb="2048" :progress="form.progress?.percentage" :error="form.errors.file" />
 * - Kiểm tra loại (`accept`) và dung lượng (`maxKb`) ngay khi chọn; tệp sai bị bỏ và báo lỗi tại ô (server vẫn kiểm lại).
 * - `multiple` → v-model là `File[]`; không thì `File | null`. Sự kiện `pick` phát các tệp hợp lệ (tải lên ngay khi chọn).
 * - `progress` (0–100) hiện thanh tiến độ khi đang gửi; `compact` chỉ hiện nút (vd. ô "+ Ảnh").
 */
const props = withDefaults(
    defineProps<{ accept?: string; maxKb?: number | null; multiple?: boolean; label?: string; progress?: number | null; error?: string | null; disabled?: boolean; compact?: boolean }>(),
    { accept: '', maxKb: null, multiple: false, label: 'Chọn tệp', progress: null, error: null, disabled: false, compact: false },
);
const model = defineModel<File | File[] | null>({ default: null });
const emit = defineEmits<{ pick: [files: File[]] }>();
const id = useId();
const input = ref<HTMLInputElement | null>(null);
const localErrors = ref<string[]>([]);

const files = computed<File[]>(() => (Array.isArray(model.value) ? model.value : model.value ? [model.value] : []));
const sending = computed(() => props.progress !== null && props.progress !== undefined);
const hint = computed(() => (props.maxKb ? `Tối đa ${formatSize(props.maxKb * 1024)}` : ''));

function onChange(): void {
    const chosen = Array.from(input.value?.files ?? []);
    const { valid, errors } = partitionFiles(chosen, { accept: props.accept, maxKb: props.maxKb });
    localErrors.value = errors;
    model.value = props.multiple ? valid : (valid[0] ?? null);
    if (valid.length) emit('pick', valid);
    if (input.value) input.value.value = '';
}

function clear(): void {
    model.value = props.multiple ? [] : null;
    localErrors.value = [];
}

defineExpose({ clear, open: () => input.value?.click() });
</script>

<template>
    <div class="text-sm" :data-file-input="id">
        <div class="flex flex-wrap items-center gap-2">
            <label :for="id" :class="[compact ? '' : secondaryButton, disabled ? 'pointer-events-none opacity-50' : 'cursor-pointer']">
                <slot>{{ label }}</slot>
            </label>
            <input :id="id" ref="input" type="file" class="sr-only" :accept="accept || undefined" :multiple="multiple" :disabled="disabled" @change="onChange" />
            <template v-if="!compact">
                <span v-if="files.length" class="truncate text-slate-700">{{ files.map((file) => `${file.name} (${formatSize(file.size)})`).join(', ') }}</span>
                <button v-if="files.length && !sending" type="button" class="text-xs text-slate-500 hover:underline" @click="clear">Bỏ</button>
                <span v-else-if="!files.length && hint" class="text-xs text-slate-500">{{ hint }}</span>
            </template>
        </div>
        <div v-if="sending" class="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded bg-slate-200" role="progressbar" :aria-valuenow="progress ?? undefined" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full bg-indigo-600 transition-all" :style="{ width: `${progress}%` }" />
        </div>
        <p v-for="message in localErrors" :key="message" class="mt-1 text-red-600">{{ message }}</p>
        <p v-if="error" class="mt-1 text-red-600">{{ error }}</p>
    </div>
</template>
