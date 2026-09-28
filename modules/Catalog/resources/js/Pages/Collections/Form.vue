<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { BrandRef, NavItem, Translations } from '../../types';

const props = defineProps<{
    brand: BrandRef;
    nav: NavItem[];
    collection: null | { id: number; slug: string; status: string; position: number; translations: Translations<'name' | 'description'>; style_codes: string };
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
const form = useForm({
    slug: props.collection?.slug ?? '',
    status: props.collection?.status ?? 'active',
    position: props.collection?.position ?? 0,
    translations: {
        vi: { name: props.collection?.translations.vi?.name ?? '', description: props.collection?.translations.vi?.description ?? '' },
        en: { name: props.collection?.translations.en?.name ?? '', description: props.collection?.translations.en?.description ?? '' },
    },
    style_codes: props.collection?.style_codes ?? '',
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.collection) {
        form.put(`${baseUrl.value}/${props.collection.id}`, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

function destroy(): void {
    if (props.collection && confirm('Xoá bộ sưu tập này? Sản phẩm không bị xoá.')) {
        router.delete(`${baseUrl.value}/${props.collection.id}`);
    }
}
</script>

<template>
    <Head :title="collection ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập'" />
    <CatalogTabs :brand="brand" :nav="nav" active="collections" />
    <PageHeader :title="collection ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="collection" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid max-w-4xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField label="Tên (vi)" :error="errors['translations.vi.name']"><input v-model="form.translations.vi.name" :class="inputClass" /></FormField>
        <FormField label="Tên (en)"><input v-model="form.translations.en.name" :class="inputClass" /></FormField>
        <FormField label="Mô tả (vi)"><textarea v-model="form.translations.vi.description" :class="inputClass" rows="3" /></FormField>
        <FormField label="Mô tả (en)"><textarea v-model="form.translations.en.description" :class="inputClass" rows="3" /></FormField>
        <FormField label="Slug" :error="errors.slug"><input v-model="form.slug" :class="inputClass" /></FormField>
        <div class="grid grid-cols-2 gap-4">
            <FormField label="Trạng thái" :error="errors.status">
                <select v-model="form.status" :class="inputClass">
                    <option value="active">Hiển thị</option>
                    <option value="hidden">Ẩn</option>
                </select>
            </FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
        </div>
        <FormField class="sm:col-span-2" label="Mã sản phẩm theo thứ tự hiển thị" hint="Mỗi dòng một mã (hoặc cách nhau bằng dấu phẩy)." :error="errors.style_codes">
            <textarea v-model="form.style_codes" :class="inputClass" class="font-mono" rows="8" />
        </FormField>
        <div class="sm:col-span-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
