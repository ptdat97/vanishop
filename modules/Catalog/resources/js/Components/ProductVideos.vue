<script setup lang="ts">
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, secondaryButton } from '@admin/styles';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { VideoRow } from '../types';

const props = defineProps<{
    productUrl: string;
    videos: VideoRow[];
    drivers: Array<{ code: string; label: string; hint: string }>;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const driver = ref(props.drivers[0]?.code ?? '');
const url = ref('');
const title = ref('');
const hint = computed(() => props.drivers.find((item) => item.code === driver.value)?.hint ?? '');

function add(): void {
    if (driver.value && url.value.trim()) {
        router.post(
            `${props.productUrl}/videos`,
            { driver: driver.value, url: url.value, title: title.value || null },
            { preserveScroll: true, onSuccess: () => ((url.value = ''), (title.value = '')) },
        );
    }
}

function move(index: number, direction: -1 | 1): void {
    const order = props.videos.map((video) => video.id);
    const target = index + direction;
    if (target < 0 || target >= order.length) {
        return;
    }
    [order[index], order[target]] = [order[target], order[index]];
    router.put(`${props.productUrl}/videos/order`, { order }, { preserveScroll: true });
}

async function remove(video: VideoRow): Promise<void> {
    if ((await confirmAction({ title: 'Gỡ video này khỏi sản phẩm?', confirmLabel: 'Gỡ', tone: 'danger' })) !== false) {
        router.delete(`${props.productUrl}/videos/${video.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-medium">Video</h2>

        <p v-if="!drivers.length" class="text-sm text-slate-500">Chưa bật nền tảng video nào. Bật plugin (vd. "Video YouTube") trong Admin → Plugin.</p>
        <div v-else class="space-y-2 rounded-md bg-slate-50 p-3">
            <div class="flex flex-wrap gap-2">
                <div v-if="drivers.length > 1" class="w-40">
                    <Select v-model="driver" :options="drivers.map((item) => ({ value: item.code, label: item.label }))" aria-label="Nền tảng video" />
                </div>
                <input v-model="url" :class="inputClass" class="min-w-48 flex-1" :placeholder="hint" @keydown.enter.prevent="add" />
                <input v-model="title" :class="inputClass" class="max-w-56" placeholder="Tiêu đề (không bắt buộc)" />
                <button type="button" :class="secondaryButton" :disabled="!url.trim()" @click="add">Thêm video</button>
            </div>
            <p v-if="errors.url || errors.driver" class="text-sm text-red-600">{{ errors.url ?? errors.driver }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <div v-for="(video, index) in videos" :key="video.id" class="w-40 text-xs">
                <a v-if="video.watch_url" :href="video.watch_url" target="_blank" rel="noopener" class="block">
                    <img v-if="video.thumbnail_url" :src="video.thumbnail_url" alt="" class="aspect-video w-40 rounded object-cover" />
                    <span v-else class="flex aspect-video w-40 items-center justify-center rounded bg-slate-100 text-slate-500">{{ video.driver }}</span>
                </a>
                <span v-else class="flex aspect-video w-40 items-center justify-center rounded bg-amber-50 px-2 text-center text-amber-700">Nền tảng "{{ video.driver }}" đang tắt</span>
                <p class="mt-1 truncate" :title="video.title ?? video.reference">{{ video.title ?? video.reference }}</p>
                <div class="mt-1 flex justify-between">
                    <button type="button" :disabled="index === 0" @click="move(index, -1)">←</button>
                    <button type="button" class="text-red-600" @click="remove(video)">Gỡ</button>
                    <button type="button" :disabled="index === videos.length - 1" @click="move(index, 1)">→</button>
                </div>
            </div>
        </div>
    </div>
</template>
