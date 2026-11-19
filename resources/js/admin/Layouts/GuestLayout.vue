<script setup lang="ts">
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
</script>

<template>
    <main class="relative min-h-screen">
        <div class="absolute right-4 top-4">
            <LocaleSwitcher />
        </div>
        <slot />
    </main>
</template>
