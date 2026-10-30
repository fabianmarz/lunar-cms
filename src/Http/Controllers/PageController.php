<?php

namespace LunarCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LunarCms\Http\Requests\PageRequest;
use LunarCms\Models\Page;
use LunarCms\PageStatus;

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

                $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like));
            })
            ->when(
                in_array($request->string('status')->value(), ['draft', 'published'], true),
                fn ($query) => $query->where('status', $request->string('status')->value()),
            )
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        $pages->setCollection($pages->getCollection()->map(function (Page $page) {
            $toggle = $page->status === PageStatus::Published ? 'unpublish' : 'publish';

            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
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
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status->value,
                'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
                'blocks' => $page->blocks ?? [],
            ],
            // Preview URLs for media referenced by the page's blocks — the
            // editor only needs ids and display URLs, not full media models.
            'media' => $page->getMedia('blocks')
                ->mapWithKeys(fn ($media) => [$media->id => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'name' => $media->name,
                ]]),
            'urls' => [
                'update' => route('panel.cms.pages.update', $page),
                'destroy' => route('panel.cms.pages.destroy', $page),
                'index' => route('panel.cms.pages.index'),
                'media' => route('panel.cms.pages.media.store', $page),
            ],
        ]);
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
