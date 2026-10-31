<script setup lang="ts">
import type { LanguageOption } from '../translations';

defineProps<{ languages: LanguageOption[] }>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <select
        v-model="model"
        class="h-[22px] px-1 border border-line-strong rounded-md bg-surface-2 text-[10.5px] font-mono uppercase tracking-wide text-ink-700 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-sage/35"
        aria-label="Language"
    >
        <option v-for="language in languages" :key="language.code" :value="language.code" :title="language.name ?? language.code">
            {{ language.code.toUpperCase() }}
        </option>
    </select>
</template>
