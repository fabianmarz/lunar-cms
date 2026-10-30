<?php

namespace LunarCms;

use Closure;
use Illuminate\Support\Facades\Route;
use Lunar\Panel\Navigation\NavigationItem;
use Lunar\Panel\Navigation\NavigationRegistry;
use Lunar\Panel\Sections\Section;
use LunarCms\Http\Controllers\PageController;
use LunarCms\Http\Controllers\PageMediaController;

class CmsSection extends Section
{
    /**
     * Manifest permission gating routes (can: middleware) and the navigation
     * item together. Seeded by the pages migration on the staff guard.
     */
    public const PERMISSION = 'cms:manage-pages';

    public function key(): string
    {
        return 'cms';
    }

    public function label(): string
    {
        return __('cms::cms.nav_group');
    }

    public function navigation(NavigationRegistry $registry): void
    {
        $registry->group('content', __('cms::cms.nav_group'));
        $registry->addItem('content', new NavigationItem(
            key: 'pages',
            label: 'cms::cms.nav_pages',
            icon: 'globe',
            route: 'panel.cms.pages.index',
            permission: self::PERMISSION,
        ));
    }

    /**
     * Opt this add-on's lang groups into the panel's translations endpoint, so
     * its Vue pages can translate through vue-i18n with `t('cms::cms.*')`.
     *
     * @return array<int, string>
     */
    public function langNamespaces(): array
    {
        return ['cms'];
    }

    public function routes(): ?Closure
    {
        return function (): void {
            Route::middleware('can:'.self::PERMISSION)
                ->prefix('cms')
                ->name('panel.cms.pages.')
                ->group(function (): void {
                    Route::get('pages', [PageController::class, 'index'])->name('index');
                    Route::get('pages/create', [PageController::class, 'create'])->name('create');
                    Route::post('pages', [PageController::class, 'store'])->name('store');
                    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('edit');
                    Route::put('pages/{page}', [PageController::class, 'update'])->name('update');
                    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('destroy');
                    Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('publish');
                    Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('unpublish');
                    Route::post('pages/{page}/media', [PageMediaController::class, 'store'])->name('media.store');
                });
        };
    }
}
