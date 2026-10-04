<script setup lang="ts">
import FlashMessage from '@admin/Components/FlashMessage.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { HttpError, postJson } from '@admin/http';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    cover_path: string | null;
    cover_url: string | null;
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
        upload: string;
        preview: string;
        item: string | null;
    };
}>();

const form = useForm({
    title: props.item?.title ?? '',
    slug: props.item?.slug ?? '',
    body: props.item?.body ?? '',
    meta_title: props.item?.meta_title ?? '',
    meta_description: props.item?.meta_description ?? '',
    status: props.item?.status ?? 'draft',
    published_at: props.item?.published_at ?? '',
    excerpt: props.item?.excerpt ?? '',
    cover_path: props.item?.cover_path ?? '',
    show_in_header: props.item?.show_in_header ?? false,
    show_in_footer: props.item?.show_in_footer ?? false,
    sort_order: props.item?.sort_order ?? 0,
});
const coverUrl = ref<string | null>(props.item?.cover_url ?? null);
const previewHtml = ref<string | null>(null);
const busy = ref(false);
const uploadError = ref<string | null>(null);
const bodyField = ref<HTMLTextAreaElement | null>(null);
const label = computed(() => kindLabels[props.kind].singular);
const prefix = computed(() => (props.kind === 'pages' ? '/trang/' : '/tin-tuc/'));

function save(): void {
    const options = { preserveScroll: true };
    if (props.urls.item) {
        form.put(props.urls.item, options);
    } else {
        form.post(props.urls.store, options);
    }
}

function destroy(): void {
    if (props.urls.item && confirm(`Xoá ${label.value} "${props.item?.title}"? Không khôi phục được.`)) {
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

async function upload(file: File): Promise<{ path: string; url: string } | null> {
    uploadError.value = null;
    const data = new FormData();
    data.append('image', file);
    busy.value = true;
    try {
        return await postJson<{ path: string; url: string }>(props.urls.upload, data);
    } catch (error) {
        uploadError.value = error instanceof HttpError ? (error.errors.image?.[0] ?? error.message) : 'Không tải được ảnh.';
        return null;
    } finally {
        busy.value = false;
    }
}

async function insertImage(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    const result = await upload(file);
    if (!result) return;
    const markdown = `\n![](${result.url})\n`;
    const field = bodyField.value;
    const at = field?.selectionStart ?? form.body.length;
    form.body = form.body.slice(0, at) + markdown + form.body.slice(at);
}

async function setCover(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    const result = await upload(file);
    if (result) {
        form.cover_path = result.path;
        coverUrl.value = result.url;
    }
}

function removeCover(): void {
    form.cover_path = '';
    coverUrl.value = null;
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
    <FlashMessage />

    <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="save">
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
                        <label v-if="can.manage" :class="[secondaryButton, 'cursor-pointer']">
                            Chèn ảnh
                            <input type="file" accept="image/*" class="hidden" @change="insertImage" />
                        </label>
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
                <p v-if="uploadError" class="mt-1 text-sm text-red-600">
                    {{ uploadError }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Hỗ trợ tiêu đề (##), in đậm, danh sách, liên kết, ảnh, bảng. HTML thô không được hiển thị.</p>
            </div>
        </div>

        <aside class="space-y-4">
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <FormField label="Trạng thái" :error="form.errors.status">
                    <select v-model="form.status" :class="inputClass" :disabled="!can.manage">
                        <option value="draft">Nháp</option>
                        <option value="published">Đăng</option>
                    </select>
                </FormField>
                <FormField label="Thời điểm đăng (giờ VN)" :error="form.errors.published_at">
                    <input v-model="form.published_at" type="datetime-local" :class="inputClass" :disabled="!can.manage" />
                </FormField>
                <p class="text-xs text-slate-500">Đăng + để trống: đăng ngay. Chọn thời điểm trong tương lai: tự hiện khi tới giờ.</p>
                <div v-if="can.manage" class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="form.processing || busy">Lưu</button>
                    <button v-if="item" type="button" :class="dangerButton" @click="destroy">Xoá</button>
                </div>
            </section>

            <section v-if="kind === 'posts'" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">Ảnh bìa</h2>
                <img v-if="coverUrl" :src="coverUrl" alt="" class="w-full rounded" />
                <div v-if="can.manage" class="flex gap-2">
                    <label :class="[secondaryButton, 'cursor-pointer']">
                        {{ coverUrl ? 'Đổi ảnh' : 'Chọn ảnh' }}
                        <input type="file" accept="image/*" class="hidden" @change="setCover" />
                    </label>
                    <button v-if="coverUrl" type="button" :class="secondaryButton" @click="removeCover">Bỏ</button>
                </div>
                <p v-if="form.errors.cover_path" class="text-sm text-red-600">
                    {{ form.errors.cover_path }}
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
}
.vani-admin-prose :deep(th),
.vani-admin-prose :deep(td) {
    border: 1px solid #e2e8f0;
    padding: 0.25em 0.5em;
}
</style>
