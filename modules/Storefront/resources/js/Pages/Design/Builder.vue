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
import {
    dangerButton,
    primaryButton,
    secondaryButton,
} from '@admin/styles';
import { toast } from '@admin/toast';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import DesignTabs from '../../Components/DesignTabs.vue';
import BuilderField from '../../Components/BuilderField.vue';
import BuilderPreview from '../../Components/BuilderPreview.vue';
import {
    emptyBuilderValue,
    type BuilderBlock as Block,
    type BuilderField as Field,
    type BuilderValue,
} from '../../builderFields';

/** `plugin` = id plugin khai báo (null = hệ thống); server đã xếp hệ thống trước, plugin sau. */
type BlockType = {
    type: string;
    label: string;
    plugin?: string | null;
    fields: Field[];
};

type Zone = {
    key: string;
    label: string;
    slot: string | null;
    types: string[] | null;
    max: number | null;
};

type Surface = {
    key: string;
    label: string;
    plugin?: string | null;
    url?: string;
};

type SelectOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    types: BlockType[];
    blocks: Block[];
    configured: boolean;
    previewUrl: string | null;
    zones: Zone[];
    draftUrls: {
        preview: string;
        import: string;
    };
    allowedZones: Record<string, string[]>;
    surfaces?: Surface[];
    surface?: Surface;
    collectionName?: string;
    collectionUrl?: string | null;
}>();

// --------------------------------------------------
// JSON cloning
// --------------------------------------------------

/**
 * Builder blocks are JSON-compatible data.
 * JSON cloning avoids structuredClone DataCloneError
 * when Vue reactive proxies are present.
 */
function cloneBlocks(blocks: Block[]): Block[] {
    return JSON.parse(JSON.stringify(blocks)) as Block[];
}

// --------------------------------------------------
// Form
// --------------------------------------------------

const form = useForm<{ blocks: Block[] }>({
    blocks: cloneBlocks(props.blocks),
});

const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const importing = ref(false);
const previewOpen = ref(false);

const busy = computed(
    () => importing.value || form.processing,
);

// --------------------------------------------------
// Page metadata
// --------------------------------------------------

const collectionPage = computed(
    () => props.collectionName !== undefined,
);

const home = computed(
    () => props.surface?.key === 'home',
);

const title = computed(() =>
    collectionPage.value
        ? t('storefront::admin.collection_pages.title', {
            name: props.collectionName ?? '',
        })
        : (
            props.surface?.label ??
            t('storefront::admin.design_home_blocks.home_page')
        ),
);

const subtitle = computed(() => {
    if (collectionPage.value || props.configured) {
        return t(
            'storefront::admin.design_home_blocks.count_blocks',
            { count: form.blocks.length },
        );
    }

    return home.value
        ? t(
            'storefront::admin.design_home_blocks.using_theme_s_default_home',
        )
        : t('storefront::admin.builder.not_configured');
});

// --------------------------------------------------
// Type and zone lookup
// --------------------------------------------------

const typesByKey = computed(
    () => new Map(
        props.types.map(type => [type.type, type]),
    ),
);

const zonesByKey = computed(
    () => new Map(
        props.zones.map(zone => [zone.key, zone]),
    ),
);

const zoned = computed(
    () => props.zones.length > 0,
);

const hasSlotZones = computed(
    () => props.zones.some(zone => zone.slot !== null),
);

const productContext = computed(
    () => props.zones.some(
        zone => zone.slot?.startsWith('vani.storefront.pdp.'),
    ),
);

const listingContext = computed(
    () => props.zones.some(
        zone => zone.slot?.startsWith('vani.storefront.plp.'),
    ),
);

function typeOf(type: string): BlockType | undefined {
    return typesByKey.value.get(type);
}

function zoneLabel(key: string): string {
    return zonesByKey.value.get(key)?.label ?? key;
}

function accepts(zone: Zone, type: string): boolean {
    return props.allowedZones[type]?.includes(zone.key) ?? false;
}

const availableTypes = computed(() =>
    props.types.filter(
        type => (props.allowedZones[type.type]?.length ?? 0) > 0,
    ),
);

const adding = ref(availableTypes.value[0]?.type ?? '');

watch(
    availableTypes,
    types => {
        if (!types.some(type => type.type === adding.value)) {
            adding.value = types[0]?.type ?? '';
        }
    },
);

