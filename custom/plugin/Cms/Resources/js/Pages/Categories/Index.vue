<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Category {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    sort_order: number;
    posts_count: number;
}

/** Chuyên mục tin tức (vani.cms 1.4.0): một cấp, URL /tin-tuc/{đường dẫn} — không trùng đường dẫn bài viết. */
const props = defineProps<{ categories: Category[]; urls: { index: string; pages: string; posts: string }; can: { manage: boolean } }>();

const editing = ref<Category | null>(null);
const columns = [
    { key: 'name', label: t('vani-cms::admin.categories_index.name') },
    { key: 'slug', label: t('vani-cms::admin.categories_index.path'), class: 'font-mono text-xs' },
    { key: 'posts_count', label: t('vani-cms::admin.categories_index.posts'), align: 'right' as const },
    { key: 'actions', label: '', align: 'right' as const },
];
const blank = () => ({ name: '', slug: '', description: '', meta_title: '', meta_description: '', sort_order: props.categories.length });
const form = useForm(blank());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(category: Category): void {
    editing.value = category;
    form.defaults({ name: category.name, slug: category.slug, description: category.description ?? '', meta_title: category.meta_title ?? '', meta_description: category.meta_description ?? '', sort_order: category.sort_order });
    form.reset();
}

function cancel(): void {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    if (editing.value) {
        form.put(`${props.urls.index}/${editing.value.id}`, options);
    } else {
        form.post(props.urls.index, options);
    }
}

async function destroy(category: Category): Promise<void> {
    const message = category.posts_count ? t('vani-cms::admin.categories_index.posts_count_posts_become_uncategorised', { posts_count: category.posts_count }) : undefined;
    if ((await confirmAction({ title: t('vani-cms::admin.categories_index.delete_category_name', { name: category.name }), message, confirmLabel: t('vani-cms::admin.categories_index.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.urls.index}/${category.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('vani-cms::admin.categories_index.news_categories')" />
    <PageHeader :title="t('vani-cms::admin.categories_index.news_categories')" :subtitle="t('vani-cms::admin.categories_index.tin_tuc_path_shared_posts')">
        <Link :href="urls.posts" :class="secondaryButton">{{ t('vani-cms::admin.categories_index.posts') }}</Link>
        <Link :href="urls.pages" :class="secondaryButton">Trang</Link>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="categories" :row-label="(category) => category.name" :empty-text="t('vani-cms::admin.categories_index.no_categories_yet')">
                <template #cell-slug="{ row }">/tin-tuc/{{ row.slug }}</template>
                <template #cell-actions="{ row }">
                    <template v-if="can.manage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">{{ t('vani-cms::admin.categories_index.edit') }}</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">{{ t('vani-cms::admin.categories_index.delete') }}</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="can.manage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editing ? t('vani-cms::admin.categories_index.edit_category') : t('vani-cms::admin.categories_index.add_category') }}</h2>
            <FormField :label="t('vani-cms::admin.categories_index.name_2')" :error="errors.name"><input v-model="form.name" :class="inputClass" maxlength="120" /></FormField>
            <FormField :label="t('vani-cms::admin.categories_index.path_tin_tuc')" :error="errors.slug"><input v-model="form.slug" :class="inputClass" maxlength="120" :placeholder="t('vani-cms::admin.categories_index.leave_empty_generated_from_name')" /></FormField>
            <FormField :label="t('vani-cms::admin.categories_index.description')" :error="errors.description"><textarea v-model="form.description" :class="inputClass" rows="2" maxlength="500" /></FormField>
            <FormField :label="t('vani-cms::admin.categories_index.seo_title')" :error="errors.meta_title"><input v-model="form.meta_title" :class="inputClass" maxlength="200" /></FormField>
            <FormField :label="t('vani-cms::admin.categories_index.seo_description')" :error="errors.meta_description"><textarea v-model="form.meta_description" :class="inputClass" rows="2" maxlength="300" /></FormField>
            <FormField :label="t('vani-cms::admin.categories_index.order')" :error="errors.sort_order"><input v-model.number="form.sort_order" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-cms::admin.categories_index.save') }}</button>
                <button v-if="editing" type="button" :class="secondaryButton" @click="cancel">{{ t('vani-cms::admin.categories_index.cancel') }}</button>
            </div>
        </form>
    </div>
</template>
