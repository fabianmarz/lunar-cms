<script setup lang="ts">
import { TextInput } from '@lunarphp/panel';

const props = defineProps<{ data: { label?: string; url?: string } }>();
const emit = defineEmits<{ 'update:data': [value: { label: string; url: string }] }>();

const set = (key: 'label' | 'url', value: string): void => {
    emit('update:data', { label: props.data.label ?? '', url: props.data.url ?? '', [key]: value });
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <TextInput :model-value="data.label ?? ''" placeholder="Button label" @update:model-value="(v) => set('label', v)" />
        <TextInput :model-value="data.url ?? ''" placeholder="URL (e.g. /contact)" @update:model-value="(v) => set('url', v)" />
    </div>
</template>
