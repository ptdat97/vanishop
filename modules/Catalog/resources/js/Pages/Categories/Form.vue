<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem, Translations } from '../../types';

type Fields = 'name' | 'description' | 'meta_title' | 'meta_description';

const props = defineProps<{
    nav: NavItem[];
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
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'categories')?.url ?? '');
const locale = ref<'vi' | 'en'>('vi');

const emptyTranslation = { name: '', description: '', meta_title: '', meta_description: '' };
const form = useForm({
    slug: props.category?.slug ?? '',
    parent_id: props.category?.parent_id ?? null,
    status: props.category?.status ?? 'active',
    position: props.category?.position ?? 0,
    lock_version: props.category?.lock_version ?? null,
    translations: {
        vi: { ...emptyTranslation, ...props.category?.translations.vi },
        en: { ...emptyTranslation, ...props.category?.translations.en },
    },
});

const imageForm = useForm<{ image: File | null; alt: string }>({ image: null, alt: '' });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.category) {
        form.put(`${baseUrl.value}/${props.category.id}`, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

function destroy(): void {
    if (props.category && confirm('Xoá danh mục này?')) {
        router.delete(`${baseUrl.value}/${props.category.id}`);
    }
}

function uploadImage(): void {
    if (props.category) {
        imageForm.post(`${baseUrl.value}/${props.category.id}/image`, { forceFormData: true, preserveScroll: true, onSuccess: () => imageForm.reset() });
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
                <button v-for="code in ['vi', 'en'] as const" :key="code" type="button" class="rounded px-2 py-1" :class="locale === code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = code">
                    {{ code === 'vi' ? 'Tiếng Việt' : 'English' }}
                </button>
            </div>
            <FormField :label="locale === 'vi' ? 'Tên (bắt buộc)' : 'Tên'" :error="errors[`translations.${locale}.name`]">
                <input v-model="form.translations[locale].name" :class="inputClass" type="text" />
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
        </div>

        <div class="space-y-4">
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                <FormField label="Slug (đường dẫn)" hint="Chữ thường không dấu, số và gạch ngang." :error="errors.slug">
                    <input v-model="form.slug" :class="inputClass" type="text" />
                </FormField>
                <FormField label="Danh mục cha" :error="errors.parent_id">
                    <select v-model="form.parent_id" :class="inputClass">
                        <option :value="null">— Gốc —</option>
                        <option v-for="parent in parents" :key="parent.id" :value="parent.id">{{ parent.label }}</option>
                    </select>
                </FormField>
                <FormField label="Trạng thái" :error="errors.status">
                    <select v-model="form.status" :class="inputClass">
                        <option value="active">Hiển thị</option>
                        <option value="hidden">Ẩn</option>
                    </select>
                </FormField>
                <FormField label="Thứ tự" :error="errors.position">
                    <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
                </FormField>
                <p v-if="errors.lock_version" class="text-sm text-red-600">{{ errors.lock_version }}</p>
                <button type="submit" :class="primaryButton" :disabled="form.processing" class="w-full">Lưu</button>
            </div>

            <div v-if="category" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-medium">Ảnh danh mục</h2>
                <img v-if="category.image_url" :src="category.image_url" alt="" class="w-full rounded" />
                <input type="file" accept="image/jpeg,image/png,image/webp" class="text-sm" @input="imageForm.image = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                <input v-model="imageForm.alt" :class="inputClass" type="text" placeholder="Mô tả ảnh (alt)" />
                <p v-if="imageForm.errors.image" class="text-sm text-red-600">{{ imageForm.errors.image }}</p>
                <div class="flex gap-2">
                    <button type="button" :class="secondaryButton" :disabled="!imageForm.image || imageForm.processing" @click="uploadImage">Tải lên</button>
                    <button v-if="category.image_url" type="button" :class="dangerButton" @click="removeImage">Gỡ ảnh</button>
                </div>
            </div>
        </div>
    </form>
</template>
