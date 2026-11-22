<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import { getJson } from '@admin/http';
import Combobox from '@admin/Components/Combobox.vue';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type ProductOption = { value: string; label: string };
type Item = { style_id: number; label: string; quantity: number };

/** Form combo: sản phẩm + số lượng mỗi món, % giảm khi mua đủ bộ, lịch chạy, ảnh. */
const props = defineProps<{
    bundle: null | {
        id: number;
        slug: string;
        name: string;
        description: string;
        image_media_id: number | null;
        discount_percent: number;
        active: boolean;
        starts_at: string | null;
        ends_at: string | null;
        position: number;
        items: Item[];
    };
    limits: { min_items: number; max_items: number; max_quantity: number; min_percent: number; max_percent: number };
    urls: { index: string; save: string; products: string };
    can: { manage: boolean };
}>();

const { form, save } = useAdminForm({
    name: props.bundle?.name ?? '',
    slug: props.bundle?.slug ?? '',
    description: props.bundle?.description ?? '',
    image_media_id: props.bundle?.image_media_id ?? (null as number | null),
    discount_percent: props.bundle?.discount_percent ?? 10,
    active: props.bundle?.active ?? true,
    starts_at: props.bundle?.starts_at ?? (null as string | null),
    ends_at: props.bundle?.ends_at ?? (null as string | null),
    position: props.bundle?.position ?? 0,
    items: (props.bundle?.items ?? []).map((item) => ({ ...item })) as Item[],
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

/** Đường dẫn gợi ý từ tên (bỏ dấu tiếng Việt) khi chưa nhập. */
function suggestSlug(): void {
    if (form.slug !== '') return;
    form.slug = form.name.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

async function searchProducts(term: string): Promise<ProductOption[]> {
    const { data } = await getJson<{ data: ProductOption[] }>(props.urls.products, { q: term });

    return data.filter((product) => !form.items.some((item) => String(item.style_id) === product.value));
}

function addProduct(product: ProductOption): void {
    if (form.items.length < props.limits.max_items && !form.items.some((item) => String(item.style_id) === product.value)) {
        form.items.push({ style_id: Number(product.value), label: product.label, quantity: 1 });
    }
}

function submit(): void {
    form.transform((data) => ({ ...data, items: data.items.map((item) => ({ style_id: item.style_id, quantity: item.quantity })) }));
    save(props.bundle ? 'put' : 'post', props.urls.save);
}

async function destroy(): Promise<void> {
    if (props.bundle && (await confirmAction({ title: t('vani-bundles::admin.form.delete_title'), message: t('vani-bundles::admin.form.delete_message'), confirmLabel: t('vani-bundles::admin.form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.urls.index}/${props.bundle.id}`);
    }
}
</script>

<template>
    <div>
        <Head :title="bundle ? bundle.name : t('vani-bundles::admin.form.add')" />
        <PageHeader :title="bundle ? bundle.name : t('vani-bundles::admin.form.add')">
            <Link :href="urls.index" :class="secondaryButton">{{ t('vani-bundles::admin.form.list') }}</Link>
            <button v-if="bundle && can.manage" type="button" :class="dangerButton" @click="destroy">{{ t('vani-bundles::admin.form.delete') }}</button>
        </PageHeader>

        <form class="grid max-w-3xl gap-4" @submit.prevent="submit">
            <fieldset :disabled="!can.manage" class="contents">
                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <FormField :label="t('vani-bundles::admin.form.name') + ' *'" :error="errors.name">
                        <input v-model="form.name" :class="inputClass" maxlength="120" @blur="suggestSlug" />
                    </FormField>
                    <FormField :label="t('vani-bundles::admin.form.slug') + ' *'" :error="errors.slug" :hint="t('vani-bundles::admin.form.slug_hint')">
                        <input v-model="form.slug" :class="inputClass" maxlength="120" />
                    </FormField>
                    <FormField class="sm:col-span-2" :label="t('vani-bundles::admin.form.description')" :error="errors.description">
                        <textarea v-model="form.description" :class="inputClass" rows="2" maxlength="500" />
                    </FormField>
                    <FormField :label="t('vani-bundles::admin.form.discount_percent') + ' *'" :error="errors.discount_percent" :hint="t('vani-bundles::admin.form.discount_hint')">
                        <input v-model.number="form.discount_percent" type="number" :min="limits.min_percent" :max="limits.max_percent" :class="inputClass" />
                    </FormField>
                    <FormField :label="t('vani-bundles::admin.form.position')" :error="errors.position">
                        <input v-model.number="form.position" type="number" min="0" :class="inputClass" />
                    </FormField>
                    <FormField :label="t('vani-bundles::admin.form.starts_at')" :error="errors.starts_at"><DateInput v-model="form.starts_at" with-time /></FormField>
                    <FormField :label="t('vani-bundles::admin.form.ends_at')" :error="errors.ends_at"><DateInput v-model="form.ends_at" with-time /></FormField>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.active" type="checkbox" /> {{ t('vani-bundles::admin.form.active') }}</label>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5" data-bundle-items>
                    <h2 class="mb-1 font-medium">{{ t('vani-bundles::admin.form.items') }}</h2>
                    <p class="mb-3 text-sm text-slate-500">{{ t('vani-bundles::admin.form.items_hint', { min: limits.min_items, max: limits.max_items }) }}</p>
                    <Sortable v-model="form.items" v-slot="{ item, index, handle }" list-class="mb-3 space-y-2" item-class="flex items-center gap-2" :item-label="(item) => item.label">
                        <SortHandle v-bind="handle" />
                        <span class="min-w-0 flex-1 truncate text-sm">{{ item.label }}</span>
                        <label class="flex items-center gap-1 text-sm text-slate-500">
                            ×
                            <input v-model.number="item.quantity" type="number" min="1" :max="limits.max_quantity" :class="[inputClass, 'w-20']" :aria-label="t('vani-bundles::admin.form.quantity_for', { name: item.label })" />
                        </label>
                        <button type="button" :class="dangerButton" :aria-label="t('vani-bundles::admin.form.remove')" @click="form.items.splice(index, 1)">×</button>
                        <p v-if="errors[`items.${index}.style_id`] || errors[`items.${index}.quantity`]" class="w-full text-sm text-red-600">{{ errors[`items.${index}.style_id`] ?? errors[`items.${index}.quantity`] }}</p>
                    </Sortable>
                    <Combobox
                        v-if="form.items.length < limits.max_items"
                        :key="form.items.length"
                        model-value=""
                        :search="searchProducts"
                        :placeholder="t('vani-bundles::admin.form.search_products')"
                        :aria-label="t('vani-bundles::admin.form.add_product')"
                        @select="addProduct"
                    />
                    <p v-if="errors.items" class="mt-2 text-sm text-red-600">{{ errors.items }}</p>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5">
                    <MediaField v-model="form.image_media_id" :label="t('vani-bundles::admin.form.image')" />
                </section>

                <div v-if="can.manage"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-bundles::admin.form.save') }}</button></div>
            </fieldset>
        </form>
    </div>
</template>
