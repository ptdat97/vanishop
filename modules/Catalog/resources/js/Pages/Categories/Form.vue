<script setup lang="ts">
import { useAdminForm } from '@admin/forms';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FileInput from '@admin/Components/FileInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions, recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionSection, type MediaItem } from '@admin/types';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem, Translations } from '../../types';

type Fields = 'name' | 'slug' | 'description' | 'meta_title' | 'meta_description';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    category: null | {
        id: number;
        slug: string;
        parent_id: number | null;
        status: string;
        position: number;
        lock_version: number;
        translations: Translations<Fields>;
        image_url: string | null;
    };
    parents: Array<{ id: number; label: string }>;
    extensionSections: ExtensionSection[];
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'categories')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const locale = ref(defaultLocale);

const emptyTranslation = { name: '', slug: '', description: '', meta_title: '', meta_description: '' };
const { form, save } = useAdminForm({
    slug: props.category?.slug ?? '',
    parent_id: props.category?.parent_id ?? null,
    status: props.category?.status ?? 'active',
    position: props.category?.position ?? 0,
    lock_version: props.category?.lock_version ?? null,
    translations: translationsFor(locales, emptyTranslation, props.category?.translations),
    extensions: initialExtensionValues(props.extensionSections),
}, { lockVersion: () => props.category?.lock_version });

const imageForm = useForm<{ image: File | null; alt: string }>({ image: null, alt: '' });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.category) {
        save('put', `${baseUrl.value}/${props.category.id}`);
    } else {
        save('post', baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.category && (await confirmAction({ title: 'Xoá danh mục này?', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.category.id}`);
    }
}

function uploadImage(): void {
    if (props.category) {
        imageForm.post(`${baseUrl.value}/${props.category.id}/image`, { forceFormData: true, preserveScroll: true, onSuccess: () => imageForm.reset() });
    }
}

const picking = ref(false);

function pickImage(items: MediaItem[]): void {
    if (props.category && items[0]) {
        router.post(`${baseUrl.value}/${props.category.id}/image/library`, { media_id: items[0].id, alt: imageForm.alt || null }, { preserveScroll: true });
    }
}

function removeImage(): void {
    if (props.category) {
        router.delete(`${baseUrl.value}/${props.category.id}/image`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="category ? 'Sửa danh mục' : 'Thêm danh mục'" />
    <CatalogTabs :nav="nav" active="categories" />
    <PageHeader :title="category ? 'Sửa danh mục' : 'Thêm danh mục'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="category" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 lg:col-span-2">
            <div class="flex gap-2 text-sm">
                <button v-for="option in locales" :key="option.code" type="button" class="rounded px-2 py-1" :class="locale === option.code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = option.code">
                    {{ option.label }}
                </button>
            </div>
            <FormField :label="locale === defaultLocale ? 'Tên (bắt buộc)' : 'Tên'" :error="errors[`translations.${locale}.name`]">
                <input v-model="form.translations[locale].name" :class="inputClass" type="text" />
            </FormField>
            <FormField v-if="locale !== defaultLocale" label="Slug riêng cho ngôn ngữ này" hint="Trống = dùng slug gốc. Đổi slug đã được index làm gãy link cũ." :error="errors[`translations.${locale}.slug`]">
                    <div class="flex gap-2">
                        <input v-model="form.translations[locale].slug" :class="inputClass" :placeholder="slugify(form.translations[locale].name ?? '') || form.slug" />
                        <button type="button" :class="secondaryButton" :disabled="!form.translations[locale].name" @click="form.translations[locale].slug = slugify(form.translations[locale].name ?? '')">Tạo từ tên</button>
                    </div>
                </FormField>
            <FormField label="Mô tả" :error="errors[`translations.${locale}.description`]">
                <textarea v-model="form.translations[locale].description" :class="inputClass" rows="4" />
            </FormField>
            <FormField label="Meta title (SEO)" :error="errors[`translations.${locale}.meta_title`]">
                <input v-model="form.translations[locale].meta_title" :class="inputClass" type="text" />
            </FormField>
            <FormField label="Meta description (SEO)" :error="errors[`translations.${locale}.meta_description`]">
                <textarea v-model="form.translations[locale].meta_description" :class="inputClass" rows="2" />
            </FormField>
            <ExtensionFormSections v-model="form.extensions" :sections="extensionSections" :errors="errors" />
        </div>

        <div class="space-y-4">
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <FormField label="Slug (đường dẫn)" hint="Chữ thường không dấu, số và gạch ngang." :error="errors.slug">
                    <input v-model="form.slug" :class="inputClass" type="text" />
                </FormField>
                <FormField label="Danh mục cha" :error="errors.parent_id">
                    <Select v-model="form.parent_id" :options="recordOptions(parents)" empty-label="— Gốc —" :empty-value="null" />
                </FormField>
                <FormField label="Trạng thái" :error="errors.status">
                    <Select v-model="form.status" :options="labelOptions(labels.visibility)" />
                </FormField>
                <FormField label="Thứ tự" :error="errors.position">
                    <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
                </FormField>
                <button type="submit" :class="primaryButton" :disabled="form.processing" class="w-full">Lưu</button>
            </div>

            <div v-if="category" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-medium">Ảnh danh mục</h2>
                <img v-if="category.image_url" :src="category.image_url" alt="" class="w-full rounded" />
                <FileInput v-model="imageForm.image" accept="image/jpeg,image/png,image/webp" :max-kb="10240" label="Chọn ảnh" :progress="imageForm.progress?.percentage ?? null" :error="imageForm.errors.image" />
                <input v-model="imageForm.alt" :class="inputClass" type="text" placeholder="Mô tả ảnh (alt)" />
                <div class="flex gap-2">
                    <button type="button" :class="secondaryButton" :disabled="!imageForm.image || imageForm.processing" @click="uploadImage">Tải lên</button>
                    <button type="button" :class="secondaryButton" @click="picking = true">Chọn từ thư viện</button>
                    <button v-if="category.image_url" type="button" :class="dangerButton" @click="removeImage">Gỡ ảnh</button>
                </div>
                <MediaPicker v-model:open="picking" title="Chọn ảnh danh mục" :multiple="false" @select="pickImage" />
            </div>
        </div>
    </form>
</template>
