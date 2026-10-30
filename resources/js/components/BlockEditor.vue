<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Sortable from 'sortablejs';
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

// vuedraggable unusable here: its dists and even its ESM source self-register
// via window.Vue.component(), which throws against the panel's shared Vue
// namespace global. SortableJS direct — onEnd syncs the order back to the model.
const listEl = ref<HTMLElement>();

onMounted(() => {
    new Sortable(listEl.value!, {
        handle: '.block-drag-handle',
        animation: 150,
        ghostClass: 'opacity-40',
        onEnd: ({ oldIndex, newIndex }) => {
            if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) {
                return;
            }

            const next = [...blocks.value];
            const [moved] = next.splice(oldIndex, 1);
            next.splice(newIndex, 0, moved);
            blocks.value = next;
        },
    });
});
</script>

<template>
    <div class="space-y-2">
        <div ref="listEl">
            <template v-for="block in blocks" :key="block.id">
                <div class="rounded-md border border-line bg-surface mb-2">
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
        </div>

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
