<script setup lang="ts">
import { t } from '@admin/i18n';
import FileInput from '@admin/Components/FileInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { HttpError, postJson } from '@admin/http';
import { dangerButton, primaryButton, secondaryButton } from '@admin/styles';
import { toast } from '@admin/toast';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';
import BuilderField from '../../Components/BuilderField.vue';
import BuilderPreview from '../../Components/BuilderPreview.vue';
import { emptyBuilderValue, type BuilderBlock as Block, type BuilderField as Field, type BuilderValue } from '../../builderFields';

type BlockType = { type: string; label: string; fields: Field[] };
/** Vùng của bố cục trang (rỗng = trang một vùng); `types`/`max` null = không giới hạn. */
type Zone = { key: string; label: string; slot: string | null; types: string[] | null; max: number | null };
type Surface = { key: string; label: string; url?: string };

const props = defineProps<{
    types: BlockType[]; blocks: Block[]; configured: boolean; previewUrl: string | null; zones: Zone[];
    draftUrls: { preview: string; import: string };
    allowedZones: Record<string, string[]>;
    /** Surface của Builder Engine (trang chủ, trang sản phẩm, surface plugin…); trang bộ sưu tập dùng collectionName. */
    surfaces?: Surface[]; surface?: Surface; collectionName?: string; collectionUrl?: string | null;
}>();

const form = useForm<{ blocks: Block[] }>({ blocks: props.blocks.map((block) => ({ ...block, config: { ...block.config } })) });
const collectionPage = computed(() => props.collectionName !== undefined);
const title = computed(() => collectionPage.value
    ? t('storefront::admin.collection_pages.title', { name: props.collectionName ?? '' })
    : (props.surface?.label ?? t('storefront::admin.design_home_blocks.home_page')));
const home = computed(() => props.surface?.key === 'home');
const zoneLabel = (key: string): string => props.zones.find((zone) => zone.key === key)?.label ?? key;
const hasSlotZones = computed(() => props.zones.some((zone) => zone.slot !== null));
const subtitle = computed(() => {
    if (collectionPage.value || props.configured) return t('storefront::admin.design_home_blocks.count_blocks', { count: form.blocks.length });

    return home.value ? t('storefront::admin.design_home_blocks.using_theme_s_default_home') : t('storefront::admin.builder.not_configured');
});
const zoned = computed(() => props.zones.length > 0);
const accepts = (zone: Zone, type: string): boolean => props.allowedZones[type]?.includes(zone.key) ?? false;
const zoneOptions = (type: string) => props.zones.filter((zone) => accepts(zone, type)).map((zone) => ({ value: zone.key, label: zone.label }));
const availableTypes = computed(() => props.types.filter((type) => props.allowedZones[type.type]?.length > 0));
const adding = ref(availableTypes.value[0]?.type ?? '');
const typeOf = (type: string): BlockType | undefined => props.types.find((candidate) => candidate.type === type);
const errors = computed(() => form.errors as Record<string, string | undefined>);
const previewOpen = ref(false);
const importing = ref(false);

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
    const filled = (value: BuilderValue | undefined): boolean => Array.isArray(value) ? value.length > 0 : value !== undefined && value !== null && value !== '' && value !== false;
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

function addBlock(block: Block, after = form.blocks.length - 1): void {
    if (!props.allowedZones[block.type]?.length) {
        toast(t('storefront::admin.builder.no_zone_available'), 'error');
        return;
    }
    if (zoned.value) {
        const candidates = props.zones.filter((zone) => accepts(zone, block.type)
            && (zone.max === null || form.blocks.filter((item) => item.zone === zone.key).length < zone.max));
        const zone = candidates.find((candidate) => candidate.key === block.zone) ?? candidates[0];
        if (!zone) {
            toast(t('storefront::admin.builder.no_zone_available'), 'error');
            return;
        }
        block.zone = zone.key;
    }
    form.blocks.splice(after + 1, 0, block);
    expanded.value.add(form.blocks[after + 1]);
}

