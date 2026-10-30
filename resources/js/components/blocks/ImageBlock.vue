<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button, TextInput } from '@lunarphp/panel';

const props = defineProps<{
    data: { media_id?: number | null; alt?: string };
    media: Record<number, { id: number; url: string; name: string }>;
    mediaUploadUrl?: string;
}>();

const emit = defineEmits<{ 'update:data': [value: { media_id: number | null; alt: string }] }>();

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
        emit('update:data', { media_id: media.id, alt: props.data.alt ?? '' });
    } catch (e) {
        uploadError.value = e instanceof Error ? e.message : 'Upload failed';
    } finally {
        uploading.value = false;
        input.value = '';
    }
};

const setAlt = (alt: string): void => {
    emit('update:data', { media_id: props.data.media_id ?? null, alt });
};
</script>

<template>
    <div class="space-y-2">
        <div v-if="current" class="flex items-center gap-3">
            <img :src="current.url" :alt="data.alt" class="h-16 w-16 rounded-md object-cover border border-line" />
            <span class="text-[12px] text-ink-500 truncate">{{ current.name }}</span>
        </div>

        <div v-if="mediaUploadUrl" class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2 text-[13px] text-ink-700 cursor-pointer">
                <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="upload" />
                <Button as="span" :icon="uploading ? 'refresh' : 'upload'" :disabled="uploading">
                    {{ uploading ? 'Uploading…' : current ? 'Replace image' : 'Upload image' }}
                </Button>
            </label>
            <p v-if="uploadError" class="text-[11px] text-danger">{{ uploadError }}</p>
        </div>
        <p v-else class="text-[11px] text-ink-400">Save the page first — uploads need a page to attach to.</p>

        <TextInput
            :model-value="data.alt ?? ''"
            placeholder="Alt text"
            @update:model-value="setAlt"
        />
    </div>
</template>
