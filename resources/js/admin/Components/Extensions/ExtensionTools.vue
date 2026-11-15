<script setup lang="ts">
import FileInput from '@admin/Components/FileInput.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionTool } from '@admin/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * Công cụ tải file của plugin ở đầu một danh sách Admin (`adminTool`, EP v3 X1): mỗi công cụ là một nút; bấm mở khung chọn
 * file + ô phụ, gửi multipart `file` + ô phụ. Lỗi theo dòng (`import.N`) và lỗi file hiện ngay dưới khung.
 *
 *   <ExtensionTools :tools="extensions.tools ?? []" />
 */
defineProps<{ tools: ExtensionTool[] }>();

const open = ref<string | null>(null);
const form = useForm<{ file: File | null; [key: string]: string | File | null }>({ file: null });
const errors = computed(() =>
    Object.entries(usePage().props.errors as Record<string, string | string[]>)
        .filter(([key]) => key === 'file' || key === 'import' || key.startsWith('import.'))
        .flatMap(([, message]) => (Array.isArray(message) ? message : [message])),
);

function toggle(key: string): void {
    open.value = open.value === key ? null : key;
    form.reset();
}

function submit(tool: ExtensionTool): void {
    form.post(tool.url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            open.value = null;
            form.reset();
        },
    });
}
</script>

<template>
    <div v-if="tools.length" class="mb-4 space-y-2" data-extension-tools>
        <div class="flex flex-wrap gap-2">
            <button v-for="tool in tools" :key="tool.key" type="button" :class="secondaryButton" :aria-expanded="open === tool.key" @click="toggle(tool.key)">{{ tool.label }}</button>
        </div>
        <template v-for="tool in tools" :key="`panel-${tool.key}`">
            <form v-if="open === tool.key" class="space-y-2 rounded-lg border border-slate-200 bg-white p-4 text-sm" :data-tool="tool.key" @submit.prevent="submit(tool)">
                <p v-if="tool.help" class="text-slate-500">{{ tool.help }}</p>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <span>File</span>
                        <FileInput :model-value="form.file" :accept="tool.accept" :max-kb="tool.max_kb ?? null" :progress="form.progress?.percentage ?? null" @update:model-value="form.file = $event as File | null" />
                    </div>
                    <label v-for="field in tool.fields ?? []" :key="field.key" class="flex flex-col gap-1">
                        <span>{{ field.label }}</span>
                        <input v-model="form[field.key] as string" :class="inputClass" :placeholder="field.placeholder" />
                    </label>
                    <button type="submit" :class="primaryButton" :disabled="form.processing || !form.file">{{ tool.label }}</button>
                </div>
            </form>
        </template>
        <ul v-if="errors.length" class="space-y-1 rounded bg-red-50 p-3 text-sm text-red-700" role="alert">
            <li v-for="message in errors" :key="message">{{ message }}</li>
        </ul>
    </div>
</template>
