<script setup lang="ts">
import MediaField from '@admin/Components/Media/MediaField.vue';
import Select from '@admin/Components/Select.vue';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton } from '@admin/styles';
import { useForm } from '@inertiajs/vue3';

export type SettingField = {
    key: string;
    label: string;
    type: 'string' | 'secret' | 'text' | 'int' | 'bool' | 'select' | 'media';
    help: string | null;
    options: Record<string, string>;
    is_set: boolean;
    value: unknown;
    effective: unknown;
};

/** Form cấu hình theo SettingDefinition — dùng ở Admin → Cấu hình và trang cấu hình riêng của plugin. */
const props = defineProps<{ action: string; namespace: string; fields: SettingField[] }>();

const toInput = (field: SettingField): string => {
    if (!field.is_set || field.value === null || field.value === undefined) return '';
    if (field.type === 'bool') return field.value ? '1' : '0';
    return String(field.value);
};
const form = useForm({
    namespace: props.namespace,
    values: Object.fromEntries(props.fields.map((field) => [field.key, toInput(field)])) as Record<string, string>,
});
const booleanOptions = [
    { value: '1', label: 'Có' },
    { value: '0', label: 'Không' },
];
const display = (value: unknown): string => (value === null || value === undefined || value === '' ? '—' : String(value));

function submit(): void {
    form.put(props.action, { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submit">
        <div v-for="field in fields" :key="field.key" class="grid gap-2 md:grid-cols-3">
            <div>
                <div class="font-medium">{{ field.label }}</div>
                <div class="font-mono text-xs text-slate-400">{{ namespace }}.{{ field.key }}</div>
                <div v-if="field.help" class="text-xs text-slate-500">{{ field.help }}</div>
            </div>
            <div>
                <Select
                    v-if="field.type === 'select' || field.type === 'bool'"
                    v-model="form.values[field.key]"
                    :options="field.type === 'bool' ? booleanOptions : labelOptions(field.options)"
                    empty-label="(mặc định)"
                    :aria-label="field.label"
                />
                <textarea v-else-if="field.type === 'text'" v-model="form.values[field.key]" rows="3" :class="inputClass" placeholder="(mặc định)" />
                <MediaField
                    v-else-if="field.type === 'media'"
                    :model-value="form.values[field.key] ? Number(form.values[field.key]) : null"
                    @update:model-value="(id) => (form.values[field.key] = id ? String(id) : '')"
                />
                <input
                    v-else
                    v-model="form.values[field.key]"
                    :type="field.type === 'secret' ? 'password' : field.type === 'int' ? 'number' : 'text'"
                    :class="inputClass"
                    :placeholder="field.type === 'secret' ? (field.is_set ? 'Đã đặt — để trống để giữ nguyên' : 'Chưa đặt') : '(mặc định)'"
                    autocomplete="off"
                />
                <p v-if="form.errors[`values.${field.key}`]" class="text-xs text-red-600">{{ form.errors[`values.${field.key}`] }}</p>
            </div>
            <div v-if="field.type !== 'secret'" class="text-xs text-slate-500">
                Hiệu lực: <strong>{{ display(field.effective) }}</strong>
            </div>
        </div>
        <p v-if="!fields.length" class="text-slate-500">Không có cấu hình nào.</p>
        <button v-if="fields.length" type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
    </form>
</template>
