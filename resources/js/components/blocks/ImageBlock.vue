<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@lunarphp/panel';
import type { LanguageOption, Translations } from '../../translations';
import TranslatedField from '../TranslatedField.vue';

type ImageData = { media_id?: number | null; alt?: Translations };

const props = defineProps<{
    data: ImageData;
    media: Record<number, { id: number; url: string; original_url?: string; name: string }>;
    mediaUploadUrl?: string;
    languages: LanguageOption[];
}>();

const emit = defineEmits<{ 'update:data': [value: ImageData] }>();

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const uploadError = ref('');

const current = computed(() => (props.data.media_id ? props.media[props.data.media_id] : null));

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

const setAlt = (alt: Translations): void => {
    emit('update:data', { ...props.data, alt });
};
</script>

<template>
    <div class="space-y-2">
        <div v-if="current" class="flex items-center gap-3">
            <img :src="current.url" :alt="Object.values(data.alt ?? {})[0] ?? ''" class="h-16 w-16 rounded-md object-cover border border-line" />
            <span class="text-[12px] text-ink-500 truncate">{{ current.name }}</span>
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

        <TranslatedField :model-value="data.alt" :languages="languages" placeholder="Alt text" @update:model-value="setAlt" />
    </div>
</template>
