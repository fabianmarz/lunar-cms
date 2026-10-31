<?php

namespace LunarCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use LunarCms\Http\Requests\PageRequest;
use LunarCms\Models\Page;
use LunarCms\PageStatus;
use LunarCms\Support\Languages;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageController
{
    /** @var list<string> */
    private const array SORTABLE = ['title', 'status', 'published_at', 'created_at'];

    public function index(Request $request): Response
    {
        $sort = in_array($request->string('sort')->value(), self::SORTABLE, true)
            ? $request->string('sort')->value()
            : 'created_at';

        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        $pages = Page::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q')->value().'%';

                $query->where(function ($q) use ($like) {
                    foreach (Languages::codes() as $code) {
                        $q->orWhere("title->{$code}", 'like', $like)->orWhere("slug->{$code}", 'like', $like);
                    }
                });
            })
            ->when(
                in_array($request->string('status')->value(), ['draft', 'published'], true),
                fn ($query) => $query->where('status', $request->string('status')->value()),
            )
            ->orderBy($sort === 'title' ? 'title->'.Languages::defaultCode() : $sort, $direction)
            ->paginate(25)
            ->withQueryString();

        $pages->setCollection($pages->getCollection()->map(function (Page $page) {
            $toggle = $page->status === PageStatus::Published ? 'unpublish' : 'publish';

            return [
                'id' => $page->id,
                'title' => $page->translate('title'),
                'slug' => $page->translate('slug'),
                'status' => $page->status->value,
                'published_at' => $page->published_at?->toDateTimeString(),
                '_actions' => [
                    'edit' => route('panel.cms.pages.edit', $page),
                    $toggle => route("panel.cms.pages.{$toggle}", $page),
                    'delete' => route('panel.cms.pages.destroy', $page),
                ],
            ];
        }));

        return Inertia::render('cms::Pages/Index', [
            'pages' => $pages,
            'columns' => [
                ['key' => 'title', 'label' => 'Title', 'width' => 'minmax(0,1.6fr)'],
                ['key' => 'slug', 'label' => 'Slug', 'width' => 'minmax(0,1.2fr)'],
                ['key' => 'status', 'label' => 'Status', 'width' => '110px'],
                ['key' => 'published_at', 'label' => 'Published', 'width' => '170px'],
            ],
            'filters' => $request->only(['q', 'status', 'sort', 'direction']),
            'urls' => [
                'index' => route('panel.cms.pages.index'),
                'create' => route('panel.cms.pages.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('cms::Pages/Form', [
            'page' => null,
            'media' => [],
            'languages' => Languages::all(),
            'urls' => [
                'store' => route('panel.cms.pages.store'),
                'index' => route('panel.cms.pages.index'),
            ],
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::create($request->validated());

        $this->stampPublishDate($page);

        return redirect()
            ->route('panel.cms.pages.edit', $page)
            ->with('success', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('cms::Pages/Form', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title ?? (object) [],
                'slug' => $page->slug ?? (object) [],
                'status' => $page->status->value,
                'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
                'blocks' => $page->blocks ?: (object) [],
            ],
            'languages' => Languages::all(),
            // Preview URLs for the page's own images plus any library file its
            // image blocks reference — the editor only needs ids and display URLs.
            'media' => $this->blockMedia($page),
            'urls' => [
                'update' => route('panel.cms.pages.update', $page),
                'destroy' => route('panel.cms.pages.destroy', $page),
                'index' => route('panel.cms.pages.index'),
                'media' => route('panel.cms.pages.media.store', $page),
                'mediaPicker' => Route::has('panel.media.picker') ? route('panel.media.picker') : null,
                'mediaLibraryUpload' => Route::has('panel.media.store') ? route('panel.media.store') : null,
            ],
        ]);
    }

    /**
     * @return Collection<int, array{id: int, url: ?string, original_url: string, name: string}>
     */
    private function blockMedia(Page $page): Collection
    {
        $referenced = collect($page->blocks ?? [])
            ->flatten(1)
            ->filter(fn ($block) => ($block['type'] ?? null) === 'image' && ! empty($block['data']['media_id']))
            ->pluck('data.media_id')
            ->map(fn ($id) => (int) $id);

        $own = $page->getMedia(config('lunar.media.collection'))->pluck('id');

        return Media::query()
            ->whereIn('id', $own->merge($referenced)->unique()->all())
            ->get()
            ->mapWithKeys(fn (Media $media) => [$media->id => [
                'id' => $media->id,
                'url' => $media->getAvailableUrl(['small']),
                'original_url' => $media->getUrl(),
                'name' => $media->file_name,
            ]]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        $this->stampPublishDate($page->refresh());

        return back()->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('panel.cms.pages.index')
            ->with('success', 'Page deleted.');
    }

    public function publish(Page $page): RedirectResponse
    {
        $page->publish();

        return back()->with('success', 'Page published.');
    }

    public function unpublish(Page $page): RedirectResponse
    {
        $page->unpublish();

        return back()->with('success', 'Page unpublished.');
    }

    /**
     * A page saved with status "published" gets its publish date stamped once;
     * an explicit date entered in the form is left alone.
     */
    private function stampPublishDate(Page $page): void
    {
        if ($page->status === PageStatus::Published && $page->published_at === null) {
            $page->update(['published_at' => now()]);
        }
    }
}