const typeOptions = computed<SelectOption[]>(() =>
    availableTypes.value.map(type => ({
        value: type.type,
        label: type.plugin ? `${type.label} · ${t('storefront::admin.builder.plugin')}` : type.label,
    })),
);

// --------------------------------------------------
// Expanded state
// --------------------------------------------------

const expanded = ref<Set<Block>>(new Set());

function isExpanded(block: Block): boolean {
    return expanded.value.has(block);
}

const allExpanded = computed(
    () =>
        form.blocks.length > 0 &&
        form.blocks.every(block => expanded.value.has(block)),
);

function toggle(block: Block): void {
    const next = new Set(expanded.value);

    if (next.has(block)) {
        next.delete(block);
    } else {
        next.add(block);
    }

    expanded.value = next;
}

function toggleAll(): void {
    expanded.value = allExpanded.value
        ? new Set<Block>()
        : new Set(form.blocks);
}

function expandBlock(block: Block): void {
    expanded.value = new Set([
        ...expanded.value,
        block,
    ]);
}

// --------------------------------------------------
// Validation
// --------------------------------------------------

const errorBlockIndexes = computed(() => {
    const indexes = new Set<number>();

    for (const key of Object.keys(errors.value)) {
        const match = /^blocks\.(\d+)(?:\.|$)/.exec(key);

        if (match) {
            indexes.add(Number(match[1]));
        }
    }

    return indexes;
});

function hasBlockError(index: number): boolean {
    return errorBlockIndexes.value.has(index);
}

watch(
    errorBlockIndexes,
    indexes => {
        if (!indexes.size) return;

        const next = new Set(expanded.value);

        for (const index of indexes) {
            const block = form.blocks[index];

            if (block) {
                next.add(block);
            }
        }

        expanded.value = next;
    },
    { immediate: true },
);

// --------------------------------------------------
// Block summary
// --------------------------------------------------

function isFilled(value: BuilderValue | undefined): boolean {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    return (
        value !== undefined &&
        value !== null &&
        value !== '' &&
        value !== false
    );
}

function summary(block: Block): string {
    const fields = typeOf(block.type)?.fields ?? [];

    for (const field of fields) {
        const value = block.config[field.key];

        if (
            !['string', 'text', 'int', 'select'].includes(field.type) ||
            !isFilled(value)
        ) {
            continue;
        }

        const displayValue =
            field.type === 'select'
                ? field.options[String(value)] ?? value
                : value;

        const plain = String(displayValue)
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!plain) continue;

        const text = `${field.label}: ${plain}`;

        return text.length > 90
            ? `${text.slice(0, 90)}…`
            : text;
    }

    const count = Object.values(block.config)
        .filter(isFilled)
        .length;

    return count
        ? t(
            'storefront::admin.design_home_blocks.count_fields_filled',
            { count },
        )
        : t(
            'storefront::admin.design_home_blocks.no_content_yet',
        );
}

// --------------------------------------------------
// Zone capacity
// --------------------------------------------------

function zoneCount(key: string): number {
    return form.blocks.filter(
        block => block.zone === key,
    ).length;
}

function canPlaceInZone(
    zone: Zone,
    type: string,
    currentBlock?: Block,
): boolean {
    if (!accepts(zone, type)) {
        return false;
    }

    if (zone.max === null) {
        return true;
    }

    const occupied = zoneCount(zone.key);

    const currentOccupiesZone =
        currentBlock !== undefined &&
        form.blocks.includes(currentBlock) &&
        currentBlock.zone === zone.key;

    return occupied - (currentOccupiesZone ? 1 : 0) < zone.max;
}

function availableZoneOptions(block: Block): SelectOption[] {
    return props.zones
        .filter(zone => canPlaceInZone(zone, block.type, block))
        .map(zone => ({
            value: zone.key,
            label: zone.label,
        }));
}

// --------------------------------------------------
// Block operations
// --------------------------------------------------

function addBlock(
    block: Block,
    after = form.blocks.length - 1,
): void {
    if (busy.value) return;

    if (!props.allowedZones[block.type]?.length) {
        toast(
            t('storefront::admin.builder.no_zone_available'),
            'error',
        );
        return;
    }

    if (zoned.value) {
        const candidates = props.zones.filter(
            zone => canPlaceInZone(zone, block.type),
        );

        const zone =
            candidates.find(candidate => candidate.key === block.zone) ??
            candidates[0];

        if (!zone) {
            toast(
                t('storefront::admin.builder.no_zone_available'),
                'error',
            );
            return;
        }

        block.zone = zone.key;
    }

    const index = Math.max(
        0,
        Math.min(after + 1, form.blocks.length),
    );

    form.blocks.splice(index, 0, block);

    expandBlock(block);
}

