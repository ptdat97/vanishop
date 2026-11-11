<script setup lang="ts">
import MediaField from '@admin/Components/Media/MediaField.vue';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionField, ExtensionFieldValue } from '@admin/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';
import RichTextEditor from '../../Components/RichTextEditor.vue';

type BlockType = { type: string; label: string; fields: ExtensionField[] };
type Block = { type: string; config: Record<string, ExtensionFieldValue> };

const props = defineProps<{ types: BlockType[]; blocks: Block[]; configured: boolean; previewUrl: string }>();

const form = useForm<{ blocks: Block[] }>({ blocks: props.blocks.map((block) => ({ type: block.type, config: { ...block.config } })) });
const adding = ref(props.types[0]?.type ?? '');
const typeOf = (type: string): BlockType | undefined => props.types.find((candidate) => candidate.type === type);
const errors = computed(() => form.errors as Record<string, string | undefined>);

/*
 * Thu gọn khối để kéo-thả dễ: mở trang thì mọi khối thu gọn (chỉ còn tiêu đề + tóm tắt), khối vừa thêm và khối có lỗi
 * sau khi lưu tự mở. Trạng thái gắn với chính object khối nên đi theo khối khi kéo đổi chỗ.
 */
const expanded = ref(new Set<Block>());
const isExpanded = (block: Block): boolean => expanded.value.has(block);
const allExpanded = computed(() => form.blocks.length > 0 && form.blocks.every(isExpanded));

function toggle(block: Block): void {
    if (expanded.value.has(block)) expanded.value.delete(block);
    else expanded.value.add(block);
}

function toggleAll(): void {
    expanded.value = allExpanded.value ? new Set() : new Set(form.blocks);
}

watch(
    () => form.errors,
    (current) => {
        for (const key of Object.keys(current)) {
            const block = form.blocks[Number(key.split('.')[1])];
            if (key.startsWith('blocks.') && block) expanded.value.add(block);
        }
    },
);

/** Tóm tắt khi thu gọn: "Tên trường: giá trị" của trường chữ/số đầu tiên đã điền (bỏ thẻ HTML), hoặc số trường đã điền. */
function summary(block: Block): string {
    const filled = (value: ExtensionFieldValue): boolean => value !== null && value !== '' && value !== false;
    const fields = typeOf(block.type)?.fields ?? [];
    for (const field of fields) {
        const value = block.config[field.key];
        if (!['string', 'text', 'int', 'select'].includes(field.type) || !filled(value)) continue;
        const plain = String(field.type === 'select' ? (field.options[String(value)] ?? value) : value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        if (plain === '') continue;
        const text = `${field.label}: ${plain}`;
        return text.length > 90 ? `${text.slice(0, 90)}…` : text;
    }
    const count = Object.values(block.config).filter(filled).length;
    return count ? `${count} trường đã điền` : 'Chưa điền nội dung';
}

function add(): void {
    const type = typeOf(adding.value);
    if (!type) return;
    const config: Record<string, ExtensionFieldValue> = {};
    for (const field of type.fields) config[field.key] = field.type === 'bool' ? false : null;
    form.blocks.push({ type: type.type, config });
    expanded.value.add(form.blocks[form.blocks.length - 1]);
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
        <div v-if="form.blocks.length > 1" class="flex items-center justify-between text-sm text-slate-500">
            <span>Kéo tay nắm để đổi thứ tự khối. Thu gọn để kéo dễ hơn.</span>
            <button type="button" class="text-indigo-600 hover:underline" data-toggle-all @click="toggleAll">{{ allExpanded ? 'Thu gọn tất cả' : 'Mở tất cả' }}</button>
        </div>
        <Sortable v-model="form.blocks" v-slot="{ item: block, index, handle }" list-class="space-y-2" item-tag="section" item-class="rounded-lg border border-slate-200 bg-white" :item-label="(block) => typeOf(block.type)?.label ?? block.type">
            <div class="flex items-center justify-between gap-2 px-3 py-2" :data-block="block.type">
                <SortHandle v-bind="handle" />
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-center gap-2 text-left"
                    :aria-expanded="isExpanded(block)"
                    :data-block-toggle="index"
                    @click="toggle(block)"
                >
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="isExpanded(block) ? 'rotate-90' : ''" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.2 14.8a.75.75 0 0 1 0-1.06L10.94 10 7.2 6.26a.75.75 0 1 1 1.06-1.06l4.27 4.27a.75.75 0 0 1 0 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
                    </svg>
                    <span class="shrink-0 font-semibold">{{ index + 1 }}. {{ typeOf(block.type)?.label ?? `${block.type} (plugin đã tắt)` }}</span>
                    <span v-if="!isExpanded(block)" class="truncate text-sm text-slate-500">{{ summary(block) }}</span>
                    <span v-if="!isExpanded(block) && Object.keys(errors).some((key) => key.startsWith(`blocks.${index}.`))" class="shrink-0 text-xs font-medium text-red-600">Có lỗi</span>
                </button>
                <button type="button" class="shrink-0 px-2 text-sm text-red-600 hover:underline" @click="form.blocks.splice(index, 1)">Xoá</button>
            </div>
            <div v-if="isExpanded(block)" class="border-t border-slate-100 p-4">
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
            </div>
        </Sortable>

        <p v-if="!form.blocks.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có khối nào. Thêm khối bên dưới.</p>

        <div class="flex gap-2">
            <div class="w-64"><Select v-model="adding" :options="types.map((type) => ({ value: type.type, label: type.label }))" aria-label="Loại khối" /></div>
            <button type="button" :class="secondaryButton" @click="add">Thêm khối</button>
        </div>
    </div>
</template>
