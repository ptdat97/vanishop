<script setup lang="ts">
import FlashMessage from '@admin/Components/FlashMessage.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Texts = { title: string; markdown: string; button_text: string };
type Image = { id: number; url: string | null };

/** Banner đầu trang danh mục (store-content §3.3): nội dung theo ngôn ngữ, ảnh desktop + mobile (tuỳ chọn), nút. */
const props = defineProps<{
    banner: null | {
        id: number;
        category_id: number;
        content: Record<string, Partial<Texts>>;
        button_url: string | null;
        active: boolean;
        desktop: Image | null;
        mobile: Image | null;
    };
    categories: Array<{ value: number; label: string }>;
    urls: { index: string; save: string };
    can: { manage: boolean };
}>();

const { locales, defaultLocale } = useStoreLocales();
const images = ref<{ desktop: Image | null; mobile: Image | null }>({ desktop: props.banner?.desktop ?? null, mobile: props.banner?.mobile ?? null });
const picking = ref<'desktop' | 'mobile' | null>(null);
const form = useForm({
    category_id: props.banner?.category_id ?? (null as number | null),
    content: Object.fromEntries(locales.map((locale) => [locale.code, { title: '', markdown: '', button_text: '', ...(props.banner?.content[locale.code] ?? {}) }])) as Record<string, Texts>,
    button_url: props.banner?.button_url ?? '',
    active: props.banner?.active ?? true,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function pick(items: MediaItem[]): void {
    if (picking.value && items[0]) images.value[picking.value] = { id: items[0].id, url: items[0].thumb_url };
    picking.value = null;
}

function save(): void {
    form.transform((data) => ({ ...data, desktop_media_id: images.value.desktop?.id ?? null, mobile_media_id: images.value.mobile?.id ?? null }));
    if (props.banner) {
        form.put(props.urls.save, { preserveScroll: true });
    } else {
        form.post(props.urls.save);
    }
}

async function destroy(): Promise<void> {
    if (props.banner && (await confirmAction({ title: 'Xoá banner này?', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.urls.index}/${props.banner.id}`);
    }
}
</script>

<template>
    <Head :title="banner ? 'Sửa banner danh mục' : 'Thêm banner danh mục'" />
    <PageHeader :title="banner ? (banner.content[defaultLocale]?.title || 'Sửa banner danh mục') : 'Thêm banner danh mục'">
        <Link :href="urls.index" :class="secondaryButton">Danh sách</Link>
    </PageHeader>
    <FlashMessage />

    <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="save">
        <div class="space-y-4">
            <section v-for="locale in locales" :key="locale.code" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-medium">{{ locale.label }}</h2>
                <FormField label="Tiêu đề" :error="errors[`content.${locale.code}.title`]">
                    <input v-model="form.content[locale.code].title" :class="inputClass" maxlength="160" :disabled="!can.manage" />
                </FormField>
                <FormField label="Nội dung ngắn (Markdown)" :error="errors[`content.${locale.code}.markdown`]">
                    <textarea v-model="form.content[locale.code].markdown" :class="inputClass" rows="3" maxlength="1000" :disabled="!can.manage" />
                </FormField>
                <FormField label="Chữ trên nút" hint="Trống = không có nút." :error="errors[`content.${locale.code}.button_text`]">
                    <input v-model="form.content[locale.code].button_text" :class="inputClass" maxlength="60" :disabled="!can.manage" />
                </FormField>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <FormField label="Danh mục *" :error="errors.category_id">
                    <Select v-model="form.category_id" :options="categories" empty-label="— Chọn danh mục —" :empty-value="null" :disabled="!can.manage" />
                </FormField>
                <FormField label="Link nút" hint="Đường dẫn trong cửa hàng (/…) hoặc https://…" :error="errors.button_url">
                    <input v-model="form.button_url" :class="inputClass" maxlength="500" placeholder="/danh-muc/…" :disabled="!can.manage" />
                </FormField>
                <label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" :disabled="!can.manage" /> Đang bật</label>
                <div v-if="can.manage" class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                    <button v-if="banner" type="button" :class="dangerButton" @click="destroy">Xoá</button>
                </div>
            </section>
            <section v-for="kind in (['desktop', 'mobile'] as const)" :key="kind" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">{{ kind === 'desktop' ? 'Ảnh desktop' : 'Ảnh mobile (tuỳ chọn)' }}</h2>
                <p v-if="kind === 'desktop' && errors.desktop_media_id" class="text-sm text-red-600">{{ errors.desktop_media_id }}</p>
                <img v-if="images[kind]?.url" :src="images[kind]?.url ?? ''" alt="" class="w-full rounded" />
                <p v-else class="text-xs text-slate-500">{{ kind === 'desktop' ? 'Gợi ý 1600×480.' : 'Không chọn: dùng ảnh desktop.' }}</p>
                <div v-if="can.manage" class="flex gap-2">
                    <button type="button" :class="secondaryButton" @click="picking = kind">{{ images[kind] ? 'Đổi ảnh' : 'Chọn ảnh' }}</button>
                    <button v-if="images[kind]" type="button" :class="secondaryButton" @click="images[kind] = null">Bỏ</button>
                </div>
            </section>
        </aside>
    </form>
    <MediaPicker :open="picking !== null" :title="picking === 'mobile' ? 'Chọn ảnh mobile' : 'Chọn ảnh desktop'" @update:open="(value) => !value && (picking = null)" @select="pick" />
</template>
