<script setup>
import Sidebar from '@/Components/Sidebar.vue';

defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar />
        <main class="app-main min-h-screen min-w-0 flex-1 overflow-auto">
            <slot />
        </main>
    </div>
</template>
