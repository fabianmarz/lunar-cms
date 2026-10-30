<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { PageHeader, PageZone, Button, TextInput, Select, DatePicker, FieldLabel } from '@lunarphp/panel';
import BlockEditor from '../components/BlockEditor.vue';

interface Block {
    id: string;
    type: string;
    enabled: boolean;
    data: Record<string, unknown>;
}

interface PageForm {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    published_at: string | null;
    blocks: Block[];
}

const props = defineProps<{
    page: PageForm | null;
    media: Record<number, { id: number; url: string; name: string }>;
    urls: { store?: string; update?: string; media?: string; index: string };
}>();

const isEdit = computed(() => props.page !== null);

const form = useForm({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ?? '',
    blocks: props.page?.blocks ?? [],
});

const submit = (): void => {
    if (isEdit.value && props.urls.update) {
        form.put(props.urls.update, { preserveScroll: true });
    } else if (props.urls.store) {
        form.post(props.urls.store);
    }
};
</script>

<template>
    <div data-screen-label="Page" class="contents">
        <PageHeader
            :title="isEdit ? `Edit “${page?.title}”` : 'New page'"
            :description="isEdit ? `/${page?.slug}` : 'Create a static content page.'"
            icon="globe"
        />

        <div class="px-4 sm:px-5 lg:px-7 max-w-[1400px] w-full mx-auto pt-5 pb-7">
            <PageZone region="main" position="before" />

            <form class="max-w-xl space-y-5" @submit.prevent="submit">
                <div>
                    <FieldLabel for="title" required>Title</FieldLabel>
                    <TextInput id="title" v-model="form.title" :invalid="!!form.errors.title" autocomplete="off" />
                    <p v-if="form.errors.title" class="mt-1 text-[11px] text-danger">{{ form.errors.title }}</p>
                </div>

                <div>
                    <FieldLabel for="slug" required :hint="isEdit ? undefined : 'Blank: generated from title'">
                        Slug
                    </FieldLabel>
                    <TextInput id="slug" v-model="form.slug" :invalid="!!form.errors.slug" mono autocomplete="off" />
                    <p v-if="form.errors.slug" class="mt-1 text-[11px] text-danger">{{ form.errors.slug }}</p>
                </div>

                <div>
                    <FieldLabel for="status" required>Status</FieldLabel>
                    <Select id="status" v-model="form.status" :invalid="!!form.errors.status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </Select>
                    <p v-if="form.errors.status" class="mt-1 text-[11px] text-danger">{{ form.errors.status }}</p>
                </div>

                <div>
                    <FieldLabel for="published_at" hint="Set automatically on first publish">
                        Publish date
                    </FieldLabel>
                    <DatePicker v-model="form.published_at" :invalid="!!form.errors.published_at" />
                    <p v-if="form.errors.published_at" class="mt-1 text-[11px] text-danger">
                        {{ form.errors.published_at }}
                    </p>
                </div>

                <div>
                    <FieldLabel hint="Drag to reorder · toggle to draft a block">Content blocks</FieldLabel>
                    <BlockEditor v-model="form.blocks" :media="media" :media-upload-url="urls.media" />
                    <p v-if="form.errors.blocks" class="mt-1 text-[11px] text-danger">{{ form.errors.blocks }}</p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ isEdit ? 'Save changes' : 'Create page' }}
                    </Button>
                    <Link :href="urls.index" class="text-[13px] text-ink-500 hover:text-ink-700">Cancel</Link>
                </div>
            </form>

            <PageZone region="main" position="after" />
        </div>
    </div>
</template>
