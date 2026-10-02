<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionField, ExtensionFieldValue } from '@admin/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type BlockType = { type: string; label: string; fields: ExtensionField[] };
type Block = { type: string; config: Record<string, ExtensionFieldValue> };

const props = defineProps<{ types: BlockType[]; blocks: Block[]; configured: boolean; previewUrl: string }>();

const form = useForm<{ blocks: Block[] }>({ blocks: props.blocks.map((block) => ({ type: block.type, config: { ...block.config } })) });
const adding = ref(props.types[0]?.type ?? '');
const typeOf = (type: string): BlockType | undefined => props.types.find((candidate) => candidate.type === type);
const errors = computed(() => form.errors as Record<string, string | undefined>);

function add(): void {
    const type = typeOf(adding.value);
    if (!type) return;
    const config: Record<string, ExtensionFieldValue> = {};
    for (const field of type.fields) config[field.key] = field.type === 'bool' ? false : null;
    form.blocks.push({ type: type.type, config });
}

function move(index: number, delta: number): void {
    const [block] = form.blocks.splice(index, 1);
    form.blocks.splice(index + delta, 0, block);
}

function save(): void {
    form.put(window.location.pathname, { preserveScroll: true });
}

function reset(): void {
    if (confirm('Dùng lại trang chủ mặc định của giao diện? Cấu hình khối hiện tại sẽ bị xoá.')) {
        router.delete(window.location.pathname, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Trang chủ" />
    <p class="mb-2 text-sm text-slate-500">Giao diện</p>
    <PageHeader title="Trang chủ" :subtitle="configured ? `${form.blocks.length} khối` : 'Đang dùng trang chủ mặc định của giao diện'">
        <a :href="previewUrl" target="_blank" rel="noopener" :class="secondaryButton">Xem trang</a>
        <button v-if="configured" type="button" :class="dangerButton" @click="reset">Dùng mặc định</button>
        <button type="button" :class="primaryButton" :disabled="form.processing" @click="save">Lưu</button>
    </PageHeader>

    <div class="space-y-4">
        <section v-for="(block, index) in form.blocks" :key="index" class="rounded-lg border border-slate-200 bg-white p-4" :data-block="block.type">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="font-semibold">{{ index + 1 }}. {{ typeOf(block.type)?.label ?? `${block.type} (plugin đã tắt)` }}</h2>
                <div class="flex gap-1 text-sm">
                    <button type="button" :class="secondaryButton" :disabled="index === 0" aria-label="Lên" @click="move(index, -1)">↑</button>
                    <button type="button" :class="secondaryButton" :disabled="index === form.blocks.length - 1" aria-label="Xuống" @click="move(index, 1)">↓</button>
                    <button type="button" :class="dangerButton" @click="form.blocks.splice(index, 1)">Xoá</button>
                </div>
            </div>
            <p v-if="errors[`blocks.${index}.type`]" class="mb-2 text-sm text-red-600">{{ errors[`blocks.${index}.type`] }}</p>
            <div class="grid gap-3 md:grid-cols-2">
                <FormField v-for="field in typeOf(block.type)?.fields ?? []" :key="field.key" :label="field.label + (field.required ? ' *' : '')" :hint="field.help ?? undefined" :error="errors[`blocks.${index}.config.${field.key}`]">
                    <textarea v-if="field.type === 'text'" v-model="block.config[field.key] as string | null" :class="inputClass" rows="3" />
                    <select v-else-if="field.type === 'select'" v-model="block.config[field.key]" :class="inputClass">
                        <option :value="null">—</option>
                        <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <input v-else-if="field.type === 'bool'" v-model="block.config[field.key] as boolean" type="checkbox" />
                    <input v-else v-model="block.config[field.key] as string | null" :class="inputClass" :type="field.type === 'int' ? 'number' : field.type === 'date' ? 'date' : 'text'" />
                </FormField>
            </div>
        </section>

        <p v-if="!form.blocks.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có khối nào. Thêm khối bên dưới.</p>

        <div class="flex gap-2">
            <select v-model="adding" :class="inputClass" class="max-w-xs" aria-label="Loại khối">
                <option v-for="type in types" :key="type.type" :value="type.type">{{ type.label }}</option>
            </select>
            <button type="button" :class="secondaryButton" @click="add">Thêm khối</button>
        </div>
    </div>
</template>
