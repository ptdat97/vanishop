<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import { getJson } from '@admin/http';
import Combobox from '@admin/Components/Combobox.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Texts = { name: string; description: string; meta_title: string; meta_description: string };
type ProductOption = { value: string; label: string };
type Image = { media_id: number; alt: Record<string, string>; thumb_url: string | null; products: ProductOption[] };

/** Form lookbook (đặc tả §3.2): tên/mô tả theo ngôn ngữ, đường dẫn, ảnh bìa + ảnh từ Thư viện ảnh (thứ tự, alt), danh mục. */
const props = defineProps<{
    lookbook: null | {
        id: number;
        slug: string;
        translations: Record<string, Partial<Texts>>;
        cover: { id: number; url: string | null } | null;
        images: Image[];
        category_id: number | null;
        position: number;
        active: boolean;
        lock_version: number;
        public_url: string | null;
    };
    categories: Array<{ value: number; label: string }>;
    urls: { index: string; save: string; products: string };
    max_products_per_image: number;
    can: { manage: boolean };
}>();

const { locales, defaultLocale } = useStoreLocales();
const images = ref<Image[]>((props.lookbook?.images ?? []).map((image) => ({ ...image, alt: { ...image.alt }, products: [...image.products] })));
const coverId = ref<number | null>(props.lookbook?.cover?.id ?? null);
const picking = ref(false);
const { form, save } = useAdminForm({
    slug: props.lookbook?.slug ?? '',
    translations: Object.fromEntries(locales.map((locale) => [locale.code, { name: '', description: '', meta_title: '', meta_description: '', ...(props.lookbook?.translations[locale.code] ?? {}) }])) as Record<string, Texts>,
    category_id: props.lookbook?.category_id ?? (null as number | null),
    position: props.lookbook?.position ?? 0,
    active: props.lookbook?.active ?? false,
    lock_version: props.lookbook?.lock_version ?? null,
}, { lockVersion: () => props.lookbook?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function pick(items: MediaItem[]): void {
    for (const item of items) {
        if (!images.value.some((image) => image.media_id === item.id)) images.value.push({ media_id: item.id, alt: {}, thumb_url: item.thumb_url, products: [] });
    }
    picking.value = false;
}

async function searchProducts(image: Image, term: string): Promise<ProductOption[]> {
    const { data } = await getJson<{ data: ProductOption[] }>(props.urls.products, { q: term });
    return data.filter((product) => !image.products.some((selected) => selected.value === product.value));
}

function addProduct(image: Image, product: ProductOption): void {
    if (image.products.length < props.max_products_per_image && !image.products.some((selected) => selected.value === product.value)) {
        image.products.push(product);
    }
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        cover_media_id: coverId.value,
        images: images.value.map((image) => ({ media_id: image.media_id, alt: image.alt, product_ids: image.products.map((product) => Number(product.value)) })),
    }));
    if (props.lookbook) {
        save('put', props.urls.save);
    } else {
        save('post', props.urls.save);
    }
}

