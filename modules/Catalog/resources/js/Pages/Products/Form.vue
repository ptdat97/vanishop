<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import FullscreenDialog from '@admin/Components/FullscreenDialog.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import MultiSelect from '@admin/Components/MultiSelect.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction, promptFields } from '@admin/confirm';
import { codeOptions, recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionSection } from '@admin/types';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import DefaultVariantEditor from '../../Components/DefaultVariantEditor.vue';
import ProductLinks from '../../Components/ProductLinks.vue';
import ProductVideos from '../../Components/ProductVideos.vue';
import VariantMatrixEditor from '../../Components/VariantMatrixEditor.vue';
import type { CatalogLabels, LinkGroupRow, NavItem, Translations, VideoRow } from '../../types';

type Fields = 'name' | 'slug' | 'description' | 'care_instructions' | 'meta_title' | 'meta_description';
type AttributeValue = number | number[] | string | boolean | null;

const props = defineProps<{
    nav: NavItem[];
    stockUrl?: string | null;
    labels: CatalogLabels;
    /** Dữ liệu còn thiếu của sản phẩm (nhãn đã dịch) — rỗng khi đủ hoặc khi thêm mới. */
    missing: string[];
    product: null | {
        id: number;
        style_code: string;
        slug: string;
        status: string;
        published_from: string | null;
        published_to: string | null;
        lock_version: number;
        track_inventory: boolean;
        category_ids: number[];
        primary_category_id: number | null;
        brand_id: number | null;
        translations: Translations<Fields>;
        attributes: Record<number, AttributeValue>;
        variants: Array<{ id: number; sku: string; barcode: string | null; status: string; weight_gram: number | null; values: number[]; label: string; images: Array<{ id: number; url: string }> }>;
        links: LinkGroupRow[];
        videos: VideoRow[];
    };
    categories: Array<{ id: number; label: string }>;
    brands: Array<{ id: number; label: string }>;
    attributeDefinitions: Array<{ id: number; code: string; name: string | null; kind: string; input_type: string; values: Array<{ id: number; label: string | null }> }>;
    attributeUrls: { index: string; store: string };
    statuses: string[];
    linkTypes: Array<{ id: number; label: string; is_active: boolean }>;
    videoDrivers: Array<{ code: string; label: string; hint: string }>;
    extensionSections: ExtensionSection[];
    variantMatrix: {
        urls: { option_types: string; option_type_store: string; option_value_store: string };
        axes: Array<{ option_type_id: number; value_ids: number[] }>;
        optionTypes: Array<{ id: number; code: string; name: string; display_type: string; values: Array<{ id: number; code: string; name: string | null; hex: string | null; group: string | null }> }>;
        columns: Array<{ code: string; label: string; editable: boolean; fields: Array<{ key: string; label: string; type: string; required: boolean; options: Record<string, string>; help: string | null }> }>;
        values: Record<number, Record<string, Record<string, string | number | boolean | null>>>;
        /** SKU đã phát sinh (đơn, giỏ, giữ hàng, tồn, bảng giá, đổi trả). */
        used: number[];
    };
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const productUrl = computed(() => (props.product ? `${baseUrl.value}/${props.product.id}` : ''));
const linkTypesUrl = computed(() => props.nav.find((item) => item.key === 'link-types')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const page = usePage();
const locale = ref(defaultLocale);
const categoryOptions = computed(() => recordOptions(props.categories));
const primaryCategoryOptions = computed(() => recordOptions(props.categories.filter((category) => form.category_ids.includes(category.id))));
const booleanOptions = [
    { value: true, label: t('catalog::admin.products_form.yes') },
    { value: false, label: t('catalog::admin.products_form.no') },
];

const emptyTranslation = { name: '', slug: '', description: '', care_instructions: '', meta_title: '', meta_description: '' };
const initialAttributes: Record<number, AttributeValue> = {};
for (const definition of props.attributeDefinitions) {
    initialAttributes[definition.id] = props.product?.attributes[definition.id] ?? (definition.input_type === 'multiselect' ? [] : null);
}

const { form, save } = useAdminForm({
    style_code: props.product?.style_code ?? '',
    slug: props.product?.slug ?? '',
    status: props.product?.status ?? 'draft',
    published_from: props.product?.published_from ?? null,
    published_to: props.product?.published_to ?? null,
    lock_version: props.product?.lock_version ?? null,
    track_inventory: props.product?.track_inventory ?? true,
    category_ids: props.product?.category_ids ?? ([] as number[]),
    primary_category_id: props.product?.primary_category_id ?? null,
    brand_id: props.product?.brand_id ?? null,
    translations: translationsFor(locales, emptyTranslation, props.product?.translations),
    attributes: initialAttributes,
    extensions: initialExtensionValues(props.extensionSections),
    /** Ảnh chọn khi thêm mới — gắn cho mọi SKU khi lưu lần đầu (sau đó ảnh quản lý theo SKU trong ma trận biến thể). */
    media_ids: [] as number[],
}, { lockVersion: () => props.product?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

/**
 * Biến thể truyền cho ma trận: tham chiếu ổn định. `product?.variants ?? []` trong template tạo mảng mới mỗi lần render → ma trận
 * tính lại `summary` → trang render lại → lặp vô hạn (trang "Thêm sản phẩm" treo).
 */
const variants = computed(() => props.product?.variants ?? []);

/** Một nút Lưu cho cả sản phẩm và ma trận biến thể (ma trận chỉ gửi khi có thay đổi). */
const matrix = ref<InstanceType<typeof VariantMatrixEditor> | null>(null);
/** Popup toàn màn hình của ma trận biến thể; lỗi của ma trận khi lưu → mở lại popup để thấy ngay ô lỗi. */
const variantsOpen = ref(false);
const variantSummary = computed(() => matrix.value?.summary ?? null);
const isVariantError = (field: string): boolean => field === 'axes' || /^(axes|cells|variant|variants\.images)\./.test(field);
/**
 * Loại sản phẩm: "đơn" (một SKU không màu, size — DefaultVariantEditor) hoặc "có biến thể" (ma trận). Hai khối độc lập, mỗi lần
 * lưu chỉ gửi khối đang chọn. Đổi loại được khi sản phẩm chưa có SKU nào.
 */
const defaultVariant = ref<InstanceType<typeof DefaultVariantEditor> | null>(null);
const hasAxisVariants = props.product !== null && (variants.value.some((variant) => variant.values.length > 0) || props.variantMatrix.axes.some((axis) => axis.value_ids.length > 0));
const productKind = ref<'single' | 'variants'>(hasAxisVariants ? 'variants' : 'single');
const canChangeKind = computed(() => variants.value.length === 0);
/**
 * Chuyển về sản phẩm đơn: giữ một SKU (bỏ màu/size, giữ id và lịch sử), xoá các SKU còn lại — chỉ khi các SKU đó chưa phát sinh
 * gì. Có SKU đã phát sinh thì chỉ SKU đó giữ lại được; từ hai SKU đã phát sinh trở lên thì không chuyển được (server kiểm tra lại).
 */
const collapseTo = ref<number | null>(null);
const pickedCollapse = ref<number | null>(null);
const hasOptionVariants = computed(() => variants.value.some((variant) => variant.values.length > 0));
const usedVariants = computed(() => variants.value.filter((variant) => props.variantMatrix.used.includes(variant.id)));
const collapseCandidates = computed(() => {
    if (!hasOptionVariants.value || usedVariants.value.length > 1) return [];
    return (usedVariants.value.length === 1 ? usedVariants.value : variants.value).map((variant) => ({ value: variant.id, label: `${variant.label} · ${variant.sku}` }));
});
function startCollapse(): void {
    if (pickedCollapse.value === null) return;
    collapseTo.value = pickedCollapse.value;
    productKind.value = 'single';
}
function cancelCollapse(): void {
    collapseTo.value = null;
    productKind.value = 'variants';
}
const singleVariant = computed(() => {
    if (collapseTo.value !== null) return variants.value.find((variant) => variant.id === collapseTo.value) ?? null;
    return variants.value.length === 1 && variants.value[0].values.length === 0 ? variants.value[0] : null;
});
const singleValues = computed(() => (singleVariant.value ? props.variantMatrix.values[singleVariant.value.id] ?? {} : {}));
const kindOptions = [
    { value: 'single', label: t('catalog::admin.products_form.simple_product'), hint: t('catalog::admin.products_form.one_sku_no_colour_size') },
    { value: 'variants', label: t('catalog::admin.products_form.variants'), hint: t('catalog::admin.products_form.multiple_skus_colour_size') },
] as const;
/** Sản phẩm đơn đã có SKU → chuyển sang có biến thể: SKU đó trở thành một ô của ma trận (VariantMatrix `adopt_single`). */
const converting = ref(false);
function startConversion(): void {
    converting.value = true;
    productKind.value = 'variants';
    variantsOpen.value = true;
}
function cancelConversion(): void {
    converting.value = false;
    productKind.value = 'single';
    variantsOpen.value = false;
    matrix.value?.reset();
}
function submit(): void {
    const single = productKind.value === 'single';
    const collapsing = single ? collapseTo.value : null;
    form.transform((data) => {
        const variants = collapsing !== null
            ? { ...defaultVariant.value!.payload(true)!, collapse_to: collapsing }
            : (single ? (defaultVariant.value?.payload() ?? null) : (matrix.value?.payload() ?? null));
        return variants === null ? data : { ...data, variants };
    });
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (!Object.keys(page.props.errors ?? {}).some(isVariantError)) {
                variantsOpen.value = false;
                converting.value = false;
                collapseTo.value = null;
                pickedCollapse.value = null;
            }
        },
        onError: (errors: Record<string, string>) => {
            if (!single && Object.keys(errors).some(isVariantError)) variantsOpen.value = true;
        },
    };
    if (props.product) {
        save('put', productUrl.value, options);
    } else {
        save('post', baseUrl.value, options);
    }
}

/* Tạo nhanh thuộc tính tại form (không rời trang, giữ dữ liệu đang nhập); thuộc tính chọn nhiều giá trị nhập cách nhau dấu phẩy. */
const attributeTypes = [
    { value: 'select', label: t('catalog::admin.products_form.single_choice') },
    { value: 'multiselect', label: t('catalog::admin.products_form.multiple_choice') },
    { value: 'text', label: t('catalog::admin.products_form.text') },
    { value: 'boolean', label: t('catalog::admin.products_form.yes_no') },
];
const quickAttribute = reactive({ open: false, name: '', input_type: 'select', values: '', busy: false });
const quickAttributeErrors = computed(() => (page.props.errors as unknown as Record<string, Record<string, string> | undefined>).quickAttribute ?? {});

function createAttribute(): void {
    const name = quickAttribute.name.trim();
    let code = slugify(name).replace(/-/g, '_').slice(0, 64);
    if (!name || !code) return;
    if (!/^[a-z]/.test(code)) code = `a_${code}`.slice(0, 64);
    const choices = ['select', 'multiselect'].includes(quickAttribute.input_type)
        ? [...new Set(quickAttribute.values.split(',').map((label) => label.trim()).filter(Boolean))]
        : [];
    quickAttribute.busy = true;
    router.post(props.attributeUrls.store, {
        quick: 1, code, kind: 'spec', input_type: quickAttribute.input_type, is_filterable: choices.length > 0, position: props.attributeDefinitions.length,
        translations: { [defaultLocale]: { name } },
        values: choices.map((label) => ({ code: slugify(label).slice(0, 64) || label.toLowerCase(), translations: { [defaultLocale]: { label } } })),
    }, {
        preserveState: true, preserveScroll: true, errorBag: 'quickAttribute',
        onSuccess: () => {
            const created = props.attributeDefinitions.find((definition) => definition.code === code);
            if (created) form.attributes[created.id] = created.input_type === 'multiselect' ? [] : null;
            Object.assign(quickAttribute, { open: false, name: '', values: '' });
        },
        onFinish: () => (quickAttribute.busy = false),
    });
}

/** Nhân bản thành bản nháp với mã + tên mới (A4): SKU, ảnh, giá niêm yết chép theo; tồn kho không chép. */
async function duplicate(): Promise<void> {
    if (!props.product) return;
    const values = await promptFields({
        title: t('catalog::admin.products_form.duplicate_product'),
        message: t('catalog::admin.products_form.creates_draft_same_description_categories'),
        confirmLabel: t('catalog::admin.products_form.duplicate'),
        fields: [
            { key: 'style_code', label: t('catalog::admin.products_form.new_product_code'), required: true, maxlength: 64, value: `${props.product.style_code}-2` },
            { key: 'name', label: t('catalog::admin.products_form.new_product_name'), required: true, maxlength: 255, value: t('catalog::admin.products_form.name_copy', { name: form.translations[defaultLocale]?.name ?? '' }) },
        ],
    });
    if (values !== false) {
        router.post(`${productUrl.value}/duplicate`, { style_code: values.style_code.trim().toUpperCase(), name: values.name });
    }
}

async function destroy(): Promise<void> {
    if (props.product && (await confirmAction({ title: t('catalog::admin.products_form.delete_draft_product'), confirmLabel: t('catalog::admin.products_form.delete'), tone: 'danger' })) !== false) {
        router.delete(productUrl.value);
    }
}

</script>

<template>
    <Head :title="product ? t('catalog::admin.products_form.edit_product') : t('catalog::admin.products_form.add_product')" />
    <CatalogTabs :nav="nav" active="products" />
    <PageHeader :title="product ? t('catalog::admin.products_form.edit_style_code', { style_code: product.style_code }) : t('catalog::admin.products_form.add_product')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('catalog::admin.products_form.back') }}</Link>
        <Link v-if="stockUrl" :href="stockUrl" :class="secondaryButton" data-stock-link>{{ t('catalog::admin.products_form.inventory') }}</Link>
        <button v-if="product" type="button" :class="secondaryButton" data-duplicate @click="duplicate">{{ t('catalog::admin.products_form.duplicate') }}</button>
        <button v-if="product && product.status === 'draft'" type="button" :class="dangerButton" @click="destroy">{{ t('catalog::admin.products_form.delete') }}</button>
    </PageHeader>

    <div v-if="missing.length" role="status" class="mb-4 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-missing-details>
        <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
        <div>
            <p class="font-medium">{{ t('catalog::admin.products_form.missing_data_title') }}</p>
            <ul class="mt-1 list-disc pl-5">
                <li v-for="item in missing" :key="item">{{ item }}</li>
            </ul>
            <p class="mt-1 text-xs text-amber-800">{{ t('catalog::admin.products_form.missing_data_hint') }}</p>
        </div>
    </div>


    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex gap-2 text-sm">
                    <button v-for="option in locales" :key="option.code" type="button" class="rounded px-2 py-1" :class="locale === option.code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = option.code">
                        {{ option.label }}
                    </button>
                </div>
                <FormField :label="locale === defaultLocale ? t('catalog::admin.products_form.product_name_required') : t('catalog::admin.products_form.product_name')" :error="errors[`translations.${locale}.name`]">
                    <input v-model="form.translations[locale].name" :class="inputClass" />
                </FormField>
                <FormField v-if="locale !== defaultLocale" :label="t('catalog::admin.products_form.slug_language')" :hint="t('catalog::admin.products_form.empty_use_base_slug_changing')" :error="errors[`translations.${locale}.slug`]">
                    <div class="flex gap-2">
                        <input v-model="form.translations[locale].slug" :class="inputClass" :placeholder="slugify(form.translations[locale].name ?? '') || form.slug" />
                        <button type="button" :class="secondaryButton" :disabled="!form.translations[locale].name" @click="form.translations[locale].slug = slugify(form.translations[locale].name ?? '')">{{ t('catalog::admin.products_form.generate_from_name') }}</button>
                    </div>
                </FormField>
                <FormField :label="t('catalog::admin.products_form.description')" :error="errors[`translations.${locale}.description`]">
                    <textarea v-model="form.translations[locale].description" :class="inputClass" rows="6" />
                </FormField>
                <FormField :label="t('catalog::admin.products_form.care_instructions')" :error="errors[`translations.${locale}.care_instructions`]">
                    <textarea v-model="form.translations[locale].care_instructions" :class="inputClass" rows="3" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Meta title"><input v-model="form.translations[locale].meta_title" :class="inputClass" /></FormField>
                    <FormField label="Meta description"><input v-model="form.translations[locale].meta_description" :class="inputClass" /></FormField>
                </div>
            </div>

            <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                <div class="flex flex-wrap items-center justify-between gap-2 sm:col-span-2">
                    <h2 class="text-sm font-medium">{{ t('catalog::admin.products_form.attributes') }}</h2>
                    <div class="flex items-center gap-3 text-xs">
                        <button type="button" class="text-indigo-600 underline" @click="quickAttribute.open = !quickAttribute.open">{{ t('catalog::admin.products_form.new_attribute') }}</button>
                        <a :href="attributeUrls.index" target="_blank" class="text-indigo-600 underline">{{ t('catalog::admin.products_form.manage_attributes') }}</a>
                    </div>
                </div>
                <p v-if="!attributeDefinitions.length && !quickAttribute.open" class="text-sm text-slate-500 sm:col-span-2">{{ t('catalog::admin.products_form.no_attributes_yet_material_fit') }}</p>
                <div v-if="quickAttribute.open" class="flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3 sm:col-span-2">
                    <FormField :label="t('catalog::admin.products_form.attribute_name')" :error="quickAttributeErrors.code ?? quickAttributeErrors[`translations.${defaultLocale}.name`]">
                        <input v-model="quickAttribute.name" :class="inputClass" :placeholder="t('catalog::admin.products_form.material')" @keydown.enter.prevent="createAttribute" />
                    </FormField>
                    <FormField :label="t('catalog::admin.products_form.type')"><Select v-model="quickAttribute.input_type" :options="attributeTypes" /></FormField>
                    <FormField v-if="['select', 'multiselect'].includes(quickAttribute.input_type)" :label="t('catalog::admin.products_form.values_comma_separated')" class="min-w-60 flex-1">
                        <input v-model="quickAttribute.values" :class="inputClass" :placeholder="t('catalog::admin.products_form.cotton_silk_linen')" @keydown.enter.prevent="createAttribute" />
                    </FormField>
                    <button type="button" :class="secondaryButton" :disabled="quickAttribute.busy || !quickAttribute.name.trim()" @click="createAttribute">{{ t('catalog::admin.products_form.create') }}</button>
                    <p v-for="(message, field) in quickAttributeErrors" :key="field" class="w-full text-xs text-red-600">{{ message }}</p>
                </div>
                <FormField v-for="definition in attributeDefinitions" :key="definition.id" :label="`${definition.name}${definition.kind === 'internal' ? t('catalog::admin.products_form.internal') : ''}`" :error="errors[`attributes.${definition.id}`]">
                    <Select
                        v-if="definition.input_type === 'select'"
                        v-model="form.attributes[definition.id] as number | null"
                        :options="recordOptions(definition.values, (value) => value.label ?? String(value.id))"
                        empty-label="—"
                        :empty-value="null"
                    />
                    <MultiSelect
                        v-else-if="definition.input_type === 'multiselect'"
                        v-model="form.attributes[definition.id] as number[]"
                        :options="recordOptions(definition.values, (value) => value.label ?? String(value.id))"
                    />
                    <Select v-else-if="definition.input_type === 'boolean'" v-model="form.attributes[definition.id] as boolean | null" :options="booleanOptions" empty-label="—" :empty-value="null" />
                    <input v-else v-model="form.attributes[definition.id]" :class="inputClass" />
                </FormField>
            </div>

            <!-- Loại sản phẩm: đơn (một SKU, không màu/size) hoặc có biến thể (ma trận) — hai khối độc lập, lưu khối đang chọn. -->
            <fieldset v-if="canChangeKind" class="rounded-lg border border-slate-200 bg-white p-5" data-product-kind>
                <legend class="sr-only">{{ t('catalog::admin.products_form.product_type') }}</legend>
                <h2 class="text-sm font-medium">{{ t('catalog::admin.products_form.product_type') }}</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in kindOptions"
                        :key="option.value"
                        class="flex cursor-pointer items-start gap-2 rounded-md border p-3 text-sm"
                        :class="productKind === option.value ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50'"
                    >
                        <input v-model="productKind" type="radio" name="product-kind" :value="option.value" class="mt-0.5" />
                        <span>
                            <span class="block font-medium text-slate-900">{{ option.label }}</span>
                            <span class="block text-xs text-slate-500">{{ option.hint }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <!-- Sản phẩm đơn: một SKU ngay trên form. -->
            <section v-show="productKind === 'single'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="single-variant-title">
                <div>
                    <h2 id="single-variant-title" class="text-sm font-medium">
                        {{ t('catalog::admin.products_form.sku_price') }}
                        <span v-if="defaultVariant?.dirty && product" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-normal text-amber-800">{{ t('catalog::admin.products_form.unsaved') }}</span>
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">{{ t('catalog::admin.products_form.simple_product_one_sku_no') }} <template v-if="!product">{{ t('catalog::admin.products_form.leave_empty_save_product_without') }}</template></p>
                    <div v-if="collapseTo !== null" class="mt-2 flex flex-wrap items-center gap-2 rounded-md bg-indigo-50 p-3 text-sm text-indigo-900" data-collapsing>
                        <span>{{ t('catalog::admin.products_form.converting_simple_product_keeping_sku', { sku: singleVariant?.sku, variants: variants.length - 1 }) }}</span>
                        <button type="button" class="ml-auto underline" @click="cancelCollapse">{{ t('catalog::admin.products_form.cancel_conversion') }}</button>
                    </div>
                    <button v-else-if="singleVariant" type="button" class="mt-2 text-sm text-indigo-600 underline" data-convert-to-variants @click="startConversion">
                        {{ t('catalog::admin.products_form.convert_variants_sku_becomes_one') }}
                    </button>
                </div>
                <DefaultVariantEditor
                    ref="defaultVariant"
                    v-model:media-ids="form.media_ids"
                    :product-url="productUrl"
                    :style-code="form.style_code || 'SKU'"
                    :variant="singleVariant"
                    :values="singleValues"
                    :columns="variantMatrix.columns"
                    :status-labels="labels.variant_status"
                />
            </section>

            <!-- Có biến thể: thẻ tóm tắt trên form; quản lý trong popup toàn màn hình (một nút Lưu chung). -->
            <section v-show="productKind === 'variants'" class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="variants-card-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="variants-card-title" class="text-sm font-medium">
                            {{ t('catalog::admin.products_form.variants_2') }}
                            <span v-if="variantSummary?.dirty" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-normal text-amber-800">{{ t('catalog::admin.products_form.unsaved') }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            <template v-if="variantSummary && variantSummary.active + variantSummary.inactive > 0">
                                <span class="font-medium text-slate-900">{{ variantSummary.active }}</span> {{ t('catalog::admin.products_form.skus_sale') }}<template v-if="variantSummary.inactive"> {{ t('catalog::admin.products_form.inactive_discontinued', { inactive: variantSummary.inactive }) }}</template>
                                <template v-if="product && variantSummary.withoutImages"> · <span class="text-amber-700">{{ t('catalog::admin.products_form.without_images_without_images', { without_images: variantSummary.withoutImages }) }}</span></template>
                            </template>
                            <template v-else>{{ t('catalog::admin.products_form.no_variants_yet_choose_colours') }}</template>
                        </p>
                    </div>
                    <button type="button" :class="primaryButton" data-open-variants @click="variantsOpen = true">
                        {{ variantSummary && variantSummary.active + variantSummary.inactive > 0 ? t('catalog::admin.products_form.manage_variants') : t('catalog::admin.products_form.set_up_variants') }}
                    </button>
                </div>
                <div v-if="converting" class="mt-3 flex flex-wrap items-center gap-2 rounded-md bg-indigo-50 p-3 text-sm text-indigo-900">
                    <span>{{ t('catalog::admin.products_form.converting_variants_sku_sku_become', { sku: singleVariant?.sku }) }}</span>
                    <button type="button" class="ml-auto underline" @click="cancelConversion">{{ t('catalog::admin.products_form.cancel_conversion') }}</button>
                </div>
                <details v-if="product && hasOptionVariants && !converting" class="mt-3 text-sm" data-collapse-to-single>
                    <summary class="cursor-pointer text-indigo-600">{{ t('catalog::admin.products_form.convert_simple_product') }}</summary>
                    <div class="mt-2 space-y-2 rounded-md border border-slate-200 p-3">
                        <template v-if="collapseCandidates.length">
                            <p class="text-slate-600">
                                {{ t('catalog::admin.products_form.keep_one_sku_drops_colour') }}
                                <template v-if="usedVariants.length === 1">{{ t('catalog::admin.products_form.only_sku_kept_because_sku', { sku: usedVariants[0].sku }) }}</template>
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="w-72"><Select v-model="pickedCollapse" :options="collapseCandidates" :placeholder="t('catalog::admin.products_form.choose_sku_keep')" :aria-label="t('catalog::admin.products_form.sku_keep')" /></div>
                                <button type="button" :class="secondaryButton" :disabled="pickedCollapse === null" @click="startCollapse">{{ t('catalog::admin.products_form.convert') }}</button>
                            </div>
                        </template>
                        <p v-else class="text-amber-800">
                            {{ t('catalog::admin.products_form.cannot_convert_count_skus_have', { count: usedVariants.length }) }}
                        </p>
                    </div>
                </details>
                <div v-if="variantSummary?.axes.length" class="mt-3 space-y-1.5">
                    <div v-for="axis in variantSummary.axes" :key="axis.name" class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span class="w-16 shrink-0 font-medium text-slate-500">{{ axis.name }}</span>
                        <span v-for="value in axis.values" :key="value.name" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5">
                            <span v-if="axis.swatch" class="inline-block h-3 w-3 rounded-full border border-slate-300" :style="{ background: value.hex ?? 'transparent' }" />
                            {{ value.name }}
                        </span>
                    </div>
                </div>
                <div v-if="variantSummary?.images.length" class="mt-3 flex gap-2">
                    <img v-for="image in variantSummary.images" :key="image.id" :src="image.url" alt="" class="h-14 w-11 rounded object-cover" loading="lazy" />
                </div>
            </section>
            <FullscreenDialog
                v-model:open="variantsOpen"
                :title="t('catalog::admin.products_form.variants_i', { i: form.translations[defaultLocale]?.name || form.style_code || t('catalog::admin.products_form.new_product_fallback') })"
                :subtitle="t('catalog::admin.products_form.choose_options_turn_combinations_sell')"
            >
                <VariantMatrixEditor
                    ref="matrix"
                    :product-url="productUrl"
                    :urls="variantMatrix.urls"
                    :style-code="form.style_code || 'SKU'"
                    :axes="variantMatrix.axes"
                    :option-types="variantMatrix.optionTypes"
                    :variants="variants"
                    :columns="variantMatrix.columns"
                    :values="variantMatrix.values"
                    :status-labels="labels.variant_status"
                    :adopt-variant="converting ? singleVariant : null"
                />
                <template #footer>
                    <span v-if="variantSummary?.dirty" class="text-sm text-amber-700">{{ t('catalog::admin.products_form.unsaved_changes') }}</span>
                    <span v-else class="text-sm text-slate-500">{{ t('catalog::admin.products_form.no_changes') }}</span>
                    <span class="ml-auto" />
                    <button type="button" :class="secondaryButton" @click="variantsOpen = false">{{ t('catalog::admin.products_form.close_keep_draft') }}</button>
                    <button type="button" :class="primaryButton" :disabled="form.processing" data-save-variants @click="submit">{{ product ? t('catalog::admin.products_form.save') : t('catalog::admin.products_form.save_product') }}</button>
                </template>
            </FullscreenDialog>
            <ProductVideos v-if="product" :product-url="productUrl" :videos="product.videos" :drivers="videoDrivers" />
            <ProductLinks v-if="product" :product-url="productUrl" :links="product.links" :link-types="linkTypes" :link-types-url="linkTypesUrl" />
            <template v-else>
                <section v-if="productKind === 'variants'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="new-product-images-title" data-new-product-images>
                    <div>
                        <h2 id="new-product-images-title" class="text-sm font-medium">{{ t('catalog::admin.products_form.product_images') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ t('catalog::admin.products_form.choose_upload_images_drag_reorder') }}</p>
                    </div>
                    <FormField :label="t('catalog::admin.products_form.image')" :error="errors.media_ids ?? errors.images">
                        <MediaField v-model="form.media_ids" multiple :max="20" />
                    </FormField>
                </section>
                <p class="text-sm text-slate-500">{{ t('catalog::admin.products_form.videos_linked_products_added_after') }}</p>
            </template>
            <ExtensionFormSections v-model="form.extensions" :sections="extensionSections" :errors="errors" />
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 self-start">
            <FormField :label="t('catalog::admin.products_form.product_code_style_code')" :hint="t('catalog::admin.products_form.uppercase_letters_digits')" :error="errors.style_code">
                <input v-model="form.style_code" :class="inputClass" />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.slug_path')" :error="errors.slug">
                <input v-model="form.slug" :class="inputClass" />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.status')" :error="errors.status">
                <Select v-model="form.status" :options="codeOptions(statuses, labels.product_status)" />
            </FormField>
            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input v-model="form.track_inventory" type="checkbox" class="mt-0.5" data-track-inventory />
                    <span>
                        <span class="font-medium text-slate-700">{{ t('catalog::admin.products_form.track_stock') }}</span>
                        <span class="block text-xs text-slate-500">{{ t('catalog::admin.products_form.uncheck_made_order_dropship_services') }}</span>
                    </span>
                </label>
                <p v-if="errors.track_inventory" class="mt-1 text-sm text-red-600">{{ errors.track_inventory }}</p>
            </div>
            <FormField :label="t('catalog::admin.products_form.visible_from')" :error="errors.published_from">
                <DateInput v-model="form.published_from" with-time />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.visible_until')" :error="errors.published_to">
                <DateInput v-model="form.published_to" with-time />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.category')" :error="errors.category_ids">
                <MultiSelect v-model="form.category_ids" :options="categoryOptions" :placeholder="t('catalog::admin.products_form.type_search_categories')" />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.brand')" :error="errors.brand_id">
                <Select v-model="form.brand_id" :options="recordOptions(brands)" :empty-label="t('catalog::admin.products_form.none')" :empty-value="null" />
            </FormField>
            <FormField :label="t('catalog::admin.products_form.primary_category_breadcrumb')" :error="errors.primary_category_id">
                <Select v-model="form.primary_category_id" :options="primaryCategoryOptions" empty-label="—" :empty-value="null" />
            </FormField>
            <button type="submit" :class="primaryButton" class="w-full" :disabled="form.processing">{{ t('catalog::admin.products_form.save') }}</button>
        </div>
    </form>
</template>
