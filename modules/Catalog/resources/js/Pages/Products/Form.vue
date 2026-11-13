<script setup lang="ts">
import { syncLockVersion } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import FullscreenDialog from '@admin/Components/FullscreenDialog.vue';
import MultiSelect from '@admin/Components/MultiSelect.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions, recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionSection } from '@admin/types';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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

/** Một nút Lưu cho cả sản phẩm và ma trận biến thể (ma trận chỉ gửi khi có thay đổi). */
const matrix = ref<InstanceType<typeof VariantMatrixEditor> | null>(null);
/** Popup toàn màn hình của ma trận biến thể; lỗi của ma trận khi lưu → mở lại popup để thấy ngay ô lỗi. */
const variantsOpen = ref(false);
const variantSummary = computed(() => matrix.value?.summary ?? null);
const isVariantError = (field: string): boolean => field === 'axes' || /^(axes|cells|variant)\./.test(field);
function submit(): void {
    const withVariants = form.transform((data) => {
        const variants = matrix.value?.payload() ?? null;
        return variants === null ? data : { ...data, variants };
    });
    const options = {
        preserveScroll: true,
        onSuccess: syncLockVersion(form, () => props.product?.lock_version, () => {
            if (!Object.keys(page.props.errors ?? {}).some(isVariantError)) variantsOpen.value = false;
        }),
        onError: (errors: Record<string, string>) => {
            if (Object.keys(errors).some(isVariantError)) variantsOpen.value = true;
        },
    };
    if (props.product) {
        withVariants.put(productUrl.value, options);
    } else {
        withVariants.post(baseUrl.value, options);
    }
}

/* Tạo nhanh thuộc tính tại form (không rời trang, giữ dữ liệu đang nhập); thuộc tính chọn nhiều giá trị nhập cách nhau dấu phẩy. */
const attributeTypes = [
    { value: 'select', label: 'Chọn một' },
    { value: 'multiselect', label: 'Chọn nhiều' },
    { value: 'text', label: 'Văn bản' },
    { value: 'boolean', label: 'Có / Không' },
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

            <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                <div class="flex flex-wrap items-center justify-between gap-2 sm:col-span-2">
                    <h2 class="text-sm font-medium">Thuộc tính</h2>
                    <div class="flex items-center gap-3 text-xs">
                        <button type="button" class="text-indigo-600 underline" @click="quickAttribute.open = !quickAttribute.open">+ Thuộc tính mới</button>
                        <a :href="attributeUrls.index" target="_blank" class="text-indigo-600 underline">Quản lý thuộc tính ↗</a>
                    </div>
                </div>
                <p v-if="!attributeDefinitions.length && !quickAttribute.open" class="text-sm text-slate-500 sm:col-span-2">Chưa có thuộc tính (vd. Chất liệu, Form dáng). Bấm "+ Thuộc tính mới" để tạo ngay.</p>
                <div v-if="quickAttribute.open" class="flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3 sm:col-span-2">
                    <FormField label="Tên thuộc tính" :error="quickAttributeErrors.code ?? quickAttributeErrors[`translations.${defaultLocale}.name`]">
                        <input v-model="quickAttribute.name" :class="inputClass" placeholder="vd. Chất liệu" @keydown.enter.prevent="createAttribute" />
                    </FormField>
                    <FormField label="Kiểu"><Select v-model="quickAttribute.input_type" :options="attributeTypes" /></FormField>
                    <FormField v-if="['select', 'multiselect'].includes(quickAttribute.input_type)" label="Giá trị (cách nhau dấu phẩy)" class="min-w-60 flex-1">
                        <input v-model="quickAttribute.values" :class="inputClass" placeholder="vd. Cotton, Lụa, Linen" @keydown.enter.prevent="createAttribute" />
                    </FormField>
                    <button type="button" :class="secondaryButton" :disabled="quickAttribute.busy || !quickAttribute.name.trim()" @click="createAttribute">Tạo</button>
                    <p v-for="(message, field) in quickAttributeErrors" :key="field" class="w-full text-xs text-red-600">{{ message }}</p>
                </div>
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

            <!-- Biến thể: thẻ tóm tắt trên form; quản lý trong popup toàn màn hình (một nút Lưu chung). -->
            <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="variants-card-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="variants-card-title" class="text-sm font-medium">
                            Biến thể
                            <span v-if="variantSummary?.dirty" class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-normal text-amber-800">Chưa lưu</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            <template v-if="variantSummary && variantSummary.active + variantSummary.inactive > 0">
                                <span class="font-medium text-slate-900">{{ variantSummary.active }}</span> SKU đang bán<template v-if="variantSummary.inactive"> · {{ variantSummary.inactive }} ngừng bán</template>
                                <template v-if="product && variantSummary.withoutImages"> · <span class="text-amber-700">{{ variantSummary.withoutImages }} chưa có ảnh</span></template>
                            </template>
                            <template v-else>Chưa có biến thể — chọn màu, size… để tạo SKU.</template>
                        </p>
                    </div>
                    <button type="button" :class="primaryButton" data-open-variants @click="variantsOpen = true">
                        {{ variantSummary && variantSummary.active + variantSummary.inactive > 0 ? 'Quản lý biến thể' : 'Thiết lập biến thể' }}
                    </button>
                </div>
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
                :title="`Biến thể — ${form.translations[defaultLocale]?.name || form.style_code || 'sản phẩm mới'}`"
                subtitle="Chọn tuỳ chọn, bật tổ hợp bán, sửa SKU/giá/tồn trong bảng. Tắt SKU đã có: chưa phát sinh → xoá, đã bán/có tồn → ngừng bán."
            >
                <VariantMatrixEditor
                    ref="matrix"
                    :product-url="productUrl"
                    :urls="variantMatrix.urls"
                    :style-code="form.style_code || 'SKU'"
                    :axes="variantMatrix.axes"
                    :option-types="variantMatrix.optionTypes"
                    :variants="product?.variants ?? []"
                    :columns="variantMatrix.columns"
                    :values="variantMatrix.values"
                    :status-labels="labels.variant_status"
                />
                <template #footer>
                    <span v-if="variantSummary?.dirty" class="text-sm text-amber-700">Có thay đổi chưa lưu</span>
                    <span v-else class="text-sm text-slate-500">Không có thay đổi</span>
                    <span class="ml-auto" />
                    <button type="button" :class="secondaryButton" @click="variantsOpen = false">Đóng (giữ bản nháp)</button>
                    <button type="button" :class="primaryButton" :disabled="form.processing" data-save-variants @click="submit">{{ product ? 'Lưu' : 'Lưu sản phẩm' }}</button>
                </template>
            </FullscreenDialog>
            <ProductVideos v-if="product" :product-url="productUrl" :videos="product.videos" :drivers="videoDrivers" />
            <ProductLinks v-if="product" :product-url="productUrl" :links="product.links" :link-types="linkTypes" :link-types-url="linkTypesUrl" />
            <p v-else class="text-sm text-slate-500">Ảnh theo SKU, video và sản phẩm liên kết thêm được sau lần Lưu đầu tiên.</p>
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
            <button type="submit" :class="primaryButton" class="w-full" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
