<?php

namespace LunarCms;

use Illuminate\Support\ServiceProvider;
use Lunar\Panel\Facades\Panel;
use Lunar\Panel\PanelManager;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the package: translations, migrations, panel section, and the
     * Vite module wiring that makes the panel emit this add-on's bundle.
     */
    public function boot(): void
    {
        // The namespace registered here is what CmsSection::langNamespaces()
        // opts in to serving through the panel's translations endpoint.
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'cms');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        // Registration must land during boot — the panel processes sections
        // once at app booted and silently ignores anything later.
        Panel::section(new CmsSection);

        $this->app->make(PanelManager::class)->vite('cms', [
            'input' => 'resources/js/addon.ts',
            'hotFile' => null,
            'buildDirectory' => 'vendor/lunar-panel/cms',
            // Lets `php artisan lunar:panel:link` symlink this package's
            // compiled build/ into public/vendor/lunar-panel/cms.
            '__buildSourcePath' => dirname(__DIR__).'/build',
        ]);
    }
}
