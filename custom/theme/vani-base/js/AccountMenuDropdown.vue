<script setup lang="ts">
import { onClickOutside, onKeyStroke, useMediaQuery, useToggle } from '@vueuse/core';
import { autoUpdate, flip, offset, shift, useFloating } from '@floating-ui/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ label: string; linksHtml: string }>();

const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLElement | null>(null);
const floating = ref<HTMLElement | null>(null);
const [isOpen, toggle] = useToggle(false);
const isDesktop = useMediaQuery('(min-width: 48rem)');
const { floatingStyles } = useFloating(trigger, floating, {
    placement: 'bottom-start',
    middleware: [offset(8), flip(), shift({ padding: 8 })],
    whileElementsMounted: autoUpdate,
});
const dropdownStyles = computed(() => (isOpen.value && !isDesktop.value ? floatingStyles.value : undefined));

onClickOutside(root, () => toggle(false));
onKeyStroke('Escape', () => {
    if (isOpen.value) {
        toggle(false);
        trigger.value?.focus();
    }
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            ref="trigger"
            type="button"
            class="mb-2 w-full rounded border border-slate-200 px-3 py-2 text-left text-sm font-medium md:hidden"
            aria-controls="vani-account-menu-dropdown"
            :aria-expanded="isOpen"
            @click="toggle()"
        >
            {{ label }}
        </button>
        <div
            v-if="isOpen || isDesktop"
            id="vani-account-menu-dropdown"
            ref="floating"
            :style="dropdownStyles"
            :class="[
                isOpen ? 'block' : 'hidden',
                'absolute z-50 max-h-[70vh] min-w-56 overflow-y-auto rounded border border-slate-200 bg-white p-3 shadow-lg md:static md:block md:max-h-none md:min-w-0 md:overflow-visible md:rounded-none md:border-0 md:bg-transparent md:p-0 md:shadow-none',
            ]"
        >
            <div v-html="props.linksHtml"></div>
        </div>
    </div>
</template>
