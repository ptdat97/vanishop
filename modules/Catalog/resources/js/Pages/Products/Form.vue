<script setup lang="ts">
import DateInput from '@admin/Components/DateInput.vue';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import MultiSelect from '@admin/Components/MultiSelect.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions, recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionSection } from '@admin/types';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
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
    product: null | {
        id: number;
        style_code: string;
        slug: string;
        status: string;
        published_from: string | null;
        published_to: string | null;
        lock_version: number;
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
    attributeDefinitions: Array<{ id: number; name: string | null; kind: string; input_type: string; values: Array<{ id: number; label: string | null }> }>;
    statuses: string[];
    linkTypes: Array<{ id: number; label: string; is_active: boolean }>;
    videoDrivers: Array<{ code: string; label: string; hint: string }>;
    extensionSections: ExtensionSection[];
    variantMatrix: null | {
        saveUrl: string;
        optionTypesUrl: string;
        axes: Array<{ option_type_id: number; value_ids: number[] }>;
        optionTypes: Array<{ id: number; code: string; name: string; display_type: string; values: Array<{ id: number; code: string; name: string | null; hex: string | null; group: string | null }> }>;
        columns: Array<{ code: string; label: string; editable: boolean; fields: Array<{ key: string; label: string; type: string; required: boolean; options: Record<string, string>; help: string | null }> }>;
        values: Record<number, Record<string, Record<string, string | number | boolean | null>>>;
    };
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const productUrl = computed(() => (props.product ? `${baseUrl.value}/${props.product.id}` : ''));
const linkTypesUrl = computed(() => props.nav.find((item) => item.key === 'link-types')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const locale = ref(defaultLocale);
const categoryOptions = computed(() => recordOptions(props.categories));
const primaryCategoryOptions = computed(() => recordOptions(props.categories.filter((category) => form.category_ids.includes(category.id))));
const booleanOptions = [
    { value: true, label: 'Có' },
    { value: false, label: 'Không' },
];

const emptyTranslation = { name: '', slug: '', description: '', care_instructions: '', meta_title: '', meta_description: '' };
const initialAttributes: Record<number, AttributeValue> = {};
for (const definition of props.attributeDefinitions) {
    initialAttributes[definition.id] = props.product?.attributes[definition.id] ?? (definition.input_type === 'multiselect' ? [] : null);
}

const form = useForm({
    style_code: props.product?.style_code ?? '',
    slug: props.product?.slug ?? '',
    status: props.product?.status ?? 'draft',
    published_from: props.product?.published_from ?? null,
    published_to: props.product?.published_to ?? null,
    lock_version: props.product?.lock_version ?? null,
    category_ids: props.product?.category_ids ?? ([] as number[]),
    primary_category_id: props.product?.primary_category_id ?? null,
    brand_id: props.product?.brand_id ?? null,
    translations: translationsFor(locales, emptyTranslation, props.product?.translations),
    attributes: initialAttributes,
    extensions: initialExtensionValues(props.extensionSections),
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.product) {
        form.put(productUrl.value, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.product && (await confirmAction({ title: 'Xoá sản phẩm nháp này?', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(productUrl.value);
    }
}

</script>

<template>
    <Head :title="product ? 'Sửa sản phẩm' : 'Thêm sản phẩm'" />
    <CatalogTabs :nav="nav" active="products" />
    <PageHeader :title="product ? `Sửa: ${product.style_code}` : 'Thêm sản phẩm'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <Link v-if="stockUrl" :href="stockUrl" :class="secondaryButton" data-stock-link>Tồn kho</Link>
        <button v-if="product && product.status === 'draft'" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <p v-if="errors.product" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ errors.product }}</p>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex gap-2 text-sm">
                    <button v-for="option in locales" :key="option.code" type="button" class="rounded px-2 py-1" :class="locale === option.code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = option.code">
                        {{ option.label }}
                    </button>
                </div>
                <FormField :label="locale === defaultLocale ? 'Tên sản phẩm (bắt buộc)' : 'Tên sản phẩm'" :error="errors[`translations.${locale}.name`]">
                    <input v-model="form.translations[locale].name" :class="inputClass" />
                </FormField>
                <FormField v-if="locale !== defaultLocale" label="Slug riêng cho ngôn ngữ này" hint="Trống = dùng slug gốc. Đổi slug đã được index làm gãy link cũ." :error="errors[`translations.${locale}.slug`]">
                    <div class="flex gap-2">
                        <input v-model="form.translations[locale].slug" :class="inputClass" :placeholder="slugify(form.translations[locale].name ?? '') || form.slug" />
                        <button type="button" :class="secondaryButton" :disabled="!form.translations[locale].name" @click="form.translations[locale].slug = slugify(form.translations[locale].name ?? '')">Tạo từ tên</button>
                    </div>
                </FormField>
                <FormField label="Mô tả" :error="errors[`translations.${locale}.description`]">
                    <textarea v-model="form.translations[locale].description" :class="inputClass" rows="6" />
                </FormField>
                <FormField label="Hướng dẫn bảo quản" :error="errors[`translations.${locale}.care_instructions`]">
                    <textarea v-model="form.translations[locale].care_instructions" :class="inputClass" rows="3" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Meta title"><input v-model="form.translations[locale].meta_title" :class="inputClass" /></FormField>
                    <FormField label="Meta description"><input v-model="form.translations[locale].meta_description" :class="inputClass" /></FormField>
                </div>
            </div>

            <div v-if="attributeDefinitions.length" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                <h2 class="text-sm font-medium sm:col-span-2">Thuộc tính</h2>
                <FormField v-for="definition in attributeDefinitions" :key="definition.id" :label="`${definition.name}${definition.kind === 'internal' ? ' (nội bộ)' : ''}`" :error="errors[`attributes.${definition.id}`]">
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

            <VariantMatrixEditor
                v-if="product && variantMatrix"
                :save-url="variantMatrix.saveUrl"
                :product-url="productUrl"
                :option-types-url="variantMatrix.optionTypesUrl"
                :style-code="product.style_code"
                :axes="variantMatrix.axes"
                :option-types="variantMatrix.optionTypes"
                :variants="product.variants"
                :columns="variantMatrix.columns"
                :values="variantMatrix.values"
                :status-labels="labels.variant_status"
            />
            <ProductVideos v-if="product" :product-url="productUrl" :videos="product.videos" :drivers="videoDrivers" />
            <ProductLinks v-if="product" :product-url="productUrl" :links="product.links" :link-types="linkTypes" :link-types-url="linkTypesUrl" />
            <p v-else class="text-sm text-slate-500">Lưu sản phẩm trước, sau đó thêm biến thể, ảnh, video và sản phẩm liên kết.</p>
            <ExtensionFormSections v-model="form.extensions" :sections="extensionSections" :errors="errors" />
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 self-start">
            <FormField label="Mã sản phẩm (style code)" hint="Chữ in hoa, số, . _ -" :error="errors.style_code">
                <input v-model="form.style_code" :class="inputClass" />
            </FormField>
            <FormField label="Slug (đường dẫn)" :error="errors.slug">
                <input v-model="form.slug" :class="inputClass" />
            </FormField>
            <FormField label="Trạng thái" :error="errors.status">
                <Select v-model="form.status" :options="codeOptions(statuses, labels.product_status)" />
            </FormField>
            <FormField label="Hiển thị từ" :error="errors.published_from">
                <DateInput v-model="form.published_from" with-time />
            </FormField>
            <FormField label="Hiển thị đến" :error="errors.published_to">
                <DateInput v-model="form.published_to" with-time />
            </FormField>
            <FormField label="Danh mục" :error="errors.category_ids">
                <MultiSelect v-model="form.category_ids" :options="categoryOptions" placeholder="Gõ để tìm danh mục" />
            </FormField>
            <FormField label="Thương hiệu" :error="errors.brand_id">
                <Select v-model="form.brand_id" :options="recordOptions(brands)" empty-label="— Không —" :empty-value="null" />
            </FormField>
            <FormField label="Danh mục chính (breadcrumb)" :error="errors.primary_category_id">
                <Select v-model="form.primary_category_id" :options="primaryCategoryOptions" empty-label="—" :empty-value="null" />
            </FormField>
            <p v-if="errors.lock_version" class="text-sm text-red-600">{{ errors.lock_version }}</p>
            <button type="submit" :class="primaryButton" class="w-full" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