async function destroy(): Promise<void> {
    if (props.lookbook && (await confirmAction({ title: t('vani-lookbook::admin.form.delete_lookbook'), confirmLabel: t('vani-lookbook::admin.form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.urls.index}/${props.lookbook.id}`);
    }
}
</script>

<template>
    <div>
        <Head :title="lookbook ? t('vani-lookbook::admin.form.edit_lookbook') : t('vani-lookbook::admin.form.add_lookbook')" />
        <PageHeader :title="lookbook ? (lookbook.translations[defaultLocale]?.name ?? lookbook.slug) : t('vani-lookbook::admin.form.add_lookbook')">
            <Link :href="urls.index" :class="secondaryButton">{{ t('vani-lookbook::admin.form.list') }}</Link>
            <a v-if="lookbook?.public_url" :href="lookbook.public_url" target="_blank" rel="noopener" :class="secondaryButton">Xem trang</a>
        </PageHeader>

        <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="submit">
            <div class="space-y-4">
                <section v-for="locale in locales" :key="locale.code" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-medium">{{ locale.label }}</h2>
                    <FormField :label="t('vani-lookbook::admin.form.name') + (locale.code === defaultLocale ? ' *' : '')" :error="errors[`translations.${locale.code}.name`]">
                        <input v-model="form.translations[locale.code].name" :class="inputClass" maxlength="160" :disabled="!can.manage" />
                    </FormField>
                    <FormField :label="t('vani-lookbook::admin.form.description_markdown')" :error="errors[`translations.${locale.code}.description`]">
                        <textarea v-model="form.translations[locale.code].description" :class="inputClass" rows="4" maxlength="5000" :disabled="!can.manage" />
                    </FormField>
                    <div class="grid gap-3 md:grid-cols-2">
                        <FormField :label="t('vani-lookbook::admin.form.seo_title')"><input v-model="form.translations[locale.code].meta_title" :class="inputClass" maxlength="200" :disabled="!can.manage" /></FormField>
                        <FormField :label="t('vani-lookbook::admin.form.seo_description')"><input v-model="form.translations[locale.code].meta_description" :class="inputClass" maxlength="300" :disabled="!can.manage" /></FormField>
                    </div>
                </section>

                <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" data-lookbook-images>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium">{{ t('vani-lookbook::admin.form.photos_count', { count: images.length }) }}</h2>
                        <button v-if="can.manage" type="button" :class="secondaryButton" @click="picking = true">{{ t('vani-lookbook::admin.form.add_photos') }}</button>
                    </div>
                    <p v-if="errors.images" class="text-sm text-red-600">{{ errors.images }}</p>
                    <Sortable v-model="images" v-slot="{ item: image, index, handle }" tag="ol" item-tag="li" item-class="flex items-start gap-3 rounded border border-slate-100 p-2" :item-key="(image) => image.media_id" :disabled="!can.manage">
                        <SortHandle v-if="can.manage" v-bind="handle" />
                        <img v-if="image.thumb_url" :src="image.thumb_url" alt="" class="h-16 w-16 rounded object-cover" />
                        <div class="min-w-0 flex-1 space-y-1">
                            <input v-for="locale in locales" :key="locale.code" v-model="image.alt[locale.code]" :class="inputClass" :placeholder="t('vani-lookbook::admin.form.photo_description_label', { label: locale.label })" maxlength="200" :disabled="!can.manage" />
                            <ul v-if="image.products.length" class="flex flex-wrap gap-1">
                                <li v-for="(product, productIndex) in image.products" :key="product.value" class="inline-flex max-w-full items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs">
                                    <span class="min-w-0 break-words">{{ product.label }}</span>
                                    <button v-if="can.manage" type="button" :aria-label="t('vani-lookbook::admin.form.remove_product', { name: product.label })" class="px-1 text-red-600" @click="image.products.splice(productIndex, 1)">×</button>
                                    <span v-if="errors[`images.${index}.product_ids.${productIndex}`]" class="text-red-600">{{ errors[`images.${index}.product_ids.${productIndex}`] }}</span>
                                </li>
                            </ul>
                            <Combobox
                                v-if="can.manage && image.products.length < max_products_per_image"
                                :key="image.products.length"
                                model-value=""
                                :search="(term) => searchProducts(image, term)"
                                :placeholder="t('vani-lookbook::admin.form.search_products')"
                                :aria-label="t('vani-lookbook::admin.form.add_product_to_photo', { number: index + 1 })"
                                @select="(product) => addProduct(image, product)"
                            />
                            <p v-if="errors[`images.${index}.product_ids`]" class="text-sm text-red-600">{{ errors[`images.${index}.product_ids`] }}</p>
                        </div>
                        <button v-if="can.manage" type="button" :class="dangerButton" :aria-label="t('vani-lookbook::admin.form.remove')" @click="images.splice(index, 1)">×</button>
                    </Sortable>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" :disabled="!can.manage" /> {{ t('vani-lookbook::admin.form.active_shown_store') }}</label>
                    <FormField :label="t('vani-lookbook::admin.form.path_lookbook')" :error="errors.slug"><input v-model="form.slug" :class="inputClass" maxlength="120" :placeholder="t('vani-lookbook::admin.form.leave_empty_generated_from_name')" :disabled="!can.manage" /></FormField>
                    <FormField :label="t('vani-lookbook::admin.form.order')" :error="errors.position"><input v-model.number="form.position" type="number" min="0" :class="inputClass" :disabled="!can.manage" /></FormField>
                    <FormField :label="t('vani-lookbook::admin.form.related_category')" :hint="t('vani-lookbook::admin.form.see_more_hint')" :error="errors.category_id">
                        <Select v-model="form.category_id" :options="categories" :empty-label="t('vani-lookbook::admin.form.none')" :empty-value="null" :disabled="!can.manage" />
                    </FormField>
                    <div v-if="can.manage" class="flex gap-2">
                        <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-lookbook::admin.form.save') }}</button>
                        <button v-if="lookbook" type="button" :class="dangerButton" @click="destroy">{{ t('vani-lookbook::admin.form.delete') }}</button>
                    </div>
                </section>
                <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-medium">{{ t('vani-lookbook::admin.form.cover_image') }}</h2>
                    <MediaField v-model="coverId" :label="t('vani-lookbook::admin.form.cover_image_2')" :disabled="!can.manage" />
                    <p v-if="!coverId" class="text-xs text-slate-500">{{ t('vani-lookbook::admin.form.not_chosen_use_first_photo') }}</p>
                </section>
            </aside>
        </form>
        <MediaPicker v-model:open="picking" :title="t('vani-lookbook::admin.form.add_photos_lookbook')" multiple @select="pick" />
    </div>
</template>
