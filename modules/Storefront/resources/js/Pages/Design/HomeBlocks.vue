<script setup lang="ts">
import { t } from '@admin/i18n';
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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';
import RichTextEditor from '../../Components/RichTextEditor.vue';

type BlockType = { type: string; label: string; fields: ExtensionField[] };
type Block = { type: string; config: Record<string, ExtensionFieldValue>; zone?: string };

const props = defineProps<{ types: BlockType[]; blocks: Block[]; configured: boolean; previewUrl: string | null; collectionName?: string; collectionUrl?: string | null }>();

const form = useForm<{ blocks: Block[] }>({ blocks: props.blocks.map((block) => ({ ...block, config: { ...block.config } })) });
const collectionPage = computed(() => props.collectionName !== undefined);
const title = computed(() => collectionPage.value
    ? t('storefront::admin.collection_pages.title', { name: props.collectionName ?? '' })
    : t('storefront::admin.design_home_blocks.home_page'));
const zones = ['banner', 'before', 'after'];
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
    return count ? t('storefront::admin.design_home_blocks.count_fields_filled', { count }) : t('storefront::admin.design_home_blocks.no_content_yet');
}

function add(): void {
    const type = typeOf(adding.value);
    if (!type) return;
    const config: Record<string, ExtensionFieldValue> = {};
    for (const field of type.fields) config[field.key] = field.type === 'bool' ? false : null;
    const zone = type.type === 'hero' && !form.blocks.some((block) => block.zone === 'banner') ? 'banner' : 'before';
    form.blocks.push({ type: type.type, config, ...(collectionPage.value ? { zone } : {}) });
    expanded.value.add(form.blocks[form.blocks.length - 1]);
}

function save(): void {
    form.put(window.location.pathname, { preserveScroll: true });
}

async function reset(): Promise<void> {
    if ((await confirmAction({ title: t('storefront::admin.design_home_blocks.use_default_home_page_again'), message: t('storefront::admin.design_home_blocks.current_block_configuration_deleted'), confirmLabel: t('storefront::admin.design_home_blocks.use_default'), tone: 'danger' })) !== false) {
        router.delete(window.location.pathname, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="title" />
    <p class="mb-2 text-sm text-slate-500">{{ t('storefront::admin.design_home_blocks.storefront_design') }}</p>
    <PageHeader :title="title" :subtitle="collectionPage || configured ? t('storefront::admin.design_home_blocks.count_blocks', { count: form.blocks.length }) : t('storefront::admin.design_home_blocks.using_theme_s_default_home')">
        <Link v-if="collectionUrl" :href="collectionUrl" :class="secondaryButton">{{ t('storefront::admin.collection_pages.back') }}</Link>
        <a v-if="previewUrl" :href="previewUrl" target="_blank" rel="noopener" :class="secondaryButton">{{ t('storefront::admin.design_home_blocks.view_page') }}</a>
        <button v-if="configured && !collectionPage" type="button" :class="dangerButton" @click="reset">{{ t('storefront::admin.design_home_blocks.use_default') }}</button>
        <button type="button" :class="primaryButton" :disabled="form.processing" @click="save">{{ t('storefront::admin.design_home_blocks.save') }}</button>
    </PageHeader>
    <DesignTabs v-if="!collectionPage" active="home" />
    <p v-else class="mb-4 text-sm text-slate-600">{{ t('storefront::admin.collection_pages.help') }}</p>

    <div class="space-y-4">
        <div v-if="form.blocks.length > 1" class="flex items-center justify-between text-sm text-slate-500">
            <span>{{ t('storefront::admin.design_home_blocks.drag_handle_reorder_blocks_collapse') }}</span>
            <button type="button" class="text-indigo-600 hover:underline" data-toggle-all @click="toggleAll">{{ allExpanded ? t('storefront::admin.design_home_blocks.collapse_all') : t('storefront::admin.design_home_blocks.expand_all') }}</button>
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
                    <span class="shrink-0 font-semibold">{{ index + 1 }}. {{ typeOf(block.type)?.label ?? t('storefront::admin.design_home_blocks.type_plugin_disabled', { type: block.type }) }}</span>
                    <span v-if="collectionPage && block.zone" class="truncate text-sm text-slate-500">{{ t(`storefront::admin.collection_pages.${block.zone}`) }}</span>
                    <span v-if="!isExpanded(block)" class="truncate text-sm text-slate-500">{{ summary(block) }}</span>
                    <span v-if="!isExpanded(block) && Object.keys(errors).some((key) => key.startsWith(`blocks.${index}.`))" class="shrink-0 text-xs font-medium text-red-600">{{ t('storefront::admin.design_home_blocks.has_errors') }}</span>
                </button>
                <button type="button" class="shrink-0 px-2 text-sm text-red-600 hover:underline" @click="form.blocks.splice(index, 1)">{{ t('storefront::admin.design_home_blocks.delete') }}</button>
            </div>
            <div v-if="isExpanded(block)" class="border-t border-slate-100 p-4">
            <p v-if="errors[`blocks.${index}.type`]" class="mb-2 text-sm text-red-600">{{ errors[`blocks.${index}.type`] }}</p>
            <div class="grid gap-3 md:grid-cols-2">
                <FormField v-if="collectionPage" :label="t('storefront::admin.collection_pages.zone')" :error="errors[`blocks.${index}.zone`]">
                    <Select v-model="block.zone" :options="zones.filter((zone) => zone !== 'banner' || block.type === 'hero').map((zone) => ({ value: zone, label: t(`storefront::admin.collection_pages.${zone}`) }))" />
                </FormField>
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

        <p v-if="!form.blocks.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">{{ t('storefront::admin.design_home_blocks.no_blocks_yet_add_block') }}</p>

        <div class="flex gap-2">
            <div class="w-64"><Select v-model="adding" :options="types.map((type) => ({ value: type.type, label: type.label }))" :aria-label="t('storefront::admin.design_home_blocks.block_type')" /></div>
            <button type="button" :class="secondaryButton" @click="add">{{ t('storefront::admin.design_home_blocks.add_block') }}</button>
        </div>
    </div>
</template>