function add(): void {
    const type = typeOf(adding.value);

    if (!type || busy.value) return;

    addBlock({
        type: type.type,
        config: Object.fromEntries(
            type.fields.map(field => [
                field.key,
                emptyBuilderValue(field),
            ]),
        ),
    });
}

function duplicate(block: Block, index: number): void {
    if (busy.value) return;

    addBlock(cloneBlocks([block])[0], index);
}

function removeBlock(block: Block): void {
    if (busy.value) return;

    const index = form.blocks.indexOf(block);

    if (index === -1) return;

    form.blocks.splice(index, 1);

    const next = new Set(expanded.value);
    next.delete(block);
    expanded.value = next;

    form.clearErrors();
}

// --------------------------------------------------
// Import / Export
// --------------------------------------------------

function exportLayout(): void {
    if (busy.value) return;

    const payload = {
        version: 1,
        blocks: form.blocks,
    };

    const blob = new Blob(
        [JSON.stringify(payload, null, 2)],
        { type: 'application/json' },
    );

    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');

    link.href = url;
    link.download = `builder-${props.surface?.key ?? 'collection'}.json`;

    document.body.appendChild(link);
    link.click();
    link.remove();

    window.setTimeout(
        () => URL.revokeObjectURL(url),
        0,
    );
}

async function importLayout([file]: File[]): Promise<void> {
    if (!file || busy.value) return;

    importing.value = true;

    try {
        const parsed: unknown = JSON.parse(await file.text());

        if (
            parsed === null ||
            typeof parsed !== 'object' ||
            Array.isArray(parsed)
        ) {
            throw new Error('Invalid layout');
        }

        const data = parsed as Record<string, unknown>;

        if (form.blocks.length > 0) {
            const confirmed = await confirmAction({
                title: t('storefront::admin.builder.import_layout'),
                message: t('storefront::admin.builder.replace_draft'),
                confirmLabel: t('storefront::admin.builder.import_layout'),
            });

            if (confirmed === false) return;
        }

        const result = await postJson<{ blocks: Block[] }>(
            props.draftUrls.import,
            data,
        );

        form.clearErrors();
        form.blocks = cloneBlocks(result.blocks);
        expanded.value = new Set<Block>();

        toast(
            t('storefront::admin.builder.import_ready'),
            'success',
        );
    } catch (failure) {
        const message =
            failure instanceof HttpError
                ? Object.values(failure.errors).flat()[0] ??
                failure.message
                : t('storefront::admin.builder.import_failed');

        toast(message, 'error');
    } finally {
        importing.value = false;
    }
}

// --------------------------------------------------
// Save / Reset
// --------------------------------------------------

function save(): void {
    if (busy.value) return;

    form.put(window.location.pathname, {
        preserveScroll: true,
    });
}

async function reset(): Promise<void> {
    if (busy.value) return;

    const resetTitle = home.value
        ? t(
            'storefront::admin.design_home_blocks.use_default_home_page_again',
        )
        : t('storefront::admin.builder.reset_title');

    const confirmLabel = home.value
        ? t('storefront::admin.design_home_blocks.use_default')
        : t('storefront::admin.builder.reset');

    const confirmed = await confirmAction({
        title: resetTitle,
        message: t(
            'storefront::admin.design_home_blocks.current_block_configuration_deleted',
        ),
        confirmLabel,
        tone: 'danger',
    });

    if (confirmed === false || busy.value) return;

    router.delete(window.location.pathname, {
        preserveScroll: true,
        preserveState: false,
    });
}
</script>