function add(): void {
    const type = typeOf(adding.value);
    if (!type) return;
    addBlock({ type: type.type, config: Object.fromEntries(type.fields.map((field) => [field.key, emptyBuilderValue(field)])) });
}

function duplicate(block: Block, index: number): void {
    addBlock(JSON.parse(JSON.stringify(block)) as Block, index);
}

function exportLayout(): void {
    const url = URL.createObjectURL(new Blob([JSON.stringify({ version: 1, blocks: form.blocks }, null, 2)], { type: 'application/json' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `builder-${props.surface?.key ?? 'collection'}.json`;
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 0);
}

async function importLayout([file]: File[]): Promise<void> {
    if (!file || importing.value) return;

    importing.value = true;
    try {
        const data = JSON.parse(await file.text());
        if (data === null || typeof data !== 'object' || Array.isArray(data)) throw new Error();
        const result = await postJson<{ blocks: Block[] }>(props.draftUrls.import, data);
        if (form.blocks.length > 0 && (await confirmAction({
            title: t('storefront::admin.builder.import_layout'),
            message: t('storefront::admin.builder.replace_draft'),
            confirmLabel: t('storefront::admin.builder.import_layout'),
        })) === false) return;

        form.clearErrors();
        form.blocks = result.blocks;
        expanded.value = new Set();
        toast(t('storefront::admin.builder.import_ready'), 'success');
    } catch (failure) {
        toast(failure instanceof HttpError ? Object.values(failure.errors).flat()[0] ?? failure.message : t('storefront::admin.builder.import_failed'), 'error');
    } finally {
        importing.value = false;
    }
}

function save(): void {
    form.put(window.location.pathname, { preserveScroll: true });
}

async function reset(): Promise<void> {
    const title = home.value ? t('storefront::admin.design_home_blocks.use_default_home_page_again') : t('storefront::admin.builder.reset_title');
    const confirmLabel = home.value ? t('storefront::admin.design_home_blocks.use_default') : t('storefront::admin.builder.reset');
    if ((await confirmAction({ title, message: t('storefront::admin.design_home_blocks.current_block_configuration_deleted'), confirmLabel, tone: 'danger' })) !== false) {
        router.delete(window.location.pathname, { preserveScroll: true, preserveState: false });
    }
}
</script>

<template>
    <div>
        <Head :title="title" />
        <p class="mb-2 text-sm text-slate-500">{{ t('storefront::admin.design_home_blocks.storefront_design') }}</p>
        <PageHeader :title="title" :subtitle="subtitle">
            <Link v-if="collectionUrl" :href="collectionUrl" :class="secondaryButton">{{ t('storefront::admin.collection_pages.back') }}</Link>
            <a v-if="previewUrl" :href="previewUrl" target="_blank" rel="noopener" :class="secondaryButton">{{ t('storefront::admin.design_home_blocks.view_page') }}</a>
            <button type="button" :class="secondaryButton" @click="previewOpen = true">{{ t('storefront::admin.builder.responsive_preview') }}</button>
            <button v-if="configured && !collectionPage" type="button" :class="dangerButton" @click="reset">{{ home ? t('storefront::admin.design_home_blocks.use_default') : t('storefront::admin.builder.reset') }}</button>
            <button type="button" :class="primaryButton" :disabled="form.processing" @click="save">{{ t('storefront::admin.design_home_blocks.save') }}</button>
        </PageHeader>
        <template v-if="!collectionPage">
            <DesignTabs active="builder" />
            <nav v-if="(surfaces?.length ?? 0) > 1" class="mb-4 flex flex-wrap gap-2 text-sm" :aria-label="t('storefront::admin.builder.surface_nav')" data-builder-surfaces>
                <Link
                    v-for="candidate in surfaces"
                    :key="candidate.key"
                    :href="candidate.url ?? ''"
                    class="rounded-full border px-3 py-1"
                    :class="candidate.key === surface?.key ? 'border-indigo-600 bg-indigo-50 font-medium text-indigo-700' : 'border-slate-200 text-slate-600 hover:text-slate-900'"
                    :aria-current="candidate.key === surface?.key ? 'page' : undefined"
                >{{ candidate.label }}</Link>
            </nav>
            <p v-if="hasSlotZones" class="mb-4 text-sm text-slate-600">{{ t('storefront::admin.builder.slot_help') }}</p>
        </template>
        <p v-else class="mb-4 text-sm text-slate-600">{{ t('storefront::admin.collection_pages.help') }}</p>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <FileInput compact accept=".json,application/json" :max-kb="1024" :label="t('storefront::admin.builder.import_layout')" :disabled="importing || form.processing" @pick="importLayout">
                    <span :class="secondaryButton">{{ t('storefront::admin.builder.import_layout') }}</span>
                </FileInput>
                <button type="button" :class="secondaryButton" @click="exportLayout">{{ t('storefront::admin.builder.export_layout') }}</button>
            </div>
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
                        <span v-if="zoned && block.zone" class="truncate text-sm text-slate-500">{{ zoneLabel(block.zone) }}</span>
                        <span v-if="!isExpanded(block)" class="truncate text-sm text-slate-500">{{ summary(block) }}</span>
                        <span v-if="!isExpanded(block) && Object.keys(errors).some((key) => key.startsWith(`blocks.${index}.`))" class="shrink-0 text-xs font-medium text-red-600">{{ t('storefront::admin.design_home_blocks.has_errors') }}</span>
                    </button>
                    <button v-if="typeOf(block.type)" type="button" class="shrink-0 px-2 text-sm text-indigo-700 hover:underline" @click="duplicate(block, index)">{{ t('storefront::admin.builder.duplicate') }}</button>
                    <button type="button" class="shrink-0 px-2 text-sm text-red-600 hover:underline" @click="form.blocks.splice(index, 1)">{{ t('storefront::admin.design_home_blocks.delete') }}</button>
                </div>
                <div v-if="isExpanded(block)" class="border-t border-slate-100 p-4">
                <p v-if="errors[`blocks.${index}.type`]" class="mb-2 text-sm text-red-600">{{ errors[`blocks.${index}.type`] }}</p>
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField v-if="zoned" :label="t('storefront::admin.collection_pages.zone')" :error="errors[`blocks.${index}.zone`]">
                        <Select v-model="block.zone as string" :options="zoneOptions(block.type)" />
                    </FormField>
                    <BuilderField
                        v-for="field in typeOf(block.type)?.fields ?? []"
                        :key="field.key"
                        v-model="block.config[field.key]"
                        :field="field"
                        :path="`blocks.${index}.config.${field.key}`"
                        :rich-text="block.type === 'rich_text' && field.key === 'body'"
                        :errors="errors"
                    />
                </div>
                </div>
            </Sortable>

            <p v-if="!form.blocks.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">{{ t('storefront::admin.design_home_blocks.no_blocks_yet_add_block') }}</p>

            <div class="flex gap-2">
                <div class="w-64"><Select v-model="adding" :options="availableTypes.map((type) => ({ value: type.type, label: type.label }))" :aria-label="t('storefront::admin.design_home_blocks.block_type')" /></div>
                <button type="button" :class="secondaryButton" :disabled="!availableTypes.length" @click="add">{{ t('storefront::admin.design_home_blocks.add_block') }}</button>
            </div>
        </div>
        <BuilderPreview
            v-model:open="previewOpen"
            :blocks="form.blocks"
            :url="draftUrls.preview"
            :product-context="zones.some((zone) => zone.slot?.startsWith('vani.storefront.pdp.'))"
            :listing-context="zones.some((zone) => zone.slot?.startsWith('vani.storefront.plp.'))"
        />
    </div>
</template>
