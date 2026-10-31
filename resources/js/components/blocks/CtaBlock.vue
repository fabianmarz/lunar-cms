<script setup lang="ts">
import { TextInput } from '@lunarphp/panel';

type CtaData = { label?: string; url?: string };

const props = defineProps<{ data: CtaData }>();
const emit = defineEmits<{ 'update:data': [value: CtaData] }>();

const set = (key: keyof CtaData, value: string): void => {
    emit('update:data', { ...props.data, [key]: value });
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <TextInput :model-value="data.label ?? ''" placeholder="Button label" @update:model-value="(v) => set('label', v)" />
        <TextInput :model-value="data.url ?? ''" placeholder="URL (e.g. /contact)" @update:model-value="(v) => set('url', v)" />
    </div>
</template>
