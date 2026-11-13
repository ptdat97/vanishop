<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
const form = useForm({
    desktop_media_id: props.banner?.desktop?.id ?? (null as number | null),
    mobile_media_id: props.banner?.mobile?.id ?? (null as number | null),
    category_id: props.banner?.category_id ?? (null as number | null),
    content: Object.fromEntries(locales.map((locale) => [locale.code, { title: '', markdown: '', button_text: '', ...(props.banner?.content[locale.code] ?? {}) }])) as Record<string, Texts>,
    button_url: props.banner?.button_url ?? '',
    active: props.banner?.active ?? true,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function save(): void {
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
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">Ảnh desktop</h2>
                <p v-if="errors.desktop_media_id" class="text-sm text-red-600">{{ errors.desktop_media_id }}</p>
                <MediaField v-model="form.desktop_media_id" label="ảnh desktop" :disabled="!can.manage" />
                <p class="text-xs text-slate-500">Gợi ý 1600×480.</p>
            </section>
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-medium">Ảnh mobile (tuỳ chọn)</h2>
                <MediaField v-model="form.mobile_media_id" label="ảnh mobile" :disabled="!can.manage" />
                <p class="text-xs text-slate-500">Không chọn: dùng ảnh desktop.</p>
            </section>
        </aside>
    </form>
</template>
