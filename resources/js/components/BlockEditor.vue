<script setup lang="ts">
import { computed } from 'vue';
import draggable from 'vuedraggable';
import { Button, Icon, Toggle } from '@lunarphp/panel';
import { blockTypes } from '../blocks';

interface Block {
    id: string;
    type: string;
    enabled: boolean;
    data: Record<string, unknown>;
}

const props = defineProps<{
    modelValue: Block[];
    media: Record<number, { id: number; url: string; name: string }>;
    mediaUploadUrl?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: Block[]] }>();

const blocks = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const add = (type: string): void => {
    blocks.value = [
        ...blocks.value,
        { id: crypto.randomUUID(), type, enabled: true, data: blockTypes[type].defaults() },
    ];
};

const remove = (id: string): void => {
    blocks.value = blocks.value.filter((b) => b.id !== id);
};

const meta = (type: string) => blockTypes[type] ?? { label: type, icon: 'alert', component: null };
</script>

<template>
    <div class="space-y-2">
        <draggable
            v-model="blocks"
            item-key="id"
            handle=".block-drag-handle"
            :animation="150"
            ghost-class="opacity-40"
        >
            <template #item="{ element: block }">
                <div class="rounded-md border border-line bg-surface">
                    <div class="flex items-center gap-2 px-3 py-2 border-b border-line">
                        <span class="block-drag-handle cursor-grab text-ink-400 hover:text-ink-700">
                            <Icon name="grip" />
                        </span>
                        <Icon :name="meta(block.type).icon" />
                        <span class="text-[13px] font-medium text-ink-900">{{ meta(block.type).label }}</span>
                        <span v-if="!block.enabled" class="text-[10px] uppercase tracking-wide text-ink-400">
                            draft
                        </span>
                        <span class="flex-1" />
                        <Toggle :on="block.enabled" @toggle="block.enabled = !block.enabled" />
                        <button
                            type="button"
                            class="text-ink-400 hover:text-danger"
                            aria-label="Remove block"
                            @click="remove(block.id)"
                        >
                            <Icon name="trash" />
                        </button>
                    </div>
                    <div class="p-3">
                        <component
                            :is="meta(block.type).component"
                            v-if="meta(block.type).component"
                            v-model:data="block.data"
                            :media="media"
                            :media-upload-url="mediaUploadUrl"
                        />
                        <p v-else class="text-[12px] text-ink-500">Unsupported block type: {{ block.type }}</p>
                    </div>
                </div>
            </template>
        </draggable>

        <div class="flex items-center gap-2 pt-1">
            <span class="text-[12px] text-ink-500">Add block:</span>
            <Button
                v-for="(type, key) in blockTypes"
                :key="key"
                :icon="type.icon"
                @click="add(key)"
            >
                {{ type.label }}
            </Button>
        </div>
    </div>
</template>