<template>
    <div class="min-w-0">

        <Head :title="title" />

        <!-- Page heading -->
        <p class="mb-2 text-sm text-slate-500">
            {{
                t(
                    'storefront::admin.design_home_blocks.storefront_design',
                )
            }}
        </p>

        <PageHeader :title="title" :subtitle="subtitle">
            <Link v-if="collectionUrl" :href="collectionUrl" :class="secondaryButton">
                {{ t('storefront::admin.collection_pages.back') }}
            </Link>

            <a v-if="previewUrl" :href="previewUrl" target="_blank" rel="noopener noreferrer" :class="secondaryButton">
                {{
                    t(
                        'storefront::admin.design_home_blocks.view_page',
                    )
                }}
            </a>

            <button type="button" :class="secondaryButton" :disabled="busy" @click="previewOpen = true">
                {{ t('storefront::admin.builder.responsive_preview') }}
            </button>

            <button v-if="configured && !collectionPage" type="button" :class="dangerButton" :disabled="busy"
                @click="reset">
                {{
                    home
                        ? t(
                            'storefront::admin.design_home_blocks.use_default',
                        )
                        : t('storefront::admin.builder.reset')
                }}
            </button>

            <button type="button" :class="primaryButton" :disabled="busy" @click="save">
                {{
                    t(
                        'storefront::admin.design_home_blocks.save',
                    )
                }}
            </button>
        </PageHeader>

        <!-- Design navigation -->
        <template v-if="!collectionPage">
            <DesignTabs active="builder" />

            <nav v-if="(surfaces?.length ?? 0) > 1" class="mb-4 flex flex-wrap gap-2 text-sm"
                :aria-label="t('storefront::admin.builder.surface_nav')" data-builder-surfaces>
                <Link v-for="(candidate, index) in surfaces" :key="candidate.key" :href="candidate.url ?? ''"
                    class="rounded-full border px-3 py-1.5 transition-colors"
                    :data-builder-surface-plugin="candidate.plugin ?? undefined"
                    :style="candidate.plugin && !surfaces?.[index - 1]?.plugin ? 'margin-inline-start: 0.75rem' : undefined"
                    :class="candidate.key === surface?.key
                            ? 'border-indigo-600 bg-indigo-50 font-medium text-indigo-700'
                            : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:text-slate-900'
                        " :aria-current="candidate.key === surface?.key
                            ? 'page'
                            : undefined
                        ">
                    {{ candidate.label }}
                    <span v-if="candidate.plugin" class="ms-1 text-[10px] tracking-[0.06em] text-slate-400 uppercase">{{ t('storefront::admin.builder.plugin') }}</span>
                </Link>
            </nav>

            <p v-if="hasSlotZones" class="mb-4 text-sm text-slate-600">
                {{ t('storefront::admin.builder.slot_help') }}
            </p>
        </template>

        <p v-else class="mb-4 text-sm text-slate-600">
            {{ t('storefront::admin.collection_pages.help') }}
        </p>

        <div class="space-y-4">

            <!-- Import / Export -->
            <div class="grid grid-cols-2 gap-2">
                <div class="min-w-0 [&_[data-file-input]]:w-full [&_label]:w-full">
                    <FileInput compact accept=".json,application/json" :max-kb="1024"
                        :label="t('storefront::admin.builder.import_layout')" :disabled="busy" @pick="importLayout">
                        <span :class="[
                            secondaryButton,
                            'flex h-10 w-full items-center justify-center text-center',
                        ]">
                            {{
                                t(
                                    'storefront::admin.builder.import_layout',
                                )
                            }}
                        </span>
                    </FileInput>
                </div>

                <button type="button" :class="[
                    secondaryButton,
                    'flex h-10 w-full items-center justify-center text-center',
                ]" :disabled="busy" @click="exportLayout">
                    {{
                        t(
                            'storefront::admin.builder.export_layout',
                        )
                    }}
                </button>
            </div>

            <!-- Reorder helper -->
            <div v-if="form.blocks.length > 1"
                class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-500">
                <span>
                    {{
                        t(
                            'storefront::admin.design_home_blocks.drag_handle_reorder_blocks_collapse',
                        )
                    }}
                </span>

                <button type="button" class="shrink-0 font-medium text-indigo-600 hover:underline disabled:opacity-50"
                    data-toggle-all :disabled="busy" @click="toggleAll">
                    {{
                        allExpanded
                            ? t(
                                'storefront::admin.design_home_blocks.collapse_all',
                            )
                            : t(
                                'storefront::admin.design_home_blocks.expand_all',
                            )
                    }}
                </button>
            </div>

            <!-- Blocks -->
            <Sortable v-model="form.blocks" v-slot="{ item: block, index, handle }" list-class="space-y-2"
                item-tag="section"
                item-class="min-w-0 rounded-lg border border-slate-200 bg-white transition-colors hover:border-slate-300"
                :item-label="(block) => typeOf(block.type)?.label ?? block.type
                    ">
                <!-- Block header -->
                <div class="flex min-w-0 items-center gap-2 px-3 py-2.5" :data-block="block.type">
                    <SortHandle v-bind="handle" />

                    <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left"
                        :aria-expanded="isExpanded(block)" :data-block-toggle="index" @click="toggle(block)">
                        <svg viewBox="0 0 20 20" fill="currentColor"
                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                            :class="isExpanded(block) ? 'rotate-90' : ''" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.2 14.8a.75.75 0 0 1 0-1.06L10.94 10 7.2 6.26a.75.75 0 1 1 1.06-1.06l4.27 4.27a.75.75 0 0 1 0 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0Z"
                                clip-rule="evenodd" />
                        </svg>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-800">
                                {{ index + 1 }}.
                                {{
                                    typeOf(block.type)?.label ??
                                    t(
                                        'storefront::admin.design_home_blocks.type_plugin_disabled',
                                        { type: block.type },
                                    )
                                }}
                                <span v-if="typeOf(block.type)?.plugin" class="ms-1 align-middle text-[10px] font-medium tracking-[0.06em] text-slate-400 uppercase">{{ t('storefront::admin.builder.plugin') }}</span>
                            </span>

                            <span v-if="zoned && block.zone" class="block truncate text-xs text-slate-500">
                                {{ zoneLabel(block.zone) }}
                            </span>

                            <span v-if="!isExpanded(block)" class="block truncate text-xs text-slate-500">
                                {{ summary(block) }}
                            </span>
                        </span>

                        <span v-if="hasBlockError(index)" class="shrink-0 text-xs font-medium text-red-600">
                            {{
                                t(
                                    'storefront::admin.design_home_blocks.has_errors',
                                )
                            }}
                        </span>
                    </button>

                    <!-- Block actions -->
                    <div class="flex shrink-0 items-center gap-1">
                        <button v-if="typeOf(block.type)" type="button"
                            class="rounded px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                            :disabled="busy" @click="duplicate(block, index)">
                            {{
                                t(
                                    'storefront::admin.builder.duplicate',
                                )
                            }}
                        </button>

                        <button type="button"
                            class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50"
                            :disabled="busy" @click="removeBlock(block)">
                            {{
                                t(
                                    'storefront::admin.design_home_blocks.delete',
                                )
                            }}
                        </button>
                    </div>
                </div>

                <!-- Block editor -->
                <div v-if="isExpanded(block)" class="border-t border-slate-100 p-3 sm:p-4">
                    <p v-if="errors[`blocks.${index}.type`]" class="mb-3 text-sm text-red-600">
                        {{ errors[`blocks.${index}.type`] }}
                    </p>

                    <div class="grid gap-3 md:grid-cols-2">
                        <FormField v-if="zoned" :label="t(
                            'storefront::admin.collection_pages.zone',
                        )
                            " :error="errors[`blocks.${index}.zone`]">
                            <Select v-model="block.zone as string" :options="availableZoneOptions(block)" />
                        </FormField>

                        <BuilderField v-for="field in typeOf(block.type)?.fields ?? []" :key="field.key"
                            v-model="block.config[field.key]" :field="field"
                            :path="`blocks.${index}.config.${field.key}`" :rich-text="block.type === 'rich_text' &&
                                field.key === 'body'
                                " :errors="errors" />
                    </div>
                </div>
            </Sortable>

            <!-- Empty state -->
            <p v-if="!form.blocks.length"
                class="rounded-lg border border-dashed border-slate-300 bg-slate-50/50 p-8 text-center text-sm text-slate-500">
                {{
                    t(
                        'storefront::admin.design_home_blocks.no_blocks_yet_add_block',
                    )
                }}
            </p>

            <!-- Add block -->
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                <div class="min-w-0">
                    <Select v-model="adding" :options="typeOptions" :aria-label="t(
                        'storefront::admin.design_home_blocks.block_type',
                    )
                        " />
                </div>

                <button type="button" :class="[
                    secondaryButton,
                    'flex h-10 items-center justify-center whitespace-nowrap',
                ]" :disabled="!availableTypes.length || busy" @click="add">
                    {{
                        t(
                            'storefront::admin.design_home_blocks.add_block',
                        )
                    }}
                </button>
            </div>
        </div>

        <!-- Responsive preview -->
        <BuilderPreview v-model:open="previewOpen" :blocks="form.blocks" :url="draftUrls.preview"
            :product-context="productContext" :listing-context="listingContext" />
    </div>
</template>
