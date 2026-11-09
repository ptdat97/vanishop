<script setup lang="ts">
import MediaField from '@admin/Components/Media/MediaField.vue';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionField, ExtensionFieldValue } from '@admin/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';
import RichTextEditor from '../../Components/RichTextEditor.vue';

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

async function reset(): Promise<void> {
    if ((await confirmAction({ title: 'Dùng lại trang chủ mặc định?', message: 'Cấu hình khối hiện tại sẽ bị xoá.', confirmLabel: 'Dùng mặc định', tone: 'danger' })) !== false) {
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
    <DesignTabs active="home" />

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
                    <RichTextEditor v-if="block.type === 'rich_text' && field.key === 'body'" v-model="block.config[field.key] as string | null" />
                    <textarea v-else-if="field.type === 'text'" v-model="block.config[field.key] as string | null" :class="inputClass" rows="3" />
                    <Select v-else-if="field.type === 'select'" v-model="block.config[field.key] as string | null" :options="labelOptions(field.options)" empty-label="—" :empty-value="null" />
                    <DateInput v-else-if="field.type === 'date'" v-model="block.config[field.key] as string | null" />
                    <input v-else-if="field.type === 'bool'" v-model="block.config[field.key] as boolean" type="checkbox" />
                    <MediaField v-else-if="field.type === 'media' || field.type === 'video'" v-model="block.config[field.key] as number | null" :kind="field.type === 'video' ? 'video' : 'image'" :label="field.label.toLowerCase()" />
                    <input v-else v-model="block.config[field.key] as string | null" :class="inputClass" :type="field.type === 'int' ? 'number' : 'text'" />
                </FormField>
            </div>
        </section>

        <p v-if="!form.blocks.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có khối nào. Thêm khối bên dưới.</p>

        <div class="flex gap-2">
            <div class="w-64"><Select v-model="adding" :options="types.map((type) => ({ value: type.type, label: type.label }))" aria-label="Loại khối" /></div>
            <button type="button" :class="secondaryButton" @click="add">Thêm khối</button>
        </div>
    </div>
</template>
