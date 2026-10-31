<?php

namespace LunarCms\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Core\Models\Concerns\HasMedia;
use Lunar\Core\Models\Concerns\HasTranslations;
use LunarCms\PageStatus;
use LunarCms\Support\Languages;
use Spatie\MediaLibrary\HasMedia as SpatieHasMedia;

/**
 * `title` and `slug` are `{language code: value}` maps; read them with
 * `translate('title')`, which falls back to the default language. `blocks` is
 * a `{language code: block list}` map, read with `blocksFor()`.
 */
class Page extends Model implements SpatieHasMedia
{
    use HasMedia;
    use HasTranslations;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'status',
        'published_at',
        'blocks',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'title' => 'array',
            'slug' => 'array',
            'blocks' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Media rows/files don't cascade on their own.
        static::deleting(fn (Page $page) => $page->clearMediaCollection(config('lunar.media.collection')));
    }

    /**
     * The block list for one language, falling back to the default language's
     * list while that translation has no blocks yet. Each language owns its
     * own list, so structure can differ between languages.
     *
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public function blocksFor(?string $locale = null): array
    {
        $blocks = $this->blocks ?? [];

        return $blocks[$locale ?? app()->getLocale()]
            ?? $blocks[Languages::defaultCode()]
            ?? [];
    }

    public function publish(): void
    {
        $this->status = PageStatus::Published;
        $this->published_at ??= now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = PageStatus::Draft;
        $this->save();
    }
}
