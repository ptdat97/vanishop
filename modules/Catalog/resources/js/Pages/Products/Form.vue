<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import VariantMatrix from '../../Components/VariantMatrix.vue';
import type { BrandRef, NavItem, Translations } from '../../types';

type Fields = 'name' | 'description' | 'care_instructions' | 'meta_title' | 'meta_description';
type AttributeValue = number | number[] | string | boolean | null;

interface ColorEntry {
    id: number;
    code: string;
    name: string | null;
    hex: string | null;
    images: Array<{ id: number; url: string }>;
}

const props = defineProps<{
    brand: BrandRef;
    nav: NavItem[];
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
        translations: Translations<Fields>;
        attributes: Record<number, AttributeValue>;
        colors: ColorEntry[];
        variants: Array<{ id: number; sku: string; barcode: string | null; status: string; weight_gram: number | null; color_code: string; size_code: string }>;
    };
    categories: Array<{ id: number; label: string }>;
    attributeDefinitions: Array<{ id: number; name: string | null; kind: string; input_type: string; values: Array<{ id: number; label: string | null }> }>;
    availableColors: Array<{ id: number; label: string }>;
    statuses: string[];
    availableSizes: Array<{ id: number; label: string; system: string }>;
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const productUrl = computed(() => (props.product ? `${baseUrl.value}/${props.product.id}` : ''));
const locale = ref<'vi' | 'en'>('vi');
const statusLabels: Record<string, string> = { draft: 'Nháp', active: 'Đang bán', archived: 'Lưu trữ' };

