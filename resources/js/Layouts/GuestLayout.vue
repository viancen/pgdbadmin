<script setup>
defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
    <main>
        <slot />
    </main>
</template>
