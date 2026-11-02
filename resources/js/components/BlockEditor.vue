<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Sortable from 'sortablejs';
import { Icon, Toggle } from '@lunarphp/panel';
import { customEditors } from '../blocks';
import BlockFields, { type BlockField } from './BlockFields.vue';

interface Block {
    id: string;
    type: string;
    enabled: boolean;
    data: Record<string, unknown>;
}

interface BlockType {
    label: string;
    icon: string;
    editor?: string | null;
    fields: BlockField[];
    defaults: Record<string, unknown>;
}

const props = defineProps<{
    modelValue: Block[];
    blockTypes: Record<string, BlockType>;
    locale: string;
    errors?: Record<string, string>;
    media: Record<number, { id: number; url: string; original_url?: string; name: string }>;
    mediaUploadUrl?: string;
    mediaPickerUrl?: string | null;
    mediaLibraryUploadUrl?: string | null;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: Block[]] }>();

const blocks = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Insertion menu teleported to <body>: menus rendered inside the page hit
// stacking problems with the panel's sticky chrome (z-30 bars) — clicks land
// on whatever covers them. A single menu, positioned at the clicked trigger.
const menu = ref<{ index: number; top: number; left: number } | null>(null);

const openMenu = (event: MouseEvent, index: number): void => {
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    menu.value = { index, top: rect.bottom + 4, left: rect.left + rect.width / 2 };
};

const insert = (index: number, type: string): void => {
    const next = [...blocks.value];
    next.splice(index, 0, {
        id: crypto.randomUUID(),
        type,
        enabled: true,
        data: { ...props.blockTypes[type].defaults },
    });
    blocks.value = next;
    menu.value = null;
};

const remove = (id: string): void => {
    blocks.value = blocks.value.filter((b) => b.id !== id);
};

const meta = (type: string): BlockType =>
    props.blockTypes[type] ?? { label: type, icon: 'alert', editor: null, fields: [], defaults: {} };

const customEditor = (type: string) => {
    const name = meta(type).editor;

    return name ? (customEditors as Record<string, unknown>)[name] : null;
};

// Validation errors arrive keyed `blocks.{locale}.{i}.data.{field}` — slice
// them down to the field map a single block's renderer expects.
const fieldErrors = (index: number): Record<string, string> => {
    const prefix = `blocks.${props.locale}.${index}.data.`;

    return Object.fromEntries(
        Object.entries(props.errors ?? {})
            .filter(([key]) => key.startsWith(prefix))
            .map(([key, value]) => [key.slice(prefix.length), value]),
    );
};

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
        <div v-if="blocks.length" class="flex justify-center mb-1">
            <button
                type="button"
                class="h-5 w-5 grid place-items-center rounded-full border border-line bg-surface text-ink-400 hover:text-ink-900 hover:border-ink-300 transition-colors"
                aria-label="Insert block at top"
                @click="openMenu($event, 0)"
            >
                <Icon name="plus" cls="sm" />
            </button>
        </div>

        <div ref="listEl">
            <div
                v-for="(block, i) in blocks"
                :key="block.id"
                class="cms-block relative rounded-xl border border-line bg-surface shadow-sm mb-3"
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
                        :is="customEditor(block.type)"
                        v-if="customEditor(block.type)"
                        v-model:data="block.data"
                        :errors="fieldErrors(i)"
                        :media="media"
                        :media-upload-url="mediaUploadUrl"
                        :media-picker-url="mediaPickerUrl"
                        :media-library-upload-url="mediaLibraryUploadUrl"
                    />
                    <BlockFields
                        v-else-if="meta(block.type).fields.length"
                        :fields="meta(block.type).fields"
                        :data="block.data"
                        :errors="fieldErrors(i)"
                    />
                    <p v-else class="text-[12px] text-ink-500">Unsupported block type: {{ block.type }}</p>
                </div>

                <!-- Insert trigger on the card's bottom edge → inserts after this block -->
                <button
                    type="button"
                    class="cms-block-add h-5 w-5 grid place-items-center rounded-full border border-line bg-surface text-ink-400 hover:text-ink-900 hover:border-ink-300"
                    :aria-label="`Insert block after ${meta(block.type).label}`"
                    @click="openMenu($event, i + 1)"
                >
                    <Icon name="plus" cls="sm" />
                </button>
            </div>
        </div>

        <!-- Trailing add control -->
        <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-line py-2.5 text-[12.5px] text-ink-500 hover:text-ink-900 hover:border-ink-300 transition-colors"
            @click="openMenu($event, blocks.length)"
        >
            <Icon name="plus" cls="sm" /> Add block
        </button>

        <Teleport to="body">
            <div v-if="menu" :style="{ position: 'fixed', inset: '0', zIndex: 70 }" @click="menu = null" />
            <div
                v-if="menu"
                class="cms-menu rounded-md border border-line bg-surface shadow-md p-1"
                :style="{ position: 'fixed', top: menu.top + 'px', left: menu.left + 'px', transform: 'translateX(-50%)', zIndex: 80 }"
            >
                <button
                    v-for="(type, key) in blockTypes"
                    :key="key"
                    type="button"
                    class="cms-menu-item flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[12.5px] text-ink-700"
                    @click="insert(menu.index, key)"
                >
                    <Icon :name="type.icon" cls="sm" /> {{ type.label }}
                </button>
            </div>
        </Teleport>
    </div>
</template>
