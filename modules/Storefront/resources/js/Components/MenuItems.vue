<script setup lang="ts">
import DateInput from '@admin/Components/DateInput.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { inputClass, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { inject, ref } from 'vue';
import { type MenuItem, menuEditorKey, newMenuItem } from '../menu';
import LinkPicker from './LinkPicker.vue';

/**
 * Cây menu của trình sửa. Sắp xếp bằng kéo-thả dùng chung (`Sortable`, dạng cây): kéo tay nắm để đổi chỗ, kéo lệch phải
 * để thành mục con, lệch trái để ra cấp ngoài; bàn phím trên tay nắm: ↑ ↓ đổi chỗ, → thụt vào mục trên, ← thụt ra.
 */
const props = defineProps<{
    maxDepth: number;
    errors: Record<string, string | undefined>;
    /** Tiền tố khoá lỗi validate của cây, vd. `items` → `items.0.children.1.link`. */
    path: string;
}>();
const items = defineModel<MenuItem[]>('items', { required: true });

const context = inject(menuEditorKey)!;
const { locales } = useStoreLocales();
const opened = ref<string | null>(null);
const picking = ref<MenuItem | null>(null);
const badgeOptions = [
    { value: 'new' as const, label: 'Mới' },
    { value: 'sale' as const, label: 'Sale' },
];

function title(item: MenuItem): string {
    const override = Object.values(item.label).find((text) => text);
    return override || context.linkLabels[item.id]?.label || (item.link ? `${item.link.type}: ${item.link.value || '…'}` : '(chưa đặt tên)');
}

async function remove(siblings: MenuItem[], index: number): Promise<void> {
    const item = siblings[index];
    const message = item.children.length ? `Mục con (${item.children.length}) cũng bị xoá.` : undefined;
    if ((await confirmAction({ title: `Xoá mục "${title(item)}"?`, message, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        // Cấp gốc là mảng của v-model: phát mảng mới; cấp con sửa tại chỗ.
        if (siblings === items.value) items.value = items.value.filter((candidate) => candidate !== item);
        else siblings.splice(index, 1);
    }
}

function addChild(item: MenuItem): void {
    const child = newMenuItem();
    item.children.push(child);
    opened.value = child.id;
}

function pickImage(items: MediaItem[]): void {
    if (picking.value && items[0]) {
        picking.value.image_media_id = items[0].id;
        context.images[items[0].id] = items[0].thumb_url ?? items[0].url;
    }
    picking.value = null;
}

const fieldError = (path: number[], field: string): string | undefined => props.errors[`${props.path}.${path.join('.children.')}.${field}`];
</script>

<template>
    <div>
    <Sortable
        v-model="items"
        v-slot="{ item, index, depth: level, path: itemPath, siblings, handle }"
        children-key="children"
        :max-depth="maxDepth"
        tag="ol"
        item-tag="li"
        :item-key="(item) => item.id"
        :item-label="title"
    >
        <div class="rounded-md border border-slate-200 bg-white" :data-menu-item="item.id">
            <div class="flex flex-wrap items-center gap-2 px-3 py-2 text-sm">
                <SortHandle v-bind="handle" />
                <button type="button" class="flex-1 text-left font-medium" :aria-expanded="opened === item.id" @click="opened = opened === item.id ? null : item.id">
                    {{ title(item) }}
                    <span v-if="item.children.length" class="ml-1 text-xs text-slate-400">({{ item.children.length }} mục con)</span>
                </button>
                <StatusBadge v-if="!item.enabled" label="Tắt" />
                <StatusBadge v-if="item.badge" :label="item.badge === 'new' ? 'Mới' : 'Sale'" :tone="item.badge === 'new' ? 'success' : 'danger'" />
                <StatusBadge v-if="context.linkLabels[item.id]?.exists === false" label="Link hỏng" tone="danger" />
                <button type="button" class="px-2 text-red-600 hover:underline" @click="remove(siblings, index)">Xoá</button>
            </div>

            <div v-if="opened === item.id" class="grid gap-3 border-t border-slate-100 p-3 text-sm md:grid-cols-2">
                <div class="space-y-2">
                    <span class="block font-medium text-slate-700">Link</span>
                    <LinkPicker v-model="item.link" :item-id="item.id" />
                    <p v-if="fieldError(itemPath, 'link')" class="text-xs text-red-600">{{ fieldError(itemPath, 'link') }}</p>
                </div>
                <div class="space-y-2">
                    <label v-for="option in locales" :key="option.code" class="block">
                        <span class="mb-1 block text-slate-700">Nhãn ({{ option.label }})</span>
                        <input v-model="item.label[option.code]" :class="inputClass" maxlength="80" :placeholder="context.linkLabels[item.id]?.label ?? 'Để trống = tên của đối tượng'" />
                    </label>
                    <p v-if="fieldError(itemPath, 'label')" class="text-xs text-red-600">{{ fieldError(itemPath, 'label') }}</p>
                </div>
                <label class="block">
                    <span class="mb-1 block text-slate-700">Huy hiệu</span>
                    <Select v-model="item.badge" :options="badgeOptions" empty-label="Không" :empty-value="null" />
                </label>
                <label class="flex items-center gap-2 self-end"><input v-model="item.enabled" type="checkbox" /> Hiển thị</label>
                <label class="block">
                    <span class="mb-1 block text-slate-700">Hiện từ (giờ cửa hàng)</span>
                    <DateInput v-model="item.visible_from" with-time />
                </label>
                <label class="block">
                    <span class="mb-1 block text-slate-700">Ẩn từ</span>
                    <DateInput v-model="item.visible_to" with-time />
                    <span v-if="fieldError(itemPath, 'visible_to')" class="text-xs text-red-600">{{ fieldError(itemPath, 'visible_to') }}</span>
                </label>
                <div v-if="level === 1" class="flex items-center gap-3 md:col-span-2">
                    <img v-if="item.image_media_id && context.images[item.image_media_id]" :src="context.images[item.image_media_id]" alt="" class="h-12 w-12 rounded object-cover" />
                    <button type="button" :class="secondaryButton" @click="picking = item">{{ item.image_media_id ? 'Đổi ảnh' : 'Ảnh (mega menu)' }}</button>
                    <button v-if="item.image_media_id" type="button" class="text-red-600 hover:underline" @click="item.image_media_id = null">Gỡ ảnh</button>
                </div>
                <div v-if="level < maxDepth" class="md:col-span-2">
                    <button type="button" :class="secondaryButton" @click="addChild(item)">+ Mục con</button>
                </div>
            </div>
        </div>
    </Sortable>
    <MediaPicker :open="picking !== null" title="Ảnh cho mục menu" :multiple="false" @update:open="(value) => !value && (picking = null)" @select="pickImage" />
    </div>
</template>
