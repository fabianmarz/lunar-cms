<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Sortable from 'sortablejs';
import { Icon, Toggle } from '@lunarphp/panel';
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

// Insertion menu: `menuAt` is the index the new block will take. Triggers sit
// between blocks and after the last one — Statamic-style — all opening the
// same picker and inserting at their own position.
const menuAt = ref<number | null>(null);

const insert = (index: number, type: string): void => {
    const next = [...blocks.value];
    next.splice(index, 0, {
        id: crypto.randomUUID(),
        type,
        enabled: true,
        data: blockTypes[type].defaults(),
    });
    blocks.value = next;
    menuAt.value = null;
};

const remove = (id: string): void => {
    blocks.value = blocks.value.filter((b) => b.id !== id);
};

const meta = (type: string) => blockTypes[type] ?? { label: type, icon: 'alert', component: null };

// SortableJS direct — vuedraggable self-registers via window.Vue.component(),
// which throws against the panel's shared Vue namespace global.
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
    <div>
        <!-- Insert trigger above the first block (stays out of the Sortable list) -->
        <div v-if="blocks.length" class="relative flex justify-center mb-1">
            <button
                type="button"
                class="h-5 w-5 grid place-items-center rounded-full border border-line bg-surface text-ink-400 hover:text-ink-900 hover:border-ink-300 transition-colors"
                :aria-label="`Insert block at top`"
                @click="menuAt = menuAt === 0 ? null : 0"
            >
                <Icon name="plus" cls="sm" />
            </button>
            <div
                v-if="menuAt === 0"
                class="absolute top-6 z-20 min-w-40 rounded-md border border-line bg-surface shadow-md p-1"
            >
                <button
                    v-for="(type, key) in blockTypes"
                    :key="key"
                    type="button"
                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-ink-700 hover:bg-surface-2"
                    @click="insert(0, key)"
                >
                    <Icon :name="type.icon" cls="sm" /> {{ type.label }}
                </button>
            </div>
        </div>
        <div
            v-if="menuAt !== null"
            class="fixed inset-0 z-10"
            @click="menuAt = null"
        />

        <div ref="listEl">
            <div
                v-for="(block, i) in blocks"
                :key="block.id"
                class="relative rounded-xl border border-line bg-surface shadow-sm mb-3"
            >
                <div class="flex items-center gap-2 px-3 py-2 border-b border-line">
                    <span class="block-drag-handle cursor-grab active:cursor-grabbing text-ink-400 hover:text-ink-700">
                        <Icon name="grip" />
                    </span>
                    <Icon :name="meta(block.type).icon" />
                    <span class="text-[12.5px] font-medium text-ink-900">{{ meta(block.type).label }}</span>
                    <span v-if="!block.enabled" class="text-[10px] uppercase tracking-wide text-ink-400">
                        draft
                    </span>
                    <span class="flex-1" />
                    <Toggle :on="block.enabled" @toggle="block.enabled = !block.enabled" />
                    <button
                        type="button"
                        class="text-ink-400 hover:text-danger"
                        :aria-label="`Remove ${meta(block.type).label} block`"
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

                <!-- Insert trigger on the card's bottom edge → inserts after this block -->
                <div class="absolute -bottom-2.5 left-0 right-0 z-10 flex justify-center">
                    <button
                        type="button"
                        class="h-5 w-5 grid place-items-center rounded-full border border-line bg-surface text-ink-400 opacity-0 hover:opacity-100 hover:text-ink-900 hover:border-ink-300 focus-visible:opacity-100 transition-opacity"
                        :class="{ '!opacity-100': menuAt === i + 1 }"
                        :aria-label="`Insert block after ${meta(block.type).label}`"
                        @click="menuAt = menuAt === i + 1 ? null : i + 1"
                    >
                        <Icon name="plus" cls="sm" />
                    </button>
                    <div
                        v-if="menuAt === i + 1"
                        class="absolute top-4 z-20 min-w-40 rounded-md border border-line bg-surface shadow-md p-1"
                    >
                        <button
                            v-for="(type, key) in blockTypes"
                            :key="key"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-ink-700 hover:bg-surface-2"
                            @click="insert(i + 1, key)"
                        >
                            <Icon :name="type.icon" cls="sm" /> {{ type.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trailing add control -->
        <div class="relative">
            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-line py-2.5 text-[12.5px] text-ink-500 hover:text-ink-900 hover:border-ink-300 transition-colors"
                @click="menuAt = menuAt === blocks.length ? null : blocks.length"
            >
                <Icon name="plus" cls="sm" /> Add block
            </button>
            <div
                v-if="menuAt === blocks.length"
                class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 z-20 min-w-40 rounded-md border border-line bg-surface shadow-md p-1"
            >
                <button
                    v-for="(type, key) in blockTypes"
                    :key="key"
                    type="button"
                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-ink-700 hover:bg-surface-2"
                    @click="insert(blocks.length, key)"
                >
                    <Icon :name="type.icon" cls="sm" /> {{ type.label }}
                </button>
            </div>
        </div>
    </div>
</template>
