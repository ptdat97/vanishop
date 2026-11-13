<script setup lang="ts">
import { useAdminForm } from '@admin/forms';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import DateInput from '@admin/Components/DateInput.vue';
import Combobox from '@admin/Components/Combobox.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { getJson, postJson } from '@admin/http';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { kindLabels, type Kind } from './labels';

type Item = {
    id: number;
    title: string;
    slug: string;
    body: string;
    meta_title: string | null;
    meta_description: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    excerpt: string | null;
    cover_media_id: number | null;
    cover_url: string | null;
    category_id: number | null;
    products: Array<{ id: number; name: string; visible: boolean }>;
    show_in_header: boolean;
    show_in_footer: boolean;
    sort_order: number;
    public_url: string | null;
    preview_url: string;
};

const props = defineProps<{
    kind: Kind;
    item: Item | null;
    can: { manage: boolean };
    urls: {
        index: string;
        store: string;
        preview: string;
        item: string | null;
        categories?: string;
        products?: string;
    };
    categories: Array<{ value: number; label: string }>;
}>();

type ProductOption = { id: number; name: string; visible?: boolean; code?: string };

const { form, save } = useAdminForm({
    title: props.item?.title ?? '',
    slug: props.item?.slug ?? '',
    body: props.item?.body ?? '',
    meta_title: props.item?.meta_title ?? '',
    meta_description: props.item?.meta_description ?? '',
    status: props.item?.status ?? 'draft',
    published_at: props.item?.published_at ?? '',
    excerpt: props.item?.excerpt ?? '',
    cover_media_id: props.item?.cover_media_id ?? (null as number | null),
    remove_cover: false,
    category_id: props.item?.category_id ?? (null as number | null),
    product_ids: (props.item?.products ?? []).map((product) => product.id),
    show_in_header: props.item?.show_in_header ?? false,
    show_in_footer: props.item?.show_in_footer ?? false,
    sort_order: props.item?.sort_order ?? 0,
});
const coverUrl = ref<string | null>(props.item?.cover_url ?? null);
// Sản phẩm gắn cuối bài (1.4.0): tối đa 12, giữ thứ tự.
const MAX_PRODUCTS = 12;
const chosen = ref<ProductOption[]>([...(props.item?.products ?? [])]);
/** Kết quả tìm gần nhất (để lấy lại sản phẩm khi chọn trong Combobox). */
const productResults = new Map<string, ProductOption>();

function syncProducts(): void {
    form.product_ids = chosen.value.map((product) => product.id);
}

async function searchProducts(term: string): Promise<Array<{ value: string; label: string }>> {
    if (!props.urls.products) return [];
    const response = await getJson<{ data: ProductOption[] }>(props.urls.products, { q: term });
    return response.data
        .filter((product) => !chosen.value.some((item) => item.id === product.id))
        .map((product) => {
            productResults.set(String(product.id), product);
            return { value: String(product.id), label: product.code ? `${product.name} · ${product.code}` : product.name };
        });
}

function addProduct(option: { value: string }): void {
    const product = productResults.get(option.value);
    if (!product || chosen.value.length >= MAX_PRODUCTS || chosen.value.some((item) => item.id === product.id)) return;
    chosen.value.push({ ...product, visible: true });
    syncProducts();
}

function reorderProducts(products: typeof chosen.value): void {
    chosen.value = products;
    syncProducts();
}

function removeProduct(index: number): void {
    chosen.value.splice(index, 1);
    syncProducts();
}
const previewHtml = ref<string | null>(null);
const busy = ref(false);
const picking = ref<'cover' | 'body' | null>(null);
const bodyField = ref<HTMLTextAreaElement | null>(null);
const label = computed(() => kindLabels[props.kind].singular);
const prefix = computed(() => (props.kind === 'pages' ? '/trang/' : '/tin-tuc/'));

function submit(): void {
    if (props.urls.item) {
        save('put', props.urls.item);
    } else {
        save('post', props.urls.store, { preserveScroll: true });
    }
}