const emptyTranslation = { name: '', description: '', care_instructions: '', meta_title: '', meta_description: '' };
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
    translations: {
        vi: { ...emptyTranslation, ...props.product?.translations.vi },
        en: { ...emptyTranslation, ...props.product?.translations.en },
    },
    attributes: initialAttributes,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.product) {
        form.put(productUrl.value, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

function destroy(): void {
    if (props.product && confirm('Xoá sản phẩm nháp này?')) {
        router.delete(productUrl.value);
    }
}

const newColorId = ref<number | null>(null);
function addColor(): void {
    if (newColorId.value) {
        router.post(`${productUrl.value}/colors`, { color_id: newColorId.value }, { preserveScroll: true, onSuccess: () => (newColorId.value = null) });
    }
}

function removeColor(color: ColorEntry): void {
    if (confirm(`Gỡ màu ${color.code} và toàn bộ ảnh của màu này?`)) {
        router.delete(`${productUrl.value}/colors/${color.id}`, { preserveScroll: true });
    }
}

function uploadImages(color: ColorEntry, event: Event): void {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    if (files.length) {
        router.post(`${productUrl.value}/colors/${color.id}/images`, { images: files }, { forceFormData: true, preserveScroll: true });
    }
}

function moveImage(color: ColorEntry, index: number, direction: -1 | 1): void {
    const order = color.images.map((image) => image.id);
    const target = index + direction;
    if (target < 0 || target >= order.length) {
        return;
    }
    [order[index], order[target]] = [order[target], order[index]];
    router.put(`${productUrl.value}/colors/${color.id}/images/order`, { order }, { preserveScroll: true });
}

function removeImage(color: ColorEntry, imageId: number): void {
    router.delete(`${productUrl.value}/colors/${color.id}/images/${imageId}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="product ? 'Sửa sản phẩm' : 'Thêm sản phẩm'" />
    <CatalogTabs :brand="brand" :nav="nav" active="products" />
    <PageHeader :title="product ? `Sửa: ${product.style_code}` : 'Thêm sản phẩm'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="product && product.status === 'draft'" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <p v-if="errors.product" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ errors.product }}</p>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex gap-2 text-sm">
                    <button v-for="code in ['vi', 'en'] as const" :key="code" type="button" class="rounded px-2 py-1" :class="locale === code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = code">
                        {{ code === 'vi' ? 'Tiếng Việt' : 'English' }}
                    </button>
                </div>
                <FormField :label="locale === 'vi' ? 'Tên sản phẩm (bắt buộc)' : 'Tên sản phẩm'" :error="errors[`translations.${locale}.name`]">
                    <input v-model="form.translations[locale].name" :class="inputClass" />
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
                    <select v-if="definition.input_type === 'select'" v-model="form.attributes[definition.id]" :class="inputClass">
                        <option :value="null">—</option>
                        <option v-for="value in definition.values" :key="value.id" :value="value.id">{{ value.label }}</option>
                    </select>
                    <select v-else-if="definition.input_type === 'multiselect'" v-model="form.attributes[definition.id]" :class="inputClass" multiple size="4">
                        <option v-for="value in definition.values" :key="value.id" :value="value.id">{{ value.label }}</option>
                    </select>
                    <select v-else-if="definition.input_type === 'boolean'" v-model="form.attributes[definition.id]" :class="inputClass">
                        <option :value="null">—</option>
                        <option :value="true">Có</option>
                        <option :value="false">Không</option>
                    </select>
                    <input v-else v-model="form.attributes[definition.id]" :class="inputClass" />
                </FormField>
            </div>

            <div v-if="product" class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h2 class="text-sm font-medium">Màu và ảnh</h2>
                    <div class="flex gap-2">
                        <select v-model="newColorId" :class="inputClass" class="max-w-56">
                            <option :value="null">Chọn màu…</option>
                            <option v-for="color in availableColors" :key="color.id" :value="color.id">{{ color.label }}</option>
                        </select>
                        <button type="button" :class="secondaryButton" :disabled="!newColorId" @click="addColor">Thêm màu</button>
                    </div>
                </div>
                <p v-if="errors.color_id" class="text-sm text-red-600">{{ errors.color_id }}</p>
                <p v-if="errors.images" class="text-sm text-red-600">{{ errors.images }}</p>
                <div v-for="color in product.colors" :key="color.id" class="rounded-md border border-slate-200 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <span class="inline-block h-4 w-4 rounded-full border border-slate-300" :style="{ background: color.hex ?? 'transparent' }" />
                            {{ color.name }} <span class="font-mono text-xs text-slate-400">{{ color.code }}</span>
                        </div>
                        <button type="button" class="text-sm text-red-600" @click="removeColor(color)">Gỡ màu</button>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="(image, index) in color.images" :key="image.id" class="w-24">
                            <img :src="image.url" alt="" class="h-32 w-24 rounded object-cover" />
                            <div class="mt-1 flex justify-between text-xs">
                                <button type="button" :disabled="index === 0" @click="moveImage(color, index, -1)">←</button>
                                <button type="button" class="text-red-600" @click="removeImage(color, image.id)">Xoá</button>
                                <button type="button" :disabled="index === color.images.length - 1" @click="moveImage(color, index, 1)">→</button>
                            </div>
                        </div>
                        <label class="flex h-32 w-24 cursor-pointer items-center justify-center rounded border border-dashed border-slate-300 text-xs text-slate-500">
                            + Ảnh
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" multiple @change="uploadImages(color, $event)" />
                        </label>
                    </div>
                </div>
                <p v-if="!product.colors.length" class="text-sm text-slate-500">Chưa có màu. Thêm màu rồi tải ảnh cho từng màu (khuyến nghị tỉ lệ 4:5).</p>
            </div>
            <VariantMatrix v-if="product" :product-url="productUrl" :variants="product.variants" :sizes="availableSizes" />
            <p v-else class="text-sm text-slate-500">Lưu sản phẩm trước, sau đó thêm màu, ảnh và biến thể.</p>
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 self-start">
            <FormField label="Mã sản phẩm (style code)" hint="Chữ in hoa, số, . _ -" :error="errors.style_code">
                <input v-model="form.style_code" :class="inputClass" />
            </FormField>
            <FormField label="Slug (đường dẫn)" :error="errors.slug">
                <input v-model="form.slug" :class="inputClass" />
            </FormField>
            <FormField label="Trạng thái" :error="errors.status">
                <select v-model="form.status" :class="inputClass">
                    <option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] ?? status }}</option>
                </select>
            </FormField>
            <FormField label="Hiển thị từ" :error="errors.published_from">
                <input v-model="form.published_from" :class="inputClass" type="datetime-local" />
            </FormField>
            <FormField label="Hiển thị đến" :error="errors.published_to">
                <input v-model="form.published_to" :class="inputClass" type="datetime-local" />
            </FormField>
            <FormField label="Danh mục" :error="errors.category_ids">
                <select v-model="form.category_ids" :class="inputClass" multiple size="6">
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.label }}</option>
                </select>
            </FormField>
            <FormField label="Danh mục chính (breadcrumb)" :error="errors.primary_category_id">
                <select v-model="form.primary_category_id" :class="inputClass">
                    <option :value="null">—</option>
                    <option v-for="category in categories.filter((c) => form.category_ids.includes(c.id))" :key="category.id" :value="category.id">{{ category.label }}</option>
                </select>
            </FormField>
            <p v-if="errors.lock_version" class="text-sm text-red-600">{{ errors.lock_version }}</p>
            <button type="submit" :class="primaryButton" class="w-full" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
