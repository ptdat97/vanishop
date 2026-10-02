<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';

type Field = {
    key: string;
    label: string;
    type: 'string' | 'secret' | 'text' | 'int' | 'bool' | 'select';
    help: string | null;
    options: Record<string, string>;
    is_set: boolean;
    value: unknown;
    effective: unknown;
};

const props = defineProps<{
    baseUrl: string;
    namespaces: string[];
    namespace: string;
    fields: Field[];
}>();

const toInput = (field: Field): string => {
    if (!field.is_set || field.value === null || field.value === undefined) return '';
    if (field.type === 'bool') return field.value ? '1' : '0';
    return String(field.value);
};
const form = useForm({
    namespace: props.namespace,
    values: Object.fromEntries(props.fields.map((field) => [field.key, toInput(field)])) as Record<string, string>,
});
const display = (value: unknown): string => (value === null || value === undefined || value === '' ? '—' : String(value));

function go(changes: { namespace?: string }): void {
    router.get(props.baseUrl, { namespace: props.namespace, ...changes });
}

function submit(): void {
    form.put(props.baseUrl, { preserveScroll: true });
}
</script>

<template>
    <Head title="Cấu hình" />
    <PageHeader
        title="Cấu hình"
        subtitle="Cấu hình của cửa hàng. Để trống = dùng giá trị mặc định. Secret không hiển thị lại."
    />

    <div class="mb-4 flex flex-wrap gap-2">
        <select :class="[inputClass, 'w-64']" :value="namespace" @change="go({ namespace: ($event.target as HTMLSelectElement).value })">
            <option v-for="item in namespaces" :key="item" :value="item">{{ item === 'core' ? 'Core' : `Plugin ${item}` }}</option>
        </select>
    </div>

    <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submit">
        <div v-for="field in fields" :key="field.key" class="grid gap-2 md:grid-cols-3">
            <div>
                <div class="font-medium">{{ field.label }}</div>
                <div class="font-mono text-xs text-slate-400">{{ namespace }}.{{ field.key }}</div>
                <div v-if="field.help" class="text-xs text-slate-500">{{ field.help }}</div>
            </div>
            <div>
                <select v-if="field.type === 'select' || field.type === 'bool'" v-model="form.values[field.key]" :class="inputClass">
                    <option value="">(mặc định)</option>
                    <template v-if="field.type === 'bool'">
                        <option value="1">Có</option>
                        <option value="0">Không</option>
                    </template>
                    <option v-for="(label, code) in field.options" v-else :key="code" :value="code">{{ label }}</option>
                </select>
                <textarea v-else-if="field.type === 'text'" v-model="form.values[field.key]" rows="3" :class="inputClass" placeholder="(mặc định)" />
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
