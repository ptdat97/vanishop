<script setup lang="ts">
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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Texts = { name: string; description: string; meta_title: string; meta_description: string };
type Image = { media_id: number; alt: Record<string, string>; thumb_url: string | null };

/** Form lookbook (đặc tả §3.2): tên/mô tả theo ngôn ngữ, đường dẫn, ảnh bìa + ảnh từ Thư viện ảnh (thứ tự, alt), danh mục. */
const props = defineProps<{
    lookbook: null | {
        id: number;
        slug: string;
        translations: Record<string, Partial<Texts>>;
        cover: { id: number; url: string | null } | null;
        images: Array<{ media_id: number; alt: Record<string, string>; thumb_url: string | null }>;
        category_id: number | null;
        position: number;
        active: boolean;
        public_url: string | null;
    };
    categories: Array<{ value: number; label: string }>;
    urls: { index: string; save: string };
    can: { manage: boolean };
}>();

const { locales, defaultLocale } = useStoreLocales();
const images = ref<Image[]>((props.lookbook?.images ?? []).map((image) => ({ ...image, alt: { ...image.alt } })));
const coverId = ref<number | null>(props.lookbook?.cover?.id ?? null);
const picking = ref(false);
const form = useForm({
    slug: props.lookbook?.slug ?? '',
    translations: Object.fromEntries(locales.map((locale) => [locale.code, { name: '', description: '', meta_title: '', meta_description: '', ...(props.lookbook?.translations[locale.code] ?? {}) }])) as Record<string, Texts>,
    category_id: props.lookbook?.category_id ?? (null as number | null),
    position: props.lookbook?.position ?? 0,
    active: props.lookbook?.active ?? false,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function pick(items: MediaItem[]): void {
    for (const item of items) {
        if (!images.value.some((image) => image.media_id === item.id)) images.value.push({ media_id: item.id, alt: {}, thumb_url: item.thumb_url });
    }
    picking.value = false;
}

function save(): void {
    form.transform((data) => ({
        ...data,
        cover_media_id: coverId.value,
        images: images.value.map((image) => ({ media_id: image.media_id, alt: image.alt })),
    }));
    if (props.lookbook) {
        form.put(props.urls.save, { preserveScroll: true });
    } else {
        form.post(props.urls.save);
    }
}

async function destroy(): Promise<void> {
    if (props.lookbook && (await confirmAction({ title: 'Xoá lookbook này?', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.urls.index}/${props.lookbook.id}`);
    }
}
</script>

<template>
    <Head :title="lookbook ? 'Sửa lookbook' : 'Thêm lookbook'" />
    <PageHeader :title="lookbook ? (lookbook.translations[defaultLocale]?.name ?? lookbook.slug) : 'Thêm lookbook'">
        <Link :href="urls.index" :class="secondaryButton">Danh sách</Link>
        <a v-if="lookbook?.public_url" :href="lookbook.public_url" target="_blank" rel="noopener" :class="secondaryButton">Xem trang</a>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="save">
        <div class="space-y-4">
            <section v-for="locale in locales" :key="locale.code" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-medium">{{ locale.label }}</h2>
                <FormField :label="'Tên' + (locale.code === defaultLocale ? ' *' : '')" :error="errors[`translations.${locale.code}.name`]">
                    <input v-model="form.translations[locale.code].name" :class="inputClass" maxlength="160" :disabled="!can.manage" />
                </FormField>
                <FormField label="Mô tả (Markdown)" :error="errors[`translations.${locale.code}.description`]">
                    <textarea v-model="form.translations[locale.code].description" :class="inputClass" rows="4" maxlength="5000" :disabled="!can.manage" />
                </FormField>
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField label="Tiêu đề SEO"><input v-model="form.translations[locale.code].meta_title" :class="inputClass" maxlength="200" :disabled="!can.manage" /></FormField>
                    <FormField label="Mô tả SEO"><input v-model="form.translations[locale.code].meta_description" :class="inputClass" maxlength="300" :disabled="!can.manage" /></FormField>
                </div>
            </section>

            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" data-lookbook-images>
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium">Ảnh ({{ images.length }})</h2>
                    <button v-if="can.manage" type="button" :class="secondaryButton" @click="picking = true">Thêm ảnh</button>
                </div>
                <p v-if="errors.images" class="text-sm text-red-600">{{ errors.images }}</p>
                <Sortable v-model="images" v-slot="{ item: image, index, handle }" tag="ol" item-tag="li" item-class="flex items-start gap-3 rounded border border-slate-100 p-2" :item-key="(image) => image.media_id" :disabled="!can.manage">
                        <SortHandle v-if="can.manage" v-bind="handle" />
                        <img v-if="image.thumb_url" :src="image.thumb_url" alt="" class="h-16 w-16 rounded object-cover" />
                        <div class="flex-1 space-y-1">
                            <input v-for="locale in locales" :key="locale.code" v-model="image.alt[locale.code]" :class="inputClass" :placeholder="`Mô tả ảnh (${locale.label})`" maxlength="200" :disabled="!can.manage" />
                        </div>
                        <button v-if="can.manage" type="button" :class="dangerButton" aria-label="Bỏ" @click="images.splice(index, 1)">×</button>
                </Sortable>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" :disabled="!can.manage" /> Đang bật (hiện trên cửa hàng)</label>
                <FormField label="Đường dẫn (/lookbook/…)" :error="errors.slug"><input v-model="form.slug" :class="inputClass" maxlength="120" placeholder="Để trống: tạo từ tên" :disabled="!can.manage" /></FormField>
                <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" type="number" min="0" :class="inputClass" :disabled="!can.manage" /></FormField>
                <FormField label="Danh mục liên quan" hint='Trang chi tiết có nút "Xem thêm tại …".' :error="errors.category_id">
                    <Select v-model="form.category_id" :options="categories" empty-label="— Không —" :empty-value="null" :disabled="!can.manage" />
                </FormField>
                <div v-if="can.manage" class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                    <button v-if="lookbook" type="button" :class="dangerButton" @click="destroy">Xoá</button>
                </div>
            </section>
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">Ảnh bìa</h2>
                <MediaField v-model="coverId" label="ảnh bìa" :disabled="!can.manage" />
                <p v-if="!coverId" class="text-xs text-slate-500">Không chọn: dùng ảnh đầu tiên.</p>
            </section>
        </aside>
    </form>
    <MediaPicker v-model:open="picking" title="Thêm ảnh vào lookbook" multiple @select="pick" />
</template>
