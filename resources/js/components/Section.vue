<script setup lang="ts">
// Same markup as the panel's Section (not exported to add-ons); the
// first/last-child rules live in addon.ts because the panel's stylesheet
// doesn't emit those variants.
defineProps<{ title?: string }>();
</script>

<template>
    <section class="cms-section py-6 border-b border-line">
        <div class="mb-4">
            <h2 class="m-0 mb-1 text-sm font-semibold tracking-[-0.01em] text-ink-900">{{ title }}</h2>
            <div v-if="$slots.desc" class="text-xs text-ink-500 leading-normal max-w-[640px]"><slot name="desc" /></div>
        </div>
        <div class="min-w-0">
            <slot />
        </div>
    </section>
</template>