async function destroy(): Promise<void> {
    if (props.urls.item && (await confirmAction({ title: `Xoá ${label.value} "${props.item?.title}"?`, message: 'Không khôi phục được.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(props.urls.item);
    }
}

async function togglePreview(): Promise<void> {
    if (previewHtml.value !== null) {
        previewHtml.value = null;
        return;
    }
    busy.value = true;
    try {
        previewHtml.value = (
            await postJson<{ html: string }>(props.urls.preview, {
                body: form.body,
            })
        ).html;
    } finally {
        busy.value = false;
    }
}

/** Ảnh từ Thư viện ảnh chèn dạng `![mô tả](media:ID)`: URL thật (kèm srcset) được tạo lúc hiển thị. */
function insertImages(items: MediaItem[]): void {
    const markdown = items.map((item) => `\n![${item.name.replace(/\.[a-z0-9]+$/i, '').replace(/[[\]]/g, '')}](media:${item.id})\n`).join('');
    const field = bodyField.value;
    const at = field?.selectionStart ?? form.body.length;
    form.body = form.body.slice(0, at) + markdown + form.body.slice(at);
}

function setCover([item]: MediaItem[]): void {
    if (item) {
        form.cover_media_id = item.id;
        form.remove_cover = false;
        coverUrl.value = item.thumb_url;
    }
}

function removeCover(): void {
    form.cover_media_id = null;
    form.remove_cover = true;
    coverUrl.value = null;
}

function onPicked(items: MediaItem[]): void {
    if (picking.value === 'cover') {
        setCover(items);
    } else {
        insertImages(items);
    }
}
</script>

<template>
    <Head :title="item ? item.title : `Thêm ${label}`" />
    <PageHeader :title="item ? item.title : `Thêm ${label}`" :subtitle="item?.public_url ?? undefined">
        <Link :href="urls.index" :class="secondaryButton">← {{ kindLabels[kind].plural }}</Link>
        <a v-if="item" :href="item.public_url ?? item.preview_url" target="_blank" rel="noopener" :class="secondaryButton">
            {{ item.public_url ? 'Xem trên cửa hàng' : 'Xem trước' }}
        </a>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="submit">
        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <FormField label="Tiêu đề" :error="form.errors.title">
                <input v-model="form.title" :class="inputClass" maxlength="200" required :disabled="!can.manage" />
            </FormField>
            <FormField :label="`Đường dẫn (${prefix}…)`" :error="form.errors.slug">
                <input v-model="form.slug" :class="inputClass" maxlength="120" placeholder="Để trống: tạo từ tiêu đề" :disabled="!can.manage" />
            </FormField>
            <FormField v-if="kind === 'posts'" label="Tóm tắt" :error="form.errors.excerpt">
                <textarea
                    v-model="form.excerpt"
                    :class="inputClass"
                    rows="2"
                    maxlength="500"
                    placeholder="Để trống: lấy đoạn đầu nội dung"
                    :disabled="!can.manage"
                />
            </FormField>

            <div>
                <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-medium">Nội dung (Markdown)</span>
                    <div class="flex items-center gap-2">
                        <button v-if="can.manage" type="button" :class="secondaryButton" :disabled="previewHtml !== null" @click="picking = 'body'">Chèn ảnh</button>
                        <button type="button" :class="secondaryButton" :disabled="busy" @click="togglePreview">
                            {{ previewHtml === null ? 'Xem trước' : 'Soạn tiếp' }}
                        </button>
                    </div>
                </div>
                <!-- HTML xem trước do server render (bỏ HTML thô), giống storefront. -->
                <div v-if="previewHtml !== null" class="vani-admin-prose min-h-64 rounded-md border border-slate-200 p-4 text-sm" v-html="previewHtml" />
                <textarea
                    v-else
                    ref="bodyField"
                    v-model="form.body"
                    :class="[inputClass, 'min-h-96 font-mono']"
                    required
                    :disabled="!can.manage"
                    placeholder="## Tiêu đề mục&#10;&#10;Đoạn văn… **in đậm**, [liên kết](https://…), - danh sách"
                />
                <p v-if="form.errors.body" class="mt-1 text-sm text-red-600">
                    {{ form.errors.body }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Hỗ trợ tiêu đề (##), in đậm, danh sách, liên kết, ảnh, bảng. HTML thô không được hiển thị. Ảnh chèn từ thư viện có dạng
                    <code>![mô tả](media:123)</code> — sửa phần mô tả cho đúng nội dung ảnh.
                </p>
            </div>
        </div>

        <aside class="space-y-4">
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <FormField label="Trạng thái" :error="form.errors.status">
                    <Select
                        v-model="form.status"
                        :options="[
                            { value: 'draft', label: 'Nháp' },
                            { value: 'published', label: 'Đăng' },
                        ]"
                        :disabled="!can.manage"
                    />
                </FormField>
                <FormField label="Thời điểm đăng (giờ VN)" :error="form.errors.published_at">
                    <DateInput v-model="form.published_at" with-time :disabled="!can.manage" />
                </FormField>
                <p class="text-xs text-slate-500">Đăng + để trống: đăng ngay. Chọn thời điểm trong tương lai: tự hiện khi tới giờ.</p>
                <div v-if="can.manage" class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="form.processing || busy">Lưu</button>
                    <button v-if="item" type="button" :class="dangerButton" @click="destroy">Xoá</button>
                </div>
            </section>

            <section v-if="kind === 'posts'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <FormField label="Chuyên mục" :error="form.errors.category_id">
                    <Select v-model="form.category_id" :options="categories" empty-label="— Không chuyên mục —" :empty-value="null" :disabled="!can.manage" />
                </FormField>
                <Link v-if="urls.categories" :href="urls.categories" class="text-xs text-indigo-600 hover:underline">Quản lý chuyên mục</Link>
            </section>

            <section v-if="kind === 'posts'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4" data-post-products>
                <h2 class="text-sm font-medium">Sản phẩm trong bài ({{ chosen.length }}/{{ MAX_PRODUCTS }})</h2>
                <Sortable v-if="chosen.length" v-slot="{ item: product, index, handle }" :model-value="chosen" tag="ol" item-tag="li" list-class="space-y-1 text-sm" item-class="flex items-center gap-1" :item-key="(product) => product.id" :item-label="(product) => product.name" :disabled="!can.manage" @update:model-value="reorderProducts">
                        <SortHandle v-if="can.manage" v-bind="handle" />
                        <span class="flex-1 truncate" :class="product.visible === false ? 'text-slate-400' : ''">{{ product.name }}<template v-if="product.visible === false"> (đang ẩn)</template></span>
                        <button v-if="can.manage" type="button" class="px-1 text-red-600" aria-label="Bỏ" @click="removeProduct(index)">×</button>
                </Sortable>
                <template v-if="can.manage && chosen.length < MAX_PRODUCTS">
                    <Combobox :key="chosen.length" model-value="" :search="searchProducts" placeholder="Tìm sản phẩm theo tên/mã…" aria-label="Thêm sản phẩm" @select="addProduct" />
                </template>
                <p v-if="form.errors.product_ids" class="text-sm text-red-600">{{ form.errors.product_ids }}</p>
            </section>

            <section v-if="kind === 'posts'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">Ảnh bìa</h2>
                <img v-if="coverUrl" :src="coverUrl" alt="" class="w-full rounded" />
                <div v-if="can.manage" class="flex gap-2">
                    <button type="button" :class="secondaryButton" @click="picking = 'cover'">{{ coverUrl ? 'Đổi ảnh' : 'Chọn ảnh' }}</button>
                    <button v-if="coverUrl" type="button" :class="secondaryButton" @click="removeCover">Bỏ</button>
                </div>
                <p v-if="form.errors.cover_media_id" class="text-sm text-red-600">
                    {{ form.errors.cover_media_id }}
                </p>
            </section>

            <section v-if="kind === 'pages'" class="space-y-2 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="font-medium">Hiển thị</h2>
                <label class="flex items-center gap-2"
                    ><input v-model="form.show_in_header" type="checkbox" :disabled="!can.manage" /> Link ở menu đầu trang</label
                >
                <label class="flex items-center gap-2"><input v-model="form.show_in_footer" type="checkbox" :disabled="!can.manage" /> Link ở chân trang</label>
                <FormField label="Thứ tự" :error="form.errors.sort_order">
                    <input v-model.number="form.sort_order" type="number" min="0" :class="inputClass" :disabled="!can.manage" />
                </FormField>
            </section>

            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">SEO</h2>
                <FormField label="Tiêu đề SEO" :error="form.errors.meta_title">
                    <input v-model="form.meta_title" :class="inputClass" maxlength="200" placeholder="Mặc định: tiêu đề" :disabled="!can.manage" />
                </FormField>
                <FormField label="Mô tả SEO" :error="form.errors.meta_description">
                    <textarea
                        v-model="form.meta_description"
                        :class="inputClass"
                        rows="3"
                        maxlength="300"
                        placeholder="Mặc định: đoạn đầu nội dung"
                        :disabled="!can.manage"
                    />
                </FormField>
            </section>
        </aside>
    </form>
    <MediaPicker
        :open="picking !== null"
        :title="picking === 'cover' ? 'Chọn ảnh bìa' : 'Chèn ảnh vào nội dung'"
        :multiple="picking === 'body'"
        @update:open="(value) => !value && (picking = null)"
        @select="onPicked"
    />
</template>

<style scoped>
.vani-admin-prose :deep(h2) {
    margin: 1.25em 0 0.5em;
    font-size: 1.25rem;
    font-weight: 600;
}
.vani-admin-prose :deep(h3) {
    margin: 1em 0 0.5em;
    font-weight: 600;
}
.vani-admin-prose :deep(p),
.vani-admin-prose :deep(ul),
.vani-admin-prose :deep(ol),
.vani-admin-prose :deep(table) {
    margin: 0.75em 0;
}
.vani-admin-prose :deep(ul) {
    list-style: disc;
    padding-left: 1.5em;
}
.vani-admin-prose :deep(ol) {
    list-style: decimal;
    padding-left: 1.5em;
}
.vani-admin-prose :deep(a) {
    color: #4f46e5;
    text-decoration: underline;
}
.vani-admin-prose :deep(img) {
    max-width: 100%;
    height: auto;
}
.vani-admin-prose :deep(th),
.vani-admin-prose :deep(td) {
    border: 1px solid #e2e8f0;
    padding: 0.25em 0.5em;
}
</style>
