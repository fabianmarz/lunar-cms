<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button, TextInput } from '@lunarphp/panel';

type ImageData = { media_id?: number | null; alt?: string };

const props = defineProps<{
    data: ImageData;
    errors?: Record<string, string>;
    media: Record<number, { id: number; url: string; original_url?: string; name: string }>;
    mediaUploadUrl?: string;
    mediaPickerUrl?: string | null;
    mediaLibraryUploadUrl?: string | null;
}>();

const emit = defineEmits<{ 'update:data': [value: ImageData] }>();

// The media add-on is optional: its picker is looked up by name at runtime, and
// the button only shows when both the component and its URLs are available.
const MediaPicker = window.LunarPanel.resolveExtensionComponent?.('media::MediaPicker');
const canPick = computed(() => !!MediaPicker && !!props.mediaPickerUrl && !!props.mediaLibraryUploadUrl);
const pickerOpen = ref(false);

const onPick = (items: { id: number; url: string | null; original_url: string; name: string }[]): void => {
    const item = items[0];

    if (!item) {
        return;
    }

    props.media[item.id] = { id: item.id, url: item.url ?? item.original_url, original_url: item.original_url, name: item.name };
    emit('update:data', { ...props.data, media_id: item.id });
};

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const uploadError = ref('');

const current = computed(() => (props.data.media_id ? props.media[props.data.media_id] : null));

// A referenced file that is no longer in the media map was deleted (for
// example together with the entity that owned it).
const missing = computed(() => !!props.data.media_id && !current.value);

// Uploads bypass the Inertia router — the media endpoint returns plain JSON,
// which the Inertia client would reject. Same XSRF pattern as the panel's http lib.
const xsrfToken = (): string => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

const upload = async (event: Event): Promise<void> => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file || !props.mediaUploadUrl) {
        return;
    }

    uploading.value = true;
    uploadError.value = '';

    const body = new FormData();
    body.append('file', file);

    try {
        const response = await fetch(props.mediaUploadUrl, {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': xsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body,
        });

        if (!response.ok) {
            throw new Error(`Upload failed (${response.status})`);
        }

        const media = await response.json();
        // Register in the shared media map so the preview resolves immediately.
        props.media[media.id] = media;
        emit('update:data', { ...props.data, media_id: media.id });
    } catch (e) {
        uploadError.value = e instanceof Error ? e.message : 'Upload failed';
    } finally {
        uploading.value = false;
        input.value = '';
    }
};

const setAlt = (alt: string): void => {
    emit('update:data', { ...props.data, alt });
};
</script>

<template>
    <div class="space-y-2">
        <div v-if="current" class="flex items-center gap-3">
            <img :src="current.url" :alt="data.alt ?? ''" class="h-16 w-16 rounded-md object-cover border border-line" />
            <span class="text-[12px] text-ink-500 truncate">{{ current.name }}</span>
        </div>

        <p v-if="missing" class="rounded-md border border-danger px-3 py-2 text-[12px] text-danger">
            File missing: it was deleted. Choose or upload a replacement.
        </p>

        <div v-if="canPick" class="flex items-center gap-2">
            <Button icon="image" @click="pickerOpen = true">Choose from library</Button>
            <component
                :is="MediaPicker"
                v-model:open="pickerOpen"
                :picker-url="mediaPickerUrl"
                :upload-url="mediaLibraryUploadUrl"
                @select="onPick"
            />
        </div>

        <div v-if="mediaUploadUrl" class="flex items-center gap-2">
            <!-- A real button opens the picker: the panel's Button renders a
                 <button>, which a wrapping <label> never forwards clicks from. -->
            <input ref="fileInput" type="file" accept="image/*" class="hidden" :disabled="uploading" @change="upload" />
            <Button :icon="uploading ? 'refresh' : 'upload'" :disabled="uploading" @click="fileInput?.click()">
                {{ uploading ? 'Uploading…' : current ? 'Replace image' : 'Upload image' }}
            </Button>
            <p v-if="uploadError" class="text-[11px] text-danger">{{ uploadError }}</p>
        </div>
        <p v-else class="text-[11px] text-ink-400">Save the page first — uploads need a page to attach to.</p>

        <p v-if="errors?.media_id" class="text-[11px] text-danger">{{ errors.media_id }}</p>

        <div>
            <TextInput :model-value="data.alt ?? ''" placeholder="Alt text" :invalid="!!errors?.alt" @update:model-value="setAlt" />
            <p v-if="errors?.alt" class="mt-1 text-[11px] text-danger">{{ errors.alt }}</p>
        </div>
    </div>
</template>
