<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    PageHeader,
    PageZone,
    DataTable,
    Pagination,
    StatusBadge,
    Button,
    TextInput,
    FilterDropdown,
} from '@lunarphp/panel';

interface PageRow {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    published_at: string | null;
    _actions?: Record<string, string>;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    pages: Paginated<PageRow>;
    columns: { key: string; label: string; width?: string }[];
    filters: { q?: string; status?: string; sort?: string; direction?: string };
    urls: { index: string; create: string };
}>();

const rowActions = [
    { key: 'edit', label: 'Edit', icon: 'edit', method: 'get', primary: true },
    { key: 'publish', label: 'Publish', icon: 'eye', method: 'post', primary: false },
    { key: 'unpublish', label: 'Unpublish', icon: 'undo', method: 'post', primary: false },
    { key: 'delete', label: 'Delete', icon: 'trash', method: 'delete', primary: false, confirmation: 'Delete this page?' },
];

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
];

const sortOptions = [
    { value: 'recent', label: 'Recently added', sort: 'created_at', direction: 'desc' },
    { value: 'title', label: 'Title', sort: 'title', direction: 'asc' },
    { value: 'published', label: 'Publish date', sort: 'published_at', direction: 'desc' },
];

const q = ref(props.filters.q ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const sortKey = ref(
    sortOptions.find((o) => o.sort === props.filters.sort && o.direction === props.filters.direction)?.value ?? 'recent',
);

const reload = (): void => {
    const sortOption = sortOptions.find((o) => o.value === sortKey.value) ?? sortOptions[0];

    router.get(
        props.urls.index,
        {
            q: q.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            sort: sortOption.sort,
            direction: sortOption.direction,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch([statusFilter, sortKey], reload);

let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(q, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reload, 300);
});

const formatDate = (value: string | null): string => (value ? value.slice(0, 16).replace('T', ' ') : '—');
</script>

<template>
    <div data-screen-label="Pages" class="contents">
        <PageHeader title="Pages" description="Static content pages for the storefront." icon="globe">
            <template #actions>
                <Button variant="primary" icon="plus" @click="router.visit(urls.create)">New page</Button>
            </template>
        </PageHeader>

        <div class="px-4 sm:px-5 lg:px-7 max-w-[1400px] w-full mx-auto pt-5 pb-7">
            <PageZone region="main" position="before" />

            <div class="flex items-center gap-2 mb-3">
                <TextInput v-model="q" placeholder="Search title or slug…" clearable class="w-64" />
                <FilterDropdown v-model="statusFilter" :options="statusOptions" label="Status" default-value="all" />
                <FilterDropdown v-model="sortKey" :options="sortOptions" label="Sort" default-value="recent" />
            </div>

            <DataTable
                :columns="columns"
                :rows="pages.data"
                :row-actions="rowActions"
                :row-to="(row) => row._actions?.edit"
                empty-text="No pages yet"
            >
                <template #cell-status="{ value }">
                    <StatusBadge :tone="value === 'published' ? 'sage' : 'archived'" size="sm">
                        {{ value }}
                    </StatusBadge>
                </template>
                <template #cell-published_at="{ value }">
                    <span class="text-ink-500">{{ formatDate(value as string | null) }}</span>
                </template>
            </DataTable>

            <Pagination :meta="pages" class="mt-4" />

            <PageZone region="main" position="after" />
        </div>
    </div>
</template>
